<?php

namespace Rbn\Framework\Core\Render\Handlers\UI;

use Rbn\Framework\Core\Base\Web\BaseRender;
use Rbn\Framework\Bundles\RbnSuite\RbnAuth\Models\AuthIdentity;
use Rbn\Framework\Core\Support\Definitions\System\FrameworkIdentity;

/**
 * AuthHandler - RBN Framework Data Preparation for Identity Suites 🛰️🔐⚓
 * 
 * RBN Framework Architecture.
 * Orchestrates branding, SEO and layout assets autonomously via ModuleData.
 * Lives in the Render Layer to maintain layer cohesion.
 */
class AuthHandler extends BaseRender
{
    /**
     * Prepares the view data using RBN Framework metadata from the active module.
     */
    public function prepare(string $type, ?string $view, array $options = []): array
    {
        $data = $options;
        $data['appContext'] = (string) ($this->context ?? 'auth');
        $data['view']       = $view;

        // 🎼 1. RBN Framework Discovery (The Soul) 🧠🛰️⚓
        // Verileri doğrudan AuthIdentity sabitlerinden çekiyoruz (Hata payı sıfır).
        $identity = AuthIdentity::AUTH_IDENTITY;
        $configs  = AuthIdentity::VIEW_MAP;
        $defaults = AuthIdentity::DEFAULT_INFO;

        // 🎼 2. Site Identity Gathering (Global Fallbacks)
        $siteContext = $this->resolver('seo')->webContext();
        $site = $siteContext['raw']['company'] ?? [];

        // 3. Page Specific Contextual IQ 🔐
        $viewKey = (string) ($configs[$view] ?? $view);
        $defaultInfos = $this->moduleData ? $this->moduleData->get('configs.default_info') : $defaults;
        $info = (array) ($defaultInfos[$viewKey] ?? ($defaults[$viewKey] ?? []));
        
        // 🎼 4. Standard Branding Mapping (RBN Framework Injection) ⚖️⚓
        $seoResolver = $this->resolver('seo');
        
        // Slogan ve Marka verilerini mühürlüyoruz
        $data['siteName']     = $this->appName ?? (string) FrameworkIdentity::AUTH_NAME;
        $data['siteSlogan']   = (string) ($identity['ui']['slogan'] ?? FrameworkIdentity::AUTH_SLOGAN);
        $data['siteDesc']     = (string) ($identity['seo']['description'] ?? '');
        $data['siteIcon']     = (string) ($info['icon'] ?? 'fas fa-shield-alt');
        $data['siteFeatures'] = (array)  ($info['features'] ?? []);

        // 🖼️ Logo Resolution
        $projectKey = function_exists('active_project_key') ? active_project_key() : 'default';
        $data['siteLogo'] = !empty($site['company-logo']) ? $seoResolver->resolveAsset("logo-{$projectKey}.svg") : null;

        // 5. RBN Framework Modular SEO Integration 🏺🗺️⚓
        $actionTitle = (string) ($info['title'] ?? 'Erişim');
        $pageTitle   = (string) ($data['pageTitle'] ?? $identity['seo']['title'] ?? ($actionTitle . ' | ' . $data['siteName']));
        $pageDesc    = (string) ($data['pageDesc'] ?? $identity['seo']['description'] ?? '');

        $builder = $this->handler('seoBuilder');
        if ($builder) {
            $builder->prepare('identity', [
                'module-name'     => (string) FrameworkIdentity::AUTH_NAME,
                'module-version'  => (string) FrameworkIdentity::AUTH_VERSION,
                'title'           => $pageTitle,
                'description'     => $pageDesc,
                'keywords'        => (string) ($identity['seo']['keywords'] ?? ''),
                'robots'          => (string) ($identity['seo']['robots'] ?? 'noindex, nofollow'),
                'favicon'         => (string) ($identity['ui']['favicon'] ?? ''),
                'canonical'       => (string) $this->request->url()
            ]);
        }
 
        $data['seoHtml'] = (string) $this->service('seo')->render();

        // 🎼 Module & Site Vitals
        $data['auth_name']    = (string) FrameworkIdentity::AUTH_NAME;
        $data['auth_version'] = (string) FrameworkIdentity::AUTH_VERSION;
        $data['siteVersion']  = app_version();
        $data['author']       = FrameworkIdentity::FRAMEWORK_NAME;
        $data['author_url']   = FrameworkIdentity::FRAMEWORK_URL;

        // 7. Assets Orchestration (JS/CSS Delivery)
        $assetService = $this->service('asset');
        if ($assetService) {
            $assetService->prepareContext((string) $data['appContext']);
            $data['headerAssets'] = (string) $assetService->render('header');
            $data['footerAssets'] = (string) $assetService->render('footer');
        }

        return $data;
    }
}
