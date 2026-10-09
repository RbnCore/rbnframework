<?php
declare(strict_types=1);

namespace Rbn\Framework\Core\Support\Definitions\System;

use Rbn\Framework\Core\Base\Data\BaseConfig;

/**
 * FolderMatrix - The DNA of RBN Framework Directory Structure 🏺✨
 * 
 * RBN Framework: Structural metadata as a centralized system definition.
 * 
 * NOTE: Sadece ana iskelet ve spesifik (sabit) klasörler buraya eklenmelidir. 
 * Dinamik paketler ve her alt klasörün buraya eklenmesine gerek yoktur; 
 * hiyerarşi ana dallar üzerinden çözülür. ⚙️🏗️⚓
 */
class FolderMatrix extends BaseConfig
{
    /**
     * Explicit Definition Identity 🧬🏛️
     */
    public static function getDefinitionCategory(): string
    {
        return 'folder';
    }

    /**
     * Framework Engine Layout (Alphabetical Tree) 🏗️
     */
    public const FRAMEWORK = [
        'BUNDLES' => [
            'folder' => 'Bundles',
            'INTERNAL' => 'Internal',
            'SUITE' => [
                'folder' => 'RbnSuite',
                'ADMIN' => 'RbnAdmin',
                'AUTH' => 'RbnAuth',
                'STUDIO' => 'RbnStudio'
            ],
        ],
        'CORE' => [
            'folder' => 'Core',
            'BASE' => 'Base',
            'DATABASE' => 'Database',
            'HTTP' => 'Http',
            'RENDER' => 'Render',
            'ROUTES' => 'Routes',
            'SERVICES' => 'Services',
            'SUPPORT' => [
                'folder' => 'Support',
                'BRIDGES' => [
                    'folder' => 'Bridges',
                    'HELPERS' => 'Helpers',
                ],
            ],
            'SYSTEM' => 'System',
        ],
        'PACKAGES' => [
            'folder' => 'Packages',
        ],
        'RESOURCES' => [
            'folder' => 'Resources',
            'ASSETS' => 'Assets',
            'VIEWS' => 'Views',
        ],
        'VENDOR' => 'vendor',
    ];

    /**
     * Project Application Layout (Standard Tree) 🏠
     */
    public const PROJECT = [
        'APP' => [
            'folder' => 'App',
            'MODELS' => 'Models',
            'PROVIDERS' => 'Providers',
            'SERVICES' => 'Services'
        ],
        'CORE' => [
            'folder' => 'Core',
            'CONFIG' => 'Config',
            'TASKS' => 'Tasks',
        ],
        'MODULES' => [
            'folder' => 'Modules',
            'BACKEND' => 'Backend',
            'FRONTEND' => [
                'folder' => 'Frontend',
                'CONTROLLERS' => 'Controllers',
            ],
        ],
        'RESOURCES' => [
            'folder' => 'Resources',
            'COMPONENTS' => 'Components',
            'DASHBOARDS' => 'Dashboards',
            'DATA' => 'Data',
            'LAYOUTS' => 'Layouts',
        ],
        'STORAGE' => [
            'folder' => 'Storage',
            'CACHE' => 'cache',
            'FRAMEWORK' => [
                'folder' => 'framework',
                'VIEWS' => 'views',
            ],
            'LOGS' => [
                'folder' => 'logs',
                'TRAFFIC' => 'traffic',
            ],
            'SESSIONS' => 'sessions',
        ],
        'PUBLIC' => [
            'folder' => 'public',
            'CSS' => 'css',
            'JS' => 'js',
            'IMAGES' => 'images',
        ],
    ];

    /**
     * ÇALIŞMA ZAMANI KLASÖRLERİ — ASLA atlanmaz. ⏱️🔒
     *
     * Bu klasörler çalışma zamanında YAZILIR; proje onları kullanmasa bile
     * sistem (oturum, cache, log, yükleme) bunlara yazmaya çalışır.
     * `PermissionDoctor` bu listeye giren bir klasörü bulamazsa AÇAR ve
     * güvenlik dosyalarını yazar.
     *
     * Biçim: proje köküne göre göreli yol, `/` ayıracı, FolderMatrix::PROJECT ile aynı.
     */
    public const RUNTIME_LAYERS = [
        'Storage',
        'Storage/cache',
        'Storage/framework',
        'Storage/framework/views',
        'Storage/logs',
        'Storage/logs/traffic',
        'Storage/sessions',
        'public',
        'public/css',
        'public/js',
        'public/images',
    ];

    /**
     * KOD KATMANI KLASÖRLERİ — yalnız projede GERÇEKTEN varsa açılır. 📦
     *
     * Bu klasörler koda aittir; içine yazacak sınıf/dosya yoksa boş bir
     * iskelet (`index.html` dolu) üretmek projeyi kirletir, o yüzden
     * `PermissionDoctor` bunları katman kullanılmıyorsa ATLAR.
     *
     * Biçim: proje köküne göre göreli yol, `/` ayıracı, FolderMatrix::PROJECT ile aynı.
     */
    public const CODE_LAYERS = [
        'App',
        'App/Models',
        'App/Providers',
        'App/Services',
        'Core',
        'Core/Config',
        'Core/Tasks',
        'Modules',
        'Modules/Backend',
        'Modules/Frontend',
        'Modules/Frontend/Controllers',
        'Resources',
        'Resources/Components',
        'Resources/Dashboards',
        'Resources/Data',
        'Resources/Layouts',
    ];

    /**
     * Bir göreli yol çalışma zamanı katmanı mı? (Alt klasörler de dahil.)
     */
    public static function isRuntimeLayer(string $relativePath): bool
    {
        return self::layerMatches(self::RUNTIME_LAYERS, $relativePath);
    }

    /**
     * Bir göreli yol kod katmanı mı? (Alt klasörler de dahil.)
     */
    public static function isCodeLayer(string $relativePath): bool
    {
        return self::layerMatches(self::CODE_LAYERS, $relativePath);
    }

    /**
     * Alt yollar da eşleşsin diye: `Storage` listelenmişse `Storage/logs` de
     * çalışma zamanı katmanı sayılır.
     */
    private static function layerMatches(array $layers, string $relativePath): bool
    {
        $needle = self::normalizeLayerPath($relativePath);

        foreach ($layers as $layer) {
            $layer = self::normalizeLayerPath((string) $layer);
            if ($needle === $layer || str_starts_with($needle . '/', $layer . '/')) {
                return true;
            }
        }

        return false;
    }

    private static function normalizeLayerPath(string $path): string
    {
        $path = str_replace('\\', '/', trim($path));
        return trim($path, '/');
    }
}
