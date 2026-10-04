<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Services\Exception\Providers;

use Rbn\Framework\Core\Services\Exception\Providers\Base\BaseExceptionProvider;

/**
 * PreflightProvider - System Initialization & Diagnostic Organ 🛰️⚓
 * 
 * RBN 3.5: [LAYER 1] Masterpiece Standard.
 * Extends BaseExceptionProvider for symmetric service access.
 * Handles specialized Doctor-Diagnostics UI for early-boot failures.
 */
class PreflightProvider extends BaseExceptionProvider
{
    /**
     * Standard Render: Mapped to the Autonomous Fatal Path 🎭🛡️
     */
    public function render(string $view, array $data = []): void
    {
        // 🎯 RBN 3.5: Masterpiece AJAX Diagnostic Support
        if ($this->isAjax()) {
            // FW-KARAR-2 / Z-1: uretimde ham mesaj/dosya/satir gizlenir
            // (HTML yolu `renderFatal()` icinde zaten gizliyordu).
            if (!(defined('RBN_DEV') && RBN_DEV === true)) {
                $data = self::redactForPublicOutput($data, 'Sistem şu an başlatılamıyor.');
            }
            $this->renderJson($data, 500);
            return;
        }

        // Unified Autonomous Landing Path
        self::renderFatal(
            $data['type'] ?? 'Pre-flight Hata',
            $data['message'] ?? 'Tanımlanamayan bir sarsılma algılandı.',
            $data['hint'] ?? 'Sistem ayarlarını kontrol edin.'
        );
    }

    /**
     * Ultimate Failsafe: Direct Brute-Force Rendering 🛡️⚓
     * Bypasses Service Hub for catastrophic early errors.
     */
    public static function renderFatal(string $errorType, string $errorMessage, string $solutionHint = '', int $httpCode = 500): void
    {
        // K-06: ipucu ("composer install", tam yollar) ve ham mesaj yalniz yerel gelistirmede
        // gosterilir; RBN_DEV tanimsizsa (erken boot) kapali sayilir. Ayrinti sistem gunlugune.
        if (!(defined('RBN_DEV') && RBN_DEV === true)) {
            @error_log('[Preflight] ' . $errorType . ': ' . $errorMessage . ' | ' . $solutionHint);
            $errorMessage = 'Sistem şu an başlatılamıyor.';
            $solutionHint = 'Ayrıntılar sistem günlüğüne yazıldı. Sorun sürerse sistem yöneticisine başvurun.';
        }

        // Preparation of the diagnostic payload
        $data = [
            'type' => $errorType,
            'message' => $errorMessage,
            'hint' => $solutionHint,
            'isPreFlight' => true
        ];

        // 🎯 RBN 3.5: Use Centralized Masterpiece Autonomous Renderer 🏛️🛡️
        self::renderAutonomous('pre_flight', $data, $httpCode);
    }
}
