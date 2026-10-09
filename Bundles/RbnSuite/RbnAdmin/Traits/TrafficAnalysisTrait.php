<?php

namespace Rbn\Framework\Bundles\RbnSuite\RbnAdmin\Traits;

use Rbn\Framework\Core\System\Paths\Paths;

/**
 * TrafficAnalysisTrait - Analytics engine for WebtrafficService 📊
 * RBN Framework: Modular analytical logic with persistent stats support.
 */
trait TrafficAnalysisTrait
{
    protected ?string $queryDate = null;
    protected ?string $queryStartDate = null;
    protected ?string $queryEndDate = null;
    protected ?int $queryTrendDays = null;
    protected int $queryLimit = 0;
    protected array $queryFilters = [];
    protected ?string $projectKey = null;

    /**
     * Set the project key context for traffic logs
     */
    public function withProject(?string $projectKey): self
    {
        $this->projectKey = $projectKey;
        $this->storage->traffic()->projectKey = $projectKey;
        return $this;
    }

    /**
     * Start a new fluent query
     */
    public function query(): self
    {
        $this->queryDate = null;
        $this->queryStartDate = null;
        $this->queryEndDate = null;
        $this->queryTrendDays = null;
        $this->queryLimit = 0;
        $this->queryFilters = [];
        return $this;
    }

    public function forDate(string $date): self
    {
        $this->queryDate = $date;
        return $this;
    }

    public function forToday(): self
    {
        $this->queryDate = date('Y-m-d');
        return $this;
    }

    public function forRange(string $startDate, string $endDate): self
    {
        $this->queryStartDate = $startDate;
        $this->queryEndDate = $endDate;
        return $this;
    }

    public function forTrend(int $days): self
    {
        $this->queryTrendDays = $days;
        return $this;
    }

    public function limitQuery(int $count): self
    {
        $this->queryLimit = $count;
        return $this;
    }

    /**
     * Retrieve full data based on current filters or trends.
     */
    public function get(): array
    {
        $provider = $this->storage->traffic();
        $data = [];

        if ($this->queryStartDate && $this->queryEndDate) {
            $start = strtotime($this->queryStartDate);
            $end = strtotime($this->queryEndDate);
            if ($start !== false && $end !== false) {
                // Loop through all days in range
                for ($current = $start; $current <= $end; $current = strtotime("+1 day", $current)) {
                    $dateStr = date('Y-m-d', $current);
                    $dayData = $provider->get('date_' . $dateStr, []);
                    foreach ($dayData as &$item) {
                        $item['date'] = $dateStr;
                    }
                    $data = array_merge($data, $dayData);
                }
            }
        } elseif ($this->queryDate) {
            $data = $provider->get('date_' . $this->queryDate, []);
        } elseif ($this->queryTrendDays) {
            $trend = [];
            for ($i = $this->queryTrendDays - 1; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-$i days"));
                $hits = $provider->get('date_' . $date, []);
                $trend[$date] = count($hits);
            }
            return $trend;
        }

        // Apply filters
        if (!empty($this->queryFilters)) {
            foreach ($this->queryFilters as $filter) {
                $data = array_filter($data, $filter);
            }
        }

        return $this->queryLimit > 0 ? array_slice($data, 0, $this->queryLimit) : $data;
    }

    /**
     * Toplam Hit Sayısı 🛰️⚓
     */
    public function count(): int
    {
        $provider = $this->storage->traffic();
        $dates = $provider->listDates();
        $total = 0;
        foreach ($dates as $date) {
            $total += count($provider->get('date_' . $date, []));
        }
        return $total;
    }

