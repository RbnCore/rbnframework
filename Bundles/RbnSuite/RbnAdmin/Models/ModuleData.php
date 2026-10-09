<?php

namespace Rbn\Framework\Bundles\RbnSuite\RbnAdmin\Models;

use Rbn\Framework\Core\Base\Data\BaseConfig;

use Rbn\Framework\Bundles\RbnSuite\RbnAdmin\Models\PanelMap;
use Rbn\Framework\Core\Routes\Route;
use Rbn\Framework\Bundles\RbnSuite\RbnAdmin\Services;
use Rbn\Framework\Bundles\RbnSuite\RbnAdmin\Providers;

use Rbn\Framework\Core\Base\Attributes\Bundle;

/**
 * ModuleData - RbnAdmin Paket Kimliği ve Mimari Veri Merkezi 🛡️🛰️🏛️⚓
 * RBN Framework Standard.
 * 
 * Bu sınıf paketin tüm kayıtlarını (Service, Handler, Provider) yönetirken;
 * Veriyi PanelIdentity (Soul) ve PanelMap (Skeleton) üzerinden orkestre eder.
 */
#[Bundle(
    name: 'rbnadmin',
    context: 'panel',
    map: PanelMap::MAP
)]
class ModuleData extends BaseConfig
{

    /**
     * CENTRALIZED REGISTRATION MAP 🏛️⚓🛰️
     * RBN Framework: Single source of truth for all bundle components.
     */
    public function registerMap(): array
    {
        return [
            'services' => [
                'analytics' => Services\AnalyticsService::class,
                'socialmedia' => Services\RbnAdminService::class,
                'rbnAdmin' => Services\RbnAdminService::class,
            ],
            'providers' => [
                'analytics' => Providers\AnalyticsProvider::class,
                'social' => Providers\SocialMediaProvider::class,
            ],
        ];
    }

    /**
     * RBN Framework CUSTOM ROUTES 🎯
     * Modül altındaki tüm alt birimlerin özel rotalarını buraya taşıyoruz.
     */
    public function registerRoutes(): void
    {
        // 🎼 Dashboard & Appearance RBN Framework Routes
        Route::controller('RbnAdminController')->group(function () {
            Route::get('/', 'adminIndex');
            Route::get('dashboard', 'adminIndex');
            Route::get('switch-project/{key}', 'switchProject');
            Route::post('theme/save', 'saveTheme');
        });

        // 🎼 WebTraffic & Analytics RBN Framework Routes
        Route::prefix('webtraffic')->controller('WebtrafficController')->group(function () {
            Route::get('/', 'index');
            Route::get('logs', 'logs');
            Route::get('report', 'report');
            Route::get('google-analytics', 'googleAnalytics');
            Route::get('google-analytics/map', 'googleAnalyticsMap');
        });

        // 📧 HostMailHub - RBN Framework Email Management Routes
        Route::prefix('hostmailhub')->controller('HostmailhubController')->group(function () {
            Route::get('/', 'index');
            Route::get('modal/{id?}', 'modal');
            Route::post('create', 'create');
            Route::post('delete/{email?:[a-zA-Z0-9_\.-]+}', 'delete');
            Route::post('change-password', 'changePassword');
            Route::get('eternalLink', 'eternalLink');
        });

        // 🔍 SEO Intelligence RBN Framework Routes
        Route::prefix('seo-report')->controller('SeoReportController')->group(function () {
            Route::get('/', 'index');
        });

        // 1. Unified User Management, Profile & Activities Routes
        Route::prefix('users')->controller('UserManagementController')->group(function () {
            // User List & Actions
            Route::get('/', 'index');
            Route::get('modal/?([0-9]*)', 'modal');
            Route::post('store', 'store');
            Route::post('update/([0-9]+)', 'update');
            Route::post('password/([0-9]+)', 'password');
            Route::post('delete/([0-9]+)', 'delete');
            Route::post('update-role', 'updateRole');

            // Personal Profile
            Route::get('profile', 'profile');
            Route::post('profile/update', 'profileUpdate');
            Route::post('profile/password', 'profilePassword');

            // Audit Logs
            Route::get('activities', 'activities');
            Route::post('activities/clear', 'clearActivities');
        });

        // 4. Admin Settings (Hybrid Engine)
        Route::prefix('settings')->controller('AdminSettingsController')->group(function () {
            Route::get('/?([a-zA-Z0-9_-]*)', 'index');
            Route::post('save', 'update');
        });

        // 🤖 Bot & API Settings Options (wp_options style)
        Route::prefix('bot-settings')->controller('BotSettingsController')->group(function () {
            Route::get('/', 'index')->name('admin.bot-settings.index');
            Route::get('apis', 'apis')->name('admin.bot-settings.apis');
            Route::get('tasks', 'tasks')->name('admin.bot-settings.tasks');
            Route::post('save', 'save')->name('admin.bot-settings.save');
            Route::post('create', 'create')->name('admin.bot-settings.create');
            Route::get('modal', 'modal')->name('admin.bot-settings.modal');
            Route::post('status', 'status')->name('admin.bot-settings.status');
            Route::get('ai-usage', 'aiUsage')->name('admin.bot-settings.ai-usage');
            Route::post('ai-usage/clear', 'clearAiUsage')->name('admin.bot-settings.ai-usage-clear');
        });

        // ⏱️ Cron & CronLogs RBN Framework Routes
        Route::prefix('cron')->controller('CronLogsController')->group(function () {
            Route::get('/', 'cron')->name('admin.cron.index');
            Route::post('save', 'saveCron')->name('admin.cron.save');
            Route::post('toggle-cron', 'toggleCron')->name('admin.cron.toggle-cron');
            Route::get('cronlogs', 'index')->name('admin.cronlogs.index');
            Route::get('view', 'view')->name('admin.cronlogs.view');
            Route::post('delete', 'deleteFile')->name('admin.cronlogs.delete');
            Route::post('clear', 'clear')->name('admin.cronlogs.clear');
        });

        // 5. Communication: Contacts 📩
        Route::prefix('contact')->controller('ContactController')->group(function () {
            Route::get('/?([a-zA-Z0-9_-]*)', 'index');
            Route::get('unread', 'unread');
            Route::get('read', 'read');
            Route::get('trash', 'trash');
            Route::get('modal/?([0-9]*)', 'modal');
            Route::post('delete/([0-9]+)', 'delete');
            Route::post('restore/([0-9]+)', 'restore');
            Route::post('destroy/([0-9]+)', 'destroy');
            Route::post('empty-trash', 'clear');
        });

        // 6. Communication: Notifications 🔔
        Route::prefix('notification')->controller('NotificationController')->group(function () {
            Route::get('/', 'index');
            Route::get('modal/([0-9]+)', 'modal');
            Route::get('counts', 'getCounts');
            Route::post('delete/([0-9]+)', 'delete');
            Route::post('clear', 'clear');
        });
    }
}
