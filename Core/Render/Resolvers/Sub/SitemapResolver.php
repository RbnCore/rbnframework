<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Render\Resolvers\Sub;

use Rbn\Framework\Core\Base\Web\BaseRender;

/**
 * SitemapResolver - Dedicated XML Sitemap Payload & Data Resolver 🗺️🧠⚓
 * Part of RBN 3.5 Sovereign Framework Standards.
 */
class SitemapResolver extends BaseRender
{
    /**
     * [FW-094-CRAWLER / Ö-3] İÇERİĞE BAĞLI standart rotalar 🗺️❓
     *
     * Bu rotalar sabit bir sayfa DEĞİLDİR; iki çekirdek kural devreye girer:
     *
     *   1. `RedirectManager::redirectOldUrls()` (`:410-420`): tek segment'li bir
     *      yolun slug'ı DB'de AKTİF SAYFA olarak varsa yol kalıcı olarak
     *      `/sayfa/{slug}` adresine 301'lenir → rota asla 200 DÖNMEZ.
     *   2. `GroupBaseController::faqs()`: sayfa yoksa ve SSS kaydı da yoksa
     *      ana sayfaya yönlendirir (302).
     *
     * Değerler `|` ile ayrılmış, KUTUPLU kaynaklardır (bkz. `sourcesSatisfied()`):
     *   `blocked-by:shadowed-page` = bu slug'da aktif sayfa VARSA rota gölgelenir
     *      → GİRMEZ (engelleyici; tek başına "listele" demek DEĞİLDİR, yalnız
     *      engel yoksa rotayı serbest bırakır).
     *      Kanonik adres (`/sayfa/{slug}`) zaten yasal sayfa girdisinde listelenir.
     *   `satisfied-by:faq` = en az bir aktif SSS satırı varsa rota kendi içeriğini
     *      200 ile render eder → GİRER (olumlu; varsa en az biri sağlanmalı).
     *
     * KURAL FRAMEWORK STANDARDIDIR (tüm projeler aynı); projeler kendi
     * başına özelleştirmez. Ölçüm: 6 sitede bu rota 302/301/500 dönüyordu.
     *
     * @var array<string,string>
     */
    private const CONTENT_BACKED_ROUTES = [
        'sik-sorulan-sorular' => self::FAQ_SOURCES,
    ];

    /**
     * SSS standart sayfasının kaynak tanımı (`CONTENT_BACKED_ROUTES` ile
     * `FAQ_SLUGS` varyantları aynı kuralı paylaşır).
     */
    private const FAQ_SOURCES = 'blocked-by:shadowed-page|satisfied-by:faq';

    /**
     * [FW-094-CRAWLER / Ö-3] SSS sayfasının slug varyantları.
     * Çekirdek web rotası URI'yi `sik-sorulan-sorular` olarak tanımlar; bazı
     * projeler denetleyicide `sikca-sorulan-sorular` slug'ını arar. İkisi de
     * aynı standart sayfadır.
     *
     * @var string[]
     */
    private const FAQ_SLUGS = ['sik-sorulan-sorular', 'sikca-sorulan-sorular'];

    public function resolvePayload(string $view): array
    {
        $builder = $this->handler('sitemapBuilder')->reset();
        $summary = $this->getSummary();

        if ($view === 'sitemap') {
            if (count($summary) > 1) {
                $builder->addUrls($summary);
                return [
                    'builder' => $builder,
                    'is_index' => true,
                    'generated_at' => now()
                ];
            } else {
                $data = $this->getData('pages');
                $builder->addUrls($data);
                return [
                    'builder' => $builder,
                    'is_index' => false,
                    'generated_at' => now()
                ];
            }
        } else {
            $cleanView = str_replace('sitemap-', '', $view);
            $parts = explode('-', $cleanView);
            $page = (int) array_pop($parts);
            $type = implode('-', $parts);

            if ($page === 0) {
                $type = $cleanView;
                $page = 1;
            }
            $data = $this->getData($type, $page);
            $builder->addUrls($data);
            return [
                'builder' => $builder,
                'is_index' => false,
                'generated_at' => now()
            ];
        }
    }

