<?php

namespace Rbn\Framework\Bundles\RbnSuite\RbnAdmin\Providers\Fluent;

use Rbn\Framework\Core\Base\Patterns\BaseChannel;
use Rbn\Framework\Bundles\RbnSuite\RbnAdmin\Traits\TrafficAnalysisTrait;

/**
 * TrafficStatsChannel - Fluent Web Traffic Analytics API 🛫🕊️⚓
 * RBN 3.5 Masterpiece Standard.
 * 
 * @property \Rbn\Framework\Bundles\RbnSuite\RbnAdmin\Providers\AnalyticsProvider $analyticsProvider
 */
class TrafficStatsChannel extends BaseChannel
{
    use TrafficAnalysisTrait;

    public function __construct()
    {
        parent::__construct('analytics_traffic');
    }

    /* ==========================================================================
       [ REQUIRED IMPLEMENTATIONS ] - BaseChannel Abstract Methods 🎻
       ========================================================================== */

    /**
     * Trait içindeki computeSummary() metodunu stats() olarak otonom mühürle. ✨🧬
     */
    public function stats(): array
    {
        return $this->computeSummary();
    }

    /* ==========================================================================
       [ CUSTOM FLUENT METHODS ] 🛫🕊️
       ========================================================================== */

    /**
     * Get dashboard summary stats (Zero-Latency) 📊⚡
     */
    public function summary(): array
    {
        return $this->getFromCache('summary', function () {
            return $this->computeSummary();
        });
    }

    /**
     * Get current traffic hits 📈
     */
    public function hits(): int
    {
        return $this->getFromCache('hits', function () {
            $stats = $this->computeSummary();
            return (int) ($stats['today_hits'] ?? 0);
        });
    }

    /**
     * Get 7-day trend data 📉
     */
    public function trend(): array
    {
        return $this->getFromCache('trend', function () {
            $stats = $this->computeSummary();
            return $stats['seven_day_trend'] ?? [];
        });
    }
}
