<?php
declare(strict_types=1);

namespace Rbn\Framework\Core\Services\Gatekeepers;

use Rbn\Framework\Core\Base\Services\BaseService;
use Rbn\Framework\Core\Support\Contracts\Base\BaseServiceInterface;

/**
 * SystemGuardService - The Master Access Sentinel 🛡️🛰️🪐
 * 
 * RBN Framework: Standard Orchestrator. 🎻🛰️
 * [SYMMETRIC LAZY DISCOVERY] 🏛️✨
 * 
 * @property-read \Rbn\Framework\Core\Services\Gatekeepers\Handlers\SystemGuardHandler $systemGuardHandler
 */
class SystemGuardService extends BaseService implements BaseServiceInterface
{
    /**
     * Boot the Guard ⚓
     * Pre-wakes satellites via magic property access 🪄🛰️
     */
    public function boot(): void
    {
        $this->systemGuardHandler;
    }

    public function check(): void
    {
        // 1. Logic check (Handler) ⚖️
        if ($this->systemGuardHandler->isAccessDenied()) {
            
            // 2. Presentation check (Direct Shield Diagnostic) 🎭
            $message = $this->systemGuardHandler->getMaintenanceMessage();
            $this->shield->diagnostic(
                'Sistem Bakımdadır 🛠️',
                $message,
                'Şu an planlı bakım çalışması yapıyoruz. Birkaç dakika içinde tekrar deneyebilirsiniz.'
            );
        }
    }
}