    public function getSummary(): array
    {
        $summaries = [];
        $siteUrl = $this->provider('crawler')->getProject('url');
        $sources = $this->provider('crawler')->getProject('source');

        foreach ($sources as $name => $sourceCfg) {
            $total = (int) $this->getData($name, 1, true);
            if ($total > 0) {
                $pages = (int) ceil($total / 50000);
                if ($pages === 1) {
                    $summaries[] = ['loc' => $siteUrl . "/sitemap-{$name}.xml"];
                } else {
                    for ($i = 1; $i <= $pages; $i++) {
                        $summaries[] = ['loc' => $siteUrl . "/sitemap-{$name}-{$i}.xml"];
                    }
                }
            } else {
                $summaries[] = ['loc' => $siteUrl . "/sitemap-{$name}.xml"];
            }
        }
        return $summaries;
    }

    public function getData(string $type, int $page = 1, bool $onlyCount = false)
    {
        $sources = $this->provider('crawler')->getProject('source');
        if (!isset($sources[$type]))
            return $onlyCount ? 0 : [];

        if ($type === 'pages') {
            if ($onlyCount) {
                return 1 + $this->provider('crawler')->getActivePagesCount();
            }
            return $this->resolveCorePages();
        }

        if ($onlyCount) {
            return (int) $this->resolver('crawler')->callServiceMethod($sources[$type], 'count_method', 'getCount');
        }

        $siteUrl = $this->provider('crawler')->getProject('url');
        $entries = (array) $this->resolver('crawler')->callServiceMethod($sources[$type], 'entries_method', 'getEntries', $page);

        $formatted = [];
        $prefix = $sources[$type]['prefix'] ?? '';

        // ⚡ N+1 SORGUSUNU ENGELLEMEK İÇİN KATEGORİLERİ HAFİZAYA TEK SORGUSUYLA AL 🧠
        $categoryMap = [];
        if (str_contains($prefix, '{category_slug}')) {
            $catModelClass = isset($sources['categories']['model'])
                ? $this->resolver('crawler')->resolveDirectModel($sources['categories']['model'], '')
                : \Rbn\Framework\Core\Database\Models\Project\ContentCategoryModel::class;
            if (class_exists($catModelClass)) {
                $catModel = new $catModelClass();
                if (method_exists($catModel, 'all') || method_exists($catModel, 'query')) {
                    $allCats = method_exists($catModel, 'query') ? $catModel->query()->get()->all() : $catModel->all();
                    foreach ($allCats as $catItem) {
                        $cArr = is_array($catItem) ? $catItem : (method_exists($catItem, 'toArray') ? $catItem->toArray() : (array) $catItem);
                        if (isset($cArr['id']) && isset($cArr['slug'])) {
                            $categoryMap[(int) $cArr['id']] = $cArr['slug'];
                        }
                    }
                }
            }
        }

        foreach ($entries as $entry) {
            $getData = function ($key) use ($entry) {
                if (is_array($entry))
                    return $entry[$key] ?? null;
                if (is_object($entry))
                    return $entry->{$key} ?? null;
                return null;
            };

            // [FW-094-NOINDEX] Sayfa düzeyi noindex: yalnız bu kayıt çıktıdan düşer.
            if ($this->provider('crawler')->isPageNoindex($entry)) {
                continue;
            }

            $path = $getData('url');
            $slug = $getData('slug');

            if (!$path && $slug) {
                $rawPrefix = $prefix;
                if (str_contains($rawPrefix, '{category_slug}')) {
                    $catId = $getData('category_id');
                    $catSlug = ($catId !== null && isset($categoryMap[(int) $catId])) ? $categoryMap[(int) $catId] : '';

                    if (!empty($catSlug)) {
                        $rawPrefix = str_replace('{category_slug}', $catSlug, $rawPrefix);
                    } else {
                        $rawPrefix = str_replace('{category_slug}', '', $rawPrefix);
                    }
                }
                $cleanPrefix = trim(preg_replace('#/+#', '/', $rawPrefix), '/');
                $path = ($cleanPrefix !== '' ? '/' . $cleanPrefix : '') . '/' . ltrim((string) $slug, '/');
            }

            if ($path) {
                $formatted[] = [
                    'loc' => $siteUrl . '/' . ltrim($path, '/'),
                    'priority' => $getData('priority') ?? ($sources[$type]['priority'] ?? '0.7'),
                    'changefreq' => $getData('changefreq') ?? 'weekly',
                    'lastmod' => $getData('lastmod') ?? ($getData('created_at') ?? $getData('updated_at') ?? null)
                ];
            }
        }
        return $formatted;
    }

