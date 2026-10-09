<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnPipeline\Services;

use Rbn\Framework\Core\Base\Attributes\Component;
use Rbn\Framework\Core\Base\Services\BaseManager;
use Rbn\Framework\Core\Support\Exceptions\CronTaskException;
use Rbn\Framework\Packages\RbnPipeline\Concerns\PipelineResolverTrait;

/**
 * ExternalFetchManager - RBN Framework Centralized Manager for External & Internal Candidate Fetching 🛰️🧠🚀
 * 
 * Location: RbnPipeline/Services/ExternalFetchManager.php
 * RBN Framework Standard.
 * Manages Google Trends, RSS feeds, Draft candidates, Blacklisting, and Duplicate Post Prevention across all projects.
 */
#[Component(alias: 'externalFetch', type: 'manager')]
class ExternalFetchManager extends BaseManager
{
    use PipelineResolverTrait;


    /**
     * 1. Google Trends Canlı Aday Akışını Çeker, Mükerrer ve Kara Liste Filtresinden Geçirir 📈
     */
    public function fetchTrendsCandidate(array $params): ?array
    {
        $trendsService = $this->service('googleTrends');
        if (!$trendsService || !method_exists($trendsService, 'fetchTrends')) {
            return null;
        }

        $geo = (string) ($params['geo'] ?? 'TR');
        $rawTrends = $trendsService->fetchTrends($geo);
        $projectKey = $params['project_key'] ?? (function_exists('project_key') ? project_key() : '');
        $pModelName = $params['local_model'] ?? ($params['post_model'] ?? 'blog');

        if (empty($rawTrends)) {
            return null;
        }

        // 🛡️ Mükerrer ve Kara Liste Filtresi
        $cleanTrends = $this->filterUniqueCandidates($rawTrends, $projectKey, $pModelName, 'trend');
        if (empty($cleanTrends)) {
            return null;
        }

        // Kategorileri çöz
        $catModelName = $params['category_model'] ?? null;
        $allCategories = $this->resolveCategories($catModelName);

        $targetCategoryIds = !empty($params['target_category_ids']) ? array_map('intval', (array) $params['target_category_ids']) : [];
        $targetCategoryNames = !empty($params['target_categories']) ? array_map('strtolower', (array) $params['target_categories']) : [];

        $filteredCategories = [];
        if (is_array($allCategories)) {
            foreach ($allCategories as $cat) {
                $cId = (int) ($cat['id'] ?? 0);
                $cSlug = strtolower((string) ($cat['slug'] ?? ''));
                $cName = strtolower((string) ($cat['name'] ?? ''));

                if (!empty($targetCategoryIds) && in_array($cId, $targetCategoryIds, true)) {
                    $filteredCategories[] = $cat;
                } elseif (!empty($targetCategoryNames) && (in_array($cSlug, $targetCategoryNames, true) || in_array($cName, $targetCategoryNames, true))) {
                    $filteredCategories[] = $cat;
                }
            }
        }

        $activeCategories = !empty($filteredCategories) ? $filteredCategories : (is_array($allCategories) ? $allCategories : []);
        if (empty($activeCategories)) {
            return null;
        }

        // 🎯 Niş İzolasyon Filtresi:allowed_keywords parametresi varsa sadece bu anahtar kelimeleri içeren trendleri tut 🛡️
        $allowedKeywords = !empty($params['allowed_keywords'])
            ? (is_array($params['allowed_keywords']) ? $params['allowed_keywords'] : array_map('trim', explode(',', (string) $params['allowed_keywords'])))
            : [];

        if (!empty($allowedKeywords)) {
            $nicheTrends = [];
            foreach ($cleanTrends as $trend) {
                $tTitle = mb_strtolower((string) ($trend['trend_keyword'] ?? ''));
                $tSnippets = '';
                if (!empty($trend['news_items']) && is_array($trend['news_items'])) {
                    foreach ($trend['news_items'] as $n) {
                        $tSnippets .= ' ' . mb_strtolower(($n['title'] ?? '') . ' ' . ($n['snippet'] ?? ''));
                    }
                }
                $fullSearchText = $tTitle . ' ' . $tSnippets;

                foreach ($allowedKeywords as $kw) {
                    $kwLower = mb_strtolower(trim((string) $kw));
                    if (!empty($kwLower) && mb_stripos($fullSearchText, $kwLower) !== false) {
                        $nicheTrends[] = $trend;
                        break;
                    }
                }
            }
            $cleanTrends = $nicheTrends;
        }

        if (empty($cleanTrends)) {
            return null;
        }

        // Kategori-Niş Eşleştirmesi
        $selectedTrend = null;
        $selectedCatId = 0;
        $selectedCatName = '';
        $selectedCatSlug = '';

        $allNicheKws = !empty($params['niche_keywords'])
            ? (is_array($params['niche_keywords']) ? $params['niche_keywords'] : array_map('trim', explode(',', (string) $params['niche_keywords'])))
            : [];

        // Envanter dosyasındaki dev kategori anahtar kelimeler kütüphanesini DataHelper ile yükle (Resources/Data/category_keywords.json) 🗃️📚🎯
        $dataHelper = $this->helper('data');
        $masterDict = ($dataHelper && method_exists($dataHelper, 'get')) ? $dataHelper->get('category_keywords') : [];
        if (!is_array($masterDict)) {
            $masterDict = [];
        }

        // 1. AŞAMA: Gündemdeki tüm trendler içinde kategorilerimizle eşleşen bir trend ara 🎯
        foreach ($cleanTrends as $trend) {
            $tTitle = mb_strtolower((string) ($trend['trend_keyword'] ?? ''));
            $tSnippets = '';
            if (!empty($trend['news_items']) && is_array($trend['news_items'])) {
                foreach ($trend['news_items'] as $n) {
                    $tSnippets .= ' ' . mb_strtolower(($n['title'] ?? '') . ' ' . ($n['snippet'] ?? ''));
                }
            }
            $fullSearchText = $tTitle . ' ' . $tSnippets;

            foreach ($activeCategories as $cat) {
                $cId = (int) ($cat['id'] ?? 0);
                $cName = (string) ($cat['name'] ?? '');
                $cSlug = strtolower(trim((string) ($cat['slug'] ?? '')));

                $catKws = array_filter([$cName, $cSlug]);

                // Envanter veri kütüphanesinden kelimeleri al 📚
                if (!empty($masterDict[$cSlug])) {
                    $catKws = array_merge($catKws, (array) $masterDict[$cSlug]);
                }

                // Task parametrelerinden geçen özel kelimeleri ekle 🎯
                foreach ($allNicheKws as $kw) {
                    if (stripos($cName, $kw) !== false || stripos($cSlug, $kw) !== false) {
                        $catKws[] = $kw;
                    }
                }

                $catKws = array_unique(array_filter(array_map('trim', $catKws)));

                foreach ($catKws as $kw) {
                    if (!empty($kw) && mb_stripos($fullSearchText, $kw) !== false) {
                        $selectedTrend = $trend;
                        $selectedCatId = $cId;
                        $selectedCatName = $cName;
                        $selectedCatSlug = $cSlug;
                        break 2; // Eşleşen ilk trend bulundu!
                    }
                }
            }
        }

        // 2. AŞAMA: Eğer hiçbir trend kategorilerle eşleşmediyse Fallback modunu devreye sok 🧠🎯
        $isFallback = false;
        if (!$selectedTrend) {
            $isFallback = true;
            $selectedTrend = $cleanTrends[array_rand($cleanTrends)];
            $fallbackCatId = (int) ($params['fallback_category_id'] ?? 4);

            if ($fallbackCatId > 0 && is_array($allCategories)) {
                foreach ($allCategories as $cat) {
                    if ((int) ($cat['id'] ?? 0) === $fallbackCatId) {
                        $selectedCatName = (string) ($cat['name'] ?? '');
                        $selectedCatSlug = (string) ($cat['slug'] ?? '');
                        break;
                    }
                }
            }
        }

        if (empty($selectedTrend)) {
            return null;
        }

        $trendTitle = (string) ($selectedTrend['trend_keyword'] ?? 'Gündem Trendleri');
        $snippets = [];
        if (!empty($selectedTrend['news_items']) && is_array($selectedTrend['news_items'])) {
            foreach (array_slice($selectedTrend['news_items'], 0, 3) as $n) {
                if (!empty($n['title']) || !empty($n['snippet'])) {
                    $snippets[] = trim(($n['title'] ?? '') . ': ' . ($n['snippet'] ?? ''));
                }
            }
        }

        return [
            'id' => 0,
            'project_key' => $projectKey,
            'source' => 'trends',
            'source_type' => 'trends',
            'guid' => $selectedTrend['guid'] ?? md5(mb_strtolower($trendTitle)),
            'title' => $trendTitle,
            'summary' => implode(' | ', $snippets),
            // Eğer kelime eşleşmesi sağlandıysa sabit kategori verilir; sağlayan yoksa AI dinamik seçsin diye 0 (unlocked) bırakılır 🎯
            'category_id' => $isFallback ? 0 : $selectedCatId,
            'fallback_category_id' => $params['fallback_category_id'] ?? 4,
            'category_name' => $selectedCatName,
            'category_slug' => $selectedCatSlug,
            'target_category_hint' => $selectedCatName,
            'approx_traffic' => $selectedTrend['approx_traffic'] ?? '',
            'news_items' => $selectedTrend['news_items'] ?? []
        ];
    }

