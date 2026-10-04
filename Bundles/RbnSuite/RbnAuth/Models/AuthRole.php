<?php

namespace Rbn\Framework\Bundles\RbnSuite\RbnAuth\Models;

use Rbn\Framework\Core\Base\Data\BaseConfig;

/**
 * AuthRole - RbnAuth Rol ve Yetki Tablosu ⚖️🎭🛰️⚓
 * RBN 3.5 Masterpiece Standard.
 * 
 * Bu sınıf paketin tüm hiyerarşik rollerini ve yönetici yetkilerini
 * "Authority Map" olarak merkezi HUB üzerinden yönetir.
 */
class AuthRole extends BaseConfig
{
    /**
     * MASTER ROLE TABLE: Sistemin tüm hiyerarşik yapısı 🎭⚓
     */
    public const ROLES = [
        'developer' => [
            'icon' => '👑',
            'name' => 'Geliştirici',
            'job' => 'Geliştirici',
            'department' => 'Teknik yönetim',
            'level' => 100,
            'badge_color' => 'bg-danger text-white',
            'color' => 'danger'
        ],
        'superadmin' => [
            'icon' => '🦸',
            'name' => 'Süper Admin',
            'job' => 'Sistem Yöneticisi',
            'department' => 'Tüm sistem yönetimi',
            'level' => 90,
            'badge_color' => 'bg-dark text-white',
            'color' => 'dark'
        ],
        'admin' => [
            'icon' => '🛡️',
            'name' => 'Admin',
            'job' => 'Site Yöneticisi',
            'department' => 'Yönetim paneli',
            'level' => 80,
            'badge_color' => 'bg-warning text-dark',
            'color' => 'warning'
        ],
        'moderator' => [
            'icon' => '👮',
            'name' => 'Moderatör',
            'job' => 'İçerik Yönetimi',
            'department' => 'Onay yetkisi',
            'level' => 60,
            'badge_color' => 'bg-info',
            'color' => 'info'
        ],
        'editor' => [
            'icon' => '✏️',
            'name' => 'Editör',
            'job' => 'İçerik Editörü',
            'department' => 'Yazma ve düzenleme yetkisi',
            'level' => 40,
            'badge_color' => 'bg-success',
            'color' => 'success'
        ],
        'user' => [
            'icon' => '👤',
            'name' => 'Kullanıcı',
            'job' => 'Standart Kullanıcı',
            'department' => 'Kullanıcı paneli',
            'level' => 20,
            'badge_color' => 'bg-primary',
            'color' => 'primary'
        ],
        'guest' => [
            'icon' => '👥',
            'name' => 'Misafir',
            'job' => 'Misafir Kullanıcı',
            'department' => 'Sınırlı erişim',
            'level' => 5,
            'badge_color' => 'bg-secondary',
            'color' => 'secondary'
        ]
    ];

    /**
     * ADMIN ROLES: Yönetim yetkisine sahip rütbeler 🛡️
     */
    public const ADMIN_ROLES = [
        'developer',
        'superadmin',
        'admin',
        'moderator',
        'editor',
    ];

    /**
     * BYPASS ROLES: Güvenlik limitlerinden (Rate limit, honeypot, csrf vb.) muaf tutulacak üst düzey roller 🛡️🚀
     */
    public const BYPASS_ROLES = [
        'developer',
        'superadmin',
        'admin',
    ];
}
