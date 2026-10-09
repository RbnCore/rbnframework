<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\System\Discovery\Engine\Drivers;

use Rbn\Framework\Core\System\Paths\Paths;
use Rbn\Framework\Core\System\Discovery\Base\Traits\DiscoveryActionsTrait;

/**
 * ComponentDriver - Specialized Object Discovery & Factory 🏗️🛰️
 * 
 * RBN Framework: [CENTRALIZED]
 * Performs physical discovery and instantiation of instance-based components.
 */
class ComponentDriver
{
    /**
     * RBN Framework: Discovery Actions 🧬🛰️🎡
     * Centralized via DiscoveryActionsTrait.
     */
    use DiscoveryActionsTrait;

    /**
     * Internal Bridge: Resolve discovery via the physical finder. 🌉
     */
    protected function resolveDiscovery(string $type, string $name)
    {
        // 🎼 Driver Specific Rule: Map 'queries' to internal 'query' type.
        $internalKey = ($type === 'queries') ? 'query' : $type;

        return $this->discover($name, $internalKey);
    }

    /** @var object|null The Framework Orchestrator */
    public ?object $rbn = null;

    /** @var array<string, object> Cached component instances */
    protected array $instances = [];

    public function __construct(?object $rbn = null)
    {
        $this->rbn = $rbn;
    }

    /* ==========================================================================
       [ CORE ENGINE ] - Resolution & Instantiation 🧬
       ========================================================================== */

    /**
     * Unified Discovery Engine Method 🛸
     */
    private function discover(string $name, string $type)
    {
        $cacheKey = strtolower("{$type}:{$name}");
        if (isset($this->instances[$cacheKey])) {
            return $this->instances[$cacheKey];
        }

        // 1. Resolve Class Name (FQCN) via Discovery Namespace Resolver 🧬
        $class = \Rbn\Framework\Core\System\Discovery\Engine\DiscoveryEngine::instance()->namespace()->find($name, $type);

        // 2. Instantiate & Configure
        if ($class && class_exists($class)) {
            $instance = new $class();

            // RBN Framework: BaseComponent handles its own Hub Sync via bootBaseContext().
            return $this->instances[$cacheKey] = $instance;
        }

        return null;
    }
}
