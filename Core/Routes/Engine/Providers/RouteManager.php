<?php

namespace Rbn\Framework\Core\Routes\Engine\Providers;

use Rbn\Framework\Core\Base\Services\BaseService;
use Rbn\Framework\Core\Routes\Engine\Collector;
use Rbn\Framework\Core\Routes\Engine\Matcher;
use Rbn\Framework\Core\Routes\Engine\Dispatcher;
use Rbn\Framework\Core\Routes\Engine\UrlGenerator;
use Rbn\Framework\Core\System\Discovery\Clusters\Logic\Definition\Definition;
use Rbn\Framework\Core\Support\Contracts\Routes\RouteInterface;

/**
 * RouteManager - Framework URL ve Rotalama Orkestrasyonu 🛡️🛣️⚓
 * 
 * RBN 3.0: Engine bileşenlerini yöneten merkezi sistem.
 * RouteInterface kontratını uygulayarak mimari simetri sağlar.
 */
class RouteManager implements RouteInterface
{
    /** @var mixed The current request instance 📡 */
    public $request;

    /** @var mixed The current response instance 📡 */
    public $response;

    private string $basePrefix;

    // Engine Components
    private Collector $collector;
    private Matcher $matcher;
    private Dispatcher $dispatcher;
    private UrlGenerator $urlGenerator;

    public function __construct()
    {
        // 1. Initialize Engine
        $this->collector = new Collector();
        $this->matcher = new Matcher();
        $this->dispatcher = new Dispatcher();
        $this->urlGenerator = new UrlGenerator($this->collector);

        // 2. Load Config & Settings
        $this->loadSettings();
        $this->loadMiddlewareConfig();

        // 3. Prefix Guard
        $this->checkPrefixGuard();

        // 4. Project Query Guard
        $this->checkProjectQueryGuard();
    }

    /**
     * RouteInterface Implementation: Collection 🛰️
     */
    public function get(string $path, $handler): RouteInterface
    {
        $this->collector->addRoute(['GET'], $path, $handler);
        return $this;
    }

    public function post(string $path, $handler): RouteInterface
    {
        $this->collector->addRoute(['POST'], $path, $handler);
        return $this;
    }

    /**
     * RouteInterface Implementation: Matching & Dispatching ⚓
     */
    public function match(?string $method = null, ?string $uri = null): ?array
    {
        $method = $method ?? $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = $uri ?? trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
        if ($uri === '' || $uri === 'index.php') {
            $uri = '/';
        }

        // SYNC: Ensure Matcher has latest routes
        $this->matcher->setRoutes($this->collector->getRoutes());

        return $this->matcher->match($uri, $method);
    }

    public function dispatch(?string $uri = null, ?string $method = null): void
    {
        $method = $method ?? $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = $uri ?? trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
        if ($uri === '' || $uri === 'index.php') {
            $uri = '/';
        }

        $route = $this->match($method, $uri);
        if ($route) {
            // 🎼 RBN 3.5 Dynamic Context Preservation 🛰️⚓
            $panel = null;
            $module = null;
            $basePrefix = $this->getBasePrefix();
            $uriParts = explode('/', trim($route['uri'], '/'));

            if (count($uriParts) > 1 && $uriParts[0] === $basePrefix) {
                $panel = $uriParts[1];
                if (count($uriParts) > 2) {
                    $module = $uriParts[2];
                }
            }

            // Fallback for panel detection from middleware
            if (!$panel) {
                foreach (($route['middleware'] ?? []) as $mw) {
                    if (str_starts_with($mw, 'role:')) {
                        $panel = substr($mw, 5);
                        break;
                    }
                }
            }

            if ($panel) {
                $this->setContext($panel, $module);
            }

            $this->dispatcher->dispatch($route);
            return;
        }

        throw new \Rbn\Framework\Core\Support\Exceptions\PageNotFoundException($uri);
    }

    /**
     * Engine Accessors
     */
    public function collector(): Collector
    {
        return $this->collector;
    }
    public function matcher(): Matcher
    {
        return $this->matcher;
    }
    public function dispatcher(): Dispatcher
    {
        return $this->dispatcher;
    }
    public function url(): UrlGenerator
    {
        return $this->urlGenerator;
    }

    private function loadSettings(): void
    {
        $customPrefix = null;
        $projectKey = function_exists('project_key') ? project_key() : '';

        if (!empty($projectKey)) {
            $projectPrefix = \Rbn\Framework\Core\System\Storage\Constants\CacheConstants::DISCOVERY_PREFIX_PROJECT;
            $projectData = \Rbn\Framework\Core\System\Storage\Providers\BootCacheProvider::get($projectKey, null, $projectPrefix);
            if ($projectData) {
                $customPrefix = $projectData['dashboard_prefix'] ?? null;
            }
        }

        $this->basePrefix = $customPrefix ? trim($customPrefix, '/') : 'dashboard';
        $this->urlGenerator->setBasePrefix($this->basePrefix);
    }

