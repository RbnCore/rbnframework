<?php

declare(strict_types=1);

/**
 * RBN RBN Framework Diagnostic Watchdog 🛡️⚔️⚓
 */
namespace Rbn\Framework\Core\System\Kernel\Guards {

    /**
     * Watchdog Class - Identity Holder for Diagnostic Flags 🛡️
     */
    if (!class_exists('Rbn\Framework\Core\System\Kernel\Guards\Watchdog', false)) {
        class Watchdog {
            /** @var bool Panic Guard Flag 🚩 */
            public static bool $inPanic = false;
        }
    }
}

// 🚩 RBN Framework: [GLOBAL PANIC GATEWAY] 🌍🛡️
namespace {
    if (!function_exists('rbn_panic')) {
        /**
         * Terminate execution and render the RBN Shield Panic interface.
         */
        function rbn_panic(string $message, ?string $type = 'Critical Failure', ?array $stack = null): void
        {
            // 🚩 RBN Framework: [RECURSION LOCK] Atomic Brake using global constant ⚡
            if (defined('RBN_PANIC_ACTIVE')) {
                return;
            }
            define('RBN_PANIC_ACTIVE', true);

            // Update class flag as well for legacy support
            if (class_exists(\Rbn\Framework\Core\System\Kernel\Guards\Watchdog::class, false)) {
                \Rbn\Framework\Core\System\Kernel\Guards\Watchdog::$inPanic = true;
            }

            // 🔬 1. Isolation: Kill any existing output buffers to ensure clean rendering.
            while (ob_get_level() > 0) {
                ob_end_clean();
            }

            // 🛡️ 2. Identify the target template (panic.view.php)
            // Yol mutlak adresle SABITLENMEZ. Bu dosya gelistirme makinesinde (E:),
            // ofis laptopunda (D:) ve sunucuda farkli koklerde durur; sabit yol
            // hicbirinde calismaz. __DIR__ her zaman calistigi yeri verir.
            // __DIR__ = <kok>/rbnframework/Core/System/Kernel/Guards
            // dirname(__DIR__, 4) = <kok>/rbnframework
            $viewPath = dirname(__DIR__, 4) . DIRECTORY_SEPARATOR
                . 'Resources' . DIRECTORY_SEPARATOR
                . 'Views'  . DIRECTORY_SEPARATOR
                . 'Errors' . DIRECTORY_SEPARATOR
                . 'panic.view.php';

            // 🕰️ 3. Prepare Diagnostic Data
            $now = date('Y-m-d H:i:s');
            $type = $type ?? 'RBN System Panic';
            
            // Format the stack trace for the template
            $trace = '';
            $stack = $stack ?? debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 20);
            
            foreach ($stack as $i => $frame) {
                $trace .= "#$i " . ($frame['class'] ?? '') . ($frame['type'] ?? '') . ($frame['function'] ?? '') . " in " . ($frame['file'] ?? 'unknown') . ":" . ($frame['line'] ?? '?') . "\n";
            }

            $file = $stack[0]['file'] ?? 'unknown';
            $line = (int)($stack[0]['line'] ?? 0);

            // 🎭 4. Final Render
            if (!headers_sent()) {
                @header('Content-Type: text/html; charset=utf-8');
                @http_response_code(500);
            }

            if (file_exists($viewPath)) {
                include $viewPath;
            } else {
                // Fallback to plain text if template is missing 🆘
                echo "=== RBN TERMINAL PANIC (TEMPLATE MISSING) ===\n";
                echo "Message: $message\n";
                echo "File: $file on Line $line\n";
                echo "\n--- STACK TRACE ---\n$trace";
            }

            exit(1);
        }
    }
}
