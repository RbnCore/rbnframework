<?php

declare(strict_types=1);

namespace Rbn\Framework\Bundles\Internal\Syshub\Controllers\DbConsole;

use Rbn\Framework\Core\Base\Attributes\SubModule;
use Rbn\Framework\Bundles\Internal\Syshub\Controllers\SyshubController;

/**
 * DbConsoleController - Database Management Orchestrator 🛠️🛰️⚓
 * RBN Framework Standard.
 */
#[SubModule(
    entity: 'database',
    service: 'dbConsole'
)]
class DbConsoleController extends SyshubController
{
    /**
     * Dashboard Index 📊
     */
    public function index(): void
    {
        $metrics = $this->service->getInfo();
        $tables = $this->service->getTables();

        $this->render('DbConsole/index', [
            'dbInfo' => $metrics,
            'tables' => $tables
        ]);
    }

    /**
     * Seeding initial admin account 👥
     */
    public function seedAdmin(): void
    {
        $result = $this->service->seedAdmin();
        $this->handleResult($result, 'Admin hesabı', null, 'create');
    }

    /**
     * Performs a clean system reinstall 🧹
     */
    public function cleanInstall(): void
    {
        $result = $this->service->cleanInstall();
        $this->handleResult($result, 'Sistem tabloları başarıyla temizlendi ve stabilize edildi.');
    }

    /**
     * DB Optimization 🚀
     */
    public function optimize(): void
    {
        $result = $this->service->optimize();
        $this->handleResult($result, 'Veritabanı optimizasyonu.');
    }

    /**
     * Global Collation Conversion 🔠
     */
    public function convertAllCollations(): void
    {
        $results = $this->service->changeCollation('*', 'utf8mb4_unicode_ci');
        $this->handleResult($results, "Tüm tablolar başarıyla utf8mb4_unicode_ci'ye dönüştürüldü.");
    }
}
