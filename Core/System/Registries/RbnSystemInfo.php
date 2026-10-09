<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\System\Registries;

use Rbn\Framework\Core\Base\Data\BaseConfig;
use Rbn\Framework\Core\System\Registries\SystemRegistry;

/**
 * RbnSystemInfo - Central Metadata and Aggregator 🛡️🏙️⚓
 * 
 * RBN Framework: Hierarchically resolves framework-wide constants
 * by leveraging the Universal map() Orchestrator via SystemRegistry.
 * Isolated as a static utility to avoid naming collisions with BaseConfig.
 */
class RbnSystemInfo
{
    private static array $cache = [];

    /**
     * Dynamically resolve any metadata from central or modular classes 🛡️⚓
     */
    public static function get(string $key)
    {
        $key = strtoupper($key);

        // 0. Cache Discovery (Fast Path) ⚡ - Only return if NOT null to allow re-discovery
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }

        // 1. Core Framework Identity Discovery (RBN Framework Source of Truth) 🧬🏛️⚓
        $identityClass = \Rbn\Framework\Core\Support\Definitions\System\FrameworkIdentity::class;
        if (defined("$identityClass::$key")) {
            return self::$cache[$key] = constant("$identityClass::$key");
        }

        // 2. Resolve via Triple Gates Metadata (Universal map() Orchestrator) 🎻🛰️
        $map = (new SystemRegistry())->registerMap()['metadata'] ?? [];

        foreach ($map as $prefix => $class) {
            if (str_starts_with($key, $prefix)) {
                $targetKey = substr($key, strlen($prefix)); // ADMIN_NAME -> NAME
                
                // 🎼 RBN Framework: Hybrid Discovery 🧬🛰️⚓
                // Try original and normalized namespace candidates
                $candidates = [
                    $class,
                    'Rbn\\Framework\\' . ltrim($class, '\\')
                ];

                foreach ($candidates as $candidate) {
                    if (class_exists($candidate)) {
                        $class = $candidate;
                        break;
                    }
                }

                if (class_exists($class)) {
                    // 🎼 RBN Framework: Data Orchestration 🧬🛰️⚓
                    // We rely on the generic data() bridge to resolve NAME, VERSION, etc.
                    if (is_subclass_of($class, BaseConfig::class)) {
                        $data = $class::data($targetKey);
                        if ($data !== null) {
                            return self::$cache[$key] = $data;
                        }
                    }

                    // B. Legacy Constant Fallback (Standard constant lookup) 💎
                    $targetConstant = $class . '::' . $targetKey;
                    if (defined($targetConstant)) {
                        return self::$cache[$key] = constant($targetConstant);
                    }
                }
            }
        }

        return self::$cache[$key] = null;
    }
}