    /**
     * Load middleware aliases from config file
     */
    private function loadMiddlewareConfig(): void
    {
        $config = Definition::get('route', 'MIDDLEWARE') ?? [];
        if (!empty($config)) {
            $this->dispatcher->setConfig($config);
        }
    }

    /**
     * Prefix Guard Logic
     */
    private function checkPrefixGuard(): void
    {
        $projectKey = function_exists('project_key') ? project_key() : '';
        if (empty($projectKey)) {
            return;
        }

        $projectPrefix = \Rbn\Framework\Core\System\Storage\Constants\CacheConstants::DISCOVERY_PREFIX_PROJECT;
        $projectData = \Rbn\Framework\Core\System\Storage\Providers\BootCacheProvider::get($projectKey, null, $projectPrefix);

        if ($projectData) {
            $storedPrefix = $projectData['dashboard_prefix'] ?? null;

            if ($storedPrefix !== $this->basePrefix) {
                if ($storedPrefix !== null) {
                    $currentUri = (string) ($_SERVER['REQUEST_URI'] ?? '');

                    // [R-04 · 2026-10-03 · baran-6eb7f5] Hedef uretimi ve guvenli-hedef
                    // kontrolu ayri bir saf metoda tasindi (`resolvePrefixGuardRedirect`).
                    // TABAN: `$newUri` dogrudan `header("Location: {$newUri}")` ile
                    // basiliyordu; CRLF ve acik-yonlendirme (//evil.example) icin
                    // HICBIR suzgec yoktu. Artik mevcut guvenli-hedef yolundan
                    // (`response()->guvenliHedef()` = `RedirectTrait::guvenliHedef()`,
                    // A0-5/F-01) geciriliyor — YENI suzgec yazilmadi.
                    $safeUri = $this->resolvePrefixGuardRedirect($currentUri, (string) $storedPrefix, $this->basePrefix);

                    if ($safeUri !== null) {
                        header("Location: {$safeUri}", true, 302);
                        exit;
                    }

                    // [R-04] Guvenli hedef uretilemedi -> yonlendirme YAPILMAZ;
                    // asagidaki kendini-iyilestirme yolu (on ek'i kalici olarak
                    // gunceller) devreye girer.
                }

                $storage = BaseService::get()->service('storageManager');
                if ($storage) {
                    $storage->cache()->clearAll();
                    $storage->resources()->clearResource('framework');
                }

                $projectData['dashboard_prefix'] = $this->basePrefix;
                \Rbn\Framework\Core\System\Storage\Providers\BootCacheProvider::set($projectKey, $projectData, null, $projectPrefix);
            }
        }
    }

    /**
     * [R-04] Prefix Guard için GÜVENLİ yönlendirme hedefi üretir.
     *
     * SAF (side-effect'siz) metot: sadece string üretir, header basmaz, exit
     * çağırmaz. Böylece tek bir doğruluk kaynağı hem üretim hem test için
     * kullanılır.
     *
     * @param string $currentUri  `$_SERVER['REQUEST_URI']` değeri
     * @param string $storedPrefixProjede KAYITLI (eski) panel ön eki
     * @param string $basePrefix  Aktif panel ön eki
     * @return string|null Güvenli site-içi mutlak yol, ya da yönlendirme yapılmamalıysa `null`
     */
    public function resolvePrefixGuardRedirect(string $currentUri, string $storedPrefix, string $basePrefix): ?string
    {
        if ($storedPrefix === '' || $basePrefix === '' || $storedPrefix === $basePrefix) {
            return null;
        }

        $path = parse_url($currentUri, PHP_URL_PATH);
        if (!is_string($path) || $path === '') {
            return null;
        }

        $uriSegments = explode('/', trim($path, '/'));
        $oldPrefixIndex = array_search($storedPrefix, $uriSegments, true);
        if ($oldPrefixIndex === false) {
            return null;   // yol eski ön eki içermiyor -> yönlendirme gerekmez
        }

        $uriSegments[$oldPrefixIndex] = $basePrefix;
        $candidate = '/' . implode('/', $uriSegments);

        // ── Güvenli-hedef süzgeci ──────────────────────────────────────
        // `RedirectManager` tarafı `d946f1e` ile kapatılan AYNı kaynak:
        // `response()->guvenliHedef()` -> `RedirectTrait::guvenliHedef()`.
        // Kapsamı: CR/LF reddi, `\`→`/` tarayıcı normalizasyonu, şema-göreli
        // (`//host`, `/\host`) ve beyaz liste dışı mutlak host reddi.
        if (!function_exists('response')) {
            return null;   // helper yoksa yönlendirme yapma (fail-closed)
        }

        $safe = response()->guvenliHedef($candidate);

        // ── Ek kısıt: sonuç AYNI KÖK YOL olmalı ────────────────────────
        // `guvenliHedef()` beyaz listedeki kendi mutlak URL'lerimize izin verir;
        // bu yol `REQUEST_URI`'den segment birleştirerek kurulduğu için zaten
        // `/\…` olmalı. Yine de sapma olursa yönlendirme yapma.
        if (!is_string($safe) || !str_starts_with($safe, '/') || str_starts_with($safe, '//')) {
            return null;
        }
        if (preg_match('/[\x00-\x1F\x7F]/', $safe) === 1) {
            return null;
        }

        return $safe;
    }

