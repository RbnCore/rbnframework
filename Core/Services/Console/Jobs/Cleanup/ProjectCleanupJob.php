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
            $dbName = $this->resolveProjectDbName($projectKey);
            if ($dbName === '') {
                return 0;
            }

            $db = $this->model('masterProjects')->getDb();

            foreach (self::CLEANUP_TABLES as $tableName => $dateColumn) {
                try {
                    $days = (int) ($tableRetention[$tableName] ?? 7);
                    $totalDeletedRows += $this->deleteOlderRows($db, $dbName, $tableName, $dateColumn, $days);
                } catch (\Throwable $e) {
                    // Tablo temizlik hatasını yut ve sonraki tabloya geç
                }
            }
        } catch (\Throwable $e) {
            return 0;
        }

        return $totalDeletedRows;
    }

    /**
     * Hedefli tablo temizliği: yalnız `$targets` içinde yazılan proje ve tablolara gider, başka proje taranmaz.
     * Biçim: `{ "<project_key>": { "<tablo>": { "days": N, "column": "<tarih_sütunu>" } } }`.
     * Ad doğrulaması başarısız ya da projede olmayan tablo/sütun atlanır ve satıra yazılır.
     *
     * @param array<string, mixed> $targets
     * @return array{deleted: int, rows: array<int, array{project: string, table: string, deleted: int, skipped: ?string}>}
     */
    public function cleanTargets(array $targets, ?callable $onTableCleaned = null): array
    {
        $total = 0;
        $rows = [];

        foreach ($targets as $projectKey => $tables) {
            $projectKey = (string) $projectKey;
            $dbName = '';
            $db = null;
            $projectSkip = null;

            if (!$this->isSafeIdentifier($projectKey) || !is_array($tables)) {
                $projectSkip = 'geçersiz proje anahtarı ya da tablo listesi';
            } else {
                try {
                    $dbName = $this->resolveProjectDbName($projectKey);
                    $db = $this->model('masterProjects')->getDb();
                } catch (\Throwable $e) {
                    $dbName = '';
                }
                if (!$this->isSafeIdentifier($dbName)) {
                    $projectSkip = 'proje veritabanı çözülemedi';
                }
            }

            foreach ((is_array($tables) ? $tables : []) as $tableName => $rule) {
                $tableName = (string) $tableName;
                $deleted = 0;
                $skipped = $projectSkip;

                if ($skipped === null) {
                    $days = is_array($rule) ? (int) ($rule['days'] ?? 0) : 0;
                    $column = is_array($rule) ? (string) ($rule['column'] ?? '') : '';

                    if (!$this->isSafeIdentifier($tableName) || !$this->isSafeIdentifier($column) || $days < 1) {
                        $skipped = 'geçersiz tablo/sütun adı ya da gün sayısı';
                    } else {
                        try {
                            $deleted = $this->deleteOlderRows($db, $dbName, $tableName, $column, $days, true);
                        } catch (\Throwable $e) {
                            $skipped = $e->getMessage();
                        }
                    }
                }

                $total += $deleted;
                $rows[] = ['project' => $projectKey, 'table' => $tableName, 'deleted' => $deleted, 'skipped' => $skipped];
                if ($onTableCleaned !== null) {
                    $onTableCleaned($projectKey, $tableName, $deleted, $skipped);
                }
            }
        }

        return ['deleted' => $total, 'rows' => $rows];
    }

    /** Proje DB adını çözer; [FW-DB-PROFIL] profil seçimi TEK çözücüde yapılır. Çözülemezse boş döner. */
    private function resolveProjectDbName(string $projectKey): string
    {
        $settings = $this->resolveProjectConfig('project-settings', $projectKey);
        return (string) (\Rbn\Framework\Core\System\Config\Engine\Database\ProjectDbProfileResolver::resolve($settings)['DB_NAME'] ?? '');
    }

    /** Tablo/sütun/proje adı yalnız [a-z0-9_] olabilir (SQL'e backtick içinde gömülür). */
    private function isSafeIdentifier(string $name): bool
    {
        return $name !== '' && preg_match('/^[a-z0-9_]+$/', $name) === 1;
    }

    /**
     * `{$dbName}.{$table}` içinde `{$column} < şimdi-{$days} gün` satırlarını siler; silinen sayıyı döner.
     * Tablo yoksa 0 döner; `$strict` iken tablo ya da sütun yokluğu hata fırlatır (hedefli yol bunu günlüğe yazar).
     */
    private function deleteOlderRows(object $db, string $dbName, string $table, string $column, int $days, bool $strict = false): int
    {
        $exists = $db->raw('SELECT COUNT(*) AS cnt FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?', [$dbName, $table]);
        if ((int) ($exists[0]['cnt'] ?? 0) === 0) {
            if ($strict) {
                throw new \RuntimeException('tablo projede yok');
            }
            return 0;
        }
        if ($strict) {
            $col = $db->raw('SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?', [$dbName, $table, $column]);
            if ((int) ($col[0]['cnt'] ?? 0) === 0) {
                throw new \RuntimeException('sütun tabloda yok');
            }
        }

        $dateLimit = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        $countRows = $db->raw("SELECT COUNT(*) as cnt FROM `{$dbName}`.`{$table}` WHERE `{$column}` < ?", [$dateLimit]);
        $count = (int) ($countRows[0]['cnt'] ?? 0);

        if ($count > 0) {
            $db->raw("DELETE FROM `{$dbName}`.`{$table}` WHERE `{$column}` < ?", [$dateLimit]);
        }

        return $count;
    }
}
