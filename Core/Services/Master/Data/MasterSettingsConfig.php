<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Services\Master\Data;

/**
 * MasterSettingsConfig - Master Ayar Sabitleri 🏛️⚙️
 * RBN Framework Standard.
 */
class MasterSettingsConfig
{
    /**
     * İzin verilen Ayar Türleri (Type) 🛠️
     */
    public const TYPES = [
        'global'  => 'Global',
        'system'  => 'Sistem',
        'project' => 'Proje',
        'api'     => 'API',
        'other'   => 'Diğer',
    ];

    /**
     * İzin verilen Kategoriler (Category) 🧬
     */
    public const CATEGORIES = [
        'general' => 'Genel',
        'api'     => 'API Anahtarları',
        'mail'    => 'E-posta Ayarları',
        'seo'     => 'SEO Ayarları',
        'system'  => 'Sistem Ayarları',
        'other'   => 'Diğer',
    ];
}