    /**
     * 2. RSS Akışından Aday İçerik Çeker, Mükerrer ve Kara Liste Filtresinden Geçirir 📰
     */
    public function fetchRssCandidate(array $params): ?array
    {
        $rssParser = $this->service('rssParser');
        if (!$rssParser || !method_exists($rssParser, 'parse')) {
            return null;
        }

        $projectKey = $params['project_key'] ?? (function_exists('project_key') ? project_key() : '');
        $pModelName = $params['local_model'] ?? ($params['post_model'] ?? 'blog');
        $rssSourceModel = 'app.rss.source';

        $sourceModel = $this->model($rssSourceModel);
        if (!$sourceModel || !method_exists($sourceModel, 'query')) {
            throw CronTaskException::modelNotFound($rssSourceModel, $projectKey);
        }

        // [FW-ALTYAPI-3 / H · G4] `RssSourceModel` artık `scoped = true`; açık anahtar
        // `withProjectScope()` ile verilir (elle süzgeç ikinci süzgeçti).
        $sources = $sourceModel->withProjectScope((string) $projectKey)->query()->where('is_active', 1);

        $activeSources = $sources->get()->all();
        if (empty($activeSources)) {
            return null;
        }

        $allItems = [];
        foreach ($activeSources as $source) {
            $url = (string) (is_object($source) ? ($source->url ?? '') : ($source['url'] ?? ''));
            $srcName = (string) (is_object($source) ? ($source->name ?? '') : ($source['name'] ?? ''));
            if (!empty($url)) {
                $parsed = $rssParser->parse($url);
                if (is_array($parsed)) {
                    foreach ($parsed as &$pItem) {
                        $pItem['source_name'] = !empty($srcName) ? $srcName : (!empty($pItem['source']) ? $pItem['source'] : '');
                    }
                    unset($pItem);
                    $allItems = array_merge($allItems, $parsed);
                }
            }
        }

        if (empty($allItems)) {
            return null;
        }

        $cleanItems = $this->filterUniqueCandidates($allItems, $projectKey, $pModelName, 'rss');
        if (empty($cleanItems)) {
            return null;
        }

        // 🎯 Niş / İzin Verilen Anahtar Kelime Filtresi (Eğer Tanımlanmışsa)
        $allowedKeywords = !empty($params['allowed_keywords'])
            ? (is_array($params['allowed_keywords']) ? $params['allowed_keywords'] : array_map('trim', explode(',', (string) $params['allowed_keywords'])))
            : [];

        if (!empty($allowedKeywords)) {
            $nicheItems = [];
            foreach ($cleanItems as $item) {
                $itemTitle = mb_strtolower((string) ($item['title'] ?? ''));
                $itemDesc  = mb_strtolower((string) ($item['description'] ?? ''));
                $fullText  = $itemTitle . ' ' . $itemDesc;

                foreach ($allowedKeywords as $kw) {
                    $kwLower = mb_strtolower(trim((string) $kw));
                    if (!empty($kwLower) && mb_stripos($fullText, $kwLower) !== false) {
                        $nicheItems[] = $item;
                        break;
                    }
                }
            }
            $cleanItems = $nicheItems;
        }

        if (empty($cleanItems)) {
            return null;
        }

        $selected = $cleanItems[array_rand($cleanItems)];
        $title = (string) ($selected['title'] ?? '');
        $sourceUrl = (string) ($selected['link'] ?? '');

        // Kaynak adı çözümleme: 1. Tablodaki isim, 2. RSS içindeki isim, 3. URL host adı 🏷️
        $sourceName = (string) ($selected['source_name'] ?? ($selected['source'] ?? ''));
        if (empty($sourceName) && !empty($sourceUrl)) {
            $host = parse_url($sourceUrl, PHP_URL_HOST);
            $sourceName = $host ? preg_replace('/^www\./i', '', $host) : 'Kaynak';
        }

        return [
            'id' => 0,
            'project_key' => $projectKey,
            'source' => 'rss',
            'source_type' => 'rss',
            'guid' => $selected['guid'] ?? md5(mb_strtolower($title)),
            'title' => $title,
            'summary' => (string) ($selected['description'] ?? ''),
            'source_url' => $sourceUrl,
            'source_name' => $sourceName ?: 'Kaynak'
        ];
    }