    public function resolveCorePages(): array
    {
        $entries = [];
        $siteUrl = $this->provider('crawler')->getProject('url');

        $projectKey = project_key();
        $activeModule = $this->getRouteConfig($projectKey, 'module') ?? '';

        $entries[] = ['loc' => $siteUrl . '/', 'priority' => '1.0', 'changefreq' => 'daily'];

        $discoveredUris = [];
        try {
            $routes = \Rbn\Framework\Core\Routes\Engine\Providers\Router::getInstance()->getRoutes();
            $targetNamespace = 'Modules\\Frontend\\' . $activeModule;

            foreach ($routes as $route) {
                $uri = (string) $route['uri'];
                $action = is_string($route['action']) ? $route['action'] : '';

                if (
                    in_array('GET', $route['methods']) &&
                    !str_contains($uri, '{') && !str_contains($uri, '(') &&
                    !str_contains($uri, 'modal') &&
                    stripos($action, $targetNamespace) !== false &&
                    !$this->resolver('robotsResolver')->isPathRestricted($uri) &&
                    $uri !== '/' &&
                    $this->hasRouteContent(ltrim($uri, '/'))
                ) {
                    $cleanUri = ltrim($uri, '/');
                    $discoveredUris[] = $cleanUri;
                    $entries[] = ['loc' => $siteUrl . '/' . $cleanUri, 'priority' => '0.7', 'changefreq' => 'weekly'];
                }
            }
        } catch (\Throwable $e) {
            // [FW-094-CRAWLER / Ö-3] Sessizlik kaldırıldı: rota tablosu
            // okunamazsa sitemap eksik çıkar ve nedeni görünmez.
            error_log('[RBN-CRAWLER] SitemapResolver::resolveCorePages: ' . $e->getMessage());
        }

        $pages = $this->provider('crawler')->getActivePages();
        foreach ($pages as $p) {
            // [FW-094-NOINDEX] Sayfa düzeyi noindex: sitemap + llms.txt (bu çıktıyı kullanır).
            if ($this->provider('crawler')->isPageNoindex($p)) {
                continue;
            }
            $slug = trim($p['slug'], '/');
            if (in_array($slug, $discoveredUris)) {
                continue;
            }
            $entries[] = [
                'loc' => $siteUrl . '/sayfa/' . $slug,
                'priority' => '0.8',
                'changefreq' => 'monthly',
                'lastmod' => $p['created_at'] ?? $p['updated_at'] ?? null
            ];
        }

        return $entries;
    }

