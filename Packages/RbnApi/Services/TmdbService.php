<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnApi\Services;

use Rbn\Framework\Core\Base\Services\BaseService;

/**
 * TmdbService - Framework-level TMDB Integration Service 🎬🛰️⚓
 */
class TmdbService extends BaseService
{
    /**
     * TMDB API'sine doğrudan istek gönderir 🚀
     */
    public function call(string $endpoint, array $params = [], ?string $projectKey = null): array
    {
        return $this->provider('apiTmdb')->call($endpoint, $params, $projectKey);
    }

    /**
     * Search movies, TV series, or actors/persons on TMDB.
     */
    public function search(string $query, string $type = 'multi', string $language = 'tr-TR', int $page = 1, ?string $projectKey = null): array
    {
        return $this->provider('apiTmdb')->search($query, $type, $language, $page, $projectKey);
    }

    /**
     * Get detailed information for a movie.
     */
    public function getMovie(string|int $tmdbId, string $language = 'tr-TR', ?string $projectKey = null): array
    {
        return $this->provider('apiTmdb')->getMovie((string) $tmdbId, $language, $projectKey);
    }

    /**
     * Get detailed information for a TV series.
     */
    public function getSeries(string|int $tmdbId, string $language = 'tr-TR', ?string $projectKey = null): array
    {
        return $this->provider('apiTmdb')->getSeries((string) $tmdbId, $language, $projectKey);
    }

    /**
     * Get trending / popular movies or series.
     */
    public function getPopular(string $type = 'movie', int $page = 1, string $language = 'tr-TR', ?string $projectKey = null): array
    {
        return $this->provider('apiTmdb')->getPopular($type, $page, $language, $projectKey);
    }

    /**
     * Extract trailer URL from TMDB videos array.
     */
    public function extractTrailerUrl(array $videos): ?string
    {
        $results = !empty($videos['results']) && is_array($videos['results']) ? $videos['results'] : $videos;
        if (!is_array($results)) {
            return null;
        }

        foreach ($results as $video) {
            if (is_array($video) && in_array(strtolower($video['type'] ?? ''), ['trailer', 'teaser'], true) && strtolower($video['site'] ?? '') === 'youtube') {
                return "https://www.youtube.com/watch?v=" . $video['key'];
            }
        }
        foreach ($results as $video) {
            if (is_array($video) && strtolower($video['site'] ?? '') === 'youtube' && !empty($video['key'])) {
                return "https://www.youtube.com/watch?v=" . $video['key'];
            }
        }
        return null;
    }

    /**
     * Get detailed information for an actor / person (including combined credits). 🎭🎬
     */
    public function getPerson(string|int $tmdbId, string $language = 'tr-TR', ?string $projectKey = null): array
    {
        return $this->provider('apiTmdb')->getPerson($tmdbId, $language, $projectKey);
    }

    /**
     * Discover movies or series with smart filters (language, genre, streaming platform). 🍿🔍
     */
    public function discoverTmdb(string $type = 'movie', array $filters = [], string $language = 'tr-TR', ?string $projectKey = null): array
    {
        return $this->provider('apiTmdb')->discoverTmdb($type, $filters, $language, $projectKey);
    }
}
