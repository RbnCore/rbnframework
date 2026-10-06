<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\System\Registries\RegistryMap;

/**
 * SystemRenderMapTrait - The 6th Satellite: Render Hub Map 🛰️🎨⚓
 * 
 * RBN 3.5 Masterpiece: Centralized authority for Render Handlers (Contexts), 
 * Providers and specific Render Aliases.
 */
trait SystemRenderMapTrait
{
    /**
     * Map of Context & Orchestration Components 🎭🎨
     * DİKKAT: Bu haritaya (RenderMap) SADECE Core\Render klasörü altındaki yapılar eklenecektir.
     */
    protected function renderMap(): array
    {
        return [
            /* --- Render Services (Core Extensions) 📡 --- */
            'services' => [
                'asset' => 'Core\Render\Services\AssetService',
                'render' => 'Core\Render\Services\RenderService',
                'seo' => 'Core\Render\Services\SeoService',
                'crawler' => 'Core\Render\Services\CrawlerService',
            ],
 
            /* --- Render Handlers (Contexts: Data Preparation) 🧠 --- */
            'handlers' => [
                'backend' => 'Core\Render\Handlers\UI\PanelHandler',
                'frontend' => 'Core\Render\Handlers\UI\FrontendHandler',
                'auth' => 'Core\Render\Handlers\UI\AuthHandler',
 
                // --- Builders ---
                'assetBuilder' => 'Core\Render\Builders\AssetBuilder',
                'schemaBuilder' => 'Core\Render\Builders\SchemaBuilder',
                'breadcrumbBuilder' => 'Core\Render\Builders\BreadcrumbBuilder',
                'seoBuilder' => 'Core\Render\Builders\SeoBuilder',
                'sitemapBuilder' => 'Core\Render\Builders\SitemapBuilder',
                'feedBuilder' => 'Core\Render\Builders\FeedBuilder',
                'llmsBuilder' => 'Core\Render\Builders\LlmsBuilder',
            ],
 
            /* --- Render Providers (Physical Execution) 🏗️ --- */
            'providers' => [
                // --- UI ---
                'backend' => 'Core\Render\Providers\UI\PanelProvider',
                'frontend' => 'Core\Render\Providers\UI\FrontendProvider',
                'static' => 'Core\Render\Providers\UI\StaticProvider',
                'partials' => 'Core\Render\Providers\UI\PartialProvider',
                'auth' => 'Core\Render\Providers\UI\AuthProvider',
 
                // --- Others ---
                'seo' => 'Core\Render\Providers\SeoProvider',
                'schema' => 'Core\Render\Providers\SchemaProvider',
                'breadcrumb' => 'Core\Render\Providers\BreadcrumbProvider',
                'asset' => 'Core\Render\Providers\AssetProvider',
                'crawler' => 'Core\Render\Providers\CrawlerProvider',
            ],
 
            /* --- Render Presets 🎨 --- */
            'presets' => [
                'schema' => 'Core\Render\Handlers\SchemaPreset',
            ],
 
            /* --- Render Resolvers 🧠 --- */
            'resolvers' => [
                'schema' => 'Core\Render\Resolvers\SchemaResolver',
                'breadcrumb' => 'Core\Render\Resolvers\BreadcrumbResolver',
                'seo' => 'Core\Render\Resolvers\SeoResolver',
                'view' => 'Core\Render\Resolvers\ViewResolver',
                'layout' => 'Core\Render\Resolvers\LayoutResolver',
                'crawler' => 'Core\Render\Resolvers\CrawlerResolver',
                'sitemapResolver' => 'Core\Render\Resolvers\Sub\SitemapResolver',
                'feedResolver' => 'Core\Render\Resolvers\Sub\FeedResolver',
                'llmsResolver' => 'Core\Render\Resolvers\Sub\LlmsResolver',
                'robotsResolver' => 'Core\Render\Resolvers\Sub\RobotsResolver',
            ],
 
            /* --- Render Clusters (Core Engines & Resolvers) 🪐 --- */
            'clusters' => [
                'view_engine' => 'Core\Render\ViewEngine',
            ],

            /* --- Render Optimization Aliases 🏷️ --- */
            // [FW-095] KÖK NEDEN DÜZELTMESİ: Bu girdiler PHP SINIF TAKMA ADI
            // DEĞİLDİR; panel/render bağlam anahtarı eşlemesidir (örn.
            // 'admin' => 'panel'). Eskiden `'aliases'` anahtarında tutulduğu
            // için `SystemRegistry::registerMap()` bunları `SystemAccessMapTrait`
            // içindeki GERÇEK sınıf takma adlarıyla aynı diziye birleştiriyor,
            // `ComponentRegistry::registerAliases()` de hepsini sınıf takma
            // adı sanıp `S09_ALIAS_ORIGINAL_MISSING` yanlış-pozitifi üretiyordu
            // (ölçüm: 17 satır/istek). Ayrı anahtara taşındı.
            'renderAliases' => [
                'admin' => 'panel',
                'backend' => 'panel',
                'editor' => 'panel',
                'user' => 'panel',
                'auth' => 'auth',
                'login' => 'auth',
                'register' => 'auth',
                'forgot' => 'auth',
                'reset' => 'auth',
                'verify' => 'auth',
                'partial' => 'partials',
                'ajax' => 'partials',
                'xml' => 'crawler',
                'robots' => 'crawler',
                'sitemap' => 'crawler',
                'llms' => 'crawler',
                'assets' => 'asset',
            ]
        ];
    }
}
