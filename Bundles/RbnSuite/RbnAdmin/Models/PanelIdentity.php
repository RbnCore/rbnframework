<?php

namespace Rbn\Framework\Bundles\RbnSuite\RbnAdmin\Models;

use Rbn\Framework\Core\Support\Definitions\System\FrameworkIdentity;
use Rbn\Framework\Core\Base\Data\BaseConfig;

/**
 * PanelIdentity - RbnAdmin Paket Kimliği ve UI Meta Verileri 🛡️🛰️🏛️⚓
 * RBN Framework Standard.
 * 
 * Bu sınıf paketin SEO, Slogan ve Görünüm (View) meta verilerini 
 * "Identity Map" olarak merkezi HUB üzerinden yönetir.
 */
class PanelIdentity extends BaseConfig
{
    public const THEME_COLOR = '#c56a3c'; // Terracotta Craft Primary
    public const FAVICON = '@fw/images/favicon-rbnadmin.svg';

    /**
     * PANEL_IDENTITY: Paketin SEO ve Sayfa Meta Veri Haritası 🏺🗺️⚓
     */
    public const PANEL_IDENTITY = [
        'seo' => [
            'title' => FrameworkIdentity::ADMIN_TITLE . ' | ' . FrameworkIdentity::ADMIN_NAME,
            'description' => 'RBN Framework tabanlı uygulamaların profesyonel, güvenli ve yüksek performanslı yönetim merkezi.',
            'keywords' => 'rbn admin pro, yönetim paneli, rbn framework, admin dashboard, sistem yönetimi, rbn bilişim',
            'robots' => 'noindex, nofollow',
        ],
        'metadata' => [
            'viewport' => 'width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no',
            'theme-color' => self::THEME_COLOR,
            'og:title' => FrameworkIdentity::ADMIN_TITLE . ' | ' . FrameworkIdentity::ADMIN_NAME,
            'og:type' => 'website',
            'og:site_name' => FrameworkIdentity::ADMIN_NAME
        ],
        'ui' => [
            'theme' => self::THEME_COLOR,
            'favicon' => self::FAVICON,
            'slogan' => FrameworkIdentity::ADMIN_SLOGAN
        ]
    ];

    /**
     * Varsayılan Bilgilendirme Metinleri (Settings tablosu boşsa kullanılır) 🎻
     */
    public const DEFAULT_INFO = [
        'dashboard' => [
            'title' => FrameworkIdentity::ADMIN_TITLE,
            'icon' => 'fas fa-tachometer-alt',
            'features' => [
                '✨ Gerçek Zamanlı Analitik Veriler',
                '✨ Sistem Durumu İzleme',
                '✨ Hızlı Erişim Kısayolları'
            ]
        ],
        'settings' => [
            'title' => 'Sistem Ayarları',
            'icon' => 'fas fa-cogs',
            'features' => [
                '✨ Teknik Yapılandırma Yönetimi',
                '✨ Güvenlik ve Protokol Ayarları',
                '✨ Ölçeklenebilir Altyapı Desteği'
            ]
        ]
    ];

    /**
     * View-to-Config Map: Resolves internal keys for specific Admin views. 🛰️
     */
    public const VIEW_MAP = [
        'dashboard' => 'dashboard',
        'settings' => 'settings'
    ];
}
