<?php

namespace Rbn\Framework\Core\Support\Blueprints\Constants;

/**
 * EntityConstants - Sistemdeki entity (varlık) isimlerinin Türkçeleştirmelerini tutar.
 */
class EntityConstants
{
    /**
     * Entity klasör isimleri -> Türkçe karşılıkları
     */
    public const ENTITY_MAP = [
        'category' => 'Kategori',
        'menu' => 'Menü',
        'user' => 'Kullanıcı',
        'role' => 'Rol',
        'permission' => 'Yetki',
        'setting' => 'Ayar',
        'settings' => 'Ayarlar',
        'general' => 'Genel',
        'profile' => 'Profil',
        'auth' => 'Yetkilendirme',
        'page' => 'Sayfa',
        'block' => 'Blok',
        'contact' => 'İletişim',
        'notification' => 'Bildirim',
        'security' => 'Güvenlik',
        'developer' => 'Geliştirici',
        'backup' => 'Yedekleme',
        'log' => 'Günlük',
        'sidebar' => 'Sidebar',
        'navigation' => 'Sidebar'
    ];

    /**
     * Entity ismini Türkçeleştirir
     */
    public static function translate(string $entity): string
    {
        $entity = strtolower($entity);
        return self::ENTITY_MAP[$entity] ?? ucfirst($entity);
    }
}
