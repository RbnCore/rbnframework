<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnApi\Services;

use Rbn\Framework\Core\Base\Services\BaseService;

/**
 * YoutubeService - Sovereign YouTube Orchestrator 📽️🛰️⚓
 * 
 * RBN 3.5 Masterpiece: High-level interface for YouTube Data API operations.
 * @property \Rbn\Framework\Packages\RbnApi\Providers\YoutubeProvider $youtube
 */
class YoutubeService extends BaseService
{
    /**
     * Standard YouTube API Proxy (Gemini Symmetry) 🤖
     */
    public function call(string $endpoint, array $params = [], ?string $projectKey = null): array
    {
        return $this->provider('apiYoutube')->call($endpoint, $params, $projectKey);
    }

    /**
     * Fetch raw YouTube video metadata. 🎬
     */
    public function getVideo(string $videoId, ?string $projectKey = null): array
    {
        return $this->provider('apiYoutube')->getVideo($videoId, $projectKey);
    }

    /**
     * Fetch raw YouTube channel metadata. 📺
     */
    public function getChannel(string $channelId, ?string $projectKey = null): array
    {
        return $this->provider('apiYoutube')->getChannel($channelId, $projectKey);
    }

    /**
     * Fetch items from a YouTube playlist. 🎵
     */
    public function getPlaylistItems(string $playlistId, int $maxResults = 50, ?string $pageToken = null, ?string $projectKey = null): array
    {
        return $this->provider('apiYoutube')->getPlaylistItems($playlistId, $maxResults, $pageToken, $projectKey);
    }

    /**
     * Fetch active live streams for a channel. 📡
     */
    public function getLiveStreams(string $channelId, int $maxResults = 10, ?string $projectKey = null): array
    {
        return $this->provider('apiYoutube')->getLiveStreams($channelId, $maxResults, $projectKey);
    }

    /**
     * Fetch, sort by view count, and return the most popular videos of a channel. 🎬🏆
     */
    public function getPopularVideos(string $channelId, int $maxResults = 6, string $type = 'video', ?string $projectKey = null): array
    {
        return $this->provider('apiYoutube')->getPopularVideos($channelId, $maxResults, $type, $projectKey);
    }
}
