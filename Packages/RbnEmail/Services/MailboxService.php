<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnEmail\Services;

use Rbn\Framework\Core\Base\Services\BaseService;
use Rbn\Framework\Packages\RbnEmail\Models\ImapConstant;
use Rbn\Framework\Packages\RbnEmail\Support\ImapConnection;
use Rbn\Framework\Packages\RbnEmail\Support\ImapEnvelope;
use Rbn\Framework\Packages\RbnEmail\Support\TransferDecoder;

/**
 * MailboxService - Posta kutusu iş akışları (klasör rolü, çöp, kalıcı silme, artımlı senkron).
 *
 * VERİTABANINA DOKUNMAZ: açık bir `ImapConnection` alır, sonucu dizi olarak
 * döndürür; kalıcı yazım (önbellek tabloları) çağıran projenin işidir.
 * Böylece aynı akış farklı şemalı projelerde yeniden kullanılır.
 */
class MailboxService extends BaseService
{
    /** Senkronda tek FETCH'te istenecek en çok başlık. */
    private const FETCH_BATCH = 100;

    /** Özet için bölümden okunacak bayt. */
    private const SNIPPET_BYTES = 700;

    /** Ek indirmede tek FETCH'te çekilecek kodlu bayt (1 MB). */
    private const SECTION_CHUNK = 1048576;

    /** Gösterilecek metin/HTML bölümünden okunacak en çok kodlu bayt (5 MB); fazlası kesilir. */
    private const BODY_PART_MAX = 5242880;

    /**
     * Rol → gerçek klasör adı eşlemi. Rolü bulunamayanlar listede yer almaz.
     *
     * @return array{roles:array<string,string>, folders:array, prefix:string, delimiter:string}
     */
    public function resolveFolders(ImapConnection $conn): array
    {
        $folders = $conn->listFolders();
        $roles = [];
        foreach ($folders as $folder) {
            if ($folder['role'] !== null && !isset($roles[$folder['role']])) {
                $roles[$folder['role']] = $folder['name'];
            }
        }
        $roles[ImapConstant::ROLE_INBOX] = $roles[ImapConstant::ROLE_INBOX] ?? 'INBOX';
        $ns = $conn->personalNamespace();
        return ['roles' => $roles, 'folders' => $folders, 'prefix' => $ns['prefix'], 'delimiter' => $ns['delimiter']];
    }

    /**
     * Rolün klasörü yoksa ad alanı önekiyle oluşturur ve abone yapar.
     *
     * @param array $resolved `resolveFolders()` sonucu (yerinde güncellenir)
     */
    public function ensureFolder(ImapConnection $conn, array &$resolved, string $role): string
    {
        if (isset($resolved['roles'][$role])) {
            return $resolved['roles'][$role];
        }
        $base = ImapConstant::DEFAULT_FOLDER_NAMES[$role] ?? ucfirst($role);
        $name = $resolved['prefix'] . $base;
        $conn->createFolder($name);
        $conn->subscribe($name);
        $resolved['roles'][$role] = $name;
        return $name;
    }

    /**
     * Mesajları çöpe taşır.
     *
     * @param int[] $uids
     * @return array<int, int|null> eski UID => Trash'teki yeni UID (bilinmiyorsa null)
     */
    public function moveToTrash(ImapConnection $conn, array &$resolved, string $mailbox, array $uids): array
    {
        $trash = $this->ensureFolder($conn, $resolved, ImapConstant::ROLE_TRASH);
        return $this->moveBetween($conn, $mailbox, $trash, $uids);
    }

    /**
     * Çöpteki mesajları gelen kutusuna geri alır.
     *
     * @param int[] $uids
     * @return array<int, int|null>
     */
    public function restoreFromTrash(ImapConnection $conn, array &$resolved, array $uids): array
    {
        $trash = $this->ensureFolder($conn, $resolved, ImapConstant::ROLE_TRASH);
        return $this->moveBetween($conn, $trash, $resolved['roles'][ImapConstant::ROLE_INBOX], $uids);
    }

