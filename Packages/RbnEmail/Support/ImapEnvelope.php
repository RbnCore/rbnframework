<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnEmail\Support;

/**
 * ImapEnvelope - IMAP ENVELOPE / BODYSTRUCTURE çözümleyicisi ve başlık metni çözücü.
 *
 * Durumsuzdur; `ImapConnection` ayrıştırdığı sözcük dizilerini buraya verir.
 */
class ImapEnvelope
{
    /**
     * ENVELOPE → düz dizi.
     * Sıra (RFC 3501): date, subject, from, sender, reply-to, to, cc, bcc, in-reply-to, message-id.
     */
    public static function fromTokens(array $env): array
    {
        return [
            'date' => isset($env[0]) ? (string) $env[0] : null,
            'subject' => self::decodeHeader((string) ($env[1] ?? '')),
            'from' => self::addresses($env[2] ?? null),
            'reply_to' => self::addresses($env[4] ?? null),
            'to' => self::addresses($env[5] ?? null),
            'cc' => self::addresses($env[6] ?? null),
            'in_reply_to' => isset($env[8]) ? trim((string) $env[8]) : null,
            'message_id' => isset($env[9]) ? trim((string) $env[9]) : null,
        ];
    }

    /**
     * Adres listesi: ((ad kaynak-yolu posta-kutusu ana-makine) ...) → [{email, name}].
     * Grup sözdizimi (posta-kutusu dolu, ana-makine NIL) atlanır.
     */
    private static function addresses(mixed $list): array
    {
        if (!is_array($list)) {
            return [];
        }
        $out = [];
        foreach ($list as $addr) {
            if (!is_array($addr) || ($addr[2] ?? null) === null || ($addr[3] ?? null) === null) {
                continue;
            }
            $out[] = [
                'email' => strtolower(trim((string) $addr[2] . '@' . (string) $addr[3])),
                'name' => self::decodeHeader((string) ($addr[0] ?? '')),
            ];
        }
        return $out;
    }

    /**
     * MIME kodlu başlık metni (=?charset?B|Q?...?=) → UTF-8.
     * Kodlanmamış 8 bit metin UTF-8 değilse Türkçe ISO-8859-9 varsayılır.
     */
    public static function decodeHeader(string $text): string
    {
        if ($text === '') {
            return '';
        }
        if (str_contains($text, '=?')) {
            // Ardışık kodlu sözcükler arasındaki boşluk RFC 2047'ye göre yok sayılır.
            $text = (string) preg_replace('/\?=\s+=\?/', '?==?', $text);
            $decoded = function_exists('iconv_mime_decode')
                ? @iconv_mime_decode($text, ICONV_MIME_DECODE_CONTINUE_ON_ERROR, 'UTF-8')
                : false;
            if ($decoded === false && function_exists('mb_decode_mimeheader')) {
                $decoded = mb_decode_mimeheader($text);
            }
            if (is_string($decoded)) {
                $text = $decoded;
            }
        }
        return self::toUtf8(trim($text));
    }

    /** Geçersiz UTF-8'i verilen ya da varsayılan karakter kümesinden çevirir. */
    public static function toUtf8(string $text, ?string $charset = null): string
    {
        $charset = $charset !== null ? strtoupper(trim($charset)) : null;
        if ($charset !== null && $charset !== '' && $charset !== 'UTF-8' && $charset !== 'US-ASCII') {
            $map = ['WINDOWS-1254' => 'Windows-1254', 'CP1254' => 'Windows-1254', 'ISO-8859-9' => 'ISO-8859-9', 'LATIN5' => 'ISO-8859-9'];
            $from = $map[$charset] ?? $charset;
            $converted = @mb_convert_encoding($text, 'UTF-8', $from);
            if (is_string($converted)) {
                return $converted;
            }
            $converted = function_exists('iconv') ? @iconv($from, 'UTF-8//IGNORE', $text) : false;
            if (is_string($converted)) {
                return $converted;
            }
        }
        if (mb_check_encoding($text, 'UTF-8')) {
            return $text;
        }
        return (string) mb_convert_encoding($text, 'UTF-8', 'ISO-8859-9');
    }

    /**
     * BODYSTRUCTURE → özet için ilk metin bölümü, HTML bölümü ve ek listesi.
     *
     * @return array{text:?array, html:?array, attachments:array}
     */
    public static function analyzeStructure(mixed $structure): array
    {
        $result = ['text' => null, 'html' => null, 'attachments' => []];
        if (is_array($structure)) {
            self::walk($structure, '', $result);
        }
        return $result;
    }

