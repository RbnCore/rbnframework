<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Base\Concerns\Data;

use Rbn\Framework\Core\System\Paths\Paths;
use Rbn\Framework\Core\System\Kernel\Bootstrap;
use Rbn\Framework\Core\System\Kernel\Stages\ProjectDiscovery;

/**
 * ResolvesProjectConfigTrait - Core project configuration, cache, and settings resolver 📡🗺️
 */
trait ResolvesProjectConfigTrait
{
    /** @var array<string, array> Path-keyed cached project route map configurations 🗺️⚡ */
    protected static array $routeMaps = [];

    /** @var array Project Paths and Data Caches (Request Lifetime) 🧠⚡ */
    protected array $projectPathsCache = [];
    protected array $projectDataCache = [];

    /**
     * @var array<string,bool> [FW-ALTYAPI-3 / B] ÖZYİNELEME KESİCİ — çalışmakta
     *   olan `resolveProjectConfig()` çağrıları: "projeAnahtarı\0dosya" => true.
     *
     *   Neden var: `resolveProjectConfig()` → `sanitizeCustomPath()` →
     *   `resolveProjectData()` → `resolveProjectConfig()` zinciri, HENÜZ BULUNMAMIŞ
     *   bir proje anahtarında KENDİNİ BESLİYORDU (sonsuz özyineleme). Master'da
     *   satır bulunamayınca `ProjectDiscovery::getProjectData()` boş döner,
     *   `$isCurrent` false kalır ve özyineleme hiçbir yerde kırılmazdı. Sonuç:
     *   çözülemeyen/yanlış `project_key` ile boot SESSİZCE sonsuza kadar asılı
     *   kalıyordu (her düzey ayrıca bir MySQL bağlantısı+sorgu yapıyordu).
     *
     *   Kesiç yalnız GERÇEK yuvalanmayı keser: aynı (anahtar, dosya) için ikinci
     *   kez giriliş "yapılandırma yok" ([]) ile döner. Ardışık (yuvalanmayan)
     *   çağrılar etkilenmez — kayıt `finally` içinde silinir. Ölçüm:
     *   `fw_boot_project_key_hang.php`.
     */
    private static array $resolvingProjectConfig = [];

    /**
     * Projeye ait routemap önbelleğini sıfırlar 🔄
     */
    public static function resetRouteMapCache(): void
    {
        static::$routeMaps = [];
    }

    /**
     * B-85: PHP'de trait'in `static` alanı, trait'i kullanan HER SINIF için
     * AYRI bir kopyadır. Dolayısıyla `resetRouteMapCache()` yalnızca
     * çağrıldığı sınıfın önbelleğini temizler; diğer sınıfların (controller,
     * repository, service...) önbelleği eski kalır. Bu API tüm hiyerarşiyi
     * temizler. Mevcut `resetRouteMapCache()` imzası ve davranışı DEĞİŞMEDİ.
     */
    public static function resetAllRouteMapCaches(): void
    {
        foreach (get_declared_classes() as $class) {
            if (!method_exists($class, 'resetRouteMapCache')) {
                continue;
            }

            $class::resetRouteMapCache();
        }
    }

    /**
     * Projeye ait routemap yapılandırmasını veya belirli bir anahtarı yükler 🗺️
     */
    public function getRouteConfig(string $projectKey, ?string $key = null)
    {
        $routeMapPath = Paths::project()->configs('project-routemap.php');
        if (!isset(static::$routeMaps[$routeMapPath])) {
            static::$routeMaps[$routeMapPath] = file_exists($routeMapPath) ? (require $routeMapPath) : [];
        }

        $activeMap = static::$routeMaps[$routeMapPath] ?? [];

        // Eğer modül isteniyorsa dinamik olarak çözümle 🧠
        if ($key === 'module') {
            // B-82: `project_key()` guard'sız çağrılıyordu; CLI'de (helper yüklenmeden)
            // `Error: Call to undefined function` veriyordu. B-70 ile aynı desen.
            $projectKey = trim($projectKey)
                ?: (function_exists('project_key') ? (project_key() ?: '') : '');
            $config = $activeMap['view_mapping'][$projectKey] ?? [];
            $module = $config['module'] ?? null;
            if (empty($module)) {
                $bundles = $config['bundles'] ?? [];
                if (!empty($bundles)) {
                    $module = $bundles[0];
                }
            }

            // Fallback olarak ilk sıradaki projeyi varsayılan kabul et
            if (empty($module)) {
                $mapping = $activeMap['view_mapping'] ?? [];
                $firstProject = reset($mapping);
                $module = $firstProject['module'] ?? null;
                $bundles = $firstProject['bundles'] ?? [];
                if (!empty($bundles)) {
                    $module = $bundles[0];
                }
            }

            return $module;
        }

        $config = $activeMap['view_mapping'][$projectKey] ?? [];
        return $key !== null ? ($config[$key] ?? null) : $config;
    }

