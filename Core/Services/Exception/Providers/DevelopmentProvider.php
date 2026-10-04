<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Services\Exception\Providers;

use Rbn\Framework\Core\Services\Exception\Providers\Base\BaseExceptionProvider;
use Rbn\Framework\Core\System\Paths\Paths;
use Throwable;

/**
 * DevelopmentProvider - Diagnostic UI Engine 🚀🧬
 * 
 * RBN 3.5: [LAYER 3] Masterpiece Standard.
 * Extends BaseExceptionProvider for symmetric service access.
 * Generates deep diagnostic views for internal development (Debug Mode).
 */
class DevelopmentProvider extends BaseExceptionProvider
{
    /**
     * Render the Deep Diagnostic View 🎨🧬⚓
     */
    public function render(array $analysis, Throwable $exception): bool
    {
        // 🔍 RBN 3.5: Code Snippet Extraction for high-fidelity diagnostic
        $analysis['snippet'] = $this->getCodeSnippet($analysis['file'], $analysis['line']);
        $analysis['branding'] = $this->getBranding();
        $analysis['now'] = $this->getNow();

        // 🎯 RBN 3.5: Masterpiece AJAX Diagnostic Support 🧬🛰️
        if ($this->isAjax()) {
            $this->renderJson($analysis, 500);
            return true;
        }

        // 🎯 RBN 3.5: Use Centralized Masterpiece Autonomous Renderer 🛡️🚀
        $analysis['exception'] = $exception;
        $analysis['isPreFlight'] = false;

        self::renderAutonomous('development', $analysis, 500);
        return true;
    }

    /**
     * Extracts lines surrounding the error for the UI 🕵️‍♂️⚓
     */
    private function getCodeSnippet(?string $file, ?int $line, int $padding = 8): ?array
    {
        if (!$file || !file_exists($file) || !$line) {
            return null;
        }

        try {
            $lines = file($file);
            $start = max(0, $line - $padding - 1);
            $end = min(count($lines), $line + $padding);

            $snippet = [];
            for ($i = $start; $i < $end; $i++) {
                $snippet[$i + 1] = $lines[$i];
            }

            return $snippet;
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * Extracts lines surrounding the error for the UI 🕵️‍♂️⚓
     */
    public static function renderFatal(string $errorType, string $errorMessage, string $solutionHint = '', int $httpCode = 500): void
    {
        $data = [
            'type' => $errorType,
            'message' => $errorMessage,
            'hint' => $solutionHint,
            'isPreFlight' => false
        ];

        // 🎯 RBN 3.5: Use Centralized Masterpiece Autonomous Renderer 🏛️🛡️
        self::renderAutonomous('pre_flight', $data, $httpCode);
    }

    /**
     * Emergency Terminal Output (No UI available) 🔇🆘
     */
    private function terminalRender(array $analysis): void
    {
        if (!headers_sent()) {
            header('HTTP/1.1 500 Internal Server Error');
            header('Content-Type: text/plain');
        }

        echo "[RBN CRITICAL FAILURE] " . ($analysis['message'] ?? 'Unknown Error') . PHP_EOL;
        echo "Location: " . ($analysis['file'] ?? 'unknown') . " on line " . ($analysis['line'] ?? 0);
        die();
    }
}
