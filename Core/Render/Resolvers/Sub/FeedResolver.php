<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Render\Resolvers\Sub;

use Rbn\Framework\Core\Base\Web\BaseRender;

/**
 * FeedResolver - Dedicated RSS XML Feed Payload Resolver 📡📰⚓
 * Part of RBN Framework Framework Standards.
 */
class FeedResolver extends BaseRender
{
    public function resolvePayload(): array
    {
        $sources = $this->provider('crawler')->getProject('source');
        $siteUrl = $this->provider('crawler')->getProject('url');

        $posts = [];
        $title = $this->appName . ' Feed';
        $feedLink = $siteUrl . '/feed';
        $channelLink = $siteUrl;

        $postsSource = null;
        $postsSourceKey = 'posts';
        foreach ($sources as $key => $source) {
            if (!empty($source['feed'])) {
                $postsSource = $source;
                $postsSourceKey = $key;
                break;
            }
        }
        if (!$postsSource) {
            $postsSource = $sources['posts'] ?? null;
        }

        $categoriesSource = $sources['categories'] ?? null;

        if ($postsSource) {
            $prefix = $postsSource['prefix'] ?? '/blog';
            $channelLink = $siteUrl . '/' . ltrim($prefix, '/');

            $categoryMap = [];
            if ($categoriesSource) {
                $cats = (array) $this->resolver('crawler')->callServiceMethod($categoriesSource, 'raw_feed_method', 'getEntries', 1);
                foreach ($cats as $cat) {
                    $cArray = is_array($cat) ? $cat : (method_exists($cat, 'toArray') ? $cat->toArray() : (array) $cat);
                    if (isset($cArray['id']) && isset($cArray['slug'])) {
                        $categoryMap[(int) $cArray['id']] = $cArray['slug'];
                    }
                }
            }

            $entries = (array) $this->resolver('crawler')->callServiceMethod($postsSource, 'raw_feed_method', 'getEntries', 1);
            // [FW-094-NOINDEX] Sayfa düzeyi noindex kayıtlar feed'e girmez.
            $entries = array_values(array_filter(
                $entries,
                fn ($entry) => !$this->provider('crawler')->isPageNoindex($entry)
            ));
            $entries = array_slice($entries, 0, 30);

            foreach ($entries as $entry) {
                $eArray = is_array($entry) ? $entry : (method_exists($entry, 'toArray') ? $entry->toArray() : (array) $entry);

                $path = $eArray['url'] ?? null;
                $slug = $eArray['slug'] ?? null;

                if (!$path && $slug) {
                    $rawPrefix = $prefix;
                    if (str_contains($rawPrefix, '{category_slug}')) {
                        $catId = $eArray['category_id'] ?? null;
                        $catSlug = ($catId !== null && isset($categoryMap[(int)$catId])) ? $categoryMap[(int)$catId] : '';
                        $rawPrefix = str_replace('{category_slug}', $catSlug, $rawPrefix);
                    }
                    $cleanPrefix = trim(preg_replace('#/+#', '/', $rawPrefix), '/');
                    $path = ($cleanPrefix !== '' ? '/' . $cleanPrefix : '') . '/' . ltrim((string) $slug, '/');
                }

                if ($path) {
                    $titleVal = $eArray['title'] ?? ($eArray['name'] ?? '');
                    $summaryVal = $eArray['summary'] ?? ($eArray['short_description'] ?? ($eArray['excerpt'] ?? ($eArray['description'] ?? ($eArray['content'] ?? null))));
                    if (!empty($summaryVal)) {
                        $summaryVal = $this->helper('text')->cutText(strip_tags((string) $summaryVal), 350);
                    }

                    $dateVal = $eArray['created_at'] ?? ($eArray['updated_at'] ?? 'now');

                    $posts[] = [
                        'title' => $titleVal,
                        'link' => $siteUrl . '/' . ltrim($path, '/'),
                        'pubDate' => date(DATE_RSS, strtotime((string)$dateVal)),
                        'description' => $summaryVal ?? '',
                        'image' => $eArray['image'] ?? null
                    ];
                }
            }
        }

        return [
            'title' => $title,
            'channelLink' => $channelLink,
            'feedLink' => $feedLink,
            'description' => $this->appName . ' en güncel yazıları.',
            'posts' => $posts
        ];
    }
}
