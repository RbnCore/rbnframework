<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\System\Config\Engine\Database;

use Rbn\Framework\Core\System\Config\Definitions\DbProfiles\CommonDbData;
use Rbn\Framework\Core\System\Config\Definitions\DbProfiles\MasterDbData;
use Rbn\Framework\Core\System\Config\Definitions\DbProfiles\ProjectDbData;
use Rbn\Framework\Core\System\Config\Secrets;

/**
 * DbProfileResolver - Veritabani kimlik profillerinin BAĞLANTI ÇÖZÜCÜSÜ 🔌🗃️
 *
 * [CONFIG-D-BARAN] PATRON KURALI: "sabitlerin olduğu dosyalarda METOT olmaz;
 * sabitler ayrı dosya, metotlar ayrı dosya." `Definitions/DbProfiles/*`
 * yalnız `public const` taşıyan SABİT dosyalarıdır; bağlantı değerlerini
 * üreten metotlar BURAYA taşındı.
 *
 * SORUMLULUK (tek): bir profil sınıfı için `host`/`port`/veritabanı adı/
 * kullanıcı/parola/anahtar haritası/bağlantı dizisini ÜRETMEK. Değer
 * kaynağı her zaman `Secrets` (TEK sır dosyası). Ortam değişkeni yolu YOKTUR
 * [FW-096-D8].
 *
 * KAYNAK HARİTASI (davranış birebir korundu):
 *   - `MasterDbData`  → tamamen `Secrets::masterDb()` (host, port, name,
 *                       user, pass). Ortam değişkeni yolu YOKTUR.
 *   - `CommonDbData`  → `Secrets::masterDb()` ile AYNI sunucu/hesap.
 *   - proje profili   → `Secrets::optional()` (`db_user`/`db_pass`) →
 *                       yoksa fail-closed.
 *                       (Asıl kaynak her projenin kendi
 *                       `project-settings.php` dosyasıdır; bu yol YEDEKTİR.)
 *
 * FAIL-CLOSED: parola hiçbir kaynaktan çözülemezse `RuntimeException`; hata
 * metni yalnız ALAN ADINI yazar, hiçbir sır değeri yazmaz.
 *
 * @see \Rbn\Framework\Core\System\Config\Secrets
 */
final class DbProfileResolver
{
    /**
     * Bağlantı dizisi (PDO/`DatabaseConfig` için tek giriş noktası).
     *
     * @param class-string $profile Profil sınıfı.
     * @return array{host:string,database:string,user:string,password:string,charset:string}
     */
    public static function credentials(string $profile): array
    {
        return [
            'host' => self::host($profile),
            'database' => self::databaseName($profile),
            'user' => self::user($profile),
            'password' => self::password($profile),
            'charset' => self::charset($profile),
        ];
    }

    /**
     * DB sunucusu. Master/ortak `Secrets::masterDb()['host']`; proje profili
     * kendi `HOST` varsayılanını kullanır (gizli DEĞİLDİR).
     *
     * @param class-string $profile
     */
    public static function host(string $profile): string
    {
        return self::isMaster($profile) || self::isCommon($profile)
            ? Secrets::masterDb()['host']
            : $profile::HOST;
    }

    /**
     * DB portu. Yalnız master/ortak profilde tanımlıdır; proje DB'sinin portu
     * `project-settings.php` dosyasındadır ve bu katman bilmez.
     *
     * @param class-string $profile
     *
     * @throws \RuntimeException Proje profili için çağrılırsa (fail-closed).
     */
    public static function port(string $profile): int
    {
        if (self::isProject($profile)) {
            throw new \RuntimeException(
                'RBN Yapılandırma: proje veritabanı portu profil katmanında '
                . 'TANIMLI DEĞİLDİR; projenin kendi dosyasından okunur '
                . '(Core/Config/project-settings.php).'
            );
        }

        return (int) Secrets::masterDb()['port'];
    }

    /**
     * Veritabanı adı. Master `secrets.php`'ten; ortak `DB_NAME` şema
     * sabitinden; proje profili bu katmanda `''` döner (kendi dosyasından gelir).
     *
     * @param class-string $profile
     */
    public static function databaseName(string $profile): string
    {
        if (self::isMaster($profile)) {
            return Secrets::masterDb()['name'];
        }

        if (self::isCommon($profile)) {
            return CommonDbData::DB_NAME;
        }

        return '';
    }

    /**
     * DB kullanıcı adı (kimlik, tek çıkış - A0-8).
     *
     * @param class-string $profile
     */
    public static function user(string $profile): string
    {
        if (self::isMaster($profile)) {
            return Secrets::masterDb()['user'];
        }

        if (self::isCommon($profile)) {
            return Secrets::masterDb()['user'];
        }

        return Secrets::optional(ProjectDbData::DB_USER_KEY)
            ?? $profile::DEFAULT_DB_USER;
    }

    /**
     * DB parolası - tek çıkış (A0-8, fail-closed).
     *
     * Boş parola YALNIZ sır dosyasında gerçekten boş tanımlıysa geçer.
     * Sır dosyasında yoksa sessiz `''` dönülmez.
     *
     * @param class-string $profile
     *
     * @throws \RuntimeException Parola hiçbir kaynaktan çözülemezse.
     */
    public static function password(string $profile): string
    {
        if (self::isMaster($profile)) {
            return Secrets::masterDb()['pass'];
        }

        if (self::isCommon($profile)) {
            return Secrets::masterDb()['pass'];
        }

        $p = Secrets::optional(ProjectDbData::DB_PASS_KEY);

        if ($p === null) {
            throw new \RuntimeException(
                'RBN Guvenlik: ' . $profile . ' veritabani parolasi cozulemedi. '
                . 'Core/System/Config/Secrets/secrets.php dosyasındaki `'
                . ProjectDbData::DB_PASS_KEY . '` anahtarını tanımlayın. '
                . 'Sessiz bos fallback YOKTUR.'
            );
        }

        return $p;
    }

    /** Bağlantı karakter kümesi (şema, gizli DEĞİLDİR). */
    public static function charset(string $profile): string
    {
        return $profile::CHARSET;
    }

    /**
     * Anlamsal anahtar HARİTASI. Profil sabitidir: `Definition::get(...,
     * 'KEYS_MAP')` doğrudan `Profile::KEYS_MAP` sabitini okur.
     *
     * @param class-string $profile
     * @return array<string,string>
     */
    public static function keysMap(string $profile): array
    {
        return $profile::KEYS_MAP;
    }

    /** Sistem (master) profili mi? Alt sınıflar da dahil. */
    private static function isMaster(string $profile): bool
    {
        return is_a($profile, MasterDbData::class, true);
    }

    /** Ortak operasyonel profil mi? Alt sınıflar da dahil. */
    private static function isCommon(string $profile): bool
    {
        return is_a($profile, CommonDbData::class, true);
    }

    /** Proje profili mi? Alt sınıflar da dahil. */
    private static function isProject(string $profile): bool
    {
        return is_a($profile, ProjectDbData::class, true);
    }
}
