<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\System\Discovery\Base;

use Rbn\Framework\Core\Base\BaseComponent;

/**
 * BaseDiscoveryContext - The Standardized Discovery DNA 🧬🏛️🛰️⚖️
 * 
 * RBN Framework: Centralized base for all Discovery Contexts.
 * Provides resilient diagnostic reporting, input normalization and caching capabilities.
 * Inherits the full RBN DNA (Shield & ErrorHandling) via BaseComponent.
 */
abstract class BaseDiscoveryContext extends BaseComponent
{
    /** @var object|null Global DNA Slot for autonomous booting 🧬 */
    protected static ?object $globalRbn = null;

    /**
     * Discovery Context Constructor 🚀
     * Automatically seeds DNA from the global slot if no orchestrator is provided.
     */
    public function __construct(?object $rbn = null)
    {
        // 🎼 RBN Framework: Absolute Isolation 🪓🛡️⚓
        // We DO NOT call parent::__construct() here.
        // We MUST prevent BaseComponent from triggering bootConcernsContext().

        $this->rbn = $rbn ?? self::$globalRbn;

        // 🎼 RBN Framework: Pure Discovery Linkage 🏛️⚔️🛡️⚓
        // Ensure the discovery motor is ALWAYS initialized across the hierarchy.
        $this->discover = \Rbn\Framework\Core\System\Discovery\Engine\DiscoveryEngine::instance($this->rbn);
    }

    /**
     * Seed the Global Discovery DNA 🏛️⚓
     */
    public static function setGlobalDNA(?object $rbn): void
    {
        self::$globalRbn = $rbn;
    }

    /**
     * Trigger RbnShield Diagnostic for discovery failures 🛡️🎭🚫
     * 
     * @param string $name The name of the missing component/folder.
     * @param string $type The category (e.g., Model, Service, Folder).
     */
    protected function triggerDiagnostic(string $name, string $type): void
    {
        // 🥇 Utilizing the Global Shield integrated into Root DNA ($this->shield)
        // RBN Framework: Lazy initialization to prevent boot-time recursion 🧬🛡️⚓
        $this->shield ??= (function_exists('shield') ? shield() : null);

        if ($this->shield) {
            $this->shield->diagnostic(
                "Discovery Failure 🛰️",
                "The Discovery Engine failed to resolve a [{$type}] named [{$name}].",
                "1. Verification: Check if the resource exists in the physical directory.\n2. Convention: Ensure the naming follows RBN standards (e.g., Suffixes like Model/Service).\n3. Metadata: Check if the registration is correctly defined in constants or folder matrix."
            );
        }
    }

    /**
     * Standardized Result Caching via DiscoveryMapper ⚡📦
     * 
     * @param string $key Unique cache key for the discovery attempt.
     * @param callable $resolver The logic to execute if cache miss occurs.
     * @return mixed
     */
    protected function cacheDiscovery(string $key, callable $resolver)
    {
        $mapper = $this->discover()->getMapper();
        $cached = $mapper->get($key);

        if ($cached !== null) {
            return $cached;
        }

        $result = $resolver();

        if ($result !== null) {
            $mapper->set($key, $result);
        }

        return $result;
    }

}
