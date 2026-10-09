<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\System\Kernel\Stages;

use Rbn\Framework\Core\System\Kernel\Kernel;
use Rbn\Framework\Core\System\Kernel\Base\BaseStage;
use Rbn\Framework\Core\Base\Services\BaseService;

/**
 * DatabaseGuardStage - The Consolidated Security & Environment Gatekeeper 👨‍🍳🛡️⚓
 * 
 * RBN Framework: Architecture.
 * Replaces legacy EnvDiscovery and ProjectDiscovery stages.
 * Delegates all responsibility to the DatabaseGuardService.
 */
class DatabaseGuardStage extends BaseStage
{
    public function handle(Kernel $kernel): void
    {
        $guard = $this->getStageService()->service('databaseGuard');

        // 🎼 Orchestrate full diagnostic and environment loading 🏛️⚖️🛡️⚓
        $guard->check();
    }
}
