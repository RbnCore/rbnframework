<?php

namespace Rbn\Framework\Core\Render\Providers\UI;

use Rbn\Framework\Core\Base\Web\BaseRender;
use Rbn\Framework\Core\Support\Contracts\Base\BaseRenderInterface;

/**
 * FrontendProvider - Website Layout Renderer 🛰️🌐⚓
 * 
 * Part of the RBN 3.5 "Masterpiece" Architecture.
 */
class FrontendProvider extends BaseRender implements BaseRenderInterface
{

    /**
     * Renders standard frontend view.
     */
    public function render(?string $view, array $data = []): string
    {
        // 📊 [SOVEREIGN ANALYTICS] 🛫🕊️⚓
        // RBN 3.5: Masterpiece Direct Storage Access 🛰️🪐
        $this->storage->traffic()->record();

        $renderService = $this->service('render');

        // 🎼 RBN 3.5: Masterpiece Context Orchestration 🛰️🪐⚓
        // 1. Dynamic Site Identity (Prefers Controller-Injected State for 0 Duplicate Reads)
        $site         = $data['site'] ?? ($this->service('settings')?->read('site') ?? []);
        $siteName     = $data['siteName'] ?? (trim((string) ($site['name'] ?? '')) ?: 'RBN Core');
        $siteSlogan   = $data['siteSlogan'] ?? trim((string) ($site['slogan'] ?? ''));
        $siteDesc     = $data['siteDesc'] ?? trim((string) ($site['description'] ?? ''));
        $rbnUrl       = $data['rbn_url'] ?? \Rbn\Framework\Core\Support\Definitions\System\FrameworkIdentity::FRAMEWORK_URL;
        $rbnName      = $data['rbn_name'] ?? \Rbn\Framework\Core\Support\Definitions\System\FrameworkIdentity::FRAMEWORK_NAME;
        $integrations = $data['integrations'] ?? ($this->service('settings')?->read('integrations') ?? []);
        $company      = $data['company'] ?? ($this->service('settings')?->read('company') ?? []);
        $social       = $data['social'] ?? ($this->service('settings')?->read('social') ?? []);
        $contact      = $data['contact'] ?? ($this->service('settings')?->read('contact') ?? []);

        // 2. Fetch Sovereign SEO Metadata (Specialized Shield Packaging)
        $builder = $this->handler('seoBuilder');
        if ($builder) {
            $builder->prepare($this->context ?? 'frontend');
        }
        $appSeoHtml = $this->service('seo')?->render() ?? '';

        // 3. Fetch Sovereign Asset Manifest
        $assetService = $this->service('asset');
        if ($assetService) {
            // 🎼 RBN 3.5: Masterpiece Asset Orchestration 🛰️⚓
            // Triggers Core Stack (Universal) + Frontend Core Bundle (inc. master.css)
            $assetService->prepareContext($this->context ?? 'frontend');
        }

        $headerAssets = $assetService ? $assetService->render('header') : '';
        $footerAssets = $assetService ? $assetService->render('footer') : '';

        // 🧬 4. Unified Payload Injection (Robust Identity Fallbacks)
        $data = array_merge([
            'site' => $site,
            'company' => $company,
            'social' => $social,
            'contact' => $contact,
            'siteName' => $siteName,
            'siteSlogan' => $siteSlogan,
            'siteDesc' => $siteDesc,
            'rbn_url' => $rbnUrl,
            'rbn_name' => $rbnName,
            'appSeoHtml' => $appSeoHtml,
            'appSchemaHtml' => $this->provider('schema')?->render() ?? '',
            'headerAssets' => $headerAssets,
            'footerAssets' => $footerAssets,
            'google_analytics_code' => $this->safeSnippet($integrations['google_analytics_code'] ?? ''),
            'google_adsense_code' => $this->safeSnippet($integrations['google_adsense_code'] ?? ''),
            'head_scripts' => $this->safeSnippet($integrations['head_scripts'] ?? ''),
            'body_scripts' => $this->safeSnippet($integrations['body_scripts'] ?? '')
        ], $data);

        // [RBN 3.0] Layout Resolve Strategy (Header/Footer Auto-Wrapping)
        $context = $this->context ?? 'frontend';
        $viewPath = $renderService->resolveViewPath($view, $data, 'auto', false, $context);
        $headerPath = $renderService->resolveViewPath('Layouts/header', $data, 'auto', false, $context);
        $footerPath = $renderService->resolveViewPath('Layouts/footer', $data, 'auto', false, $context);

        if (!$viewPath || !file_exists($viewPath)) {
            $this->handleMissing($view ?? 'unknown');
        }

        ob_start();

        // 🛡️ Masterpiece Fragment Guard:
        if ($fragment = $this->resolveFragment($viewPath, $data)) {
            return $fragment;
        }

        // 🏛️ Layout Composition 🎨
        if ($headerPath && file_exists($headerPath)) {
            $this->viewEngine()->render($headerPath, $data);
        }

        $this->viewEngine()->render($viewPath, $data);

        if ($footerPath && file_exists($footerPath)) {
            $this->viewEngine()->render($footerPath, $data);
        }

        return ob_get_clean();
    }

    /**
     * [R-04] Veritabanindan gelen ham entegrasyon kodunu ACIK kacis kapisindan
     * gecirir.
     *
     * Bu degerler duzende `{!! !!}` ile basilir ve amaclari zaten ham HTML
     * olmak (analytics / AdSense / Tag Manager snippet'i). Bu yuzden
     * kacis kapisi kaldirilamaz; `RawHtmlGate` ise ikinci katmanda inline
     * olay handler'larini (`onerror=` vb.) ve tehlikeli URI semalarini
     * (`javascript:`, `data:text/html`) notrlestirir. Meşru snippet'ler
     * (`<script async src="...googletagmanager.com...">`) AYNEN korunur.
     *
     * Yazma yetkisi `SettingsConfig::REQUIRED_ROLE` (admin/developer)
     * tarafindan kisitlanir; bu gate ONA EK olarak calisir.
     */
    protected function safeSnippet(mixed $value): string
    {
        if (!is_string($value) || trim($value) === '') {
            return '';
        }

        static $gate = null;
        if (!$gate instanceof \Rbn\Framework\Core\Render\Handlers\RawHtmlGate) {
            $gate = new \Rbn\Framework\Core\Render\Handlers\RawHtmlGate();
        }

        return $gate->sanitize($value);
    }
}
