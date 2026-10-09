<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Services\Console\Services;

use Rbn\Framework\Core\Base\Services\BaseService;

/**
 * CronService - Master Orchestration Entry Gate 👨‍🍳🛰️⚡
 * 
 * RBN Framework: Pure, Thin Service Gatekeeper.
 */
class CronService extends BaseService
{
    /**
     * Ana Motor: OS Cron veya CLI tarafından tetiklenen giriş noktası 🕰️🛰️⚓
     */
    public function runMaster(array $options = []): void
    {
        $this->manager('cron')->execute($options);
    }

    /**
     * Belirtilen cron görevini manuel çalıştırır ⚡
     */
    public function run(mixed $job): bool
    {
        $jobId = is_array($job) ? ($job['id'] ?? null) : (is_object($job) ? ($job->id ?? null) : $job);
        if (!$jobId) {
            return false;
        }
        $this->manager('cron')->execute(['job' => $jobId]);
        return true;
    }

    /**
     * Crontab komutunu otonom olarak üretir 🕰️
     */
    public function getCrontabCommand(): string
    {
        $rbnPath = \Rbn\Framework\Core\System\Paths\Paths::frameworkRoot() . DIRECTORY_SEPARATOR . 'rbn';
        return "* * * * * php {$rbnPath} master:run >> /dev/null 2>&1";
    }

    /**
     * Tek seferlik anlık görev tetikleyici ⚡
     */
    public function dispatch(string $taskClass, array $params = [], ?string $customKey = null): bool
    {
        $taskKey = $customKey ?? ('dispatch_' . time());

        $cronRepo = $this->repository('master.cronJob');
        $jobId = $cronRepo ? $cronRepo->createJob([
            'project_key' => $this->projectKey,
            'name'        => "[DISPATCH] " . basename(str_replace('\\', '/', $taskClass)),
            'task_key'    => $taskKey,
            'task_class'  => $taskClass,
            'frequency'   => 0,
            'is_active'   => 1,
            'params'      => !empty($params) ? json_encode($params) : null,
            'next_run_at' => now(),
        ]) : null;

        if (!$jobId) {
            return false;
        }

        $this->manager('cron')->execute(['job' => $jobId]);
        return true;
    }
}
