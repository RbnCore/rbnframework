<?php
declare(strict_types=1);

namespace Rbn\Framework\Core\System\Kernel\Guards;

use Rbn\Framework\Core\System\Kernel\Base\BaseGuard;

/**
 * MaintenanceGuard - Core System State Guard 🛡️🚧
 */
class MaintenanceGuard extends BaseGuard
{

    /**
     * Executes the maintenance check via SystemGuardService. 🛰️🚧
     */
    public static function check(): void
    {
        $guard = self::getGuardService()->service('systemGuard');

        // 🎼 RBN Framework: Delegation 🏛️⚖️🛡️
        // The guard no longer has its own logic. It simply asks the Sentinel.
        $guard->check();
    }
}
