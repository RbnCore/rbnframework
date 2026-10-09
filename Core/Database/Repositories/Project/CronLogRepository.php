<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Database\Repositories\Project;

use Rbn\Framework\Core\Base\Data\BaseRepository;
use Rbn\Framework\Core\System\Config\Definitions\DbProfiles\MasterDbData;
use Rbn\Framework\Core\System\Config\Engine\Database\DbProfileResolver;

/**
 * CronLogRepository - Execution History & Logging Repository 📜📑⚓
 * 
 * RBN Framework: Standard.
 * Manages project cron execution logging and history retrieval.
 * 
 * @property \Rbn\Framework\Core\Database\Models\Project\CronLogsModel $cronLogsModel
 */
class CronLogRepository extends BaseRepository
{
    /** @var string Target primary model alias */
    protected $targetModel = 'project.cronLog';

    /**
     * Log execution result to DB. 🖋️
     */
    public function logExecution(array $data): bool
    {
        try {
            // [FW-ALTYAPI-3 / H · G4] Yazma kapsamı AÇIKÇA verilir: görev
            // `runInProjectContext()` içinde koşsa bile `project_key` kaydı
            // görevin hedefi olur (B-20: kullanıcı/çağıran değeri ezilir).
            $model = $this->model('project.cronLog');
            if (!empty($data['project_key']) && method_exists($model, 'withProjectScope')) {
                $model = $model->withProjectScope((string) $data['project_key']);
            }
            unset($data['project_key']);
            return $model->create($data) !== false;
        } catch (\Throwable $e) {
            $this->storage->logs()->channel('cron')->error("[CronLogRepository] Log veritabanına yazılırken hata oluştu: " . $e->getMessage(), [
                'job_id' => $data['job_id'] ?? null,
                'project_key' => $data['project_key'] ?? null
            ]);
            return false;
        }
    }

    /**
     * Get execution logs with job names. 🕰️
     */
    public function getLogs(int $limit = 100, ?string $projectKey = null): array
    {
        $masterDb = DbProfileResolver::databaseName(MasterDbData::class);
        $tableName = "`{$masterDb}`.`cron_jobs`";

        // [FW-ALTYAPI-3 / H · G4] `CronLogsModel` artık `scoped = true`.
        // Açık anahtar `withProjectScope()` ile verilir; elle
        // `where('z_log_crons.project_key', ...)` ikinci süzgeçti.
        //
        // NOT (JOIN): `master.cron_jobs` tablosunda da `project_key` kolonu VAR
        // (ölçüldü). Kapsam süzgeci artık `z_log_crons.project_key` ile
        // NİTELİKLİ yazıldığı için MySQL 1052 (ambiguous) vermez.
        $projectKey = $projectKey ?: (function_exists('active_project_key') ? active_project_key() : null);
        $model = $this->model('project.cronLog');
        if (!empty($projectKey) && method_exists($model, 'withProjectScope')) {
            $model = $model->withProjectScope((string) $projectKey);
        }

        $query = $model->query()
            ->select("z_log_crons.*, `{$masterDb}`.`cron_jobs`.`name` as job_name, `{$masterDb}`.`cron_jobs`.`task_key` as task_key")
            ->join($tableName, "`{$masterDb}`.`cron_jobs`.`id`", '=', 'z_log_crons.job_id', 'LEFT');

        $logs = $query->orderBy('z_log_crons.started_at', 'DESC')
            ->limit($limit)
            ->get();

        $result = [];
        foreach ($logs as $log) {
            $result[] = is_array($log) ? $log : (method_exists($log, 'toArray') ? $log->toArray() : (array) $log);
        }

        return $result;
    }

    /**
     * Get a single log by ID 🎯
     */
    public function getLog(int $id): ?array
    {
        $masterDb = DbProfileResolver::databaseName(MasterDbData::class);
        $tableName = "`{$masterDb}`.`cron_jobs`";

        $log = $this->model('project.cronLog')->query()
            ->select("z_log_crons.*, `{$masterDb}`.`cron_jobs`.`name` as job_name, `{$masterDb}`.`cron_jobs`.`task_key` as task_key")
            ->join($tableName, "`{$masterDb}`.`cron_jobs`.`id`", '=', 'z_log_crons.job_id', 'LEFT')
            ->where('z_log_crons.id', $id)
            ->first();

        return $log ? (is_array($log) ? $log : (method_exists($log, 'toArray') ? $log->toArray() : (array) $log)) : null;
    }

    /**
     * Delete a single log by ID 🗑️
     */
    public function deleteLog(int $id): bool
    {
        return (bool) $this->model('project.cronLog')->where('id', $id)->delete();
    }

    /**
     * Belirtilen günden eski cron loglarını temizler (Varsayılan 7 gün) 🧹
     */
    public function cleanupLogs(int $days = 7): int
    {
        try {
            $dateLimit = date('Y-m-d H:i:s', strtotime("-{$days} days"));
            return (int) $this->model('project.cronLog')
                ->where('started_at', '<', $dateLimit)
                ->delete();
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
