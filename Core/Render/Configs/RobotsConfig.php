<?php

namespace Rbn\Framework\Core\Render\Configs;

use Rbn\Framework\Core\Base\Data\BaseConfig;

/**
 * RobotsConfig - Robots.txt Configuration Registry 🤖
 * 
 * Centralizes all path disallows, allows, and crawler-specific rules.
 */
class RobotsConfig extends BaseConfig
{
    public const NAME = 'RbnRobots';
    public const VERSION = '1.0';

    /**
     * Bot-specific configuration blocks.
     */
    public const BOT_RULES = [];

    /**
     * Standard disallow rules for robots.txt.
     */
    public const ROBOTS_DISALLOWS = [
        ...self::CORE_DISALLOWS,
        ...self::ASSET_DISALLOWS
    ];

    /**
     * System-critical paths that should always be disallowed for analytics and sitemaps.
     */
    public const SYSTEM_DISALLOWS = [
        ...self::CORE_DISALLOWS,
        '/home',
        '/sitemap.xml',
        '/robots.txt',
        '/migrations',
        '/docs',
        '/fw-proxy'
    ];

    /**
     * Shared core disallowed paths.
     *
     * FW-ROBOTS-TAKIP-109: buraya SADECE yonetim/sistem yollari girer.
     *
     * 1) `RouteBlueprint::HONEYPOT_PATHS` ve `LOGIN_PATH` YOKTUR.
     *    Bu liste robots.txt'e DIZI olarak yayilir ve arama motoru ON-EK
     *    eslestirmesi yapar. Tuzak yollar (`/site`, `/www`, `/test`, ...)
     *    zaten `RedirectManager` tarafindan TAM eslesmeyle 301 -> / yonlendirilir,
     *    yani bot onlari hic ziyaret etmez; robots'ta yazmanin tek sonucu
     *    (i) tuzaklari herkese ilan etmek, (ii) meşru on-ekleri kapatmak.
     *    Olculdu: `/site` on-ek olarak `/sitemap.xml`, `/sitemap-{type}.xml`
     *    ve `/sitemap.xsl` adreslerini kapatiyordu — robots.txt ayni dosyada
     *    `Sitemap:` bildirirken kendi sitemap'ini engelliyordu.
     *    Tek merkez: tuzaklar yalniz `HONEYPOT_PATHS`'ta yasar, buraya yayilmaz.
     *
     * 2) GIZLI giris yolu (`LOGIN_PATH` = `/rbn-admin`) yazilmaz; giris
     *    sayfasinin dizinlenmemesi `noindex` ile saglanir (AuthViewController).
     *    Boylece gizli yol tum framework sitelerine ilan edilmez.
     */
    public const CORE_DISALLOWS = [
        '/admin',
        '/developer',
        '/user',
        '/panel',
        '/dashboard',
        '/register',
        '/auth',
        '/forgot-password',
        '/reset-password',
        '/verify-code',
        '/guest',
        '/app',
        '/Modules',
        '/rbnframework',
        '/vendor',
        '/storage',
        '/views',
        '/Views',
        '/layouts',
        '/Layouts',
        '/search',
        '/ara',
        '/wp',
        '/wp-content',
        '/wp-includes',
        '/wp-json',
        '/wp-login',
        '/xmlrpc',
        '/administrator',
        '/git',
        '/env',
        '/phpinfo',
        '/setup',
        '/install',
        '/config',
        '/temp',
        '/tmp',
        '/sql',
        '/db',
        '/api'
    ];

    /**
     * Asset and file type disallows.
     */
    public const ASSET_DISALLOWS = [
        '/*.php$',
        '/*.json$'
    ];

    /**
     * Standard path allow rules.
     */
    public const ALLOWED_PATHS = [
        '/images/',
        '/css/',
        '/js/',
        '/uploads/',
        '/project-assets/',
        '/rbn-assets/',
        '/framework-assets/',
        '/ads.txt',
        '/app-ads.txt'
    ];

    /**
     * Default Dynamic Configuration used by RobotsService.
     */
    public const DEFAULT_CONFIG = [
        'crawl_delay' => 1,
        'bing_crawl_delay' => 1,
        'yandex_crawl_delay' => 2,
        'ecommerce_enabled' => false,
        'google_news_enabled' => false,
        'social_media_crawl' => true,
        'china_market' => false,
        'yandex_clean_param' => true,
        'custom_allowed_paths' => [],
        'custom_disallowed_paths' => [],
        'custom_banned_bots' => [],
        'cms_version' => SeoConfig::VERSION
    ];
}
