<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Render\Resolvers;

use Rbn\Framework\Core\Base\BaseComponent;
use Rbn\Framework\Core\Base\Services\BaseService;
use Rbn\Framework\Core\Support\Definitions\Render\AssetConvention;

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
     * Resolve the JSON-LD default image (fallback when the model has no image).
     *
     * [FW-ASSET-KONVANSIYON] KURAL TEK MERKEZ: `AssetConvention`
     * (`images/og-image-{project_key}.{png,jpg,webp}`). Önceki hâlde burada
     * sabit `/images/og-image.png` ve `project-routemap` `og_image` anahtarı
     * kullanılıyordu — meta etiketi ile JSON-LD İKİ AYRI kaynaktan geliyordu
     * ve sabit ad hiçbir projede yoktu.
     *
     *geriye uyum: routemap'te `og_image` / `og-image` anahtarı varsa TOLERE
     * edilir (patron bu anahtarları routemap'ten silmek istiyor; bu görevde
     * routemap dosyalarına DOKUNULMADI — CHANGELOG'da kaldırma notu yazılı).
     * Ancak KURAL ÖNCELİKLİ: dosya gerçekten varsa kullanılır.
     *
     * @return string tam URL; proje dosyası YOKSA boş string (uydurma görsel
     *                     adresi üretilmez — çağıran taraf `image` alanını boş bırakır)
     */
    public function resolveDefaultImage(): string
    {
        $projectKey = function_exists('project_key') ? project_key() : 'default';

        $goreli = AssetConvention::findOgImage($projectKey);
        if ($goreli !== null) {
            return url($goreli);
        }

        // GERİYE UYUM (son çare): routemap'te yazılı GÖRELİ yol gerçekten var mı?
        $routeMap = $this->resolveProjectConfig('project-routemap');
        if (is_array($routeMap)) {
            $eski = $routeMap['view_mapping'][$projectKey]['og_image']
                ?? ($routeMap['view_mapping'][$projectKey]['og-image'] ?? null);

            if (is_string($eski) && $eski !== '') {
                $temiz = ltrim(str_replace('\\', '/', $eski), '/');
                if (!str_contains($temiz, '..')
                    && \Rbn\Framework\Core\System\Paths\Paths::isInitialized()
                    && is_file(\Rbn\Framework\Core\System\Paths\Paths::publicRoot()
                        . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $temiz))) {
                    return url($temiz);
                }
            }
        }

        return '';
    }
}
