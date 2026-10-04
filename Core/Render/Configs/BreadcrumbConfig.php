<?php

namespace Rbn\Framework\Core\Render\Configs;

use Rbn\Framework\Core\Base\Data\BaseConfig;

/**
 * BreadcrumbConfig - Breadcrumb Engine Configuration Registry 🍞
 * 
 * Centralizes blacklist names and path behaviors.
 */
class BreadcrumbConfig extends BaseConfig
{
    /**
     * Field names that should never be used as a display name for a record.
     */
    public const NAME_BLACKLIST = [
        'id',
        'parent_id',
        'user_id',
        'created_at',
        'updated_at',
        'deleted_at',
        'slug',
        'is_active',
        'status',
        'type',
        'order',
        'sort',
        'hit_count',
        'password',
        'token',
        'csrf_token',
        'remember_token',
        'view_source',
    ];

    /**
     * [SOVEREIGN ACTION MAP] 🛡️🛰️⚓
     * Centralized dictionary for common URL segments to provide autonomous icons and labels.
     */
    public const ACTION_ICON_MAP = [
        'index'     => ['icon' => 'bi bi-list-ul', 'label' => 'Liste'],
        'logs'      => ['icon' => 'bi bi-journal-text', 'label' => 'İşlem Kayıtları'],
        'log'       => ['icon' => 'bi bi-journal-text', 'label' => 'Detay'],
        'edit'      => ['icon' => 'bi bi-pencil-square', 'label' => 'Düzenle'],
        'update'    => ['icon' => 'bi bi-save', 'label' => 'Güncelle'],
        'delete'    => ['icon' => 'bi bi-trash', 'label' => 'Sil'],
        'remove'    => ['icon' => 'bi bi-trash', 'label' => 'Kaldır'],
        'create'    => ['icon' => 'bi bi-plus-circle', 'label' => 'Yeni Ekle'],
        'add'       => ['icon' => 'bi bi-plus-circle', 'label' => 'Yeni Ekle'],
        'view'      => ['icon' => 'bi bi-eye', 'label' => 'Görüntüle'],
        'show'      => ['icon' => 'bi bi-eye', 'label' => 'Görüntüle'],
        'settings'  => ['icon' => 'bi bi-gear', 'label' => 'Ayar Mimarisi'],
        'config'    => ['icon' => 'bi bi-sliders', 'label' => 'Yapılandırma'],
        'list'      => ['icon' => 'bi bi-list-ul', 'label' => 'Liste'],
        'search'    => ['icon' => 'bi bi-search', 'label' => 'Arama'],
        'history'   => ['icon' => 'bi bi-clock-history', 'label' => 'Geçmiş'],
        'stats'     => ['icon' => 'bi bi-bar-chart-fill', 'label' => 'İstatistik'],
        'analytics' => ['icon' => 'bi bi-graph-up-arrow', 'label' => 'Analiz'],
        'report'    => ['icon' => 'bi bi-file-earmark-bar-graph', 'label' => 'Rapor'],
        'users'     => ['icon' => 'bi bi-people', 'label' => 'Kullanıcılar'],
        'profile'   => ['icon' => 'bi bi-person-badge', 'label' => 'Profil'],
        'developer' => ['icon' => 'bi bi-code-slash', 'label' => 'Geliştirici Hub'],
        'unread'    => ['icon' => 'bi bi-patch-exclamation', 'label' => 'Okunmamış'],
        'read'      => ['icon' => 'bi bi-envelope-open', 'label' => 'Okunmuş'],
        'trash'     => ['icon' => 'bi bi-trash', 'label' => 'Çöp Kutusu'],
        'group'     => ['icon' => 'bi bi-folder2-open', 'label' => 'Grup Yönetimi'],
        'manage'    => ['icon' => 'bi bi-grid-3x3-gap', 'label' => 'Yönetim'],
        'wizard'    => ['icon' => 'bi bi-magic', 'label' => 'Sihirbaz'],
    ];

    /**
     * Actions that should terminate the breadcrumb trail.
     * Navigation will not proceed beyond these points.
     */
    public const TERMINAL_ACTIONS = [
        'view',
        'show',
        'preview',
        'download'
    ];
}
