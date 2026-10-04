<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Render\Providers;

use Rbn\Framework\Core\Base\Web\BaseRender;
use Rbn\Framework\Core\Support\Contracts\Base\BaseRenderInterface;
use Rbn\Framework\Core\Support\Definitions\System\FrameworkIdentity;

/**
 * SeoProvider - The Sovereign HTML Metadata Renderer Hub & Single Entry Point 🎨🛰️⚓
 * Part of RBN 3.5 Masterpiece.
 */
class SeoProvider extends BaseRender implements BaseRenderInterface
{
    /**
     * Renders the complete segmented SEO metadata block. 🏹
     */
    public function render(?string $view = null, array $data = []): string
    {
        $finalData = $data;
        if (!isset($finalData['meta'])) {
            $resolver = $this->resolver('seo');
            $finalData = $resolver ? $resolver->resolve('project') : [];
        }

        $meta = $finalData['meta'] ?? $finalData;
        $og = $finalData['og'] ?? ($meta['og'] ?? []);
        $context = $finalData['context'] ?? 'project';

        if ($view === 'branding') {
            return $this->renderBranding($meta);
        }

        $techHtml = $this->renderTechnical($meta);
        $projectHtml = $this->renderProjectMeta($meta);
        $socialHtml = $this->renderSocial($og);
        $brandingHtml = $this->renderBranding($meta);
        $linkHtml = $this->renderLinks($meta, (string) $context);

        $label = $finalData['label'] ?? ($finalData['module_name'] ?? $context);
        $contextLabel = strtoupper((string) $label);
        $output = "\n    <!-- 🔱 RBN SOVEREIGN SEO ENGINE [v" . FrameworkIdentity::FRAMEWORK_VERSION . "] -->\n";

        if (!empty($techHtml)) {
            $output .= $techHtml . "\n";
        }
        if (!empty($projectHtml)) {
            $output .= "\n    <!-- [ {$contextLabel} SEO ] -->\n" . $projectHtml . "\n";
        }
        if (!empty($socialHtml)) {
            $output .= $socialHtml . "\n";
        }
        if (!empty($brandingHtml)) {
            $output .= $brandingHtml . "\n";
        }
        if (!empty($linkHtml)) {
            $output .= "\n    <!-- [ RESOURCE LINKS ] -->\n" . implode("\n", $linkHtml) . "\n";
        }
        $output .= "\n    <!-- ⚓ SOVEREIGN SEO ENGINE - END -->\n";

        return $output;
    }

    /**
     * Segment: Technical Foundation (Charset, Viewport, Compatibility) 🛡️⚓
     */
    protected function renderTechnical(array $meta): string
    {
        $html = ["\n    <!-- [ TECHNICAL FOUNDATION ] -->"];
        $html[] = '    <meta charset="UTF-8">';
        $viewport = $meta['viewport'] ?? 'width=device-width, initial-scale=1.0';
        $html[] = '    <meta name="viewport" content="' . htmlspecialchars($viewport) . '">';
        $html[] = '    <meta http-equiv="X-UA-Compatible" content="IE=edge" />';
        return implode("\n", $html);
    }

    /**
     * Segment: Project SEO Meta tags
     */
    protected function renderProjectMeta(array $meta): string
    {
        $html = [];
        $title = $meta['title'] ?? ($meta['meta-title'] ?? FrameworkIdentity::FRAMEWORK_NAME);
        $desc = $meta['description'] ?? ($meta['meta-description'] ?? FrameworkIdentity::FRAMEWORK_DESCRIPTION);
        $keys = $meta['keywords'] ?? ($meta['meta-keywords'] ?? null);
        $canon = $meta['canonical-url'] ?? null;

        if (!empty($title)) {
            $html[] = "    <title>" . htmlspecialchars_decode((string) $title) . "</title>";
        }

        if (!empty($meta['module-name'])) {
            $signature = (string) $meta['module-name'];
            if (!empty($meta['module-version'])) {
                $signature .= ' ' . $meta['module-version'];
            }
            $html[] = '    <meta name="module-name" content="' . htmlspecialchars($signature) . '">';
        }

        if (!empty($meta['robots'])) {
            $html[] = '    <meta name="robots" content="' . htmlspecialchars((string) $meta['robots'], ENT_NOQUOTES, 'UTF-8') . '">';
        }

        if (!empty($desc)) {
            $html[] = '    <meta name="description" content="' . htmlspecialchars((string) $desc, ENT_NOQUOTES, 'UTF-8') . '">';
        }
        if (!empty($keys)) {
            $html[] = '    <meta name="keywords" content="' . htmlspecialchars((string) $keys, ENT_NOQUOTES, 'UTF-8') . '">';
        }
        if (!empty($canon)) {
            $html[] = '    <link rel="canonical" href="' . htmlspecialchars((string) $canon, ENT_NOQUOTES, 'UTF-8') . '">';
        }

        return implode("\n", $html);
    }

