<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Render\Resolvers\Sub;

use Rbn\Framework\Core\Base\Web\BaseRender;

/**
 * LlmsResolver - Dedicated LLMs.txt Payload & AI Map Resolver 🤖📄⚓
 * Part of RBN 3.5 Sovereign Framework Standards.
 */
class LlmsResolver extends BaseRender
{
    public function resolvePayload(): array
    {
        $siteUrl = $this->provider('crawler')->getProject('url');
        
        $crawlerMap = $this->resolver('crawler')->resolveCrawlerMap();
        $siteName = $crawlerMap['brand']['name'] ?? ($this->appName ?: 'RBN Application');

        $sections = [];

        $corePages = $this->resolver('sitemapResolver')->resolveCorePages();
        $pagesList = [];
        foreach ($corePages as $page) {
            $url = $page['loc'];
            $path = parse_url($url, PHP_URL_PATH) ?: '/';
            $cleanPath = trim($path, '/');
            $title = ($path === '/') ? 'Ana Sayfa' : ucwords(str_replace(['/', '-', '_'], [' ', ' ', ' '], $cleanPath));
            $pagesList[] = "- [{$title}]({$url})";
        }

        if (!empty($pagesList)) {
            $sections['Ana Sayfalar & Rehberler'] = $pagesList;
        }

        $limit = $context['llms_limit'] ?? 50;
        $sources = $this->provider('crawler')->getProject('source');
        foreach ($sources as $type => $sourceConfig) {
            // 🛡️ Yalnızca CrawlerMap üzerinde llms => true (veya tanımlı) olan kaynakları işle
            if (isset($sourceConfig['llms']) && !$sourceConfig['llms']) {
                continue;
            }

            $typeTitle = $sourceConfig['title'] ?? ucwords(str_replace(['_', '-'], ' ', $type));
            $entries = (array) $this->resolver('crawler')->callServiceMethod($sourceConfig, 'entries_method', 'getEntries', 1);
            $typeList = [];

            $prefix = $sourceConfig['prefix'] ?? '';

            // ⚡ KATEGORİ HARİTASINI HAFİZAYA TEK SORGUSUYLA AL 🧠
            $categoryMap = [];
            if (str_contains($prefix, '{category_slug}') && isset($sources['categories']['model'])) {
                $catModelClass = $this->resolver('crawler')->resolveDirectModel(
                    $sources['categories']['model'], 
                    ''
                );
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

            foreach (array_slice($entries, 0, $limit) as $entry) {
                $getData = function ($key) use ($entry) {
                    if (is_array($entry)) return $entry[$key] ?? null;
                    if (is_object($entry)) return $entry->{$key} ?? null;
                    return null;
                };

                $path = $getData('url');
                $slug = $getData('slug');

                $titleColumn = $sourceConfig['title_column'] ?? ($sourceConfig['title_key'] ?? 'title');
                $title = $getData($titleColumn) ?? ($getData('title') ?? ($getData('name') ?? null));

                if (!$title && ($path || $slug)) {
                    $targetPath = $path ?: $slug;
                    $title = ucwords(str_replace(['-', '_'], ' ', basename((string)$targetPath)));
                }

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
                    $fullUrl = $siteUrl . '/' . ltrim($path, '/');
                    $typeList[] = "- [{$title}]({$fullUrl})";
                }
            }

            if (!empty($typeList)) {
                $sections[$typeTitle] = $typeList;
            }
        }

        return [
            'site_name' => $siteName,
            'site_url' => $siteUrl,
            'sections' => $sections
        ];
    }
}
