<?php

declare(strict_types=1);

namespace Rbn\Framework\Bundles\Internal\Syshub\Models;

use Rbn\Framework\Core\Routes\Route;
use Rbn\Framework\Core\Base\Data\BaseConfig;
use Rbn\Framework\Core\Base\Attributes\Bundle;

/**
 * ModuleData - SysHub Orchestrator & Discovery Engine 🧩🛡️⚓
 * RBN Framework Standard.
 */
#[Bundle(
    name: 'syshub',
    context: 'developer',
    map: SyshubMap::MAP
)]
class ModuleData extends BaseConfig
{

    /**
     * CENTRALIZED REGISTRATION MAP 🏛️⚓🛰️
     */
    public function registerMap(): array
    {
        return [
            'services' => [
                'syshub' => \Rbn\Framework\Bundles\Internal\Syshub\Services\SyshubService::class,
                'dbConsole' => \Rbn\Framework\Bundles\Internal\Syshub\Services\SyshubDbConsoleService::class,
            ],
            'handlers' => [
                'syshub' => \Rbn\Framework\Bundles\Internal\Syshub\Handlers\SyshubHandler::class,
                'purge' => \Rbn\Framework\Bundles\Internal\Syshub\Handlers\DataPurgeHandler::class,
                'syshubSecurity' => \Rbn\Framework\Bundles\Internal\Syshub\Handlers\SyshubSecurityHandler::class,
                'syshubDbConsole' => \Rbn\Framework\Bundles\Internal\Syshub\Handlers\SyshubDbConsoleHandler::class,
            ],
            'providers' => [
                'syshub' => \Rbn\Framework\Bundles\Internal\Syshub\Providers\SyshubProvider::class,
                'firewall' => \Rbn\Framework\Bundles\Internal\Syshub\Providers\SyshubFirewallProvider::class,
                'rateLimit' => \Rbn\Framework\Bundles\Internal\Syshub\Providers\SyshubRateLimitProvider::class,
                'whitelist' => \Rbn\Framework\Bundles\Internal\Syshub\Providers\SyshubWhitelistProvider::class,
                'maintenance' => \Rbn\Framework\Bundles\Internal\Syshub\Providers\SyshubMaintenanceProvider::class,
                'syshubCron' => \Rbn\Framework\Bundles\Internal\Syshub\Providers\SyshubCronProvider::class,
                'syshubDbConsole' => \Rbn\Framework\Bundles\Internal\Syshub\Providers\SyshubDbConsoleProvider::class,
            ],
        ];
    }

