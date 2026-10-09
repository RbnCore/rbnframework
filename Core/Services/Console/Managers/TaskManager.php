<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Services\Console\Managers;

use Rbn\Framework\Core\Base\Services\BaseManager;
use Rbn\Framework\Core\Support\Exceptions\CronTaskException;

/**
 * TaskManager - RBN Framework Master Task Executor & Telemetry Pipeline 🚀⚡⚓
 * 
 * RBN Framework Framework Standards.
 * @var \Rbn\Framework\Core\Services\Console\Base\AbstractCronTask|object|null $task
 */
class TaskManager extends BaseManager
{
    /**
     * Executes a task within its isolated project context 🚀
     */
    public function execute(string $taskClass, array $params = [], array $options = []): array
    {
        $projectKey = (string) ($params['project_key'] ?? ($options['project'] ?? ($options['project_key'] ?? '')));
        $taskName = basename(str_replace('\\', '/', $taskClass));

        // 🎼 RBN Framework MASTER PIPELINE: Görev Yürütme Motoru (5-Adım Mimarisi) 🚀🛰️⚓
        return $this->runInProjectContext($projectKey, function () use ($taskClass, $taskName, $params, $options, $projectKey) {
            $startedAt = date('Y-m-d H:i:s');
            $startTime = microtime(true);
            $cronLogRepo = $this->repository('project.cronLog');
            $taskLog = $this->service('base.taskLog');

            $mergedParams = is_array($params) ? $params : [];
            if (!empty($options)) {
                $mergedParams = array_merge($mergedParams, $options);
            }

            $jobId = (int) ($mergedParams['job_id'] ?? 0);
            $taskKey = (string) ($mergedParams['task_key'] ?? '');

            // 1. Görev Log Başlangıcı (TaskLogService) 🚀
            if ($taskLog) {
                $taskLog->start($taskName, $projectKey, $taskKey);
            }

            try {
                $task = $this->resolver('task')->resolveTaskInstance($taskClass, $projectKey);
                if (!$task) {
                    throw CronTaskException::invalidTaskClass($taskClass);
                }

                $runResult = $task->run($mergedParams);

                // 3. Statü ve Mesaj Analizi (AbstractCronTask Kontratı: success, skipped, failed) 🎯
                $message = method_exists($task, 'getMessage') ? (string) $task->getMessage() : '';
                $taskStatus = method_exists($task, 'getStatus') ? (string) $task->getStatus() : 'success';

                if ($runResult === false && $taskStatus === 'success') {
                    $status = 'failed';
                } else {
                    $status = in_array($taskStatus, ['success', 'skipped', 'failed'], true) ? $taskStatus : 'success';
                }

                $duration = round(microtime(true) - $startTime, 4);
                $finishedAt = date('Y-m-d H:i:s');

                // 4. TaskLogService ile Sonuç Günlüğü 📝
                if ($taskLog) {
                    if ($status === 'failed') {
                        $taskLog->failed($message);
                    } elseif ($status === 'skipped') {
                        $taskLog->skipped($message);
                    } else {
                        $taskLog->succeeded($message);
                    }
                }

                // 5. DB Kaydı (cron_logs Tablosuna Tek Satır Kayıt & Seyrek Temizlik) 📊
                if ($cronLogRepo) {
                    $cronLogRepo->logExecution([
                        'job_id' => $jobId,
                        'task_key' => $taskKey,
                        'project_key' => $projectKey,
                        'started_at' => $startedAt,
                        'finished_at' => $finishedAt,
                        'duration' => $duration,
                        'status' => $status,
                        'message' => $message
                    ]);

                    // Projenin kendi DB'sindeki eski logları seyrek olarak temizle 🧹
                    if (rand(1, 50) === 1) {
                        $cronLogRepo->cleanupLogs(7);
                    }
                }

                return [
                    'status' => $status,
                    'message' => $message,
                    'duration' => $duration,
                    'started_at' => $startedAt,
                    'finished_at' => $finishedAt
                ];
            } catch (\Throwable $e) {
                $duration = round(microtime(true) - $startTime, 4);
                $finishedAt = date('Y-m-d H:i:s');
                $message = $e->getMessage();
                $status = ($e instanceof CronTaskException) ? $e->getCronStatus() : 'failed';

                if ($taskLog) {
                    if ($status === 'skipped') {
                        $taskLog->skipped($message);
                    } else {
                        $taskLog->failed($message, $e);
                    }
                }

                if ($cronLogRepo) {
                    $cronLogRepo->logExecution([
                        'job_id' => $jobId,
                        'task_key' => $taskKey,
                        'project_key' => $projectKey,
                        'started_at' => $startedAt,
                        'finished_at' => $finishedAt,
                        'duration' => $duration,
                        'status' => $status,
                        'message' => $message
                    ]);
                }

                return [
                    'status' => $status,
                    'message' => $message,
                    'duration' => $duration,
                    'started_at' => $startedAt,
                    'finished_at' => $finishedAt
                ];
            }
        });
    }

    /**
     * Safely queries today's published item count for a task within its isolated project context 🎯
     */
    public function getTodayPublishedCount(string $taskClass, array $params = []): int
    {
        $projectKey = (string) ($params['project_key'] ?? '');
        if (empty($projectKey)) {
            return 0;
        }

        try {
            return (int) $this->runInProjectContext($projectKey, function () use ($taskClass, $params, $projectKey) {
                $taskInstance = $this->resolver('task')->resolveTaskInstance($taskClass, $projectKey);
                if ($taskInstance && method_exists($taskInstance, 'getTodayPublishedCount')) {
                    return (int) $taskInstance->getTodayPublishedCount($params);
                }
                return 0;
            });
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
