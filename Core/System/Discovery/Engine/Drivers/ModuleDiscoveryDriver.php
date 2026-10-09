<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\System\Discovery\Engine\Drivers;

use ReflectionClass;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use Rbn\Framework\Core\Routes\Route;
use Rbn\Framework\Core\System\Paths\Paths;
use Rbn\Framework\Core\Base\Attributes\Component;
use Rbn\Framework\Core\System\Paths\Contexts\ModuleContext;
use Rbn\Framework\Core\System\Registries\SystemRegistry;
use Rbn\Framework\Core\Support\Definitions\System\NamespaceMap;
use Rbn\Framework\Core\System\Discovery\Engine\Cache\ComponentMapper;
use Rbn\Framework\Core\Base\Concerns\Identity\SovereignIdentity;
use Rbn\Framework\Core\Base\Concerns\Data\ResolvesProjectConfigTrait;
use Rbn\Framework\Core\Support\Bridges\Traits\NormalizationTrait;
use Rbn\Framework\Core\System\Discovery\Clusters\Structure\Folder\FolderContext;

/**
 * ModuleDiscoveryDriver - Centralized Physical Discovery Engine 🕵️‍♂️🛰️⚓
 * 
 * RBN Framework: Core driver for scanning disk bundles and components via Attributes.
 * Encapsulated purely inside the Core/System/Discovery cluster.
 */
class ModuleDiscoveryDriver
{
    use SovereignIdentity;
    use ResolvesProjectConfigTrait;
    use NormalizationTrait;

    /** @var array Track loaded module routes to prevent duplication 🛡️⚓ */
    private static array $loadedRoutes = [];

    /** @var array|null Static RAM Cache for discovered bundles 🧠⚡ */
    private static ?array $bundlesCache = null;

    /** @var array Static RAM Cache for discovered components 🧠⚡ */
    private static array $componentsCache = [];

    /**
     * Clear memory cache (useful when switching projects or in CLI) 🧼
     */
    public static function resetMemory(): void
    {
        self::$loadedRoutes = [];
        self::$bundlesCache = null;
        self::$componentsCache = [];
    }

    /**
     * [FW-094-CRAWLER / Ö-2] Bir modülün rotalarının yüklendiğini İŞARETLE 🛡️⚓
     *
     * KÖK NEDEN: `$loadedRoutes` bayrağı yalnızca `registerBundles()` içinde
     * kontrol edilip doldurulurdu (`:67` kontrol → `:71` yazım). Ama
     * `ModuleData::registerRoutes()` İKİ AYRI YOLDAN çağrılır:
     *   1. `Core/Routes/Mappings/web.php:33` → `Route::module('frontend', …)->load()`
     *   2. `Kernel/Stages/Routing.php:38` → `registerBundles('routes')` → `:89`
     *      `Route::module(…)->load()`
     * 1. yol bayrağa dokunmadığı için 2. yükleme engellenemiyordu.
     *
     * ÖLÇÜM (17/17 proje, `docs/agent-results/FW-CRAWLER-COKLU-OLCUM-team member-6eb7f5.md`):
     * statik GET rotaları iki kez kayıtlıydı — toplam 95 çift kayıt; sitemap'e
     * 63 fazladan `<loc>`, `llms.txt`'e 62 fazladan satır yazılıyordu.
     * Tek koşul: aktif modülün `ModuleData::registerRoutes()`'ında ≥1 statik
     * GET rotası (modül sayısı/alt host sayısıyla ilgisi YOK).
     *
     * Bayrak, rotalar GERÇEKTEN yüklendikten SONRA konur: `Route::load()`
     * `registerRoutes()`'ı çağırdıktan sonra buraya gelir. Önce konulursa
     * ikinci yükleme engellenirken rota hiç yüklenmemiş olurdu.
     *
     * [Ö-2 / ikinci katman] ANAHTAR KANONİKLEŞTİRİLİR. `Route::load()` sınıf
     * adını BAŞLANGIÇTAKİ `\` ile üretir (`'\\' . $baseNamespace . …`),
     * `discoverBundles()` ise `\` İÇERMEZ. `class_exists()` ikisini de kabul
     * eder ama dizi anahtarı olarak İKİ FARKLI değerdir; bu yüzden bayrak
     * yazıldıktan sonra `isset()` kontrolü yine de kaçırır ve ikinci yükleme
     * engellenmez. Anahtar `ltrim($class, '\\')` ile tek biçime indirilir —
     * `registerBundles()` içindeki kontrol ve yazım da aynı biçimi kullanır.
     *
     * @param string $bundleClass Tam `ModuleData` sınıf adı
     */
    public static function markRoutesLoaded(string $bundleClass): void
    {
        self::$loadedRoutes[self::routeKey($bundleClass)] = true;
    }

