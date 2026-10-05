<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\System\Config\Definitions;

/**
 * EnvKeys - ORTAM DEĞİŞKENİ ADLARININ SABİT KAYIT DOSYASI (TEK MERKEZ). 🔑
 *
 * FW-ENV-KAYIT-160 (Baran): "global anahtarlar TEK dosyada toplansın; herkes
 * her yerde ayrı okuyup tanımlamasın." Bu dosya **sabit dosyasıdır**: İÇİNDE
 * METOT YOKTUR. Yalnız `public const` ad sabitleri + iki sabit liste vardır.
 *
 * AYRIM (K-0142 hafızasıyla aynı çizgi):
 *   - **Gizli değerler** (parola, app_key, jeton) -> `Core/System/Config/Secrets.php`
 *     (TEK sır dosyası: bölümlü `Secrets/secrets.php`).
 *   - **Ortam değişkeni / kill-switch / bayrak ADLARI** -> BURASI (TEK kayıt) +
 *     `Core/System/Config/Env.php` (TEK okuyucu).
 * `SECRET_KEYS` listesinde olan adların **değeri** asla hata mesajına, loga ya
 * da bu dosyaya yazılmaz; yalnız ADI tutulur.
 *
 * AYRIM - ConfigMap: `Definitions/ConfigMap.php` **bu dosyadan tamamen
 * AYRIDIR** ve ikisi BİRLEŞTİRİLMEZ/ SİLİNMEZ. `ConfigMap` = framework'ün
 * GLOB OLARAK tanımladığı ayar anahtarları ve PHP sabitleri (`app.debug`,
 * `app.logging`, `app.env`, `RBN_CLI`, `RBN_SESSION_TIMEOUT` ...);
 * `EnvKeys` = yalnız **ortam değişkeni olarak okunan** adlar. Bir `define()`
 * sabiti (`RBN_CLI`, `RBN_PANIC_ACTIVE`,
 * `RBN_SESSION_TIMEOUT`) ortam değişkeni DEĞİLDİR ve buraya YAZILMAZ.
 *
 * YENİ ANAHTAR EKLEME KURALI: kodda `getenv` / `$_ENV` / `$_SERVER` ile yeni bir
 * ad okunacaksa ÖNCE buraya bir `public const` + (gizliyse) `SECRET_KEYS`
 * girdisi eklenir. `Env` yalnız burada kayıtlı adları okur; kayıtlı olmayan
 * bir ad `RuntimeException` ile fail-closed reddedilir.
 *
 * KAYIT SINIR KURALI (patron, FW-ENV-KAYIT-160): buradaki HER adın kodda
 * GERÇEKTEN `Env::string()/flag()/int()` ile okunduğu kanıtlanmıştır
 * (`rg -n "Env::" rbnframework projects`). Kanıtı olmayan ad KALDIRILIR.
 *
 * @see \Rbn\Framework\Core\System\Config\Env
 * @see \Rbn\Framework\Core\System\Config\Secrets
 */
final class EnvKeys
{
    /* ------------------------------------------------------------------
     * ŞİFRELEME / UYGULAMA ANAHTARI (gizli - değer HİÇBİR YERDE yazılmaz)
     * ---------------------------------------------------------------- */

    /** Uygulama şifreleme anahtarı. Kullanım: `CryptoHelper::resolveKey()` (sıra 2). */
    public const APP_KEY = 'APP_KEY';

    /** Şifreleme anahtarı yedeği; `APP_KEY` yoksa kullanılır. Kullanım: `CryptoHelper::resolveKey()` (sıra 3). */
    public const ENCRYPTION_KEY = 'ENCRYPTION_KEY';

    /** Operatörün verdiği eski gömülü tuz (legacy). Kullanım: `CryptoHelper::legacySaltApproval()`. */
    public const RBN_LEGACY_SALT = 'RBN_LEGACY_SALT';

    /**
     * Legacy tuz kill-switch AÇIK onayı.
     * Kullanım: `CryptoHelper::legacySaltApproval()`.
     * DİKKAT: yorumu `1|true|on|yes` listesiyle yapılır (geriye uyum),
     * `Env::flag()` / `normalizeSwitch` DEĞİL - dönüştürülmemiştir.
     */
    public const RBN_ALLOW_LEGACY_SALT = 'RBN_ALLOW_LEGACY_SALT';

    /* ------------------------------------------------------------------
     * VERİTABANI BAĞLANTI KİMLİĞİ (A0-8 / FW-TEK-SECRETS-DOSYASI-161)
     * ---------------------------------------------------------------- */

    /**
     * Ortak (common) veritabanı kullanıcı adı - kimlik, sır DEĞİLDİR.
     * Kullanım: `DbProfileResolver::user(CommonDbData::class)`.
     */
    public const COMMON_DB_USER = 'COMMON_DB_USER';

