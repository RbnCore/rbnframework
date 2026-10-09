<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Services\Exception\Handlers;

use Rbn\Framework\Core\Base\BaseComponent;
use Rbn\Framework\Core\System\Paths\Paths;
use Throwable;

/**
 * LogHandler - Exception Persistence Layer 📓🛡️
 * 
 * RBN Framework: [THE BLACK-BOX RECORDER] RBN Framework Standard.
 * Responsible for persisting exception details to logs in all conditions.
 * Features a static 'failsafeLog' for catastrophic kernel failures.
 */
class LogHandler extends BaseComponent
{
    /** @var bool Recursion Guard 🛡️ */
    private static bool $isLogging = false;

    /**
     * Persist Exception Analysis Data (Rich Orchestration Mode) 📓🛰️
     * Uses the LogProvider via Service Hub.
     */
    public function log(array $analysis): void
    {
        // [RBN Framework] RECURSION GUARD 🛡️⚓
        if (self::$isLogging) {
            return;
        }

        self::$isLogging = true;

        try {
            // 🛡️ RBN Framework: [BOOT AWARENESS] 🚀
            // If the failure is a Pre-flight (initialization) error, we bypass the Service Hub
            // to avoid discovery loops while the database is still being guarded.
            $isPreflight = ($analysis['type'] ?? '') === 'PreflightException' || !Paths::isInitialized();

            if ($isPreflight) {
                self::failsafeLog($analysis, "Pre-flight Mode Bypass");
                return;
            }

            // 1. Identify the Semantic Channel (Layer-based) 🏛️⚓
            $channel = $analysis['level'] ?? 'error';

            // 2. Access the Centralized Logging Provider ⚓💎
            // 🛡️ RBN Framework: [RECURSION-SAFE RESOLUTION] 🧪⚓
            try {
                $logger = $this->service('log');
                if ($logger) {
                    $logger->channel($channel)->log(
                        $analysis['level'] === 'user' ? 'INFO' : 'ERROR',
                        $analysis['message'],
                        $analysis
                    );
                } else {
                    self::failsafeLog($analysis, "Log Service Discovery Empty");
                }
            } catch (Throwable $internalError) {
                // Critical Loop Break: Discovery or Service failed during logging 🆘
                self::failsafeLog($analysis, "Reporting Cycle Interrupted: " . $internalError->getMessage());
            }
        } catch (Throwable $e) {
            // Silent failure if rich logging fails, but try a master panic log 🔇
            self::failsafeLog($analysis, "Terminal Reporting Failure: " . $e->getMessage());
        } finally {
            self::$isLogging = false;
        }
    }

    /**
     * Failsafe Logging Engine (Absolute Terminal Defense) 🛡️🆘📓
     * RBN Framework: Survival Mode.
     * Writes directly to <project>/Storage/logs/panic/ (proje kökü çalışma zamanında çözülür)
     * Does NOT require Service Hub or any framework components.
     */
    public static function failsafeLog(array $data, string $panicReason = ''): void
    {
        try {
            $projectData = \Rbn\Framework\Core\System\Kernel\Bootstrap::getAppContext('project_data');
            $projectKey = !empty($projectData['project_key']) ? (string)$projectData['project_key'] : (function_exists('project_key') ? project_key() : 'default');

            $isWarning = ($data['level'] ?? '') === 'user';
            $subFolder = $isWarning ? 'warning' : 'panic';
            $logDir = Paths::project()->storage('logs') . '/' . $subFolder;

            if (!is_dir($logDir)) {
                @mkdir($logDir, 0775, true);
            }

            $date = \now('Y-m-d');
            $file = $logDir . "/{$date}_{$projectKey}_{$subFolder}.jsonl";

            $reasonKey = $isWarning ? 'warning_reason' : 'panic_reason';

            $entry = [
                'timestamp' => \now('c'),
                'project_key' => $projectKey,
                $reasonKey => $panicReason,
                'type' => $data['type'] ?? 'Unknown',
                'message' => $data['message'] ?? 'No message provided',
                'file' => $data['file'] ?? 'Unknown',
                'line' => $data['line'] ?? 0,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'CLI',
                'uri' => $_SERVER['REQUEST_URI'] ?? 'CLI',
                'method' => $_SERVER['REQUEST_METHOD'] ?? 'N/A',
                // FW-KARAR-2 / Z-1: kullaniciya gosterilen hata kimligi; log'daki
                // tam ayrinti ile ayni kaydi birlestirmek icin.
                'error_id' => $data['error_id'] ?? null
            ];

            $jsonEntry = json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            @file_put_contents($file, $jsonEntry . PHP_EOL, FILE_APPEND | LOCK_EX);

        } catch (Throwable $e) {
            // Absolutely terminal - total silence required to avoid loops 🔇
        }
    }
}
