<?php

namespace Rbn\Framework\Core\System\Kernel\Stages;

use Rbn\Framework\Core\System\Kernel\Kernel;
use Rbn\Framework\Core\System\Kernel\Base\BaseStage;
use Rbn\Framework\Core\Base\Services\BaseService;
use Rbn\Framework\Core\System\Registries\SystemRegistry;
use Rbn\Framework\Core\Routes\Route;

/**
 * Routing - Routes, Maintenance and Redirects.
 */
class Routing extends BaseStage
{
    public function handle(Kernel $kernel): void
    {
        // 1. Load Project Routes 🛣️
        // Ana proje rotaları her zaman önceliklidir (Örn: Ana Sayfa).
        Route::loadRoutes();

        // 2. Autonomous Module Route Discovery 🛰️⚓
        $moduleService = BaseService::get()->service('module');
        if (!$moduleService) {
            // [S-11] Modul servisi cozulemezse framework paket rotalari
            // (`SystemRegistry::sovereignBundles()`, RbnAdmin vb.) ve proje modul
            // rotalari HICBIR KAYIT OLMAZDI — asama sessizce biterdi. Sonuc:
            // panel rotalari kaybolur, teshis imkansiz. Artik gunluga yazilir.
            // Istek yine DUSURULMEZ (fail-soft): framework rotalari
            // (`Route::loadRoutes()`) zaten yuklendi.
            error_log('[RBN-KERNEL] Routing: modul servisi cozulemedi — paket/proje rotalari yuklenmedi.');
        }
        if ($moduleService) {
            // 🎼 [RBN Framework ROUTES] - Framework Core Bundles (RbnAdmin etc.)
            $moduleService->registerBundles('routes', SystemRegistry::sovereignBundles());

            // 🎼 [PROJECT ROUTES] - Modules in the current project
            $moduleService->registerBundles('routes');
        }
    }
}
