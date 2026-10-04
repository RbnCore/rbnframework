<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnApi\Providers;

use Rbn\Framework\Core\Base\BaseComponent;

/**
 * TmdbProvider - TMDB Connectivity and HTTP Request Hub 🎬🛰️⚓
 */
class TmdbProvider extends BaseComponent
{
    private string $baseUrl = 'https://api.themoviedb.org/3';

    /**
     * TMDB API'sine istek gönderen ana metot. 🚀
     */
    public function call(string $endpoint, array $params = [], ?string $projectKey = null): array
    {
        $apiKey = $this->manager('api')->resolveApiKey('tmdb', $projectKey);

        if (empty($apiKey)) {
            return [
                'status' => 'error',
                'message' => 'RBN 3.5: TMDB API yetkisi veya anahtarı bulunamadı! 🛡️⚓'
            ];
        }

        $params['api_key'] = $apiKey;
        $url = "{$this->baseUrl}/" . ltrim($endpoint, '/');
        
        $result = $this->remote->get($url, $params);

        return $result;
    }

    /**
     * Search movies, TV series, or actors/persons on TMDB.
     */
    public function search(string $query, string $type = 'multi', string $language = 'tr-TR', int $page = 1, ?string $projectKey = null): array
    {
        $endpoint = match ($type) {
            'series', 'tv' => 'search/tv',
            'movie' => 'search/movie',
            'person', 'actor' => 'search/person',
            default => 'search/multi'
        };

        return $this->call($endpoint, [
            'query'    => $query,
            'language' => $language,
            'page'     => $page
        ], $projectKey);
    }

    /**
     * Get detailed information for a movie.
     */
    public function getMovie(string|int $tmdbId, string $language = 'tr-TR', ?string $projectKey = null): array
    {
        return $this->call("movie/{$tmdbId}", [
            'language'           => $language,
            'append_to_response' => 'credits,videos',
            'include_video_language' => 'tr,en,null'
        ], $projectKey);
    }

    /**
     * Get detailed information for a TV series.
     */
    public function getSeries(string|int $tmdbId, string $language = 'tr-TR', ?string $projectKey = null): array
    {
        return $this->call("tv/{$tmdbId}", [
            'language'           => $language,
            'append_to_response' => 'credits,videos',
            'include_video_language' => 'tr,en,null'
        ], $projectKey);
    }

    /**
     * Get trending / popular movies or series.
     */
    public function getPopular(string $type = 'movie', int $page = 1, string $language = 'tr-TR', ?string $projectKey = null): array
    {
        $endpoint = $type === 'series' ? 'tv/popular' : 'movie/popular';
        return $this->call($endpoint, [
            'page'     => $page,
            'language' => $language
        ], $projectKey);
    }

    /**
     * Get detailed information for an actor / person (including combined credits). 🎭🎬
     */
    public function getPerson(string|int $tmdbId, string $language = 'tr-TR', ?string $projectKey = null): array
    {
        $params = [
            'append_to_response' => 'combined_credits,external_ids,images'
        ];
        if (!empty($language)) {
            $params['language'] = $language;
        }

        return $this->call("person/{$tmdbId}", $params, $projectKey);
    }

    /**
     * Discover movies or series with smart filters (language, genre, streaming platform). 🍿🔍
     */
    public function discoverTmdb(string $type = 'movie', array $filters = [], string $language = 'tr-TR', ?string $projectKey = null): array
    {
        $endpoint = $type === 'series' ? 'discover/tv' : 'discover/movie';

        $params = [
            'language' => $language,
            'page' => $filters['page'] ?? 1,
            'sort_by' => 'popularity.desc'
        ];

        // 1. Dil Filtresi (Yerli / Yabancı)
        if (!empty($filters['lang'])) {
            if ($filters['lang'] === 'local') {
                $params['with_original_language'] = 'tr';
            } elseif ($filters['lang'] === 'foreign') {
                $params['without_original_languages'] = 'tr';
            }
        }

        // 2. Tür Filtresi
        if (!empty($filters['genre'])) {
            $params['with_genres'] = $filters['genre'];
        }

        // 3. Platform Filtresi (Netflix, Disney+, Amazon Prime vb.)
        if (!empty($filters['platform'])) {
            $params['with_watch_providers'] = $filters['platform'];
            $params['watch_region'] = 'TR'; // Türkiye kataloğuna göre filtreleme
        }

        return $this->call($endpoint, $params, $projectKey);
    }
}
