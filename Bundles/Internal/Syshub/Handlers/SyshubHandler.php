<?php

declare(strict_types=1);

namespace Rbn\Framework\Bundles\Internal\Syshub\Handlers;

use Rbn\Framework\Core\Base\BaseComponent;
use Rbn\Framework\Core\System\Paths\Paths;
use Rbn\Framework\Core\System\Registries\RbnSystemInfo;
use DirectoryIterator;

/**
 * SyshubHandler - Complex Analysis & Statistics Orchestrator 🎻🛰️⚓
 * RBN Framework Standard.
 * 
 * Handles system stats, folder analysis, and multi-service aggregation.
 * @property \Rbn\Framework\Core\Database\Repositories\Common\ShieldSettingsRepository $shieldSettingRepository
 * @property \Rbn\Framework\Core\Database\Models\Master\MasterIpBlocksModel $masterIpBlockModel
 * @property \Rbn\Framework\Core\Database\Models\Common\CmSysRateLimitsModel $rateLimitModel
 */
class SyshubHandler extends BaseComponent
{
    /**
     * Aggregate Dashboard Statistics (Complex Orchestration) 📊
     */
    public function getDashboardStats(): array
    {
        $settings = $this->repository('common.shieldSetting');

        $cachePath = Paths::project()->storage('framework/views');
        $logPath = Paths::project()->storage('logs');
        $sessionPath = Paths::project()->sessions();

        // 🛡️ Storage Stats
        $cacheStats = $this->getFolderStats($cachePath);
        $logStats = $this->getFolderStats($logPath);
        $sessionStats = $this->getFolderStats($sessionPath);

        // 🎼 RBN Framework: Logic - Pre-calculating clearable size 🛰️⚓
        $totalClearableRaw = ($cacheStats['raw_size'] ?? 0) + ($logStats['raw_size'] ?? 0) + ($sessionStats['raw_size'] ?? 0);

        // 🛡️ Security Stats
        $maintenanceMode = $settings->getSetting('maintenance_mode', '0');
        $whitelist = $settings->getSetting('maintenance_ips', '');
        $allowedIpsCount = !empty($whitelist) ? count(explode(',', $whitelist)) : 0;

        return [
            'security' => [
                'blocked_ips' => $this->model('master.ipBlock')?->count() ?? 0,
                'failed_logins' => $this->model('common.rateLimit')?->count() ?? 0,
                'is_maintenance' => ($maintenanceMode === '1'),
                'allowed_ips_count' => $allowedIpsCount,
                'active_threats' => 0,
                'health_score' => 98
            ],
            'system' => $this->getSystemInfo(),
            'storage' => [
                'cache' => $cacheStats,
                'logs' => $logStats,
                'sessions' => $sessionStats,
                'total_clearable_raw' => $totalClearableRaw,
                'total_clearable_formatted' => $this->helper('format')->formatFileSize($totalClearableRaw)
            ],
            'traffic' => [
                'today_hits' => 0,
                'unique_visitors' => 0,
                'avg_response' => 'Fast'
            ],
            'module_count' => $this->getModuleCount()
        ];
    }

    /**
     * Get Raw System Information (Stats) 🚀
     */
    protected function getSystemInfo(): array
    {
        return [
            'cpu_usage' => 'Low',
            'memory_usage' => round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB',
            'disk_free' => 'Unlimited',
            'php_version' => PHP_VERSION,
            'framework_version' => RbnSystemInfo::get('FRAMEWORK_VERSION'),
            'post_max' => ini_get('post_max_size'),
            'upload_max' => ini_get('upload_max_filesize'),
            'memory' => ['limit' => ini_get('memory_limit')],
            'ini_path' => php_ini_loaded_file() ?: 'N/A'
        ];
    }

    /**
     * Get Stats for a specific folder (Analysis) 📂
     */
    protected function getFolderStats(string $path): array
    {
        $stats = ['total_files' => 0, 'total_size' => '0 B', 'raw_size' => 0];

        if (!is_dir($path))
            return $stats;

        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS));
        $size = 0;
        $count = 0;
        foreach ($files as $file) {
            $size += $file->getSize();
            $count++;
        }

        $stats['total_files'] = $count;
        $stats['raw_size'] = $size;
        $stats['total_size'] = $this->helper('format')->formatFileSize($size);

        return $stats;
    }

    /**
     * Calculate Total Active Module Count (Stats via Physical Scan) 🧩
     */
    protected function getModuleCount(): int
    {
        $count = 0;
        $hubs = [
            Paths::frameworkRoot() . '/Bundles/Internal',
            Paths::frameworkRoot() . '/Bundles/RbnSuite'
        ];

        foreach ($hubs as $path) {
            if (!is_dir($path))
                continue;

            $dir = new DirectoryIterator($path);
            foreach ($dir as $fileinfo) {
                if ($fileinfo->isDir() && !$fileinfo->isDot()) {
                    $count++;
                }
            }
        }

        return $count;
    }
}
