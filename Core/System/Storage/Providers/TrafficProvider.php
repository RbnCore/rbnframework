<?php

namespace Rbn\Framework\Core\System\Storage\Providers;

use Rbn\Framework\Core\System\Storage\Base\BaseStorageProvider;
use Rbn\Framework\Core\System\Paths\Paths;
use Rbn\Framework\Core\System\Storage\Constants\TrafficConstants;
use Rbn\Framework\Core\Base\Services\BaseService;
use Rbn\Framework\Core\System\Discovery\Clusters\Logic\Definition\Definition;
use Rbn\Framework\Core\Support\Definitions\Route\RouteBlueprint;

/**
 * TrafficProvider - Pulse Korumalı Aylık Konsolide & Tekil IP Trafik Kayıt Birimi 🧬
 * 
 * RBN 3.5 Sovereign Standard.
 * Her ay için TEK BİR 'pulse_' ön ekli JSON dosyası tutar (pulse_YYYY-MM_projectKey.json).
 * 'pulse_' ön eki sayesinde sistemdeki geçici temizlik görevleri bu dosyalara dokunmaz.
 * Günlük bazda aynı IP'yi sadece 1 defa kaydeder (Tekil Ziyaretçi / Unique IP).
 * Bot trafiği ile gerçek insan trafiğini tam ayrıştırır.
 */
class TrafficProvider extends BaseStorageProvider
{
    protected string $storageName = 'traffic';
    protected bool $encrypted = false;
    protected string $format = 'json';
    public ?string $projectKey = null;

    protected function getStorageDir(?string $projectKey = null): string
    {
        $targetKey = $projectKey ?: ($this->projectKey ?: (function_exists('project_key') ? project_key() : null));

        if ($targetKey) {
            $customPath = $this->resolveProjectData('custom_path', $targetKey);
            if ($customPath) {
                return Paths::workspace() . "/projects/{$customPath}/Storage/logs/traffic";
            }
        }

        return Paths::project()->logs('traffic');
    }

    /**
     * Get monthly storage file path with 'pulse_' prefix 📂
     * Format: Storage/logs/traffic/pulse_YYYY-MM_{projectKey}.json
     */
    public function getMonthlyFilePath(string $yearMonth): string
    {
        $projectKey = $this->projectKey ?: (function_exists('project_key') ? project_key() : 'default');
        $dir = $this->getStorageDir($projectKey);

        return "{$dir}/pulse_{$yearMonth}_{$projectKey}.json";
    }

    /**
     * Read monthly JSON data 📖
     */
    public function getMonthlyData(string $yearMonth): array
    {
        $path = $this->getMonthlyFilePath($yearMonth);
        $data = $this->driver->read($path, 'json');
        if (empty($data)) {
            return [
                'month' => $yearMonth,
                'project_key' => $this->projectKey ?: (project_key() ?: 'default'),
                'summary' => [
                    'total_hits' => 0,
                    'human_hits' => 0,
                    'bot_hits' => 0,
                    'devices' => ['PC' => 0, 'Mobile' => 0, 'Tablet' => 0],
                    'locations' => [],
                    'bots_summary' => []
                ],
                'days' => []
            ];
        }

        if (!isset($data['summary'])) {
            $data['summary'] = [
                'total_hits' => 0,
                'human_hits' => 0,
                'bot_hits' => 0,
                'devices' => ['PC' => 0, 'Mobile' => 0, 'Tablet' => 0],
                'locations' => [],
                'bots_summary' => []
            ];
        }

        return $data;
    }

    /**
     * Write monthly JSON data 💾
     */
    public function saveMonthlyData(string $yearMonth, array $data): bool
    {
        $path = $this->getMonthlyFilePath($yearMonth);
        return $this->driver->write($path, $data, 'json');
    }