    private static function walk(array $part, string $prefix, array &$result): void
    {
        // Çok parçalı: önce alt parçalar (dizi), sonra alt tür (dizge).
        if (isset($part[0]) && is_array($part[0])) {
            $index = 0;
            foreach ($part as $child) {
                if (!is_array($child)) {
                    break;
                }
                $index++;
                self::walk($child, $prefix === '' ? (string) $index : "{$prefix}.{$index}", $result);
            }
            return;
        }

        $section = $prefix === '' ? '1' : $prefix;
        $type = strtolower((string) ($part[0] ?? ''));
        $subtype = strtolower((string) ($part[1] ?? ''));
        $params = self::pairs($part[2] ?? null);
        $encoding = strtolower((string) ($part[5] ?? '7bit'));
        $size = (int) ($part[6] ?? 0);

        // Uzantı alanlarının başladığı yer türe göre değişir (text: +lines; message/rfc822: +envelope+body+lines).
        $dispositionIndex = match (true) {
            $type === 'text' => 9,
            $type === 'message' && $subtype === 'rfc822' => 11,
            default => 8,
        };
        $disposition = is_array($part[$dispositionIndex] ?? null) ? $part[$dispositionIndex] : null;
        $dispositionType = strtolower((string) ($disposition[0] ?? ''));
        $dispositionParams = self::pairs($disposition[1] ?? null);
        $filename = $dispositionParams['filename'] ?? $params['name'] ?? null;

        $isAttachment = $dispositionType === 'attachment'
            || ($filename !== null && $type !== 'text')
            || ($filename !== null && $dispositionType !== 'inline');

        if ($isAttachment) {
            $result['attachments'][] = [
                'section' => $section,
                'filename' => self::decodeHeader((string) ($filename ?? 'ek')),
                'content_type' => "{$type}/{$subtype}",
                'size' => $size,
                'encoding' => $encoding,
            ];
            return;
        }

        if ($type === 'text') {
            $info = ['section' => $section, 'encoding' => $encoding, 'charset' => $params['charset'] ?? null];
            if ($subtype === 'plain' && $result['text'] === null) {
                $result['text'] = $info;
            } elseif ($subtype === 'html' && $result['html'] === null) {
                $result['html'] = $info;
            }
        }
    }

    /** ("ad" "değer" ...) → [ad(küçük) => değer]; RFC 2231 parçaları birleştirilip çözülür. */
    private static function pairs(mixed $list): array
    {
        if (!is_array($list)) {
            return [];
        }
        $out = [];
        for ($i = 0; $i + 1 < count($list); $i += 2) {
            $out[strtolower((string) $list[$i])] = (string) $list[$i + 1];
        }
        return self::mergeRfc2231($out);
    }

    /**
     * RFC 2231 parametreleri (`ad*=UTF-8''%C3%96`, `ad*0*=…; ad*1=…`) → `ad` => UTF-8.
     * Genişletilmiş değer düz `ad`'ın önüne geçer; diğer anahtarlar aynen kalır.
     *
     * @param array<string,string> $params küçük harf anahtarlı
     */
    public static function mergeRfc2231(array $params): array
    {
        $segments = [];
        foreach ($params as $key => $value) {
            if (!preg_match('/^([a-z0-9_.-]+)\*(\d+)?(\*)?$/', (string) $key, $m)) {
                continue;
            }
            $numbered = isset($m[2]) && $m[2] !== '';
            $segments[$m[1]][$numbered ? (int) $m[2] : 0] = [(string) $value, $numbered ? !empty($m[3]) : true];
            unset($params[$key]);
        }
        foreach ($segments as $name => $parts) {
            ksort($parts);
            $charset = null;
            $bytes = '';
            foreach ($parts as $n => [$value, $encoded]) {
                if ($encoded && $n === array_key_first($parts) && preg_match("/^([^']*)'[^']*'(.*)$/s", $value, $cm)) {
                    $charset = $cm[1] !== '' ? $cm[1] : null;
                    $value = $cm[2];
                }
                $bytes .= $encoded ? rawurldecode($value) : $value;
            }
            $params[$name] = self::toUtf8($bytes, $charset);
        }
        return $params;
    }

    /**
     * BODYSTRUCTURE'daki kodlu bölüm boyutundan yaklaşık çözülmüş bayt.
     * base64: 76 karakter + CRLF satırları → ×(76/78)×(3/4).
     */
    public static function decodedSize(int $encodedSize, string $encoding): int
    {
        return match (strtolower($encoding)) {
            'base64' => (int) floor($encodedSize * 76 / 78 * 3 / 4),
            default => $encodedSize,
        };
    }

    /**
     * Bölümün ilk baytlarından tek satırlık düz metin özeti üretir.
     * Kesilmiş base64 dört karaktere hizalanır; HTML etiketleri ayıklanır.
     */
    public static function snippet(string $raw, string $encoding, ?string $charset, bool $isHtml, int $max = 160): string
    {
        if ($encoding === 'base64') {
            $clean = (string) preg_replace('/[^A-Za-z0-9+\/=]/', '', $raw);
            $raw = (string) base64_decode(substr($clean, 0, intdiv(strlen($clean), 4) * 4));
        } elseif ($encoding === 'quoted-printable') {
            $raw = quoted_printable_decode((string) preg_replace('/=[0-9A-Fa-f]?$/', '', $raw));
        }
        $text = self::toUtf8($raw, $charset);
        if ($isHtml) {
            $text = (string) preg_replace('#<(style|script|head)\b[^>]*>.*?</\1>#is', ' ', $text);
            $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
        $text = trim((string) preg_replace('/\s+/u', ' ', $text));
        // Yarım kalmış çok baytlı karakteri at.
        $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        return mb_substr($text, 0, $max, 'UTF-8');
    }
}
