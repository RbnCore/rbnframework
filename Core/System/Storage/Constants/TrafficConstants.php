<?php

namespace Rbn\Framework\Core\System\Storage\Constants;

class TrafficConstants
{
    // 🤖 Algılanabilir botlar ve etiketleri
    public const DETECTABLE_BOTS = [
        'googlebot'   => 'Googlebot',
        'bingbot'     => 'Bingbot',
        'yandexbot'   => 'Yandexbot',
        'baiduspider' => 'Baiduspider',
        'ahrefsbot'   => 'AhrefsBot',
        'semrushbot'  => 'SemrushBot',
        'mj12bot'     => 'MJ12Bot',
        'bot'         => 'Crawler',
        'spider'      => 'Crawler',
        'crawl'       => 'Crawler'
    ];

    // ⛔ Sunucu kaynaklarını korumak için engellenecek botların User-Agent kelimeleri
    public const BLOCKED_BOTS = [
        'ahrefsbot',    // Ahrefs SEO botu
        'semrushbot',   // Semrush SEO botu
        'mj12bot',      // Majestic SEO botu
        'dotbot',       // Moz SEO botu
        'rogerbot',     // Moz SEO botu
        'petalbot',     // Huawei botu
        'screaming frog', // SEO site audit aracı
        'barkrowler',   // Agresif reklam botu
        'sitebot',      // Çeşitli SEO botları
        'blexbot'       // Spam/Agresif crawler
    ];
}
