<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\System\Discovery\Engine\Drivers;

use Rbn\Framework\Core\System\Paths\Paths;

/**
 * PathDriver - Specialized Physical Path Discovery 📂🛰️
 * 
 * RBN 3.5: Bridges the DiscoveryEngine to the physical directory structure.
 * Wraps the Paths hub for consistent folder and file resolution.
 */
class PathDriver
{
    /** @var object The Framework Orchestrator */
    private object $rbn;

    public function __construct(object $rbn)
    {
        $this->rbn = $rbn;
    }

    /* ==========================================================================
       [ DISCOVERY API ] - Physical Endpoints 🔍
       ========================================================================== */

    /**
     * Resolve a generic path or alias 📂
     */
    public function resolve(string $alias): ?string
    {
        // 1. Framework Root Check
        if ($alias === 'framework') return Paths::frameworkRoot();
        
        // 2. Project Root Check
        if ($alias === 'project') return Paths::project()->root();

        // 3. Dynamic Alias Resolution via Paths Hub
        if (method_exists(Paths::class, $alias)) {
            return Paths::$alias();
        }

        return null;
    }

    /**
     * Resolve a specialized folder from the FolderMatrix 🏛️
     */
    public function resolveFolder(string $key): ?string
    {
        // 🎯 RBN 3.5: Connect to the specialized Folder Resolver within the cluster
        return \Rbn\Framework\Core\System\Discovery\Engine\DiscoveryEngine::instance()->folders()->resolveLayerDirectory($key);
    }
}