    /**
     * Dashboard Özet İstatistikleri (Zero-Latency Mode) 📊⚡
     */
    public function computeSummary(): array
    {
        static $isCalculating = false;
        if ($isCalculating) {
            return []; // Otonom döngüyü kır! 🛡️⚓
        }
        $isCalculating = true;

        try {
            $provider = $this->storage->traffic();
            $todayStr = date('Y-m-d');
            $yearMonth = date('Y-m');

            $monthData = $provider->getMonthlyData($yearMonth);
            $todayData = $monthData['days'][$todayStr] ?? [
                'total_hits' => 0,
                'human_hits' => 0,
                'bot_hits' => 0,
                'devices' => ['PC' => 0, 'Mobile' => 0, 'Tablet' => 0],
                'locations' => [],
                'bots_summary' => [],
                'ip_log' => []
            ];

            $totalHits = 0;
            $totalOrganic = 0;
            $totalBot = 0;
            $locationStats = [];
            $deviceStats = ['PC' => 0, 'Mobile' => 0, 'Tablet' => 0];

            $dates = $provider->listDates();
            $processedMonths = [];
            foreach ($dates as $d) {
                $dMonth = substr($d, 0, 7);
                if (isset($processedMonths[$dMonth])) continue;
                $processedMonths[$dMonth] = true;

                $mData = $provider->getMonthlyData($dMonth);
                $sum = $mData['summary'] ?? [];

                $totalHits += ($sum['total_hits'] ?? 0);
                $totalOrganic += ($sum['human_hits'] ?? 0);
                $totalBot += ($sum['bot_hits'] ?? 0);

                foreach ($sum['devices'] ?? [] as $dev => $cnt) {
                    $deviceStats[$dev] = ($deviceStats[$dev] ?? 0) + $cnt;
                }
                foreach ($sum['locations'] ?? [] as $locKey => $cnt) {
                    $locationStats[$locKey] = ($locationStats[$locKey] ?? 0) + $cnt;
                }
            }

            $topLocation = 'Bilinmiyor';
            if (!empty($locationStats)) {
                $locs = $locationStats;
                foreach (array_keys($locs) as $locKey) {
                    if (str_starts_with($locKey, 'Bot') || str_contains($locKey, 'Bot')) {
                        unset($locs[$locKey]);
                    }
                }
                if (count($locs) > 1) {
                    unset($locs['Localhost']);
                }
                arsort($locs);
                $topLocation = key($locs) ?: 'Bilinmiyor';
            }

            $todayLocs = $todayData['locations'] ?? [];
            foreach (array_keys($todayLocs) as $locKey) {
                if (str_starts_with($locKey, 'Bot') || str_contains($locKey, 'Bot')) {
                    unset($todayLocs[$locKey]);
                }
            }
            if (count($todayLocs) > 1) {
                unset($todayLocs['Localhost']);
            }
            arsort($todayLocs);
            $topLocationToday = key($todayLocs) ?: 'Bilinmiyor';

            return [
                'active_now' => $this->getActiveCount(5),
                'today_hits' => (int) ($todayData['total_hits'] ?? 0),
                'total_hits' => $totalHits,
                'top_location' => $topLocation,
                'top_location_today' => $topLocationToday,
                'seven_day_trend' => $this->query()->forTrend(7)->get(),
                'device_stats' => $todayData['devices'] ?? ['PC' => 0, 'Mobile' => 0, 'Tablet' => 0],
                'total_device_stats' => $deviceStats,
                'bot_stats' => [
                    'organic' => (int) ($todayData['human_hits'] ?? 0),
                    'bot' => (int) ($todayData['bot_hits'] ?? 0)
                ],
                'total_bot_stats' => [
                    'organic' => $totalOrganic,
                    'bot' => $totalBot
                ],
                'top_pages' => $this->query()->forToday()->top('url', 5),
                'top_sources' => $this->query()->forToday()->top('referer', 5)
            ];
        } finally {
            $isCalculating = false; // Mührü kaldır, yolu aç! 🛰️🔓
        }
    }

    /**
     * Get Device Distribution Stats (PC, Mobile, Tablet)
     */
    public function devices(): array
    {
        $hits = $this->get();
        $stats = ['PC' => 0, 'Mobile' => 0, 'Tablet' => 0];

        foreach ($hits as $hit) {
            $ua = strtolower($hit['ua'] ?? '');

            if (str_contains($ua, 'ipad') || str_contains($ua, 'tablet')) {
                $stats['Tablet']++;
            } elseif (str_contains($ua, 'mobile') || str_contains($ua, 'iphone') || str_contains($ua, 'android') || str_contains($ua, 'phone')) {
                $stats['Mobile']++;
            } else {
                $stats['PC']++;
            }
        }

        return $stats;
    }

    /**
     * Get Top-N items for a specific key (e.g. url, referer, lang)
     */
    public function top(string $key, int $limit = 5): array
    {
        $hits = $this->get();
        $counts = [];

        foreach ($hits as $hit) {
            $value = $hit[$key] ?? 'Unknown';
            $counts[$value] = ($counts[$value] ?? 0) + 1;
        }

        arsort($counts);
        return array_slice($counts, 0, $limit, true);
    }

    /**
     * Get unique active users in the last N minutes ⏱️🛰️
     */
    public function getActiveCount(int $minutes = 5): int
    {
        $hits = $this->get();
        if (empty($hits))
            return 0;

        $now = strtotime(\now('H:i:s'));
        $activeIps = [];

        foreach ($hits as $hit) {
            $hitTime = strtotime($hit['time'] ?? '00:00:00');
            // Son N dakika içinde mi?
            if (($now - $hitTime) <= ($minutes * 60)) {
                $ip = $hit['ip'] ?? 'UNKNOWN';
                $activeIps[$ip] = true;
            }
        }

        return count($activeIps);
    }

    /**
     * Alias for get() for enhanced compatibility 🛰️
     */
    public function all(): array
    {
        return $this->get();
    }

    /**
     * Alias for count()
     */
    public function hits(): int
    {
        return $this->count();
    }
}
