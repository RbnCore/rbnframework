<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Support\Bridges\Traits;

/**
 * ViewHelperTrait - Sovereign UI & Template Helpers 🧬🎨⚓
 * 
 * RBN 3.5 Masterpiece: Positioned in the Bridge layer next to NormalizationTrait.
 * Provides core template shortcuts like CSRF tokens, method spoofing, 
 * and metadata tags directly within the Component DNA.
 */
trait ViewHelperTrait
{
    /**
     * Helper: Returns CSRF Hidden Token Value. 🛡️⚓
     */
    public function csrfToken(): string
    {
        return $this->service('form')->token()['token'];
    }

    /**
     * Helper: Generates CSRF Hidden Input Field. 📥🛡️
     */
    public function csrfField(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . $this->csrfToken() . '">';
    }

    /**
     * Helper: Generates CSRF Meta Tag (For AJAX Hubs). 🛰️🛡️
     */
    public function csrfMeta(): string
    {
        return '<meta name="csrf-token" content="' . $this->csrfToken() . '">';
    }

    /**
     * Helper: Generates Method Spoofing Input Field (PUT, DELETE etc.). 🏷️⚓
     */
    public function methodField(string $method): string
    {
        return '<input type="hidden" name="_method" value="' . strtoupper($method) . '">';
    }

    /**
     * Helper: Generates Masked Media Proxy URL (Optional Category & Size) 🖼️⚡
     */
    public static function mediaUrl(?string $url, ?string $category = null, string $size = 'w500', string $fallback = '/images/default.png'): string
    {
        if (empty($url)) {
            return $fallback;
        }

        if (str_contains($url, 'project-assets/media')) {
            return $url;
        }

        $targetUrl = $url;
        $filename = basename(parse_url($url, PHP_URL_PATH) ?: 'media.jpg');

        $encodedUrl = rtrim(strtr(base64_encode($targetUrl), '+/', '-_'), '=');
        $catPath = !empty($category) ? trim($category, '/') . '/' : '';

        return url("/project-assets/media/{$catPath}{$encodedUrl}/{$filename}");
    }

    /**
     * Autonomous Output Processor: Normalizes relative frontend links, media paths, and template tags 🌐🛰️⚓
     * 
     * @param string $html Raw HTML content
     * @param string|null $targetBaseUrl Target base URL for relative links
     * @param array $proxyConfig View layer proxy configuration and exclusions
     */
    public static function normalizeHtmlUrls(string $html, ?string $targetBaseUrl = null, array $proxyConfig = []): string
    {
        if (empty($html)) {
            return '';
        }

        $baseUrl = rtrim($targetBaseUrl ?: \Rbn\Framework\Core\System\Paths\Paths::project()->baseUrl(), '/');
        $dashPrefix = function_exists('project_data') ? (project_data('dashboard_prefix') ?: 'rbn') : 'rbn';
        $dashPattern = preg_quote($dashPrefix, '/');

        // Extract proxy setup passed from View layer
        $prPrefix = $proxyConfig['pr_prefix'] ?? null;
        $fwPrefix = $proxyConfig['fw_prefix'] ?? null;
        $proxyPath = $proxyConfig['proxy_path'] ?? null;
        $mediaPrefix = $proxyConfig['media_prefix'] ?? '/media/';

        // 1. Transform frontend relative preview links: href="/blog/..." -> href="http://domain.test/blog/..."
        $html = preg_replace_callback(
            '/href=["\']\/(?!(?:' . $dashPattern . '\/|admin\/|assets\/|css\/|js\/|#|javascript:))([^"\']+)["\']/i',
            function ($matches) use ($baseUrl) {
                return 'href="' . $baseUrl . '/' . ltrim($matches[1], '/') . '"';
            },
            $html
        );

        // 2. Autonomous Global Media Proxy & Relative Image Transformer 🖼️
        $html = preg_replace_callback(
            '/(<img\s+[^>]*?\bsrc=["\'])([^"\']+)(["\'][^>]*>)/i',
            function ($m) use ($baseUrl, $prPrefix, $fwPrefix, $proxyPath, $mediaPrefix) {
                $prefix = $m[1];
                $src = trim($m[2]);
                $suffix = $m[3];

                if (empty($src) || str_starts_with($src, 'data:')) {
                    return $m[0];
                }

                // Check View layer proxy exclusions
                if ($prPrefix && str_starts_with($src, $prPrefix))
                    return $m[0];
                if ($fwPrefix && str_starts_with($src, $fwPrefix))
                    return $m[0];
                if ($mediaPrefix && str_contains($src, $mediaPrefix))
                    return $m[0];

                // If proxyPath is active, route images through central mediaUrl helper 🖼️
                if (!empty($proxyPath)) {
                    $proxyUrl = self::mediaUrl($src);
                    return $prefix . $proxyUrl . $suffix;
                }

                // Standard relative image URL resolution
                if (!str_starts_with($src, 'http://') && !str_starts_with($src, 'https://') && !str_starts_with($src, '//')) {
                    $fullUrl = $baseUrl . '/' . ltrim($src, '/');
                    return $prefix . $fullUrl . $suffix;
                }

                return $m[0];
            },
            $html
        );

        // 3. Parse dynamic template URL tags: {{ url('/...') }}
        $html = preg_replace_callback('/{{\s*url\((.*?)\)\s*}}/', function ($matches) {
            $slug = html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8');
            $slug = trim($slug, " '\"");
            return url($slug);
        }, $html);

        // 4. Parse global placeholders
        return str_replace('{year}', date('Y'), $html);
    }
}
