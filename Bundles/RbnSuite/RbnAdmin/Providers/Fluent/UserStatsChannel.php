<?php

namespace Rbn\Framework\Bundles\RbnSuite\RbnAdmin\Providers\Fluent;

use Rbn\Framework\Core\Base\Patterns\BaseChannel;


/**
 * UserStatsChannel - Fluent User Analytics API 👥🕊️⚓
 * RBN Framework Standard.
 * 
 * @property \Rbn\Framework\Bundles\RbnSuite\RbnAdmin\Providers\AnalyticsProvider $analyticsProvider
 */
class UserStatsChannel extends BaseChannel
{
    public function __construct()
    {
        parent::__construct('analytics_users');
    }

    /* ==========================================================================
       [ REQUIRED IMPLEMENTATIONS ] - BaseChannel Abstract Methods 🎻
       ========================================================================== */

    public function get(): array
    {
        return $this->all();
    }

    public function count(): int
    {
        return $this->total();
    }

    public function stats(): array
    {
        return $this->distribution();
    }

    /* ==========================================================================
       [ CUSTOM FLUENT METHODS ] 👥🕊️
       ========================================================================== */

    /**
     * Get total registered users 📈
     */
    public function total(): int
    {
        return $this->getFromCache('total', function() {
            $stats = $this->analyticsProvider->getUserStats();
            return (int) ($stats['total'] ?? 0);
        });
    }

    /**
     * Get user distribution by status 📊
     */
    public function distribution(): array
    {
        return $this->getFromCache('distribution', function() {
            $stats = $this->analyticsProvider->getUserStats();
            return $stats['status'] ?? [];
        });
    }

    /**
     * Get user distribution by role 🎭
     */
    public function roles(): array
    {
        return $this->getFromCache('roles', function() {
            $stats = $this->analyticsProvider->getUserStats();
            return $stats['roles'] ?? [];
        });
    }

    /**
     * Get all summarized user stats 🍱
     */
    public function all(): array
    {
        return $this->getFromCache('all', function() {
            return $this->analyticsProvider->getUserStats();
        });
    }
}
