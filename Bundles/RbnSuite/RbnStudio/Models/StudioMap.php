<?php

declare(strict_types=1);

namespace Rbn\Framework\Bundles\RbnSuite\RbnStudio\Models;

use Rbn\Framework\Core\Base\Data\BaseConfig;

/**
 * StudioMap - RbnStudio Structural Map 🗺️🎨🚀⚓
 * RBN 3.5 Masterpiece Standard.
 */
class StudioMap extends BaseConfig
{
    public const MAP = [
        'module_name' => 'studio',
        'title' => 'Studio',
        'icon' => 'ri-palette-line',
        'sub_modules' => [
            'drafts' => [
                'title' => 'Fikir & Taslak Yönetimi',
                'description' => 'Yapay zeka blog fikirleri ve taslak içerik yönetimi.',
                'icon' => 'ri-lightbulb-line'
            ],
            'categories' => [
                'title' => 'İçerik Kategorileri',
                'description' => 'Tüm içerik türleri için taksonomi ve kategori yönetimi.',
                'icon' => 'ri-folder-line'
            ],
            'posts' => [
                'title' => 'Yayınlanan Makaleler',
                'description' => 'Tüm yayınlanmış makale ve blog içeriklerinin yönetimi.',
                'icon' => 'ri-article-line'
            ],
            'news' => [
                'title' => 'Yayınlanan Haberler',
                'description' => 'Tüm yayınlanmış haber içeriklerinin ve bültenlerinin yönetimi.',
                'icon' => 'ri-newspaper-line'
            ]
        ]
    ];
}