    /**
     * Project Query Guard - Forces active project context in request URI if missing
     */
    private function checkProjectQueryGuard(): void
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $cleanUri = '/' . trim((string) parse_url($uri, PHP_URL_PATH), '/');

        // 🎼 Dynamic Admin Prefix & LOGIN_PATH Detection (rbn-admin & dashPrefix) 🛡️
        $dashPrefix = function_exists('project_data') ? project_data('dashboard_prefix') : null;
        $dashPrefix = !empty($dashPrefix) ? trim((string) $dashPrefix, '/') : null;
        $basePrefix = !empty($this->basePrefix) ? trim((string) $this->basePrefix, '/') : null;
        $loginPath = \Rbn\Framework\Core\Support\Definitions\Route\RouteBlueprint::LOGIN_PATH ?? 'rbn-admin';

        $hasDashboardPrefix = str_starts_with($cleanUri, '/' . ltrim($loginPath, '/'));

        if (!$hasDashboardPrefix && $dashPrefix && $dashPrefix !== 'user') {
            $hasDashboardPrefix = str_starts_with($cleanUri, '/' . $dashPrefix);
        }

        if (!$hasDashboardPrefix && $basePrefix && $basePrefix !== 'user') {
            $hasDashboardPrefix = str_starts_with($cleanUri, '/' . $basePrefix);
        }

        // 🛑 Admin Panel Disabled Guard: Block all admin prefix and auth URLs when panel is disabled
        $disabledOptions = function_exists('project_data') ? (array) project_data('disabled_options', []) : [];
        $adminPanelDisabled = function_exists('project_data')
            ? ((bool) project_data('admin_panel_disabled') || !empty($disabledOptions['is_adminpanel_disabled']))
            : false;

        $authPages = class_exists(\Rbn\Framework\Core\Support\Definitions\Route\RouteBlueprint::class)
            ? \Rbn\Framework\Core\Support\Definitions\Route\RouteBlueprint::AUTH_ROOTS
            : [$loginPath];

        $currentPath = ltrim($cleanUri, '/');
        $isBlockedAuthRoute = false;
        foreach ($authPages as $page) {
            if ($currentPath === $page || str_starts_with($currentPath, $page . '/')) {
                $isBlockedAuthRoute = true;
                break;
            }
        }

        $isLogoutRoute = $currentPath === 'logout' || $currentPath === 'cikis' || $currentPath === 'auth/logout';
        $isPostAuthSubmit = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && str_starts_with($currentPath, 'auth/');

        // 🛑 Admin Panel Disabled Guard: Block all admin prefix and auth view GET URLs when panel is disabled (except logout)
        if ($adminPanelDisabled && !$isLogoutRoute && !$isPostAuthSubmit && ($hasDashboardPrefix || $isBlockedAuthRoute)) {
            header('Location: /', true, 302);
            exit;
        }

        // [R-03] Burada once `if (!$hasDashboardPrefix) return;` zaten
        // calistigi icin fonksiyonun baska bir sonraki adimi YOK; devaminda
        // yalnizca `getBasePrefix()` gelir. Olu bir auth bayragi hesaplaniyor
        // ama HICBIR YERDE kullanilmiyordu (ol kod) VE `str_contains`
        // yuzunden `/blog/rbn-admin-konusu` gibi yasal bir yolu yanlis
        // "auth" sayardi. Kaldirildi: ayni isi yapan, `str_starts_with`
        // kullanan ve GERCEKTEN kullanilan blok yukarida (`$isBlockedAuthRoute`)
        // zaten var.
        if (!$hasDashboardPrefix) {
            return;
        }
    }

    public function getBasePrefix(): string
    {
        return $this->basePrefix;
    }

    /**
     * Set context for the current request
     */
    public function setContext(string $panel, ?string $module = null): void
    {
        $this->urlGenerator->setContext($panel, $module);
    }

    /**
     * Legacy Bridge Methods
     */
    public function to(string $path, ?string $panel = null, ?string $module = null): string
    {
        return $this->urlGenerator->to($path, $panel, $module);
    }

    public function isActive(string $path, ?string $panel = null, ?string $module = null, bool $exact = false): bool
    {
        return $this->urlGenerator->isActive($path, $panel, $module, $exact);
    }
}