    /**
     * RBN Framework CUSTOM ROUTES 🎯
     * Modül altındaki tüm alt birimlerin özel rotalarını buraya topluyoruz.
     */
    public function registerRoutes(): void
    {
        // 🎼 1. HUB DASHBOARD & CORE ACTIONS 🚀
        Route::prefix('syshub')->controller('SyshubController')->group(function () {
            Route::get('/', 'index')->name('syshub');
            Route::get('fix-php-limits', 'fixPhpLimits');
        });

        // 🎼 2. DATA PURGE (Önbellek, Log, Oturum Temizliği) 🧹⚓
        Route::prefix('syshub/datapurge')->controller('PurgeController')->group(function () {
            // Main Dashboard & Actions
            Route::get('/', 'index');
            Route::post('clearAll', 'clearAll');
            Route::post('clear/{type}', 'clear');
            Route::post('clear/{type}/{resource}', 'clear');

            Route::get('{type}', 'module');
            Route::get('{type}/show', 'show');
            Route::get('{type}/view/{path:.+}', 'show'); // Compatibility for logs
            Route::get('{type}/download', 'download');
            Route::get('{type}/download/{path:.+}', 'download');

            // Unified Actions
            Route::post('delete/{type}/{path:.+}', 'bulkDelete');
            Route::post('bulkDelete/{type}', 'bulkDelete');
            Route::post('bulkDownload/{type}', 'bulkDownload');
            Route::post('{type}/delete/{path:.+}', 'bulkDelete'); // Compatibility for single delete
            Route::post('{type}/bulkDelete', 'bulkDelete');
            Route::post('{type}/bulkDownload', 'bulkDownload');
        });

        // 🎼 3. DB CONSOLE & EXPLORER (Veritabanı Yönetimi) 🗄️🛰️⚓
        Route::prefix('syshub/dbconsole')->group(function () {
            // Main Console
            Route::controller('DbConsole\DbConsoleController')->group(function () {
                Route::get('/', 'index');
                Route::post('cleanInstall', 'cleanInstall');
                Route::post('seedAdmin', 'seedAdmin');
                Route::post('optimize', 'optimize');
                Route::post('convertAllCollations', 'convertAllCollations');
            });

            // SQL Console
            Route::prefix('console')->controller('DbConsole\SqlConsoleController')->group(function () {
                Route::get('/', 'index');
                Route::post('execute', 'executeQuery');
                Route::post('getDatabaseInfo', 'getDatabaseInfo');
                Route::post('getTableList', 'getTableList');
                Route::post('getTableData', 'getTableData');
            });

            // Table Explorer
            Route::prefix('tables')->controller('DbConsole\TableExplorerController')->group(function () {
                Route::get('/', 'index');
                Route::get('browse', 'browse');
                Route::post('deleteRow', 'deleteRow');
                Route::post('bulkDeleteRows', 'bulkDeleteRows');
                Route::post('bulkExportRows', 'bulkExportRows');
                Route::post('dropTable', 'dropTable');
                Route::post('exportSQL', 'exportSQL');
                Route::post('changeCollation', 'changeCollation');

                // Bulk Actions matching views
                Route::post('bulk-export-sql', 'bulkExportSQL');
                Route::post('bulk-export-zip', 'bulkExportZip');
                Route::post('bulk-drop-tables', 'bulkDropTables');
            });
        });

        // 🎼 4. SECURITY & SHIELD (IP & Rate Limit) 🛡️⚓
        Route::prefix('syshub/security')->group(function () {
            // 🛡️ Firewall & Core Security Settings
            Route::prefix('firewall')->controller('Security\FirewallController')->group(function () {
                Route::get('/', 'index');
                Route::post('updatesetting', 'updatesetting');
            });

            Route::controller('Security\SecurityController')->group(function () {
                Route::get('/', 'index');
                Route::post('save', 'update');
            });

            Route::prefix('ratelimits')->controller('Security\RateLimitsController')->group(function () {
                Route::get('/', 'index');
                Route::post('delete-ip/{id}', 'deleteIP');
                Route::post('bulk-delete-ip', 'bulkDeleteIP');
                Route::post('clear', 'clearAll');
            });

            Route::prefix('ipblock')->controller('Security\IpBlockController')->group(function () {
                Route::get('/', 'index');
                Route::get('modal/{id?}', 'modal');
                Route::post('create', 'store');
                Route::post('delete/{id}', 'delete');
                Route::post('clearAll', 'clearAll');
            });

            Route::prefix('ipwhitelist')->controller('Security\IpWhitelistController')->group(function () {
                Route::get('/', 'index');
                Route::get('modal/{id?}', 'modal');
                Route::post('create', 'store');
                Route::post('delete/{id}', 'delete');
            });
        });

        // 🎼 5. MAINTENANCE GUARD (Bakım Modu) 🚧🛡️
        Route::prefix('syshub/maintenance')->controller('MaintenanceController')->group(function () {
            Route::get('/', 'index');
            Route::get('modal/{id?}', 'modal');
            Route::post('status', 'status');
            Route::post('toggle', 'status');
            Route::post('save', 'update');
            Route::post('create', 'store');
            Route::post('update', 'update');
            Route::post('delete/{ip}', 'destroy');
        });
    }
}
