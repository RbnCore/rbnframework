<?php
declare(strict_types=1);

namespace Rbn\Framework\Core\System\Config\Definitions\DbProfiles;

/**
 * ProjectDbData - Standart proje veritabanı kimlik profili (yalniz SABIT) 🛰️📐🗃️
 *
 * RBN 3.5: Centralizes all core database requirements for standard projects.
 * [FW-DBPROFILES-TASIMA-95] Core/Services/Gatekeepers/Models ->
 * Core/System/Config/Definitions/DbProfiles. Kimlik/yapılandırma verisi;
 * koruma mantığı değildir (ters bağımlılık giderildi).
 *
 * [A0-8] GÖMÜLÜ `PASS` SABİTİ SİLİNDİ (parola artık kolda değil).
 * [FW-TEK-SECRETS-DOSYASI-161] Proje DB parolası/kullanıcı adı TEK ÇIKIŞ
 * olarak kendi yapılandırma dosyasından gelir:
 * `Core/System/Config/Engine/Database/DatabaseConfig::fromFile()` ve
 * `DatabaseGuardProvider::loadCredentials()` `project-settings.php` dosyasını
 * okur. `DbProfileResolver` içindeki proje profili yolu yalnız YEDEK
 * yoldur: `DB_USER`/`DB_PASS` ortam değişkeni. Framework seviyesindeki TEK sır
 * dosyası (`secrets.php`) proje DB'sini TARAMAZ — her projenin kendi DB'si
 * kendi dosyasındadır. Hiçbiri yoksa sessiz `''` dönülmez — fail-closed
 * (`DbProfileResolver::pass()`).
 *
 * [CONFIG-D-BARAN] PATRON KURALI: bu bir SABİT dosyasıdır — **içinde `function`
 * YOKTUR.** `DB_NAME` sabiti de burada YOKTUR (eski `defined('static::DB_NAME')`
 * koruması gereksizleşti): proje veritabanı adı her zaman kendi dosyasından
 * (`project-settings.php`) gelir.
 *
 * @see \Rbn\Framework\Core\System\Config\Engine\Database\DbProfileResolver
 */
final class ProjectDbData
{
    /**
     * Explicit Definition Identity 🧬🏛️
     */
    protected static ?string $definitionCategory = 'database_project';

    public const PREFIX = 'DB';
    public const ENV_FILE = 'project-settings.php';

    /** Varsayılan DB sunucusu (yalnız PROJE profili; gizli DEĞİLDİR). Master/ortak `secrets.php` içindeki `master_db.host` değerini okur. */
    public const HOST = '127.0.0.1';

    /** Bağlantı karakter kümesi (şema kimliği, sır DEĞİLDİR). */
    public const CHARSET = 'utf8mb4';

    /** En az ayarlanmış kurulumlar için DB kullanıcı adı (kimlik, sır DEĞİLDİR); proje ayarında tanımlıysa kodda yazan KULLANILMAZ. */
    public const DEFAULT_DB_USER = 'root';

    /**
     * Ortam değişkeni ADLARI (yedek okuma yolu; kayıt `Definitions/EnvKeys.php`).
     *
     * [FW-ENV-KAYIT-160] Ad burada AÇIKÇA yazılır; `PREFIX . '_USER'` ile
     * birleştirmek okuma noktasını görünmez yapıyordu.
     */
    public const USER_ENV = 'DB_USER';
    public const PASS_ENV = 'DB_PASS';

    /**
     * Sır dosyasındaki OPSİYONEL anahtar adları (küçük harf, eski düz-anahtar
     * biçimi korunur). Profil `DB_NAME` sabiti taşımadığı için anahtar
     * `strtolower(PREFIX)` (= `db`) ön ekiyle türetilir.
     */
    public const DB_USER_KEY = 'db_user';
    public const DB_PASS_KEY = 'db_pass';

    /**
     * Required Project Tables (Production Standard 🛰️)
     */
    public const REQUIRED_TABLES = [
        'z_app_faqs',
        'z_setting_groups',
        'z_log_crons',
        'z_app_menus_frontend',
        'z_app_pages',
        'z_settings',
        'z_bs_sidebar_categories',
        'z_bs_sidebar_menus',
        'z_users_activities',
        'z_users_security',
        'z_users'
    ];

    /**
     * Tables Permitted for Universal Cleanup Task 🧹
     */
    public const CLEANUP_ALLOWED_TABLES = [
        'z_log_crons',
        'z_users_activities'
    ];

