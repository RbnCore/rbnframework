<?php

namespace Rbn\Framework\Core\Routes;

use Rbn\Framework\Core\Base\Services\BaseService;
use Rbn\Framework\Core\System\Paths\Paths;
use Rbn\Framework\Core\System\Discovery\Clusters\Logic\Definition\Definition;
use Rbn\Framework\Core\Routes\Engine\Providers\RouteManager;
use Rbn\Framework\Core\Routes\Engine\Providers\Router;
use Rbn\Framework\Core\System\Discovery\Clusters\Structure\Folder\FolderContext;

/**
 * Route - Static Proxy Facade
 * 
 * RBN 3.0: Thin facade that proxies static calls to the central RouteService Engine.
 * Keep this at root for developer accessibility.
 */
class Route
{
    /** @var self|null Facade instance 🏛️ */
    private static ?self $instance = null;

    /** @var array $attributes Active group attributes */
    private static array $attributes = [];

    /**
     * Get instance for fluent chaining
     */
    private static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    /**
     * Get the central RouteService
     */
    private static function service(): RouteManager
    {
        return BaseService::get()->service('route');
    }

    /**
     * Facade Methods
     */
    public static function prefix(string $prefix): self
    {
        $current = self::$attributes['prefix'] ?? '';
        self::$attributes['prefix'] = $current ? rtrim($current, '/') . '/' . ltrim($prefix, '/') : $prefix;
        return self::getInstance();
    }

    public static function middleware($middleware): self
    {
        $current = (array) (self::$attributes['middleware'] ?? []);
        self::$attributes['middleware'] = array_merge($current, (array) $middleware);
        return self::getInstance();
    }

    public static function role(string $role): self
    {
        self::$attributes['role'] = $role;
        return self::getInstance();
    }

    public static function controller(string $controller): self
    {
        self::$attributes['controller'] = $controller;
        return self::getInstance();
    }

    public static function namespace(string $namespace): self
    {
        self::$attributes['namespace'] = $namespace;
        return self::getInstance();
    }

    public static function module(string $moduleOrSource, ?string $moduleName = null): self
    {
        // 🎼 RBN 3.5: [FLEXIBLE SIGNATURE] Hem Route::module('sd') hem de Route::module('suite', 'RbnAdmin') / Route::module('RbnAdmin', 'suite') destekler
        if ($moduleName === null) {
            $name = $moduleOrSource;
            $source = 'auto';
        } else {
            // Eğer ilk parametre Framework paketi ise (suite, internal, backend, frontend vb.)
            $knownSources = ['suite', 'internal', 'packages', 'backend', 'frontend', 'modules'];
            if (in_array(strtolower($moduleOrSource), $knownSources)) {
                $source = $moduleOrSource;
                $name = $moduleName;
            } else {
                $name = $moduleOrSource;
                $source = $moduleName;
            }
        }

        $moduleContext = Paths::module($name, $source);

        // 🎼 RBN 3.5: [STRUCTURAL SOVEREIGN ORCHESTRATION] 🛰️🪐⚓
        // Modülün nerede yaşadığına bak: Bundles (Framework) mi yoksa Module (Project) mi?
        $modulePath = realpath($moduleContext->root()) ?: $moduleContext->root();
        $frameworkBundles = realpath(Paths::framework()->bundles()) ?: Paths::framework()->bundles();
        $projectModules = realpath(Paths::project()->module()) ?: Paths::project()->module();

        // Eğer modül projenin Modules dizinindeyse ve Frontend DEĞİLSE (Backend/Admin ise), prefix eklenir.
        $isFrontend = (strtolower($source) === 'frontend');
        $isProjectFeature = !$isFrontend && ((strpos($modulePath, $projectModules) === 0) || (strpos($modulePath, $frameworkBundles) === false));

        if ($isProjectFeature) {
            $currentPrefix = self::$attributes['prefix'] ?? '';
            $modulePrefix = strtolower($name);

            // 🎼 RBN 3.5: [SOVEREIGN PREFIX MERGE] 🛰️🪐⚓
            self::$attributes['prefix'] = $currentPrefix
                ? rtrim($currentPrefix, '/') . '/' . ltrim($modulePrefix, '/')
                : $modulePrefix;
        }

        self::$attributes['namespace'] = $moduleContext->namespaces()->getBase() . '\\Controllers';
        self::$attributes['active_module'] = $name;
        self::$attributes['active_source'] = $source;

        return new self();
    }

