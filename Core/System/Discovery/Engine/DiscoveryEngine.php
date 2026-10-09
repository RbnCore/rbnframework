<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\System\Discovery\Engine;

use Rbn\Framework\Core\System\Discovery\Clusters\Logic\Validation\ValidationResolver;
use Rbn\Framework\Core\System\Discovery\Clusters\Resources\AssetResolver;
use Rbn\Framework\Core\Render\Resolvers\ViewResolver;
use Rbn\Framework\Core\Render\ViewEngine;
use Rbn\Framework\Core\System\Discovery\Engine\Cache\DiscoveryMapper;
use Rbn\Framework\Core\System\Discovery\Clusters\Structure\Namespace\NamespaceResolver;
use Rbn\Framework\Core\System\Discovery\Clusters\Structure\Folder\FolderResolver;
use Rbn\Framework\Core\System\Discovery\Clusters\Logic\Definition\DefinitionResolver;
use Rbn\Framework\Core\System\Discovery\Base\BaseDiscoveryContext;
use Rbn\Framework\Core\System\Discovery\Engine\Drivers\ComponentDriver;
use Rbn\Framework\Core\System\Discovery\Engine\Drivers\MetadataDriver;
use Rbn\Framework\Core\System\Discovery\Clusters\Logic\Component\ComponentResolver;
use Rbn\Framework\Core\System\Discovery\Base\Traits\DiscoveryActionsTrait;
use Rbn\Framework\Core\Support\Contracts\Discovery\DiscoveryInterface;

/**
 * DiscoveryEngine - The Pure Discovery Motor 🛰️⚙️
 * 
 * RBN Framework: [CENTRALIZED]
 * Manages discovery via a unified action trait and specialized drivers.
 */
class DiscoveryEngine extends BaseDiscoveryContext implements DiscoveryInterface
{
    /**
     * RBN Framework: Discovery Actions 🧬🛰️🎡
     * Centralized via DiscoveryActionsTrait.
     */
    use DiscoveryActionsTrait;

    /** @var array<string, bool> Combined Recursion Guard 🛡️ */
    private static array $resolvingKeys = [];

    /**
     * Internal Bridge: Orchestrate between Component and Metadata Drivers. 🌉
     */
    protected function resolveDiscovery(string $type, string $name)
    {
        $key = "{$type}:{$name}";

        // 🛡️ RBN Framework: [COMBINED RECURSION GUARD] 🏛️⚓
        // Allows same-named components of different types (e.g., provider:module vs cluster:module) 
        // to resolve nestedly without deadlocking.
        if (isset(self::$resolvingKeys[$key])) {
            return null; // Stop the loop for the EXACT same component!
        }

        self::$resolvingKeys[$key] = true;

        try {
            return match ($type) {
                'constant', 'validation' => $this->getDriver('metadata')->{$type}($name),
                default => $this->getDriver('component')->{$type}($name)
            };
        } finally {
            unset(self::$resolvingKeys[$key]);
        }
    }


    /** @var self|null Singleton instance (Shared Cache) */
    protected static ?self $instance = null;

    /** @var array Lazy loaded drivers & resolvers */
    private array $instances = [];

    /** @var DiscoveryMapper|null Common persistence layer */
    private ?DiscoveryMapper $mapper = null;

    /**
     * Initialize the Discovery Engine ⚓
     */
    public function __construct(?object $rbn = null)
    {
        // 🎼 RBN Framework: Singleton Seal 🏛️⚔️🛡️⚓
        if (self::$instance === null) {
            self::$instance = $this;
        }

        $this->rbn = $rbn;

        // 🎼 Global Discovery DNA Enjeksiyonu 🧬💉
        BaseDiscoveryContext::setGlobalDNA($this->rbn);
    }

    /**
     * Singleton Instance Getter 🎻⚓
     */
    public static function instance(?object $rbn = null): self
    {
        if (self::$instance === null) {
            self::$instance = new self($rbn);
        } elseif (self::$instance->rbn === null && $rbn !== null) {
            // 🎼 RBN Framework: [DNA HOT REFILL] 💉🧬
            // Eğer motor rbn objesi olmadan ayağa kalkmışsa, 
            // gelen ilk geçerli objeyle motorun çekirdeğini doldur.
            self::$instance->rbn = $rbn;
            BaseDiscoveryContext::setGlobalDNA($rbn);
        }

        return self::$instance;
    }

    /* ==========================================================================
       [ RBN Framework RESOLUTION ] - Unified Entry Point 🏹🛰️⚓
       ========================================================================== */

    /**
     * Unified component resolution proxy. 🏛️⚓靶
     * Bridges context-aware resolution to the ComponentResolver.
     */
    public function resolve(object $context, string $type, ?string $name = null, bool $mandatory = true): ?object
    {
        return ComponentResolver::resolve($context, $type, $name, $mandatory);
    }

    /* ==========================================================================
       [ RESOLVER ACCESSORS ] - Data & Structure 🛰️
       ========================================================================== */

    public function configs()
    {
        return $this->getResolver('config');
    }

    public function views()
    {
        return $this->getResolver('view');
    }

    public function assets()
    {
        return $this->getResolver('asset');
    }

    public function folders()
    {
        return $this->getResolver('folder');
    }

    public function namespace()
    {
        return $this->getResolver('namespace');
    }

    public function definitions()
    {
        return $this->getResolver('definition');
    }

    /**
     * Get a standardized resolver instance by key 🛰️
     */
    public function getResolver(string $type): object
    {
        return $this->instances[$type] ??= match ($type) {
            'view' => new ViewResolver($this->rbn),
            'view_engine' => new ViewEngine(),
            'asset' => new AssetResolver($this->rbn),

            /* --- Structure Resolvers --- */
            'folder' => new FolderResolver($this->rbn),
            'namespace' => new NamespaceResolver($this->rbn),
            'definition' => new DefinitionResolver($this->rbn),
            'config' => new DefinitionResolver($this->rbn),
            'configs' => new DefinitionResolver($this->rbn),

            /* --- Logic-Oriented Resolvers --- */
            'validation' => new ValidationResolver($this->rbn),

            /* --- RBN Framework Component Resolvers (Autonomous Fallback) --- */
            default => new NamespaceResolver($this->rbn)
        };
    }

    /**
     * Get a driver instance by key 🎡
     */
    public function getDriver(string $type): object
    {
        return $this->instances[$type] ??= match ($type) {
            'component' => new ComponentDriver($this->rbn),
            'metadata' => new MetadataDriver($this->rbn),
            default => throw new \Exception("Unknown Discovery Driver: {$type}")
        };
    }

    /**
     * Get the common persistence mapper 🛰️📦
     */
    public function getMapper(): DiscoveryMapper
    {
        return $this->mapper ??= new DiscoveryMapper();
    }

    /**
     * Discovery Action for Core Clusters (Engine/Architectural Motor) 🪐⚓
     */
    public function cluster(string $name)
    {
        return $this->getResolver($name);
    }
}
