<?php

declare(strict_types=1);

namespace Rbn\Framework\Bundles\Internal\Syshub\Services;

use Rbn\Framework\Core\Base\Services\BaseService;

/**
 * SyshubService - Modular Hub Orchestrator 🛡️🛰️⚓
 * RBN 3.5 Masterpiece Standard.
 * 
 * @property-read \Rbn\Framework\Bundles\Internal\Syshub\Handlers\DataPurgeHandler $purge
 * @property-read \Rbn\Framework\Bundles\Internal\Syshub\Handlers\SyshubHandler $syshubHandler
 * @property-read \Rbn\Framework\Bundles\Internal\Syshub\Providers\SyshubProvider $syshubProvider
 * @property-read \Rbn\Framework\Bundles\Internal\Syshub\Providers\SyshubMaintenanceProvider $maintenance
 * @property-read \Rbn\Framework\Bundles\Internal\Syshub\Handlers\SyshubSecurityHandler $security
 * @property-read \Rbn\Framework\Bundles\Internal\Syshub\Providers\SyshubFirewallProvider $firewall
 * @property-read \Rbn\Framework\Bundles\Internal\Syshub\Providers\SyshubRateLimitProvider $rateLimit
 * @property-read \Rbn\Framework\Bundles\Internal\Syshub\Providers\SyshubWhitelistProvider $whitelist
 * @property-read \Rbn\Framework\Bundles\Internal\Syshub\Providers\SyshubCronProvider $cron
 */
class SyshubService extends BaseService
{
    /**
     * Data Purge Logic Hub 🧹🛰️⚓
     */
    public function purge()
    {
        return $this->handler('purge');
    }

    /**
     * Maintenance Logic Hub 🚧🛡️
     */
    public function maintenance()
    {
        return $this->provider('maintenance');
    }

    /**
     * Security Logic Hub 🛡️🛰️
     */
    public function security()
    {
        return $this->handler('syshubSecurity');
    }

    /**
     * Security Data Providers 🛡️⚓
     */
    public function firewall()
    {
        return $this->provider('firewall');
    }

    public function rateLimit()
    {
        return $this->provider('rateLimit');
    }

    public function ipBlock()
    {
        return $this->repository('common.ipBlock');
    }

    public function whitelist()
    {
        return $this->provider('whitelist');
    }

    public function cron()
    {
        return $this->provider('syshubCron');
    }

    /**
     * Get Dashboard Stats for Syshub 📊
     */
    public function getDashboardStats(): array
    {
        return $this->handler('syshub')->getDashboardStats();
    }

    /**
     * Optimize PHP Limits 🚀
     */
    public function optimizePhpLimits(): bool
    {
        return $this->provider('syshub')->writePhpLimits(64);
    }
}
