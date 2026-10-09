<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Services\Console\Jobs;

use Rbn\Framework\Core\Base\BaseComponent;

/**
 * CleanupJob - Master RBN Framework System Cleanup Orchestrator 🧼🛰️⚡
 * 
 * RBN Framework Standard.
 * Server (Workspace) ve Projects (Tenant) katmanlarını akıllı saklama süreleri (retention) ile orkestre eder.
 */
class CleanupJob extends BaseComponent
{
    /**
     * Otomatik Temizlik Orkestrasyonu 🧹
     */
    public function execute(array $params = []): array
    {
        $taskLog = $this->service('base.taskLog');

        // 🛡️ SİBER GÜVENLİK: Sadece ana project yetkili projesi üzerinden çalışabilir
        $currentProjectKey = (string) ($params['project_key'] ?? $this->projectKey ?? 'rbncore');
        if ($currentProjectKey !== 'rbncore') {
            $msg = "SİBER GÜVENLİK: Global temizlik yetkisi bulunmayan proje bağlamında engellendi: [{$currentProjectKey}]";
            if ($taskLog) {
                $taskLog->failed($msg);
            }
            return ['success' => false, 'message' => $msg];
        }

        $target = (string) ($params['target'] ?? 'all'); // 'all', 'server', 'projects'
        $retentionOverrides = $params['retention'] ?? [];

        try {
            $serverResult = ['deleted_logs' => 0, 'deleted_tmp' => 0];
            $projectResult = ['deleted_files' => 0, 'deleted_db_logs' => 0, 'details' => []];

            // 1. ADIM: Server (Workspace) Seviyesi Temizlik 🌐
            if ($target === 'all' || $target === 'server') {
                $serverJob = $this->job('cleanup.server');
                $serverResult = $serverJob ? $serverJob->execute($retentionOverrides['server'] ?? []) : ['deleted_logs' => 0, 'deleted_tmp' => 0, 'deleted_lscache' => 0, 'deleted_trash' => 0];
                if ($taskLog) {
                    $taskLog->step('Server Temizliği', "Workspace logları ({$serverResult['deleted_logs']}), tmp/ ({$serverResult['deleted_tmp']}), lscache ({$serverResult['deleted_lscache']}) ve .trash ({$serverResult['deleted_trash']}) temizlendi.");
                }
            }

            // 2. ADIM: Project (Tenant) Seviyesi Temizlik 🏢
            if ($target === 'all' || $target === 'projects') {
                $projects = $this->model('masterProjects')->query()
                    ->whereIn('status', ['active', 'maintenance'])
                    ->get();

                if (!empty($projects)) {
                    if ($taskLog) {
                        $taskLog->step('Proje Taraması', count($projects) . " adet aktif proje için akıllı temizlik başlatıldı.");
                    }

                    $projectJob = $this->job('cleanup.project');
                    $projectResult = $projectJob ? $projectJob->execute($projects, $retentionOverrides['projects'] ?? [], function ($projKey, $files, $dbLogs) use ($taskLog) {
                        if ($taskLog) {
                            $taskLog->step('Proje Temizlendi', "[{$projKey}] -> {$files} dosya, {$dbLogs} db_log temizlendi.");
                        }
                    }) : ['deleted_files' => 0, 'deleted_db_logs' => 0, 'details' => []];
                }
            }

            $totalDeletedFiles = ($serverResult['deleted_tmp'] ?? 0) + ($serverResult['deleted_lscache'] ?? 0) + ($serverResult['deleted_trash'] ?? 0) + $projectResult['deleted_files'];
            $totalDeletedLogs = $serverResult['deleted_logs'] ?? 0;
            $totalDeletedDbLogs = $projectResult['deleted_db_logs'];

            $summaryMsg = "Akıllı temizlik tamamlandı. Toplam {$projectResult['deleted_files']} proje dosyası, {$totalDeletedLogs} server log, " . ($serverResult['deleted_tmp'] ?? 0) . " tmp, " . ($serverResult['deleted_lscache'] ?? 0) . " lscache, " . ($serverResult['deleted_trash'] ?? 0) . " trash ve {$totalDeletedDbLogs} DB log satırı silindi.";

            if ($taskLog) {
                $taskLog->succeeded($summaryMsg);
            }

            return [
                'success' => true,
                'message' => $summaryMsg,
                'total_files' => $totalDeletedFiles,
                'total_logs' => $totalDeletedLogs,
                'total_db_logs' => $totalDeletedDbLogs,
                'server' => $serverResult,
                'projects' => $projectResult
            ];

        } catch (\Throwable $e) {
            $msg = "Otonom temizlik görevinde hata: " . $e->getMessage();
            if ($taskLog) {
                $taskLog->failed($msg);
            }
            return ['success' => false, 'message' => $msg];
        }
    }
}
