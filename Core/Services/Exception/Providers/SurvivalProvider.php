<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Services\Exception\Providers;

use Rbn\Framework\Core\Support\Definitions\System\FrameworkIdentity;
use Rbn\Framework\Core\Services\Exception\Providers\Base\BaseExceptionProvider;
use Rbn\Framework\Core\Services\Exception\Data\ShieldMetadata;

/**
 * SurvivalProvider - The Final Bastion UI 🛡️💀
 * 
 * RBN Framework: [LAYER 0 / 2] RBN Framework Standard.
 * Standardized Unified Rendering for Critical Failures.
 */
class SurvivalProvider extends BaseExceptionProvider
{
    /**
     * Renders the "RBN Critical" Standardized View 🛡️💀
     */
    public static function render(string $type, string $message, string $hint = '', int $code = 500): void
    {
        // FW-KARAR-2 / Z-1: Uretimde ham istisna mesaji/dosya/satir ve istisna
        // SINIFI ADI gizlenir; genel metin + hata kimligi gosterilir, ayrinti
        // ayni kimlikle mevcut hata kanalina yazilir. Gelistirmede AYNEN kalir.
        if (!(defined('RBN_DEV') && RBN_DEV === true)) {
            $veri = self::redactForPublicOutput(
                ['type' => $type, 'message' => $message, 'hint' => $hint],
                'Sistem şu an kullanılamıyor.',
                self::GENERIC_HINT,
                true
            );
            $type = $veri['type'];
            $message = $veri['message'];
            $hint = $veri['hint'];
        }

        // 1. Branding Synchronization (SSoT) 🏺⚓
        $shieldName    = FrameworkIdentity::SHIELD_NAME;
        $shieldVersion = FrameworkIdentity::SHIELD_VERSION;

        // 2. Precise Timing (date()) 🕰️
        $now = date('Y-m-d H:i:s');

        // 3. Prepare Copy Data (JSON formatted for Diagnostic Bridge) 📼
        $cleanMsg = strip_tags($message);
        $cleanHint = strip_tags($hint);
        $copyText = "=== {$shieldName} CRITICAL ERROR ===\nTIME: $now\nTYPE: $type\nMSG: $cleanMsg\nHINT: $cleanHint\n===========================";

        // 4. Diagnostic Payload
        $data = [
            'shieldName' => $shieldName,
            'shieldVersion' => $shieldVersion,
            'type' => $type,
            'message' => $message,
            'hint' => $hint,
            'now' => $now,
            'copyText' => $copyText
        ];

        // 🎯 RBN Framework: Use Centralized RBN Framework Autonomous Renderer 🏛️🛡️
        self::renderAutonomous('survival', $data, $code);
    }

    /**
     * Internal AJAX Detection (Inherited from Parent - Redirect for clarity) 🛰️🧠
     */
    public static function checkAjax(): bool
    {
        return (new self)->isAjax();
    }
}
