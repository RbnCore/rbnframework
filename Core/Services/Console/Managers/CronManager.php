<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Services\Console\Managers;

use Rbn\Framework\Core\Base\Services\BaseManager;
use Rbn\Framework\Core\Services\Console\Base\ConsoleStyle;
use Rbn\Framework\Core\Services\Console\Handlers\CronNotificationHandler;

/**
 * CronManager - RBN Framework Master Pipeline Orchestrator (Şef) 👨‍🍳🛰️⚡
 * 
 * Konum: E:\localhost\rbnframework\Core\Services\Console\Managers\CronManager.php
 * 
 * MİMARİ KURAL:
 * 1. Tüm zamanlama, gün, saat ve kota kontrolleri TEK MERKEZDE ($this) yapılır.
 * 2. Görev alt katmanlarda (Builder/AutoTaskManager) saçma sapan kontrollerle yarıda KESİLEMEZ.
 * 3. DB tek gerçek kaynaktır.
 */
class CronManager extends BaseManager
{
    /**
     * Ana Pipeline Çalıştırma Metodu
     */
    public function execute(array $options = []): void
    {
        $this->logWorkspace('cron')->info('=== [CronManager Pipeline] Akış Başladı ===');

        // =========================================================================
        // ADIM 1: GÖREV TESPİTİ (Önce Cache, Yoksa Provider ile DB Sorgusu)
        // =========================================================================
        $job = null;

        // 1.1: Özel Parametre Kontrolü (--task=... veya --job=... geçilmişse Cache Atlanır) 🚀
        $hasSpecificTaskOption = !empty($options['job']) || !empty($options['job_id']) || !empty($options['task']) || !empty($options['task_key']);

        if (!$hasSpecificTaskOption) {
            $cachedJob = $this->resolver('cron')->resolveNextExpectedRunCache();
            if ($cachedJob !== false) {
                $job = $cachedJob;
                $this->logWorkspace('cron')->info('[CronManager] [Adım 1] Görev Cache Üzerinden Okundu.');
            }
        }

        if (empty($job)) {
            // 1.2: Cache Yoksa veya Özel İstenmişse DB'den En Eski Zamanı Gelmiş TEK Görev Çekilir
            $job = $this->repository('master.cronJob')->getDueJobFromDb($options);
            $this->logWorkspace('cron')->info('[CronManager] [Adım 1] Görev CronJobsProvider Üzerinden DB\'den Çekildi.');
        }

        if (empty($job)) {
            ConsoleStyle::info("Şu an zamanı gelmiş bekleyen aktif görev bulunamadı.");
            $this->logWorkspace('cron')->info('[CronManager] Zamanı gelmiş aktif görev bulunamadı. Akış sonlandırıldı.');
            return;
        }

        $jobId = (int) $job['id'];
        $taskKey = $job['task_key'] ?? '';
        $projectKey = $job['project_key'] ?? '';

        ConsoleStyle::info("[Adım 1] Görev Tespit Edildi: ID={$jobId}, Proje={$projectKey}, Görev={$taskKey}");
        $this->logWorkspace('cron')->withProject($projectKey)->info("[CronManager] [Adım 1] Görev Çözümlendi: ID={$jobId}, TaskKey={$taskKey}, Project={$projectKey}");

        // Proje ve Dosya Doğrulaması
        $taskClass = $job['task_class'] ?? $job['task_class?'] ?? '';
        if (!$this->resolver('task')->resolveTaskFileExists($taskClass, $projectKey)) {
            ConsoleStyle::error("Görev dosyası bulunamadı ({$taskClass}). Görev pasife alınıyor.");
            $this->logWorkspace('cron')->withProject($projectKey)->error("[CronManager] Görev dosyası bulunamadı ({$taskClass}). Görev pasife alınıyor.");
            $this->repository('master.cronJob')->updateJobSchedule($jobId, ['is_active' => 0]);
            
            // Hatalı/olmayan görevi önbellekten düşür ve sıradaki geçerli görevi yaz ⚡
            $this->repository('master.cronJob')->writeNextExpectedRunCache();
            return;
        }

        // DB Parametrelerini Çöz (DB TEK GERÇEK KAYNAKTIR)
        $scheduleParams = $this->resolver('cron')->resolveScheduleParams($job);
        $allowedDays = $scheduleParams['days'];
        $targetHours = $scheduleParams['hours'];
        $everyMinutes = (int) ($scheduleParams['every_minutes'] ?? 0);
        $rawParams = $scheduleParams['params'];

        // =========================================================================
        // ADIM 2: SCHEDULER - Gün, Saat ve Kota Uygunluk Doğrulaması 📅
        // =========================================================================
        $isForceOrBypass = !empty($options['force'])
            || !empty($options['bypass_today_check'])
            || !empty($options['bypass_time'])
            || !empty($rawParams['force'])
            || !empty($rawParams['bypass_today_check']);

        if ($isForceOrBypass) {
            $this->logWorkspace('cron')->withProject($projectKey)->info("[CronManager] [Adım 2] Force/Bypass Modu Algılandı. Gün, Saat ve Kota Kontrolleri Atlandı. Görev Doğrudan Çalıştırılıyor...");
        } else {
            $jobParams = is_array($rawParams) ? $rawParams : (json_decode((string) $rawParams, true) ?: []);
            $jobParams['project_key'] = $projectKey;

            $todayPublishedCount = $this->manager('task')->getTodayPublishedCount($taskClass, $jobParams);
            $dueCheck = $this->handler('cronScheduler')->isRunDue($allowedDays, $targetHours, $todayPublishedCount);

            if (!$dueCheck['is_due']) {
                $nextRunAt = $this->handler('cronScheduler')->calculateNextRunTime($allowedDays, $targetHours, $everyMinutes);
                $this->repository('master.cronJob')->updateJobSchedule($jobId, [
                    'next_run_at' => $nextRunAt
                ]);

                // Sıradaki en yakın işi diske önbellek olarak tazele ⚡
                $this->repository('master.cronJob')->writeNextExpectedRunCache();

                ConsoleStyle::warning("[Adım 2] Görev Ertelendi: {$dueCheck['reason']} -> Sıradaki: {$nextRunAt}");
                $this->logWorkspace('cron')->withProject($projectKey)->info("[CronManager] [Adım 2] Görev İptal Edildi ({$dueCheck['reason']}). Bir sonraki çalışma zamanına ({$nextRunAt}) ertelendi ve önbellek tazelendi.");
                return;
            }

            ConsoleStyle::success("[Adım 2] Onay Verildi: {$dueCheck['reason']}");
            $this->logWorkspace('cron')->withProject($projectKey)->info("[CronManager] [Adım 2] Onay Verildi: {$dueCheck['reason']}. Görev Çalıştırılıyor...");
        }

        // =========================================================================
        // ADIM 3: EXECUTER - Görevi TaskManager'a Pasla ve Yürüt 🚀
        // =========================================================================
        $startedAt = date('Y-m-d H:i:s');
        $startTime = microtime(true);

        $jobParams = is_array($rawParams) ? $rawParams : (json_decode((string) $rawParams, true) ?: []);
        $jobParams['job_id'] = $jobId;
        $jobParams['task_key'] = $taskKey;
        $jobParams['project_key'] = $projectKey;
        $jobParams['name'] = $job['name'] ?? '';

        $executionResult = $this->manager('task')->execute($taskClass, $jobParams, $options);

        $status = $executionResult['status'] ?? 'failed';
        $message = $executionResult['message'] ?? '';
        $isSuccess = ($status === 'success' || $status === 'skipped' || $status === 'published');

        if ($isSuccess) {
            ConsoleStyle::success("[Adım 3] Görev Tamamlandı [{$status}]: {$message}");
        } else {
            ConsoleStyle::error("[Adım 3] Görev Başarısız [{$status}]: {$message}");
        }

        $this->logWorkspace('cron')->withProject($projectKey)->info("[CronManager] [Adım 3] TaskManager Tarafından Görev Yürütüldü. Sonuç: {$status}, Mesaj: {$message}");

        // =========================================================================
        // ADIM 4: FINALIZER - DB Güncelleme, Hata Yönetimi ve Önbellek Yazımı
        // =========================================================================
        $isActive = 1;
        $nextRunAt = null;

        if ($isSuccess) {
            // Başarılı veya Skipped (Aday bulunamadı) olursa hata sayacını temizle
            if (isset($rawParams['consecutive_failures'])) {
                unset($rawParams['consecutive_failures']);
            }
            // DB'deki days ve hour parametrelerine göre bir sonraki KESİN hedef zamanı hesapla
            $calculatedNext = $this->handler('cronScheduler')->calculateNextRunTime($allowedDays, $targetHours, $everyMinutes);
            
            // Güvenlik Kilidi: Hesaplanan tarih ŞU ANDAN KESİNLİKLE İLERİDE OLMALIDIR 🛡️
            if (strtotime($calculatedNext) <= time()) {
                $frequency = max(15, (int) ($job['frequency'] ?? 60));
                $nextRunAt = date('Y-m-d H:i:00', strtotime("+{$frequency} minutes"));
            } else {
                $nextRunAt = $calculatedNext;
            }
        } else {
            // Başarısız olursa hata sayacını arttır
            $failures = ($rawParams['consecutive_failures'] ?? 0) + 1;
            $rawParams['consecutive_failures'] = $failures;

            if ($failures >= 3) {
                $isActive = 0;
                $nextRunAt = null; // Pasife alındı, ertelemeye gerek yok!
                $this->logWorkspace('cron')->withProject($projectKey)->error("[CronManager] Job ID {$jobId} üst üste 3 kez hata aldığı için otomatik PASİFE (is_active=0) alındı.");
            } else {
                // 3 hatadan azsa frekansı kadar (minimum 15 dk) ertele ve tekrar dene
                $frequency = max(15, (int) ($job['frequency'] ?? 60));
                $nextRunAt = date('Y-m-d H:i:00', strtotime("+{$frequency} minutes"));
            }
        }

        $updatedParams = !empty($rawParams) ? json_encode($rawParams) : null;

        // DB Güncelle
        $this->repository('master.cronJob')->updateJobSchedule($jobId, [
            'last_run_at' => now(),
            'next_run_at' => $nextRunAt,
            'params' => $updatedParams,
            'is_active' => $isActive
        ]);

        // Sıradaki işi diske önbellek olarak yaz
        $this->repository('master.cronJob')->writeNextExpectedRunCache();

        $this->logWorkspace('cron')->withProject($projectKey)->info("[CronManager] [Adım 4] Zamanlayıcı (Scheduler) DB'yi Güncelledi.");

        // =========================================================================
        // ADIM 5: NOTIFIER - Bildirim Gönderimi (Local, Skipped ve görev politikası 'failure'/'never' ise ATLANIR)
        // =========================================================================
        $notifyPolicy = CronNotificationHandler::resolveNotifyPolicy($rawParams, $taskClass);
        if (!is_local() && CronNotificationHandler::shouldNotify($status, $notifyPolicy)) {
            try {
                $this->handler('cronNotification')->sendNotification($job, $status, $message, null, $startedAt, $startTime);
                $this->logWorkspace('cron')->withProject($projectKey)->info("[CronManager] [Adım 5] E-Posta bildirimi başarıyla iletildi.");
            } catch (\Throwable $notifErr) {
                $this->logWorkspace('cron')->withProject($projectKey)->error("[CronManager] [Adım 5] E-Posta bildirimi gönderilemedi: " . $notifErr->getMessage());
            }
        } else {
            $this->logWorkspace('cron')->withProject($projectKey)->info("[CronManager] [Adım 5] E-Posta bildirimi atlandı (Local ortam, Status: {$status}, Politika: {$notifyPolicy}).");
        }

        $this->logWorkspace('cron')->withProject($projectKey)->info("=== [CronManager Pipeline] Job ID {$jobId} Başarıyla Tamamlandı ===");
    }
}