    /** Ortak veritabanı parolası. Kullanım: `DbProfileResolver::password(CommonDbData::class)`; çözülemezse fail-closed. */
    public const COMMON_DB_PASS = 'COMMON_DB_PASS';

    /** Proje veritabanı kullanıcı adı - kimlik, sır DEĞİLDİR. Kullanım: `DbProfileResolver::user()` (proje profili). */
    public const DB_USER = 'DB_USER';

    /** Proje veritabanı parolası. Kullanım: `DbProfileResolver::password()` (proje profili); çözülemezse fail-closed. */
    public const DB_PASS = 'DB_PASS';

    /**
     * [FW-DB-PROFIL] Proje DB PROFİLİ seçimi (`local` | `production`).
     * Kullanım: `ProjectDbProfileResolver::activeProfile()`.
     *
     * Boş/tanımsız → ortam otomatik belirlenir (HTTP'te `is_local()`, CLI'da
     * framework kökündeki tam `localhost` yol segmenti). Sunucu cron'ı gibi
     * HTTP'siz ortamlarda operatörün kararı AÇIKça bildirebilmesi içindir.
     * `Env::flag()` DEĞİL: yalnız `local`/`production` kabul edilir, diğer
     * her değer "karar yok" sayılır (yazım hatası profili çalıştırmaz).
     */
    public const RBN_DB_PROFILE = 'RBN_DB_PROFILE';

    /* ------------------------------------------------------------------
     * KORUMA / HATA AYIKLAMA KAPILARI
     * ---------------------------------------------------------------- */

    /** Koruma kontrolü hata verirse ENGELLE mi geçir mi (varsayılan fail-closed). Kullanım: `SystemGuardHandler::resolveFailClosed()`. */
    public const RBN_GUARD_FAILCLOSED = 'RBN_GUARD_FAILCLOSED';

    /**
     * Operatörün açık hata ayıklama kapısı. Kullanım: `PreBoot::envOverrideRequested()`
     * (orada `define(RBN_DEBUG)` olur).
     * DİKKAT: `Env::flag()` DEĞİL, kendi `1|true|on|yes|development` listesiyle
     * okunur; varsayılan KAPALI'dır (belirsizlik burada "açık"a düşmez).
     */
    public const RBN_DEBUG = 'RBN_DEBUG';

    /** Operatörün açık geliştirme modu kapısı; `RBN_DEBUG` ile aynı liste. Kullanım: `PreBoot::envOverrideRequested()`. */
    public const RBN_DEV = 'RBN_DEV';

    /** Uygulama ortamı (`development` / `production`). Kullanım: `AiUsageManager`, `DebugHelper`, `rbn` CLI. */
    public const APP_ENV = 'APP_ENV';

    /**
     * Tanılama (log-only) günlüklerinin saatlik kapısı; `0|false|off|no|hayir`
     * ile KAPALIR (kısıt uygulanmaz, her çağrı yazar).
     *
     * Kullanım: `LogThrottle::enabled()` (`Core/System/Storage/LogThrottle.php`).
     * Varsayılan AÇIK — gürültü üretimde sınırlıdır, görünürlük kaybolmaz.
     * YALNIZ test/ölçüm amaçlıdır; `RBN_DEV`/`RBN_DEBUG` kapıları DEĞİŞTİRİLMEZ
     * (yerelde de throttle açık kalır).
     */
    public const RBN_LOG_THROTTLE = 'RBN_LOG_THROTTLE';

    /* ------------------------------------------------------------------
     * İŞYABENİ / MODULE JETONLARI (gizli)
     * ---------------------------------------------------------------- */

    /** İçerik fabrikası worker endpoint jetonu; boş dönerse endpoint fail-closed reddeder. Kullanım: `WorkerTokenService::configuredToken()`. */
    public const RBN_WORKER_TOKEN = 'RBN_WORKER_TOKEN';

    /** Module API jetonu; boş dönerse API istekleri fail-closed reddedilir. Kullanım: `ProjectStatusService::configuredToken()`. */
    public const RBN_CREW_TOKEN = 'RBN_CREW_TOKEN';

    /** Bazı FastCGI/Apache kurulumlarında jeton `REDIRECT_` önekiyle gelir; yedek kaynak. Kullanım: `WorkerTokenService::configuredToken()`. */
    public const REDIRECT_RBN_WORKER_TOKEN = 'REDIRECT_RBN_WORKER_TOKEN';

    /** `REDIRECT_` önekli Module jetonu (yedek kaynak). Kullanım: `ProjectStatusService::configuredToken()`. */
    public const REDIRECT_RBN_CREW_TOKEN = 'REDIRECT_RBN_CREW_TOKEN';

    /* ------------------------------------------------------------------
     * DEPOLAMA / YÜKLEME DİZİNLERİ
     * ---------------------------------------------------------------- */

