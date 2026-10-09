<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnEmail\Handlers;

use Rbn\Framework\Core\Base\BaseComponent;
use Rbn\Framework\Core\Support\Bridges\Helpers\Library\CryptoHelper;

/**
 * MailCredentialHandler - Posta hesabı parolasının saklama biçimi.
 *
 * Şifrelemenin KENDİSİ framework'ün tek merkezi `CryptoHelper`'ıdır
 * (anahtar `APP_KEY`/`ENCRYPTION_KEY` ya da `secrets.php` `app_key`;
 * bulunamazsa fail-closed). Bu sınıf biçim önekini ve kayda bağlamayı yönetir.
 *
 * Biçimler:
 *   - `enc:v2:` AES-256-GCM, anahtar HKDF(`rbn-mail-credential`), AAD = hesap kimliği
 *     + IMAP sunucusu + SMTP sunucusu. Şifreli değer başka bir hesaba kopyalanırsa ya da
 *     DB'de sunucu adı değiştirilirse (parolayı saldırganın sunucusuna göndermek için)
 *     ÇÖZÜLMEZ. Yeni yazımların tamamı v2'dir.
 *   - `enc:v1:` AES-256-CBC (MAC'siz). Yalnız okunur; `needsUpgrade()` true döner,
 *     çağıran v2'ye yeniden mühürler. `sealV1()` yalnız geri alma komutu içindir.
 */
class MailCredentialHandler extends BaseComponent
{
    private const PREFIX_V1 = 'enc:v1:';
    private const PREFIX_V2 = 'enc:v2:';
    private const HKDF_INFO = 'rbn-mail-credential';

    /**
     * Parolayı v2 biçiminde hesaba bağlı mühürler.
     *
     * @param array{account_id:int, imap_host:string, smtp_host:string} $binding
     * @throws \InvalidArgumentException hesap kimliği yoksa (bağlamasız v2 yazılmaz)
     */
    public function seal(string $password, array $binding): string
    {
        return self::PREFIX_V2 . CryptoHelper::encryptAead($password, self::HKDF_INFO, self::aad($binding));
    }

    /**
     * Şifreli değeri açar. Önek yoksa, bağlama tutmazsa ya da çözülemezse `null`
     * (düz metin KABUL EDİLMEZ). v1 değerde bağlama kullanılmaz.
     *
     * @param array{account_id:int, imap_host:string, smtp_host:string} $binding
     */
    public function open(?string $sealed, array $binding): ?string
    {
        if ($sealed === null) {
            return null;
        }
        if (str_starts_with($sealed, self::PREFIX_V2)) {
            return CryptoHelper::decryptAead(substr($sealed, strlen(self::PREFIX_V2)), self::HKDF_INFO, self::aad($binding));
        }
        if (str_starts_with($sealed, self::PREFIX_V1)) {
            return CryptoHelper::decrypt(substr($sealed, strlen(self::PREFIX_V1)));
        }
        return null;
    }

    /** Değer eski (v1) biçimde mi? (v2'ye yeniden mühürlenmeli.) */
    public function needsUpgrade(?string $sealed): bool
    {
        return $sealed !== null && str_starts_with($sealed, self::PREFIX_V1);
    }

    /** Değerin biçim sürümü: `v1`, `v2` ya da tanınmıyorsa `null`. */
    public function version(?string $sealed): ?string
    {
        foreach (['v2' => self::PREFIX_V2, 'v1' => self::PREFIX_V1] as $version => $prefix) {
            if ($sealed !== null && str_starts_with($sealed, $prefix)) {
                return $version;
            }
        }
        return null;
    }

    /**
     * v1 (CBC) mühür. YALNIZ v2 geçişini geri alan komut kullanır; yeni yazımda kullanılmaz.
     */
    public function sealV1(string $password): string
    {
        return self::PREFIX_V1 . CryptoHelper::encrypt($password);
    }

    /** Anahtar tanımlı mı (değer döndürmez). */
    public function keyPresent(): bool
    {
        try {
            return CryptoHelper::decrypt(CryptoHelper::encrypt('probe')) === 'probe';
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Doğrulanan ek veri: kayıt kimliği + sunucu adları (küçük harf, boşluksuz).
     *
     * @throws \InvalidArgumentException hesap kimliği yoksa
     */
    private static function aad(array $binding): string
    {
        $id = (int) ($binding['account_id'] ?? 0);
        if ($id <= 0) {
            throw new \InvalidArgumentException('Posta parolası hesap kimliği olmadan mühürlenemez/açılamaz (account_id).');
        }

        return 'rbn-mail:v2|' . $id
            . '|' . strtolower(trim((string) ($binding['imap_host'] ?? '')))
            . '|' . strtolower(trim((string) ($binding['smtp_host'] ?? '')));
    }
}
