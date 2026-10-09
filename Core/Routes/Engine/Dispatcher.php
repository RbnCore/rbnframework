<?php

namespace Rbn\Framework\Core\Routes\Engine;

/**
 * Dispatcher - Route Execution Logic
 * 
 * Runs middlewares and calls the controller method.
 */
class Dispatcher
{
    private array $middlewareAliases = [];
    private array $middlewareGroups = [];

    public function __construct(array $config = [])
    {
        $this->middlewareAliases = $config['aliases'] ?? [];
        $this->middlewareGroups = $config['groups'] ?? [];
    }

    /**
     * Dispatch a matched route
     */
    public function dispatch(array $route): void
    {
        // 1. Run Middleware
        foreach ($route['middleware'] as $mw) {
            $this->runMiddleware($mw);
        }

        // 2. Call Controller
        $this->callController($route['action'], $route['params']);
    }

    /**
     * Resolve and run middleware via IoC Hub 🪄⚙️🛰️⚓
     */
    private function runMiddleware(string $middleware): void
    {
        $resolved = $this->resolveMiddleware($middleware);
        $hub = \Rbn\Framework\Core\Base\Services\BaseService::get();

        foreach ($resolved as $mw) {
            // 1. Role Based Shorthand 🛡️
            if (str_starts_with($mw, 'role:')) {
                $auth = $hub?->service('auth');
                // Giriş yapılmamışsa doğrudan ana sayfaya yönlendir 🚀
                if (!$auth || !$auth->check()) {
                    response()->redirect('/');
                    exit;
                }

                // Giriş yapılmış ama rol/yetki yetersizse 403 Forbidden mesajı göster 🔐
                $role = substr($mw, 5);
                $access = $hub?->handler('access');
                if (!$access || !$access->can($role)) {
                    shield()->forbidden("Bu sayfayı görüntülemek için yetkiniz bulunmamaktadır.");
                }
                continue;
            }

            // 2. IoC Discovery / Handler Resolution 🏛️🛰️⚓
            $handler = null;
            if ($hub) {
                $handler = $hub->handler($mw);
            }

            if (!$handler && class_exists($mw)) {
                $handler = new $mw();
            }

            // [R-10 · 2026-10-03 · team member] FAIL-CLOSED 🛑
            //
            // TABAN: asagidaki `if` sessizce gectigi icin COZULEMEYEN bir
            // middleware koruma hic uygulamadan controller'a gidiyordu:
            // rota `Route::middleware('admin')` yazsa bile `admin` cozulemezse
            // (config yuklenmemis, yanlis alias, silinmis sinif) yetkisiz kullanici
            // DOGRUDAN controller'a ulasiyordu. Bu fail-OPEN bir rotadir.
            //
            // KARAR: exception firlat -> controller HICBIR CAGIRILMAZ.
            // Gerekce (tarama kaniti): framework+projects+domains taramasinda
            // (Core/Bundles/Packages + projects + domains) rotalarda kullanilan
            // middleware adlari yalniz `auth`, `admin`, `developer`, `superadmin`
            // (RouteBlueprint::MIDDLEWARE group/alias -> AuthMiddleware) ve
            // `role:user` (yukaridaki kisa yol) ve
            // `\Rbn\Framework\Core\Http\Security\ApiGuard` (FQCN).
            // COZULEMEYEN MIDDLEWARE: 0 -> fail-closed'in canli rotayi
            // kirma etkisi olculerek uygulandi.
            // Betik: E:\tmp\_araclar\fw-regresyon\_tarama_middleware.php
            if ($handler && method_exists($handler, 'handle')) {
                $handler->handle();
                continue;
            }

            // [R-10] Handler COZULDU ama `handle()` metodu yok (orn. middleware
            // yerine kullanilan bir controller). Bu bir koruma DEGIL; burada hata
            // vermek rotayi kirardi -> yalniz kayit + devam.
            if ($handler) {
                error_log(sprintf(
                    '[RBN][Dispatcher] middleware handler cozuldu ama handle() yok, ATLANDI: %s',
                    $mw
                ));
                continue;
            }

            // [R-10] Handler HICBIR cozulmedi -> fail-closed.
            throw new \RuntimeException(
                'Middleware cozulemedi, rota calistirilmadi (fail-closed): ' . $mw
            );
        }
    }