    /**
     * Kalıcı siler: `\Deleted` + EXPUNGE (UIDPLUS varsa yalnız bu UID'ler).
     *
     * @param int[] $uids
     */
    public function purge(ImapConnection $conn, string $mailbox, array $uids): void
    {
        if (empty($uids)) {
            return;
        }
        if ($conn->selected() !== $mailbox) {
            $conn->select($mailbox);
        }
        $conn->storeFlags($uids, '+', ['\\Deleted']);
        $conn->expunge($uids);
    }

    /**
     * Klasörü tamamen boşaltır (çöpü boşalt).
     *
     * @return int[] silinen UID'ler
     */
    public function emptyFolder(ImapConnection $conn, string $mailbox): array
    {
        $conn->select($mailbox);
        $uids = $conn->searchUids('ALL');
        $this->purge($conn, $mailbox, $uids);
        return $uids;
    }

    /**
     * Bayrak değiştirir (`\Seen`, `\Flagged`).
     *
     * @param int[] $uids
     */
    public function setFlags(ImapConnection $conn, string $mailbox, array $uids, array $changes): void
    {
        if ($conn->selected() !== $mailbox) {
            $conn->select($mailbox);
        }
        foreach (['seen' => '\\Seen', 'starred' => '\\Flagged'] as $key => $flag) {
            if (array_key_exists($key, $changes) && $changes[$key] !== null) {
                $conn->storeFlags($uids, $changes[$key] ? '+' : '-', [$flag]);
            }
        }
    }

    /**
     * Artımlı senkron: son bilinen UID'den sonrasını başlık olarak döndürür,
     * pencere içindeki bayrakları ve sunucuda hâlâ duran UID'leri bildirir.
     *
     * UIDVALIDITY değişmişse `reset = true` döner ve TÜM klasör yeni sayılır
     * (çağıran bu klasörün önbelleğini temizlemelidir).
     *
     * @param int $lastUid    önbellekteki en büyük UID (yoksa 0)
     * @param int $maxNew     ilk senkronda en çok kaç yeni başlık (en yeniler)
     * @param int $flagWindow bayrakları tazelenecek son N UID
     * @return array{uidvalidity:int, reset:bool, new:array, flags:array<int,array>, server_uids:int[], last_uid:int}
     */
    public function syncFolder(ImapConnection $conn, string $mailbox, ?int $knownValidity, int $lastUid, int $maxNew = 500, int $flagWindow = 500): array
    {
        $info = $conn->select($mailbox, true);
        $reset = $knownValidity !== null && $knownValidity !== 0 && $knownValidity !== $info['uidvalidity'];
        if ($reset) {
            $lastUid = 0;
        }

        $serverUids = $info['exists'] > 0 ? $conn->searchUids('ALL') : [];
        $newUids = array_values(array_filter($serverUids, static fn (int $uid): bool => $uid > $lastUid));
        if (count($newUids) > $maxNew) {
            $newUids = array_slice($newUids, -$maxNew);
        }

        $new = [];
        foreach (array_chunk($newUids, self::FETCH_BATCH) as $chunk) {
            $summaries = $conn->fetchSummaries($chunk);
            $snippets = $this->fetchSnippets($conn, $summaries);
            foreach ($summaries as $uid => $summary) {
                $new[$uid] = self::normalizeSummary($summary, $snippets[$uid] ?? '');
            }
        }

        $known = array_values(array_filter($serverUids, static fn (int $uid): bool => $uid <= $lastUid));
        $window = array_slice($known, -$flagWindow);
        $flags = empty($window) ? [] : $conn->fetchFlags(ImapConnection::uidSet($window));

        return [
            'uidvalidity' => $info['uidvalidity'],
            'reset' => $reset,
            'new' => $new,
            'flags' => $flags,
            'server_uids' => $serverUids,
            'last_uid' => empty($serverUids) ? $lastUid : max($lastUid, (int) end($serverUids)),
        ];
    }

