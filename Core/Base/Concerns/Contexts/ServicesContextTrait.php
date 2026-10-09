<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Base\Concerns\Contexts;

use Rbn\Framework\Core\Base\Concerns\BaseContextTrait;
use Rbn\Framework\Core\System\Discovery\Base\Traits\DiscoveryActionsTrait;

/**
 * ServicesContextTrait - Core Discovery & Context Hub 🧬⚓
 * 
 * RBN Framework: Accesses core properties via BaseContextTrait hierarchy.
 * Prevents code redundancy while ensuring total linter compatibility.
 */
trait ServicesContextTrait
{
    /** --- Shared DNA Hierarchy --- */
    use BaseContextTrait;
    use DiscoveryActionsTrait;

    /**
     * Internal Bridge: Resolve discovery via the core engine. 🌉
     */
    protected function resolveDiscovery(string $type, string $name)
    {
        return $this->discover()->{$type}($name);
    }

    /**
     * Initialize Services Context (The Hub Sync) ⚓
     */
    protected function initServicesContext(): array
    {
        // 🎯 RBN Framework [PANIC BRAKE] 🏹
        // During a terminal error rendering, keep the context minimal to avoid recursion.
        if (defined('RBN_PANIC_ACTIVE')) {
            return [
                'service' => null,
                'model' => null
            ];
        }

        return [
            'service' => $this->service,
            'model' => $this->model
        ];
    }

    /* ==========================================================================
       [ DISCOVERY PROXIES ] - Shorthand access to the Engine 🔍
       ========================================================================== */

    public function config(string $key, $default = null)
    {
        // 🎼 RBN Framework: Parameter Unification 🧬⚓
        return $this->discover()->configs()->get('config', $key) ?? $default;
    }

    /**
     * Get the Database connection instance 🪐
     */
    public function db(): \Rbn\Framework\Core\Database\Database
    {
        return \Rbn\Framework\Core\Database\Database::getInstance();
    }

    /**
     * Get the Session Manager instance 🔑
     */
    public function session(): \Rbn\Framework\Core\Services\System\Managers\SessionManager
    {
        return $this->manager('session');
    }
}