    public function resolveMiddleware(string $middleware): array
    {
        if (isset($this->middlewareGroups[$middleware])) {
            $resolved = [];
            foreach ($this->middlewareGroups[$middleware] as $m) {
                $resolved = array_merge($resolved, $this->resolveMiddleware($m));
            }
            return $resolved;
        }

        if (isset($this->middlewareAliases[$middleware])) {
            return [$this->middlewareAliases[$middleware]];
        }

        if (strpos($middleware, 'role:') === 0) {
            return [$middleware];
        }

        return [$middleware];
    }

    /**
     * Call the controller method
     */
    private function callController($controller, $params = [])
    {
        // 🎼 RBN Framework: [POLYMORPHIC ACTION RESOLUTION] 🎻🛰️⚓
        // Support both modern Array format [Class, Method] and legacy String format "Class@Method"
        if (is_array($controller)) {
            $class = $controller[0];
            $method = $controller[1] ?? 'index';
            $staticParams = [];
        } else {
            // Legacy String Dispatcher
            [$class, $methodPart] = explode('@', $controller);

            $methodParts = explode('/', $methodPart);
            $method = $methodParts[0];
            $staticParams = array_slice($methodParts, 1);
        }

        $params = array_merge($staticParams, $params);

        // Use ControllerResolver from the same namespace
        $class = ControllerResolver::resolve($class);

        if (class_exists($class) && method_exists($class, $method)) {
            // 🎼 RBN Framework: [RBN Framework RESPONSE HUB] 🎻🛰️⚓
            // Capture the return value and echo it automatically.
            $controllerInstance = new $class;
            $response = call_user_func_array([$controllerInstance, $method], $params);
            
            // 🛡️ Explicit Render Fallback (Emulates "return $this->render()" behavior automatically)
            if ($response === null && method_exists($controllerInstance, 'getActiveView')) {
                $response = $controllerInstance->getActiveView();
            }

            if ($response !== null) {
                echo $response;
            }
        } else {
            // [R-11] Istisna mesaji sinf + metot adi (ve dolaylIyla mutlak
            // dosya yolu) ICERIYORDU; bu mesaj `ExceptionHandler` -> panic/
            // survival gorunumu -> HTTP govdesi yoluna tasinabiliyor.
            // Prod'da (RBN_DEV kapali) genel metni basilir; ayrintI yalniz
            // gelistirme ortaminda gorunur. `LogHandler` her ortamda ayrinti
            // yazmaya devam eder (asagida).
            $ayrinti = is_array($controller) ? "$class::$method" : $controller;

            try {
                \Rbn\Framework\Core\Services\Exception\Handlers\LogHandler::failsafeLog([
                    'type' => 'Dispatcher',
                    'message' => "Controller not found or method missing: {$ayrinti}",
                    'file' => __FILE__,
                    'line' => __LINE__,
                ], 'Rota hedefi cozumlenemedi');
            } catch (\Throwable $e) {
                // Telemetri ASLA istegi dusurmemeli (G-09 dersi).
            }

            throw new \Exception(
                (defined('RBN_DEV') && RBN_DEV === true)
                    ? "Controller not found or method missing: {$ayrinti}"
                    : 'Controller not found or method missing.'
            );
        }
    }

    /**
     * Set middleware configuration
     */
    public function setConfig(array $config): void
    {
        $this->middlewareAliases = $config['aliases'] ?? [];
        $this->middlewareGroups = $config['groups'] ?? [];
    }
}
