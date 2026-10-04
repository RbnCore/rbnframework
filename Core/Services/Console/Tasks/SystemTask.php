<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Services\Console\Tasks;

use Rbn\Framework\Core\Services\Console\Base\AbstractCronTask;

/**
 * SystemTask - Framework System & Maintenance Task Dispatcher 🧼🎻⚓
 * 
 * RBN 3.5 Sovereign Architecture Standard.
 * Handles system-level cleanup and maintenance operations using Jobs.
 */
class SystemTask extends AbstractCronTask
{
    public function run(array $params = []): bool
    {
        $params = array_merge([
            'project_key' => 'rbncore',
            'task_type' => 'global_cleanup',
            'default_days' => [1, 2, 3, 4, 5, 6, 7],
            'default_hours' => [2],
        ], $params);

        // Evrensel Temizlik Görevini ($this->job vasıtasıyla) çöz ve çalıştır 🎯
        $jobInstance = $this->job('cleanup');

        if (!$jobInstance) {
            $this->setMessage("Hata: Temizlik Görevi [cleanup] çözümlenemedi.");
            return false;
        }

        $jobResult = $jobInstance->execute($params);

        if (isset($jobResult['message'])) {
            $this->setMessage((string) $jobResult['message']);
        }

        return (bool) ($jobResult['success'] ?? false);
    }
}