    /**
     * Segment: Social Visibility (OpenGraph & Twitter) 📱⚓
     */
    protected function renderSocial(array $og): string
    {
        $html = ["\n    <!-- [ SOCIAL VISIBILITY ] -->"];
        $hasAny = false;

        $ogMap = [
            'og:site_name' => $og['og:site_name'] ?? 'RBN Framework',
            'og:type' => $og['og:type'] ?? 'website',
            'og:title' => $og['og:title'] ?? null,
            'og:description' => $og['og:description'] ?? null,
            'og:url' => $og['og:url'] ?? null,
            'og:locale' => $og['og:locale'] ?? 'tr_TR',
            'og:image' => $og['og:image'] ?? null,
        ];

        foreach ($ogMap as $property => $content) {
            if ($content) {
                $html[] = '    <meta property="' . htmlspecialchars($property, ENT_NOQUOTES, 'UTF-8') . '" content="' . htmlspecialchars_decode((string) $content) . '">';
                $hasAny = true;
            }
        }

        if (!empty($ogMap['og:title'])) {
            $html[] = "\n    <!-- [ TWITTER CARD ] -->";
            $html[] = '    <meta name="twitter:card" content="summary_large_image">';
            $html[] = '    <meta name="twitter:title" content="' . htmlspecialchars_decode((string) $ogMap['og:title']) . '">';
            if (!empty($ogMap['og:description'])) {
                $html[] = '    <meta name="twitter:description" content="' . htmlspecialchars_decode((string) $ogMap['og:description']) . '">';
            }
            if (!empty($ogMap['og:image'])) {
                $html[] = '    <meta name="twitter:image" content="' . htmlspecialchars_decode((string) $ogMap['og:image']) . '">';
            }
            $hasAny = true;
        }

        return $hasAny ? implode("\n", $html) : '';
    }

    /**
     * Segment: Framework Identity & Branding 🏛️⚓
     */
    protected function renderBranding(array $data): string
    {
        $html = ["\n    <!-- [ FRAMEWORK IDENTITY ] -->"];
        $gen = FrameworkIdentity::FRAMEWORK_NAME . ' v' . FrameworkIdentity::FRAMEWORK_VERSION;
        $slogan = FrameworkIdentity::FRAMEWORK_SLOGAN;
        $desc = FrameworkIdentity::FRAMEWORK_DESCRIPTION;
        $frameUrl = FrameworkIdentity::FRAMEWORK_URL;
        $author = FrameworkIdentity::DEVELOPER_NAME;
        $authUrl = FrameworkIdentity::DEVELOPER_URL;

        $html[] = '    <meta name="generator" content="' . htmlspecialchars((string) $gen, ENT_COMPAT | ENT_HTML5) . '">';

        if (!empty($slogan)) {
            $html[] = "    <meta name='framework-slogan' content='{$slogan}'>";
        }
        if (!empty($desc)) {
            $html[] = '    <meta name="framework-description" content="' . htmlspecialchars((string) $desc, ENT_COMPAT | ENT_HTML5) . '">';
        }
        if (!empty($frameUrl)) {
            $html[] = '    <meta name="framework-url" content="' . htmlspecialchars((string) $frameUrl, ENT_COMPAT | ENT_HTML5) . '">';
        }
        $html[] = '    <meta name="author" content="' . htmlspecialchars((string) $author, ENT_COMPAT | ENT_HTML5) . '">';

        if (!empty($authUrl)) {
            $html[] = '    <meta name="author-url" content="' . htmlspecialchars((string) $authUrl, ENT_COMPAT | ENT_HTML5) . '">';
        }

        return implode("\n", $html);
    }

    /**
     * Segment: Resource Links (Preconnect, Dynamic Fonts & Favicon) 🔗⚓
     */
    protected function renderLinks(array $meta, string $context = 'frontend'): array
    {
        $html = [];
        
        // 1. 🚀 Google Fonts Preconnect Optimization
        $html[] = '    <link rel="preconnect" href="https://fonts.googleapis.com">';
        $html[] = '    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';

        // 2. 🔤 Dynamic Fonts under RESOURCE LINKS (Resolved from AssetBuilder queue & Custom Controller Fonts)
        $builder = $this->handler('assetBuilder');
        $compiledFonts = [];
        if ($builder) {
            $built = $builder->build();
            foreach ($built['styles'] as $style) {
                $path = $style['path'] ?? '';
                if (str_contains($path, '/framework-assets/fonts/')) {
                    $compiledFonts[] = '    <link href="' . htmlspecialchars($path) . '" rel="stylesheet" type="text/css" />';
                }
            }
        }

        if (!empty($compiledFonts)) {
            $html = array_merge($html, array_unique($compiledFonts));
        }

        // 3. 🖼️ Favicon Link (Placed cleanly at the bottom of resource links)
        if (!empty($meta['favicon'])) {
            $favicon = (string) $meta['favicon'];
            $type = 'image/x-icon';
            if (str_contains($favicon, '.png')) {
                $type = 'image/png';
            }
            if (str_contains($favicon, '.svg') || str_starts_with($favicon, 'data:image/svg')) {
                $type = 'image/svg+xml';
            }
            $html[] = '    <link rel="icon" href="' . htmlspecialchars($favicon) . '" type="' . $type . '">';
        }

        return $html;
    }
}
