<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\System\Registries\RegistryMap;

/**
 * SystemLogicMapTrait - The Intellectual Hub of the Framework 🧠⚡⚓
 * 
 * RBN Framework: Centralized authority for Services and Logical Handlers.
 */
trait SystemLogicMapTrait
{
    /**
     * Map of Intellectual & Logic Components 🎡⚙️
     * DİKKAT: Bu haritaya (LogicMap) SADECE 'services' ve 'handlers' eklenecektir.
     * Validations, metadata, helpers vb. kaynaklar SystemResourceMapTrait'e gitmelidir.
     */
    protected function logicMap(): array
    {
        return [
            /* --- Core Framework Managers (System Orchestrators) 🏛️🛰️⚓ --- */
            'managers' => [
                /* --- Console & Task Managers 💻 --- */
                'cron' => 'Core\Services\Console\Managers\CronManager',
                'task' => 'Core\Services\Console\Managers\TaskManager',

                /* --- System & Domain Managers 🏛️ --- */
                'user' => 'Core\Services\System\Managers\UserManager',
                'session' => 'Core\Services\System\Managers\SessionManager',

                /* --- Route & Redirection Managers 🌐 --- */
                'redirect' => 'Core\Routes\RedirectManager',
            ],

            /* --- Core Framework Services ⚙️ --- */
            'services' => [
                /* --- Console Services 💻 --- */
                'rbncli' => 'Core\Services\Console\RbnCli',
                'cron' => 'Core\Services\Console\Services\CronService',
                'base.taskLog' => 'Core\Services\Console\Services\TaskLogService',

                /* --- Master Services 🏛️ --- */
                'masterProjects' => 'Core\Services\Master\MasterProjectsService',
                'masterSettings' => 'Core\Services\Master\MasterSettingsService',
                'masterLicences' => 'Core\Services\Master\MasterLicencesService',
                'masterApplications' => 'Core\Services\Master\MasterApplicationsService',

                /* --- System Services ⚙️ --- */
                'settings' => 'Core\Services\System\SettingsService',
                'settingsApi' => 'Core\Services\System\SettingsApiService',
                'cdn' => 'Core\Services\System\CdnService',
                'module' => 'Core\Services\System\ModuleService',
                'communication' => 'Core\Services\System\CommunicationService',
                'base.project' => 'Core\Services\System\BaseProjectService',

                /* --- Database & Storage Services 🗄️ --- */
                'db' => 'Core\Database\Services\DatabaseService',
                'storage' => 'Core\System\Storage\StorageManager',

                /* --- Gatekeeper & Security Services 🛡️ --- */
                'bootSentinel' => 'Core\Services\Gatekeepers\BootSentinel',
                'shieldSettings' => 'Core\Services\Gatekeepers\ShieldSettingsService',
                'ipGuard' => 'Core\Services\Gatekeepers\IpGuardService',
                'systemGuard' => 'Core\Services\Gatekeepers\SystemGuardService',
                'databaseGuard' => 'Core\Services\Gatekeepers\DatabaseGuardService',
                'form' => 'Core\Http\Security\FormService',

                /* --- HTTP & Routing Services 🌐 --- */
                'route' => 'Core\Routes\Engine\Providers\RouteManager',
                'alert' => 'Core\Http\AlertService',
                'exception' => 'Core\Services\Exception\ExceptionService',

                /* --- Hosting Services 🛰️ --- */
                'cpanel' => 'Core\Services\Hosting\CPanelService',

                /* --- Application & Bundle Services 📦 --- */
                'socialmedia' => 'Bundles\RbnSuite\RbnAdmin\Services\RbnAdminService',
                'frontendMenu' => 'Bundles\Internal\Webhub\Services\FrontendMenuService',
            ],

            /* --- Core Component Handlers 🎡 --- */
            'handlers' => [
                /* --- Console & Cron Handlers 💻 --- */
                'cronScheduler' => 'Core\Services\Console\Handlers\SchedulerHandler',
                'cronNotification' => 'Core\Services\Console\Handlers\CronNotificationHandler',

                /* --- Gatekeeper & Security Handlers 🛡️ --- */
                'ipGuard' => 'Core\Services\Gatekeepers\Handlers\IpGuardHandler',
                'rateLimit' => 'Core\Services\Gatekeepers\Handlers\RateLimitHandler',
                'systemGuard' => 'Core\Services\Gatekeepers\Handlers\SystemGuardHandler',
                'databaseGuard' => 'Core\Services\Gatekeepers\Handlers\DatabaseGuardHandler',
                'formGuard' => 'Core\Http\Security\Handlers\FormGuardHandler',
                'csrf' => 'Core\Http\Security\Handlers\CsrfHandler',
                'sanitization' => 'Core\Http\Security\Handlers\SanitizationHandler',
                'bot' => 'Core\Http\Security\Handlers\BotHandler',
                'origin' => 'Core\Http\Security\Handlers\OriginHandler',
                'injection' => 'Core\Http\Security\Handlers\InjectionHandler',
                'fileSecurity' => 'Core\Http\Security\Handlers\FileSecurityHandler',
                'geoIP' => 'Core\Services\Gatekeepers\Handlers\GeoIPHandler',

                /* --- Exception & Diagnostic Handlers 🩺 --- */
                'errorAnalysis' => 'Core\Services\Exception\Handlers\ErrorAnalysisHandler',
                'log' => 'Core\Services\Exception\Handlers\LogHandler',
                'exception' => 'Core\Services\Exception\Handlers\ExceptionHandler',

                /* --- System & Storage Handlers ⚙️ --- */
                'settings' => 'Core\Services\System\Handlers\SettingsHandler',
                'cdn' => 'Core\Services\System\Handlers\CdnHandler',
                'storage' => 'Core\System\Storage\Handlers\StorageHandler',
                'localization' => 'Core\System\Localization\Handlers\LocalizationHandler',

                /* --- Render & Hosting Handlers 🌐 --- */
                'seo' => 'Core\Render\Handlers\Seo\SeoHandler',
                'cpanelMail' => 'Core\Services\Hosting\Handlers\CPanelMailHandler',
                'cpanelDomains' => 'Core\Services\Hosting\Handlers\CPanelDomainsHandler',
            ],

            /* --- Core Component Resolvers 🧠 --- */
            'resolvers' => [
                'cron' => 'Core\Services\Console\Handlers\Resolvers\CronResolver',
                'task' => 'Core\Services\Console\Handlers\Resolvers\TaskResolver',
            ],

            /* --- Core Component Jobs 🛠️ --- */
            'jobs' => [
                'cleanup' => 'Core\Services\Console\Jobs\CleanupJob',
                'cleanup.server' => 'Core\Services\Console\Jobs\Cleanup\ServerCleanupJob',
                'cleanup.project' => 'Core\Services\Console\Jobs\Cleanup\ProjectCleanupJob',
            ],
        ];
    }
}
