<?php

namespace Rbn\Framework\Core\System\Storage\Providers;

use Rbn\Framework\Core\System\Storage\Base\BaseStorageProvider;
use Rbn\Framework\Core\System\Paths\Paths;
use Rbn\Framework\Core\System\Storage\Constants\LogConstant;
use Exception;

/**
 * LogProvider - Framework Günlük Kayıt Birimi 📜
 * 
 * Kanal bazlı, tarihli ve JSONL formatlı log yazımını yönetir.
 */
class LogProvider extends BaseStorageProvider
{
    protected string $storageName = 'logs';
    protected bool $encrypted = false;
    protected string $format = 'jsonl';

    protected ?string $targetFile = null;
    protected bool $targetAll = false;
    protected ?int $targetOldDays = null;

    /**
     * Fluent Channel Tracking 🛰️
     */
    protected string $currentChannel = 'app';


    /* ==========================================================================
       [ FLUENT TARGETING ] - Search & Filter Focus 🎯
       ========================================================================== */

    public function file(string $filename): self
    {
        $this->targetFile = $filename;
        $this->targetAll = false;
        $this->targetOldDays = null;
        return $this;
    }

    public function targetAll(): self
    {
        $this->targetAll = true;
        $this->targetFile = null;
        $this->targetOldDays = null;
        return $this;
    }

    public function old(int $days = 30): self
    {
        $this->targetOldDays = $days;
        $this->targetAll = false;
        $this->targetFile = null;
        return $this;
    }

    /* ==========================================================================
       [ WORKSPACE & CUSTOM PATH TARGETING ] 🌐
       ========================================================================== */

    protected ?string $customStoragePath = null;

    /**
     * Target custom path for global / root logging 🎯
     */
    public function toPath(string $path): self
    {
        $this->customStoragePath = $path;
        return $this;
    }

    /**
     * Target workspace global logs directory (workspace/logs) 🌐
     */
    public function workspace(): self
    {
        $this->customStoragePath = Paths::workspace() . DIRECTORY_SEPARATOR . 'logs';
        return $this;
    }

    /* ==========================================================================
       [ WRITER CONFIGURATION ] - Fluent Log Setup 🖊️
       ========================================================================== */

    /**
     * Set the target channel for the next log operations.
     */
    public function channel(string $name): self
    {
        $this->currentChannel = preg_replace('/[^a-zA-Z0-9_\-]/', '', $name) ?: 'app';
        return $this;
    }

    /* ==========================================================================
       [CORE ACTIONS] - RBN Logic 🧠
       ========================================================================== */

    protected function getStorageDir(): string
    {
        if (!empty($this->customStoragePath)) {
            $dir = $this->customStoragePath;
            $this->customStoragePath = null;
            return $dir;
        }

        $projectKey = $this->projectKey ?: (function_exists('project_key') ? project_key() : null);
        if (!empty($projectKey) && $projectKey !== 'default') {
            $projectPath = $this->resolveProjectPath($projectKey);
            if (!empty($projectPath) && is_dir($projectPath)) {
                return $projectPath . '/Storage/logs';
            }
        }

        return Paths::project()->storage('logs');
    }

    /**
     * Log Yaz (Kanal ve Seviye odaklı)
     */
    public function log(string $level, string $message, array $context = [], ?string $channel = null): bool
    {
        $activeChannel = $channel ?? $this->currentChannel;
        $activeChannel = preg_replace('/[^a-zA-Z0-9_\-]/', '', $activeChannel) ?: 'app';

        // Channel to folder mapping
        $folder = $activeChannel;

        $projectKey = $this->projectKey ?: project_key() ?: 'default';
        $date = \now('Y-m-d');
        $path = $this->getStorageDir() . '/' . $folder . '/' . $date . '_' . $projectKey . '.jsonl';

        $logEntry = [
            'timestamp' => \now('c'),
            'level' => strtoupper($level),
            'message' => $message,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN',
            'uri' => $_SERVER['REQUEST_URI'] ?? 'CLI',
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'N/A',
            'context' => $context
        ];

        // Format is 'json', but we need to append with newline
        $result = $this->driver->write($path, $logEntry, 'json', true, $this->encrypted);

        // Reset channel to default after one-off log if it was a manual override
        // Or keep it if it was set via fluent API? 
        // Strategy: Reset only if it's NOT the default 'app' and logic is finished.
        // Actually, for fluent API, we'll keep it as per USER request ($rbn->log()->set('auth'))

        return $result;
    }

    /* ==========================================================================
       [ CONVENIENCE METHODS ] - PSR-3 Like Logging 🚀
       ========================================================================== */

    public function emergency(string $message, array $context = [], ?string $channel = null): void
    {
        $this->log('EMERGENCY', $message, $context, $channel);
    }

    public function alert(string $message, array $context = [], ?string $channel = null): void
    {
        $this->log('ALERT', $message, $context, $channel);
    }

    public function critical(string $message, array $context = [], ?string $channel = null): void
    {
        $this->log('CRITICAL', $message, $context, $channel);
    }

    public function error(string $message, array $context = [], ?string $channel = null): void
    {
        $this->log('ERROR', $message, $context, $channel);
    }

    public function warning(string $message, array $context = [], ?string $channel = null): void
    {
        $this->log('WARNING', $message, $context, $channel);
    }

