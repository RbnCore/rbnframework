<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnEmail\Support;

/**
 * TransferDecoder - Content-Transfer-Encoding'i parça parça çözer.
 *
 * Bölüm IMAP'ten dilimler hâlinde gelirken (ek indirme) tüm bölümü belleğe
 * almadan çözmek için: base64'te 4'ün katına hizalanmayan artık, QP'de yarım
 * kalan son satır bir sonraki `push()`'a taşınır.
 */
class TransferDecoder
{
    private string $encoding;
    private string $carry = '';

    public function __construct(string $encoding)
    {
        $this->encoding = strtolower(trim($encoding));
    }

    public function push(string $chunk): string
    {
        if ($this->encoding === 'base64') {
            $data = $this->carry . (string) preg_replace('/[^A-Za-z0-9+\/=]/', '', $chunk);
            $usable = intdiv(strlen($data), 4) * 4;
            $this->carry = substr($data, $usable);
            return (string) base64_decode(substr($data, 0, $usable));
        }
        if ($this->encoding === 'quoted-printable') {
            $data = $this->carry . $chunk;
            $end = strrpos($data, "\n");
            if ($end === false) {
                $this->carry = $data;
                return '';
            }
            $this->carry = substr($data, $end + 1);
            return quoted_printable_decode(substr($data, 0, $end + 1));
        }
        return $chunk;
    }

    public function finish(): string
    {
        $rest = $this->carry;
        $this->carry = '';
        return match ($this->encoding) {
            'base64' => (string) base64_decode($rest),
            'quoted-printable' => quoted_printable_decode($rest),
            default => $rest,
        };
    }
}
