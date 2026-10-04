<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnUtility\Services;

use Rbn\Framework\Core\Base\Services\BaseService;

/**
 * RssParserService - Standalone RSS / Atom Parser Package Service 🪐🛰️⚓
 */
class RssParserService extends BaseService
{
    /**
     * Fetch and parse an RSS or Atom feed URL.
     */
    public function parse(string $url): array
    {
        try {
            $res = $this->remote->get($url, [], [], [
                'timeout' => 15,
                'curl' => [
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) RbnFrameworkRssBot/1.0'
                ]
            ]);

            if (($res['status'] ?? 'error') !== 'success' || empty($res['raw'])) {
                return [];
            }
            $xmlString = $res['raw'];

            // Suppress warnings from broken XML
            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($xmlString);
            if ($xml === false) {
                return [];
            }

            $items = [];
            // Parse standard RSS 2.0
            if (isset($xml->channel->item)) {
                foreach ($xml->channel->item as $item) {
                    $guid = (string)($item->guid ?? $item->link);
                    $items[] = [
                        'guid' => $guid,
                        'title' => trim((string)$item->title),
                        'description' => trim(strip_tags((string)($item->description ?? $item->children('content', true)->encoded ?? ''))),
                        'link' => trim((string)$item->link),
                        'pub_date' => (string)$item->pubDate
                    ];
                }
            }
            // Parse Atom feed
            elseif (isset($xml->entry)) {
                foreach ($xml->entry as $entry) {
                    $guid = (string)($entry->id ?? $entry->link['href']);
                    $link = '';
                    if (isset($entry->link)) {
                        $link = (string)$entry->link['href'];
                        if (empty($link) && isset($entry->link[0])) {
                            $link = (string)$entry->link[0]['href'];
                        }
                    }
                    
                    $items[] = [
                        'guid' => $guid,
                        'title' => trim((string)$entry->title),
                        'description' => trim(strip_tags((string)($entry->summary ?? $entry->content ?? ''))),
                        'link' => trim($link),
                        'pub_date' => (string)($entry->published ?? $entry->updated ?? '')
                    ];
                }
            }

            return $items;

        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Parses active RSS sources, checks for duplicates, and sifts candidates.
     */
    public function getUnprocessedCandidates(array $params): array
    {
        $projectKey = $params['project_key'] ?? null;
        $postModelName = $params['local_model'] ?? ($params['post_model'] ?? null);
        $allowedKeywords = $params['allowed_keywords'] ?? [];
        $taskLog = $this->service('base.taskLog');

        if (!$projectKey || !$postModelName) {
            return [];
        }

        $postModel = $this->model($postModelName);
        $sourceModel = $this->model('rss.source');
        $blacklistModel = $this->model('rss.blacklist');

        if (!$postModel || !$sourceModel || !$blacklistModel) {
            return [];
        }

        // 1. Aktif RSS Kaynaklarını çek (Ortak model üzerinden)
        //
        // [FW-ALTYAPI-3 / H · G4] `RssSourceModel` artık `scoped = true`;
        // elle `where('project_key', $projectKey)` ikinci süzgeçti. Açık hedef
        // `withProjectScope()` ile verilir — görev `runInProjectContext()` ile
        // koştuğu için sonuç DEĞİŞMEZ, kapsam ise artık ikinci söz vermiyor.
        $sources = $sourceModel->withProjectScope((string) $projectKey)->query()
            ->where('is_active', 1)
            ->get()
            ->all();

        if (empty($sources)) {
            if ($taskLog) {
                $taskLog->step('RSS Tarama', "Proje [{$projectKey}] için aktif RSS kaynağı bulunamadı.");
            }
            return [];
        }

        if ($taskLog) {
            $taskLog->step('RSS Kaynak Taraması', count($sources) . " adet aktif RSS kaynağı taranıyor.");
        }

        // 2. Canlı feedleri oku ve aday listesini oluştur (mükerrerleri eleyerek)
        $candidates = [];
        $totalParsed = 0;
        $duplicateCount = 0;

        foreach ($sources as $source) {
            try {
                $items = $this->parse($source['url']);
                foreach ($items as $item) {
                    $guid = $item['guid'] ?? $item['link'];
                    if (empty($guid)) {
                        continue;
                    }

                    $totalParsed++;

                    // A: Post tablosunda mükerrerlik kontrolü yap
                    $existsInPosts = $postModel->query()
                        ->where('guid', $guid)
                        ->orWhere('source_url', $item['link'])
                        ->first();

                    // B: Ortak rss_blacklist tablosunda mükerrerlik kontrolü yap
                    $existsInBlacklist = $blacklistModel->withProjectScope((string) $projectKey)->query()
                        ->where('guid', $guid)
                        ->first();

                    if ($existsInPosts || $existsInBlacklist) {
                        $duplicateCount++;
                        continue;
                    }

                    $title = $item['title'] ?? '';
                    $description = $item['description'] ?? '';

                    // Opsiyonel anahtar kelime filtresi
                    if (!empty($allowedKeywords)) {
                        $combinedText = strtolower($title . ' ' . $description);
                        $hasKeyword = false;
                        foreach ($allowedKeywords as $keyword) {
                            if (str_contains($combinedText, $keyword)) {
                                $hasKeyword = true;
                                break;
                            }
                        }
                        if (!$hasKeyword) {
                            continue;
                        }
                    }

                    $pubTime = !empty($item['pub_date']) ? strtotime($item['pub_date']) : time();
                    $candidates[] = [
                        'guid' => $guid,
                        'title' => $title,
                        'description' => strip_tags($description),
                        'link' => $item['link'] ?? '',
                        'source_id' => $source['id'],
                        'source_name' => $source['name'] ?? 'Bilinmeyen Kaynak',
                        'source_language_code' => !empty($source['language']) ? $source['language'] : 'en',
                        'pub_time' => $pubTime
                    ];
                }
            } catch (\Throwable $t) {
                if ($taskLog) {
                    $taskLog->step('RSS Kaynak Hatası', "{$source['name']} ({$source['url']}) taranırken hata: " . $t->getMessage());
                }
            }
        }

        if ($taskLog) {
            $taskLog->step(
                'RSS Filtreleme Özeti',
                "Toplam {$totalParsed} haber taranıp okundu. {$duplicateCount} adet mükerrer/karalistedeki içerik elendi. " . count($candidates) . " adet işlenmemiş aday hazırlandı."
            );
        }

        return $candidates;
    }

    /**
     * Mükerrerliği önlemek için GUID bilgisini ortak rss_blacklist tablosuna kaydeder.
     */
    public function blacklistCandidate(string $projectKey, string $guid): void
    {
        try {
            // [FW-ALTYAPI-3 / H · G4] Kapsamlı model: açık anahtar
            // `withProjectScope()` ile verilir, `project_key` elle yazılmaz
            // (yazma yolu kapsamdan alır).
            $existing = $this->model('rss.blacklist')
                ->withProjectScope($projectKey)->query()
                ->where('guid', '=', $guid)
                ->first();

            if (!$existing) {
                $this->model('rss.blacklist')
                    ->withProjectScope($projectKey)->save([
                        'guid' => $guid
                    ]);
            }

            $taskLog = $this->service('base.taskLog');
            if ($taskLog) {
                $taskLog->step('Kara Liste Kaydı', "GUID [{$guid}] ortak rss_blacklist tablosuna kaydedildi.");
            }
        } catch (\Throwable $e) {
            // Ignore duplicate entry constraint violations safely
        }
    }
}