    /**
     * [RBN 3.5] SOVEREIGN ROUTE LOADER 🛰️⚓
     * 
     * Modül içerisindeki ModuleData::registerRoutes() metodunu tetikleyerek
     * özel rotaları otonom ancak kontrollü bir şekilde yükler.
     */
    public function load(): self
    {
        $moduleName = self::$attributes['active_module'] ?? null;
        $source = self::$attributes['active_source'] ?? 'auto';
        $groupAttributes = self::$attributes;

        // Önceden ayarlanmış attributes'ları temizle
        self::$attributes = [];

        if ($moduleName) {
            $moduleContext = Paths::module($moduleName, $source);
            $baseNamespace = $moduleContext->namespaces()->getBase();

            $dataClass  = '\\' . $baseNamespace . '\\' . FolderContext::DATA . '\\' . FolderContext::MODULE_DATA;
            $modelClass = '\\' . $baseNamespace . '\\' . FolderContext::MODELS . '\\' . FolderContext::MODULE_DATA;
            $moduleDataClass = class_exists($dataClass) ? $dataClass : $modelClass;

            if (class_exists($moduleDataClass) && method_exists($moduleDataClass, 'registerRoutes')) {
                // 🎼 [RBN 3.5] Namespace, Prefix & Controller SSoT: Modülün rotalarını Router stack'ine enjekte et 🛡️
                $groupAttributes['namespace'] = $baseNamespace . '\\Controllers';
                unset($groupAttributes['active_module'], $groupAttributes['active_source']);

                Router::getInstance()->group($groupAttributes, function () use ($moduleDataClass) {
                    (new $moduleDataClass())->registerRoutes();
                });

                // [FW-094-CRAWLER / Ö-2] Bu modülün rotaları yüklendi: keşif
                // sürücüsü (ModuleDiscoveryDriver) aynı modülü TEKRAR yüklemesin.
                //
                // KÖK NEDEN: `ModuleData::registerRoutes()` iki ayrı yoldan
                // çağrılıyordu — `Core/Routes/Mappings/web.php:33` ve
                // `Kernel/Stages/Routing.php:38` → `ModuleDiscoveryDriver:89`.
                // Çift kayıt bayrağı (`$loadedRoutes`) yalnızca
                // `registerBundles()` içinde doldurulduğu için ilk yol
                // bayrağa dokunmuyor, ikinci yükleme engellenemiyordu.
                //
                // ÖLÇÜM: 17/17 projede statik GET rotaları iki kez kayıtlıydı;
                // toplam 95 çift kayıt (63 fazladan sitemap `<loc>`,
                // 62 fazladan `llms.txt` satırı).
                //
                // BAYRAK ROTALAR YÜKLENDİKTEN SONRA konur — önce konulursa
                // ikinci yükleme engellenirken rota hiç yüklenmemiş olurdu.
                \Rbn\Framework\Core\System\Discovery\Engine\Drivers\ModuleDiscoveryDriver::markRoutesLoaded($moduleDataClass);
            }
        }

        return $this;
    }

    public static function group(callable $callback): void
    {
        $attr = self::$attributes;
        self::$attributes = [];
        Router::getInstance()->group($attr, $callback);
    }

    public static function get(string $uri, $action): self
    {
        $attr = self::$attributes;
        self::$attributes = [];
        Router::getInstance()->addRoute(['GET'], $uri, $action, $attr);
        return new self();
    }

    public static function post(string $uri, $action): self
    {
        $attr = self::$attributes;
        self::$attributes = [];
        Router::getInstance()->addRoute(['POST'], $uri, $action, $attr);
        return new self();
    }

    public static function any(string $uri, $action): self
    {
        $attr = self::$attributes;
        self::$attributes = [];
        Router::getInstance()->addRoute(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], $uri, $action, $attr);
        return new self();
    }

    public function name(string $name): self
    {
        Router::getInstance()->nameLastRoute($name);
        return $this;
    }

    public static function url(string $name, $params = [], ?string $panel = null, ?string $module = null): string
    {
        if (is_string($params)) {
            $panel = $params;
            $params = [];
        }
        return self::service()->url()->url($name, $params, $panel, $module);
    }

    public static function panel(string $name): self
    {
        $panelPrefix = self::service()->getBasePrefix() . '/' . $name;
        $current = self::$attributes['prefix'] ?? '';
        self::$attributes['prefix'] = $current ? rtrim($current, '/') . '/' . ltrim($panelPrefix, '/') : $panelPrefix;
        self::$attributes['role'] = $name;
        return self::getInstance();
    }

    public static function dispatch(string $uri)
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $isHead = ($method === 'HEAD');
        if ($isHead) {
            $method = 'GET';
        }

        if ($isHead) {
            ob_start();
        }

        self::service()->dispatch($uri, $method);

        if ($isHead) {
            ob_end_clean();
        }
    }

    public static function run()
    {
        // 🎼 RBN 3.5: Run Extensible Redirect Manager 🌐🔄⚓
        $redirect = BaseService::get()->manager('redirect');
        if ($redirect) {
            $redirect->process();
        }

        $rawPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
        $uri = trim((string) $rawPath, '/');
        if ($uri === '' || $uri === 'index.php') {
            $uri = '/';
        }

        self::dispatch($uri);
    }

    /**
     * Load routes from both Framework and Project 🚀🛣️⚓
     */
    public static function loadRoutes(): void
    {
        $frameworkPath = Paths::framework()->core('Routes/Mappings');

        // 1. Process Core Framework Routes (Zero-Check Direct Load for Core/Auth/Web) ⚡
        require_once $frameworkPath . '/core.php';
        require_once $frameworkPath . '/auth.php';
        require_once $frameworkPath . '/web.php';

        // 2. Process Custom Project Routes (If App/Routes exists) 🏛️
        $routesConfig = Definition::get('route', 'FILES') ?? [];
        $projectFiles = $routesConfig['project'] ?? [];

        if (!empty($projectFiles)) {
            $projectPath = Paths::project()->routes();
            $projectPrefix = Definition::get('namespace', 'PROJECT_PREFIX');

            self::namespace($projectPrefix)->group(function () use ($projectPath, $projectFiles) {
                foreach ($projectFiles as $file) {
                    $path = $projectPath . '/' . $file . '.php';
                    if (is_file($path)) {
                        require_once $path;
                    }
                }
            });
        }
    }
}
