<?php

namespace Rbn\Framework\Core\Routes\Engine;

/**
 * Collector - Route Storage & Grouping Logic
 * 
 * Manages the $routes array and handles group attribute stacking.
 */
class Collector
{
    private array $routes = [];
    private array $groupStack = [];
    private array $namedRoutes = [];

    /**
     * Group attributes stack management
     */
    public function pushGroup(array $attributes): void
    {
        $lastGroup = end($this->groupStack) ?: [];

        $newGroup = [
            'prefix' => $this->combinePrefix($lastGroup['prefix'] ?? '', $attributes['prefix'] ?? ''),
            'middleware' => array_merge($lastGroup['middleware'] ?? [], (array) ($attributes['middleware'] ?? [])),
            'controller' => $attributes['controller'] ?? ($lastGroup['controller'] ?? ''),
            'namespace' => $this->combineNamespace($lastGroup['namespace'] ?? '', $attributes['namespace'] ?? ''),
        ];

        // Role support (add as special middleware)
        if (isset($attributes['role'])) {
            $hasCustomRoleMiddleware = false;
            foreach ($newGroup['middleware'] as $mw) {
                if ($this->middlewareContainsRole($mw)) {
                    $hasCustomRoleMiddleware = true;
                    break;
                }
            }
            if (!$hasCustomRoleMiddleware) {
                $newGroup['middleware'][] = 'role:' . $attributes['role'];
            }
        }

        $this->groupStack[] = $newGroup;
    }

    public function popGroup(): void
    {
        array_pop($this->groupStack);
    }

    /**
     * Add a route to the collection
     */
    public function addRoute(array $methods, string $uri, $action, array $extraAttributes = []): void
    {
        $group = end($this->groupStack) ?: [];

        // Merge group and extra attributes
        $prefix = $this->combinePrefix($group['prefix'] ?? '', $extraAttributes['prefix'] ?? '');
        $middleware = array_merge($group['middleware'] ?? [], (array) ($extraAttributes['middleware'] ?? []));
        $namespace = $this->combineNamespace($group['namespace'] ?? '', $extraAttributes['namespace'] ?? '');
        $controller = $extraAttributes['controller'] ?? ($group['controller'] ?? '');

        if (isset($extraAttributes['role'])) {
            $middleware[] = 'role:' . $extraAttributes['role'];
        }

        $fullUri = $this->combinePrefix($prefix, $uri);
        $fullUri = '/' . trim($fullUri, '/');

        $this->routes[] = [
            'methods' => $methods,
            'uri' => $fullUri,
            'action' => $this->resolveAction($action, [
                'controller' => $controller,
                'namespace' => $namespace
            ]),
            'middleware' => $middleware
        ];
    }

    /**
     * Name the last added route
     */
    public function nameLastRoute(string $name): void
    {
        $lastIndex = count($this->routes) - 1;
        if ($lastIndex >= 0) {
            $this->namedRoutes[$name] = $this->routes[$lastIndex]['uri'];
        }
    }

    /**
     * Getters
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    public function getNamedRoutes(): array
    {
        return $this->namedRoutes;
    }

    public function getRouteByName(string $name): ?string
    {
        return $this->namedRoutes[$name] ?? null;
    }

    /**
     * Utilities (Moved from Router)
     */
    private function combinePrefix(string $old, string $new): string
    {
        return trim($old, '/') . '/' . trim($new, '/');
    }

    private function combineNamespace(string $old, string $new): string
    {
        if (empty($old))
            return $new;
        if (empty($new))
            return $old;

        $cleanNew = ltrim($new, '\\');
        $cleanOld = trim($old, '\\');

        // [RBN Framework] RBN Framework: Context Jump 🛰️⚓
        // If the new namespace starts with the North Star prefix (Rbn\ or Core\), 
        // it's an absolute path; we ignore the previous context.
        if (str_starts_with($cleanNew, 'Rbn\\') || str_starts_with($cleanNew, 'Core\\')) {
            return $new;
        }

        // [RBN Framework] RBN Framework: Duplication Shield 🛡️⚓
        // If the new namespace already includes the old one, don't repeat it.
        if (str_starts_with($cleanNew, $cleanOld . '\\')) {
            return $new;
        }

        return $cleanOld . '\\' . $cleanNew;
    }

    private function resolveAction($action, array $attributes)
    {
        if (is_string($action)) {
            $controller = $attributes['controller'] ?? '';
            $namespace = $attributes['namespace'] ?? '';

            if (!empty($controller) && strpos($action, '@') === false) {
                $action = $controller . '@' . $action;
            }

            if (!empty($namespace)) {
                $cleanAction = ltrim($action, '\\');
                // [RBN Framework] RBN Framework: Duplication Shield 🛡️⚓
                // Don't prepend namespace if the action is already absolute or already contains it.
                if (!str_starts_with($cleanAction, 'Rbn\\') && !str_starts_with($cleanAction, 'Core\\')) {
                    $action = trim($namespace, '\\') . '\\' . $cleanAction;
                }
            }
        }
        return $action;
    }

    /**
     * Dynamically verifies if a middleware is role-based by checking if it starts
     * with 'role:' or is a group that resolves to a role-based middleware.
     */
    private function middlewareContainsRole(string $middleware): bool
    {
        if (str_starts_with($middleware, 'role:')) {
            return true;
        }

        $config = \Rbn\Framework\Core\System\Discovery\Clusters\Logic\Definition\Definition::get('route', 'MIDDLEWARE') ?? [];
        $groups = $config['groups'] ?? [];

        if (isset($groups[$middleware])) {
            foreach ($groups[$middleware] as $subMw) {
                if ($this->middlewareContainsRole($subMw)) {
                    return true;
                }
            }
        }

        return false;
    }
}
