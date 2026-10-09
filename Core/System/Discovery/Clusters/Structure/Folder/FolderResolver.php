<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\System\Discovery\Clusters\Structure\Folder;

use Rbn\Framework\Core\System\Discovery\Base\BaseDiscoveryContext;
use Rbn\Framework\Core\System\Paths\Paths;

/**
 * FolderResolver - Specialized Physical Path Discovery 📂🛰️⚓
 * 
 * RBN Framework: Converts folder aliases and layer keys into absolute directory paths.
 * Decouples physical disk resolution from namespace logic.
 */
class FolderResolver extends BaseDiscoveryContext
{
    /**
     * Resolve a generic path or alias to its absolute directory 📂
     */
    public function path(string $alias): ?string
    {
        // 1. Framework Root Gateway
        if ($alias === 'framework')
            return Paths::frameworkRoot();

        // 2. Project Root Gateway
        if ($alias === 'project')
            return Paths::project()->root();

        // 3. Dynamic Alias Resolution via Paths Hub (if exists as a context)
        if (method_exists(Paths::class, $alias)) {
            return Paths::$alias();
        }

        return null;
    }

    /**
     * Resolve the physical directory root for a specific namespace layer 🏛️
     * ZERO-CODE: Derived dynamically from NamespaceMap definitions.
     */
    public function resolveLayerDirectory(string $baseNamespace): ?string
    {
        $baseNamespace = trim($baseNamespace, '\\');

        // 🧬 RBN Framework Symmetry: Unified Definition Engine access (Otonom Kalkan)
        $frameworkPrefix = trim(\Rbn\Framework\Core\System\Discovery\Clusters\Logic\Definition\Definition::get('namespace', 'FRAMEWORK_PREFIX') ?? '', '\\');
        $projectPrefix = trim(\Rbn\Framework\Core\System\Discovery\Clusters\Logic\Definition\Definition::get('namespace', 'PROJECT_PREFIX') ?? '', '\\');

        // 🎯 RBN Framework Symmetry: Path mapping via definition prefixes
        // 1. Framework Layer Alignment
        if (strpos($baseNamespace, $frameworkPrefix) === 0) {
            $relativeNamespace = trim(substr($baseNamespace, strlen($frameworkPrefix)), '\\');
            $relativeFolder = static::toPascalPath(str_replace('\\', '/', $relativeNamespace));
            return Paths::frameworkRoot() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativeFolder);
        }

        // 2. Project Layer Alignment
        if (strpos($baseNamespace, $projectPrefix) === 0) {
            $relativeNamespace = trim(substr($baseNamespace, strlen($projectPrefix)), '\\');
            $relativeFolder = static::toPascalPath(str_replace('\\', '/', $relativeNamespace));
            return Paths::project()->root() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativeFolder);
        }

        return null;
    }
}
