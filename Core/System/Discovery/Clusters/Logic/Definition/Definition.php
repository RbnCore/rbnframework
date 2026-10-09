<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\System\Discovery\Clusters\Logic\Definition;

use Rbn\Framework\Core\System\Discovery\Engine\DiscoveryEngine;
use Rbn\Framework\Core\System\Config\Definitions\DbProfiles\MasterDbData;
use Rbn\Framework\Core\System\Config\Definitions\DbProfiles\CommonDbData;
use Rbn\Framework\Core\System\Config\Definitions\DbProfiles\ProjectDbData;
use Rbn\Framework\Core\Support\Definitions\System\NamespaceMap;

/**
 * Definitions - The Framework Static Gateway 🎻🛰️⚓
 * 
 * RBN Framework: Static Facade.
 * Redirects to the Discovery Engine's DefinitionResolver.
 * No local logic, purely a bridge.
 */
class Definition
{
    /** @var bool Recursion Guard 🛡️ */
    private static bool $isResolving = false;

    /**
     * Static access proxy (Transparent bridge to Engine 🛰️)
     */
    public static function get(string $category, string $key): mixed
    {
        // [RBN Framework] RECURSION GUARD 🛡️⚓
        // Detects vertical discovery loops and forces survival fallbacks.
        // Special case: Namespace prefixes are allowed to avoid discovery deadlock.
        if (self::$isResolving) {
            return ($category === 'namespace') ? self::survivalFallback($category, $key) : null;
        }

        self::$isResolving = true;

        try {
            $instance = DiscoveryEngine::instance();

            // [RBN Framework] RBN Framework BOOTSTRAP FALLBACK 🏛️⚔️🛡️⚓
            // If the engine is still initializing or missing, use survival logic.
            if (!$instance) {
                return self::survivalFallback($category, $key);
            }

            // [RBN Framework] REGISTRY FALLBACK 🏛️⚓
            if ($category === 'registry' && !$instance) {
                return [];
            }

            // [RBN Framework] RBN Framework IDENTITY FALLBACK 🏛️⚔️🛡️⚓
            if ($category === 'identity') {
                $setup = \Rbn\Framework\Core\Render\Configs\AssetConfig::PROXY_SETUP;
                return match ($key) {
                    'PROXY_PATHS' => [
                        'project' => $setup['project']['path'] ?? 'project-assets',
                        'framework' => $setup['framework']['path'] ?? 'framework-assets'
                    ],
                    'PROXY_TOKENS' => [
                        'project' => $setup['project']['token'] ?? '@project/',
                        'framework' => $setup['framework']['token'] ?? '@fw/'
                    ],
                    default => null
                };
            }

            return $instance->definitions()->get($category, $key);

        } finally {
            self::$isResolving = false;
        }
    }

    /**
     * RBN Framework Survival Fallback Logic 🧬⚓
     */
    private static function survivalFallback(string $category, string $key): mixed
    {
        if ($category === 'namespace') {
            return match ($key) {
                'FRAMEWORK_PREFIX' => NamespaceMap::FRAMEWORK_PREFIX,
                'PROJECT_PREFIX' => NamespaceMap::PROJECT_PREFIX,
                'MAP' => NamespaceMap::MAP,
                default => null
            };
        }

        if ($category === 'database_master') {
            return match ($key) {
                'KEYS_MAP' => MasterDbData::KEYS_MAP,
                'REQUIRED_TABLES' => MasterDbData::REQUIRED_TABLES,
                default => null
            };
        }

        if ($category === 'database_common') {
            return match ($key) {
                'KEYS_MAP' => CommonDbData::KEYS_MAP,
                'REQUIRED_TABLES' => CommonDbData::REQUIRED_TABLES,
                default => null
            };
        }

        if ($category === 'database_project') {
            return match ($key) {
                'KEYS_MAP' => ProjectDbData::KEYS_MAP,
                'REQUIRED_TABLES' => ProjectDbData::REQUIRED_TABLES,
                'ENV_FILE' => ProjectDbData::ENV_FILE,
                default => null
            };
        }

        return null;
    }
}
