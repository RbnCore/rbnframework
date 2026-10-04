<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\System\Config\Engine\Config;

/**
 * ConfigResolver - The "Resolution Engine" 🏛️⚙️⚓
 * 
 * RBN 3.5 Masterpiece: This class encapsulates all logic for path discovery,
 * contextual DTO resolution, and autonomous survival fallbacks.
 * Separated from the public Config API to ensure architectural purity.
 */
class ConfigResolver
{
    use \Rbn\Framework\Core\Base\Concerns\Identity\SovereignIdentity;
    use \Rbn\Framework\Core\Base\Concerns\Data\ResolvesProjectConfigTrait;

    /** @var bool Recursion Guard Flag 🛡️ */
    private static bool $isResolving = false;

    /** @var bool Survival Mode Flag (Fast-Path Signaling) 🛡️🛰️ */
    private static bool $isSurvivalMode = false;

    /**
     * Enable or disable Survival Mode for the current execution context.
     */
    public static function setSurvivalMode(bool $status): void
    {
        self::$isSurvivalMode = $status;
    }

    /**
     * Check if the resolver is currently in a resolution cycle.
     */
    public static function isResolving(): bool
    {
        return self::$isResolving;
    }

    /**
     * Set the resolution lock status.
     */
    public static function setResolving(bool $status): void
    {
        self::$isResolving = $status;
    }

    /**
     * Internal Access to the Authoritative Guard 🛡️👨‍🍳
     * RBN 3.5: Autonomous during recursion or survival to prevent core loops.
     */
    public static function guard(): mixed
    {
        // 🎼 RBN 3.5: [AUTONOMOUS BYPASS] 
        if (self::$isResolving || self::$isSurvivalMode || !class_exists(\Rbn\Framework\Core\Base\Services\BaseService::class)) {
            return null;
        }

        try {
            return \Rbn\Framework\Core\Base\Services\BaseService::get()->service('databaseGuard');
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * MASTERPIECE RESOLUTION DNA 🧬🏛️⚓
     * RBN 3.5: Strictly asks the authority for the path with an autonomous fallback.
     */
    public static function resolve(string $name, ?string $projectKey = null): string
    {
        // 🎼 RBN 3.5: [DATABASE PATH AUTHORITY] 🏛️⚙️
        // Returning the actual SSOT path for the Master Identity
        if ($name === 'database_master') {
            return \Rbn\Framework\Core\System\Paths\Paths::frameworkRoot() . DIRECTORY_SEPARATOR .
                   'Core' . DIRECTORY_SEPARATOR .
                   'System' . DIRECTORY_SEPARATOR .
                   'Config' . DIRECTORY_SEPARATOR .
                   'Definitions' . DIRECTORY_SEPARATOR .
                   'DbProfiles' . DIRECTORY_SEPARATOR .
                   'MasterDbData.php';
        }

        // Returning the actual SSOT path for the Common Operational Identity
        if ($name === 'database_common') {
            return \Rbn\Framework\Core\System\Paths\Paths::frameworkRoot() . DIRECTORY_SEPARATOR .
                   'Core' . DIRECTORY_SEPARATOR .
                   'System' . DIRECTORY_SEPARATOR .
                   'Config' . DIRECTORY_SEPARATOR .
                   'Definitions' . DIRECTORY_SEPARATOR .
                   'DbProfiles' . DIRECTORY_SEPARATOR .
                   'CommonDbData.php';
        }

        if ($name === 'database_project') {
            if (!empty($projectKey) && $projectKey !== 'master') {
                $instance = new static();
                return $instance->resolveProjectPath($projectKey, 'Core/Config/project-settings.php');
            }
            return \Rbn\Framework\Core\System\Paths\Paths::project()->configs('project-settings.php');
        }

        $guard = self::guard();

        // 🛡️ RBN 3.5: [SURVIVAL FALLBACK]
        if (!$guard) {
            return \Rbn\Framework\Core\System\Paths\Paths::project()->configs($name . '.php');
        }

        return $guard->resolvePath($name, $projectKey);
    }

    /**
     * resolveContext Delegation 🛰️⚓⚖️
     */
    public static function resolveContext(string $type, ?string $name = null, ?string $projectKey = null): mixed
    {
        $guard = self::guard();

        $projectKey = $projectKey ?: (function_exists('active_project_key') ? active_project_key() : null);

        return match ($type) {
            'database_master'  => $guard ? $guard->resolveConfig('database_master', $projectKey) : self::resolveContextSurvival('database_master', $projectKey),
            'database_common'  => $guard ? $guard->resolveConfig('database_common', $projectKey) : self::resolveContextSurvival('database_common', $projectKey),
            'database_project', 'database' => $guard ? $guard->resolveConfig('database_project', $projectKey) : self::resolveContextSurvival('database_project', $projectKey),
            default => null
        };
    }

    /**
     * RBN 3.5: Masterpiece Autonomous Fallback for Contextual Resolution 🧬🏛️⚓
     * Bypasses the Guard service to resolve critical DTOs during early-boot or collapse.
     */
    private static function resolveContextSurvival(string $type, ?string $projectKey = null): mixed
    {
        // 🎼 Only supports database contexts for now
        if (!str_contains($type, 'database'))
            return null;

        // [RBN 3.5] Resolve Environment File name from Definitions (Pure Architecture) 🛰️🏛️
        $envFile = \Rbn\Framework\Core\System\Discovery\Clusters\Logic\Definition\Definition::get($type, 'ENV_FILE') ?? 'project-settings.php';

        // 🛡️ RBN 3.5: Authoritative Path Selection (Mirrors GuardHandler Logic) ⚖️⚓
        $projectSettingsPath = match ($type) {
            'database_master'  => self::resolve('database_master', $projectKey), // Using the central authority
            'database_common'  => self::resolve('database_common', $projectKey),
            'database_project' => self::resolve('database_project', $projectKey),
            default            => self::resolve('database_project', $projectKey)
        };

        // 🎯 RBN 3.5: [SURVIVAL] Delegate autonomous loading to the DTO 🧬🦾
        return \Rbn\Framework\Core\System\Config\Engine\Database\DatabaseConfig::fromFile($projectSettingsPath, $type);
    }
}
