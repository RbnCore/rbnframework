<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\System\Paths\Contexts;

use Rbn\Framework\Core\System\Discovery\Clusters\Logic\Definition\Definition;
use Rbn\Framework\Core\System\Discovery\Clusters\Structure\Folder\FolderContext;
use Rbn\Framework\Core\System\Discovery\Clusters\Structure\Namespace\NamespaceContext;

/**
 * Project Context
 * Provides paths related to the active application project.
 * 
 * RBN 3.5: Moved to Contexts cluster for architectural symmetry.
 */
class ProjectContext extends FolderContext
{
    private string $root;
    private string $publicRoot;

    public function __construct(string $rootPath, string $publicPath)
    {
        $this->root = $rootPath;
        $this->publicRoot = $publicPath;
    }

    /*
     | --------------------------------------------------------------------------
     | Phase 6: NAMESPACE RESOLUTION 🛰️
     | --------------------------------------------------------------------------
     */

    /**
     * Get the logical Namespace Context for the Project Core.
     * This brings the project in line with the framework's modular architecture.
     */
    public function namespaces(): NamespaceContext
    {
        // Rbn\Project\ + Core = Rbn\Project\Core
        $prefix = Definition::get('namespace', 'PROJECT_PREFIX');
        return new NamespaceContext($prefix . 'Core', 'Core');
    }

    /*
     | --------------------------------------------------------------------------
     | Phase 1: PROJECT ROOT & ANCHORS ⚓
     | --------------------------------------------------------------------------
     */

    /**
     * Get the absolute root directory of the active project.
     */
    public function root(?string $path = null): string
    {
        return $this->root . ($path ? DIRECTORY_SEPARATOR . ltrim($path, '/\\') : '');
    }

    /**
     * Get the absolute public (web) root directory of the project.
     * Use this for physical asset resolution (images, CSS, JS).
     */
    public function public(?string $path = null): string
    {
        return $this->publicRoot . ($path ? DIRECTORY_SEPARATOR . ltrim($path, '/\\') : '');
    }

    /**
     * Get the base URL for the active project (Dynamic Protocol).
     */
    public function baseUrl(): string
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';

        $projectData = \Rbn\Framework\Core\System\Kernel\Bootstrap::getAppContext('project_data') ?: [];
        $activeKey = function_exists('active_project_key') ? active_project_key() : '';

        // 🎼 RBN 3.5: If active project key is switched (e.g. ?project=example), fetch switched project's discovery data
        if (!empty($activeKey) && ($projectData['project_key'] ?? '') !== $activeKey) {
            $switchedData = \Rbn\Framework\Core\System\Storage\Providers\BootCacheProvider::get(
                $activeKey,
                null,
                \Rbn\Framework\Core\System\Storage\Constants\CacheConstants::DISCOVERY_PREFIX_PROJECT
            );
            if (!empty($switchedData)) {
                $projectData = $switchedData;
            }
        }

        $discoveredDomain = $projectData['domain'] ?? null;