    /**
     * [FW-094-CRAWLER / Ö-3] Bu rota sitemap'e girebilir mi? 🗺️✅
     *
     * Her rota için "gerçekten 200 dönecek mi" kontrolü framework seviyesinde
     * yapılamaz (içerik üretimi denetleyicinin işidir). Yapılabilen ve ölçülen
     * şey: İÇERİĞE BAĞLI standart rotaların (bkz. `CONTENT_BACKED_ROUTES`)
     * veritabanında karşılığı var mı. Yoksa denetleyici ana sayfaya
     * yönlendirir ve sitemap'te 301/302 dönen bir adres listelenmiş olur.
     *
     * Güvenli taraf: içerik sorgusu hata verirse rota DIŞARIDA bırakılır
     * (listelemektense eksik liste; eksik sayfa bulunmaz, bozuk adres olmaz).
     *
     * @param string $cleanUri Baştaki `/` temizlenmiş URI
     */
    private function hasRouteContent(string $cleanUri): bool
    {
        $uri = trim(strtolower($cleanUri), '/');

        $kaynaklar = self::CONTENT_BACKED_ROUTES[$uri] ?? null;
        if ($kaynaklar === null && in_array($uri, self::FAQ_SLUGS, true)) {
            $kaynaklar = self::FAQ_SOURCES;
        }

        if ($kaynaklar === null) {
            return true;   // içeriğe bağlı OLMAYAN rota: mevcut filtreler yeterli
        }

        $crawler = $this->provider('crawler');

        // `sik-sorulan-sorular` proje tarafında `sikca-sorulan-sorular` olarak
        // aranabiliyor: SSS rotasında iki slug varyantı da denenir.
        $slugAdaylari = in_array($uri, self::FAQ_SLUGS, true) ? self::FAQ_SLUGS : [$uri];

        try {
            return self::sourcesSatisfied(
                $kaynaklar,
                static function (string $kaynak) use ($crawler, $slugAdaylari): bool {
                    if ($kaynak === 'shadowed-page') {
                        // Aktif sayfa varsa `redirectOldUrls()` bu rotayı kalıcı
                        // olarak `/sayfa/{slug}` adresine 301'ler → rota 200 dönmez.
                        foreach ($slugAdaylari as $slug) {
                            if ($crawler->hasActivePage($slug)) {
                                return true;
                            }
                        }
                        return false;
                    }
                    if ($kaynak === 'faq') {
                        return (bool) $crawler->hasActiveFaqs();
                    }
                    return false;
                }
            );
        } catch (\Throwable $e) {
            error_log('[RBN-CRAWLER] SitemapResolver::hasRouteContent: ' . $e->getMessage());
            return false;   // belirsizlikte güvenli taraf: rota listelenmez
        }
    }

    /**
     * [FW-094-BULGU-DUZELT / O-1] Kutuplu kaynak tanımını değerlendirir.
     *
     * Kaynaklar `|` ile ayrılır; her biri `blocked-by:<ad>` (engelleyici) ya da
     * `satisfied-by:<ad>` (olumlu) önekiyle kutbunu açıkça söyler:
     *   - Engelleyici kaynaklardan HERHANGİ BİRİ tetiklenmişse → rota GİRMEZ.
     *   - Olumlu kaynak tanımlıysa EN AZ BİRİ sağlanmalı → rota GİRER.
     *   - Yalnız engelleyici tanımlıysa ve hiçbiri tetiklenmemişse → rota GİRER.
     * Böylece tek engelleyicili bir satır, engel olmasa bile rotayı sessizce
     * dışlamaz.
     *
     * @param callable(string):bool $probe Kaynak adı → koşul geçerli mi
     */
    private static function sourcesSatisfied(string $spec, callable $probe): bool
    {
        $hasPositive = false;
        $satisfied = false;

        foreach (explode('|', $spec) as $entry) {
            if (str_starts_with($entry, 'blocked-by:')) {
                if ($probe(substr($entry, 11))) {
                    return false;
                }
            } elseif (str_starts_with($entry, 'satisfied-by:')) {
                $hasPositive = true;
                if (!$satisfied && $probe(substr($entry, 13))) {
                    $satisfied = true;
                }
            }
        }

        return $hasPositive ? $satisfied : true;
    }
}
