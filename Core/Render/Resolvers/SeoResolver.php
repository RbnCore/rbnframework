<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Render\Resolvers;

use Rbn\Framework\Core\Base\Web\BaseRender;
use Rbn\Framework\Core\Render\Configs\SeoConfig;
use Rbn\Framework\Core\Support\Definitions\System\FrameworkIdentity;
use Rbn\Framework\Core\Support\Definitions\Render\AssetConvention;
use Rbn\Framework\Core\Support\Bridges\Helpers\Library\ProjectVersionResolver;

/**
 * SeoResolver - The Semantic Intelligence Layer (Framework Cluster) 🧬🗺️⚓
 * 
 * RBN Framework - Layer 2: The Refiner.
 * Standardizes raw database entries into clean metadata sets for rendering.
 */
class SeoResolver extends BaseRender
{
    public function resolve(string $context = 'project', array $overrides = []): array
    {
        $settings = $this->service('settings');
        $config = \Rbn\Framework\Core\System\Config\Config::get('project-settings') ?: [];

        $isLive = in_array($context, ['project', 'frontend', 'panel']);
        $seoDb = $isLive ? ($settings ? $settings->read('seo') : []) : [];
        $company = $settings ? $settings->read('company') : [];
        $social = $settings ? $settings->read('social') : [];
        $contact = $settings ? $settings->read('contact') : [];
        $appearance = $settings ? $settings->read('appearance') : [];

        $hub = [
            'config' => $config,
            'seo' => $seoDb,
            'company' => $company,
            'social' => $social,
            'contact' => $contact,
            'appearance' => $appearance,
            'context' => $context
        ];

        $defaults = SeoConfig::defaults();

        // 🎼 1. Core Meta Mapping (Priority Resolution) 🏛️🎯
        $title = $overrides['title'] ?? ($seoDb['meta-title'] ?? null);
        $siteName = !empty($this->appName)
            ? $this->appName
            : (!empty($config['app_name'])
                ? $config['app_name']
                : (!empty($company['company-name'])
                    ? $company['company-name']
                    : FrameworkIdentity::FRAMEWORK_NAME));

        if (empty($title) || $title === $siteName) {
            // Eğer başlık boşsa veya sadece site adından ibaretse, tam SEO başlığını kullan
            $title = $seoDb['meta-title'] ?? $defaults['title'];

            // Eğer hala sadece site adı geliyorsa (veya default'tan geliyorsa), formatı koru
            if (!str_contains($title, $siteName) && $title !== $siteName) {
                $title = "{$title} | {$siteName}";
            }
        } elseif (!str_contains($title, $siteName)) {
            $title = "{$title} | {$siteName}";
        } elseif (str_starts_with($title, $siteName . ' | ')) {
            // Eğer başta 'Site Adı | ' varsa, onu temizle ve sona al
            $title = trim(str_replace($siteName . ' | ', '', $title)) . ' | ' . $siteName;
        }

        $desc = $overrides['description'] ?? null;
        if (empty($desc))
            $desc = $seoDb['meta-description'] ?? null;
        if (empty($desc))
            $desc = $company['company-slogan'] ?? null;
        if (empty($desc))
            $desc = $config['app_slogan'] ?? null;
        if (empty($desc))
            $desc = $defaults['description'];

        $keys = ($overrides['keywords'] ?? null)
            ?: (($seoDb['meta-keywords'] ?? null)
                ?: $defaults['keywords']);

        $robots = ($overrides['robots'] ?? null)
            ?: $this->siteRobotsDirective($seoDb, $defaults);

        // 🎼 RBN Framework: [RBN Framework IDENTITY DISCOVERY] 🏛️⚖️⚓
        // Logic: Overrides > appName > DB (company-name) > Config (app_name)
        $moduleName = !empty($overrides['module-name'])
            ? $overrides['module-name']
            : (!empty($this->appName)
                ? $this->appName
                : (!empty($company['company-name'])
                    ? $company['company-name']
                    : (!empty($config['app_name'])
                        ? $config['app_name']
                        : 'RBN Project')));

        // [FW-SURUMLEME-2] `module-version` = PROJE surumu. TEK cozucu:
        // `ProjectVersionResolver` (master DB `projects.version` uzerinden).
        // Iki parcali `1.0` gibi gecersiz varsayilan YOK; deger yoksa
        // standart baslangic surumu (`0.1.1`) kullanilir.
        $moduleVersion = $overrides['module-version'] ?? ProjectVersionResolver::resolve(
            $config['app_version'] ?? project_data('version')
        );

        // 🎼 RBN Framework: Centralized Favicon Resolution 🏺🛰️⚓
        $faviconRaw = $this->resolveFaviconRaw($hub, $overrides);

        // 🎼 2. Assembly (Metadata Package)
        $format = $this->helper('format');

        $projectKey = $this->projectKey ?? 'default';
        $defaultVirtualFavicon = "favicon-{$projectKey}.svg";

        $meta = [
            'module-name' => $moduleName,
            'module-version' => $moduleVersion,
            'title' => $format->seoCleanText((string) $title),
            'description' => $format->seoCleanText((string) $desc),
            'keywords' => $format->seoCleanText((string) $keys),
            'robots' => $robots,
            'canonical-url' => $overrides['canonical'] ?? $this->request->url(),
            'favicon' => $this->resolveAsset($faviconRaw, $defaultVirtualFavicon)
        ];

        // 📱 [FW-096-C7] apple-touch-icon + web manifest: dosya GERCEKTEN varsa (kural: AssetConvention)
        // dogrudan `/images/...` adresiyle; yoksa anahtar hic eklenmez -> etiket uretilmez.
        $appleTouch = $overrides['apple_touch_icon'] ?? AssetConvention::findAppleTouchIcon($this->projectKey ?? null);
        if (!empty($appleTouch)) {
            $meta['apple-touch-icon'] = str_starts_with((string) $appleTouch, 'http') ? (string) $appleTouch : url('/' . ltrim((string) $appleTouch, '/'));
        }
        $manifest = $overrides['manifest'] ?? AssetConvention::findManifest($this->projectKey ?? null);
        if (!empty($manifest)) {
            $meta['manifest'] = str_starts_with((string) $manifest, 'http') ? (string) $manifest : url('/' . ltrim((string) $manifest, '/'));
        }

        // 🤳 3. Social Intelligence (OG & Twitter)
        $og = $this->resolveSocial($meta, $hub, $overrides, $faviconRaw);

        return [
            'context' => $context,
            'meta' => $meta,
            'og' => $og,
            'label' => $overrides['label'] ?? $moduleName,
            'copyright' => "© " . date('Y') . " " . ($company['company-name'] ?? FrameworkIdentity::DEVELOPER_NAME)
        ];
    }

