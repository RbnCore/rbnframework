<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\System\Discovery\Base\Traits;

/**
 * NamespaceExpansionTrait - Autonomous Namespace Expansion DNA 🧬🛰️⚓
 * 
 * RBN 3.5 "Masterpiece": [DECENTRALIZED INTELLIGENCE]
 * Provides capabilities to expand "Clean Strings" (paths) into Full Qualified Class Names (FQCN)
 * using dynamic prefixes from the framework and project maps.
 */
trait NamespaceExpansionTrait
{
    /**
     * Expand relative registry strings (Clean Strings) into full FQCN 🧬🛰️⚓
     * 
     * RBN 3.5: Dynamically resolves prefixes from Framework and Project definitions.
     */
    protected static function expandRegistryResult(?string $result): ?string
    {
        if (!$result) {
            return null;
        }

        $clean = ltrim($result, '\\');
        if (str_starts_with($clean, 'Rbn\\')) {
            return $result;
        }

        // 🥇 RBN 3.5: [SOVEREIGN EXPANSION] 🧬🗺️
        // Get prefixes from the Definition category (Autonomous).
        $prefixes = [
            \Rbn\Framework\Core\System\Discovery\Clusters\Logic\Definition\Definition::get('namespace', 'FRAMEWORK_PREFIX') ?? 'Rbn\Framework\\',
            \Rbn\Framework\Core\System\Discovery\Clusters\Logic\Definition\Definition::get('namespace', 'PROJECT_PREFIX') ?? 'Rbn\Project\\'
        ];

        foreach ($prefixes as $prefix) {
            $fqcn = $prefix . ltrim($result, '\\');
            if (class_exists($fqcn)) {
                return $fqcn;
            }
        }

        return $result;
    }
}
