<?php

/**
 * Project Helpers - The Identity Hub 🏛️🛰️⚓
 */

if (!function_exists('project_data')) {
    /**
     * Get the active project data context 🧬
     */
    function project_data(?string $key = null, mixed $default = null): mixed
    {
        $projectData = \Rbn\Framework\Core\System\Kernel\Bootstrap::getAppContext('project_data') ?: [];

        if ($key === null) {
            return $projectData;
        }

        // Gruba bağlı tüm projelerin listesini dinamik olarak BootCache'den oku 🚀
        if ($key === 'group_projects') {
            $groupName = $projectData['project_group'] ?? $projectData['project_key'] ?? '';
            if (empty($groupName)) {
                return [];
            }
            return \Rbn\Framework\Core\System\Storage\Providers\BootCacheProvider::get($groupName, null, 'group_') ?: [];
        }

        return $projectData[$key] ?? $default;
    }
}

if (!function_exists('project_id')) {
    /**
     * Get the current project ID from the bootstrap context
     */
    function project_id(): int
    {
        return (int) project_data('id', 0);
    }
}

if (!function_exists('project_key')) {
    /**
     * Get the current project key (e.g. rbncore)
     */
    function project_key(): string
    {
        return (string) project_data('project_key', '');
    }
}

if (!function_exists('active_project_key')) {
    /**
     * Get the active project key based on request context or active host
     */
    function active_project_key(): string
    {
        $appKey = \Rbn\Framework\Core\System\Kernel\Bootstrap::getAppContext('project_key');
        if (!empty($appKey)) {
            return explode('?', (string) $appKey)[0];
        }

        $projectKey = project_data('project_key', '') ?: 'default';
        return explode('?', (string) $projectKey)[0];
    }
}

if (!function_exists('project_group')) {
    /**
     * Get the current project group key (e.g. example)
     */
    function project_group(): string
    {
        return (string) project_data('project_group', '');
    }
}

if (!function_exists('group_projects')) {
    /**
     * Get all active projects belonging to the current project's group 🌐
     */
    function group_projects(): array
    {
        return (array) project_data('group_projects');
    }
}

if (!function_exists('app_name')) {
    /**
     * Get the current application name
     */
    function app_name(): string
    {
        return (string) project_data('project_name', 'RBN Framework');
    }
}

if (!function_exists('app_version')) {
    /**
     * Get the current application (project) version
     *
     * TEK KAYNAK: master DB `projects.version` -> `project_data('version')`.
     * Cozucu TEK merkezdedir (`ProjectVersionResolver`); deger yoksa veya
     * gecersizse standart baslangic surumu `0.1.1` doner. Iki parcali
     * `1.0` gibi gecersiz varsayilanlar YOKTUR.
     */
    function app_version(): string
    {
        return \Rbn\Framework\Core\Support\Bridges\Helpers\Library\ProjectVersionResolver::resolve(
            project_data('version')
        );
    }
}

if (!function_exists('site_domain')) {
    /**
     * Get the current site domain name 🌐⚓
     */
    function site_domain(): string
    {
        return parse_url(\Rbn\Framework\Core\System\Paths\Paths::project()->baseUrl(), PHP_URL_HOST) ?: 'localhost';
    }
}

if (!function_exists('url')) {
    /**
     * Generate an absolute URL for the project with sovereign duplication guard 🌐🛰️⚓
     */
    function url(string $path = ''): string
    {
        $base = rtrim(\Rbn\Framework\Core\System\Paths\Paths::project()->baseUrl(), '/');

        if (empty($path)) {
            return $base;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, $base)) {
            return $path;
        }

        return $base . '/' . ltrim($path, '/');
    }
}