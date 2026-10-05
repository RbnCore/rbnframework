<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\System\Kernel\Base;

use Rbn\Framework\Core\System\Paths\Paths;
use Rbn\Framework\Core\Services\Gatekeepers\BootSentinel;
use Rbn\Framework\Core\System\Kernel\Guards\AssetDoctor;
use Rbn\Framework\Core\Support\Bridges\Helpers\Library\ProjectVersionResolver;

/**
 * PreBoot - Initial Environment & Autoload Orchestrator 🛰️🧬⚓
 * 
 * RBN 3.5: Masterpiece Survival Stage.
 * Ensures Paths, Autoloader, and Sentinels are ready before the Kernel is born.
 */
class PreBoot
{
    private static bool $initialized = false;

    /**
     * FW-A0-K1-DEBUG-KAPISI-99: Guvenilir ortam tespiti (TEK MERKEZ).
     *
     * Guvenli varsayilan: URETIM. Gelistirme modu YALNIZ su iki kaynaktan acilir:
     *  1) Operator karari: RBN_DEBUG / RBN_DEV ortam degiskeni acikca '1' ise.
     *  2) Yerel istek: Host TAM eslesme (beyaz liste) VEYA `*.test` TAM son-ek
     *     eslesmesi (`str_ends_with('.test')`) **VE** istemci IP'si loopback/ozel ag.
     *
     * ALT DIZGE (`stripos`/`str_contains`) KULLANILMAZ: `www.tester-attacker.com`,
     * `evil.localhost.attacker.com`, `shop.localdomain.co`, `rbncore.tr.test.evil.com`
     * gibi basliklar uzaktan hata ayiklama modunu ACAMAZ.
     */
    public const LOCAL_HOST_ALLOWLIST = [
        'localhost',
        '127.0.0.1',
        '[::1]',
    ];

    /** Uzunlugune gore alt dizge eslesmesi yapilan hassas anahtar kalipleri. */
    private const SENSITIVE_KEY_SUBSTRINGS = [
        'password', 'passwd', 'parola', 'sifre', 'token', 'secret',
        'apikey', 'api_key', 'authorization', 'csrf', 'cookie',
        'kart', 'cvv', 'cvc', 'iban', 'kimlik', 'card', 'credential',
    ];

    /** Yalnizca TAM eslesme ile maskelenen kisa anahtarlar (`tc` -> `match` yakalamasin). */
    private const SENSITIVE_KEY_EXACT = [
        'password', 'passwd', 'pwd', 'pass', 'passphrase', 'sifre', 'parola',
        'sifre_tekrar', 'sifre_again', 'sifre2', 'yeni_sifre', 'eski_sifre',
        'parola_tekrar', 'sifre_doğrulama', 'sifre_dogrulama', 'sifre_dogru',
        'token', 'secret', 'apikey', 'api_key', 'authorization', 'cookie',
        'csrf', 'csrf_token', '_token', 'x_csrf_token',
        'kart_no', 'card', 'card_number', 'cardnumber', 'cvv', 'cvc',
        'iban', 'tc', 'tc_no', 'tcno', 'kimlik', 'kimlik_no', 'dni', 'ssn', 'pin',
    ];

    /**
     * FW-110 / K-02-TR: Turkce -> ASCII normalizasyon haritasi.
     *
     * GEREKCE: `strtolower()` Turkce harfleri ASCII'ye indirgemez ve
     * `preg_replace('/[^a-z0-9]+/', '_', ...)` her Turkce harfi '_' yapar.
     * Sonuc: `Şifre` -> `_ifre`, `ŞİFRE` -> `_fre`, `şifre_tekrar` -> `_ifre_tekrar`.
     * Boylece parola hata ekraninda ACIK yaziliyordu (K-02 sinifi sizinti).
     *
     * `İ` (U+0130) BILINCLI olarak 'i'ye eslenir; mb_lower() gibi bir
     * yardimciya BAKILMAZ (locale bagimli ve platformda farkli sonuc verir).
     */
    private const TR_MAP = [
        'Ç' => 'c', 'Ğ' => 'g', 'İ' => 'i', 'I' => 'i', 'Ö' => 'o', 'Ş' => 's', 'Ü' => 'u',
        'ç' => 'c', 'ğ' => 'g', 'ı' => 'i', 'î' => 'i', 'ö' => 'o', 'ş' => 's', 'ü' => 'u',
        // Yaygin Latin harfi Turkce ekler: `Şifre` yerel kodlamada `Sifre` yazilmis olabilir.
        'Â' => 'a', 'â' => 'a', 'Û' => 'u', 'û' => 'u', 'Î' => 'i',
    ];

