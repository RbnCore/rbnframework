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

        $target = (string) ($params['target'] ?? 'all'); // 'all', 'server', 'projects', 'targets' (yalnız hedefli tablo temizliği)
        $retentionOverrides = $params['retention'] ?? [];

        try {
            $serverResult = ['deleted_logs' => 0, 'deleted_tmp' => 0];
            $projectResult = ['deleted_files' => 0, 'deleted_db_logs' => 0, 'details' => []];
            $targetedResult = ['deleted' => 0, 'rows' => []];

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

            // 3. ADIM: Hedefli tablo temizliği — params.targets'ta yazılan proje·tablolar; başka proje taranmaz 🎯
            if (in_array($target, ['all', 'projects', 'targets'], true) && !empty($params['targets']) && is_array($params['targets'])) {
                $projectJob = $this->job('cleanup.project');
                if ($projectJob) {
                    $targetedResult = $projectJob->cleanTargets($params['targets'], function ($projKey, $table, $deleted, $skipped) use ($taskLog) {
                        if ($taskLog) {
                            $taskLog->step('Hedefli Temizlik', $skipped === null ? "[{$projKey}] {$table} -> {$deleted} satır silindi." : "[{$projKey}] {$table} atlandı: {$skipped}");
                        }
                    });
                }
            }

            $totalDeletedFiles = ($serverResult['deleted_tmp'] ?? 0) + ($serverResult['deleted_lscache'] ?? 0) + ($serverResult['deleted_trash'] ?? 0) + $projectResult['deleted_files'];
            $totalDeletedLogs = $serverResult['deleted_logs'] ?? 0;
            $totalDeletedDbLogs = $projectResult['deleted_db_logs'];

            $summaryMsg = "Akıllı temizlik tamamlandı. Toplam {$projectResult['deleted_files']} proje dosyası, {$totalDeletedLogs} server log, " . ($serverResult['deleted_tmp'] ?? 0) . " tmp, " . ($serverResult['deleted_lscache'] ?? 0) . " lscache, " . ($serverResult['deleted_trash'] ?? 0) . " trash ve {$totalDeletedDbLogs} DB log satırı silindi." . $this->describeTargeted($targetedResult);

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
                'projects' => $projectResult,
                'targets' => $targetedResult
            ];

        } catch (\Throwable $e) {
            $msg = "Otonom temizlik görevinde hata: " . $e->getMessage();
            if ($taskLog) {
                $taskLog->failed($msg);
            }
            return ['success' => false, 'message' => $msg];
        }
    }

    /** Hedefli temizlik sonucunu cron günlüğü için "proje·tablo=silinen" satırına çevirir. */
    private function describeTargeted(array $result): string
    {
        if (empty($result['rows'])) {
            return '';
        }

        $parts = [];
        foreach ($result['rows'] as $row) {
            $parts[] = "{$row['project']}·{$row['table']}=" . ($row['skipped'] === null ? $row['deleted'] : "atlandı ({$row['skipped']})");
        }

        return ' Hedefli tablo temizliği: ' . implode(', ', $parts) . '.';
    }
}
