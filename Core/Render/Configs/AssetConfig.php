<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Render\Configs;

use Rbn\Framework\Core\Base\Data\BaseConfig;

/**
 * AssetConfig - Core Asset Engine Logic ⚙️🛰️⚓
 * 
 * This file handles SYSTEM LOGIC (Paths, Extensions, Proxies).
 * For user-facing contents (Bundles, UI Maps), refer to AssetDefinition.
 */
class AssetConfig extends BaseConfig
{
    public const NAME = 'RbnKit';
    public const VERSION = '1.0';

    /**
     * PROXY_SETUP - The RBN Framework Source Registry 🛡️🏅⚓
     * RBN Framework: Grouped by source (Project/Framework) for maximum atoms.
     */
    public const PROXY_SETUP = [
        'version' => self::VERSION,
        'project' => [
            'path' => 'project-assets',
            'token' => '@project/'
        ],
        'framework' => [
            'path' => 'framework-assets',
            'token' => '@fw/'
        ]
    ];

    /**
     * CLUSTER_REGISTRY - The Unified Hub for System Sources 🗺️🛰️⚓
     * Combines Labels and Folders into a single atomic matrix.
     */
    public const CLUSTER_REGISTRY = [
        'core' => [
            'label' => 'Core',
            'folder' => ''
        ],
        'project' => [
            'label' => 'Project',
            'folder' => null // Public Root Reference
        ],
        'kit' => [
            'label' => 'RbnKit',
            'folder' => 'RbnKit'
        ]
    ];

    /**
     * PROXY_ALLOWED_HOSTS - Dış varlık vekili (proxy) için açık host beyaz listesi 🛡️
     *
     * [GÜVENLİK · A0-5 / R-01] `AssetController::serve()` `media/<base64>` ve
     * `fonts/<slug>` yollarındaki hedefleri `Location:` başlığına yazar. Eskiden
     * HEDEF DOĞRULANMAZDTI → **açık yönlendirme**. Artık:
     *   1) aktif proje alan adı + proje grubu alan adları (`RedirectTrait::guvenliHedef()`)
     *   2) BU LİSTE
     * kabul edilir; geri kalan her şey fail-closed reddedilir.
     *
     * Kullanım: dış CDN/medya sağlayıcısı kullanıyorsan host'u buraya EKLE.
     * Eşleşme: TAM alan adı VEYA alt alan adı (`cdn.example.com` -> `*.example.com`).
     * Girdi `javascript:` / `data:` şemaları burada da geçmez (şema beyaz listesi
     * ayrıdır ve yalnız `http`/`https` kabul eder).
     */
    public const PROXY_ALLOWED_HOSTS = [
        // 'cdn.example.com',
        // 'fonts.googleapis.com',
        // 'fonts.gstatic.com',
    ];

    /**
     * EXTENSION_MAP - Standard Categorization Hub 🗺️⚓
     * Maps file extensions to their internal render groups.
     */
    public const EXTENSION_MAP = [
        'css' => 'css',
        'js' => 'js',
        'mjs' => 'js',
        'less' => 'css',
        'scss' => 'css',
        'png' => 'image',
        'jpg' => 'image',
        'svg' => 'image',
        'json' => 'data'
    ];
}