    /** Diskteki yükleme kök dizini; boşsa `Paths::project()->uploads()`. Kullanım: `MediaIngestService::uploadsRoot()`. */
    public const RBN_UPLOADS_DIR = 'RBN_UPLOADS_DIR';

    /** Yükleme dosyalarının public URL öneki; boşsa `/uploads`. Kullanım: `MediaIngestService::publicUrl()`. */
    public const RBN_UPLOADS_URL_BASE = 'RBN_UPLOADS_URL_BASE';

    /** Module gelen kutusu (görev) dizini; boşsa Module kökünden türetilir. Kullanım: `ProjectStatusService::taskDirectory()`. */
    public const RBN_CREW_INBOX_DIR = 'RBN_CREW_INBOX_DIR';

    /** Module API veri dizini; boşsa kök dizinden türetilir. Kullanım: `ProjectStatusService::dataDirectory()`. */
    public const RBN_CREW_DATA_DIR = 'RBN_CREW_DATA_DIR';

    /** `REDIRECT_` önekli Module veri dizini (yedek kaynak). Kullanım: `ProjectStatusService::dataDirectory()`. */
    public const REDIRECT_RBN_CREW_DATA_DIR = 'REDIRECT_RBN_CREW_DATA_DIR';

    /* ------------------------------------------------------------------
     * YARDIMCI
     * ---------------------------------------------------------------- */

    /**
     * Telegram test router'ında mesajlar arasında beklenecek milisaniye.
     * Kullanım: `projects/<proje>/Tests/Telegram/router.php` (yalnız TEST).
     * Framework ayarı DEĞİLDİR; kayıt dışında bırakılırsa o test router'ı
     * fail-closed hata alır, bu yüzden merkezî kayıtta tutulur.
     */
    public const TG_SEND_DELAY_MS = 'TG_SEND_DELAY_MS';

    /* ------------------------------------------------------------------
     * [ BAYRAK YORUMU - buraya taşındı (Env.php'den) ]
     * ---------------------------------------------------------------- */

    /**
     * `Env::flag()` / `normalizeSwitch()` için "KAPALI" sayılan değerler.
     *
     * DİKKAT (A0-3 TEK MERKEZİ): bu liste `ShieldSettingsRepository::
     * KAPALI_DEGERLER` ile **birebir aynı** olmak ZORUNDADIR; birim testi
     * `fw_env_kayit.php` (C kümesi) eşitliği doğrular. Burada ikinci bir
     * yorum listesi YAZILMAZ, `Env::normalizeSwitch()` yalnız bu sabite bakar.
     */
    public const OFF_VALUES = ['0', 'false', 'off', 'no', 'hayir'];

    /**
     * GİZLİ anahtarların listesi: bu adların **değeri** hata mesajına, loga,
     * istisna metnine veya bu dosyaya ASLA yazılmaz; yalnız adı tutulur.
     * Kullanım: `Env` (sızdırmazlık kontrolü).
     */
    public const SECRET_KEYS = [
        self::APP_KEY,
        self::ENCRYPTION_KEY,
        self::RBN_LEGACY_SALT,
        self::COMMON_DB_PASS,
        self::DB_PASS,
        self::RBN_WORKER_TOKEN,
        self::RBN_CREW_TOKEN,
        self::REDIRECT_RBN_WORKER_TOKEN,
        self::REDIRECT_RBN_CREW_TOKEN,
    ];

    /**
     * Kayıtlı TÜM ortam değişkeni adları (sıra = okuma önceliği değil, katalog).
     * Kullanım: `Env` (kayıtsız adı fail-closed reddetmek için).
     */
    public const ALL_KEYS = [
        self::APP_KEY,
        self::ENCRYPTION_KEY,
        self::RBN_LEGACY_SALT,
        self::RBN_ALLOW_LEGACY_SALT,
        self::COMMON_DB_USER,
        self::COMMON_DB_PASS,
        self::DB_USER,
        self::DB_PASS,
        self::RBN_DB_PROFILE,
        self::RBN_GUARD_FAILCLOSED,
        self::RBN_DEBUG,
        self::RBN_DEV,
        self::APP_ENV,
        self::RBN_LOG_THROTTLE,
        self::RBN_WORKER_TOKEN,
        self::RBN_CREW_TOKEN,
        self::REDIRECT_RBN_WORKER_TOKEN,
        self::REDIRECT_RBN_CREW_TOKEN,
        self::RBN_UPLOADS_DIR,
        self::RBN_UPLOADS_URL_BASE,
        self::RBN_CREW_INBOX_DIR,
        self::RBN_CREW_DATA_DIR,
        self::REDIRECT_RBN_CREW_DATA_DIR,
        self::TG_SEND_DELAY_MS,
    ];
}