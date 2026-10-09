<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Render\Handlers\UI;

use Rbn\Framework\Core\Support\Definitions\System\FrameworkIdentity;
use Rbn\Framework\Core\Base\Web\BaseRender;

use Rbn\Framework\Bundles\RbnSuite\RbnAdmin\Models\PanelIdentity;
use Rbn\Framework\Core\Http\Security\AuthPolicy;

/**
 * PanelHandler - RBN Framework Data Architect for Admin Dashboard 🛰️📊⚓
 * 
 * RBN Framework Architecture.
 * Handles specialized dashboard context while leveraging autonomous Rendering DNA.
 */
class PanelHandler extends BaseRender
{
    /**
     * Entry Point: Prepares variables for administrative views.
     * RBN Framework: [RBN Framework PREPARATION] 🏛️⚙️⚓
     */
    public function prepare(string $type, ?string $view, array $options = []): array
    {
        $data = $options;

        // 🎼 RBN Framework [RBN Framework] RBN Framework Identity Injection 🛰️⚓
        // Sourced automatically via DNA inheritance from the active controller.
        $data['module'] = $data['module'] ?? $this->module;
        $data['panel'] = $data['panel'] ?? $this->panel;
        $data['context'] = $data['context'] ?? $this->context;

        $data['appContext'] = 'panel';
        $data['view'] = $view;

        // 🎼 RBN Framework [HUB INJECTION] 🎻🛰️⚓
        // Communication Hub'ı (Noti & Msg) global olarak tüm panel görünümlerine enjekte eder.
        $comm = $this->service('communication');
        $comm->boot(); // Verileri (Implicitly) yükle
        $data['comm'] = $comm;

        // 🎼 RBN Framework [NAVIGATION DNA] 🗺️🛰️⚓
        // Menü yapısı sadece oturum açılmış ve panel rotasına girilmiş projelerde (project_data('dashboard_prefix')) yüklenir.
        $dashPrefix = function_exists('project_data') ? project_data('dashboard_prefix') : null;
        $dashPrefix = !empty($dashPrefix) ? trim((string) $dashPrefix, '/') : null;

        $cleanPath = ltrim(strtolower($this->request->path() ?? ''), '/');
        $isDashboardRoute = $dashPrefix && str_starts_with($cleanPath, strtolower($dashPrefix));

        if ($isDashboardRoute && !empty($data['user']['role'])) {
            $sidebarService = $this->service('sidebar');
            if ($sidebarService) {
                $userRole = (string) $data['user']['role'];
                $data['sidebarItems'] = $sidebarService->getStructure($userRole);
            }
        }

        // 🎼 1. RBN Framework Site Identity Gathering 📡⚓
        // Fetches DB settings (company, appearance, config) via centralized SeoResolver.
        $siteContext = $this->resolver('seo')->webContext();
        $site = $siteContext['raw']['company'] ?? [];
        $appearance = $siteContext['raw']['appearance'] ?? [];

        $panelSeo = PanelIdentity::PANEL_IDENTITY['seo'] ?? [];

        // 🎼 2. Standard Branding Mapping (DB > PanelIdentity) ⚖️⚓
        $data['siteName'] = $site['company-name'] ?? FrameworkIdentity::ADMIN_NAME;
        $data['siteSlogan'] = $site['company-slogan'] ?? FrameworkIdentity::ADMIN_SLOGAN;

        // 🎼 siteDesc: Prioritize DB Slogan, fallback to Identity Description.
        $data['siteDesc'] = $site['company-slogan'] ?? ($panelSeo['description'] ?? '');

        // 🖼️ Virtual Logo Resolution: If company logo exists, serve via RBN Framework Hub.
        $seoResolver = $this->resolver('seo');
        $projectKey = function_exists('active_project_key') ? active_project_key() : 'default';
        $data['siteLogo'] = !empty($site['company-logo']) ? $seoResolver->resolveAsset("logo-{$projectKey}.svg") : null;

        // 3. Theme & Appearance Sync 🎨⚓
        $data['adminTheme'] = $appearance['admin_theme'] ?? 'blue';

        // 🎯 Title Standard: [Action] | [ProjectName] 🏹
        $actionTitle = FrameworkIdentity::ADMIN_TITLE;
        $pageTitle = $actionTitle . ' | ' . $data['siteName'];

        $builder = $this->handler('seoBuilder');
        if ($builder) {
            $builder->prepare('panel', [
                'module-name' => FrameworkIdentity::ADMIN_NAME,
                'module-version' => FrameworkIdentity::ADMIN_VERSION,
                'title' => $pageTitle,
                'description' => $panelSeo['description'] ?? '',
                'keywords' => $panelSeo['keywords'] ?? '',
                'robots' => $panelSeo['robots'] ?? 'noindex, nofollow',
                'favicon' => PanelIdentity::FAVICON,
                'virtual_favicon' => 'favicon-rbnadmin.svg',
                'canonical' => $this->request->url()
            ]);
        }
 
        $data['seoHtml'] = $this->service('seo')->render();
        $data['metadata'] = $builder ? $builder->payload() : [];

        // 🎼 RBN Framework: Centralized Head State & Ready Queue Hub 🛰️⚓
        $renderedHeadState = $this->provider('partial')->render('RbnCommon/head_state', ['context' => 'panel']);
        $data['headStateHtml'] = "\n    <!-- [FRAMEWORK HEAD STATE ENGINE] -->\n" . $renderedHeadState . "\n";

        // 🎼 RBN Framework [RBN Framework] Centralized App Configuration (JS Bridge) 🛰️⚓
        // Same reader as the server-side idle check (single source: `security.session_timeout`).
        $timeoutMinutes = AuthPolicy::sessionIdleMinutes();

        $data['rbnSecurity'] = [
            'SESSION_TIMEOUT' => $timeoutMinutes * 60,
            'LOCKSCREEN_URL' => $this->Route->url('lockscreen')
        ];

        // 5. Assets Orchestration (JS/CSS Delivery) 🎻⚓
        $assetService = $this->service('asset');
        if ($assetService) {
            $assetService->prepareContext('panel');
            $data['headerAssets'] = $assetService->render('header');
            $data['footerAssets'] = $assetService->render('footer');
        } else {
            $data['headerAssets'] = '';
            $data['footerAssets'] = '';
        }

        return $data;
    }
}
