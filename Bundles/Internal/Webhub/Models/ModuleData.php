<?php

declare(strict_types=1);

namespace Rbn\Framework\Bundles\Internal\Webhub\Models;

use Rbn\Framework\Core\Base\Data\BaseConfig;
use Rbn\Framework\Core\Routes\Route;
use Rbn\Framework\Bundles\Internal\Webhub\Models\WebhubMap;
use Rbn\Framework\Core\Base\Attributes\Bundle;

/**
 * ModuleData - Webhub Orchestrator & Discovery Engine 🧩🛡️⚓
 * RBN Framework Standard.
 */
#[Bundle(
    name: 'webhub',
    context: 'developer',
    map: WebhubMap::MAP
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
                'webhub' => \Rbn\Framework\Bundles\Internal\Webhub\Services\WebhubService::class,
                'seoScanner' => \Rbn\Framework\Bundles\Internal\Webhub\Services\SeoScannerService::class,
                'frontendMenu' => \Rbn\Framework\Bundles\Internal\Webhub\Services\FrontendMenuService::class,
                'policy' => \Rbn\Framework\Bundles\Internal\Webhub\Services\PolicyService::class,
                'faq' => \Rbn\Framework\Bundles\Internal\Webhub\Services\FaqService::class,
                'integrations' => \Rbn\Framework\Bundles\Internal\Webhub\Services\IntegrationsService::class,
            ],
            'handlers' => [
                'seo.settings' => \Rbn\Framework\Bundles\Internal\Webhub\Handlers\SettingsScannerHandler::class,
                'seo.core' => \Rbn\Framework\Bundles\Internal\Webhub\Handlers\CoreScannerHandler::class,
                'seo.dom' => \Rbn\Framework\Bundles\Internal\Webhub\Handlers\DomScannerHandler::class,
                'seo.advice' => \Rbn\Framework\Bundles\Internal\Webhub\Handlers\AdviceHandler::class,
            ],
            'providers' => [
                'webhub' => \Rbn\Framework\Bundles\Internal\Webhub\Providers\WebhubProvider::class,
                'frontendMenu' => \Rbn\Framework\Bundles\Internal\Webhub\Providers\FrontendMenuProvider::class,
                'policy' => \Rbn\Framework\Bundles\Internal\Webhub\Providers\PolicyProvider::class,
            ],
        ];
    }

    /**
     * RBN Framework CUSTOM ROUTES 🎯
     * Modül altındaki tüm alt birimlerin özel rotalarını buraya topluyoruz.
     */
    public function registerRoutes(): void
    {
        // 1. Dashboard
        Route::prefix('webhub')->controller('WebhubController')->group(function () {
            Route::get('/', 'index')->name('webhub');
        });

        // 2. Identity & Brand
        Route::prefix('webhub/identity')->controller('IdentityController')->group(function () {
            Route::get('/', 'index');
            Route::get('manage', 'manage');
            Route::get('modal/{id?}', 'modal');
            Route::post('create', 'create');
            Route::post('save', 'update');
            Route::post('toggle', 'status');
            Route::post('delete/{id}', 'delete');
            Route::post('reorder', 'bulkOrder');
            Route::post('bulkValueUpdate', 'bulkValueUpdate');
        });

        // 3. SEO Settings
        Route::prefix('webhub/seo')->controller('SeoController')->group(function () {
            Route::get('/', 'index');
            Route::get('manage', 'manage');

            // 🚀 RBN Framework: SEO Score Analyzer Routes
            Route::get('score', 'score');
            Route::get('report', 'report');
            Route::post('scan', 'scan');

            Route::get('modal/{id?}', 'modal');
            Route::post('create', 'create');
            Route::post('save', 'update');
            Route::post('toggle', 'status');
            Route::post('delete/{id}', 'delete');
            Route::post('reorder', 'bulkOrder');
            Route::post('bulkValueUpdate', 'bulkValueUpdate');
        });
        // 4. Navigation
        Route::prefix('webhub/navigation')->controller('NavigationController')->group(function () {
            Route::get('/', 'index');
            Route::get('modal/{id?}', 'modal');
            Route::post('save', 'save');
            Route::post('toggle', 'status');
            Route::post('delete/{id}', 'delete');
            Route::post('reorder', 'bulkOrder');
        });

        // 5. Pages & Policy Manager
        Route::prefix('webhub/policy')->controller('PolicyController')->group(function () {
            Route::get('/', 'index');
            Route::get('modal/{id?}', 'modal');
            Route::post('save', 'save');
            Route::post('status', 'status');
            Route::post('delete/{id}', 'delete');
            Route::post('reorder', 'bulkOrder');
        });

        // 6. FAQ Engine & Schema Management
        Route::prefix('webhub/faq')->controller('FaqController')->group(function () {
            Route::get('/', 'index');
            Route::get('modal/{id?}', 'modal');
            Route::post('save', 'save');
            Route::post('status', 'status');
            Route::post('delete/{id}', 'destroy');
            Route::post('reorder', 'bulkOrder');
        });

        // 7. Integrations (Scripts & Tags)
        Route::prefix('webhub/integrations')->controller('IntegrationsController')->group(function () {
            Route::get('/', 'index');
            Route::get('adsense', 'adsense');
            Route::post('setupadsense', 'setupAdsense');
            Route::get('manage', 'manage');
            Route::post('create', 'create');
            Route::post('save', 'save');
            Route::post('toggle', 'status');
            Route::post('delete/{id}', 'delete');
            Route::post('reorder', 'bulkOrder');
            Route::post('bulkvalueupdate', 'bulkValueUpdate');
        });
    }
}
