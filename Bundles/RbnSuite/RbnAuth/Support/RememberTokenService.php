<?php

declare(strict_types=1);

namespace Rbn\Framework\Bundles\RbnSuite\RbnAuth\Support;

use Rbn\Framework\Core\Support\Bridges\Helpers\Library\CryptoHelper;

/**
 * RememberTokenService - "Beni hatırla" token'inin SÜRE ve İMZA doğrulaması ⏳🔏
 *
 * [A-10 · SÜRESİZLİK] ÖNCEKİ HALİ: `createRememberMe()` rastgele bir token
 * üretip yalnız ÇEREZ `expires` alanına 30 gün yazıyordu. Token'ın KENDİSİNDE
 * bitiş zamanı olmadığı için sunucu tarafında "süre doldu mu" denetimi yoktu:
 * çerez elle/otomatik uzatılsın, DB'deki token geçerli olduğu sürece oturum
 * yeniden kuruluyordu. SÜRE YALNIZ ÇEREZDE = sunucu tarafı denetim YOK.
 *
 * ÇÖZÜM (VERİTABANI ŞEMASI DEĞİŞMEDEN): bitiş zamanı token'ın İÇİNE gömülür ve
 * uygulama anahtarıyla imzalanır:
 *
 *     <expires>.<nonce>.<signature>
 *     signature = HMAC-SHA256(uygulama anahtarı, "rbn_remember:v2:<expires>:<nonce>")
 *
 * İmza `CryptoHelper::blindIndex()` ile üretilir: MEVCUT ve TEK anahtar
 * çözümleme yolunu (`resolveKey()` → `APP_KEY` / `ENCRYPTION_KEY` /
 * `secrets.php::app_key`) kullanır, yani **yeni sır UYDURULMAZ** ve anahtar
 * yoksa sessizce "herkese açık" bir değere düşmez (fail-closed).
 *
 * Neden `blindIndex()`? Framework'te anahtarlı HMAC üreten tek AÇIK yardımcı
 * odur (`encrypt()` geri döndürülemez, `hash()`/bcrypt 72 baytta kesilir ve
 * zamana bağlı imza üretmez). Etiket olarak "blind index" olsa da çıktısı
 * düz HMAC-SHA256'dır; yalnızca `strtolower()/trim()` uygular ve bizim
 * girdimiz zaten küçük harf onaltılıktır.
 *
 * [GEÇİŞ POLİTİKASI] Bu sürümden ÖNCE üretilmiş (noktasız, süresiz) tokenlar
 * `LEGACY_ACCEPT_UNTIL` damgasına kadar kabul edilir; sonra istenmez ve kullanıcı
 * yeniden giriş yapar. Kırıcı değişiklik: bkz. `.github/UPGRADING.md`.
 *
 * Sınıf SAF (statik) tutulmuştur: konteyner/DB gerektirmez, birim testinde
 * doğrudan doğrulanabilir ve Anayusa §1 gereği `new` kullanılmaz.
 */
final class RememberTokenService
{
    /** Token biçim sürümü (imza girdisine girer; biçim değişince artar). */
    public const VERSION = 'v2';

    /** 🔐 TEK SURE KAYNAĞI (sabit): 30 gün. Hem token hem çerez bundan. */
    public const MAX_AGE_DAYS = 30;

    /** @var int 30 gün = 2.592.000 saniye */
    public const MAX_AGE_SECONDS = 2592000;

    /**
     * Eski (noktasız, süresiz) tokenların kabul penceresinin BİTİŞİ.
     *
     * 2026-11-02 00:00:00 Europe/Istanbul = 1793566800. Bu sürümün
     * yayınlandığı günden (2026-10-03) itibaren 30 gün sonra eski biçimli
     * tokenlar geçersiz sayılır.
     */
    public const LEGACY_ACCEPT_UNTIL = 1793566800;

    /**
     * Yeni, süreli ve imzalı token üretir.
     *
     * @return array{token: string, expires_at: int}
     * @throws \RuntimeException Uygulama anahtarı yoksa (fail-closed).
     */
    public static function issue(?int $now = null): array
    {
        $now = $now ?? time();
        $expiresAt = $now + self::MAX_AGE_SECONDS;
        $nonce = CryptoHelper::generateToken(16); // 32 onaltılık karakter

        return [
            'token'      => $expiresAt . '.' . $nonce . '.' . self::signature($expiresAt, $nonce),
            'expires_at' => $expiresAt,
        ];
    }

    /**
     * Token yeni (imzalı) biçimde mi? Yanlış/eksik biçim `false` döner.
     */
    public static function isSigned(string $token): bool
    {
        return (bool) preg_match('/^\d{10}\.[0-9a-f]{32}\.[0-9a-f]{64}$/D', $token);
    }

    /**
     * 🔐 SUNUCU TARAFI KAPI: token bu anda kabul edilebilir mi?
     *
     * - Yeni biçim: önce İMZA (kronolojik/veri bütünlüğü), sonra SÜRE.
     *   Süre dolmuşsa `false` → çağıran çerezi siler ve oturum AÇMAZ.
     * - Eski biçim: yalnız `LEGACY_ACCEPT_UNTIL` damgasına kadar `true`.
     * - Anahtar çözülemezse (fail-closed) yeni biçim `false` döner: tokenı
     *   doğrulayamayan sunucu kimliği kurmaz.
     *
     * @param int|null $now Test/ölçüm için zaman kaydırma (null = şimdi).
     */
    public static function isAcceptable(string $token, ?int $now = null): bool
    {
        $now = $now ?? time();

        if ($token === '') {
            return false;
        }

        if (!self::isSigned($token)) {
            return self::isLegacyAcceptable($token, $now);
        }

        [$expiresAt, $nonce, $signature] = explode('.', $token);
        $expiresAt = (int) $expiresAt;

        try {
            $expected = self::signature($expiresAt, $nonce);
        } catch (\Throwable $e) {
            // Anahtar yoksa doğrulama YAPILAMAZ → kimlik kurulmaz (fail-closed).
            return false;
        }

        if (!hash_equals($expected, $signature)) {
            return false;
        }

        if ($expiresAt <= $now) {
            return false;
        }

        // Kalan süre, üretim süresini aşamaz (DB'de biri token ömrünü uzatmışsa
        // yakalanır). `isAcceptable` her istekte çalıştığı için tokenın
        // yaşı = 30 gün - kalan süre olarak kabul edilir.
        return ($expiresAt - $now) <= self::MAX_AGE_SECONDS;
    }

    /**
     * Tokenın bitiş zamanı (yeni biçim). Geçersiz/eski biçimde `null`.
     */
    public static function expiresAt(string $token): ?int
    {
        return self::isSigned($token) ? (int) explode('.', $token)[0] : null;
    }

    /**
     * 🔁 ESKİ BİÇİM GEÇİŞ POLİTİKASI: noktasız (süresiz) tokenlar yalnız
     * `LEGACY_ACCEPT_UNTIL` damgasından ÖNCE kabul edilir.
     */
    private static function isLegacyAcceptable(string $token, int $now): bool
    {
        if (!preg_match('/^[0-9a-f]{64,256}$/D', $token)) {
            return false; // ne yeni ne eski biçim: tanınmayan token
        }

        return $now < self::LEGACY_ACCEPT_UNTIL;
    }

    /**
     * İmza: uygulama anahtarıyla HMAC-SHA256 (mevcut `CryptoHelper` yolu).
     */
    private static function signature(int $expiresAt, string $nonce): string
    {
        return CryptoHelper::blindIndex(
            'rbn_remember:' . self::VERSION . ':' . $expiresAt . ':' . $nonce
        );
    }
}