    /**
     * [FW-094-CRAWLER / Ö-2] Çift kayıt bayrağının kanonik anahtar biçimi 🛡️
     *
     * @return string Baştaki `\` temizlenmiş sınıf adı
     */
    private static function routeKey(string $bundleClass): string
    {
        return ltrim(trim($bundleClass), '\\');
    }

    /**
     * Registers and maps all active bundles (RBN Framework & Project) 🏛️🛰️⚓
     */
    public function registerBundles(string $type = 'map', ?array $bundles = null): mixed
    {
        if ($bundles === null) {
            $bundles = $this->discoverBundles();
        }

        if ($type === 'routes') {
            $uniqueClasses = array_unique(array_values($bundles));
            $moduleDataDriver = new ModuleDataDriver();

            foreach ($uniqueClasses as $bundleClass) {
                // [FW-094-CRAWLER / Ö-2] Anahtar kanonikleştirilir: `Route::load()`
                // sınıf adını baştaki `\` ile, keşif ise onsuz üretir. İki biçim
                // `class_exists()` için aynı ama dizi anahtarı olarak FARKLI
                // olduğu için bayrak tutmuyor ve modül rotaları iki kez yükleniyordu.
                if (!class_exists($bundleClass) || isset(self::$loadedRoutes[self::routeKey((string) $bundleClass)])) {
                    continue;
                }

                self::$loadedRoutes[self::routeKey((string) $bundleClass)] = true;

                // Provider dependency resolution
                $id = $moduleDataDriver->resolveFromClass($bundleClass);
                if (!empty($id['name'])) {
                    if (strtolower($id['source']) === 'frontend') {
                        $projectKey = function_exists('project_key') ? project_key() : '';
                        $activeModule = \Rbn\Framework\Core\Base\Services\BaseService::get()->getRouteConfig($projectKey, 'module');
                        if (!empty($activeModule) && strcasecmp((string) $id['name'], (string) $activeModule) !== 0) {
                            continue;
                        }
                    }

                    if (strtolower($id['source']) === 'backend') {
                        Route::middleware('admin')->panel('admin')->group(function () use ($id) {
                            Route::module($id['source'], $id['name'])->load();
                        });
                    } else {
                        Route::module($id['source'], $id['name'])->load();
                    }
                }
            }
            return null;
        }

        if ($type === 'list') {
            return $bundles;
        }

        if ($type === 'map') {
            $map = [];
            $uniqueBundles = array_unique(array_values($bundles));
            foreach ($uniqueBundles as $bundleClass) {
                if (class_exists($bundleClass)) {
                    $bundle = new $bundleClass();
                    if (method_exists($bundle, 'registerMap')) {
                        $map = array_merge_recursive($map, $bundle->registerMap());
                    }
                }
            }
            return $map;
        }

        return null;
    }

