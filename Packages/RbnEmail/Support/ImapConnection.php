<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnEmail\Support;

use Rbn\Framework\Core\Support\Exceptions\MailTransportException;
use Rbn\Framework\Packages\RbnEmail\Models\ImapConstant;

/**
 * ImapConnection - Tek bir posta kutusuna açılmış IMAP4rev1 oturumu.
 *
 * ext-imap KULLANMAZ: `stream_socket_client` + openssl. Sertifika ve ana
 * makine adı doğrulaması HER ZAMAN açıktır (kapatma seçeneği yoktur).
 * Nesne kısa ömürlüdür: aç → işlemler → `logout()`. Hesap başına tek bağlantı.
 *
 * Yanıt okuyucu `{n}` literal'lerini bayt sayısıyla okur; böylece gövde ya da
 * başlık içindeki satır sonları, parantezler ve etiket benzeri dizgeler yanıt
 * sınırını bozmaz.
 */
class ImapConnection
{
    /** @var resource|null */
    private $socket = null;
    private int $tagIndex = 0;
    private array $capabilities = [];
    private ?string $selected = null;
    private string $host;

    /**
     * @param array $config host, port, security (ssl|starttls), timeout?, ca_file?
     */
    public function __construct(array $config)
    {
        $this->host = (string) ($config['host'] ?? '');
        $port = (int) ($config['port'] ?? 993);
        $security = (string) ($config['security'] ?? ImapConstant::SECURITY_SSL);
        $timeout = (int) ($config['timeout'] ?? ImapConstant::TIMEOUT);

        if ($this->host === '' || !in_array($security, ImapConstant::SECURITIES, true)) {
            throw MailTransportException::unavailable('geçersiz sunucu ayarı');
        }

        $context = stream_context_create(['ssl' => self::tlsOptions($this->host, $config['ca_file'] ?? null)]);
        $scheme = $security === ImapConstant::SECURITY_SSL ? 'ssl' : 'tcp';

        $this->socket = self::openSocket("{$scheme}://{$this->host}:{$port}", $timeout, $context, $this->host);
        stream_set_timeout($this->socket, $timeout);

        $greeting = $this->readResponse();
        if (!str_starts_with($greeting, '* OK') && !str_starts_with($greeting, '* PREAUTH')) {
            $this->close();
            throw MailTransportException::unavailable('karşılama yok');
        }
        $this->captureCapabilities($greeting);

        if ($security === ImapConstant::SECURITY_STARTTLS) {
            $this->expectOk($this->command('STARTTLS'), 'STARTTLS');
            self::enableCrypto($this->socket, $this->host);
            $this->capabilities = [];
        }
    }

    /**
     * TLS bağlam seçenekleri — doğrulama AÇIK, ana makine adı eşleşmesi zorunlu.
     */
    public static function tlsOptions(string $host, ?string $caFile = null): array
    {
        $options = [
            'verify_peer' => true,
            'verify_peer_name' => true,
            'allow_self_signed' => false,
            'peer_name' => $host,
            'SNI_enabled' => true,
            'disable_compression' => true,
        ];
        if ($caFile !== null && $caFile !== '' && is_file($caFile)) {
            $options['cafile'] = $caFile;
        }
        return $options;
    }

    /**
     * Soket açar; TLS doğrulama hatasını erişim hatasından ayırır.
     *
     * @return resource
     */
    public static function openSocket(string $address, int $timeout, $context, string $host)
    {
        $warning = '';
        set_error_handler(static function (int $no, string $str) use (&$warning): bool {
            $warning .= $str . ' ';
            return true;
        });
        try {
            $socket = stream_socket_client($address, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $context);
        } finally {
            restore_error_handler();
        }

        if (!is_resource($socket)) {
            if (self::looksLikeTlsFailure($warning . ' ' . ($errstr ?? ''))) {
                throw MailTransportException::tls($host);
            }
            throw MailTransportException::unavailable($errno ? "bağlantı kodu {$errno}" : 'bağlantı kurulamadı');
        }

        return $socket;
    }

