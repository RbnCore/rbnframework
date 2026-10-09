<?php

namespace Rbn\Framework\Core\Routes\Engine\Providers;

use Rbn\Framework\Core\Base\Services\BaseService;

/**
 * Router - Modern Fluent Routing Engine Bridge
 * 
 * Bridge to the Engine components managed by RouteService.
 */
class Router
{
    private static ?Router $instance = null;

    public static function getInstance(): Router
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function service(): RouteManager
    {
        return BaseService::get()->service('route');
    }

    public function group(array $attributes, callable $callback): void
    {
        $this->service()->collector()->pushGroup($attributes);
        try {
            $callback();
        } finally {
            $this->service()->collector()->popGroup();
        }
    }

    public function addRoute(array $methods, string $uri, $action, array $extraAttributes = []): void
    {
        $this->service()->collector()->addRoute($methods, $uri, $action, $extraAttributes);
    }

    public function nameLastRoute(string $name): void
    {
        $this->service()->collector()->nameLastRoute($name);
    }

    public function getRouteByName(string $name): ?string
    {
        return $this->service()->collector()->getRouteByName($name);
    }

    public function match(string $uri, string $method)
    {
        $this->service()->matcher()->setRoutes($this->service()->collector()->getRoutes());
        return $this->service()->matcher()->match($uri, $method);
    }

    public function dispatch(string $uri, string $method)
    {
        return $this->match($uri, $method);
    }

    public function getRoutes(): array
    {
        return $this->service()->collector()->getRoutes();
    }
}
