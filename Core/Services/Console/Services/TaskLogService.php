<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Services\Console\Services;

use Rbn\Framework\Core\Base\Services\BaseService;
use Rbn\Framework\Core\Base\Attributes\Component;

/**
 * TaskLogService - Otonom Görevler için Aşamalı ve Yapılandırılmış Günlükleme (Logging) Servisi 🛰️📝⚓
 * Part of RBN Framework Framework.
 */
#[Component(alias: 'base.taskLog', type: 'service')]
class TaskLogService extends BaseService
{
    protected ?string $taskName = null;
    protected ?string $taskKey = null;
    protected ?string $projectKey = null;
    protected array $steps = [];

    /**
     * Yeni bir görevi başlatır.
     */
    public function start(string $taskName, string $projectKey, ?string $taskKey = null): void
    {
        $this->taskName = $taskName;
        $this->projectKey = $projectKey;
        $this->taskKey = $taskKey;
        $this->steps = [];

        $keyTag = !empty($this->taskKey) ? " [{$this->taskKey}]" : '';
        $message = "=== [{$this->projectKey}]{$keyTag} GÖREV BAŞLADI: {$this->taskName} ===";
        $this->logToFile('info', $message);
        $this->logToConsole($message, 'info');
    }

    /**
     * Görevin belirli bir aşamasını günlükler.
     */
    public function step(string $stepName, string $message, array $metadata = []): void
    {
        $stepIndex = count($this->steps) + 1;
        $formattedMsg = "[{$this->projectKey}] [Aşama {$stepIndex}: {$stepName}] {$message}";

        $this->steps[] = [
            'step' => $stepName,
            'message' => $message,
            'metadata' => $metadata,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        $this->logToFile('info', $formattedMsg);

        $consoleMsg = "   -> {$stepName}: {$message}";
        if (!empty($metadata)) {
            $consoleMsg .= " " . json_encode($metadata, JSON_UNESCAPED_UNICODE);
        }
        $this->logToConsole($consoleMsg, 'info');
    }

    /**
     * Görevin başarıyla tamamlandığını günlükler.
     */
    public function succeeded(string $message): void
    {
        $this->step('Görev Tamamlandı', $message);
        $keyTag = !empty($this->taskKey) ? " [{$this->taskKey}]" : '';
        $nameTag = !empty($this->taskName) ? " [{$this->taskName}]" : '';
        $formattedMsg = "✔ [{$this->projectKey}]{$keyTag}{$nameTag} [BAŞARILI] {$message}";
        $this->logToFile('info', $formattedMsg);
        $this->logToConsole($formattedMsg, 'success');
        $this->logToFile('info', "=== GÖREV TAMAMLANDI ===\n");
    }

    /**
     * Görevin pas geçildiğini (atlandığını) günlükler ⏭
     */
    public function skipped(string $message): void
    {
        $this->step('Görev Atlandı', $message);
        $keyTag = !empty($this->taskKey) ? " [{$this->taskKey}]" : '';
        $nameTag = !empty($this->taskName) ? " [{$this->taskName}]" : '';
        $formattedMsg = "⏭ [{$this->projectKey}]{$keyTag}{$nameTag} [ATLANDI] {$message}";
        $this->logToFile('info', $formattedMsg);
        $this->logToConsole($formattedMsg, 'warning');
        $this->logToFile('info', "=== GÖREV ATLANDI ===\n");
    }

    /**
     * Görevin hata ile sonlandığını günlükler.
     */
    public function failed(string $message, ?\Throwable $e = null): void
    {
        $this->step('Görev Hatası', $message);
        $keyTag = !empty($this->taskKey) ? " [{$this->taskKey}]" : '';
        $nameTag = !empty($this->taskName) ? " [{$this->taskName}]" : '';
        $formattedMsg = "✘ [{$this->projectKey}]{$keyTag}{$nameTag} [HATA] {$message}";
        if ($e) {
            $formattedMsg .= " | Hata Mesajı: " . $e->getMessage();
        }

        $this->logToFile('error', $formattedMsg);
        if ($e) {
            $this->logToFile('error', $e->getTraceAsString());
        }

        $this->logToConsole($formattedMsg, 'error');
        $this->logToFile('info', "=== GÖREV HATA İLE BİTTİ ===\n");
    }

    /**
     * Görev adımlarını array olarak döner (Cron trace için).
     */
    public function getSteps(): array
    {
        return $this->steps;
    }

    /**
     * Başlatılan görevin adını döner.
     */
    public function getTaskName(): ?string
    {
        return $this->taskName;
    }

    /**
     * Görevin bağlı olduğu proje anahtarını döner.
     */
    public function getProjectKey(): ?string
    {
        return $this->projectKey;
    }

    /**
     * Görev boyunca kaydedilen tüm adımları HTML formatında döküm olarak döner.
     */
    public function getFormattedLogsHtml(): string
    {
        if (empty($this->steps)) {
            return '<p style="color: #64748b; font-style: italic;">Henüz bir adım log kaydı kaydedilmedi.</p>';
        }

        $html = '<table style="width:100%; border-collapse:collapse; margin-top:10px; font-family:sans-serif; font-size:13px; table-layout:fixed; word-wrap:break-word;">';
        $html .= '<tr style="background:#f1f5f9; color:#334155; text-align:left;">';
        $html .= '<th style="padding:10px 12px; border-bottom:2px solid #cbd5e1; width:75px;">Zaman</th>';
        $html .= '<th style="padding:10px 12px; border-bottom:2px solid #cbd5e1; width:140px;">Aşama</th>';
        $html .= '<th style="padding:10px 12px; border-bottom:2px solid #cbd5e1;">Açıklama / Detay</th>';
        $html .= '</tr>';

        foreach ($this->steps as $idx => $step) {
            $bgColor = ($idx % 2 === 0) ? '#ffffff' : '#f8fafc';
            $time = isset($step['timestamp']) ? date('H:i:s', strtotime($step['timestamp'])) : '-';
            $name = htmlspecialchars($step['step'] ?? ('Aşama ' . ($idx + 1)));
            $msg = htmlspecialchars($step['message'] ?? '');

            $html .= "<tr style=\"background:{$bgColor}; border-bottom:1px solid #e2e8f0;\">";
            $html .= "<td style=\"padding:10px 12px; color:#64748b; font-size:12px; white-space:nowrap; vertical-align:top;\">{$time}</td>";
            $html .= "<td style=\"padding:10px 12px; color:#0f172a; font-weight:600; vertical-align:top; word-break:break-word;\">{$name}</td>";
            $html .= "<td style=\"padding:10px 12px; color:#334155; vertical-align:top; word-break:break-word; line-height:1.5;\">{$msg}</td>";
            $html .= "</tr>";
        }

        $html .= '</table>';
        return $html;
    }

    /**
     * Dosya günlüğüne yazar.
     */
    private function logToFile(string $level, string $message): void
    {
        try {
            $logger = $this->storage->logs()->withProject($this->projectKey)->channel('cron');
            if ($level === 'error') {
                $logger->error($message);
            } else {
                $logger->info($message);
            }
        } catch (\Throwable $err) {
            // Fail silently if log driver has issues
        }
    }

    /**
     * Terminale/Konsola renkli çıktı basar (Eğer CLI modunda çalışıyorsa).
     */
    private function logToConsole(string $message, string $type = 'info'): void
    {
        if (PHP_SAPI !== 'cli') {
            return;
        }

        $colors = [
            'info' => "\033[36m", // Cyan
            'success' => "\033[32m", // Green
            'error' => "\033[31m", // Red
            'reset' => "\033[0m"
        ];

        $color = $colors[$type] ?? $colors['info'];
        $reset = $colors['reset'];

        echo "{$color}{$message}{$reset}\n";
    }
}
