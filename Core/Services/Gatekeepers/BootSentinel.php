<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Services\Gatekeepers;

use Rbn\Framework\Core\Services\Exception\Handlers\ExceptionHandler;
use Rbn\Framework\Core\Services\Exception\Handlers\LogHandler;
use Rbn\Framework\Core\Services\Exception\Providers\SurvivalProvider;
use Rbn\Framework\Core\System\Paths\Paths;
use Throwable;
use ErrorException;

/**
 * BootSentinel - The Strategic System Gatekeeper 🛡️🚨⚓
 * 
 * RBN 3.5: Unified Sentinel for early-boot security and failure rendering.
 * Groups all system integrity guards under the Gatekeepers service.
 * Zero-Dependency: No inheritance to ensure it works even if core services fail.
 */
class BootSentinel
{
    /**
     * Registers the Sentinel to catch early failures 🛡️
     */
    public static function register(?Throwable $e = null): void
    {
        $instance = new self();

        // 1. If an error is passed (manual boot failure), handle it immediately
        if ($e) {
            $instance->dispatch($e);
            return;
        }

        // 2. Register Global Callbacks (Exceptions, Errors, Fatal Shutdowns)
        set_exception_handler([$instance, 'dispatch']);
        
        set_error_handler(function($level, $message, $file, $line) use ($instance) {
            if (!(error_reporting() & $level)) return false;
            $instance->dispatch(new ErrorException($message, 0, $level, $file, $line));
            return true;
        });

        register_shutdown_function(function() use ($instance) {
            $error = error_get_last();
            if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
                $instance->dispatch(new ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line']));
            }
        });
    }

    /**
     * Centralized bridge to the Exception Decision Brain 🩺🛰️
     */
    public function dispatch(Throwable $e): void
    {
        // 🔬 RBN 3.5: Black-box Recording (Panic Level) 📓📼
        try {
            if (class_exists(LogHandler::class)) {
                $level = 'development';
                if (class_exists(\Rbn\Framework\Core\Services\Exception\Data\ShieldMetadata::class)) {
                    $level = \Rbn\Framework\Core\Services\Exception\Data\ShieldMetadata::ERROR_MAP[get_class($e)]['level'] ?? 'development';
                }
                $reason = ($level === 'user') ? 'warning' : 'panic';
                LogHandler::failsafeLog([
                    'type' => get_class($e),
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'level' => $level
                ], $reason);
            }
        } catch (Throwable $logError) {
            // Silently fail logging to prioritize error rendering
        }

        // 1. Primary Path: Attempt to use the synchronized ExceptionHandler
        if (class_exists(ExceptionHandler::class)) {
            try {
                $handler = new ExceptionHandler();
                $handler->handle($e);
                exit;
            } catch (Throwable $internalError) {
                // ExceptionHandler itself crashed during boot 🆘
            }
        }

        // 2. Failsafe Path: RBN Survival UI (Layer 0) 🛡️🎭
        if (class_exists(SurvivalProvider::class)) {
            try {
                SurvivalProvider::render(
                    get_class($e),
                    $e->getMessage(),
                    "Hata BootSentinel aşamasında oluştu. Sistem stabilize edilemedi.",
                    500
                );
                exit;
            } catch (Throwable $e2) {
                // SurvivalProvider failed
            }
        }

        // 3. Panic Path: Masterpiece BootGuard (Zero Dependency Render) 🏛️⚔️🛡️⚓
        self::render($e);
    }

    /**
     * Terminate the boot sequence and render a clear error page 🛡️🚨
     */
    public static function render(Throwable $e): void
    {
        // 🚩 RBN 3.5: [PANIC LOCK] Atomic Brake using global constant ⚡
        if (!defined('RBN_PANIC_ACTIVE')) {
            define('RBN_PANIC_ACTIVE', true);
        }

        if (class_exists(\Rbn\Framework\Core\System\Kernel\Guards\Watchdog::class)) {
            \Rbn\Framework\Core\System\Kernel\Guards\Watchdog::$inPanic = true;
        }

        // 🎼 RBN 3.5: Clean ALL Buffers (Prevent trapped output)
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        // 🥇 RBN 3.5: Absolute Headers
        if (!headers_sent()) {
            @header('HTTP/1.1 500 Internal Server Error');
            @header('Content-Type: text/html; charset=utf-8');
        }

        $message = $e->getMessage();
        $file = $e->getFile();
        $line = $e->getLine();
        $trace = $e->getTraceAsString();

        try {
            // 🎯 RBN 3.5: Strategic View Loading (Hardcoded Absolute Path Fallback) 🏺⚓
            $viewPath = Paths::frameworkRoot() . DIRECTORY_SEPARATOR . 'Resources' . DIRECTORY_SEPARATOR . 'Views' . DIRECTORY_SEPARATOR . 'Errors' . DIRECTORY_SEPARATOR . 'panic.view.php';

            if (file_exists($viewPath)) {
                include $viewPath;
                exit(1);
            }
        } catch (Throwable $renderError) {
            // Even the panic view failed! 🆘
            $message .= " (Double Fault: " . $renderError->getMessage() . ")";
        }

        // 💀 Absolute Terminal Fallback (Only if view file is missing or failed)
        die("<h1>RBN Critical Boot Failure</h1><p>{$message}</p><p>At {$file} on line {$line}</p>");
    }
}
