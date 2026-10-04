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
                    $uri !== '/'
                ) {
                    $cleanUri = ltrim($uri, '/');
                    $discoveredUris[] = $cleanUri;
                    $entries[] = ['loc' => $siteUrl . '/' . $cleanUri, 'priority' => '0.7', 'changefreq' => 'weekly'];
                }
            }
        } catch (\Throwable $e) {
        }

        $pages = $this->provider('crawler')->getActivePages();
        foreach ($pages as $p) {
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
}
