<?php

namespace Rbn\Framework\Core\Routes\Engine;

use Rbn\Framework\Core\System\Discovery\Clusters\Logic\Definition\Definition;

/**
 * UrlGenerator - Unified Link Generation Logic
 * 
 * Handles URL creation for named routes and path-based prefixes.
 */
class UrlGenerator
{
    private Collector $collector;
    private string $basePrefix;
    private ?string $currentPanel = null;
    private ?string $currentModule = null;

    public function __construct(Collector $collector)
    {
        $this->collector = $collector;
        $this->basePrefix = Definition::get('route', 'DASHBOARD_PREFIX') ?? 'dashboard';
    }

    /**
     * Configuration setters
     */
    public function setBasePrefix(string $prefix): void
    {
        $this->basePrefix = trim($prefix, '/');
    }

    public function setContext(?string $panel, ?string $module = null): void
    {
        $this->currentPanel = $panel;
        $this->currentModule = $module;
    }

    /**
     * Unified URL generator (Named Route first, then Path-based)
     */
    public function url(string $nameOrPath, array $params = [], ?string $panel = null, ?string $module = null): string
    {
        // 1. Check for named route
        $uri = $this->collector->getRouteByName($nameOrPath);
        if ($uri !== null) {
            $generated = $this->buildNamedUrl($uri, $params);

            // RBN 3.5: Append extra parameters not used in route definition as query string
            $unusedParams = [];
            foreach ($params as $key => $value) {
                if (strpos($uri, '{' . $key . '}') === false && strpos($uri, '{' . $key . '?}') === false && strpos($uri, '{' . $key . ':') === false) {
                    $unusedParams[$key] = $value;
                }
            }
            if (!empty($unusedParams)) {
                $parts = parse_url($generated);
                $path = $parts['path'] ?? '';
                $query = $parts['query'] ?? '';
                parse_str($query, $queryParts);
                $queryParts = array_merge($queryParts, $unusedParams);
                $newQuery = http_build_query($queryParts);
                $generated = $path . (!empty($newQuery) ? '?' . $newQuery : '');
            }
        } else {
            // 2. Fallback to path-based (to)
            $generated = $this->to($nameOrPath, $panel, $module);
            if (!empty($params)) {
                $parts = parse_url($generated);
                $path = $parts['path'] ?? '';
                $query = $parts['query'] ?? '';
                parse_str($query, $queryParts);
                $queryParts = array_merge($queryParts, $params);
                $newQuery = http_build_query($queryParts);
            }
        }

        return $generated;
    }

    /**
     * Build URL from named route URI template
     */
    private function buildNamedUrl(string $uri, array $params): string
    {
        foreach ($params as $key => $value) {
            $uri = preg_replace('/\{' . preg_quote($key, '/') . '(\?)?(:[^}]+)?\}/', (string)$value, $uri);
        }
        $uri = preg_replace('/\/\{[a-zA-Z0-9_]+\?\}/', '', $uri);
        return '/' . ltrim($uri, '/');
    }

    /**
     * Smart Path-based URL Generator (Moved from RouteService)
     */
    public function to(string $path, ?string $panel = null, ?string $module = null): string
    {
        // 1. Absolute URL check
        if (strpos($path, '://') !== false) {
            return $path;
        }

        // 2. Check if already prefixed
        $baseWithSlash = '/' . $this->basePrefix . '/';
        $fullPath = '/' . ltrim($path, '/');

        if (str_starts_with($fullPath . '/', $baseWithSlash)) {
            $parts = explode('/', trim($fullPath, '/'));
            $panels = Definition::get('route', 'PANELS') ?? [];
            if (count($parts) > 1 && in_array($parts[1], $panels)) {
                return rtrim($fullPath, '/');
            }
        }

        // Auth roots
        $preservedRoots = Definition::get('route', 'AUTH_ROOTS') ?? [];
        $firstSegment = explode('/', ltrim($path, '/'))[0];
        if (in_array($firstSegment, $preservedRoots)) {
            return $fullPath;
        }

        // Smart panel detection from path
        if (str_starts_with($path, '/')) {
            $parts = explode('/', trim($path, '/'));
            $potentialPanel = $parts[0] ?? null;
            if ($potentialPanel === 'user') {
                $panel = 'user';
            } else {
                $panels = Definition::get('route', 'PANELS') ?? [];
                if (in_array($potentialPanel, $panels) && $potentialPanel !== 'frontend') {
                    $panel = $potentialPanel;
                    $path = implode('/', array_slice($parts, 1)) ?: (Definition::get('route', 'DASHBOARD_PREFIX') ?? 'dashboard');
                }
            }
        }

        // Prefix logic
        $prefix = $this->getPrefix($panel, $module, $path);

        $cleanPath = ltrim($path, '/');
        $dashPrefix = Definition::get('route', 'DASHBOARD_PREFIX') ?? 'dashboard';
        if ($cleanPath === $dashPrefix && str_ends_with($prefix, '/' . $this->basePrefix . '/' . ($panel ?? $this->currentPanel) . '/')) {
            return rtrim($prefix, '/');
        }

        return $prefix . $cleanPath;
    }

