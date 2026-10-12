<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnFactory\Handlers;

use Rbn\Framework\Core\Base\BaseComponent;

/**
 * FactorySignatureHandler - HMAC-SHA256 imza 🔏
 * TEK ŞEMA: imza = hex(HMAC(anahtar, zaman "\n" yol "\n" nonce "\n" gövde)); iki yönde, 2xx cevaplar dahil.
 * Nonce boş olamaz; `X-Fab-Nonce` başlığı ZORUNLUDUR (yoksa 401). Nonce biçimi satır sonu içermez, bu yüzden
 * alanlar belirsizliksiz ayrışır (gövdeye "NONCE\n" kaydırarak başka şemaya indirgeme mümkün değildir).
 * Nonce süreli TEK KULLANIMDIR. Site→VPS 2xx cevabı, isteğin nonce'unu kullanarak aynı şemayla imzalanır (cevap isteğe bağlanır).
 * Anahtar yalnız projenin z_settings_api tablosundan (FACTORY_SIGNING_KEY) okunur; log'a/çıktıya yazılmaz.
 */
class FactorySignatureHandler extends BaseComponent
{
    /** VPS ile ortak başlık adları (sözleşme) */
    public const HEADER_SITE = 'X-Fab-Site';
    public const HEADER_TIME = 'X-Fab-Zaman';
    public const HEADER_SIGNATURE = 'X-Fab-Imza';
    public const HEADER_NONCE = 'X-Fab-Nonce';

    public function signingKey(): ?string
    {
        $key = trim((string) $this->handler('factoryConfig')->rawSigningKey());
        if ($key === '') {
            error_log('[factory] z_settings_api FACTORY_SIGNING_KEY: değer boş; uçlar 503 verir');
            return null;
        }
        if (strlen($key) < 32) {
            error_log('[factory] z_settings_api FACTORY_SIGNING_KEY: anahtar en az 32 karakter olmalı');
            return null;
        }
        return $key;
    }

    public function sign(string $key, int $time, string $path, string $nonce, string $body): string
    {
        if ($nonce === '') {
            throw new \InvalidArgumentException('factory: nonce boş olamaz');
        }
        return hash_hmac('sha256', $time . "\n" . $path . "\n" . $nonce . "\n" . $body, $key);
    }

    /** İsteğin nonce başlığı (verify() biçimi doğrular); cevap imzası buna bağlanır */
    public function requestNonce(): string
    {
        return (string) $this->request->header(self::HEADER_NONCE, '');
    }

    /**
     * İstek gövdesini imzalı baytlarıyla okur. Request motoru ham gövdeyi sunmadığı ve HMAC
     * birebir bayt istediği için php://input yalnız burada, tek yerde okunur (+1 bayt: sınır aşımı tespiti).
     */
    public function readBody(int $maxBytes): string
    {
        return (string) file_get_contents('php://input', false, null, 0, $maxBytes + 1);
    }

    /** @return string|null hata nedeni; null = geçerli */
    public function verify(string $key, string $path, string $body): ?string
    {
        $config = $this->handler('factoryConfig');
        $site = (string) $config->get('site_code', '');
        if ($site !== '' && !hash_equals($site, (string) $this->request->header(self::HEADER_SITE, ''))) {
            return 'site kodu';
        }
        $time = (string) $this->request->header(self::HEADER_TIME, '');
        $signature = (string) $this->request->header(self::HEADER_SIGNATURE, '');
        if (!ctype_digit($time) || $signature === '') {
            return 'başlık eksik';
        }
        if (abs(time() - (int) $time) > (int) $config->get('clock_tolerance', 300)) {
            return 'zaman penceresi';
        }
        $nonce = $this->requestNonce();
        if ($nonce === '') {
            return 'nonce zorunlu';
        }
        if (!preg_match('/^[A-Za-z0-9_-]{16,64}\z/', $nonce)) {
            return 'nonce biçimi';
        }
        if (!hash_equals($this->sign($key, (int) $time, $path, $nonce, $body), strtolower($signature))) {
            return 'imza';
        }
        // Nonce yalnız İMZA DOĞRULANDIKTAN sonra tüketilir (sahte istek nonce yakamaz); süre = pencerenin iki yönü + pay
        $ttl = 2 * (int) $config->get('clock_tolerance', 300) + 60;
        if (!$this->handler('factoryLock')->claimOnce('nonce:' . $path . ':' . $nonce, $ttl)) {
            return 'tekrar';
        }
        return null;
    }
}
