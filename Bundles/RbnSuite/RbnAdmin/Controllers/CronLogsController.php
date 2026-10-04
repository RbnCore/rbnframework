<?php

declare(strict_types=1);

namespace Rbn\Framework\Bundles\RbnSuite\RbnAdmin\Controllers;

use Rbn\Framework\Core\Base\Attributes\SubModule;

#[SubModule(
    entity: 'cron',
    service: 'cron',
    modal: 'Setting/Partials/modal'
)]
class CronLogsController extends RbnAdminController
{
    /**
     * Zamanlanmış Görevler (Cron) ayarlarını listeler ⏰
     */
    public function cron()
    {
        $projectKey = active_project_key();
        $cronRepo = $this->repository('master.cronJob');
        $rawJobs = $cronRepo ? $cronRepo->getJobsByProject($projectKey) : [];

        $cronResolver = $this->resolver('cron');
        $cronJobs = [];
        foreach ($rawJobs as $job) {
            $jobData = is_object($job) && method_exists($job, 'toArray') ? $job->toArray() : (array) $job;
            $schedule = $cronResolver ? $cronResolver->resolveScheduleParams($jobData) : ['days' => [], 'hours' => []];
            $jobData['selected_days'] = $schedule['days'] ?? [];
            $jobData['hours_string'] = !empty($schedule['hours']) ? implode(', ', $schedule['hours']) : '';
            $cronJobs[] = $jobData;
        }

        return $this->render('Setting/cron', [
            'cronJobs' => $cronJobs,
            'daysOfWeek' => $this->helper('format')->weekdaysMap()
        ]);
    }

    /**
     * Cron log kayıtlarını ve dosyalarını listeler ⏱️
     */
    public function index()
    {
        $activeTab = (string) $this->request->query('tab', 'db');
        if ($activeTab !== 'files') {
            $activeTab = 'db';
        }

        $activeProjectKey = function_exists('active_project_key') ? active_project_key() : 'master';
        $logRepo = $this->repository('project.cronLog');

        // 1. Veritabanı kayıtları
        $allDbLogs = $logRepo ? $logRepo->getLogs(300, $activeProjectKey) : [];
        $totalDbCount = count($allDbLogs);

        // 2. Dosya kayıtları (.jsonl)
        $allFiles = [];
        $logDir = \Rbn\Framework\Core\System\Paths\Paths::project()->root('Storage/logs/cron');
        if (is_dir($logDir)) {
            $rawFiles = glob($logDir . '/*.jsonl');
            if ($rawFiles) {
                usort($rawFiles, function ($a, $b) {
                    return filemtime($b) <=> filemtime($a);
                });

                foreach ($rawFiles as $file) {
                    $filename = basename($file);
                    if (str_contains($filename, '_' . $activeProjectKey . '.jsonl')) {
                        $allFiles[] = [
                            'name' => $filename,
                            'size' => filesize($file),
                            'modified' => filemtime($file)
                        ];
                    }
                }
            }
        }
        $totalFilesCount = count($allFiles);

        $items = ($activeTab === 'files') ? $allFiles : $allDbLogs;

        // Seçilen listeyi sayfala (her sayfada 15 kayıt)
        $paginator = $this->paginate($items, 15);

        return $this->render('Cronlogs/index', [
            'activeTab' => $activeTab,
            'files' => $activeTab === 'files' ? $paginator->items() : [],
            'dbLogs' => $activeTab === 'db' ? $paginator->items() : [],
            'totalDbCount' => $totalDbCount,
            'totalFilesCount' => $totalFilesCount,
            'pager' => $paginator
        ]);
    }

