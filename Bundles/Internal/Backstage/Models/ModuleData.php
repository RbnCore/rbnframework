<?php

namespace Rbn\Framework\Bundles\Internal\Backstage\Models;

use Rbn\Framework\Core\Base\Data\BaseConfig;
use Rbn\Framework\Core\Routes\Route;
use Rbn\Framework\Core\Base\Attributes\Bundle;
use Rbn\Framework\Bundles\Internal\Backstage\Models\BackstageMap;

/**
 * ModuleData - Backstage Bundle DNA & Orchestration Center 🛡️🛰️🏛️⚓
 * 
 * RBN 3.5 Masterpiece Standard.
 * Centralizes identity, orchestration map, and sovereign routing.
 */
#[Bundle(
    name: 'backstage',
    context: 'developer',
    map: BackstageMap::MAP
)]
class ModuleData extends BaseConfig
{
    /**
     * Component Registration Map (Service/Handler/Provider) 🏛️⚓
     */
    public function registerMap(): array
    {
        return [
            'services' => [
                'backstage' => \Rbn\Framework\Bundles\Internal\Backstage\Services\BackstageService::class,
                'sidebar' => \Rbn\Framework\Bundles\Internal\Backstage\Services\SidebarService::class,
            ],
            'handlers' => [
                'sidebar' => \Rbn\Framework\Bundles\Internal\Backstage\Handlers\SidebarHandler::class,
            ],
            'providers' => [
                'backstage' => \Rbn\Framework\Bundles\Internal\Backstage\Providers\BackstageProvider::class,
                'CrudSettingsProvider' => \Rbn\Framework\Bundles\Internal\Backstage\Providers\CrudSettingsProvider::class,
                'sidebar' => \Rbn\Framework\Bundles\Internal\Backstage\Providers\SidebarProvider::class,
            ]
        ];
    }

    /**
     * Sovereign Route Registration 🎯🛰️
     * 🎼 RBN 3.5: Enabling autonomous URI mapping for all sub-modules.
     */
    public function registerRoutes(): void
    {
        // 🎼 Dashboard & Root Gateway
        Route::prefix('backstage')->controller('BackstageController')->group(function () {
            Route::get('/', 'index')->name('backstage');
            Route::get('dashboard', 'index');
        });

        // 🎼 Sidebar Management (Atomic & Sovereign)
        Route::prefix('backstage/sidebar')->controller('SidebarController')->group(function () {
            Route::get('/', 'index');
            Route::get('modal/{id?}', 'modal');

            // 🎯 Sovereign Categories (RBN 3.5 Standard: toggle/reorder)
            Route::prefix('category')->group(function () {
                Route::post('save', 'categorySave');
                Route::post('delete/{id}', 'delete');
                Route::post('toggle', 'status');
                Route::post('reorder', 'bulkOrder');
            });

            // 🎯 Sovereign Menus (RBN 3.5 Standard: toggle/reorder)
            Route::prefix('menu')->group(function () {
                Route::post('save', 'menuSave');
                Route::post('delete/{id}', 'delete');
                Route::post('toggle', 'status');
                Route::post('reorder', 'bulkOrder');
            });
        });

        // 🎼 Email Configuration Hub
        Route::prefix('backstage/email')->controller('EmailController')->group(function () {
            Route::get('/', 'index');
            Route::get('manage', 'manage');
            Route::post('update', 'updateMailSettings');
            Route::post('test-mail', 'testMail');
            Route::post('reorder', 'bulkOrder');
            Route::post('toggle', 'status');
            Route::get('modal/?([0-9]*)', 'modal');
            Route::post('store', 'create');
            Route::post('update-structural', 'update');
            Route::post('delete/{id}', 'delete');
        });

        // 🎼 Settings Architect
        Route::prefix('backstage/settings')->controller('SettingsArchitectController')->group(function () {
            Route::get('/', 'index');
            Route::get('group/{id}', 'group');

            // ⚙️ Group Management
            Route::post('createGroup', 'createGroup');
            Route::post('updateGroup', 'updateGroup');
            Route::post('group/toggle', 'toggleGroup');
            Route::post('group/delete/{id}', 'deleteGroup');

            // ⚙️ Setting Management
            Route::get('modal/?([0-9]*)', 'modal');
            Route::post('createSetting', 'createSetting');
            Route::post('updateSetting', 'updateSetting');
            Route::post('status', 'status');
            Route::post('delete/{id}', 'delete');
            Route::post('sort', 'bulkOrder');
        });

    }
}
