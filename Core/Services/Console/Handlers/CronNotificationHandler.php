<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Services\Console\Handlers;

use Rbn\Framework\Core\Base\BaseComponent;

/**
 * CronNotificationHandler - Cron Execution Email Notification Authority 📧🛰️⚓
 * 
 * Part of RBN Framework Framework RBN Framework.
 * Reads notification emails zero-SQL from workspace/.cache/project_{projectKey}.json
 * and dispatches formatted HTML execution reports via SMTP email service.
 */
class CronNotificationHandler extends BaseComponent
{
    /** @var array Deduplication registry to prevent double email delivery */
    private static array $sentJobs = [];

    public const NOTIFY_ALWAYS = 'always';
    public const NOTIFY_FAILURE = 'failure';
    public const NOTIFY_NEVER = 'never';

    /**
     * Görev bazlı bildirim politikası: cron_jobs.params `notify_on` > görev sınıfı NOTIFY_ON > 'always'.
     * Geçersiz değer bir alt kaynağa düşer; bildirim sessizce kaybolmaz.
     */
    public static function resolveNotifyPolicy(array $params, string $taskClass): string
    {
        $valid = [self::NOTIFY_ALWAYS, self::NOTIFY_FAILURE, self::NOTIFY_NEVER];

        $fromDb = strtolower(trim((string) ($params['notify_on'] ?? '')));
        if (in_array($fromDb, $valid, true)) {
            return $fromDb;
        }

        $const = $taskClass . '::NOTIFY_ON';
        if ($taskClass !== '' && class_exists($taskClass) && defined($const)) {
            $fromClass = strtolower((string) constant($const));
            if (in_array($fromClass, $valid, true)) {
                return $fromClass;
            }
        }

        return self::NOTIFY_ALWAYS;
    }

    /**
     * Bu koşu sonucu için bildirim gönderilmeli mi? 'skipped' hiçbir politikada bildirmez.
     */
    public static function shouldNotify(string $status, string $policy): bool
    {
        if ($status === 'skipped' || $policy === self::NOTIFY_NEVER) {
            return false;
        }

        if ($policy === self::NOTIFY_FAILURE) {
            return !in_array($status, ['success', 'published'], true);
        }

        return true;
    }

