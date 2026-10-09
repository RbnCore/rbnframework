<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnApi\Models;

/**
 * ApiKeysRegistry - API Keys Configuration & Whitelist Dictionary 🛡️🛰️⚓
 * RBN Framework Standard.
 */
class ApiKeysRegistry
{
    /**
     * Desteklenen API'ler ve bunlara karşılık gelen Config/Master anahtar isimleri 🗺️
     */
    public const MAP = [
        'gemini'      => 'GEMINI_API_KEY',
        'gemini_free' => 'GEMINI_API_KEY_FREE',
        'openai'      => 'OPENAI_API_KEY',
        'youtube'   => 'YOUTUBE_API_KEY',
        'shopier'   => 'SHOPIER_API_KEY',
        'instagram' => [
            'access_token' => 'INSTAGRAM_ACCESS_TOKEN',
            'account_id'   => 'INSTAGRAM_ACCOUNT_ID',
        ],
        'facebook'  => [
            'page_id'      => 'FACEBOOK_PAGE_ID',
            'access_token' => 'FACEBOOK_ACCESS_TOKEN',
        ],
        'virustotal'=> 'VIRUSTOTAL_API_KEY',
        'google'    => [
            'api_key'       => 'GOOGLE_API_KEY',
            'analytics_key' => 'GOOGLE_ANALYTICS_KEY',
        ],
        'tmdb'      => 'TMDB_API_KEY',
        'telegram' => [
            'bot_token'      => 'TELEGRAM_BOT_TOKEN',
            'bot_username'   => 'TELEGRAM_BOT_USERNAME',
            'webhook_secret' => 'TELEGRAM_WEBHOOK_SECRET',
        ],
        'twitter'   => [
            'consumer_key'        => 'TWITTER_CONSUMER_KEY',
            'consumer_secret'     => 'TWITTER_CONSUMER_SECRET',
            'access_token'        => 'TWITTER_ACCESS_TOKEN',
            'access_token_secret' => 'TWITTER_ACCESS_TOKEN_SECRET',
        ]
    ];

    /**
     * Kota sınırı ve maliyeti olan (Ücretli) API anahtar isimleri 💳
     */
    public const PAID_KEYS = [
        'gemini',
        'google',
        'openai'
    ];

    /**
     * İlgili API anahtarının ücretli/kotalı tipte olup olmadığını kontrol eder 🛡️
     */
    public static function isPaidKey(string $apiName): bool
    {
        return in_array(strtolower($apiName), self::PAID_KEYS, true);
    }
}
