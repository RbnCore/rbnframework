<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnEmail\Handlers;

use Rbn\Framework\Core\Base\BaseComponent;
use Rbn\Framework\Packages\RbnEmail\Models\EmailConstant;
use Rbn\Framework\Core\System\Config\Engine\Database\SmtpProfileResolver;

/**
 * EmailConfigHandler - The Settings Resolver (Worker) ⚙️🎻📧
 * 
 * RBN Framework: Standard (BaseComponent Actor).
 * Orchestrates SMTP credentials and application identity metadata.
 * Now Project-Aware: Resolves branding from project-settings.php DNA.
 */
class EmailConfigHandler extends BaseComponent
{
    private array $resolvedConfig = [];

    /**
     * Resolve all necessary email configurations 🏗️
     */
    public function resolve(bool $useMaster = false): array
    {
        if (!$useMaster && !empty($this->resolvedConfig)) {
            return $this->resolvedConfig;
        }

        // 🎼 RBN Framework: Project DNA Resolution 🧬
        $projectKey = (string) ($this->resolveProjectData('project_key') ?: (function_exists('active_project_key') ? active_project_key() : (function_exists('project_key') ? project_key() : '')));
        $projectName = (string) ($this->resolveProjectData('project_name') ?: '');
        $projectDomain = (string) ($this->resolveProjectData('domain') ?: '');

        // 🎼 RBN Framework: Autonomous Settings Discovery from DB & Routemap SSoT
        // Master/Sistem maillerinde (useMaster = true) gereksiz proje ayar okumaları ve cache tetiklenmez 🛡️
        $smtp = [];
        $smtpCritical = [];
        if (!$useMaster && !empty($projectKey) && $projectKey !== 'default' && $projectKey !== 'master') {
            $settings = $this->service('settings');
            $smtp = $settings ? $settings->read('email') : [];
            $smtpCritical = $settings ? $settings->read('email_critical') : [];
        }
        $config = array_merge($smtp, $smtpCritical);

        // 🖼️ Resolve Logo via native ResolvesProjectConfigTrait::getRouteConfig() SSoT 🎨
        $siteLogo = $this->getRouteConfig($projectKey, 'favicon');

        if (!empty($siteLogo) && str_ends_with(strtolower($siteLogo), '.svg')) {
            $siteLogo = str_replace('.svg', '.png', $siteLogo);
        }

        if (!empty($siteLogo) && !empty($projectDomain) && !str_starts_with($siteLogo, 'http://') && !str_starts_with($siteLogo, 'https://') && !str_starts_with($siteLogo, 'data:')) {
            $siteLogo = 'https://' . $projectDomain . '/' . ltrim($siteLogo, '/');
        }
        $siteLogo = $siteLogo ?: EmailConstant::DEFAULT_LOGO_SVG;

        $resolved = [
            // --- [ TRANSPORT DNA ] ---
            // [FW-TEK-SECRETS-DOSYASI-161] SMTP degerleri `secrets.php` `smtp`
            // bolumunden gelir; gomulu SMTP_* / EMAIL_FROM_* sabitleri KALDIRILDI.
            // Proje ayarlarindaki degerler yine onceliklidir (multitenant).
            'enabled' => $useMaster ? SmtpProfileResolver::enabled() : $this->isTrue($config['email_enabled'] ?? SmtpProfileResolver::enabled()),
            'host' => $useMaster ? SmtpProfileResolver::host() : (!empty($config['smtp_host']) ? $config['smtp_host'] : SmtpProfileResolver::host()),
            'port' => (int) ($useMaster ? SmtpProfileResolver::port() : (!empty($config['smtp_port']) ? $config['smtp_port'] : SmtpProfileResolver::port())),
            'username' => $useMaster ? SmtpProfileResolver::user() : (!empty($config['smtp_username']) ? $config['smtp_username'] : SmtpProfileResolver::user()),
            'password' => $useMaster ? SmtpProfileResolver::pass() : (!empty($config['smtp_password']) ? $config['smtp_password'] : SmtpProfileResolver::pass()),
            'from_address' => $useMaster ? SmtpProfileResolver::fromAddress() : (!empty($config['email_from_address']) ? $config['email_from_address'] : SmtpProfileResolver::fromAddress()),
            'from_name' => $useMaster ? SmtpProfileResolver::fromName() : (!empty($config['email_from_name']) ? $config['email_from_name'] : ($projectName ?? $company['company-name'] ?? SmtpProfileResolver::fromName())),

            // --- [ VISUAL DNA ] ---
            // 🎯 Priority: Cached Project Data > DB Settings > Master Fallback
            'site_name' => $projectName ?? $company['company-name'] ?? SmtpProfileResolver::fromName(),
            'site_slogan' => $company['company-slogan'] ?? EmailConstant::SLOGAN,
            'site_logo' => $siteLogo,
            'primary_color' => $appearance['theme_color_primary'] ?? EmailConstant::DEFAULT_THEME_COLOR,
            'secondary_color' => $appearance['theme_color_secondary'] ?? EmailConstant::DEFAULT_THEME_COLOR,
            'bg_color' => EmailConstant::DEFAULT_BG_COLOR,
            'text_color' => EmailConstant::DEFAULT_TEXT_COLOR,

            // --- [ SIGNATURE DNA ] ---
            'signature' => EmailConstant::FOOTER_SIGNATURE,
            'app_url' => !empty($projectDomain) ? ('https://' . $projectDomain) : (EmailConstant::FOOTER_URL ?? $this->request->url())
        ];

        if (!$useMaster) {
            $this->resolvedConfig = $resolved;
        }

        return $resolved;
    }

    private function isTrue($value): bool
    {
        return in_array($value, ['on', '1', 1, true, 'true'], true);
    }
}
