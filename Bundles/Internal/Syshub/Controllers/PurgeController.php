<?php

declare(strict_types=1);

namespace Rbn\Framework\Bundles\Internal\Syshub\Controllers;

use Rbn\Framework\Core\Base\Attributes\SubModule;
use Rbn\Framework\Bundles\Internal\Syshub\Controllers\SyshubController;
use Rbn\Framework\Bundles\Internal\Syshub\Models\PurgeConfig;
use Rbn\Framework\Bundles\Internal\Syshub\Models\SyshubMap;

/**
 * PurgeController - RBN Framework Purge Controller 🧹🛰️⚓
 * Centralized dashboard for data purging and storage statistics.
 */
#[SubModule(
    entity: 'datapurge',
    service: 'syshub',
    handler: 'purge', // Autonomous Handler Discovery
)]
class PurgeController extends SyshubController
{
    /**
     * Data Purge Dashboard 📊
     */
    public function index(): void
    {
        $projectKey = active_project_key();
        $projectQuery = $projectKey ? '?project=' . urlencode($projectKey) : '';
        $stats = $this->service->purge()->getDashboardStats($projectKey);

        // 🎼 RBN Framework: Autonomous Discovery 🛰️⚓
        // Pull sub-modules directly from the static SyshubMap constant.
        $subModules = SyshubMap::MAP['sub_modules']['datapurge']['sub_modules'] ?? [];

        // Enrich with UI config (colors, etc.)
        foreach ($subModules as $key => &$sub) {
            $config = PurgeConfig::get($key);
            $sub['color'] = $config['bg_color'] ?? '#6366f1';
            $sub['url'] = $this->Route->url("syshub/datapurge/{$key}", 'developer') . $projectQuery;
        }

        $this->render('DataPurge/index', [
            'allStats' => $stats['all'],
            'rootStats' => $stats['root'],
            'diskUsage' => $stats['disk'],
            'subModules' => $subModules,
            'purgeAllUrl' => $this->Route->url('syshub/datapurge/clearAll', 'developer') . $projectQuery
        ]);
    }

    /**
     * List Files for Specific Type 📂
     */
    public function module(string $type): void
    {
        $type = strtolower($type);
        $projectKey = active_project_key();
        $provider = $this->service('storage')->{$type}()->withProject($projectKey);
        $stats = $provider->getStats();
        $files = $stats['files'] ?? [];

        $data = [
            'type' => $type,
            'title' => ucfirst($type),
            'stats' => $stats
        ];

        if ($type === 'logs') {
            $data['categorizedLogs'] = $provider->getCategorizedLogs();
            $data['stats'] = $data['categorizedLogs']; // Sync for view summary cards
            $data['logConfigs'] = $provider->getConfigs(); // Pass configs for row icons/classes
            $currentTab = $this->request->input('tab', 'total');
            $data['currentTab'] = $currentTab;
            $data['logs'] = $data['categorizedLogs'][$currentTab]['files'] ?? [];
            $this->render("DataPurge/Logs/index", $data);
        } else {
            $data['moduleConfig'] = PurgeConfig::get($type) ?? [
                'title' => ucfirst($type),
                'icon' => 'bi-folder',
                'color' => 'primary',
                'description' => 'Sistem depolama birimi.',
                'info_points' => ['📁 Bu dizin altındaki dosyaları yönetebilirsiniz.'],
                'bulk_actions' => ['delete' => 'Dosyaları Sil'],
                'table_header' => 'DOSYA ADI',
                'confirm_text' => 'Seçili tüm dosyalar silinecektir.'
            ];
            $paginator = $this->paginate($files, 12);
            $data['paginator'] = $paginator;
            $data['files'] = $paginator->items();
            $this->render("DataPurge/common/index", $data);
        }
    }

