<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Services\Console\Jobs\Cleanup;

use Rbn\Framework\Core\System\Paths\Paths;
use Rbn\Framework\Core\Base\Attributes\Component;

/**
 * ProjectCleanupJob - Proje / Tenant Seviyesi Otonom Temizlik Görevi 🏢🧼
 * 
 * RBN Framework Standard.
 * Multi-tenant projelerin Storage (logs, sessions, cache) dosyalarını ve DB log tablolarını temizler.
 */
#[Component(alias: 'cleanup.project', type: 'job')]
class ProjectCleanupJob extends BaseCleanupHandler
{
    /** @var array Storage alt klasörleri saklama süreleri (Gün) 📁⏳ */
    public const DIRECTORY_RETENTION = [
        'sessions'  => 1, // 1 günden eski oturumlar (Güvenlik & Hız)
        'cache'     => 2, // 2 günden eski önbellekler
        'framework' => 3, // 3 günden eski derlenmiş şablonlar
        'logs'      => 7, // 7 günden eski loglar (Hata analizi için)
    ];

    /** @var array Veritabanı tabloları saklama süreleri (Gün) 🗄️⏳ */
    public const TABLE_RETENTION = [
        'z_sys_rate_limits'  => 1, // 1 gün
        'z_sys_heartbeats'   => 1, // 1 gün
        'z_users_activities' => 3, // 3 gün
        'z_log_crons'        => 7, // 7 gün
    ];

    /** @var array Veritabanında silinecek izin verilen temizlik tabloları (tablo_adı => tarih_sütunu) 🗄️ */
    public const CLEANUP_TABLES = [
        'z_log_crons' => 'created_at',
        'z_users_activities' => 'created_at',
        'z_sys_rate_limits' => 'created_at',
        'z_sys_heartbeats' => 'created_at',
    ];

    /**
     * Belirli bir projeyi veya tüm aktif projeleri temizler
     */
    public function execute(iterable $projects, array $retentionOverrides = [], ?callable $onProjectCleaned = null): array
    {
        $dirRetention = array_merge(self::DIRECTORY_RETENTION, $retentionOverrides['directories'] ?? []);
        $tableRetention = array_merge(self::TABLE_RETENTION, $retentionOverrides['tables'] ?? []);

        $totalDeletedFiles = 0;
        $totalDeletedLogs = 0;
        $details = [];

        foreach ($projects as $project) {
            $projectKey = (string) ($project['project_key'] ?? $project);
            
            // 1. Proje Storage temizliği (Her alt klasör kendi gün süresiyle temizlenir)
            $storagePath = $this->resolveProjectStoragePath($projectKey);
            $projectDeletedFiles = 0;

            if (!empty($storagePath) && is_dir($storagePath)) {
                foreach ($dirRetention as $subDir => $days) {
                    $targetPath = $storagePath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $subDir);
                    $projectDeletedFiles += $this->cleanDirectoryFiles($targetPath, (int) $days);
                }
            }

            // 2. Proje Veritabanı Tablo temizliği (Her tablo kendi gün süresiyle temizlenir)
            $projectDeletedLogs = $this->cleanProjectTables($projectKey, $tableRetention);

            $totalDeletedFiles += $projectDeletedFiles;
            $totalDeletedLogs += $projectDeletedLogs;

            $details[$projectKey] = [
                'files' => $projectDeletedFiles,
                'db_logs' => $projectDeletedLogs
            ];

            if ($onProjectCleaned !== null) {
                $onProjectCleaned($projectKey, $projectDeletedFiles, $projectDeletedLogs);
            }
        }

        return [
            'success' => true,
            'deleted_files' => $totalDeletedFiles,
            'deleted_db_logs' => $totalDeletedLogs,
            'details' => $details
        ];
    }

    /**
     * Projenin Storage dizin yolunu çözer 🗺️
     */
    public function resolveProjectStoragePath(string $projectKey): ?string
    {
        try {
            $customPath = $this->resolveProjectData('custom_path', $projectKey) ?: $projectKey;
            $fullPath = Paths::workspace() . DIRECTORY_SEPARATOR . 'projects' . DIRECTORY_SEPARATOR . $customPath . DIRECTORY_SEPARATOR . 'Storage';
            return is_dir($fullPath) ? $fullPath : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Projenin veritabanında izin verilen temizlik tablolarını (CLEANUP_TABLES) temizler 🧼🗄️
     */
    public function cleanProjectTables(string $projectKey, array $tableRetention): int
    {
        $totalDeletedRows = 0;

        try {
            // [FW-DB-PROFIL] Veritabanı adı artık düz `DB_NAME` yerine
            // `DB_PROFILES[local|production].DB_NAME` içinde olabilir; profil
            // seçimi TEK çözücüde yapılır (eski düz biçim de çalışır).
            $settings = $this->resolveProjectConfig('project-settings', $projectKey);
            $dbName = \Rbn\Framework\Core\System\Config\Engine\Database\ProjectDbProfileResolver::resolve($settings)['DB_NAME'] ?? '';
            if (empty($dbName)) {
                return 0;
            }

            $db = $this->model('masterProjects')->getDb();

            foreach (self::CLEANUP_TABLES as $tableName => $dateColumn) {
                try {
                    $days = (int) ($tableRetention[$tableName] ?? 7);
                    $dateLimit = date('Y-m-d H:i:s', strtotime("-{$days} days"));

                    $tableCheck = $db->raw("SHOW TABLES FROM `{$dbName}` LIKE '{$tableName}'");
                    if (empty($tableCheck)) {
                        continue;
                    }

                    $countRows = $db->raw("SELECT COUNT(*) as cnt FROM `{$dbName}`.`{$tableName}` WHERE `{$dateColumn}` < ?", [$dateLimit]);
                    $countBefore = (int) ($countRows[0]['cnt'] ?? 0);

                    if ($countBefore > 0) {
                        $db->raw("DELETE FROM `{$dbName}`.`{$tableName}` WHERE `{$dateColumn}` < ?", [$dateLimit]);
                        $totalDeletedRows += $countBefore;
                    }
                } catch (\Throwable $e) {
                    // Tablo temizlik hatasını yut ve sonraki tabloya geç
                }
            }
        } catch (\Throwable $e) {
            return 0;
        }

        return $totalDeletedRows;
    }
}