    /**
     * Hedef projenin domain adresine göre tam URL üretir 🌐🛰️⚓
     */
    public function targetProjectUrl(string $projectKey, string $path = ''): string
    {
        $domain = $this->getRouteConfig($projectKey, 'domain');
        if (empty($domain)) {
            return '/' . ltrim($path, '/');
        }

        // B-81: `core-architecture.md` §7 — ham `$_SERVER` okumak yasak.
        // Kaynak: `$this->request` nesnesi (`isSecure()` + `fullUrl()`).
        // `property_exists` kullanılır: `isset()`/`??` `BaseComponent::__get`
        // keşif zincirini tetiklerdi. Request yoksa (CLI/erken boot) güvenli
        // varsayılanlara düşülür — davranış DEĞİŞMEZ, sadece ham okuma kalkar.
        $request = property_exists($this, 'request') ? $this->request : null;
        $isSecure = is_object($request) && method_exists($request, 'isSecure')
            ? (bool) $request->isSecure()
            : false;

        $port = 80;
        if (is_object($request) && method_exists($request, 'fullUrl')) {
            $hostPort = parse_url((string) $request->fullUrl(), PHP_URL_PORT);
            if (is_int($hostPort)) {
                $port = $hostPort;
            }
        }

        $scheme = $isSecure ? 'https://' : 'http://';
        $portStr = ($port != 80 && $port != 443 && !str_contains($domain, ':')) ? ":{$port}" : '';

        return $scheme . $domain . $portStr . '/' . ltrim($path, '/');
    }

    /**
     * B-83/B-84: custom_path (master DB'den gelir) dosya yoluna girmeden önce doğrulanır.
     * Yalnız [A-Za-z0-9_-] parçaları "/" ile; "..", nokta, ters eğik çizgi, mutlak yol YOK.
     * Dizin varsa realpath, projects kökünün altında kalmalıdır. Geçersizse '' döner (exception yok).
     */
    protected function sanitizeCustomPath(mixed $candidate): string
    {
        if (!is_string($candidate) || preg_match('#^[A-Za-z0-9_\-]+(?:/[A-Za-z0-9_\-]+)*$#', $candidate) !== 1) {
            return '';
        }

        $full = Paths::workspace() . '/projects/' . $candidate;
        $real = realpath($full);
        if ($real !== false) {
            $rootReal = realpath(Paths::workspace() . '/projects');
            if ($rootReal === false) {
                return '';
            }
            $prefix = rtrim(str_replace('\\', '/', $rootReal), '/') . '/';
            if (!str_starts_with(str_replace('\\', '/', $real) . '/', $prefix)) {
                return '';
            }
        }

        return $candidate;
    }