    /* ==========================================================================
       [FW-ALTYAPI-2 / H] TENANT (KIRACI) KOLONU SÖZLEŞMESİ 🔑🏗️
       ------------------------------------------------------------------------
       Kiracı izolasyonunun şema tarafındaki TEK doğruluk kaynağı.

       ÖLÇÜM (yerel, `information_schema`, yalnız SELECT): 7 proje veritabanında
       26 tablo **gerçekten çok-kiracılı** (aynı tabloda `project_key` değeri
       >= 2). Bu liste o 26 tabloyu tek yerde toplar.

       [ANAYASA §9] Burada YALNIZ tablo adı vardır; **veritabanı adı, proje,
       müşteri veya kişi adı YAZILMAZ** — hedef veritabanı her koşuda işletme
       tarafından verilir.

       [ASW YASAĞI] `asw_*` tabloları listede **YOKTUR** ve motor listede
       olmayan hiçbir tabloya dokunmaz; bu yasak yapısal olarak garanti edilir
       (sabit bir "hariç tut" listesine değil, kapsamın kendisine dayanır).
       ========================================================================== */

    /** Kiracı kolonunun adı. */
    public const TENANT_COLUMN = 'project_key';

    /** Kiracı kolonunun şema tipi (ölçülen: en uzun beyan 15 karakter; 64 yeterli). */
    public const TENANT_COLUMN_TYPE = 'varchar(64)';

    /** Kiracı kolonu eklenirken `NULL` + `DEFAULT NULL` olur (sessiz yanlış kiracı sızıntısını önler). */
    public const TENANT_COLUMN_NULLABLE = true;
    public const TENANT_COLUMN_DEFAULT = null;

    /** İndeks adı ön eki (MySQL tanımlayıcı sınırı 64 karakter). */
    public const TENANT_INDEX_PREFIX = 'ix_';

    /**
     * Çok-kiracılı ölçülmüş hedef tablolar.
     *
     * ÖLÇÜLEN GERÇEK: 7 proje veritabanında çok-kiracılı `(veritabanı, tablo)`
     * çifti sayısı **26**; bunların **tekil tablo** sayısı **12**'dir (11'i
     * projeler arası PAYLAŞILAN `z_*`/`app_*` yüzeyi, 1'i tek bir ürünün kendi
     * tablosu). Migration her çifte ayrı yazılır/koşulur; motor tabloyu kendi
     * veritabanında arar, yoksa ATLAR.
     *
     * Kullanım kuralı: motor yalnız bu listedeki tabloları işler; listede
     * olmayan hiçbir tabloya dokunmaz (bkz. `asw_*` yasağı yukarıda).
     *
     * @var string[]
     */
    public const TENANT_TABLES = [
        // --- projeler arası PAYLAŞILAN yüzey (11) ---
        // içerik kategorileri
        'app_content_categories',
        // RSS kaynakları / kara listesi
        'app_rss_blacklist',
        'app_rss_sources',
        // bilgi bankası
        'z_app_faqs',
        // ön yüz menüleri / sayfalar
        'z_app_menus_frontend',
        'z_app_pages',
        // zamanlanmış görev kayıtları
        'z_log_crons',
        // panel ayarları (değer + API ayarı)
        'z_settings',
        'z_settings_api',
        // kenar çubuğu
        'z_bs_sidebar_categories',
        'z_bs_sidebar_menus',
        // --- ölçümde tek bir ürüne ait çıkan tablo (1) ---
        // [ANAYASA §9 TANSISI] Bu ad bir proje/ürün ADI taşımaz; bir TABLO
        // adıdır (REQUIRED_TABLES'taki `z_*` adlarıyla aynı sınıf). Yine de
        // paylaşılan yüzeyden ayrı durduğu için burada AYRI ve YORUMLU durur.
        'aa_products',
    ];

    /**
     * Anlamsal veritabanı anahtarı HARİTASI (sabit, metot değil) 🏺
     *
     * `ProjectDataMapper::buildProjectPdo()` ve
     * `DatabaseConfig::fromRaw('database_project')` bu sabiti okur.
     *
     * @see \Rbn\Framework\Core\System\Config\Engine\Database\DbProfileResolver
     */
    public const KEYS_MAP = [
        'host' => 'DB_HOST',
        'database' => 'DB_NAME',
        'user' => 'DB_USER',
        'password' => 'DB_PASS',
        'charset' => 'DB_CHARSET',
    ];
}