    /**
     * Priority Resolution: Extracts the raw favicon data (before resolution). 🏺🛰️⚓
     */
    public function resolveFaviconRaw(array $hub, array $overrides = []): string
    {
        $seoDb = $hub['seo'] ?? [];
        $company = $hub['company'] ?? [];
        $config = $hub['config'] ?? [];

        // 1. Overrides first (if set by controller)
        if (!empty($overrides['favicon'])) {
            return $this->formatFaviconData($overrides['favicon']);
        }

        // 2. Routemap SSoT Check (project-routemap.php) 🗺️
        $projectKey = $this->projectKey ?? (function_exists('project_key') ? project_key() : null);
        if (!empty($projectKey)) {
            $routeFavicon = $this->getRouteConfig($projectKey, 'favicon');
            if (!empty($routeFavicon)) {
                return $this->formatFaviconData((string) $routeFavicon);
            }
        }

        // 3. [FW-ASSET-KONVANSIYON] KURAL: `images/favicon-{project_key}.{svg,png,ico}`
        // Ad listesi ve oncelik sirasi TEK YERDE: `AssetConvention`.
        // (Onceki hâli burada kendi listesini tasiyordu; `og-image` tarafi
        //  baska bir liste tasiyordu -> og meta etiketi her sitede 404 veriyordu.)
        $conventionFavicon = AssetConvention::findFavicon($projectKey);
        if ($conventionFavicon !== null) {
            return $this->formatFaviconData($conventionFavicon);
        }

        // 4. Config next (the old structure in project-settings.php)
        if (!empty($config['favicon'])) {
            return $this->formatFaviconData($config['favicon']);
        }

        // 5. Database setting next
        if (!empty($company['company-favicon'])) {
            return $this->formatFaviconData($company['company-favicon']);
        }

        // 6. Fallback Default
        return 'favicon.ico';
    }

