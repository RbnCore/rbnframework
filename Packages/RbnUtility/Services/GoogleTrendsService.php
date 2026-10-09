<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnUtility\Services;

use Rbn\Framework\Core\Base\Services\BaseService;

/**
 * GoogleTrendsService - RBN Framework Google Trends & Social Seed Generator 📈🪐🛰️⚓
 * 
 * RBN Framework Standard.
 * Autonomous Google Trends Parser, Niche Relevancy Filter & AI Seed Generator.
 */
class GoogleTrendsService extends BaseService
{
    /**
     * Google Trends RSS URL Şablonu
     */
    protected string $rssBaseUrl = 'https://trends.google.com/trending/rss';

    /**
     * Google Trends RSS Akışını Çeker ve Ayrıştırır 📡
     * 
     * @param string $geo Ülke kodu (Varsayılan: 'TR')
     * @return array Trend nesneleri listesi
     */
    public function fetchTrends(string $geo = 'TR'): array
    {
        $url = $this->rssBaseUrl . '?geo=' . strtoupper($geo);

        try {
            $res = $this->remote->get($url, [], [], [
                'timeout' => 15,
                'curl' => [
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) RbnGoogleTrendsBot/1.0'
                ]
            ]);

            if (($res['status'] ?? 'error') !== 'success' || empty($res['raw'])) {
                return [];
            }

            $xmlString = $res['raw'];
            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($xmlString);

            if ($xml === false || !isset($xml->channel->item)) {
                return [];
            }

            $trends = [];
            foreach ($xml->channel->item as $item) {
                // ht namespace tanımlarını al
                $namespaces = $item->getNameSpaces(true);
                $ht = isset($namespaces['ht']) ? $item->children($namespaces['ht']) : null;

                $title = trim((string) $item->title);
                $guid = md5(mb_strtolower($title));
                $approxTraffic = $ht ? trim((string) ($ht->approx_traffic ?? '')) : '';
                $pubDate = (string) ($item->pubDate ?? '');

                $newsItems = [];
                if ($ht && isset($ht->news_item)) {
                    foreach ($ht->news_item as $news) {
                        $newsItems[] = [
                            'title'   => trim((string) ($news->news_item_title ?? '')),
                            'snippet' => trim(strip_tags((string) ($news->news_item_snippet ?? ''))),
                            'url'     => trim((string) ($news->news_item_url ?? '')),
                            'source'  => trim((string) ($news->news_item_source ?? ''))
                        ];
                    }
                }

                $trends[] = [
                    'guid'           => $guid,
                    'trend_keyword'  => $title,
                    'approx_traffic' => $approxTraffic,
                    'pub_date'       => $pubDate,
                    'news_items'     => $newsItems
                ];
            }

            return $trends;
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Trendleri Proje Sektör/Niche Kelimelerine Göre Filtreler 🎯
     * 
     * @param array $trends Trend listesi
     * @param array|string $nicheKeywords Aranacak sektör kelimeleri
     * @return array Sektörle eşleşen trendler
     */
    public function filterByNiche(array $trends, array|string $nicheKeywords): array
    {
        if (is_string($nicheKeywords)) {
            $nicheKeywords = array_map('trim', explode(',', $nicheKeywords));
        }

        $keywords = array_filter(array_map(fn($k) => mb_strtolower((string) $k, 'UTF-8'), (array) $nicheKeywords));

        if (empty($keywords)) {
            return $trends;
        }

        $filtered = [];
        foreach ($trends as $trend) {
            $haystack = mb_strtolower($trend['trend_keyword'], 'UTF-8');
            foreach ($trend['news_items'] as $news) {
                $haystack .= ' ' . mb_strtolower($news['title'] . ' ' . $news['snippet'], 'UTF-8');
            }

            $matched = false;
            foreach ($keywords as $kw) {
                if (preg_match('/\b' . preg_quote($kw, '/') . '\b/iu', $haystack) || mb_strpos($haystack, $kw) !== false) {
                    $matched = true;
                    break;
                }
            }

            if ($matched) {
                $filtered[] = $trend;
            }
        }

        return $filtered;
    }
}

