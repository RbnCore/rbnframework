<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Services\Console\Handlers\Resolvers;

use Rbn\Framework\Core\Base\BaseComponent;
use Rbn\Framework\Core\System\Storage\Providers\BootCacheProvider;

/**
 * CronResolver - Cron Zamanlama Çözümleme Bileşeni 🎯⏰
 * 
 * Sadece Cron zamanlama (scheduling) işlerini yapar:
 * 1. .cache/cron_next_expected_run.json önbellek verisini okuma
 * 2. DB Parametreleri (days ve hours) çözümleme (DB TEK GERÇEK KAYNAKTIR)
 */
class CronResolver extends BaseComponent
{
    /**
     * .cache/cron_next_expected_run.json dosyasından beklenen sıradaki cron görevini çözer.
     * Dosya var ve zamanı gelmişse (veya geçerliyse) önbellekteki görevi döner, aksi halde false döner.
     */
    public function resolveNextExpectedRunCache(): array|false
    {
        $cacheData = BootCacheProvider::get('next_expected_run', null, 'cron_');

        if ($cacheData !== null && isset($cacheData['next_run_at'])) {
            $currentTime = time();
            $expectedTime = strtotime((string) $cacheData['next_run_at']);

            // Zamanı geldiyse veya geçtiyse önbellekteki görevi döner
            if ($currentTime >= $expectedTime) {
                return $cacheData;
            }
        }

        return false;
    }

    /**
     * DB'deki `params` verisinden `days` ve `hour` dizilerini çözer.
     * MİMARİ KURAL: DB TEK GERÇEK KAYNAKTIR.
     * DB'de parametre tanımlanmamışsa TaskResolver aracılığıyla Task dosyasındaki varsayılanlar (default_days / default_hours) fallback olarak devreye girer.
     */
    public function resolveScheduleParams(array|object $job): array
    {
        $jobData = is_object($job) && method_exists($job, 'toArray') ? $job->toArray() : (array) $job;
        $rawParams = $jobData['params'] ?? null;
        $params = [];

        if (!empty($rawParams)) {
            $params = is_array($rawParams) ? $rawParams : (json_decode((string) $rawParams, true) ?: []);
        }

        $days = $params['days'] ?? null;
        $hours = $params['hour'] ?? ($params['hours'] ?? null);

        // 🎯 EMNİYET SUBABI (Fallback): Eğer DB'de `days` veya `hour` boşsa TaskResolver üzerinden varsayılanları çek!
        if (empty($days) || empty($hours)) {
            $taskDefaults = $this->resolver('task')->getTaskClassDefaults($job);

            if (empty($days)) {
                $days = $taskDefaults['default_days'] ?? [1, 2, 3, 4, 5, 6, 7];
            }
            if (empty($hours)) {
                $hours = $taskDefaults['default_hours'] ?? [7, 15, 23];
            }
        }

        if (is_numeric($hours)) {
            $hours = [(int) $hours];
        }

        $days = is_array($days) ? array_map('intval', $days) : [1, 2, 3, 4, 5, 6, 7];
        $hours = is_array($hours) ? array_map('intval', $hours) : [7, 15, 23];

        sort($hours);
        sort($days);

        return [
            'days' => $days,
            'hours' => $hours,
            'params' => $params
        ];
    }
}
