<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnApi\Providers;

use Rbn\Framework\Core\Base\BaseComponent;

/**
 * YoutubeProvider - YouTube Data API v3 Connectivity Hub 📽️🛰️⚓
 * 
 * RBN 3.5 Masterpiece: Autonomous YouTube orchestrator.
 */
class YoutubeProvider extends BaseComponent
{
    private string $baseUrl = 'https://www.googleapis.com/youtube/v3/';

    /**
     * YouTube API'sine çağrı yapar. 🚀
     */
    public function call(string $endpoint, array $params = [], ?string $projectKey = null): array
    {
        $apiKey = $this->manager('api')->resolveApiKey('youtube', $projectKey);

        if (empty($apiKey)) {
            return [
                'status' => 'error',
                'message' => 'RBN 3.5: YouTube API yetkisi veya anahtarı bulunamadı! 🛡️⚓'
            ];
        }

        $params['key'] = $apiKey;
        $result = $this->remote->request('GET', $this->baseUrl . ltrim($endpoint, '/'), $params);

        return $result['status'] === 'success'
            ? ['status' => 'success', 'data' => $result['data']]
            : ['status' => 'error', 'message' => $result['message'] ?? 'YouTube API request failed.', 'data' => $result['data'] ?? []];
    }

    /**
     * Fetch raw YouTube video metadata. 🎬
     */
    public function getVideo(string $videoId, ?string $projectKey = null): array
    {
        return $this->call('videos', [
            'id' => $videoId,
            'part' => 'snippet,status,contentDetails,statistics'
        ], $projectKey);
    }

    /**
     * Fetch raw YouTube channel metadata. 📺
     */
    public function getChannel(string $channelId, ?string $projectKey = null): array
    {
        $params = ['part' => 'snippet,statistics,brandingSettings'];
        if (str_starts_with($channelId, '@')) {
            $params['forHandle'] = $channelId;
        } else {
            $params['id'] = $channelId;
        }
        return $this->call('channels', $params, $projectKey);
    }

    /**
     * Fetch items from a YouTube playlist. 🎵
     */
    public function getPlaylistItems(string $playlistId, int $maxResults = 50, ?string $pageToken = null, ?string $projectKey = null): array
    {
        $params = [
            'playlistId' => $playlistId,
            'part' => 'snippet,status,contentDetails',
            'maxResults' => $maxResults
        ];
        if ($pageToken) {
            $params['pageToken'] = $pageToken;
        }
        return $this->call('playlistItems', $params, $projectKey);
    }

    /**
     * Fetch active live streams for a channel. 📡
     */
    public function getLiveStreams(string $channelId, int $maxResults = 10, ?string $projectKey = null): array
    {
        return $this->call('search', [
            'part' => 'snippet',
            'channelId' => $channelId,
            'eventType' => 'live',
            'type' => 'video',
            'maxResults' => $maxResults
        ], $projectKey);
    }

    /**
     * Fetch, sort by view count, and return top popular videos. 🎬🏆
     * Supports types: 'video' (standard videos only), 'shorts' (shorts only), 'all' (both).
     */
    public function getPopularVideos(string $channelId, int $maxResults = 6, string $type = 'video', ?string $projectKey = null): array
    {
        $videos = [];
        $type = strtolower($type);

        // 1. If we only want standard videos, fetch Shorts playlist items to build an exclusion lookup list
        $shortsIds = [];
        if ($type === 'video') {
            $shortsPlaylistId = 'UUSH' . substr($channelId, 2);
            $shortsResult = $this->getPlaylistItems($shortsPlaylistId, 50, null, $projectKey);
            if (($shortsResult['status'] ?? '') === 'success' && !empty($shortsResult['data']['items'])) {
                foreach ($shortsResult['data']['items'] as $item) {
                    $cDetails = $item['contentDetails'] ?? [];
                    $vId = $cDetails['videoId'] ?? null;
                    if ($vId) {
                        $shortsIds[$vId] = true;
                    }
                }
            }
        }

        // 2. Select target playlist (UUSH for Shorts, UU for standard uploads/all)
        $targetPlaylistId = ($type === 'shorts' ? 'UUSH' : 'UU') . substr($channelId, 2);
        $playlistResult = $this->getPlaylistItems($targetPlaylistId, 50, null, $projectKey);

        if (($playlistResult['status'] ?? '') === 'success' && !empty($playlistResult['data']['items'])) {
            $videoIds = [];
            $videoMap = [];

            foreach ($playlistResult['data']['items'] as $item) {
                $contentDetails = $item['contentDetails'] ?? [];
                $videoId = $contentDetails['videoId'] ?? null;
                if ($videoId) {
                    // Skip Shorts if we are fetching standard videos only
                    if ($type === 'video' && isset($shortsIds[$videoId])) {
                        continue;
                    }

                    $snippet = $item['snippet'] ?? [];
                    $videoIds[] = $videoId;
                    $videoMap[$videoId] = [
                        'id' => $videoId,
                        'title' => $snippet['title'] ?? '',
                        'description' => $snippet['description'] ?? '',
                        'thumbnail' => $snippet['thumbnails']['high']['url'] ?? ($snippet['thumbnails']['medium']['url'] ?? ''),
                        'publishedAt' => isset($snippet['publishedAt']) ? date('d.m.Y', strtotime($snippet['publishedAt'])) : '',
                        'views' => 0
                    ];
                }
            }

            if (!empty($videoIds)) {
                // Fetch video stats to get viewCount
                $videoIdsStr = implode(',', $videoIds);
                $statsResult = $this->call('videos', [
                    'part' => 'statistics',
                    'id' => $videoIdsStr
                ], $projectKey);

                if (($statsResult['status'] ?? '') === 'success' && !empty($statsResult['data']['items'])) {
                    foreach ($statsResult['data']['items'] as $statsItem) {
                        $vId = $statsItem['id'] ?? null;
                        if ($vId && isset($videoMap[$vId])) {
                            $vStats = $statsItem['statistics'] ?? [];
                            $videoMap[$vId]['views'] = (int) ($vStats['viewCount'] ?? 0);
                        }
                    }
                }

                // Sort by views descending
                uasort($videoMap, function ($a, $b) {
                    return ($b['views'] ?? 0) <=> ($a['views'] ?? 0);
                });

                // Slice popular videos
                $videos = array_slice(array_values($videoMap), 0, $maxResults);
            }
        }

        return $videos;
    }
}
