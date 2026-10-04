<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Render\Resolvers;

use Rbn\Framework\Core\Base\Web\BaseRender;
use Rbn\Framework\Core\Render\Configs\SeoConfig;
use Rbn\Framework\Core\Support\Definitions\System\FrameworkIdentity;

/**
 * SeoResolver - The Semantic Intelligence Layer (Framework Cluster) 🧬🗺️⚓
 * 
 * RBN 3.5 "Masterpiece" - Layer 2: The Refiner.
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
            ?: (($seoDb['meta-robots'] ?? null)
                ?: ($defaults['robots'] ?? null));

        // 🎼 RBN 3.5: [SOVEREIGN IDENTITY DISCOVERY] 🏛️⚖️⚓
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

        $moduleVersion = $overrides['module-version'] ?? ($config['app_version'] ?? '1.0');

        // 🎼 RBN 3.5: Centralized Favicon Resolution 🏺🛰️⚓
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

        // 3. Dynamic Domain/Public folder check using project-key
        $publicRoot = \Rbn\Framework\Core\System\Paths\Paths::isInitialized() ? \Rbn\Framework\Core\System\Paths\Paths::publicRoot() : null;
        if ($publicRoot) {
            $candidates = [];
            if (!empty($projectKey)) {
                $candidates[] = "images/{$projectKey}.svg";
                $candidates[] = "images/favicon-{$projectKey}.svg";
                $candidates[] = "images/favicon-{$projectKey}.png";
                $candidates[] = "images/{$projectKey}.png";
                $candidates[] = "images/{$projectKey}.ico";
            }
            $candidates[] = "images/favicon.svg";
            $candidates[] = "images/favicon.png";
            $candidates[] = "images/favicon.ico";

            foreach ($candidates as $candidate) {
                $fullPath = $publicRoot . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $candidate);
                if (file_exists($fullPath)) {
                    return $this->formatFaviconData($candidate);
                }
            }
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

        $publicRoot = \Rbn\Framework\Core\System\Paths\Paths::isInitialized() ? \Rbn\Framework\Core\System\Paths\Paths::publicRoot() : null;
        $projectKey = $this->projectKey ?? null;

        if ($publicRoot) {
            $candidates = [];
            if (!empty($projectKey)) {
                $candidates[] = "images/og-image-{$projectKey}.png";
                $candidates[] = "images/og-image-{$projectKey}.jpg";
            }
            $candidates[] = "images/og-image.png";
            $candidates[] = "images/og-image.jpg";

            foreach ($candidates as $candidate) {
                $fullPath = $publicRoot . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $candidate);
                if (file_exists($fullPath)) {
                    $ogImageRaw = $candidate;
                    break;
                }
            }
        }

        $projectKey = $this->projectKey ?? 'default';
        $virtualOgName = "og-image-{$projectKey}.png";

        // 🖼️ OpenGraph / Social Crawler URL Resolution (Standard project-assets Proxy)
        $resolvedImage = null;
        if (!empty($ogImageRaw)) {
            if (str_starts_with($ogImageRaw, 'http') || str_starts_with($ogImageRaw, 'data:')) {
                $resolvedImage = $ogImageRaw;
            } else {
                $resolvedImage = url('/project-assets/' . $virtualOgName);
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

        // 🎼 RBN 3.5: [VIRTUAL ASSET DETECTION] 🏺🛰️⚓
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

        // 🌐 RBN 3.5: Masterpiece URL Encoding (Ensures social crawlers can fetch files with spaces)
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
}