    /**
     * Açık düz bağlantıyı TLS'e yükseltir (STARTTLS).
     *
     * @param resource $socket
     */
    public static function enableCrypto($socket, string $host): void
    {
        $warning = '';
        set_error_handler(static function (int $no, string $str) use (&$warning): bool {
            $warning .= $str . ' ';
            return true;
        });
        try {
            $ok = stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT);
        } finally {
            restore_error_handler();
        }
        if ($ok !== true) {
            throw self::looksLikeTlsFailure($warning) || $warning === ''
                ? MailTransportException::tls($host)
                : MailTransportException::unavailable('TLS el sıkışması');
        }
    }

    private static function looksLikeTlsFailure(string $text): bool
    {
        return (bool) preg_match('/certificate|verify|peer|SSL operation failed|handshake|crypto/i', $text);
    }

    // =====================================================================
    // Oturum
    // =====================================================================

    /**
     * Kimlik doğrular. SASL-IR + AUTH=PLAIN varsa AUTHENTICATE PLAIN, yoksa LOGIN.
     * Parola hiçbir loga/istisnaya yazılmaz.
     */
    public function login(string $user, string $password): void
    {
        $caps = $this->capabilities();
        if (in_array('AUTH=PLAIN', $caps, true) && in_array('SASL-IR', $caps, true)) {
            $res = $this->command('AUTHENTICATE PLAIN ' . base64_encode("\0{$user}\0{$password}"));
        } else {
            $res = $this->command('LOGIN ' . $this->quote($user) . ' ' . $this->quote($password));
        }

        if ($res['status'] !== 'OK') {
            throw MailTransportException::auth();
        }
        $this->capabilities = [];
        $this->captureCapabilities($res['text']);
    }

    public function capabilities(): array
    {
        if (empty($this->capabilities)) {
            $res = $this->command('CAPABILITY');
            foreach ($res['untagged'] as $line) {
                $this->captureCapabilities($line);
            }
        }
        return $this->capabilities;
    }

    public function hasCapability(string $name): bool
    {
        return in_array(strtoupper($name), $this->capabilities(), true);
    }

    public function logout(): void
    {
        if (is_resource($this->socket)) {
            try {
                $this->command('LOGOUT');
            } catch (\Throwable) {
                // Kapanışta hata önemsizdir.
            }
        }
        $this->close();
    }

    public function close(): void
    {
        if (is_resource($this->socket)) {
            @fclose($this->socket);
        }
        $this->socket = null;
        $this->selected = null;
    }

    public function __destruct()
    {
        $this->close();
    }

    // =====================================================================
    // Klasörler
    // =====================================================================

    /**
     * Ad alanı öneki ve ayraç (NAMESPACE yoksa LIST "" "" ile ayraç).
     *
     * @return array{prefix:string, delimiter:string}
     */
    public function personalNamespace(): array
    {
        if ($this->hasCapability('NAMESPACE')) {
            $res = $this->command('NAMESPACE');
            foreach ($res['untagged'] as $line) {
                $tokens = self::parse($line);
                // * NAMESPACE (("INBOX." ".")) NIL NIL
                if (($tokens[1] ?? '') === 'NAMESPACE' && is_array($tokens[2] ?? null) && is_array($tokens[2][0] ?? null)) {
                    return ['prefix' => (string) ($tokens[2][0][0] ?? ''), 'delimiter' => (string) ($tokens[2][0][1] ?? '.')];
                }
            }
        }
        $res = $this->command('LIST "" ""');
        foreach ($res['untagged'] as $line) {
            $tokens = self::parse($line);
            if (($tokens[1] ?? '') === 'LIST') {
                return ['prefix' => '', 'delimiter' => (string) ($tokens[3] ?? '.')];
            }
        }
        return ['prefix' => '', 'delimiter' => '.'];
    }

    /**
     * Tüm klasörler: ham (UTF-7) ad, okunur (UTF-8) ad, öznitelikler, rol.
     *
     * @return array<int, array{name:string, display:string, delimiter:string, attributes:array, role:?string}>
     */
    public function listFolders(): array
    {
        $res = $this->expectOk($this->command('LIST "" "*"'), 'LIST');
        $folders = [];
        foreach ($res['untagged'] as $line) {
            $tokens = self::parse($line);
            if (($tokens[1] ?? '') !== 'LIST') {
                continue;
            }
            $attributes = array_map('strtolower', is_array($tokens[2] ?? null) ? $tokens[2] : []);
            if (in_array('\\noselect', $attributes, true) || in_array('\\nonexistent', $attributes, true)) {
                continue;
            }
            $name = (string) ($tokens[4] ?? '');
            $delimiter = (string) ($tokens[3] ?? '.');
            $folders[] = [
                'name' => $name,
                'display' => self::decodeMailboxName($name),
                'delimiter' => $delimiter,
                'attributes' => $attributes,
                'role' => self::detectRole($name, $delimiter, $attributes),
            ];
        }
        return $folders;
    }

    /**
     * Klasör rolünü SPECIAL-USE özniteliğinden, yoksa son ad bölümünden çıkarır.
     */
    public static function detectRole(string $name, string $delimiter, array $attributes): ?string
    {
        if (strcasecmp($name, 'INBOX') === 0) {
            return ImapConstant::ROLE_INBOX;
        }
        foreach ($attributes as $attr) {
            if (isset(ImapConstant::SPECIAL_USE[$attr])) {
                return ImapConstant::SPECIAL_USE[$attr];
            }
        }
        $display = self::decodeMailboxName($name);
        $parts = $delimiter !== '' ? explode($delimiter, $display) : [$display];
        $leaf = mb_strtolower((string) end($parts), 'UTF-8');
        foreach (ImapConstant::ROLE_NAMES as $role => $names) {
            if (in_array($leaf, $names, true)) {
                return $role;
            }
        }
        return null;
    }

    public function createFolder(string $name): void
    {
        $res = $this->command('CREATE ' . $this->quote($name));
        // Zaten varsa NO [ALREADYEXISTS] döner; bu durum hata değildir.
        if ($res['status'] !== 'OK' && stripos($res['text'], 'ALREADYEXISTS') === false && stripos($res['text'], 'exists') === false) {
            throw MailTransportException::protocol('CREATE');
        }
    }

    public function subscribe(string $name): void
    {
        $this->command('SUBSCRIBE ' . $this->quote($name));
    }

    /**
     * Klasörü seçer (yazılabilir) ya da inceler (salt okunur).
     *
     * @return array{exists:int, uidvalidity:int, uidnext:int}
     */
    public function select(string $mailbox, bool $readOnly = false): array
    {
        $res = $this->expectOk($this->command(($readOnly ? 'EXAMINE ' : 'SELECT ') . $this->quote($mailbox)), 'SELECT');
        $info = ['exists' => 0, 'uidvalidity' => 0, 'uidnext' => 0];
        foreach ($res['untagged'] as $line) {
            if (preg_match('/^\* (\d+) EXISTS/i', $line, $m)) {
                $info['exists'] = (int) $m[1];
            } elseif (preg_match('/\[UIDVALIDITY (\d+)\]/i', $line, $m)) {
                $info['uidvalidity'] = (int) $m[1];
            } elseif (preg_match('/\[UIDNEXT (\d+)\]/i', $line, $m)) {
                $info['uidnext'] = (int) $m[1];
            }
        }
        // EXAMINE salt okunurdur; yazma işlemleri yeniden SELECT etsin diye seçili sayılmaz.
        $this->selected = $readOnly ? null : $mailbox;
        return $info;
    }

    public function selected(): ?string
    {
        return $this->selected;
    }

    // =====================================================================
    // Mesajlar
    // =====================================================================

    /**
     * `UID SEARCH` — ölçüt ham IMAP söz dizimidir (ör. `UID 120:*`, `ALL`).
     *
     * @return int[]
     */
    public function searchUids(string $criteria): array
    {
        $res = $this->expectOk($this->command('UID SEARCH ' . $criteria), 'SEARCH');
        $uids = [];
        foreach ($res['untagged'] as $line) {
            if (preg_match('/^\* SEARCH\s*(.*)$/i', trim($line), $m) && trim($m[1]) !== '') {
                foreach (preg_split('/\s+/', trim($m[1])) as $uid) {
                    $uids[] = (int) $uid;
                }
            }
        }
        sort($uids);
        return $uids;
    }

    /**
     * Başlık özetleri: UID, bayraklar, tarih, boyut, ENVELOPE, BODYSTRUCTURE, References.
     *
     * @param int[] $uids
     * @return array<int, array> UID anahtarlı
     */
    public function fetchSummaries(array $uids): array
    {
        if (empty($uids)) {
            return [];
        }
        $res = $this->expectOk($this->command(
            'UID FETCH ' . self::uidSet($uids)
            . ' (UID FLAGS INTERNALDATE RFC822.SIZE ENVELOPE BODYSTRUCTURE BODY.PEEK[HEADER.FIELDS (REFERENCES)])'
        ), 'FETCH');

        $out = [];
        foreach ($this->fetchItems($res['untagged']) as $item) {
            if (!isset($item['UID'])) {
                continue;
            }
            $uid = (int) $item['UID'];
            $envelope = ImapEnvelope::fromTokens(is_array($item['ENVELOPE'] ?? null) ? $item['ENVELOPE'] : []);
            $structure = ImapEnvelope::analyzeStructure($item['BODYSTRUCTURE'] ?? null);
            $references = '';
            foreach ($item as $key => $value) {
                if (str_starts_with($key, 'BODY[HEADER.FIELDS') && is_string($value)) {
                    $references = trim((string) preg_replace('/^references:\s*/i', '', str_replace(["\r\n ", "\r\n\t"], ' ', trim($value))));
                }
            }
            $out[$uid] = [
                'uid' => $uid,
                'flags' => array_map('strtolower', is_array($item['FLAGS'] ?? null) ? $item['FLAGS'] : []),
                'internal_date' => isset($item['INTERNALDATE']) ? (string) $item['INTERNALDATE'] : null,
                'size' => (int) ($item['RFC822.SIZE'] ?? 0),
                'envelope' => $envelope,
                'structure' => $structure,
                'references' => $references,
            ];
        }
        return $out;
    }

    /**
     * Yalnız bayraklar (eşitleme için).
     *
     * @return array<int, string[]> UID => bayraklar (küçük harf)
     */
    public function fetchFlags(string $uidSet): array
    {
        $res = $this->expectOk($this->command("UID FETCH {$uidSet} (UID FLAGS)"), 'FETCH');
        $out = [];
        foreach ($this->fetchItems($res['untagged']) as $item) {
            if (isset($item['UID'])) {
                $out[(int) $item['UID']] = array_map('strtolower', is_array($item['FLAGS'] ?? null) ? $item['FLAGS'] : []);
            }
        }
        return $out;
    }

    /**
     * Tam ham ileti (RFC 822). `\Seen` DEĞİŞTİRMEZ (PEEK).
     */
    public function fetchRaw(int $uid): ?string
    {
        $res = $this->expectOk($this->command("UID FETCH {$uid} (UID BODY.PEEK[])"), 'FETCH');
        foreach ($this->fetchItems($res['untagged']) as $item) {
            if ((int) ($item['UID'] ?? 0) === $uid && isset($item['BODY[]']) && is_string($item['BODY[]'])) {
                return $item['BODY[]'];
            }
        }
        return null;
    }

    /**
     * Tek iletinin ham BODYSTRUCTURE sözcükleri (`ImapEnvelope::analyzeStructure()` girdisi).
     */
    public function fetchStructure(int $uid): mixed
    {
        $res = $this->expectOk($this->command("UID FETCH {$uid} (UID BODYSTRUCTURE)"), 'FETCH');
        foreach ($this->fetchItems($res['untagged']) as $item) {
            if ((int) ($item['UID'] ?? 0) === $uid && isset($item['BODYSTRUCTURE'])) {
                return $item['BODYSTRUCTURE'];
            }
        }
        return null;
    }

    /**
     * Bir bölümün `$offset`'ten başlayan en çok `$length` baytı (kodlu, ham). `\Seen` DEĞİŞTİRMEZ.
     * Bölüm sonu geçildiyse boş dizge; ileti yoksa null.
     */
    public function fetchSection(int $uid, string $section, int $offset, int $length): ?string
    {
        if (!preg_match('/^[0-9]+(\.[0-9]+)*$/', $section) || $offset < 0 || $length < 1) {
            return null;
        }
        $res = $this->expectOk($this->command("UID FETCH {$uid} (UID BODY.PEEK[{$section}]<{$offset}.{$length}>)"), 'FETCH');
        foreach ($this->fetchItems($res['untagged']) as $item) {
            if ((int) ($item['UID'] ?? 0) !== $uid) {
                continue;
            }
            foreach ($item as $key => $value) {
                if (str_starts_with($key, "BODY[{$section}]")) {
                    return is_string($value) ? $value : '';
                }
            }
        }
        return null;
    }

    /**
     * Bir gövde bölümünün ilk `$length` baytı (özet çıkarmak için).
     *
     * @param int[] $uids
     * @return array<int, string> UID => ham bayt
     */
    public function fetchPartial(array $uids, string $section, int $length): array
    {
        if (empty($uids) || !preg_match('/^[0-9.]+$|^TEXT$/', $section)) {
            return [];
        }
        $res = $this->command('UID FETCH ' . self::uidSet($uids) . " (UID BODY.PEEK[{$section}]<0.{$length}>)");
        if ($res['status'] !== 'OK') {
            return [];
        }
        $out = [];
        foreach ($this->fetchItems($res['untagged']) as $item) {
            foreach ($item as $key => $value) {
                if (str_starts_with($key, "BODY[{$section}]") && is_string($value) && isset($item['UID'])) {
                    $out[(int) $item['UID']] = $value;
                }
            }
        }
        return $out;
    }

    /**
     * Bayrak ekler/çıkarır. `$mode`: `+` ya da `-`.
     *
     * @param int[] $uids
     */
    public function storeFlags(array $uids, string $mode, array $flags): void
    {
        if (empty($uids)) {
            return;
        }
        $op = $mode === '-' ? '-FLAGS.SILENT' : '+FLAGS.SILENT';
        $this->expectOk($this->command('UID STORE ' . self::uidSet($uids) . " {$op} (" . implode(' ', $flags) . ')'), 'STORE');
    }

    /**
     * Mesajları başka klasöre taşır. MOVE yoksa COPY + \Deleted + EXPUNGE.
     *
     * @param int[] $uids
     * @return array<int, int> eski UID => hedefteki yeni UID (sunucu UIDPLUS bildirdiyse)
     */
    public function move(array $uids, string $destination): array
    {
        if (empty($uids)) {
            return [];
        }
        $set = self::uidSet($uids);
        if ($this->hasCapability('MOVE')) {
            $res = $this->expectOk($this->command("UID MOVE {$set} " . $this->quote($destination)), 'MOVE');
            return self::parseCopyUid(implode("\n", array_merge($res['untagged'], [$res['text']])));
        }

        $res = $this->expectOk($this->command("UID COPY {$set} " . $this->quote($destination)), 'COPY');
        $map = self::parseCopyUid($res['text']);
        $this->storeFlags($uids, '+', ['\\Deleted']);
        $this->expunge($uids);
        return $map;
    }

    /**
     * `\Deleted` işaretlileri kalıcı siler. UIDPLUS varsa yalnız verilen UID'ler.
     *
     * @param int[]|null $uids
     */
    public function expunge(?array $uids = null): void
    {
        if ($uids !== null && !empty($uids) && $this->hasCapability('UIDPLUS')) {
            $this->expectOk($this->command('UID EXPUNGE ' . self::uidSet($uids)), 'EXPUNGE');
            return;
        }
        $this->expectOk($this->command('EXPUNGE'), 'EXPUNGE');
    }

    /**
     * Ham iletiyi klasöre ekler (gönderilenler için).
     *
     * @return int|null yeni UID (APPENDUID bildirildiyse)
     */
    public function append(string $mailbox, string $rawMessage, array $flags = ['\\Seen']): ?int
    {
        $rawMessage = (string) preg_replace("/\r?\n/", "\r\n", $rawMessage);
        $tag = $this->nextTag();
        $this->write("{$tag} APPEND " . $this->quote($mailbox) . ' (' . implode(' ', $flags) . ') {' . strlen($rawMessage) . "}\r\n");

        $first = $this->readResponse();
        if (!str_starts_with($first, '+')) {
            throw MailTransportException::protocol('APPEND');
        }
        $this->write($rawMessage . "\r\n");

        while (true) {
            $line = $this->readResponse();
            if (str_starts_with($line, $tag . ' ')) {
                if (!preg_match('/^' . preg_quote($tag, '/') . ' OK/i', $line)) {
                    throw MailTransportException::protocol('APPEND');
                }
                return preg_match('/\[APPENDUID \d+ (\d+)\]/i', $line, $m) ? (int) $m[1] : null;
            }
        }
    }

    // =====================================================================
    // Protokol katmanı
    // =====================================================================

    /**
     * Komutu gönderir, etiketli yanıta kadar okur.
     *
     * @return array{status:string, text:string, untagged:string[]}
     */
    public function command(string $command): array
    {
        $tag = $this->nextTag();
        $this->write("{$tag} {$command}\r\n");

        $untagged = [];
        while (true) {
            $response = $this->readResponse();
            if (str_starts_with($response, $tag . ' ')) {
                $rest = substr($response, strlen($tag) + 1);
                $status = strtoupper((string) strtok($rest, ' '));
                return ['status' => $status, 'text' => trim(substr($rest, strlen($status))), 'untagged' => $untagged];
            }
            if (str_starts_with($response, '+')) {
                // Beklenmeyen devam isteği: komutu iptal et.
                $this->write("\r\n");
                continue;
            }
            $untagged[] = $response;
        }
    }

    private function expectOk(array $res, string $what): array
    {
        if ($res['status'] !== 'OK') {
            throw MailTransportException::protocol($what);
        }
        return $res;
    }

    private function nextTag(): string
    {
        return 'R' . str_pad((string) ++$this->tagIndex, 4, '0', STR_PAD_LEFT);
    }

    private function write(string $data): void
    {
        if (!is_resource($this->socket)) {
            throw MailTransportException::unavailable('bağlantı kapalı');
        }
        $length = strlen($data);
        $written = 0;
        while ($written < $length) {
            $n = @fwrite($this->socket, substr($data, $written));
            if ($n === false || $n === 0) {
                $this->close();
                throw MailTransportException::unavailable('yazma');
            }
            $written += $n;
        }
    }

    /**
     * Tek bir tam yanıt okur: satır + varsa literal baytları + devam satırları.
     */
    private function readResponse(): string
    {
        $buffer = '';
        while (true) {
            $line = $this->readLine();
            $buffer .= $line;
            if (preg_match('/\{(\d+)\+?\}\r?\n$/', $line, $m)) {
                $size = (int) $m[1];
                if ($size > ImapConstant::MAX_LITERAL_BYTES) {
                    $this->close();
                    throw MailTransportException::protocol('literal çok büyük');
                }
                $buffer .= $this->readBytes($size);
                continue;
            }
            return $buffer;
        }
    }

    private function readLine(): string
    {
        if (!is_resource($this->socket)) {
            throw MailTransportException::unavailable('bağlantı kapalı');
        }
        $line = '';
        while (!str_ends_with($line, "\n")) {
            $chunk = fgets($this->socket, ImapConstant::LINE_CHUNK);
            if ($chunk === false) {
                $this->failRead();
            }
            $line .= $chunk;
        }
        return $line;
    }

    private function readBytes(int $size): string
    {
        $data = '';
        while (strlen($data) < $size) {
            $chunk = fread($this->socket, min(65536, $size - strlen($data)));
            if ($chunk === false || ($chunk === '' && feof($this->socket))) {
                $this->failRead();
            }
            if ($chunk === '') {
                $meta = stream_get_meta_data($this->socket);
                if (!empty($meta['timed_out'])) {
                    $this->failRead();
                }
            }
            $data .= $chunk;
        }
        return $data;
    }

    private function failRead(): never
    {
        $timedOut = is_resource($this->socket) && !empty(stream_get_meta_data($this->socket)['timed_out']);
        $this->close();
        throw MailTransportException::unavailable($timedOut ? 'zaman aşımı' : 'bağlantı koptu');
    }

    private function captureCapabilities(string $text): void
    {
        if (preg_match('/CAPABILITY ([^\]\r\n]+)/i', $text, $m)) {
            $this->capabilities = array_values(array_filter(array_map('strtoupper', preg_split('/\s+/', trim($m[1])))));
        }
    }

    /**
     * Dizgeyi IMAP quoted-string ya da (8 bit/denetim karakteri varsa) literal yapar.
     * CR/LF/NUL içeren değer reddedilir: quoted-string içinde komut satırını bölüp
     * aynı oturuma ikinci bir komut sokabilir (RFC 3501 quoted-string bunlara izin vermez).
     *
     * @throws MailTransportException PROTOCOL
     */
    private function quote(string $value): string
    {
        if (preg_match('/[\x00\r\n]/', $value)) {
            throw MailTransportException::protocol('değerde CR/LF/NUL');
        }
        if (preg_match('/[\x00-\x1f\x7f-\xff]/', $value)) {
            // Literal+ yoksa da güvenli: çoğu sunucu LITERAL+ destekler; yoksa quoted'a düşeriz.
            if ($this->hasCapability('LITERAL+')) {
                return '{' . strlen($value) . "+}\r\n" . $value;
            }
        }
        return '"' . addcslashes($value, "\"\\") . '"';
    }

    /**
     * FETCH yanıtlarını `ANAHTAR => değer` dizilerine çevirir.
     *
     * @param string[] $untagged
     * @return array<int, array>
     */
    private function fetchItems(array $untagged): array
    {
        $items = [];
        foreach ($untagged as $line) {
            if (!preg_match('/^\* \d+ FETCH /i', $line)) {
                continue;
            }
            $tokens = self::parse($line);
            $list = is_array($tokens[3] ?? null) ? $tokens[3] : [];
            $item = [];
            for ($i = 0; $i + 1 < count($list); $i += 2) {
                $item[strtoupper((string) $list[$i])] = $list[$i + 1];
            }
            $items[] = $item;
        }
        return $items;
    }

    // =====================================================================
    // Yardımcılar (durumsuz)
    // =====================================================================

    /**
     * IMAP yanıtını sözcüklere ayırır: atom, quoted, literal, NIL (null), liste (dizi).
     */
    public static function parse(string $text): array
    {
        $pos = 0;
        return self::parseList($text, $pos, false);
    }

    private static function parseList(string $s, int &$i, bool $nested): array
    {
        $out = [];
        $len = strlen($s);
        while ($i < $len) {
            $c = $s[$i];
            if ($c === ' ' || $c === "\r" || $c === "\n") {
                $i++;
                continue;
            }
            if ($c === ')') {
                $i++;
                if ($nested) {
                    return $out;
                }
                continue;
            }
            if ($c === '(') {
                $i++;
                $out[] = self::parseList($s, $i, true);
                continue;
            }
            if ($c === '"') {
                $i++;
                $value = '';
                while ($i < $len && $s[$i] !== '"') {
                    if ($s[$i] === '\\' && $i + 1 < $len) {
                        $i++;
                    }
                    $value .= $s[$i];
                    $i++;
                }
                $i++;
                $out[] = $value;
                continue;
            }
            if ($c === '{' && preg_match('/\G\{(\d+)\+?\}\r?\n/', $s, $m, 0, $i)) {
                $i += strlen($m[0]);
                $out[] = substr($s, $i, (int) $m[1]);
                $i += (int) $m[1];
                continue;
            }
            // Atom; `[` içindeki boşluklar ve parantezler atomun parçasıdır (BODY[HEADER.FIELDS (X)]).
            $atom = '';
            $depth = 0;
            while ($i < $len) {
                $ch = $s[$i];
                if ($ch === '[') {
                    $depth++;
                } elseif ($ch === ']') {
                    $depth--;
                } elseif ($depth <= 0 && ($ch === ' ' || $ch === '(' || $ch === ')' || $ch === "\r" || $ch === "\n")) {
                    break;
                }
                $atom .= $ch;
                $i++;
            }
            $out[] = strtoupper($atom) === 'NIL' ? null : $atom;
        }
        return $out;
    }

    /**
     * UID listesini aralıklı kümeye sıkıştırır: [1,2,3,7] → "1:3,7".
     *
     * @param int[] $uids
     */
    public static function uidSet(array $uids): string
    {
        $uids = array_values(array_unique(array_map('intval', $uids)));
        sort($uids);
        $parts = [];
        $start = $prev = null;
        foreach ($uids as $uid) {
            if ($start === null) {
                $start = $prev = $uid;
                continue;
            }
            if ($uid === $prev + 1) {
                $prev = $uid;
                continue;
            }
            $parts[] = $start === $prev ? (string) $start : "{$start}:{$prev}";
            $start = $prev = $uid;
        }
        if ($start !== null) {
            $parts[] = $start === $prev ? (string) $start : "{$start}:{$prev}";
        }
        return implode(',', $parts);
    }

    /**
     * `[COPYUID <validity> <kaynak-küme> <hedef-küme>]` → eski UID => yeni UID.
     *
     * @return array<int, int>
     */
    public static function parseCopyUid(string $text): array
    {
        if (!preg_match('/\[COPYUID \d+ ([0-9:,]+) ([0-9:,]+)\]/i', $text, $m)) {
            return [];
        }
        $src = self::expandSet($m[1]);
        $dst = self::expandSet($m[2]);
        return count($src) === count($dst) ? array_combine($src, $dst) : [];
    }

    /** @return int[] */
    private static function expandSet(string $set): array
    {
        $out = [];
        foreach (explode(',', $set) as $part) {
            if (str_contains($part, ':')) {
                [$a, $b] = array_map('intval', explode(':', $part, 2));
                for ($n = min($a, $b); $n <= max($a, $b); $n++) {
                    $out[] = $n;
                }
            } else {
                $out[] = (int) $part;
            }
        }
        return $out;
    }

    /** Değiştirilmiş UTF-7 (RFC 3501 §5.1.3) → UTF-8. */
    public static function decodeMailboxName(string $name): string
    {
        if (!str_contains($name, '&')) {
            return $name;
        }
        $decoded = @mb_convert_encoding($name, 'UTF-8', 'UTF7-IMAP');
        return is_string($decoded) && $decoded !== '' ? $decoded : $name;
    }

    /** UTF-8 → değiştirilmiş UTF-7. */
    public static function encodeMailboxName(string $name): string
    {
        if (!preg_match('/[^\x20-\x7e]|&/', $name)) {
            return $name;
        }
        $encoded = @mb_convert_encoding($name, 'UTF7-IMAP', 'UTF-8');
        return is_string($encoded) && $encoded !== '' ? $encoded : $name;
    }
}
