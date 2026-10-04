<?php
namespace Rbn\Framework\Core\Routes\Engine;

use Rbn\Framework\Core\System\Discovery\Clusters\Logic\Definition\Definition;

/**
 * ControllerResolver - Specialized Controller Namespace Resolver 🚀
 */
class ControllerResolver
{
    /**
     * Resolve action to Fully Qualified Class Name (FQCN) 🎯
     * 
     * @param string $action The action string (e.g. 'Core\Auth', 'Suite\RbnAuth\Login')
     * @param string|null $default Alternative default namespace if match fails
     * @return string Calculated FQCN
     */
    public static function resolve(string $action, ?string $default = null): string
    {
        $action = ltrim($action, '\\'); // [RBN 3.5] Normalize leading backslash for Absolute Flow 🛰️⚓

        $framework = Definition::get('namespace', 'FRAMEWORK_PREFIX');
        $project = Definition::get('namespace', 'PROJECT_PREFIX');

        // 1. Direct FQCN? (already has Rbn\Framework\ or Rbn\Project\ prefix)
        if (str_starts_with($action, trim($framework, '\\')) || str_starts_with($action, trim($project, '\\'))) {
            return self::ensureControllerSuffix($action);
        }

        // 2. Direct Class Check (e.g. Fully qualified class)
        if (class_exists($action)) {
            return self::ensureControllerSuffix($action);
        }

        // 3. Fallback: Modules Backend Controllers Shared Hub
        $backendShared = $project . 'Modules\\Backend\\Controllers\\' . self::ensureControllerSuffix($action);
        if (class_exists($backendShared)) {
            return $backendShared;
        }

        // 4. Point-and-Shoot Discovery (Config Map)
        $map = Definition::get('namespace', 'MAP') ?? [];
        $parts = explode('\\', trim($action, '\\'));
        $prefix = $parts[0] ?? '';

        if (isset($map[$prefix])) {
            $baseNamespace = rtrim($map[$prefix], '\\');
            $remaining = array_slice($parts, 1);

            return self::applySmartInjection($baseNamespace, $remaining);
        }

        // 5. Fallback: Project Core (Rbn\Project\Core\Controllers)
        // Since PROJECT_DEFAULT was removed, we derive from the North Star Prefix
        $projectDefault = $default ?? ($project . 'Core\\');

        return self::applySmartInjection(rtrim($projectDefault, '\\'), $parts);
    }

    /**
     * Smart Injection: Add \Controllers\ before the final class name if not present 🎯
     */
    private static function applySmartInjection(string $baseNamespace, array $parts): string
    {
        if (empty($parts)) {
            return ltrim(self::ensureControllerSuffix($baseNamespace), '\\');
        }

        $finalClass = array_pop($parts);
        $subPath = !empty($parts) ? implode('\\', $parts) . '\\' : '';

        // If the base namespace doesn't already contain \Controllers\, and the subpath doesn't either, inject it
        if (!str_contains($baseNamespace, '\\Controllers') && !str_contains($subPath, 'Controllers\\')) {
            $finalNamespace = $baseNamespace . '\\' . $subPath . 'Controllers\\' . self::ensureControllerSuffix($finalClass);
            return ltrim($finalNamespace, '\\');
        }

        $finalNamespace = $baseNamespace . '\\' . $subPath . self::ensureControllerSuffix($finalClass);
        return ltrim($finalNamespace, '\\');
    }

    /**
     * Ensures the class name is handled (RBN 3.0 standard suffixing is optional)
     */
    private static function ensureControllerSuffix(string $class): string
    {
        if (str_contains($class, '@')) {
            $class = explode('@', $class)[0];
        }

        return $class;
    }
}
