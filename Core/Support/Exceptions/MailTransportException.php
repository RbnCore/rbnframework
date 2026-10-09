<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Support\Exceptions;

/**
 * MailTransportException - IMAP/SMTP bağlantı ve protokol hataları.
 *
 * `errorCode()` makinenin okuduğu sabit kodu taşır (ör. `AUTH_FAILED`);
 * mesaj metni kullanıcıya gösterilebilir, ama içine parola/kimlik bilgisi
 * ya da sunucunun ham yanıtı YAZILMAZ.
 */
class MailTransportException extends \RuntimeException
{
    protected string $errorCode;

    public function __construct(string $errorCode, string $message, ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->errorCode = $errorCode;
    }

    public function errorCode(): string
    {
        return $this->errorCode;
    }

    public static function unavailable(string $detail = ''): self
    {
        return new self('IMAP_UNAVAILABLE', 'Posta sunucusuna ulaşılamadı' . ($detail !== '' ? " ({$detail})" : '') . '.');
    }

    public static function tls(string $host): self
    {
        return new self('TLS_VERIFY_FAILED', "Sunucu sertifikası doğrulanamadı ({$host}).");
    }

    public static function auth(): self
    {
        return new self('AUTH_FAILED', 'E-posta adresi ya da parola hatalı.');
    }

    public static function protocol(string $detail): self
    {
        return new self('PROTOCOL_ERROR', "Posta sunucusu beklenmeyen yanıt verdi ({$detail}).");
    }

    /** Şifreleme anahtarı tanımlı değil: parola hatalı DEĞİL, sunucu yapılandırması eksik (500). */
    public static function cryptoKeyMissing(): self
    {
        return new self('CRYPTO_KEY_MISSING', 'Sunucu yapılandırması eksik (şifreleme anahtarı tanımlı değil).');
    }

    public static function smtp(string $detail): self
    {
        return new self('SMTP_FAILED', "Gönderim başarısız ({$detail}).");
    }
}
