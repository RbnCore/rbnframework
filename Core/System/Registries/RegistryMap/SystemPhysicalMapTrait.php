<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\System\Registries\RegistryMap;

/**
 * SystemPhysicalMapTrait - The Physical Inventory of the Framework 🏺🏗️⚓
 * 
 * RBN Framework: Centralized authority for Models and Data Providers.
 */
trait SystemPhysicalMapTrait
{
    /**
     * Map of Physical & Data Components 🏺🏗️
     * DİKKAT: Bu haritaya (PhysicalMap) SADECE 'models' ve 'providers' eklenecektir.
     */
    protected function physicalMap(): array
    {
        return [
            /* --- Core Data Models 🏺 --- */
            'models' => [
                // --- RBN Framework Master Layer 🌍 ---
                'master.projects' => 'Core\Database\Models\Master\MasterProjectsModel',
                'master.settings' => 'Core\Database\Models\Master\MasterSettingsModel',
                // [FW-LICENCE] Merkezi lisans + uygulama kaydi (TEK 3 tablo kurali).
                'master.licence' => 'Core\Database\Models\Master\MasterLicenceModel',
                'master.application' => 'Core\Database\Models\Master\MasterApplicationModel',
                'master.cronJob' => 'Core\Database\Models\Master\MasterCronJobsModel',
                'master.ipBlock' => 'Core\Database\Models\Master\MasterIpBlocksModel',
                'master.ipWhitelist' => 'Core\Database\Models\Master\MasterIpWhitelistModel',
                // [A0-1 / G-02] Kayıtsız alias'lar. `model('ipBlock')`,
                // `model('ipWhitelist')` çağrıları hiçbir registry'de karşılığı
                // yoktu -> NULL -> IP katmanı sessizce ölü. Tek-merkez ilkesi:
                // alias'lar yalnız BURADA tanımlanır, tüketici FQCN de yazabilir.
                'ipBlock' => 'Core\Database\Models\Master\MasterIpBlocksModel',
                'ipWhitelist' => 'Core\Database\Models\Master\MasterIpWhitelistModel',
                'shieldSetting' => 'Core\Database\Models\Common\CmSysSettingsShieldModel',
                'master.developer' => 'Core\Database\Models\Master\MasterDevelopersModel',
                'master.rbnHeartbeat' => 'Core\Database\Models\Master\MasterRbnHeartbeatsModel',

                // --- [ Common Layer 🌐 ] ---
                'common.rateLimit' => 'Core\Database\Models\Common\CmSysRateLimitsModel',
                'common.ipBlock' => 'Core\Database\Models\Common\CmSysIpBlocksModel',
                'common.notification' => 'Core\Database\Models\Common\CmLogNotificationsModel',
                'common.contactMessage' => 'Core\Database\Models\Common\CmLogContactsModel',
                'common.aiUsage' => 'Core\Database\Models\Common\CmLogAiUsagesModel',
                'common.shieldSetting' => 'Core\Database\Models\Common\CmSysSettingsShieldModel',

                // --- [ Project Layer 📂 ] ---
                'project.settings' => 'Core\Database\Models\Project\SettingsModel',
                'project.settingsGroup' => 'Core\Database\Models\Project\SettingsGroupModel',
                'project.settingsApi' => 'Core\Database\Models\Project\SettingsApiModel',
                'project.faq' => 'Core\Database\Models\Project\FaqsModel',
                'project.page' => 'Core\Database\Models\Project\PagesModel',
                'project.rbnHeartbeat' => 'Core\Database\Models\Project\RbnHeartbeatsModel',
                'project.cronLog' => 'Core\Database\Models\Project\CronLogsModel',
                'project.user' => 'Core\Database\Models\Project\UsersModel',
                'project.userActivity' => 'Core\Database\Models\Project\UserActivitiesModel',
                'project.userSecurity' => 'Core\Database\Models\Project\UserSecurityModel',
                // [A0-6] Tek kullanimlik token kasasi (e-posta dogrulama / parola sifirlama).
                // Kayitli olmazsa `model('project.userToken')` NULL doner ve token akisi sessizce
                // calisir; bu yuzden tek-merkez ilkesiyle buraya yazildi.
                'project.userToken' => 'Core\Database\Models\Project\UserTokenModel',
                'frontendMenu' => 'Core\Database\Models\Project\FrontendMenusModel',
                'sidebarCategory' => 'Core\Database\Models\Project\SidebarCategoriesModel',
                'sidebarMenu' => 'Core\Database\Models\Project\SidebarMenusModel',
                'app.contentCategory' => 'Core\Database\Models\Project\ContentCategoryModel',
                'app.contentDraft' => 'Core\Database\Models\Project\ContentDraftModel',
                'app.rss.source' => 'Core\Database\Models\Project\RssSourceModel',
                'app.rss.blacklist' => 'Core\Database\Models\Project\RssBlacklistModel',
            ],

            /* --- Core Component Providers 🏗️ --- */
            'providers' => [
                'databaseGuard' => 'Core\Services\Gatekeepers\Providers\DatabaseGuardProvider',
                'cpanel' => 'Core\Services\Hosting\Providers\CPanelProvider',
                'legal' => 'Core\Services\System\Providers\LegalProvider',
                'socialmedia' => 'Bundles\RbnSuite\RbnAdmin\Providers\SocialMediaProvider',
                'development' => 'Core\Services\Exception\Providers\DevelopmentProvider',
                'userError' => 'Core\Services\Exception\Providers\UserErrorProvider',
                // [FW-110 / 97-1] `shieldDbSettings` kaydi KALDIRILDI (Anayasa §8
                // kural 1): bu "Provider" hiçbir DIŞ SERVISE baglanmiyordu, tek
                // isi `repository('common.shieldSetting')` cagirmakti. Ayarlari
                // okuyan 4 katman (IpGuard/GeoIP/SystemGuard/RateLimit) artik
                // dogrudan Repository'yi kullanir; anahtar sabitleri ve tip
                // normalizasyonu da orada (tek merkez).
                // A0-1'in ekledigi kaydin varligi korunur: Repository kaydi zaten
                // vardi, cagiranlar onu cozuyor -> IP katmani yine ayakta.
            ],

            /* --- Core Component Repositories 📦 --- */
            'repositories' => [
                // --- RBN Framework Master Layer 🌍 ---
                'master.projects' => 'Core\Database\Repositories\Master\MasterProjectsRepository',
                // [FW-BASE-3 / BULGU-1] Noktasız TAKMA AD (alias). Suffix keşfi
                // (`$this->masterProjectsRepository` -> hedef "masterProjects")
                // noktalı anahtarı bulamıyor ve diagnostic fırlatıyordu; proje
                // kaydı/lisans anahtarı üretimi bu yüzden ÖLÜYDÜ. Aynı desen
                // `masterLicence` alias'ıyla (FW-LICENCE-G) zaten çözülmüştü.
                'masterProjects' => 'Core\Database\Repositories\Master\MasterProjectsRepository',
                'master.settings' => 'Core\Database\Repositories\Master\MasterSettingsRepository',
                // [FW-LICENCE] Lisans/uygulama sozlesmesi tek merkezde (bu servisler bunlari cagirir).
                'master.licence' => 'Core\Database\Repositories\Master\MasterLicenceRepository',
                'master.application' => 'Core\Database\Repositories\Master\MasterApplicationRepository',
                // [FW-LICENCE-G] Noktasız TAKMA AD (alias). Suffix keşfi
                // (`$this->masterLicenceRepository` -> hedef "masterLicence") noktalı
                // anahtarı bulamıyor ve diagnostic fırlatıyordu; servis bu yüzden
                // repository'sine ulaşamıyordu. Tek-merkez ilkesi: alias'lar YALNIZCA
                // burada tanımlanır, tüketici FQCN de yazabilir.
                'masterLicence' => 'Core\Database\Repositories\Master\MasterLicenceRepository',
                'masterApplication' => 'Core\Database\Repositories\Master\MasterApplicationRepository',
                'master.cronJob' => 'Core\Database\Repositories\Master\CronJobsRepository',
                'master.ipGuard' => 'Core\Database\Repositories\Master\IpGuardRepository',

                // --- [ Common Layer 🌐 ] ---
                'common.contact' => 'Core\Database\Repositories\Common\ContactRepository',
                'common.notification' => 'Core\Database\Repositories\Common\NotificationRepository',
                'common.ipBlock' => 'Core\Database\Repositories\Common\IpBlockRepository',
                'common.shieldSetting' => 'Core\Database\Repositories\Common\ShieldSettingsRepository',

                // --- [ Project Layer 📂 ] ---
                'project.settings' => 'Core\Database\Repositories\Project\SettingsRepository',
                'project.settingsApi' => 'Core\Database\Repositories\Project\SettingsApiRepository',
                'project.faq' => 'Core\Database\Repositories\Project\FaqRepository',
                'project.cronLog' => 'Core\Database\Repositories\Project\CronLogRepository',
                'project.user' => 'Core\Database\Repositories\Project\UserRepository',
                'project.userActivity' => 'Core\Database\Repositories\Project\UserActivityRepository',
                'project.userSecurity' => 'Core\Database\Repositories\Project\UserSecurityRepository',
                'project.userToken' => 'Core\Database\Repositories\Project\UserTokenRepository',
                'app.contentCategory' => 'Core\Database\Repositories\Project\ContentCategoryRepository',
                'app.contentDraft' => 'Core\Database\Repositories\Project\ContentDraftRepository',
            ],
        ];
    }
}