    /**
     * Belirli bir log kaydını veya dosyasını görüntüler 🔍
     */
    public function view()
    {
        $id = $this->request->query('id');
        if (!empty($id) && is_numeric($id)) {
            $logRepo = $this->repository('project.cronLog');
            $dbLog = $logRepo ? $logRepo->getLog((int) $id) : null;

            if (!$dbLog) {
                return $this->Route->alert('error', 'Log kaydı bulunamadı.', 'cronlogs', 'admin');
            }

            $jobTitle = $dbLog['job_name'] ?? ('Görev #' . $dbLog['job_id']);
            $logs = [
                [
                    'timestamp' => $dbLog['started_at'],
                    'level' => $dbLog['status'] === 'success' ? 'INFO' : 'ERROR',
                    'message' => $dbLog['message'] ?: 'Görev başarıyla tamamlandı.',
                    'context' => [
                        'duration' => number_format((float) ($dbLog['duration'] ?? 0), 4) . 's',
                        'task_key' => $dbLog['task_key'] ?? null,
                        'finished_at' => $dbLog['finished_at'] ?? null,
                    ]
                ]
            ];

            return $this->render('Cronlogs/view', [
                'filename' => "DB Log #{$dbLog['id']} - {$jobTitle}",
                'logId' => $dbLog['id'],
                'logs' => $logs,
                'pager' => $this->paginate($logs, 1),
                'size' => strlen((string) ($dbLog['message'] ?: '')),
                'modified' => strtotime($dbLog['finished_at'] ?? 'now')
            ]);
        }

        $filename = (string) $this->request->query('file');
        if (empty($filename) || str_contains($filename, '..') || str_contains($filename, '/') || str_contains($filename, '\\')) {
            return $this->Route->alert('error', 'Geçersiz dosya adı.', 'cronlogs', 'admin');
        }

        $logPath = \Rbn\Framework\Core\System\Paths\Paths::project()->root('Storage/logs/cron/' . $filename);
        if (!file_exists($logPath)) {
            return $this->Route->alert('error', 'Log dosyası bulunamadı.', 'cronlogs', 'admin');
        }

        $logs = [];
        $lines = file($logPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines) {
            foreach ($lines as $line) {
                $data = json_decode($line, true);
                if ($data) {
                    $logs[] = $data;
                }
            }
        }

        // Logları en yeni en üstte olacak şekilde sırala
        usort($logs, function ($a, $b) {
            return strcmp($b['timestamp'] ?? '', $a['timestamp'] ?? '');
        });

        // Sayfalama (her sayfada 50 kayıt)
        $paginator = $this->paginate($logs, 50);

        return $this->render('Cronlogs/view', [
            'filename' => $filename,
            'logId' => $filename,
            'logs' => $paginator->items(),
            'pager' => $paginator,
            'size' => filesize($logPath),
            'modified' => filemtime($logPath)
        ]);
    }

    /**
     * Belirli bir log dosyasını veya DB kaydını siler 🗑️
     */
    public function deleteFile()
    {
        $id = $this->request->input('id');
        if (empty($id)) {
            return $this->response->json(['success' => false, 'message' => 'Geçersiz parametre.']);
        }

        // Eğer ID numerik ise veritabanı kaydı silinecektir
        if (is_numeric($id)) {
            $logRepo = $this->repository('project.cronLog');
            $deleted = $logRepo ? $logRepo->destroy((int) $id) : false;
            if ($deleted) {
                return $this->response->json(['success' => true, 'message' => 'Log kaydı veritabanından başarıyla silindi.']);
            }
            return $this->response->json(['success' => false, 'message' => 'Kayıt bulunamadı.']);
        }

        $filename = (string) $id;
        if (str_contains($filename, '..') || str_contains($filename, '/') || str_contains($filename, '\\')) {
            return $this->response->json(['success' => false, 'message' => 'Geçersiz dosya adı.']);
        }

        $logPath = \Rbn\Framework\Core\System\Paths\Paths::project()->root('Storage/logs/cron/' . $filename);
        if (file_exists($logPath)) {
            @unlink($logPath);
            return $this->response->json(['success' => true, 'message' => $filename . ' dosyası başarıyla silindi.']);
        }

        return $this->response->json(['success' => false, 'message' => 'Dosya bulunamadı.']);
    }

    /**
     * Tüm cron log dosyalarını temizler 🗑️
     */
    public function clear()
    {
        $logDir = \Rbn\Framework\Core\System\Paths\Paths::project()->root('Storage/logs/cron');
        if (is_dir($logDir)) {
            $files = glob($logDir . '/*.jsonl');
            if ($files) {
                foreach ($files as $file) {
                    @unlink($file);
                }
            }
        }

        return $this->response->json(['success' => true, 'message' => 'Tüm cron logları başarıyla temizlendi.']);
    }

    /**
     * Toggle Cron Job Status via AJAX 🕰️
     */
    public function toggleCron(): void
    {
        $id = (int) $this->request->input('id');
        $value = $this->request->input('status') ?? $this->request->input('value');
        $status = ($value == '1' || $value === true || $value == 'true' || $value == 'on') ? 1 : 0;

        $cronRepo = $this->repository('master.cronJob');
        $result = $cronRepo ? $cronRepo->updateJobSchedule($id, ['is_active' => $status]) : false;

        $this->handleResult($result, null, false, 'status');
    }

    /**
     * Save Cron job settings ⏰
     */
    public function saveCron(): void
    {
        $inputs = $this->request->form([
            'cron' => 'nullable|array'
        ]);

        $cronRepo = $this->repository('master.cronJob');
        $success = true;

        if (!empty($inputs['cron']) && $cronRepo) {
            foreach ($inputs['cron'] as $id => $cronData) {
                $result = $cronRepo->updateJobSchedule((int) $id, $cronData);
                if (!$result) {
                    $success = false;
                }
            }
        }

        $this->handleResult($success, 'Zamanlayıcı ayarları', '/cron');
    }
}
