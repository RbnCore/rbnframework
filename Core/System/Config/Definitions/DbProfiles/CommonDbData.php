<?php
declare(strict_types=1);

namespace Rbn\Framework\Core\System\Config\Definitions\DbProfiles;

/**
 * CommonDbData - Ortak operasyonel veritabanı kimlik profili (yalniz SABIT) 🏛️📐
 *
 * RBN 3.5: Centralizes shared operational requirements for the rbncore_common
 * database. [FW-DBPROFILES-TASIMA-95] Core/Services/Gatekeepers/Models ->
 * Core/System/Config/Definitions/DbProfiles. Kimlik/yapılandırma verisi;
 * koruma mantığı değildir (ters bağımlılık giderildi).
 *
 * [FW-TEK-SECRETS-DOSYASI-161] Ortak DB, sistem (master) DB ile **AYNI** MySQL
 * sunucusunda ve aynı `master_db` hesabıyla açılır; bu yüzden bağlantı
 * değerleri `Secrets::masterDb()` bölümünden gelir (ayrı `common_db` bölümü
 * YOKTUR - tek dosya, tek kaynak). Ortam değişkenleri (`COMMON_DB_USER`/
 * `COMMON_DB_PASS`) yedek yoldur; `DB_NAME` bir ŞEMA kimliğidir, sır değildir.
 *
 * [CONFIG-D-BARAN] PATRON KURALI: bu bir SABİT dosyasıdır — **içinde `function`
 * YOKTUR.** Bağlantı değerlerini okuyan metotlar
 * `Engine/Database/DbProfileResolver` sınıfındadır.
 *
 * @see \Rbn\Framework\Core\System\Config\Engine\Database\DbProfileResolver
 */
final class CommonDbData
{
    /**
     * Explicit Definition Identity 🧬🏛️
     */
    protected static ?string $definitionCategory = 'database_common';

    public const PREFIX = 'COMMON_DB';

    /** Bağlantı karakter kümesi (şema kimliği, sır DEĞİLDİR). */
    public const CHARSET = 'utf8mb4';

    /** Bu profilin ayar dosyası adı (anahtar keşfi `ENV_FILE` anahtarını sınıftan okur; `.env` varsayılanı). */
    public const ENV_FILE = '.env';

    /**
     * Ortam degiskeni ADLARI (kayit: `Definitions/EnvKeys.php`).
     *
     * [FW-ENV-KAYIT-160] Ad once burada AÇIKÇA yazılır; `static::PREFIX . '_USER'`
     * ile birleştirmek okuma noktasını görünmez yapıyordu. Sabit, ortam
     * değişkeni adının kodda **tek ve yazılı** yerini gösterir.
     */
    public const USER_ENV = 'COMMON_DB_USER';
    public const PASS_ENV = 'COMMON_DB_PASS';

    /** Ortak veritabani adi - sema kimligi (SIR DEGILDIR, kodda kalir). */
    public const DB_NAME = 'rbncore_common';

    /**
     * Common Database Required Tables (Shared Operational Standard 🛰️)
     */
    public const REQUIRED_TABLES = [
        'cm_sys_ip_blocks',
        'cm_sys_rate_limits',
        'cm_log_notifications',
        'cm_log_contacts',
        'cm_log_ai_usages',
        'cm_sys_settings_shield',
    ];

    /**
     * Tables Permitted for Universal Cleanup Task 🧹
     */
    public const CLEANUP_ALLOWED_TABLES = [
        'cm_sys_rate_limits',
        'cm_sys_ip_blocks',
        'cm_log_notifications',
        'cm_log_ai_usages',
    ];

    /**
     * Anlamsal veritabanı anahtarı HARİTASI (sabit, metot değil) 🏺
     *
     * @see \Rbn\Framework\Core\System\Config\Engine\Database\DbProfileResolver
     */
    public const KEYS_MAP = [
        'host' => 'COMMON_DB_HOST',
        'database' => 'COMMON_DB_NAME',
        'user' => 'COMMON_DB_USER',
        'password' => 'COMMON_DB_PASS',
        'charset' => 'COMMON_DB_CHARSET',
    ];
}