    /**
     * Tek ileti gövdesi: ayrıştırılmış ve (istenirse) temizlenmiş.
     *
     * Ham ileti bütün olarak ÇEKİLMEZ: BODYSTRUCTURE'dan yalnız ilk text/plain ve text/html
     * bölümleri okunur (EMAIL-T1A: 20 MB ekli ileti 128M bellekte açılabilsin). Ek listesi de
     * aynı yapıdan gelir; sırası `saveAttachment()`'ın `$index`'iyle aynıdır.
     *
     * @return array|null html, text, attachments, remote_blocked
     */
    public function readMessage(ImapConnection $conn, string $mailbox, int $uid, bool $allowRemoteImages = false, bool $markSeen = true): ?array
    {
        $conn->select($mailbox, !$markSeen);
        $tokens = $conn->fetchStructure($uid);
        if ($tokens === null) {
            return null;
        }
        $structure = ImapEnvelope::analyzeStructure($tokens);
        $text = $structure['text'] !== null ? $this->readTextPart($conn, $uid, $structure['text']) : '';
        $html = $structure['html'] !== null ? $this->readTextPart($conn, $uid, $structure['html']) : '';
        // MailParserHandler ile aynı: yalnız düz metinse HTML onun kaçışlı hâli, yalnız HTML ise metin etiketsiz hâli.
        if ($html === '' && $text !== '') {
            $html = nl2br(htmlspecialchars($text, ENT_QUOTES, 'UTF-8'));
        } elseif ($text === '' && $html !== '') {
            $text = strip_tags($html);
        }
        $clean = $this->handler('emailSanitizer')->sanitize($html, $allowRemoteImages);
        if ($markSeen) {
            $conn->storeFlags([$uid], '+', ['\\Seen']);
        }
        return [
            'html' => $clean['html'],
            'text' => $text,
            'attachments' => array_map(static fn (array $a) => [
                'filename' => $a['filename'],
                'content_type' => $a['content_type'],
                'size' => ImapEnvelope::decodedSize((int) $a['size'], (string) $a['encoding']),
            ], $structure['attachments']),
            'remote_blocked' => $clean['remote_blocked'],
        ];
    }

    /**
     * Metin bölümünü (en çok `BODY_PART_MAX` kodlu bayt) çekip transfer kodlamasını ve
     * karakter kümesini çözer.
     *
     * @param array{section:string, encoding:string, charset:?string} $part
     */
    private function readTextPart(ImapConnection $conn, int $uid, array $part): string
    {
        $decoder = new TransferDecoder((string) $part['encoding']);
        $out = '';
        $offset = 0;
        while ($offset < self::BODY_PART_MAX) {
            $chunk = $conn->fetchSection($uid, (string) $part['section'], $offset, self::SECTION_CHUNK);
            if ($chunk === null || $chunk === '') {
                break;
            }
            $out .= $decoder->push($chunk);
            $offset += strlen($chunk);
            if (strlen($chunk) < self::SECTION_CHUNK) {
                break;
            }
        }
        return ImapEnvelope::toUtf8($out . $decoder->finish(), $part['charset'] ?? null);
    }

    /**
     * Seçili klasördeki iletinin ekleri (BODYSTRUCTURE sırası; `section`, `encoding`, kodlu `size` dahil).
     *
     * @return array<int, array{section:string, filename:string, content_type:string, size:int, encoding:string}>
     */
    public function attachmentList(ImapConnection $conn, int $uid): array
    {
        return ImapEnvelope::analyzeStructure($conn->fetchStructure($uid))['attachments'];
    }

