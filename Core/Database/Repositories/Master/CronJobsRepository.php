<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Database\Repositories\Master;

use Rbn\Framework\Core\Base\Data\BaseRepository;
use Rbn\Framework\Core\System\Storage\Providers\BootCacheProvider;

/**
 * CronJobsRepository - Master `cron_jobs` Tablosu Data Access & Query Repository 🗄️⚡
 * 
 * RBN 3.5 Masterpiece Standard.
 * Sadece master veritabanındaki `cron_jobs` tablosu üzerinde okuma ve güncelleme yapar.
 * Hiçbir zamanlama veya iş mantığı (business logic) içermez.
 * 
 * @property \Rbn\Framework\Core\Database\Models\Master\MasterCronJobsModel $cronJobModel
 */
class CronJobsRepository extends BaseRepository
{
    /** @var string Target primary model alias */
    protected $targetModel = 'master.cronJob';

    /**
     * Master DB'den süresi en çok geçmiş/en eski zamana sahip TEK bir aktif görevi sorgular (LIMIT 1).
     */
    public function getDueJobFromDb(array $options = []): ?array
    {
        $jobId = !empty($options['job']) ? (int) $options['job'] : (!empty($options['job_id']) ? (int) $options['job_id'] : null);
        $taskKey = $options['task_key'] ?? ($options['task'] ?? null);
        $projectKeyParam = $options['project'] ?? ($options['project_key'] ?? null);

        $query = $this->model('master.cronJob')->query()->where('is_active', '=', 1);

        if ($jobId) {
            $query->where('id', '=', $jobId);
        } elseif ($taskKey) {
            $query->where('task_key', '=', $taskKey);
        } else {
            $now = now();
            $query->whereRaw('(next_run_at IS NULL OR next_run_at <= :now)', ['now' => $now])
                  ->orderBy('next_run_at', 'ASC')
                  ->orderBy('id', 'ASC');
        }

        if (!empty($projectKeyParam)) {
            $query->where('project_key', '=', $projectKeyParam);
        }

        $job = $query->first();
        return $job ? (is_array($job) ? $job : (method_exists($job, 'toArray') ? $job->toArray() : (array) $job)) : null;
    }

    /**
     * ID'ye göre tek bir görevi getirir.
     */
    public function getJobById(int $jobId): ?array
    {
        $job = $this->model('master.cronJob')->find($jobId);
        return $job ? (is_array($job) ? $job : (method_exists($job, 'toArray') ? $job->toArray() : (array) $job)) : null;
    }

    /**
     * Görevin `next_run_at`, `last_run_at`, `params` ve `is_active` durumunu günceller.
     */
    public function updateJobSchedule(int $jobId, array $data): bool
    {
        $updateData = [];

        if (isset($data['last_run_at'])) {
            $updateData['last_run_at'] = $data['last_run_at'];
        }
        if (isset($data['next_run_at'])) {
            $updateData['next_run_at'] = $data['next_run_at'];
        }
        if (isset($data['params'])) {
            $updateData['params'] = is_array($data['params']) ? json_encode($data['params']) : $data['params'];
        }
        if (isset($data['is_active'])) {
            $updateData['is_active'] = (int) $data['is_active'];
        }

        if (empty($updateData)) {
            return false;
        }

        return (bool) $this->model('master.cronJob')->query()
            ->where('id', '=', $jobId)
            ->update($updateData);
    }

    /**
     * Yeni bir cron görevi oluşturur.
     */
    public function createJob(array $data): int|false
    {
        $job = $this->model('master.cronJob')->create($data);
        return $job ? (int) ($job['id'] ?? $job->id ?? 0) : false;
    }

    /**
     * Projeye ait cron görevlerini döner.
     */
    public function getJobsByProject(string $projectKey): array
    {
        $jobs = $this->model('master.cronJob')->query()->where('project_key', '=', $projectKey)->get();
        return $jobs ? $jobs->toArray() : [];
    }

    /**
     * Tüm aktif/pasif cron görevlerini döner 📋
     */
    public function getAllJobs(): array
    {
        $jobs = $this->model('master.cronJob')->query()->get();
        return $jobs ? $jobs->toArray() : [];
    }

    /**
     * Admin Paneli için zamanlama özet verisini hesaplar 📊
     */
    public function getScheduleSummaryData(string $projectKey): array
    {
        $jobs = $this->model('master.cronJob')->query()
            ->where('is_active', '=', 1)
            ->where('project_key', '=', $projectKey)
            ->get();

        $dayNames = $this->helper('format')->weekdaysMap();
        $totalDailyRuns = 0;
        $tasksList = [];

        foreach ($jobs ?? [] as $job) {
            $params = is_string($job['params'] ?? '') ? json_decode($job['params'], true) : ($job['params'] ?? []);
            $days = $params['days'] ?? [1, 2, 3, 4, 5, 6, 7];
            $hours = $params['hour'] ?? ($params['hours'] ?? []);

            if (is_numeric($hours)) {
                $hours = [(int)$hours];
            }

            if (is_array($hours)) {
                sort($hours);
            } else {
                $hours = [];
            }

            $dailyCount = count($hours);
            $totalDailyRuns += $dailyCount;

            $formattedDays = 'Her Gün';
            if (is_array($days) && count($days) < 7) {
                $dayLabels = [];
                foreach ($days as $d) {
                    if (isset($dayNames[$d])) {
                        $dayLabels[] = mb_substr($dayNames[$d], 0, 3, 'UTF-8');
                    }
                }
                $formattedDays = implode(', ', $dayLabels);
            }

            $formattedHours = [];
            foreach ($hours as $h) {
                $formattedHours[] = sprintf('%02d:00', $h);
            }
            $hoursStr = !empty($formattedHours) ? implode(' • ', $formattedHours) : 'Saatlik';

            $tasksList[] = [
                'name' => $job['name'] ?? $job['task_key'],
                'task_key' => $job['task_key'],
                'days' => $formattedDays,
                'hours' => $hoursStr,
                'daily_count' => $dailyCount,
                'last_run_at' => !empty($job['last_run_at']) ? date('d.m.Y H:i', strtotime((string)$job['last_run_at'])) : 'Henüz çalışmadı'
            ];
        }

        return [
            'tasks' => $tasksList,
            'total_tasks' => count($tasksList),
            'total_daily_runs' => $totalDailyRuns
        ];
    }

    /**
     * Sıradaki en yakın görevi tespit edip `.cache/cron_next_expected_run.json` dosyasına yazar.
     */
    public function writeNextExpectedRunCache(): void
    {
        $cacheKey = 'next_expected_run';
        $cachePrefix = 'cron_';

        $nearestJob = $this->model('master.cronJob')->query()
            ->where('is_active', '=', 1)
            ->whereNotNull('next_run_at')
            ->orderBy('next_run_at', 'ASC')
            ->first();

        if ($nearestJob) {
            $jobArr = is_array($nearestJob) ? $nearestJob : (method_exists($nearestJob, 'toArray') ? $nearestJob->toArray() : (array) $nearestJob);
            BootCacheProvider::set($cacheKey, $jobArr, null, $cachePrefix);
        } else {
            BootCacheProvider::delete($cacheKey, null, $cachePrefix);
        }
    }
}