    /**
     * Hit Kaydet (Monthly Aggregation & Daily Unique IP Deduplication) 📌
     */
    public function record(array $data = []): bool
    {
        $requestUrl = $_SERVER['REQUEST_URI'] ?? '/';
        $pathOnly = parse_url($requestUrl, PHP_URL_PATH) ?? '/';
        $cleanPath = ltrim($pathOnly, '/');
        $firstSegment = explode('/', $cleanPath)[0] ?? '';

        $dashPrefix = Definition::get('route', 'DASHBOARD_PREFIX') ?? 'rbn';
        $authRoots = Definition::get('route', 'AUTH_ROOTS') ?? RouteBlueprint::AUTH_ROOTS;

        // 🛡️ 1. SSoT Auth & Dashboard Filtresi ✋
        if (
            $firstSegment === $dashPrefix ||
            $firstSegment === 'api' ||
            in_array($firstSegment, $authRoots, true)
        ) {
            return true;
        }

        // 🤖 2. SSoT RobotsConfig & RobotsResolver Filtresi (Robots.config ve sistemdeki tüm yasaklı yollar) ✋
        $robotsResolver = $this->resolver('robotsResolver');
        if ($robotsResolver && $robotsResolver->isPathRestricted($pathOnly)) {
            return true;
        }

        $today = date('Y-m-d');
        $yearMonth = date('Y-m');
        $time = date('H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';

        // 1. Context Resolution 🛰️
        $referer = $_SERVER['HTTP_REFERER'] ?? 'Direct';
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'tr', 0, 2);
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN';

        $session = BaseService::get()?->session();
        $userId = $session ? $session->get('user_id') : null;
        if (is_object($userId) || is_array($userId)) {
            $userId = null;
        }

        // 2. Bot Tespiti & Lokasyon Çözümleme
        $botName = $this->detectBot($ua);
        $isBot = !empty($botName);
        if ($isBot) {
            $location = 'Bot (' . $botName . ')';
        } else {
            $location = $this->getIpLocation($ip);
        }

        $device = 'PC';
        $uaLower = strtolower($ua);
        if (str_contains($uaLower, 'mobile') || str_contains($uaLower, 'android') || str_contains($uaLower, 'iphone')) {
            $device = 'Mobile';
        } elseif (str_contains($uaLower, 'tablet') || str_contains($uaLower, 'ipad')) {
            $device = 'Tablet';
        }

        // 3. Aylık JSON Verisini Oku
        $monthData = $this->getMonthlyData($yearMonth);
        if (!isset($monthData['days'][$today])) {
            $monthData['days'][$today] = [
                'total_hits' => 0,
                'human_hits' => 0,
                'bot_hits' => 0,
                'devices' => ['PC' => 0, 'Mobile' => 0, 'Tablet' => 0],
                'locations' => [],
                'bots_summary' => [],
                'top_pages' => [],
                'ip_log' => []
            ];
        }

        $dayRef = &$monthData['days'][$today];
        $requestUrl = $_SERVER['REQUEST_URI'] ?? '/';

        // 4. Genel Sayfa Gösterim Sayacı (En çok ziyaret edilen sayfalar) 📈
        if (!isset($dayRef['top_pages'])) {
            $dayRef['top_pages'] = [];
        }
        $dayRef['top_pages'][$requestUrl] = ($dayRef['top_pages'][$requestUrl] ?? 0) + 1;

        // 5. IP bazlı Kontrol & Gezinti Kaydı 🧭
        if (isset($dayRef['ip_log'][$ip])) {
            // Zaten bugün geldi, tekil hit artırma. Sadece gezdiği sayfayı ve son hareket zamanını güncelle!
            $dayRef['ip_log'][$ip]['last_time'] = $time;
            $dayRef['ip_log'][$ip]['page_views'] = ($dayRef['ip_log'][$ip]['page_views'] ?? 1) + 1;

            if (!isset($dayRef['ip_log'][$ip]['visited_pages'])) {
                $dayRef['ip_log'][$ip]['visited_pages'] = [$dayRef['ip_log'][$ip]['url'] ?? '/'];
            }
            if (!in_array($requestUrl, $dayRef['ip_log'][$ip]['visited_pages'])) {
                $dayRef['ip_log'][$ip]['visited_pages'][] = $requestUrl;
            }

            return $this->saveMonthlyData($yearMonth, $monthData);
        }

        // 6. İlk Kez Gelen Tekil Ziyaret Kaydı (Increment Unique Visitor Counters) 🎯
        $dayRef['total_hits']++;
        $monthData['summary']['total_hits'] = ($monthData['summary']['total_hits'] ?? 0) + 1;

        if ($isBot) {
            $dayRef['bot_hits']++;
            $dayRef['bots_summary'][$botName] = ($dayRef['bots_summary'][$botName] ?? 0) + 1;

            $monthData['summary']['bot_hits'] = ($monthData['summary']['bot_hits'] ?? 0) + 1;
            $monthData['summary']['bots_summary'][$botName] = ($monthData['summary']['bots_summary'][$botName] ?? 0) + 1;
        } else {
            $dayRef['human_hits']++;
            $dayRef['devices'][$device] = ($dayRef['devices'][$device] ?? 0) + 1;

            $monthData['summary']['human_hits'] = ($monthData['summary']['human_hits'] ?? 0) + 1;
            $monthData['summary']['devices'][$device] = ($monthData['summary']['devices'][$device] ?? 0) + 1;
        }

        $dayRef['locations'][$location] = ($dayRef['locations'][$location] ?? 0) + 1;
        $monthData['summary']['locations'][$location] = ($monthData['summary']['locations'][$location] ?? 0) + 1;

        $dayRef['ip_log'][$ip] = [
            'time' => $time,
            'last_time' => $time,
            'url' => $requestUrl,
            'location' => $location,
            'is_bot' => $isBot,
            'bot' => $botName,
            'ua' => $ua,
            'referer' => $referer,
            'page_views' => 1,
            'visited_pages' => [$requestUrl]
        ];

        // 7. Kaydet (Atomic Save)
        return $this->saveMonthlyData($yearMonth, $monthData);
    }

