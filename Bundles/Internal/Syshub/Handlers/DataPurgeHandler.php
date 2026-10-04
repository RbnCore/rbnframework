<?php

declare(strict_types=1);

namespace Rbn\Framework\Bundles\Internal\Syshub\Handlers;

use Rbn\Framework\Core\Base\BaseComponent;
use Rbn\Framework\Core\System\Paths\Paths;

/**
 * DataPurgeHandler - Storage & Purge Business Logic 🧹🛰️⚓
 * RBN 3.5 Masterpiece Standard.
 * 
 * Orchestrates raw storage operations, stats aggregation, and file deliveries.
 * 
 * --- DEPENDENCY TRACKING (Bilgi ve Takip Amaçlı) ---
 * @property-read \Rbn\Framework\Core\Support\Bridges\Helpers\Library\FormatHelper $format
 */
class DataPurgeHandler extends BaseComponent
{
    /**
     * Get Aggregated Dashboard Statistics 📊
     */
    public function getDashboardStats(?string $projectKey = null): array
    {
        $projectKey = $projectKey ?: active_project_key();
        $storagePath = Paths::project()->storage();
        $allStats = $this->getProviderStats($projectKey);

        $totalFiles = array_sum(array_column($allStats, 'total_files'));
        $totalSizeBytes = array_sum(array_column($allStats, 'total_size'));

        // Disk Usage Intelligence
        $diskTotal = disk_total_space($storagePath);
        $diskFree = disk_free_space($storagePath);
        $diskUsed = $diskTotal - $diskFree;

        return [
            'all' => $allStats,
            'root' => [
                'total_files' => $totalFiles,
                'total_size' => $this->helper('format')->formatFileSize($totalSizeBytes),
            ],
            'disk' => [
                'percentage' => round(($diskUsed / $diskTotal) * 100, 1),
                'used' => $this->helper('format')->formatFileSize($diskUsed)
            ]
        ];
    }

    /**
     * Get Stats from all Storage Providers 🎻
     */
    public function getProviderStats(?string $projectKey = null): array
    {
        $projectKey = $projectKey ?: active_project_key();
        $storage = $this->service('storage');

        $stats = [
            'cache' => $storage->cache()->withProject($projectKey)->getStats(),
            'logs' => $storage->logs()->withProject($projectKey)->getStats(),
            'sessions' => $storage->sessions()->withProject($projectKey)->getStats(),
            'backups' => $storage->backups()->withProject($projectKey)->getStats(),
            'exports' => $storage->exports()->withProject($projectKey)->getStats(),
            'uploads' => $storage->uploads()->withProject($projectKey)->getStats(),
            'view' => $storage->view()->withProject($projectKey)->getStats(), // Key must be 'view' to match SyshubMap
        ];

        // Format sizes autonomously 🎻
        foreach ($stats as $key => &$data) {
            if (isset($data['total_size']) && is_numeric($data['total_size'])) {
                $data['formatted_size'] = $this->helper('format')->formatFileSize((int) $data['total_size']);
            } else {
                $data['formatted_size'] = '0 B';
            }
        }

        return $stats;
    }

    /**
     * Clear Storage Content 🧹
     */
    public function clear(string $type, ?string $resource = null, ?string $projectKey = null): bool
    {
        $projectKey = $projectKey ?: active_project_key();
        $provider = $this->service('storage')->{$type}()->withProject($projectKey);

        if ($resource && method_exists($provider, 'clearRelated')) {
            return $provider->clearRelated($resource);
        }

        return $provider->clearAll();
    }

    /**
     * Bulk Delete Storage Files 🗑️
     */
    public function bulkDelete(string $type, array $ids, ?string $projectKey = null): bool
    {
        $projectKey = $projectKey ?: active_project_key();
        $provider = $this->service('storage')->{$type}()->withProject($projectKey);
        $success = true;

        foreach ($ids as $id) {
            if (!$provider->delete($id)) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Get Standardized File Content 🔍
     */
    public function getFileContent(string $type, string $filename, ?string $projectKey = null): mixed
    {
        $projectKey = $projectKey ?: active_project_key();
        $provider = $this->service('storage')->{$type}()->withProject($projectKey);

        // 🎼 RBN 3.5: Masterpiece Resilient Reading Strategy 🎻🛰️
        // Security Check: Prevent directory traversal or accidental directory reading 🛡️
        $path = Paths::project()->storage($type . '/' . ltrim($filename, '/\\'));
        if (is_dir($path)) {
            return null;
        }

        if (method_exists($provider, 'details')) {
            $content = $provider->details($filename);
        } elseif (method_exists($provider, 'getFileContent')) {
            $content = $provider->getFileContent($filename);
        } else {
            // Manual read via driver for providers without a raw reader
            $content = $this->service('storage')->driver()->read($path, 'raw', true);
        }

        return $content;
    }

    /**
     * Get Log Specific Information 📜
     */
    public function getLogData(string $filename, $content, ?string $projectKey = null): array
    {
        $provider = $this->service('storage')->logs();
        $parsed = $provider->parseLogContent((string) $content);

        return [
            'name' => basename($filename),
            'relative' => $filename,
            'content' => $content,
            'is_json' => $parsed['is_json'],
            'parsed_data' => $parsed['data'],
            'size' => strlen((string) $content),
            'formatted_size' => $this->helper('format')->formatFileSize(strlen((string) $content)),
            'modified' => file_exists(Paths::project()->storage('logs/' . $filename))
                ? filemtime(Paths::project()->storage('logs/' . $filename))
                : time()
        ];
    }
}
