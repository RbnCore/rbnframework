<?php

namespace Rbn\Framework\Bundles\RbnSuite\RbnAdmin\Services;

use Rbn\Framework\Core\Base\Services\BaseService;
use Rbn\Framework\Bundles\RbnSuite\RbnAdmin\Providers\Fluent\UserStatsChannel;
use Rbn\Framework\Bundles\RbnSuite\RbnAdmin\Providers\Fluent\TrafficStatsChannel;

/**
 * AnalyticsService - Unified System Intelligence Hub 📊🏛️⚓
 * RBN 3.5 Masterpiece Standard.
 * 
 * @property \Rbn\Framework\Bundles\RbnSuite\RbnAdmin\Providers\AnalyticsProvider $analyticsProvider
 * 
 * Bu servis, sistemdeki tüm istatistiksel verileri (Kullanıcı, Trafik vb.)
 * tek bir merkezden akıcı bir şekilde yönetir.
 */
class AnalyticsService extends BaseService
{
    /**
     * Fluent Accessor for User Statistics 👥🕊️
     */
    public function users(): UserStatsChannel
    {
        return new UserStatsChannel();
    }

    /**
     * Fluent Accessor for Web Traffic Analytics 🛫🕊️
     */
    public function traffic(): TrafficStatsChannel
    {
        return new TrafficStatsChannel();
    }

    /**
     * Records a new visit hit (Proxy to Storage) 🎯
     * Shared between Frontend and Middleware.
     */
    public function record(array $data = []): void
    {
        // RBN 3.5: [PASSTHROUGH] Direct delegation to the storage hub.
        $this->storage->traffic()->record($data);
    }

    /**
     * Check if Google Analytics (GA4) integration is active for the current project 🤖📊
     */
    public function isGoogleAnalyticsActive(): bool
    {
        return $this->service('api')->google('analytics_active');
    }

    public function getGoogleAnalyticsReports(?string $startDate = null, ?string $endDate = null, string $type = 'standard'): array
    {
        return $this->service('api')->google('analytics' . ($type === 'map' ? '_map' : ''), [
            'start_date' => $startDate,
            'end_date'   => $endDate
        ]);
    }
}