    /**
     * Helper: If path points to an existing SVG file in publicRoot, returns its SVG content; otherwise returns the path/data string. 🎨
     */
    protected function formatFaviconData(string $pathOrData): string
    {
        $trimmed = trim($pathOrData);
        if (str_starts_with($trimmed, '<svg') || str_starts_with($trimmed, 'data:image/svg+xml')) {
            return $trimmed;
        }

        $publicRoot = \Rbn\Framework\Core\System\Paths\Paths::isInitialized() ? \Rbn\Framework\Core\System\Paths\Paths::publicRoot() : null;
        if ($publicRoot) {
            $cleanPath = ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $pathOrData), DIRECTORY_SEPARATOR);
            $fullPath = $publicRoot . DIRECTORY_SEPARATOR . $cleanPath;
            if (file_exists($fullPath)) {
                $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
                if ($ext === 'svg') {
                    $svgContent = file_get_contents($fullPath);
                    if ($svgContent && str_contains($svgContent, '<svg')) {
                        return $svgContent;
                    }
                }
            }
        }

        return $pathOrData;
    }

    /**
     * Resolves Social and OpenGraph metadata. 🤳🗺️
     */
    protected function resolveSocial(array $meta, array $hub, array $overrides = [], string $faviconRaw = ''): array
    {
        $seoDb = $hub['seo'] ?? [];
        $ogImageRaw = $overrides['og_image'] ?? ($seoDb['og-image'] ?? ($hub['config']['og_image'] ?? null));

        $projectKey = $this->projectKey ?? null;

        // 🖼️ [FW-ASSET-KONVANSIYON] KURAL: `images/og-image-{project_key}.{png,jpg,webp}`.
        // Dosya GERCEKTEN varsa sanal `/project-assets/og-image-<key>.<ext>` adı
        // uretilir (AssetController bu adi guvenli cozuyor).
        //
        // DOSYA YOKSA `null` -> `og:image` meta etiketi HIC URETILMEZ.
        // Onceki hâl `og-image-{key}.png` adini SORU SORMAKTAYDI: dosya yoksa
        // bile meta etiketi yaziliyor, `AssetController` bu adi cozemedigi icin
        // HER SITE 404 donuyordu (olcum: 6 site).
        $virtualOgName = null;
        $legacyOgPath = null;
        $conventionOg = AssetConvention::findOgImage($projectKey);
        if ($conventionOg !== null) {
            $base = basename($conventionOg);
            // Sanal ad YALNIZCA kurala uyan dosya için üretilir
            // (`og-image-<key>.<png|jpg|webp>`). Anahtarsız ESKİ ad
            // (`images/og-image.png`) sanal ad DEĞİLDİR: sanal URL üretmek
            // ölü adres verirdi; bu dosyalar doğrudan `/images/...` adresiyle
            // servis edilir.
            if (AssetConvention::isVirtualOgImageName($base)) {
                $virtualOgName = $base;
            } else {
                $legacyOgPath = $conventionOg;
            }
        }

        // 🖼️ OpenGraph / Social Crawler URL Resolution
        $resolvedImage = null;
        if (!empty($ogImageRaw) && (str_starts_with($ogImageRaw, 'http') || str_starts_with($ogImageRaw, 'data:'))) {
            // Açık URL (ayar/DB) — dokunulmaz.
            $resolvedImage = $ogImageRaw;
        } elseif ($virtualOgName !== null) {
            $resolvedImage = url('/project-assets/' . $virtualOgName);
        } elseif ($legacyOgPath !== null) {
            $resolvedImage = url('/' . $legacyOgPath);
        } elseif (!empty($ogImageRaw)) {
            // Kuralda dosya yok: ayar/DB'deki GÖRELİ yol gerçekten var mı?
            // Varsa düz dosya adresi kullanılır; yoksa meta etiketi üretilmez.
            $goreli = ltrim(str_replace('\\', '/', (string) $ogImageRaw), '/');
            $publicRoot = \Rbn\Framework\Core\System\Paths\Paths::isInitialized()
                ? \Rbn\Framework\Core\System\Paths\Paths::publicRoot()
                : null;
            if ($goreli !== '' && !str_contains($goreli, '..') && $publicRoot
                && is_file($publicRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $goreli))) {
                $resolvedImage = url('/' . $goreli);
            }
        }

        return [
            'site_name' => $meta['label'] ?? ($hub['company']['company-name'] ?? 'RBN Framework'),
            'og:site_name' => $meta['label'] ?? ($hub['company']['company-name'] ?? 'RBN Framework'),
            'type' => 'website',
            'og:type' => 'website',
            'title' => $meta['title'] ?? '',
            'og:title' => $meta['title'] ?? '',
            'description' => $meta['description'] ?? '',
            'og:description' => $meta['description'] ?? '',
            'url' => $meta['canonical-url'] ?? $this->request->url(),
            'og:url' => $meta['canonical-url'] ?? $this->request->url(),
            'image' => $resolvedImage,
            'og:image' => $resolvedImage,
        ];
    }

    /**
     * Internal: Resolves a path into a valid URL (Handles SVGs/DataURIs/Assets). 🎡
     */
    public function resolveAsset(?string $path, string $virtualName = 'favicon.svg'): ?string
    {
        if (empty($path)) {
            return $path;
        }

        // 🎼 RBN Framework: [VIRTUAL ASSET DETECTION] 🏺🛰️⚓
        // If the path is raw SVG code or a data URI representing an SVG,
        // we transform it into a clean, cachable project-asset URL.
        $trimmed = trim($path);
        if (str_starts_with($trimmed, '<svg') || str_starts_with($trimmed, 'data:image/svg+xml')) {
            $proxyPath = \Rbn\Framework\Core\Render\Configs\AssetConfig::PROXY_SETUP['project']['path'] ?? 'project-assets';
            return url('/' . ltrim($proxyPath, '/') . '/' . $virtualName);
        }

        if (str_starts_with($path, 'http') || str_starts_with($path, 'data:')) {
            return $path;
        }

        // 🎼 AssetBuilder Resolution
        $builder = $this->handler('assetBuilder');
        $compiled = $builder ? $builder->compile($path, 'frontend') : null;

        $finalPath = ($compiled && !empty($compiled['path']))
            ? url($compiled['path'])
            : url('/' . ltrim($path, '/'));

        // 🌐 RBN Framework: URL Encoding (Ensures social crawlers can fetch files with spaces)
        return str_replace(' ', '%20', $finalPath);
    }

    /**
     * Get the raw content of virtual resources.
     */
    public function getVirtualResourceRaw(string $name, string $context = 'project'): string
    {
        if ($name === 'favicon-rbnadmin.svg') {
            return (string) file_get_contents(\Rbn\Framework\Core\System\Paths\Paths::frameworkRoot() . '/Resources/images/favicon-rbnadmin.svg');
        }
        if ($name === 'favicon-rbnauth.svg') {
            return (string) file_get_contents(\Rbn\Framework\Core\System\Paths\Paths::frameworkRoot() . '/Resources/images/favicon-rbnauth.svg');
        }

        // Dynamic Project Key Extraction from virtual resource name (e.g., favicon-example.svg -> example)
        if (str_starts_with($name, 'favicon-') && str_ends_with($name, '.svg')) {
            $key = substr($name, 8, -4);
            if (!empty($key) && !in_array($key, ['rbnadmin', 'rbnauth'])) {
                $this->projectKey = $key;
            }
        }

        $hub = $this->resolve($context);

        if ($name === 'favicon.svg' || str_starts_with($name, 'favicon-')) {
            return $this->resolveFaviconRaw($hub);
        }

        if ($name === 'logo.svg' || str_starts_with($name, 'logo-')) {
            return $hub['company']['company-logo'] ?? ($hub['company']['logo'] ?? '');
        }

        // [FW-ASSET-KONVANSIYON] `og-image-{key}.{png,jpg|webp}` sanal adi.
        // OG meta etiketi bu adi uretiyor; icerik fiziksel dosyadan gelir.
        // Cozum ve yol guvenligi TEK YERDE: `AssetConvention`.
        if (AssetConvention::isVirtualOgImageName($name)) {
            $fiziksel = AssetConvention::resolveVirtualOgImage($name);

            return $fiziksel !== null ? (string) file_get_contents($fiziksel) : '';
        }

        return match ($name) {
            default => ''
        };
    }

    /**
     * Get context for web rendering.
     */
    public function webContext(): array
    {
        $hub = $this->resolve('project');
        return [
            'siteName' => $hub['meta']['title'] ?? null,
            'siteSlogan' => $hub['meta']['description'] ?? null,
            'raw' => $hub
        ];
    }

    public function getSeoConfig(): array
    {
        $config = \Rbn\Framework\Core\System\Config\Config::get('project-settings') ?: [];
        return $config;
    }

    /** Okunamayan noindex kararı süreç başına bir kez günlüğe yazılır. */
    private static bool $noindexUnreadableLogged = false;

    /**
     * [FW-094-NOINDEX] Robots yönergesi `noindex` içeriyor mu? 🛡️
     * Site ve sayfa düzeyi kararların TEK yorumlayıcısı (büyük/küçük harf duyarsız).
     */
    public static function directiveHasNoindex(mixed $directives): bool
    {
        // Yönerge dizi (liste) olarak da yazılabilir: ['noindex', 'nofollow'].
        // İsimli anahtarlı diziler (ör. `disallow` yolları) yönerge DEĞİLDİR.
        if (is_array($directives) && array_is_list($directives)) {
            $directives = implode(',', array_filter($directives, 'is_string'));
        }

        return is_string($directives) && stripos($directives, 'noindex') !== false;
    }

    /**
     * [FW-094-BULGU-DUZELT / D-5] Site düzeyi robots yönergesi: panel ayarı
     * `meta-robots` → varsayılan `robots`. `resolve()` (önüne sayfa
     * `overrides['robots']` eklenir) ve `isSiteNoindex()` AYNI sırayı kullanır.
     */
    private function siteRobotsDirective(array $seoDb, array $defaults): mixed
    {
        return ($seoDb['meta-robots'] ?? null) ?: ($defaults['robots'] ?? null);
    }

    /**
     * [FW-094-NOINDEX] SİTE düzeyi noindex mi? 🛡️
     *
     * robots.txt, sitemap, llms.txt ve feed AYNI cevaba bakar; projeler
     * özelleştirmez. İki kaynaktan biri yeterlidir:
     *   1. `view_mapping[site]['robots']['noindex'] = true`
     *      (`getSeoConfig()['robots']['noindex']`; mevcut `robots` dizisinin anahtarı)
     *   2. Panel ayarı `seo` grubundaki `meta-robots` değeri `noindex` içeriyor
     *      (`resolve()` içindeki meta robots ile AYNI okuma).
     * Sayfa başına `noIndex()` çağrısı site düzeyi SAYILMAZ (bkz. `isPageNoindex()`).
     *
     * Karar okunamazsa (ayar servisi yok / okuma istisnası) FAIL-CLOSED: noindex
     * varsayılır ve nedeni günlüğe yazılır (bkz. UPGRADING 0.9.4).
     */
    public function isSiteNoindex(): bool
    {
        // Proje kaydı `robots` anahtarı dizi (`['noindex' => true, ...]`) ya da
        // yönerge metni (`'noindex, nofollow'`) olabilir; ikisi de geçerlidir.
        $robots = $this->getSeoConfig()['robots'] ?? [];
        if (is_array($robots) ? !empty($robots['noindex']) : self::directiveHasNoindex($robots)) {
            return true;
        }

        try {
            $settings = $this->service('settings');
            if (!$settings) {
                throw new \RuntimeException('settings service unavailable');
            }
            $seoDb = (array) $settings->read('seo');
        } catch (\Throwable $e) {
            // [FW-094-BULGU-DUZELT / G-1] FAIL-CLOSED: karar okunamıyorsa site
            // noindex SAYILIR. Aksi halde ayar/DB arızasında gizli bir site
            // tam arıza anında sitemap/llms yayınlar. Süreç başına TEK satır.
            if (!self::$noindexUnreadableLogged) {
                self::$noindexUnreadableLogged = true;
                error_log('[RBN-CRAWLER] isSiteNoindex: karar okunamadi, noindex varsayildi: '
                    . preg_replace('/\s+/', ' ', $e->getMessage()));
            }
            return true;
        }

        return self::directiveHasNoindex($this->siteRobotsDirective($seoDb, SeoConfig::defaults()));
    }

    /**
     * [FW-094-NOINDEX] SAYFA/kayıt düzeyi noindex mi? 🛡️
     * Kayıt satırı `robots` / `meta_robots` / `meta-robots` yönergesi taşıyorsa
     * ya da `noindex` bayrağı doluysa true. Site normal yayın yapar, yalnız bu
     * kayıt sitemap, llms.txt ve feed çıktısına girmez.
     *
     * @param array|object $entry Kaynak/sayfa satırı
     */
    public function isPageNoindex(array|object $entry): bool
    {
        $row = is_array($entry) ? $entry : (method_exists($entry, 'toArray') ? $entry->toArray() : (array) $entry);

        if (!empty($row['noindex'])) {
            return true;
        }

        foreach (['robots', 'meta_robots', 'meta-robots'] as $key) {
            if (self::directiveHasNoindex($row[$key] ?? null)) {
                return true;
            }
        }

        return false;
    }
}
