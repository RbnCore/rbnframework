<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Services\Exception\Handlers;

use Rbn\Framework\Core\Base\Services\BaseService;
use Rbn\Framework\Core\Support\Exceptions\PreflightException;
use Rbn\Framework\Core\Support\Exceptions\DiagnosticException;
use Rbn\Framework\Core\Services\Exception\Providers\PreflightProvider;
use Rbn\Framework\Core\Services\Exception\Providers\SurvivalProvider;
use Throwable;

/**
 * ExceptionHandler - The Global Error Sensor 🩺🛰️
 */
class ExceptionHandler
{
    /**
     * Entry Point: 4-Stage Hierarchy Logic (Panic -> Fatal -> Pre-flight -> Development) 🏺⚖️🛡️⚓
     */
    public function handle(Throwable $exception): void
    {
        try {
            // [LAYER 3] RBN Framework: Strategic Pre-flight Dispatch (Zero-Dependency) 🛡️🚀⚓
            // If the failure is at the boot/initialization level, we MUST bypass discovery.
            if ($exception instanceof PreflightException) {
                PreflightProvider::renderFatal(
                    $exception->getType(),
                    $exception->getMessage(),
                    $this->publicHint($exception->getHint()),
                    500
                );
                return;
            }

            // 1. Try to resolve the Orchestra Conductor (ExceptionService)
            $service = BaseService::get() ? BaseService::get()->service('exception') : null;

            if ($service) {
                // [LAYER 4] Development / App Standard
                $service->handle($exception);
                return;
            }

            // [LAYER 2] Fatal (Strategic Infrastructure / Discovery Failure) 🆘💀⚓
            if ($exception instanceof DiagnosticException) {
                SurvivalProvider::render(
                    $exception->getType(),
                    $exception->getMessage(),
                    $this->publicHint($exception->getHint()),
                    500
                );
                return;
            }

            // [LAYER 1] Panic (Survival / Kernel Collapse) 🆘🆘🛡️
            $this->survivalFallback($exception);

        } catch (Throwable $e) {
            $this->survivalFallback($exception, "Critical Orchestration Failure: " . $e->getMessage());
        }
    }

    /**
     * Layer 0: Final Bastion Logic 🛡️🆘
     * Used when the framework or orchestral hub crashes during boot.
     */
    private function survivalFallback(Throwable $exception, string $messagePrefix = ""): void
    {
        // 📓 RBN Framework: Black-box Recording (Panic Mode)
        LogHandler::failsafeLog([
            'type' => $messagePrefix ?: get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine()
        ], "Kernel Panic Sensor Triggered");

        // 🎭 Renders the "RBN Critical" Hardcoded Black Screen 🛡️💀
        SurvivalProvider::render(
            $messagePrefix ?: get_class($exception),
            $exception->getMessage(),
            $this->publicHint("Original Error at {$exception->getFile()} on line {$exception->getLine()}"),
            500
        );
    }

    /**
     * K-06: Çözüm ipucu ("composer install", tam yollar) yalnız yerel geliştirmede gösterilir.
     * RBN_DEV tanımsızsa (erken boot) kapalı sayılır: fail-closed.
     */
    private function publicHint(string $hint): string
    {
        if (defined('RBN_DEV') && RBN_DEV === true) {
            return $hint;
        }
        return 'Ayrıntılar sistem günlüğüne yazıldı. Sorun sürerse sistem yöneticisine başvurun.';
    }
}
