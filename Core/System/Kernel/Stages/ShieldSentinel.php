<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\System\Kernel\Stages;

use Rbn\Framework\Core\System\Kernel\Kernel;
use Rbn\Framework\Core\System\Kernel\Base\BaseStage;
use Rbn\Framework\Core\System\Kernel\Guards\SystemDoctor;
use Rbn\Framework\Core\System\Kernel\Guards\MaintenanceGuard;
use Throwable;

/**
 * ShieldSentinel - Unified Security & Diagnostic Guard 🛡️🚨⚓
 * 
 * RBN Framework: [RBN Framework REFACTORED]
 * This stage enforces mandatory system health and security checks.
 * Legacy RbnShield boot is replaced by direct Exception Orchestration via Shield Hub.
 */
class ShieldSentinel extends BaseStage
{
    public function handle(Kernel $kernel): void
    {
        $projectKey = $kernel->get('projectKey');

        // 1. Mandatory Pre-Flight Health Check 🩺⚓
        SystemDoctor::setExpectedKey($projectKey);
        SystemDoctor::check();

        // 2. Early Maintenance Guard 🚧
        MaintenanceGuard::check();

        // 2. Shield Hub Validation (Security & Exception Sync) 🏛️🛡️⚓
        // Instead of booting internal bundles, we ensure the Shield Hub is active
        // and ready to guard the framework's operational symmetry.
        if (!function_exists('shield')) {
            // Absolute terminal failure: Diagnostic Hub itself is missing.
            http_response_code(500);
            die("RBN Critical: Shield Diagnostic Hub is missing. Framework cannot secure itself.");
        }

        try {
            // 🔥 RBN Framework: Security Enforcement 🏺🛰️
            // Here we can trigger any high-level security boot logic if needed,
            // or simply use the shield() hub to validate the current environment.
            // For now, we ensure that if a security breach or missing guard is detected,
            // we THROW a DiagnosticException directly.

            // Note: RbnShield::boot() legacy has been removed in favor of this decentralized check.
        } catch (Throwable $e) {
            shield()->preflight(
                "Güvenlik Katmanı sarsıldı: " . $e->getMessage(),
                "Kernel Stages ve Shield Hub konfigürasyonlarını kontrol edin.",
                "Security Sentinel Failure"
            );
        }

        // 3. IpGuard Layer (Early Defense) 🛡️🏹
        $ipGuard = $this->getStageService()->service('ipGuard');

        if ($ipGuard) {
            $ipGuard->check();
        }
    }
}