    /** Guvenli varsayilan: uretim. */
    public const MASK = '***';

    /**
     * Ortam degiskeni ile acik operator karari (RBN_DEBUG / RBN_DEV).
     *
     * [FW-ENV-KAYIT-160] Okuma TEK kapidan gelir: `Env::string()`.
     * Buradaki ACIK kabul listesi **bilerek korunur** ve `normalizeSwitch` ile
     * DEGISTIRILMEZ: bu kapinin varsayilani KAPALI (fail-closed)'dir; belirsiz
     * bir degerde (`RBN_DEV=banal`) hata ayiklama modu ACILMAMALIDIR.
     * `Env::flag()` kullanmak bu kapiyi tersine cevirirdi.
     *
     * `Env` autoloader'dan ONCE kullanilir (PreBoot en erken asamadir), bu
     * yuzden dosya elle yuklenir - PSR-4 burada henuz yoktur.
     */
    private static function envOverrideRequested(): bool
    {
        require_once __DIR__ . '/../../Config/Env.php';
        $env = 'Rbn\\Framework\\Core\\System\\Config\\Env';

        foreach (['RBN_DEBUG', 'RBN_DEV'] as $name) {
            $value = $env::string($name);
            if ($value === null) {
                continue;
            }
            $normalized = strtolower(trim($value));
            if (in_array($normalized, ['1', 'true', 'on', 'yes', 'development'], true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Host basligini guvenli bicime indirger: kucuk harf, port atilir, IPv6
     * literal korunur. Gecersiz/asinir degerler bos string doner (fail-closed).
     */
    public static function normalizeHost(string $host): string
    {
        $host = strtolower(trim($host));
        if ($host === '' || strlen($host) > 255) {
            return '';
        }

        // IPv6 literal: [::1] / [::1]:8000
        if (str_starts_with($host, '[')) {
            $end = strpos($host, ']');
            if ($end === false) {
                return '';
            }
            $host = substr($host, 0, $end + 1);
            if (!preg_match('/^\[[0-9a-f:.]+\]$/', $host)) {
                return '';
            }

            return $host;
        }

        // Port ayristir: yalniz SONDaki :port (diger kolonlar IPv6 olabilir)
        if (substr_count($host, ':') === 1) {
            $parts = explode(':', $host);
            if (preg_match('/^[0-9]{1,5}$/', $parts[1])) {
                $host = $parts[0];
            }
        }

        // Sondaki nokta normalizasyonu (site.test. -> site.test)
        $host = rtrim($host, '.');

        if ($host === '' || !preg_match('/^[a-z0-9]([a-z0-9.-]*[a-z0-9])?$/', $host)) {
            return '';
        }

        return $host;
    }

    /**
     * Istemci IP'si loopback ya da ozel (RFC1918 / link-local / ULA) agda mi?
     * X-Forwarded-For BILINCLI OLARAK YOK SAYILIR (guvenilmeyen, istemci tarafindan
     * uretilir; guvenilir kaynak proxy zinciri sunucu config'iyle ayrilir).
     */
    public static function isLocalClientAddress(string $addr): bool
    {
        $addr = trim($addr);
        if ($addr === '') {
            return false;
        }

        if (str_starts_with($addr, '[') && str_ends_with($addr, ']')) {
            $addr = substr($addr, 1, -1);
        }

        // FW-110: IPv4-ESLEMELI IPv6 (RFC 4291 ::ffff:0:0/96).
        // `REMOTE_ADDR` dual-stack (IPv6) soketlerde `::ffff:127.0.0.1` olarak
        // gelir; es IPv4 kurallari buna UYMAZ ve guvenilir yerel istemci reddedilir
        // -> gelistirme/hata ayiklama kapisi hic acilmaz, yerel testler kirilir.
        $addr = self::unmapIpv4Mapped($addr);

        if (filter_var($addr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
            // 0.0.0.0 ("any") guvenilir DEGIL
            if ($addr === '0.0.0.0') {
                return false;
            }
            // 127.0.0.0/8 loopback
            if (str_starts_with($addr, '127.')) {
                return true;
            }
            // RFC1918 ozel ag
            if (str_starts_with($addr, '10.') || str_starts_with($addr, '192.168.')) {
                return true;
            }
            if (preg_match('/^172\.(1[6-9]|2[0-9]|3[01])\./', $addr) === 1) {
                return true;
            }
            // 169.254.0.0/16 link-local
            if (str_starts_with($addr, '169.254.')) {
                return true;
            }

            return false;
        }

        if (filter_var($addr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false) {
            $packed = @inet_pton($addr);
            if ($packed === false) {
                return false;
            }
            $hex = bin2hex($packed);
            // ::1 (loopback)
            if ($hex === str_repeat('0', 31) . '1') {
                return true;
            }
            // fc00::/7 (unique local)  -> ilk bayt 0xfc | 0xfd
            if (str_starts_with($hex, 'fc') || str_starts_with($hex, 'fd')) {
                return true;
            }
            // fe80::/10 (link local)
            if (str_starts_with($hex, 'fe8') || str_starts_with($hex, 'fe9')
                || str_starts_with($hex, 'fea') || str_starts_with($hex, 'feb')) {
                return true;
            }

            return false;
        }

        return false;
    }

    /**
     * [FW-IPKATMAN-ON · 2026-10-03 · zeki-6eb7f5] Adres **KESIN olarak** yerel
     * dongu (loopback) adresi mi? YALNIZ `127.0.0.0/8`, `::1` ve bunlarin
     * IPv4-ESLEMELI karsiliklari (`::ffff:127.0.0.1`, `0:0:0:0:0:ffff:...`)
     * ile parantezli IPv6 literal'leri.
     *
     * 🔑 AYRIMI `isLocalClientAddress()`'ten (neden ayrı):
     *   `isLocalClientAddress()` = loopback **+ RFC1918 + link-local + ULA**;
     *   o "yerel gelistirme ortami" kapisi icindir (hata ayiklama/hot-reload).
     *   Buradaki muafiyet ise **CEZA/BAN** katmani icindir: ofis ici ag
     *   (192.168.x / 10.x) gercek istemci olabilir, orayi muaf saymak
     *   "ofis/NAT arkadasi gercekten saldirdi" halinde korumayi kapatirdi.
     *   Bu yuzden ozel ag burada **KAPSAM DIISI**.
     *
     * IPv4-eslemeli cozumleme `unmapIpv4Mapped()` ile PAYLASILIR (ikinci bir
     * IP ayristirici YAZILMAZ; dual-stack soketlerde `REMOTE_ADDR` `::ffff:127.0.0.1`
     * gelir ve metin `str_starts_with('::ffff:')` kontrolleri bicim varyantlarini
     * (`::FFFF:`, tam yazim) kacirirdi).
     *
     * X-Forwarded-For YINE OKUNMAZ (gerekce `isLocalClientAddress()` ile ayni).
     * Girdi gecersizse `false` (fail-closed: "kesinlikle loopback" degilse muafiyet YOK).
     */
    public static function isLoopbackAddress(string $addr): bool
    {
        $addr = trim($addr);
        if ($addr === '') {
            return false;
        }

        if (str_starts_with($addr, '[') && str_ends_with($addr, ']')) {
            $addr = substr($addr, 1, -1);
        }

        // Ayni IKILI esleme (RFC 4291 ::ffff:0:0/96) -> duz IPv4.
        $addr = self::unmapIpv4Mapped($addr);

        if (filter_var($addr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
            // 127.0.0.0/8. `127.` oneki GECERLI IPv4 uzerinde kontrol edildigi icin
            // `127.0.0.1-evil` gibi bir metin buraya GIREMEZ.
            return str_starts_with($addr, '127.');
        }

        if (filter_var($addr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false) {
            $packed = @inet_pton($addr);
            if ($packed === false || strlen($packed) !== 16) {
                return false;
            }
            // ::1 = 15 bayt sifir + 0x01
            return $packed === str_repeat("\0", 15) . "\x01";
        }

        return false;
    }

    /**
     * IPv4-eslemeli IPv6 adresi (`::ffff:a.b.c.d`) duz IPv4 metnine indirger;
     * indirgenemiyse girdiyi OLDUGU GIBI dondurur.
     *
     * YALNIZ `::ffff:0:0/96` (RFC 4291) eslenir: paketin ilk 12 bayti
     * 10 adet `0x00` + `0xff 0xff` olmalidir. `::2` gibi gercek IPv6 adresleri
     * ya da IPv4-compatible (`::a.b.c.d`, ilk 12 bayt sifir) formu **ESLENMEZ** —
     * aksi halde `::` adresi kendi kuralina girer.
     *
     * Karsilastirma IKILI (binary) yapilir; metin karsilastirmasi
     * `str_starts_with($ip, '::ffff:')` bicim varyantlarini (`::FFFF:`, `0:0:0:0:0:ffff:...`)
     * kaçirirdi.
     */
    private static function unmapIpv4Mapped(string $addr): string
    {
        if (filter_var($addr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) === false) {
            return $addr;
        }

        $packed = @inet_pton($addr);
        if ($packed === false || strlen($packed) !== 16) {
            return $addr;
        }

        // 10 x 0x00 + 0xff 0xff
        $oneki = str_repeat("\0", 10) . "\xff\xff";
        if (substr($packed, 0, 12) !== $oneki) {
            return $addr;
        }

        $v4 = inet_ntop(substr($packed, 12));

        return $v4 === false ? $addr : $v4;
    }

    /**
     * Host + istemci IP'sinden guvenilir ortam karari (fail-closed).
     */
    public static function isTrustedLocalEnvironment(string $host, string $addr): bool
    {
        $host = self::normalizeHost($host);
        if ($host === '') {
            return false;
        }

        if (!self::isLocalClientAddress($addr)) {
            return false;
        }

        if (in_array($host, self::LOCAL_HOST_ALLOWLIST, true)) {
            return true;
        }

        // `*.test` TAM son-ek eslesmesi: `rbncore.tr.test.evil.com` GEÇMEZ
        return str_ends_with($host, '.test');
    }

    /**
     * Otonom olarak calisma ortamini tespit eder ve RBN_DEV/RBN_DEBUG sabitlerini
     * tanimlar. 🛰️🔍 (FW-A0-K1-DEBUG-KAPISI-99: fail-closed, alt dizge YOK)
     */
    public static function detectEnvironment(): void
    {
        if (defined('RBN_DEV')) {
            return;
        }

        $rawHost = isset($_SERVER['HTTP_HOST']) ? (string) $_SERVER['HTTP_HOST'] : '';
        $addr = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '';

        $host = self::normalizeHost($rawHost);
        $isLocal = self::envOverrideRequested() || self::isTrustedLocalEnvironment($rawHost, $addr);

        define('RBN_DEV', $isLocal);
        define('RBN_DEBUG', $isLocal); // Default debug to local status, allows later override
        define('DEFAULT_LANGUAGE', 'tr'); // 🌍 RBN 3.5: [MASTERPIECE] Default language sync
        define('APP_NAME', 'RBN CORE'); // 🎼 App Identity
        // [FW-SURUMLEME-2] `APP_VERSION` BURADA TANIMLANMAZ.
        // Gerekce (koddan okunarak): `detectEnvironment()` `orchestrate()` icinde
        // 0. adimda calisir; proje veri onbellegi ise 0.5. adimde
        // (`ProjectDiscovery::getProjectData()`) doldurulur. Yani burada
        // `projects.version` HENUZ OKUNAMAZ. Sabit, onbellek hazir olduktan
        // SONRA `defineAppVersion()` ile tanimlanir. Girdis noktalarinda elle
        // `define('APP_VERSION', ...)` yazmak da ayni sebepten yasaktir.
        define('APP_URL', $isLocal ? 'http://' . $host : 'https://' . $host);
    }

    /**
     * `APP_VERSION` sabitini PROJE surumunden turetir.
     *
     * [FW-SURUMLEME-2] Sabit bir KAYNAK DEGILDIR; tek kaynak master veritabani
     * `projects.version` kolonudur (`ProjectDataMapper` -> `project_data('version')`).
     * Burada yalnizca o deger `ProjectVersionResolver` ile guvenli hale
     * getirilip sabit olarak muhurlenir. Deger yoksa/gecersizse standart
     * baslangic surumu (`0.1.1`) kullanilir — iki parcali `1.0` gibi gecersiz
     * bir varsayilan ASLA yazilmaz.
     *
     * ZAMANLAMA: cagrildigi nokta, proje onbellegi (`Bootstrap::appContext`)
     * doldurulduktan SONRA olmalidir. `handleFastAssets()` ve sentinel bu
     * adimdan sonra calisir; hicbiri `APP_VERSION` okumaz (framework genelinde
     * yalniz sablonlar `{{APP_VERSION}}` yazar ve sablonlar daha sonra
     * derlenir), yani gecikmis tanim guvenlidir.
     */
    public static function defineAppVersion(): void
    {
        if (defined('APP_VERSION')) {
            return;
        }

        // Autoload henuz kurulmamis olabilir; sinif elle dahil edilir
        // (Proje kesfi ile ayni "hayatta kalma" deseni).
        if (!class_exists(ProjectVersionResolver::class, false)) {
            require_once __DIR__ . '/../../../Support/Bridges/Helpers/Library/LogThrottle.php';
            require_once __DIR__ . '/../../../Support/Bridges/Helpers/Library/Version.php';
            require_once __DIR__ . '/../../../Support/Bridges/Helpers/Library/ProjectVersionResolver.php';
        }

        $surum = ProjectVersionResolver::resolveFromCache();

        if (!defined('APP_VERSION')) {
            define('APP_VERSION', $surum);
        }
    }

    /**
     * Orchestrates the early boot diagnostics and environment setup.
     */
    public static function orchestrate(string $publicPath, array $config = []): void
    {
        // [FW-CANLI-ONCESI-2] PHP surum kapisi (>= 8.3) — autoload'dan SONRAKI
        // savunma katmani. `domains/*/index.php` ve `rbn` zaten autoload'dan
        // once kapiyi gecirir; burada kapi TEK MERKEZE de baglanir, boylece
        // yeni bir giris noktasi eklendiginde kapidan kacinilamaz.
        require_once __DIR__ . '/PhpVersionGate.php';
        PhpVersionGate::enforce();

        if (self::$initialized)
            return;

        // 🌍 RBN 3.5: Set Timezone & Locale globally for all Web and CLI/Cron requests ⏰
        date_default_timezone_set('Europe/Istanbul');
        setlocale(LC_ALL, 'tr_TR.UTF-8', 'tr_TR', 'tr', 'turkish');

        // 0. Detect Environment (Autonomous Dev Mode) 🛰️🔍
        self::detectEnvironment();

        // 0.5. Project Discovery (Dinamik Proje Keşfi) 🛰️🏛️⚓
        require_once __DIR__ . '/../Stages/ProjectDiscovery.php';
        $projectData = \Rbn\Framework\Core\System\Kernel\Stages\ProjectDiscovery::getProjectData($publicPath, $config['project_key'] ?? null);

        if (!empty($projectData)) {
            // Bulunan projeyi sistem bağlamına mühürle 🧬
            \Rbn\Framework\Core\System\Kernel\Bootstrap::setAppContext('project_data', $projectData);
            \Rbn\Framework\Core\System\Kernel\Bootstrap::setAppContext('project_key', $projectData['project_key']);
            // Config içindeki key'i güncelle (Fallback'leri ezmek için)
            $config['project_key'] = $projectData['project_key'];
        }

        // 1. Resolve Project DNAs (Paths) 🧬🏛️
        self::initPaths($publicPath, $config);

        // 1.5 [FW-SURUMLEME-2] `APP_VERSION` = PROJE surumu.
        // Buraya kadar projeler onbellegi doldu (0.5) ve `Paths` hazir (1), yani
        // `Projects.version` OKUNABILIR durumda. Once `3.5.0` gibi bir sabit
        // yaziliyordu; o kalinti kaldirildi (bkz. `defineAppVersion()`).
        self::defineAppVersion();

        // 2. Register the Final Sentinel (En erken aşamada zırhı giyiyoruz) 🛡️🚨
        self::registerSentinel();

        // 2.5 Load the Diagnostic Watchdog (Global Panic Hub) 🏺⚔️⚓
        require_once __DIR__ . '/../Guards/Watchdog.php';

        // 3. Activate Survival Autoloader (Artık koruma altındayız) 🚀
        self::initAutoloader();

        // 4. Fast-Path Asset Serving 🩺⚡
        self::handleFastAssets();

        self::$initialized = true;
    }

    /**
     * Hata ayiklama ekranlarinda gosterilen veri kumesinde hassas alan maskesi.
     * FW-A0-K1-DEBUG-KAPISI-99 (K-2): `password`, `token`, `csrf*`, `api_key`,
     * `authorization`, `cookie`, `kart_no`/`card`/`cvv`, `iban`, `tc`/`kimlik`
     * → `***`. Ic ice dizilerde de uygulanir. Buyuk/kucuk harf duyarsiz.
     *
     * FW-110 / K-02-TR: once Turkce harfler ASCII'ye indirgenir (`ŞİFRE` -> `sifre`),
     * sonra kucuk harf + ayrac normalizasyonu yapilir. Aksi halde Turkce alan adlari
     * (`şifre`, `sifre_tekrar`) maske listesinden KACARDI.
     */
    public static function isSensitiveKey(string $key): bool
    {
        $normalized = strtr(trim($key), self::TR_MAP);
        $normalized = strtolower($normalized);
        $normalized = (string) preg_replace('/[^a-z0-9]+/', '_', $normalized);
        $normalized = trim($normalized, '_');

        if ($normalized === '') {
            return false;
        }

        if (in_array($normalized, self::SENSITIVE_KEY_EXACT, true)) {
            return true;
        }

        foreach (self::SENSITIVE_KEY_SUBSTRINGS as $needle) {
            if (str_contains($normalized, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Tek deger veya ic ice dizi: hassas alanlari `***` ile degistirir.
     * @param array<mixed> $data
     * @return array<mixed>
     */
    public static function maskSensitiveData(array $data): array
    {
        $out = [];
        foreach ($data as $key => $value) {
            $out[$key] = self::isSensitiveKey((string) $key)
                ? self::MASK
                : (is_array($value) ? self::maskSensitiveData($value) : $value);
        }

        return $out;
    }

    /**
     * Resolves and initializes project and framework paths.
     */
    private static function initPaths(string $publicPath, array $config): void
    {
        // Require Paths manually if not yet autoloaded (Survival Mode)
        require_once __DIR__ . '/../../Paths/Paths.php';

        $rootDir = Paths::workspace();
        $projectData = \Rbn\Framework\Core\System\Kernel\Bootstrap::getAppContext('project_data');

        // 1. Project Key Çözümü
        $projectKey = $config['project_key'] ?? explode('.', str_replace(' ', '', basename($publicPath)))[0];

        // 2. Proje Yolu Belirleme (Mutlak olarak projects/ dizini altında) 🏛️🛰️
        $customPath = $projectData['custom_path'] ?? $projectKey;
        $projectPath = $rootDir . DIRECTORY_SEPARATOR . 'projects' . DIRECTORY_SEPARATOR . ltrim($customPath, './\\');

        Paths::init($projectPath, $publicPath);
    }

    /**
     * Locates and requires the Composer autoloader.
     */
    private static function initAutoloader(): void
    {
        $frameworkRoot = Paths::frameworkRoot();
        $autoloadPath = $frameworkRoot . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

        if (file_exists($autoloadPath)) {
            require_once $autoloadPath;
        } else {
            // [RBN PANIC] Absolute terminal failure: dependencies missing 💀
            die("<h1>RBN Critical Failure</h1><p>Composer autoloader not found at: {$autoloadPath}</p><p>Please run 'composer install' to initialize the framework engine.</p>");
        }
    }

    /**
     * Attaches the BootSentinel to catch any early-boot disasters.
     */
    private static function registerSentinel(): void
    {
        // Require BootSentinel manually (Survival Mode Protection)
        $sentinelPath = Paths::frameworkRoot() . '/Core/Services/Gatekeepers/BootSentinel.php';
        if (file_exists($sentinelPath)) {
            require_once $sentinelPath;
            BootSentinel::register();
        }
    }

    /**
     * Fast-Path check for static assets (Zero-Dependency Serving).
     */
    private static function handleFastAssets(): void
    {
        if (class_exists(AssetDoctor::class)) {
            AssetDoctor::check();
        }
    }
}
