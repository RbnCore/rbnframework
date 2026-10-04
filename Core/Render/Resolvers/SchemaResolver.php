<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Render\Resolvers;

use Rbn\Framework\Core\Base\BaseComponent;
use Rbn\Framework\Core\Base\Services\BaseService;

/**
 * SchemaResolver - Context resolver for page SEO metadata and request paths 🧠🛰️⚓
 */
class SchemaResolver extends BaseComponent
{
    /**
     * Resolve page SEO metadata (title, description, URL, appName) from current active controller context.
     */
    public function meta(): array
    {
        $controller = BaseService::get()->activeController();
        $seo = [];
        $pageTitle = '';

        if ($controller && method_exists($controller, 'get')) {
            $seo = $controller->get('seo_overrides') ?? [];
            $pageTitle = $controller->get('page_title') ?? '';
        }

        // 🎼 Retrieve title and description from the prepared global SEO service payload if available
        $seoService = $this->service('seo');
        $payload    = $seoService ? $seoService->payload() : [];
        $metaPayload = $payload['meta'] ?? [];

        // 🎼 [LAZY DB PREPARE] payload henüz hazırlanmamışsa (metaseo() çağrılmadıysa)
        // DB'deki global SEO ayarlarından otomatik yükle.
        // Bu sayede WebPage schema inşası her zaman doğru title/description ile çalışır.
        if (empty($metaPayload) && $seoService) {
            $seoService->prepare('project');
            $metaPayload = ($seoService->payload())['meta'] ?? [];
        }

        $title = $metaPayload['title'] ?? $seo['title'] ?? $pageTitle ?? '';
        $description = $metaPayload['description'] ?? $seo['description'] ?? '';
        $appName = $this->appName ?? 'RBN';

        if (str_contains($title, '|')) {
            $title = trim(explode('|', $title)[0]);
        }

        return [
            'title' => $title,
            'description' => $description,
            'url' => url($this->request->path()),
            'appName' => $appName
        ];
    }

    /**
     * Resolve breadcrumb steps dynamically from the HTTP request URI path.
     */
    public function breadcrumbs(?string $currentTitle = null): array
    {
        $steps = [[
            'name' => 'Anasayfa',
            'url' => url()
        ]];

        $path = trim($this->request->path(), '/');
        if (empty($path)) {
            return $steps;
        }

        $parts = explode('/', $path);
        $slugAccumulator = '';
        $totalParts = count($parts);

        for ($i = 0; $i < $totalParts; $i++) {
            $part = $parts[$i];
            $slugAccumulator .= ($i === 0 ? '' : '/') . $part;

            if ($i === $totalParts - 1 && $currentTitle) {
                $steps[] = [
                    'name' => $currentTitle,
                    'url' => url($slugAccumulator)
                ];
                continue;
            }

            $translations = [
                'blog' => 'Blog',
                'iletisim' => 'İletişim',
                'hakkimizda' => 'Hakkımızda',
                'sayfa' => 'Sayfa'
            ];
            $name = $translations[$part] ?? ucwords(str_replace('-', ' ', $part));

            $steps[] = [
                'name' => $name,
                'url' => url($slugAccumulator)
            ];
        }

        return $steps;
    }

    /**
     * Resolve default fallback image from project-routemap.php config 🖼️
     */
    public function resolveDefaultImage(): string
    {
        $projectKey = function_exists('project_key') ? project_key() : 'default';
        $ogImage = '/images/og-image.png';

        $routeMap = $this->resolveProjectConfig('project-routemap');
        if (is_array($routeMap)) {
            $ogImage = $routeMap['view_mapping'][$projectKey]['og_image'] ?? $ogImage;
        }

        return url(ltrim($ogImage, '/'));
    }
}