    /**
     * Projeye ait özel yapılandırma dosyasını çözer (Proje Klasörü İçinden) 🛠️
     */
    public function resolveProjectConfig(string $configFile, ?string $projectKey = null): array
    {
        $activeData = Bootstrap::getAppContext('project_data') ?: [];
        $currentKey = $activeData['project_key'] ?? null;
        $isCurrent = ($projectKey === null || $projectKey === '' || $projectKey === $currentKey);
        $projectKey = $projectKey ?: ($currentKey ?: 'default');

        if (isset($this->projectDataCache[$projectKey][$configFile])) {
            return $this->projectDataCache[$projectKey][$configFile];
        }

        // [FW-ALTYAPI-3 / B] ÖZYİNELEME KESİCİ. Aynı (anahtar, dosya) için
        // zaten çözülmekte olan bir çağrıya yeniden girdiysek, bu yuvalanmadır:
        // "yapılandırma yok" ([]) dönüp zinciri kesiyoruz. Master'da olmayan bir
        // proje anahtarında `resolveProjectData()` her seferinde boş döndüğü için
        // aksi halde bu çağrı KENDİSİNİ çağırarak sonsuza kadar dönerdi.
        $cacheId = $projectKey . "\0" . $configFile;
        if (isset(self::$resolvingProjectConfig[$cacheId])) {
            return [];
        }
        self::$resolvingProjectConfig[$cacheId] = true;

        try {
            $customPath = $this->sanitizeCustomPath($isCurrent
                ? ($activeData['custom_path'] ?? $projectKey)
                : ($this->resolveProjectData('custom_path', $projectKey) ?: $projectKey));

            // B-83/B-84: geçersiz custom_path require yoluna GİRMEZ; "yapılandırma yok" yoluna düşer.
            if ($customPath === '' || preg_match('/^[A-Za-z0-9_\-]+$/', $configFile) !== 1) {
                return [];
            }

            $path = Paths::workspace() . "/projects/{$customPath}/Core/Config/{$configFile}.php";

            if (file_exists($path)) {
                $config = require $path;
                $this->projectDataCache[$projectKey][$configFile] = is_array($config) ? $config : [];
                return $this->projectDataCache[$projectKey][$configFile];
            }

            return [];
        } finally {
            // Kesiç kaydı HER YOLDA temizlenir: aksi halde bir sonraki (yuvalanmayan)
            // çağrı hatalı şekilde "yapılandırma yok" ile dönerdi.
            unset(self::$resolvingProjectConfig[$cacheId]);
        }
    }

    /**
     * Projenin verisini okur (SSoT: Doğrudan Bootstrap AppContext RAM Belleği) 📦
     */
    public function resolveProjectData(string $key, ?string $projectKey = null): mixed
    {
        $activeData = Bootstrap::getAppContext('project_data') ?: [];
        $currentKey = $activeData['project_key'] ?? null;

        // 1. SSoT: Aktif proje için doğrudan RAM'deki Bootstrap Context dizisinden oku (0ms)
        if ($projectKey === null || $projectKey === '' || $projectKey === $currentKey) {
            return $activeData[$key] ?? null;
        }

        // 2. Aktif projeden farklı bir proje anahtarı istendiyse Discovery üzerinden al
        $data = ProjectDiscovery::getProjectData(null, $projectKey);
        if (isset($data[$key])) {
            return $data[$key];
        }

        $configData = $this->resolveProjectConfig('project-data', $projectKey);
        return $configData[$key] ?? null;
    }

    /**
     * Projenin core/config/project-settings.php dosyasından ayar okur (Settings Çözümleme) ⚙️
     */
    public function resolveProjectSetting(string $key, ?string $projectKey = null): mixed
    {
        $settings = $this->resolveProjectConfig('project-settings', $projectKey);
        return $settings[$key] ?? null;
    }

    /**
     * Projenin workspace altındaki fiziki klasör yolunu çözümler 📁🗺️
     */
    public function resolveProjectPath(?string $projectKey = null, string $subPath = ''): string
    {
        $activeData = Bootstrap::getAppContext('project_data') ?: [];
        $currentKey = $activeData['project_key'] ?? null;
        $isCurrent = ($projectKey === null || $projectKey === '' || $projectKey === $currentKey);
        $projectKey = $projectKey ?: ($currentKey ?: 'default');

        if (!isset($this->projectPathsCache[$projectKey])) {
            $customPath = $this->sanitizeCustomPath($isCurrent
                ? ($activeData['custom_path'] ?? $projectKey)
                : ($this->resolveProjectData('custom_path', $projectKey) ?: $projectKey));

            // Geçersizse var olmayan, güvenli bir alt dizine düşer (dosya bulunamaz = yapılandırma yok).
            $this->projectPathsCache[$projectKey] = Paths::workspace() . '/projects/' . ($customPath !== '' ? $customPath : '__gecersiz_custom_path__');
        }

        $basePath = $this->projectPathsCache[$projectKey];

        if (!empty($subPath)) {
            $basePath .= '/' . ltrim($subPath, '/\\');
        }

        return $basePath;
    }

    /**
     * Projenin kök PHP Namespace (İsim Alanı) bilgisini çözümler 🏷️
     */
    public function resolveProjectNamespace(?string $projectKey = null, string $subNamespace = ''): string
    {
        $baseNamespace = "Rbn\\Project";

        if (!empty($subNamespace)) {
            $baseNamespace .= '\\' . ltrim($subNamespace, '\\');
        }

        return $baseNamespace;
    }
}