    /**
     * Show/View Storage Content 🔍
     */
    public function show(string $type, $path = null): void
    {
        $type = strtolower($type);
        $projectKey = active_project_key();
        $filename = $path ?? ($this->request->input('file') ?? $this->request->input('path'));

        if (!$filename) {
            $this->Route->alert('error', 'Dosya belirtilmedi.', "datapurge/{$type}" . ($projectKey ? "?project=" . urlencode($projectKey) : ""));
            return;
        }

        $content = $this->service->purge()->getFileContent($type, (string) $filename, $projectKey);

        if ($content === null || $content === false) {
            $this->Route->alert('error', 'Dosya içeriği okunamadı.', "datapurge/{$type}" . ($projectKey ? "?project=" . urlencode($projectKey) : ""));
            return;
        }

        $renderData = [
            'type' => $type,
            'filename' => $filename,
            'content' => $content,
            'moduleConfig' => PurgeConfig::get($type)
        ];

        if ($type === 'logs') {
            $renderData['log'] = $this->service->purge()->getLogData($filename, $content, $projectKey);
            $this->render("DataPurge/Logs/view", $renderData);
        } else {
            $this->render("DataPurge/common/show", $renderData);
        }
    }

    /**
     * Clear Specific Resource or All Module 🧹
     */
    public function clear(string $type): void
    {
        $projectKey = active_project_key();
        $projectQuery = $projectKey ? '?project=' . urlencode($projectKey) : '';
        $resource = $this->request->input('resource');
        $success = $this->service->purge()->clear($type, $resource, $projectKey);

        $message = $resource
            ? "{$resource} verileri temizlendi."
            : ucfirst($type) . " başarıyla temizlendi.";

        $this->handleResult($success, $message, "datapurge/{$type}" . $projectQuery);
    }

    /**
     * Nuclear Purge: Clear EVERYTHING 🚀☢️
     */
    public function clearAll(): void
    {
        $projectKey = active_project_key();
        $projectQuery = $projectKey ? '?project=' . urlencode($projectKey) : '';
        $subModules = SyshubMap::MAP['sub_modules']['datapurge']['sub_modules'] ?? [];
        $success = true;

        foreach (array_keys($subModules) as $type) {
            if (!$this->service->purge()->clear((string) $type, null, $projectKey)) {
                $success = false;
            }
        }

        $this->handleResult($success, "Sistem genel temizliği başarıyla tamamlandı.", "datapurge" . $projectQuery);
    }

    /**
     * Bulk Deletion for Storage Files 🗑️
     */
    public function bulkDelete($target = null, string $method = 'bulkDestroy', $redirect = null): void
    {
        // 🎼 RBN Framework: Parameter Mapping 🛰️⚓
        // Router passes {type} as first arg ($target) and {path} as second arg ($method)
        $type = $target ?? $this->request->input('type');
        $ids = $this->request->input('ids');
        $path = ($method !== 'bulkDestroy') ? $method : null;

        // Handle single deletion from path parameter
        if (empty($ids) && !empty($path)) {
            $ids = [$path];
        }

        if (empty($ids) || !is_array($ids)) {
            $this->handleResult(false, "Silinecek {$type} dosyaları bulunamadı.", "datapurge/{$type}");
            return;
        }

        $success = $this->service->purge()->bulkDelete((string) $type, $ids);

        $this->handleResult($success, "Seçili {$type} dosyaları", "datapurge/{$type}");
    }

    /**
     * Bulk Download for Storage Files 📦
     */
    public function bulkDownload($ids = null, $service = null, string $method = 'downloadBulk'): void
    {
        $type = is_string($ids) ? $ids : $this->request->input('type');
        $actualIds = $this->request->input('ids') ?? (is_array($ids) ? $ids : []);

        if (empty($actualIds)) {
            $this->Route->alert('error', 'İndirilecek dosya seçilmedi.', "datapurge/{$type}");
            return;
        }

        $files = [];
        foreach ($actualIds as $id) {
            $files[] = \Rbn\Framework\Core\System\Paths\Paths::project()->storage($type . '/' . ltrim((string) $id, '/\\'));
        }

        $this->deliverZip($files, "{$type}_export_" . date('Ymd_His') . ".zip");
    }

    /**
     * Single File Download 📥
     */
    public function download($id = null, $service = null, string $method = 'download'): void
    {
        $type = $id ?? $this->request->input('type');
        $filename = $service ?? ($this->request->input('file') ?? $this->request->input('path'));

        if (!$filename) {
            $this->Route->alert('error', 'Dosya belirtilmedi.', "datapurge/{$type}");
            return;
        }

        $path = \Rbn\Framework\Core\System\Paths\Paths::project()->storage($type . '/' . ltrim($filename, '/\\'));

        if (!file_exists($path)) {
            $this->Route->alert('error', 'Dosya bulunamadı.', "datapurge/{$type}");
            return;
        }

        $this->deliverFile($path);
    }
}
