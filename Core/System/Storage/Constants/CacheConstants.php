<?php

namespace Rbn\Framework\Core\System\Storage\Constants;

class CacheConstants
{
    // Varsayılan cache süresi (saniye)
    public const DEFAULT_TTL = 300; // 5 dakika
    public const TTL_SHORT = 60;    // 1 dakika
    public const TTL_MEDIUM = 600;   // 10 dakika
    public const TTL_LONG = 1800;  // 30 dakika
    public const TTL_DAY = 86400; // 1 gün

    // Sistem cache anahtarları
    public const SYSTEM_KEYS = [
        'SETTINGS_ALL'    => 'settings_all',
        'SETTINGS_SHIELD' => 'settings_shield',
        'SIDEBAR_ROLE'    => 'sidebar_%s',
    ];

    // Okunabilir (MD5'siz) dosya ismi üretecek önekler 📜
    public const READABLE_PREFIXES = [
        'settings_',
        'comm_',
        'api_',
        'sidebar_',
        'users_',
        'sitemap_xml',
        'robots_txt',
        'rss_feed',
        'llms_txt',
        'cron_',
        'content_',
        'mail_', // RbnEmail: kısa ömürlü ileti gövdesi önbelleği
    ];

    // Keşif (Discovery) Önekleri 📜
    public const DISCOVERY_PREFIX_PROJECT = 'project_';
    public const DISCOVERY_PREFIX_DOMAIN = 'domain_';
    public const DISCOVERY_PREFIX_GROUP = 'group_';
}