    public function notice(string $message, array $context = [], ?string $channel = null): void
    {
        $this->log('NOTICE', $message, $context, $channel);
    }

    public function info(string $message, array $context = [], ?string $channel = null): void
    {
        $this->log('INFO', $message, $context, $channel);
    }

    public function debug(mixed $message, array $context = [], ?string $channel = null): void
    {
        if (is_array($message) || is_object($message)) {
            $context['data'] = $message;
            $message = 'Debug Data Dump';
        }
        $this->log('DEBUG', (string) $message, $context, $channel);
    }

    public function logException(\Throwable $e, string $customMessage = '', ?string $channel = null): void
    {
        $message = ($customMessage ? $customMessage . ": " : "") . $e->getMessage();
        $context = [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ];

        $this->log('ERROR', $message, $context, $channel);
    }

    /**
     * Tüm log dosyalarını listele
     */
    public function all(): array
    {
        $logFiles = [];
        $root = $this->getStorageDir();

        if (!is_dir($root))
            return [];

        $channels = array_diff(scandir($root), ['..', '.']);
        $projectKey = $this->projectKey ?: project_key() ?: 'default';

        foreach ($channels as $channel) {
            $channelPath = $root . '/' . $channel;
            if (!is_dir($channelPath))
                continue;

            $files = glob($channelPath . '/*_' . $projectKey . '.jsonl') ?: [];
            foreach ($files as $file) {
                $logFiles[] = [
                    'name' => basename($file),
                    'channel' => $channel,
                    'relative' => $channel . '/' . basename($file),
                    'size' => filesize($file),
                    'modified' => filemtime($file),
                    'mtime' => filemtime($file)
                ];
            }
        }

        return $logFiles;
    }

    /**
     * Log Dosyası Detaylarını Döner (Download/Preview için)
     */
    public function details(?string $identifier = null): ?string
    {
        $file = $identifier ?? $this->targetFile;
        if (!$file)
            return null;

        $projectKey = $this->projectKey ?: project_key() ?: 'default';
        
        // Security check: ensure the file belongs to this project
        if (!str_contains(basename($file), '_' . $projectKey . '.jsonl')) {
            return null;
        }

        $path = $this->getStorageDir() . '/' . ltrim($file, '/\\');
        return $this->driver->read($path, 'raw', $this->encrypted);
    }

    /**
     * Tümünü Temizle (Yalnızca bu projeye ait log dosyalarını siler)
     */
    public function clearAll(): bool
    {
        $projectKey = $this->projectKey ?: project_key() ?: 'default';
        $root = $this->getStorageDir();

        if (!is_dir($root))
            return true;

        $channels = array_diff(scandir($root), ['..', '.']);
        foreach ($channels as $channel) {
            $channelPath = $root . '/' . $channel;
            if (!is_dir($channelPath))
                continue;

            $files = glob($channelPath . '/*_' . $projectKey . '.jsonl');
            if ($files !== false) {
                foreach ($files as $file) {
                    if (is_file($file))
                        @unlink($file);
                }
            }
        }
        return true;
    }

    public function getCategorizedLogs(): array
    {
        $files = $this->all();
        $categories = LogConstant::CATEGORIES;

        foreach ($categories as $key => &$cat) {
            $cat['files'] = [];
            $cat['size_raw'] = 0;
        }

        foreach ($files as $file) {
            $matched = false;
            $channel = strtolower($file['channel'] ?? '');

            foreach ($categories as $key => &$cat) {
                if ($key === 'total')
                    continue;
                if (($channel && str_contains($key, $channel)) || (!empty($cat['keywords']) && array_filter($cat['keywords'], fn($kw) => str_contains($channel, $kw)))) {
                    $file['type'] = $key;
                    $cat['files'][] = $file;
                    $cat['size_raw'] += $file['size'] ?? 0;
                    $matched = true;
                    break;
                }
            }

            if (!$matched) {
                $file['type'] = 'other';
                $categories['other']['files'][] = $file;
                $categories['other']['size_raw'] += $file['size'] ?? 0;
            }
        }

        // Finalize statistics and enrich with UI metadata for view compatibility
        foreach ($categories as $key => &$cat) {
            $cat['count'] = count($cat['files']);
            $cat['total_size'] = \Rbn\Framework\Core\Base\Services\BaseService::get()->helper('format')->formatFileSize($cat['size_raw']);
            $cat['config'] = [
                'title' => $cat['title'] ?? '',
                'icon' => $cat['icon'] ?? '',
                'class' => $cat['class'] ?? ''
            ];
        }

        return $categories;
    }

    public function getConfigs(): array
    {
        return LogConstant::CATEGORIES;
    }

    public function parseLogContent(string $rawContent): array
    {
        $jsonContent = [];
        $isJson = false;
        $rawContent = trim($rawContent);
        if (empty($rawContent))
            return ['is_json' => false, 'data' => []];

        $lines = explode("\n", $rawContent);
        foreach ($lines as $line) {
            if (empty(trim($line)))
                continue;
            $decoded = @json_decode($line, true);
            if (json_last_error() === JSON_ERROR_NONE && $decoded !== null) {
                $jsonContent[] = $decoded;
                $isJson = true;
            } else {
                $isJson = false;
                break;
            }
        }
        return ['is_json' => $isJson, 'data' => $jsonContent];
    }
}