    /**
     * `$index`'teki eki dilim dilim çekip çözerek `$out` akışına yazar; ileti belleğe alınmaz.
     *
     * @param resource $out yazılabilir akış (ör. geçici dosya)
     * @return array{filename:string, content_type:string, bytes:int}|null ileti ya da ek yoksa null
     * @throws \LengthException kodlu boyut `$maxEncodedBytes`'ı aşarsa
     */
    public function saveAttachment(ImapConnection $conn, string $mailbox, int $uid, int $index, $out, int $maxEncodedBytes): ?array
    {
        $conn->select($mailbox, true);
        $part = $this->attachmentList($conn, $uid)[$index] ?? null;
        if ($part === null) {
            return null;
        }
        if ((int) $part['size'] > $maxEncodedBytes) {
            throw new \LengthException('ATTACHMENT_TOO_LARGE');
        }

        $decoder = new TransferDecoder((string) $part['encoding']);
        $bytes = 0;
        $offset = 0;
        while (true) {
            $chunk = $conn->fetchSection($uid, (string) $part['section'], $offset, self::SECTION_CHUNK);
            if ($chunk === null || $chunk === '') {
                break;
            }
            $offset += strlen($chunk);
            if ($offset > $maxEncodedBytes) {
                // BODYSTRUCTURE boyutu yanlış bildirdiyse de üst sınır tutulur.
                throw new \LengthException('ATTACHMENT_TOO_LARGE');
            }
            $bytes += (int) fwrite($out, $decoder->push($chunk));
            if (strlen($chunk) < self::SECTION_CHUNK) {
                break;
            }
        }
        $bytes += (int) fwrite($out, $decoder->finish());

        return ['filename' => (string) $part['filename'], 'content_type' => (string) $part['content_type'], 'bytes' => $bytes];
    }

    /**
     * FETCH özetini depolanabilir düz satıra çevirir.
     */
    public static function normalizeSummary(array $summary, string $snippet = ''): array
    {
        $env = $summary['envelope'];
        $date = $env['date'] ?? null;
        $ts = $date ? strtotime((string) $date) : false;
        if ($ts === false && !empty($summary['internal_date'])) {
            $ts = strtotime((string) $summary['internal_date']);
        }
        return [
            'uid' => (int) $summary['uid'],
            'message_id' => $env['message_id'] ?? null,
            'in_reply_to' => $env['in_reply_to'] ?? null,
            'references' => $summary['references'] ?? '',
            'from' => $env['from'][0] ?? ['email' => '', 'name' => ''],
            'to' => $env['to'] ?? [],
            'cc' => $env['cc'] ?? [],
            'reply_to' => $env['reply_to'] ?? [],
            'subject' => (string) ($env['subject'] ?? ''),
            'date' => date('Y-m-d H:i:s', $ts !== false ? $ts : time()),
            'size' => (int) ($summary['size'] ?? 0),
            'has_attachments' => !empty($summary['structure']['attachments']),
            'is_read' => in_array('\\seen', $summary['flags'], true),
            'is_starred' => in_array('\\flagged', $summary['flags'], true),
            'snippet' => $snippet,
        ];
    }

    /**
     * @param int[] $uids
     * @return array<int, int|null>
     */
    private function moveBetween(ImapConnection $conn, string $from, string $to, array $uids): array
    {
        if (empty($uids)) {
            return [];
        }
        if ($conn->selected() !== $from) {
            $conn->select($from);
        }
        $map = $conn->move($uids, $to);
        $out = [];
        foreach ($uids as $uid) {
            $out[$uid] = $map[$uid] ?? null;
        }
        return $out;
    }

    /**
     * Her ileti için ilk düz metin (yoksa HTML) bölümünden kısa özet.
     *
     * @return array<int, string>
     */
    private function fetchSnippets(ImapConnection $conn, array $summaries): array
    {
        $groups = [];
        foreach ($summaries as $uid => $summary) {
            $part = $summary['structure']['text'] ?? $summary['structure']['html'] ?? null;
            if ($part === null) {
                continue;
            }
            $part['is_html'] = ($summary['structure']['text'] ?? null) === null;
            $groups[$part['section']][$uid] = $part;
        }

        $out = [];
        foreach ($groups as $section => $parts) {
            $raw = $conn->fetchPartial(array_keys($parts), (string) $section, self::SNIPPET_BYTES);
            foreach ($raw as $uid => $bytes) {
                $p = $parts[$uid] ?? null;
                if ($p !== null) {
                    $out[$uid] = ImapEnvelope::snippet($bytes, (string) $p['encoding'], $p['charset'], (bool) $p['is_html']);
                }
            }
        }
        return $out;
    }
}