    /**
     * Discover and get all registered Bundles (Autonomous) 🚀🛰️⚓
     */
    public function discoverBundles(): array
    {
        if (self::$bundlesCache !== null) {
            return self::$bundlesCache;
        }

        $bundles = [];
        $modulePath = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, Paths::project()->module()), DIRECTORY_SEPARATOR);
        $ns = NamespaceMap::PROJECT_PREFIX;
        $baseNamespace = $ns . 'Modules';

        if (is_dir($modulePath)) {
            // 🎼 Dynamic Context Scan (Supports backend, Backend, BACKEND, frontend, etc.)
            $contextDirs = array_diff(scandir($modulePath), ['.', '..']);
            foreach ($contextDirs as $rawContext) {
                $path = $modulePath . DIRECTORY_SEPARATOR . $rawContext;
                if (!is_dir($path)) {
                    continue;
                }

                $contextNorm = ucfirst(strtolower($rawContext)); // Standardize to PascalCase for Namespace
                $ctxKey = strtolower($rawContext);

                foreach (array_diff(scandir($path), ['.', '..']) as $module) {
                    $moduleDataFile  = $path . DIRECTORY_SEPARATOR . $module . DIRECTORY_SEPARATOR . FolderContext::DATA . DIRECTORY_SEPARATOR . FolderContext::MODULE_DATA . '.php';
                    $moduleModelFile = $path . DIRECTORY_SEPARATOR . $module . DIRECTORY_SEPARATOR . FolderContext::MODELS . DIRECTORY_SEPARATOR . FolderContext::MODULE_DATA . '.php';

                    if (is_file($moduleDataFile)) {
                        $class = "{$baseNamespace}\\{$contextNorm}\\{$module}\\" . FolderContext::DATA . "\\" . FolderContext::MODULE_DATA;
                    } elseif (is_file($moduleModelFile)) {
                        $class = "{$baseNamespace}\\{$contextNorm}\\{$module}\\" . FolderContext::MODELS . "\\" . FolderContext::MODULE_DATA;
                    } else {
                        continue;
                    }

                    $modKey = strtolower($module);

                    $bundles["{$ctxKey}.{$modKey}"] = $class;
                    $bundles[$modKey] = $class;
                }
            }
        }

        // 🎼 Framework Bundles Scan (RbnSuite, Internal, RBN Framework) 🏛️⚓
        $frameworkBundles = SystemRegistry::sovereignBundles();
        foreach ($frameworkBundles as $bundleClass) {
            if (!class_exists($bundleClass)) {
                continue;
            }
            $reflection = new ReflectionClass($bundleClass);
            $nsParts = explode('\\', $reflection->getNamespaceName());
            $bundleName = str_replace('ModuleData', '', $reflection->getShortName());
            if (empty($bundleName)) {
                // If named just ModuleData, pick parent folder name
                $bundleName = basename(dirname(dirname($reflection->getFileName())));
            }

            $modKey = strtolower($bundleName);
            $cleanKey = (string) preg_replace('/^rbn/i', '', $modKey);

            // Context prefix from namespace (e.g., suite, internal, etc.)
            $suiteIndex = array_search('Bundles', $nsParts);
            $ctxKey = ($suiteIndex !== false && isset($nsParts[$suiteIndex + 1])) ? strtolower($nsParts[$suiteIndex + 1]) : 'suite';

            $bundles["{$ctxKey}.{$modKey}"] = $bundleClass;
            $bundles["{$ctxKey}.{$cleanKey}"] = $bundleClass;
            $bundles[$modKey] = $bundleClass;
            $bundles[$cleanKey] = $bundleClass;
        }

        return self::$bundlesCache = $bundles;
    }

    /**
     * Autonomous Component Discovery for registerMap 🚀🛰️⚓
     */
    public function discoverComponents(string $folder): array
    {
        if (isset(self::$componentsCache[$folder])) {
            return self::$componentsCache[$folder];
        }

        $mapper = new ComponentMapper();
        $cached = $mapper->get($folder);
        if (is_array($cached)) {
            return self::$componentsCache[$folder] = $cached;
        }

        // Önbellek yoksa disk taramasını gerçekleştir 🔍
        $components = [];
        $ns = NamespaceMap::PROJECT_PREFIX;

        // A. Project Core / App / Packages / Bundles scanning
        $targets = [
            'App' => rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, Paths::project()->app() . DIRECTORY_SEPARATOR . $folder), DIRECTORY_SEPARATOR),
            'Core' => rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, Paths::project()->core() . DIRECTORY_SEPARATOR . $folder), DIRECTORY_SEPARATOR),
            'Packages' => rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, Paths::workspace() . DIRECTORY_SEPARATOR . 'Packages' . DIRECTORY_SEPARATOR . $folder), DIRECTORY_SEPARATOR),
            'Bundles' => rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, Paths::workspace() . DIRECTORY_SEPARATOR . 'Bundles' . DIRECTORY_SEPARATOR . $folder), DIRECTORY_SEPARATOR),
        ];

        foreach ($targets as $subNamespace => $targetPath) {
            $baseNamespace = $ns . $subNamespace . '\\' . str_replace(['/', '\\'], '\\', $folder);
            if (!is_dir($targetPath)) {
                $lowPath = strtolower($targetPath);
                if (is_dir($lowPath)) {
                    $targetPath = $lowPath;
                } else {
                    continue;
                }
            }

            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($targetPath));
            foreach ($iterator as $file) {
                if ($file->isDir() || $file->getExtension() !== 'php') {
                    continue;
                }
                $this->processComponentFile($file->getPathname(), $targetPath, $baseNamespace, $components);
            }
        }

        // B. Project Modules/ scanning (Modules/Backend, Modules/Frontend, etc.) 🚀
        $moduleRoot = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, Paths::project()->module()), DIRECTORY_SEPARATOR);
        if (is_dir($moduleRoot)) {
            $contextDirs = array_diff(scandir($moduleRoot), ['.', '..']);
            foreach ($contextDirs as $rawContext) {
                $ctxPath = $moduleRoot . DIRECTORY_SEPARATOR . $rawContext;
                if (!is_dir($ctxPath))
                    continue;
                $contextNorm = ucfirst(strtolower($rawContext));

                foreach (array_diff(scandir($ctxPath), ['.', '..']) as $mod) {
                    $targetPath = $ctxPath . DIRECTORY_SEPARATOR . $mod . DIRECTORY_SEPARATOR . $folder;
                    if (!is_dir($targetPath)) {
                        $targetPath = $ctxPath . DIRECTORY_SEPARATOR . $mod . DIRECTORY_SEPARATOR . ucfirst(strtolower($folder));
                    }
                    if (!is_dir($targetPath)) {
                        $targetPath = $ctxPath . DIRECTORY_SEPARATOR . $mod . DIRECTORY_SEPARATOR . strtolower($folder);
                    }
                    if (!is_dir($targetPath))
                        continue;

                    $baseNamespace = $ns . 'Modules\\' . $contextNorm . '\\' . $mod . '\\' . ucfirst($folder);
                    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($targetPath));
                    foreach ($iterator as $file) {
                        if ($file->isDir() || $file->getExtension() !== 'php')
                            continue;
                        $this->processComponentFile($file->getPathname(), $targetPath, $baseNamespace, $components);
                    }
                }
            }
        }

        // Bulunan sonuçları ComponentMapper ile kaydet 💾
        $mapper->set($folder, $components);

        return self::$componentsCache[$folder] = $components;
    }

    /**
     * Inspect a PHP file for #[Component] attribute and register its alias in component map 🧬
     */
    private function processComponentFile(string $physicalPath, string $targetPath, string $baseNamespace, array &$components): void
    {
        $physicalPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $physicalPath);
        if (!is_file($physicalPath)) {
            return;
        }

        // ⚡ Hızlı Akıllı Süzgeç: İçinde #[Component yoksa anında atla!
        $fileContent = @file_get_contents($physicalPath);
        if ($fileContent === false || !str_contains($fileContent, '#[Component')) {
            return;
        }

        $targetPathNorm = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $targetPath);
        $relativePath = str_ireplace([$targetPathNorm, '.php'], '', $physicalPath);
        $relativePath = str_replace([DIRECTORY_SEPARATOR, '/'], '\\', $relativePath);

        $className = rtrim($baseNamespace, '\\') . '\\' . ltrim($relativePath, '\\');
        $className = str_replace('\\\\', '\\', $className);

        require_once $physicalPath;

        if (class_exists($className, true)) {
            $reflect = new ReflectionClass($className);
            $attr = $reflect->getAttributes(Component::class);
            if (!empty($attr)) {
                $compObj = $attr[0]->newInstance();
                $alias = $compObj->alias;
                $type = $compObj->type;

                if ($alias) {
                    $components["{$type}.{$alias}"] = $className;
                    if (!isset($components[$alias])) {
                        $components[$alias] = $className;
                    }
                }
            }
        }
    }

    /**
     * Resolve a specific Module Context via Deterministic Convention 📦🛰️⚓⚖️
     */
    public function resolveModuleContext(string $name, string $source = 'auto'): ModuleContext
    {
        $clean = $this->toPascalCase((string) preg_replace('/^rbn/i', '', strtolower($name)));
        $searchKey = strtolower($name);
        $discovered = $this->discoverBundles();

        // 1. Hedef anahtarı belirle (Context öncelikli veya auto)
        if ($source !== 'auto') {
            $key = strtolower($source) . '.' . $searchKey;
        } else {
            $key = isset($discovered['frontend.' . $searchKey]) ? 'frontend.' . $searchKey : 'backend.' . $searchKey;
        }

        $targetClass = $discovered[$key] ?? ($discovered[$searchKey] ?? null);
        if ($targetClass && class_exists($targetClass)) {
            return $this->createModuleContext($clean, $source, $targetClass);
        }

        return new ModuleContext($clean, 'Unknown', '');
    }

    /**
     * Internal: Helper to build ModuleContext via Reflection 🔮
     */
    private function createModuleContext(string $name, string $source, string $dataClass): ModuleContext
    {
        $reflection = new ReflectionClass($dataClass);
        $rootPath = dirname(dirname($reflection->getFileName()));
        $physicalIdentity = basename($rootPath);
        $baseNamespace = dirname(str_replace('\\', '/', $reflection->getNamespaceName()));
        $baseNamespace = str_replace('/', '\\', $baseNamespace);

        return new ModuleContext($physicalIdentity, $source, $rootPath, $baseNamespace);
    }
}
