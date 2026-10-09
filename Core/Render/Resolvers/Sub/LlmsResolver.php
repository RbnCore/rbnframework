<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Render\Resolvers\Sub;

use Rbn\Framework\Core\Base\Web\BaseRender;

/**
 * LlmsResolver - Dedicated LLMs.txt Payload & AI Map Resolver 🤖📄⚓
 * Part of RBN Framework Framework Standards.
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
            $pagesList[] = \Rbn\Framework\Core\Render\Builders\LlmsBuilder::link($title, $url);
        }

        if (!empty($pagesList)) {
            $sections['Ana Sayfalar & Rehberler'] = $pagesList;
        }

        $projectLimit = self::llmsLimit($this->projectSetting('llms_limit'));
        $sources = $this->provider('crawler')->getProject('source');
        foreach ($sources as $type => $sourceConfig) {
            // 🛡️ Yalnızca CrawlerMap üzerinde llms => true (veya tanımlı) olan kaynakları işle
            if (isset($sourceConfig['llms']) && !$sourceConfig['llms']) {
                continue;
            }

            // Kaynak başına giriş sınırı: kaynak `llms_limit` > site `project-settings.llms_limit` > 50.
            $limit = self::llmsLimit($sourceConfig['llms_limit'] ?? null, $projectLimit);

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

            // [FW-094-NOINDEX] Sayfa düzeyi noindex kayıtlar llms.txt'ye girmez.
            $entries = array_values(array_filter(
                $entries,
                fn ($entry) => !$this->provider('crawler')->isPageNoindex($entry)
            ));

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
                    $typeList[] = \Rbn\Framework\Core\Render\Builders\LlmsBuilder::link((string) $title, $fullUrl);
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

    /** Varsayılan ve üst sınır: llms.txt makine okuyucusu için kısa kalmalı. */
    public const DEFAULT_LIMIT = 50;
    public const MAX_LIMIT = 1000;

    /** Geçerli sınır (1..MAX_LIMIT); sayı değilse ya da < 1 ise `$fallback`. */
    public static function llmsLimit(mixed $value, int $fallback = self::DEFAULT_LIMIT): int
    {
        $limit = is_numeric($value) ? (int) $value : 0;

        return $limit >= 1 ? min($limit, self::MAX_LIMIT) : $fallback;
    }

    /** Site ayarı (`project-routemap` `view_mapping[<site>]` → `project-settings`). */
    private function projectSetting(string $key): mixed
    {
        try {
            return \Rbn\Framework\Core\System\Config\Config::get('project-settings.' . $key);
        } catch (\Throwable) {
            return null;
        }
    }
}
