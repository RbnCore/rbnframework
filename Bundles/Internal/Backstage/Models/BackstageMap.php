<?php

declare(strict_types=1);

namespace Rbn\Framework\Bundles\Internal\Backstage\Models;

use Rbn\Framework\Core\Base\Data\BaseConfig;

/**
 * BackstageMap - Backstage Architecture & Navigation Map 🗺️🛰️⚓
 * 
 * RBN Framework Standard.
 */
class BackstageMap extends BaseConfig
{
    /**
     * STRUCTURAL MAP: Internal Module Hierarchy 🗺️⚓
     */
    public const MAP = [
        'identity' => [
            'name' => 'backstage',
            'title' => 'Backstage',
            'description' => 'Admin paneli çekirdek yapılandırması, menü mimarisi, e-posta ve ayar yönetimi.',
            'icon' => 'ri-equalizer-line',
        ],
        'sub_modules' => [
            // 1. SIDEBAR YÖNETİMİ
            'sidebar' => [
                'title' => 'Sidebar Yönetimi',
                'description' => 'Admin paneli menülerini ve kategorilerini hiyerarşik olarak düzenleyin.',
                'icon' => 'ri-layout-left-line',
                'sub_modules' => [
                    'category' => ['controller' => 'SidebarController'],
                    'menu' => ['controller' => 'SidebarController']
                ]
            ],
            // 2. E-POSTA AYARLARI
            'email' => [
                'title' => 'E-Posta Ayarları',
                'description' => 'SMTP ve kritik e-posta gönderim yapılandırması.',
                'icon' => 'ri-mail-settings-line',
            ],
            // 3. AYAR MİMARİSİ
            'settings' => [
                'title' => 'Ayar Mimarisi',
                'description' => 'Ayar anahtarları, veri tipleri ve erişim yetkilerini kurgulayın.',
                'icon' => 'ri-settings-5-line',
                'sub_modules' => [
                    'group' => [
                        'controller' => 'SettingsArchitectController',
                    ]
                ]
            ],
        ]
    ];
}