    /**
     * Sends an email notification for a completed cron task.
     *
     * @param array $job Task job record metadata
     * @param string $status 'success' or 'failed'
     * @param string $message Final message / error text
     * @param object|null $task Task instance if available
     * @param string $startedAt Datetime string
     * @param float $startTime Microtime timestamp
     * @return bool
     */
    public function sendNotification(array $job, string $status, string $message, ?object $task = null, string $startedAt = '', float $startTime = 0.0): bool
    {
        try {
            // 🛑 Local Ortamda Mail Gönderimini Pas Geç! 🛡️
            if (function_exists('is_local') && is_local()) {
                return false;
            }

            // 🛑 Pas Geçilen İşlemler ve Görev Bazlı Bildirim Politikası 📬
            $taskClass = (string) ($job['task_class'] ?? ($task ? get_class($task) : ''));
            $params = is_array($job['params'] ?? null) ? $job['params'] : (json_decode((string) ($job['params'] ?? ''), true) ?: []);
            if (!self::shouldNotify($status, self::resolveNotifyPolicy($params, $taskClass))) {
                return false;
            }

            $projectKey = $job['project_key'] ?? (function_exists('project_key') ? project_key() : '') ?: '';
            if (empty($projectKey)) {
                return false;
            }

            // 🛑 Çift Mail Gönderimini Engelleme (Deduplication) 🛡️
            $dedupKey = $projectKey . '_' . ($job['task_key'] ?? 'task') . '_' . date('Y-m-d_H');
            if (isset(self::$sentJobs[$dedupKey])) {
                return true; // Zaten bu proje ve görev için mail gönderildi, mükerrer mail engellendi
            }
            self::$sentJobs[$dedupKey] = true;

            // 1. Read notification recipient emails zero-SQL from project cache
            $recipients = $this->resolveRecipients($projectKey);
            if (empty($recipients)) {
                return false;
            }

            // 2. Fetch task logs & metadata
            $taskLog = $this->service('base.taskLog');
            $taskName = $job['name'] ?? ($taskLog ? $taskLog->getTaskName() : null) ?: ($job['task_key'] ?? 'Zamanlanmış Görev');
            $duration = $startTime > 0 ? round(microtime(true) - $startTime, 2) : 0.00;
            $finishedAt = date('Y-m-d H:i:s');

            $logHtml = $taskLog ? $taskLog->getFormattedLogsHtml() : '';

            // 3. Construct HTML email body
            $isSuccess = ($status === 'success');
            $statusBadge = $isSuccess
                ? '<span style="background-color:#10b981; color:#ffffff; padding:6px 14px; border-radius:20px; font-weight:700; font-size:13px;">✔ GÖREV BAŞARILI</span>'
                : '<span style="background-color:#ef4444; color:#ffffff; padding:6px 14px; border-radius:20px; font-weight:700; font-size:13px;">✘ GÖREV HATA İLE BİTTİ</span>';

            $projectTitle = strtoupper($projectKey);
            $subject = "[{$projectTitle}] Cron Raporu: {$taskName} (" . ($isSuccess ? 'BAŞARILI' : 'HATA') . ")";

            $bodyHtml = "
            <div style=\"font-family:'Segoe UI', -apple-system, BlinkMacSystemFont, Roboto, sans-serif; color:#1e293b;\">
                <div style=\"background-color:#ffffff; border-radius:8px; overflow:hidden; border:1px solid #e2e8f0;\">
                    
                    <!-- Status Bar -->
                    <div style=\"padding:16px 20px; background-color:#f8fafc; border-bottom:1px solid #e2e8f0;\">
                        <table style=\"width:100%; border-collapse:collapse;\">
                            <tr>
                                <td>
                                    <div style=\"font-size:11px; color:#64748b; text-transform:uppercase; font-weight:600;\">Görev / Sonuç</div>
                                    <div style=\"font-weight:700; color:#0f172a; font-size:15px; margin-bottom:4px;\">{$taskName}</div>
                                    <div>{$statusBadge}</div>
                                </td>
                                <td style=\"text-align:right; vertical-align:top;\">
                                    <div style=\"font-size:11px; color:#64748b; text-transform:uppercase; font-weight:600;\">Çalışma Süresi</div>
                                    <div style=\"font-weight:700; color:#334155; font-size:15px; margin-top:4px;\">{$duration} saniye</div>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <!-- Execution Meta -->
                    <div style=\"padding:20px; border-bottom:1px solid #f1f5f9;\">
                        <table style=\"width:100%; font-size:13px; color:#475569;\">
                            <tr>
                                <td style=\"padding:5px 0; font-weight:600;\">Görev Kimliği (Key):</td>
                                <td style=\"padding:5px 0; text-align:right; font-family:monospace; color:#0f172a;\">" . htmlspecialchars((string) ($job['task_key'] ?? '-')) . "</td>
                            </tr>
                            <tr>
                                <td style=\"padding:5px 0; font-weight:600;\">Başlangıç Zamanı:</td>
                                <td style=\"padding:5px 0; text-align:right;\">{$startedAt}</td>
                            </tr>
                            <tr>
                                <td style=\"padding:5px 0; font-weight:600;\">Bitiş Zamanı:</td>
                                <td style=\"padding:5px 0; text-align:right;\">{$finishedAt}</td>
                            </tr>
                            " . (!empty($message) ? "
                            <tr>
                                <td style=\"padding:10px 0 0 0; font-weight:600; vertical-align:top;\" colspan=\"2\">
                                    <div style=\"margin-top:6px; padding:12px 14px; background-color:" . ($isSuccess ? '#ecfdf5' : '#fef2f2') . "; border-left:4px solid " . ($isSuccess ? '#10b981' : '#ef4444') . "; border-radius:4px; font-size:13px; color:" . ($isSuccess ? '#065f46' : '#991b1b') . "; line-height:1.5;\">
                                        <strong>Özet Mesajı:</strong> " . htmlspecialchars($message) . "
                                    </div>
                                </td>
                            </tr>
                            " : "") . "
                        </table>
                    </div>

                    <!-- Task Steps Breakdown -->
                    <div style=\"padding:20px 15px;\">
                        <h3 style=\"margin-top:0; margin-bottom:14px; font-size:15px; color:#0f172a; border-bottom:2px solid #e2e8f0; padding-bottom:8px;\">
                            📋 Adım Adım Görev Log Çıktıları
                        </h3>
                        {$logHtml}
                    </div>
                </div>
            </div>";

            // 4. Send email via EmailService using MasterDbData sender credentials (useMaster = true)
            $emailService = $this->service('email');
            if ($emailService) {
                foreach ($recipients as $recipient) {
                    $emailService->direct($recipient, $subject, $bodyHtml, $projectTitle . " Yönetimi", true);
                }
                return true;
            }

            return false;
        } catch (\Throwable $e) {
            if (PHP_SAPI === 'cli') {
                echo "CronNotificationHandler Exception: " . $e->getMessage() . " in " . $e->getFile() . " L:" . $e->getLine() . "\n" . $e->getTraceAsString() . "\n";
            }
            try {
                if ($this->storage && method_exists($this->storage, 'logs') && $this->storage->logs()) {
                    $this->storage->logs()->channel('cron')->error("CronNotificationHandler Error: " . $e->getMessage(), ['exception' => $e]);
                }
            } catch (\Throwable $err) {
            }
            return false;
        }
    }

    /**
     * Resolves notification recipients zero-SQL from project cache (.cache/project_{projectKey}.json)
     */
    private function resolveRecipients(string $projectKey): array
    {
        $emails = [];

        // Read unified Master + Project cron_notification_emails from project cache file 🪐
        $raw = $this->resolveProjectData('cron_notification_emails', $projectKey);
        if (!empty($raw)) {
            $list = is_array($raw) ? $raw : preg_split('/[\s,;]+/', (string) $raw, -1, PREG_SPLIT_NO_EMPTY);
            $emails = array_merge($emails, $list);
        }

        $validEmails = [];
        foreach ($emails as $email) {
            $email = trim((string) $email);
            if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $validEmails[] = $email;
            }
        }

        return array_values(array_unique($validEmails));
    }
}