    /**
     * Get hits for a specific date key (e.g. 'date_2026-07-30') 🔍
     */
    public function get(string $key, $default = null): mixed
    {
        if (str_starts_with($key, 'date_')) {
            $dateStr = str_replace('date_', '', $key);
            $yearMonth = substr($dateStr, 0, 7);

            $monthData = $this->getMonthlyData($yearMonth);
            if (isset($monthData['days'][$dateStr]['ip_log'])) {
                $hits = [];
                foreach ($monthData['days'][$dateStr]['ip_log'] as $ip => $log) {
                    $hits[] = array_merge([
                        'ip' => $ip,
                        'date' => $dateStr
                    ], $log);
                }
                return $hits;
            }
            return [];
        }

        return parent::get($key, $default);
    }

    /**
     * User-Agent üzerinden arama motoru / crawler botu tespit eder 🤖
     */
    private function detectBot(string $userAgent): ?string
    {
        if (empty($userAgent)) {
            return null;
        }

        $ua = strtolower($userAgent);

        foreach (TrafficConstants::DETECTABLE_BOTS as $key => $name) {
            if (str_contains($ua, $key)) {
                return $name;
            }
        }

        return null;
    }

    /**
     * IP adresinden lokasyon bilgisini çeker 🗺️
     */
    private function getIpLocation(string $ip): string
    {
        if ((function_exists('is_local') && \is_local()) || $ip === '127.0.0.1' || $ip === '::1') {
            return 'Localhost';
        }

        $geoIP = $this->handler('geoIP');
        if ($geoIP) {
            return $geoIP->getCountryCode($ip);
        }

        return 'XX';
    }

    /**
     * Mevcut kayıt tarihlerini aylık JSON dosyalarından tarar 📅
     */
    public function listDates(): array
    {
        $dir = str_replace('\\', '/', $this->getStorageDir());
        $projectKey = $this->projectKey ?: (project_key() ?: 'default');
        
        $files = glob(rtrim($dir, '/') . '/pulse_*_' . $projectKey . '.json') ?: [];
        $dates = [];

        if (!empty($files)) {
            foreach ($files as $file) {
                if (basename($file) === "pulse_{$projectKey}.json") {
                    continue;
                }
                $monthData = $this->driver->read($file, 'json');
                if (!empty($monthData['days'])) {
                    foreach (array_keys($monthData['days']) as $dateStr) {
                        $dates[] = $dateStr;
                    }
                }
            }
        }

        rsort($dates);
        return array_values(array_unique($dates));
    }

    /**
     * Eski Logları Temizle (Auto-Rotation) 🪒✨
     */
    public function purgeOldLogs(int $keepMonths = 12): int
    {
        $dir = str_replace('\\', '/', $this->getStorageDir());
        $projectKey = $this->projectKey ?: (project_key() ?: 'default');
        $deletedCount = 0;

        // 1. Eski legacy pulse_{projectKey}.json dosyasını temizle
        $singlePulse = rtrim($dir, '/') . "/pulse_{$projectKey}.json";
        if (file_exists($singlePulse)) {
            @unlink($singlePulse);
            $deletedCount++;
        }

        // 2. 12 aydan eski pulse_YYYY-MM arşiv dosyalarını temizle
        $thresholdMonth = date('Y-m', strtotime("-{$keepMonths} months"));
        $monthlyFiles = glob(rtrim($dir, '/') . '/pulse_*_' . $projectKey . '.json');
        if (!empty($monthlyFiles)) {
            foreach ($monthlyFiles as $file) {
                $name = basename($file);
                if (preg_match('/pulse_(\d{4}-\d{2})_/', $name, $matches)) {
                    $fileMonth = $matches[1];
                    if ($fileMonth < $thresholdMonth) {
                        @unlink($file);
                        $deletedCount++;
                    }
                }
            }
        }

        return $deletedCount;
    }
}
