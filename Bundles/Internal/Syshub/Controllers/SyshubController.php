<?php

declare(strict_types=1);

namespace Rbn\Framework\Bundles\Internal\Syshub\Controllers;

use Rbn\Framework\Core\Base\Web\BaseController;
use Rbn\Framework\Core\Base\Attributes\Module;
use Rbn\Framework\Bundles\Internal\Syshub\Models\ModuleData;

#[Module(
    name: 'syshub',
    data: ModuleData::class,
    service: 'syshub',
    panel: 'developer',
    context: 'panel'
)]
class SyshubController extends BaseController
{
    /**
     * SysHub Dashboard 📊
     */
    public function index(): void
    {
        $stats = $this->service->getDashboardStats();

        $this->render('Dashboard/index', [
            'stats' => $stats['system'],
            'security' => $stats['security'],
            'isMaintenance' => $stats['security']['is_maintenance'],
            'cacheStats' => $stats['storage']['cache'],
            'logStats' => $stats['storage']['logs'],
            'sessionStats' => $stats['storage']['sessions'],
            'totalClearableSize' => $stats['storage']['total_clearable_formatted'],
            'moduleCount' => $stats['module_count'],
            'frameworkVersion' => $stats['system']['framework_version']
        ]);
    }

    /**
     * Optimize PHP Limits 🚀
     */
    public function fixPhpLimits(): void
    {
        $result = $this->service->optimizePhpLimits();
        $this->handleResult($result, 'PHP Limits');
    }
}
