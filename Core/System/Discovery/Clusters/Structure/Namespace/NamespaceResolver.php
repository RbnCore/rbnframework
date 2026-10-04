<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\System\Discovery\Clusters\Structure\Namespace;

use Rbn\Framework\Core\Base\Services\BaseService;
use Rbn\Framework\Core\System\Discovery\Base\BaseDiscoveryContext;
use Rbn\Framework\Core\System\Paths\Paths;
use Rbn\Framework\Core\System\Registries\SystemRegistry;

/**
 * NamespaceResolver - Specialized Identity Discovery 🧬🛰️⚓
 * 
 * RBN 3.5 "Masterpiece": Converts component names and types into Full Qualified Class Names (FQCN).
 * Now uses Namespace-Driven Dynamic Expansion for Clean String resolution.
 */
class NamespaceResolver extends BaseDiscoveryContext
{
    /**
     * RBN 3.5: Masterpiece Expansion DNA 🧬🛰️⚓
     */
    use \Rbn\Framework\Core\System\Discovery\Base\Traits\NamespaceExpansionTrait;

    /** @var array<string, bool> Infinite Loop Guard */
    private static array $resolving = [];

    public function __construct(?BaseService $rbn = null)
    {
        parent::__construct($rbn);
    }

    /**
     * Find a component's FQCN by name and type 🔍
     */
    public function find(string $name, string $type): ?string
    {
        $cacheKey = "namespace:{$type}:{$name}";

        if (isset(self::$resolving[$cacheKey])) {
            return null;
        }

        self::$resolving[$cacheKey] = true;

        try {
            // 🎼 Standardized DNA Cache ⚡📦
            $cached = $this->cacheDiscovery($cacheKey, function () use ($name, $type) {
                // 🧬 0. [Strategy: Triple Gates] Check centralized registry hierarchy first 🏛️⚓
                $registryResult = SystemRegistry::locate($name, $type);

                if ($registryResult) {
                    // RBN 3.5 Masterpiece: Dynamic Namespace Expansion 🌉✨
                    return static::expandRegistryResult($registryResult);
                }

                // 1. [Strategy: Autonomous Discovery] Fallback to physical scanning 🛰️
                return $this->resolve($name, $type);
            });

            unset(self::$resolving[$cacheKey]);
            return $cached;
        } catch (\Throwable $e) {
            unset(self::$resolving[$cacheKey]);
            throw $e;
        }
    }

    /**
     * Resolve a base namespace prefix from the constitutional MAP 🏛️⚓
     */
    public function resolveLayer(string $key): string
    {
        $upperKey = strtoupper($key);

        $map = \Rbn\Framework\Core\System\Discovery\Clusters\Logic\Definition\Definition::get('namespace', 'MAP') ?? [];
        foreach ($map as $mapKey => $namespace) {
            if (strtoupper($mapKey) === $upperKey) {
                return $namespace;
            }
        }

        return $key . '\\';
    }

    /**
     * Core resolution logic following RBN structural conventions 🧬
     */
    private function resolve(string $name, string $type): ?string
    {
        $name = str_replace(['/', '.'], '\\', $name);
        $nameParts = explode('\\', $name);
        $normalizedName = implode('\\', array_map('ucfirst', $nameParts));

        $typeSuffix = ucfirst($type);
        $typePlural = $typeSuffix . 's';
        $className = $normalizedName . $typeSuffix;
        $flatName = end($nameParts);
        $flatNameNormalized = ucfirst($flatName);

        // 🎼 RBN 3.5: [SOVEREIGN] Purged Scanned Discovery 🛰️⚓
        // We no longer scan folders or use ModuleResolver. Everything goes through Core Layer mapping.
        return $this->resolveCoreLayer($normalizedName, $type);
    }

    /**
     * Resolves components across global framework layers defined in NamespaceMap ⚖️
     */
    private function resolveCoreLayer(string $name, string $type): ?string
    {
        $normalizedName = static::toPascalPath($name);
        $normalizedName = str_replace('/', '\\', $normalizedName);

        $typeSuffix = ucfirst($type);
        $typePlural = $typeSuffix . 's';
        $className = $name . $typeSuffix;

        $nameParts = explode('\\', $name);
        $nameRoot = ucfirst(end($nameParts));

        $map = \Rbn\Framework\Core\System\Discovery\Clusters\Logic\Definition\Definition::get('namespace', 'MAP') ?? [];
        foreach ($map as $baseNamespace) {
            // Strategy A: Standard Layer (e.g., Core\Database\Models\SettingsModel)
            $fqcn = $baseNamespace . "{$typePlural}\\{$className}";
            if (class_exists($fqcn)) {
                return $fqcn;
            }

            // Strategy B: Module Deep Layer (RBN 3.5 Sovereign: Bundles\Module\Controllers\NameController) 🧬🛰️🎯
            // We treat the $name as a potential module folder.
            $moduleName = ucfirst($nameParts[0] ?? $name);
            $moduleAttempt = $baseNamespace . "{$moduleName}\\{$typePlural}\\{$className}";
            if (class_exists($moduleAttempt)) {
                return $moduleAttempt;
            }

            // Strategy C: Segmented Module Layer (RBN 3.5 Symmetry: syshub/security/ipBlock -> Syshub\Controllers\Security\IpBlockController)
            if (count($nameParts) > 1) {
                $tempParts = $nameParts;
                $compModule = ucfirst(array_shift($tempParts));
                $targetClass = ucfirst(array_pop($tempParts)) . $typeSuffix;
                $subPath = !empty($tempParts) ? implode('\\', array_map('ucfirst', $tempParts)) . '\\' : '';

                $segmentedFqcn = $baseNamespace . "{$compModule}\\{$typePlural}\\{$subPath}{$targetClass}";
                if (class_exists($segmentedFqcn)) {
                    return $segmentedFqcn;
                }

                // 🎼 RBN 3.5: [DEEP FOLDER DISCOVERY] - Symmetry: Controllers/Cron/CronController 🏙️🛰️⚓
                if (empty($subPath)) {
                    $folderName = str_replace($typeSuffix, '', $targetClass);
                    $deepFqcn = $baseNamespace . "{$compModule}\\{$typePlural}\\{$folderName}\\{$targetClass}";
                    if (class_exists($deepFqcn)) {
                        return $deepFqcn;
                    }
                }
            }

            // Strategy D: Absolute Deep Layer (Universal Fallback)
            foreach (['Services', 'Tasks', 'UI', 'Frontend', 'Project', 'Master'] as $subLayer) {
                $deepFqcn = $baseNamespace . "{$subLayer}\\{$typePlural}\\{$className}";
                if (class_exists($deepFqcn)) {
                    return $deepFqcn;
                }
                $deepFqcnAlt = $baseNamespace . "{$typePlural}\\{$subLayer}\\{$className}";
                if (class_exists($deepFqcnAlt)) {
                    return $deepFqcnAlt;
                }
            }

            // Strategy D: Flat Deep Layer (e.g., Core\Database\Models\Project\Settings)
            foreach (['Project', 'Master'] as $subLayer) {
                $fqcn = $baseNamespace . "{$typePlural}\\{$subLayer}\\{$nameRoot}\\{$className}";
                if (class_exists($fqcn)) {
                    return $fqcn;
                }
            }
        }

        return null;
    }
}