    public function getPrefix(?string $panel = null, ?string $module = null, string $path = ''): string
    {
        $targetPanel = $panel ?? ($this->currentPanel ?? 'frontend');
        $targetModule = $module;
        if ($targetModule === null && ($panel === null || $panel === $this->currentPanel)) {
            $dashPrefix = Definition::get('route', 'DASHBOARD_PREFIX') ?? 'dashboard';
            $isRootPath = in_array(trim($path, '/'), ['', $dashPrefix]);
            $targetModule = $isRootPath ? null : $this->currentModule;
        }

        if ($targetPanel === 'guest')
            return '/auth/';
        if ($targetPanel === 'user')
            return '/';
        if ($targetPanel === 'frontend') {
            $projectKey = function_exists('active_project_key') ? (string) active_project_key() : (function_exists('project_key') ? (string) project_key() : '');
            $currentProject = function_exists('project_key') ? project_key() : '';

            // 🎼 RBN 3.5: Active project URLs stay relative to current host 🌐
            if (empty($projectKey) || $projectKey === $currentProject) {
                return '/';
            }

            if (!empty($projectKey) && function_exists('group_projects')) {
                foreach (group_projects() as $proj) {
                    if ($proj['project_key'] === $projectKey && !empty($proj['domain'])) {
                        return '//' . $proj['domain'] . '/';
                    }
                }
            }
            return '/';
        }

        $fullPrefix = '/' . $this->basePrefix . '/' . trim($targetPanel, '/');

        $coreModules = Definition::get('route', 'CORE_MODULES') ?? [];
        if ($targetModule && !in_array($targetModule, $coreModules)) {
            $cleanPath = ltrim($path, '/');
            if (!str_starts_with($cleanPath . '/', $targetModule . '/')) {
                $fullPrefix .= '/' . trim($targetModule, '/');
            }
        }

        return rtrim($fullPrefix, '/') . '/';
    }

    /**
     * Active state check (Moved from RouteService)
     */
    public function isActive(string $path, ?string $panel = null, ?string $module = null, bool $exact = false): bool
    {
        $targetUrl = rtrim($this->url($path, [], $panel, $module), '/');
        $currentUri = rtrim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');

        $panels = Definition::get('route', 'PANELS') ?? [];
        $dashPrefix = Definition::get('route', 'DASHBOARD_PREFIX') ?? 'dashboard';
        $isRootMenu = in_array(trim($path, '/'), array_merge(['', $dashPrefix], $panels));

        if ($exact || $isRootMenu) {
            return $targetUrl === $currentUri;
        }

        if ($targetUrl === $currentUri)
            return true;

        // Path parts analysis
        $targetParts = explode('/', trim($targetUrl, '/'));
        $basePrefix = $this->basePrefix;

        if (isset($targetParts[0]) && $targetParts[0] === $basePrefix && isset($targetParts[1])) {
            $detectedPanelRoot = '/' . $basePrefix . '/' . $targetParts[1];
            if ($targetUrl === $detectedPanelRoot)
                return false;
        }

        $panelRoot = rtrim($this->getPrefix($panel, ''), '/');
        if ($targetUrl === $panelRoot || $targetUrl === '/' . $basePrefix)
            return false;

        $currentParts = explode('/', trim($currentUri, '/'));

        $isPrefix = true;
        if (count($targetParts) > count($currentParts)) {
            $isPrefix = false;
        } else {
            foreach ($targetParts as $i => $seg) {
                if ($seg !== $currentParts[$i]) {
                    $isPrefix = false;
                    break;
                }
            }
        }

        if ($isPrefix && $targetUrl !== '') {
            if (count($targetParts) === 3 && count($currentParts) > 3)
                return false;
            return true;
        }

        return false;
    }
}
