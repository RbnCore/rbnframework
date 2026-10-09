<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnEmail\Support;

use PHPMailer\PHPMailer\PHPMailer;

/**
 * AccountMailer - Ek adlarını RFC 2231 ile yazan PHPMailer.
 *
 * PHPMailer 6 ek adını `filename="=?utf-8?B?…?="` (tırnak içinde RFC 2047) yazar;
 * bu, standart dışıdır ve bazı istemcilerde Türkçe ad bozulur. Burada yalnız
 * `attachment` yerleşimli eklerin bölüm başlığı değişir:
 *   Content-Type: <tür>; name="<RFC 2047>"            (eski istemciler için, PHPMailer ile aynı)
 *   Content-Disposition: attachment;
 *    filename*=UTF-8''<yüzde kodlu>                    (RFC 2231, uzunsa *0* *1* parçalı)
 * Kısa ASCII adlarda `filename="ad"` kalır. Gömülü (inline) ekler PHPMailer'a bırakılır.
 */
class AccountMailer extends PHPMailer
{
    /** RFC 2231 parça başına en çok yüzde kodlu karakter (katlanmış satır 78'i aşmasın). */
    private const PARAM_CHUNK = 50;

    /** `Content-Disposition: attachment; filename="…"` tek satırda 78'i aşmayan en uzun ASCII ad. */
    private const INLINE_ASCII_MAX = 33;

    protected function attachAll($disposition_type, $boundary)
    {
        if ($disposition_type !== 'attachment') {
            return parent::attachAll($disposition_type, $boundary);
        }

        $le = static::$LE;
        $mime = [];
        foreach ($this->getAttachments() as $attachment) {
            if ($attachment[6] !== 'attachment') {
                continue;
            }
            [$source, , $name, $encoding, $type, $isString] = $attachment;
            $name = $this->secureHeader((string) $name);

            $mime[] = "--{$boundary}{$le}";
            $mime[] = $name !== ''
                ? 'Content-Type: ' . $type . '; name=' . static::quotedString($this->encodeHeader($name)) . $le
                : "Content-Type: {$type}{$le}";
            if ($encoding !== static::ENCODING_7BIT) {
                $mime[] = "Content-Transfer-Encoding: {$encoding}{$le}";
            }
            $mime[] = 'Content-Disposition: attachment' . ($name !== '' ? ';' . self::filenameParam($name, $le) : '') . $le . $le;
            $mime[] = $isString ? $this->encodeString((string) $source, $encoding) : $this->encodeFile((string) $source, $encoding);
            if ($this->isError()) {
                return '';
            }
            $mime[] = $le;
        }
        $mime[] = "--{$boundary}--{$le}";

        return implode('', $mime);
    }

    /**
     * Content-Disposition'ın ad parametresi (önündeki `;` hariç). Satırlar 78 karakteri aşmasın diye
     * kısa ASCII ad aynı satırda ` filename="ad"`; diğerleri katlanmış satırda RFC 2231
     * ` filename*=UTF-8''…` ya da parçalı ` filename*0*=UTF-8''…;` ` filename*1*=…`.
     */
    public static function filenameParam(string $name, string $le = "\r\n"): string
    {
        if (preg_match('/^[\x20-\x7E]*$/', $name) && strlen($name) <= self::INLINE_ASCII_MAX && strpbrk($name, "\"\\") === false) {
            return ' filename="' . $name . '"';
        }
        $encoded = rawurlencode($name);
        if (strlen($encoded) <= self::PARAM_CHUNK) {
            return "{$le} filename*=UTF-8''{$encoded}";
        }
        // Yüzde dizisini (%XX) bölmeden parçala.
        preg_match_all('/%[0-9A-F]{2}|[^%]/', $encoded, $m);
        $chunks = [];
        $current = '';
        foreach ($m[0] as $token) {
            if (strlen($current) + strlen($token) > self::PARAM_CHUNK) {
                $chunks[] = $current;
                $current = '';
            }
            $current .= $token;
        }
        $chunks[] = $current;
        $params = [];
        foreach ($chunks as $i => $chunk) {
            $params[] = "{$le} filename*{$i}*=" . ($i === 0 ? "UTF-8''" : '') . $chunk;
        }
        return implode(';', $params);
    }
}