    /**
     * 🛡️ Evrensel Süzgeç: Aday listesini alır; kara liste ve veritabanı yayımlanmış makalelerden süzüp temiz adayları döner ⚖️
     */
    public function filterUniqueCandidates(array $candidates, string $projectKey, string $modelAlias, string $type = 'generic'): array
    {
        if (empty($candidates)) {
            return [];
        }

        // 1. Kara Listedeki GUID'leri ve Hash'leri Çek 🛡️
        $blacklistedGuids = $this->getBlacklistedGuids($projectKey);

        // 1B. Veritabanındaki Mevcut (Aktif + Taslak) GUID'leri Doğrudan Çek 🛡️🎯
        $existingDbGuids = $this->getExistingGuids($modelAlias, $projectKey);
        if (!empty($existingDbGuids)) {
            $blacklistedGuids = array_unique(array_merge($blacklistedGuids, $existingDbGuids));
        }

        // 2. Veritabanındaki Son Yayınlanmış Makale Başlıklarını Çek 📰
        $publishedTitles = $this->getPublishedTitles($modelAlias, 200);

        $clean = [];
        foreach ($candidates as $cand) {
            $candArray = is_array($cand) ? $cand : (array) $cand;
            $title = (string) ($candArray['title'] ?? ($candArray['trend_keyword'] ?? ($candArray['name'] ?? '')));
            $guid = (string) ($candArray['guid'] ?? ($candArray['link'] ?? ''));

            if (empty($title)) {
                continue;
            }

            $guidLower = mb_strtolower(trim($guid));
            $titleHash = md5(mb_strtolower(trim($title)));

            // A: Kara liste kontrolü
            if (in_array($guidLower, $blacklistedGuids, true) || in_array($titleHash, $blacklistedGuids, true)) {
                continue;
            }

            // B: Veritabanı yayınlanmış makale çakışması kontrolü 🛡️
            $isDuplicate = false;
            $titleLower = mb_strtolower(trim($title));
            foreach ($publishedTitles as $pubTitle) {
                $pubLower = trim((string)$pubTitle);
                if (empty($pubLower)) continue;

                if ($pubLower === $titleLower) {
                    $isDuplicate = true;
                    break;
                }
                // Sadece başlıklar yeterince uzunsa (>= 12 karakter) ve tam dize olarak içeriyorsa mükerrer kabul et
                if (mb_strlen($titleLower) >= 12 && mb_strlen($pubLower) >= 12) {
                    if (mb_stripos($pubLower, $titleLower) !== false || mb_stripos($titleLower, $pubLower) !== false) {
                        $isDuplicate = true;
                        break;
                    }
                }
            }

            if (!$isDuplicate) {
                $clean[] = $candArray;
            }
        }

        return $clean;
    }

