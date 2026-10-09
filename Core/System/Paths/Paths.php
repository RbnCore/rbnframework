<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\System\Paths;

use LogicException;
use Rbn\Framework\Core\Support\Bridges\Traits\NormalizationTrait;
use Rbn\Framework\Core\System\Paths\Contexts\ProjectContext;
use Rbn\Framework\Core\System\Paths\Contexts\FrameworkContext;
use Rbn\Framework\Core\System\Paths\Contexts\ModuleContext;
use Rbn\Framework\Core\System\Discovery\Engine\Drivers\ModuleDiscoveryDriver;

/**
 * Paths - Centralized Directory Management Hub 🎻🛰️⚓⚖️✨
 * 
 * RBN Framework: Master Hub for the Paths Cluster Cluster.
 * Orchestrates specialized Contexts and Data Registries.
 */
class Paths
{
    use NormalizationTrait;

    private static string $workspacePath = '';
    private static string $frameworkPath = '';
    private static string $projectPath = '';
    private static string $publicPath = '';
    private static bool $initialized = false;

    private static ?ProjectContext $projectContext = null;
    private static ?FrameworkContext $frameworkContext = null;
    private static array $moduleContextsCache = [];

    /**
     * Initialize paths
     */
    public static function init(?string $project, ?string $public = null): void
    {
        self::$projectPath = rtrim($project ?? '', '/\\');
        self::$publicPath = rtrim($public ?? '', '/\\');

        self::$projectContext = new ProjectContext(
            self::$projectPath,
            self::$publicPath
        );

        self::$frameworkContext = new FrameworkContext();

        // [RBN Framework] Load Core Support Helpers AFTER contexts are ready 🏹
        require_once self::frameworkRoot() . DIRECTORY_SEPARATOR . 'Core' . DIRECTORY_SEPARATOR . 'Support' . DIRECTORY_SEPARATOR . 'Bridges' . DIRECTORY_SEPARATOR . 'Helpers' . DIRECTORY_SEPARATOR . 'rbn_helpers.php';

        self::$initialized = true;

        // DUSUK-6: is_local() Host alt-dizgesine bakar (sahte Host ile yol sizdirir); karar RBN_DEV'den (fail-closed).
        if (isset($_GET['rbn_diag']) && defined('RBN_DEV') && RBN_DEV === true) {
            die("LOG: Paths Initialized. \nFramework: " . self::$frameworkPath . " \nProject: " . self::$projectPath . " \nWorkspace: " . self::$workspacePath);
        }
    }

    /**
     * Returns the Workspace Root (Parent of rbnframework)
     */
    public static function workspace(): string
    {
        if (empty(self::$workspacePath)) {
            $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, __DIR__);
            while (!empty($path)) {
                if (basename($path) === 'rbnframework') {
                    self::$workspacePath = dirname($path);
                    self::$frameworkPath = $path;
                    break;
                }
                $parent = dirname($path);
                if ($parent === $path)
                    break;
                $path = $parent;
            }

            if (empty(self::$workspacePath)) {
                self::$frameworkPath = dirname(__DIR__, 3);
                self::$workspacePath = dirname(self::$frameworkPath);
            }
        }
        return self::$workspacePath;
    }

    /**
     * Returns the Framework Root (rbnframework directory)
     */
    public static function frameworkRoot(): string
    {
        if (empty(self::$frameworkPath)) {
            self::workspace();
        }
        return self::$frameworkPath;
    }

    /**
     * Returns the Project / Application Root Path 🏠
     */
    public static function projectRoot(): string
    {
        return self::$projectPath;
    }

    /**
     * Returns the Public / Web Root Path 🛫
     */
    public static function publicRoot(): string
    {
        $dbPublicPath = function_exists('project_data') ? project_data('public_path') : null;

        if (!empty($dbPublicPath)) {
            $workspace = self::workspace();
            $candidatePath = $workspace . DIRECTORY_SEPARATOR . 'domains' . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $dbPublicPath);
            if (is_dir($candidatePath)) {
                return $candidatePath;
            }
        }

        return self::$publicPath;
    }

    /**
     * Check if paths are initialized
     */
    public static function isInitialized(): bool
    {
        return isset(self::$initialized) && self::$initialized === true;
    }

    /**
     * Get the Application / Project Context
     */
    public static function project(): ProjectContext
    {
        if (self::$projectContext === null) {
            throw new LogicException("RBN Path Registry: Project Context accessed before initialization. (Run Paths::init first)");
        }
        return self::$projectContext;
    }

    /**
     * Get the Framework Context
     */
    public static function framework(): FrameworkContext
    {
        if (self::$frameworkContext === null) {
            // 🎯 RBN Framework [SELF-HEALING] 🩹
            // Framework context is independent of project init. 
            // We can self-initialize it to avoid circular dependency loops during early boot.
            self::$frameworkContext = new FrameworkContext();
        }
        return self::$frameworkContext;
    }

    /**
     * Get a specific Module Context via Deterministic Convention 📦🛰️⚓⚖️
     */
    public static function module(string $name, string $source = 'auto'): ModuleContext
    {
        $cacheKey = "{$name}_{$source}";
        if (isset(self::$moduleContextsCache[$cacheKey])) {
            return self::$moduleContextsCache[$cacheKey];
        }

        $driver = new ModuleDiscoveryDriver();
        return self::$moduleContextsCache[$cacheKey] = $driver->resolveModuleContext($name, $source);
    }
}