        if (!empty($discoveredDomain)) {
            if (str_starts_with((string) $discoveredDomain, 'http://') || str_starts_with((string) $discoveredDomain, 'https://')) {
                return rtrim((string) $discoveredDomain, '/');
            }

            $rawHost = $_SERVER['HTTP_HOST'] ?? '';
            $currentHostName = explode(':', $rawHost)[0];

            // Preserve local dev TLD suffixes (.test / .local)
            if (str_ends_with($currentHostName, '.test') && !str_ends_with((string) $discoveredDomain, '.test')) {
                $discoveredDomain .= '.test';
            } elseif (str_ends_with($currentHostName, '.local') && !str_ends_with((string) $discoveredDomain, '.local')) {
                $discoveredDomain .= '.local';
            }

            $port = '';
            if (str_contains($rawHost, ':')) {
                $parts = explode(':', $rawHost);
                $port = ':' . end($parts);
            }

            return $protocol . '://' . $discoveredDomain . $port;
        }

        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $protocol . '://' . $host;
    }

    /*
     | --------------------------------------------------------------------------
     | Phase 2: APPLICATION LEVEL HUBS 🛠️
     | --------------------------------------------------------------------------
     */

    /**
     * Get the Project's Core directory (Foundational logic hub 🧠⚓)
     */
    public function core(?string $path = null): string
    {
        return $this->root(self::path('PROJECT.CORE', $path));
    }

    /**
     * Get the Project's App directory (Project Soul ✨)
     */
    public function app(?string $path = null): string
    {
        return $this->root(self::path('PROJECT.APP', $path));
    }

    /**
     * Get the project's Modules directory (User-Level Modules)
     */
    public function module(?string $path = null): string
    {
        return $this->root(self::path('PROJECT.MODULES', $path));
    }

    /**
     * Get the project's Resources directory (User-Level Resources)
     */
    public function resources(?string $path = null): string
    {
        return $this->root(self::path('PROJECT.RESOURCES', $path));
    }

    /**
     * Get absolute storage path (Logs, Cache, Sessions)
     */
    public function storage(?string $path = null): string
    {
        return $this->root(self::path('PROJECT.STORAGE', $path));
    }

    /*
     | --------------------------------------------------------------------------
     | Phase 3: CORE PROJECT LAYERS 🧱
     | --------------------------------------------------------------------------
     */

    /**
     * Get the project's Config directory (Core/Config)
     * RBN 3.5: Renamed to avoid collision with ServicesContextTrait::config().
     */
    public function configs(?string $path = null): string
    {
        return $this->root(self::path('PROJECT.CORE.CONFIG', $path));
    }

    /**
     * Get the project's Tasks directory (Core/Tasks)
     */
    public function tasks(?string $path = null): string
    {
        return $this->root(self::path('PROJECT.CORE.TASKS', $path));
    }
    public function system(?string $path = null): string
    {
        return $this->root(self::path('PROJECT.CORE.SYSTEM', $path));
    }

    /**
     * Get the project's Routes directory (Core/Routes)
     */
    public function routes(?string $path = null): string
    {
        return $this->root(self::path('PROJECT.CORE.SYSTEM.ROUTES', $path));
    }

    /*
     | --------------------------------------------------------------------------
     | Phase 4: RESOURCES = VIEWS & ASSETS 📦
     | --------------------------------------------------------------------------
     */

    /**
     * Get the project's views directory (Resources)
     */
    public function views(?string $path = null): string
    {
        return $this->root(self::path('PROJECT.RESOURCES', $path));
    }

    /**
     * Get the project's assets directory (Resources/Assets)
     */
    public function assets(?string $path = null): string
    {
        return $this->root(self::path('PROJECT.RESOURCES.ASSETS', $path));
    }

    /**
     * Get the project's layouts directory (inside views)
     */
    public function layouts(?string $path = null): string
    {
        return $this->root(self::path('PROJECT.RESOURCES.LAYOUTS', $path));
    }

    /**
     * Get the project's components directory
     */
    public function components(?string $path = null): string
    {
        return $this->root(self::path('PROJECT.RESOURCES.COMPONENTS', $path));
    }

    /**
     * Get the project's dashboards directory
     */
    public function dashboards(?string $path = null): string
    {
        return $this->root(self::path('PROJECT.RESOURCES.DASHBOARDS', $path));
    }

    /*
     | --------------------------------------------------------------------------
     | Phase 5: STORAGE 📦
     | --------------------------------------------------------------------------
     */

    /**
     * Get session storage path
     */
    public function sessions(?string $path = null): string
    {
        return $this->root(self::path('PROJECT.STORAGE', 'sessions' . ($path ? DIRECTORY_SEPARATOR . ltrim($path, '/\\') : '')));
    }

    /**
     * Get logs storage path
     */
    public function logs(?string $path = null): string
    {
        return $this->root(self::path('PROJECT.STORAGE', 'logs' . ($path ? DIRECTORY_SEPARATOR . ltrim($path, '/\\') : '')));
    }

    /**
     * Get uploads storage path (Secure Storage) 📦🛡️
     */
    public function uploads(?string $path = null): string
    {
        return $this->root(self::path('PROJECT.STORAGE', 'uploads' . ($path ? DIRECTORY_SEPARATOR . ltrim($path, '/\\') : '')));
    }

    /**
     * Get exports storage path
     */
    public function exports(?string $path = null): string
    {
        return $this->root(self::path('PROJECT.STORAGE', 'exports' . ($path ? DIRECTORY_SEPARATOR . ltrim($path, '/\\') : '')));
    }

    /**
     * Get backups storage path
     */
    public function backups(?string $path = null): string
    {
        return $this->root(self::path('PROJECT.STORAGE', 'backups' . ($path ? DIRECTORY_SEPARATOR . ltrim($path, '/\\') : '')));
    }

    /**
     * Get temporary storage path (Auto-creates directory if missing)
     */
    public function temp(?string $path = null): string
    {
        $tempPath = $this->root(self::path('PROJECT.STORAGE', 'temp' . ($path ? DIRECTORY_SEPARATOR . ltrim($path, '/\\') : '')));
        if (!is_dir($tempPath) && $path === null) {
            @mkdir($tempPath, 0775, true);
        }
        return $tempPath;
    }
}