    /**
     * Projeye ait Kara Liste GUID ve Hash Değerlerini Çeker 🛡️
     */
    public function getBlacklistedGuids(string $projectKey): array
    {
        $blacklistModel = $this->model('app.rss.blacklist');
        if ($blacklistModel && method_exists($blacklistModel, 'query')) {
            $rows = $blacklistModel->withProjectScope($projectKey)->query()
                ->select('guid')
                ->get();

            $guids = [];
            foreach ($rows as $row) {
                $val = is_object($row) ? ($row->guid ?? '') : ($row['guid'] ?? '');
                if (!empty($val)) {
                    $guids[] = mb_strtolower(trim((string) $val));
                }
            }
            return $guids;
        }

        return [];
    }

    /**
     * Hedef İçerik/Haber tablosunda kayıtlı tüm GUID'leri çeker (Mükerrerliği AI öncesi sıfıra indirme) 🛡️🎯
     */
    public function getExistingGuids(string $modelAlias, string $projectKey): array
    {
        if (empty($modelAlias)) {
            return [];
        }

        try {
            $model = $this->model($modelAlias);
            if ($model && method_exists($model, 'query')) {
                // [FW-ALTYAPI-3 / H · G4] Kapsamlı model ise açık anahtar
                // `withProjectScope()` ile verilir; kapsamsız modelde eski
                // elle süzgeç korunur (G1 beyanı `single-tenant-now` modelleri).
                $kapsamli = method_exists($model, 'isProjectScoped') && $model->isProjectScoped();
                if ($kapsamli && !empty($projectKey)) {
                    $model = $model->withProjectScope((string) $projectKey);
                }
                $query = $model->query()->select('guid');
                if (!$kapsamli && !empty($projectKey)) {
                    try {
                        $query->where('project_key', $projectKey);
                    } catch (\Throwable $e) {
                    }
                }
                $rows = $query->get();
                $guids = [];
                foreach ($rows as $row) {
                    $val = is_object($row) ? ($row->guid ?? '') : ($row['guid'] ?? '');
                    if (!empty($val)) {
                        $guids[] = mb_strtolower(trim((string) $val));
                    }
                }
                return $guids;
            }
        } catch (\Throwable $e) {
            // Kolon yoksa veya model yüklenemediyse yoksay
        }

        return [];
    }

    /**
     * Projeye ait Veritabanında Yayınlanmış Makale Başlıklarını Çeker 📰
     */
    public function getPublishedTitles(string $modelAlias, int $limit = 200): array
    {
        $posts = $this->resolveRecentPosts($modelAlias, $limit);
        $titles = [];
        foreach ($posts as $p) {
            $t = mb_strtolower(trim((string) (is_object($p) ? ($p->title ?? '') : ($p['title'] ?? ''))));
            if (!empty($t)) {
                $titles[] = $t;
            }
        }
        return $titles;
    }

    /**
     * Yayınlanan haberin GUID ve başlık MD5 karmasını rss_blacklist tablosuna yazar ✍️
     */
    public function blacklistCandidate(string $projectKey, string $guid, string $title = ''): void
    {
        $rssParser = $this->service('rssParser');
        if ($rssParser && method_exists($rssParser, 'blacklistCandidate')) {
            if (!empty($guid)) {
                $rssParser->blacklistCandidate($projectKey, (string) $guid);
            }
            if (!empty($title)) {
                $titleHash = md5(mb_strtolower(trim($title)));
                if ($titleHash !== $guid) {
                    $rssParser->blacklistCandidate($projectKey, $titleHash);
                }
            }
        }
    }
}
