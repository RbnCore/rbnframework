<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Services\System;

use Rbn\Framework\Core\Base\Services\BaseService;
use Rbn\Framework\Core\Support\Definitions\System\FrameworkIdentity;
use Rbn\Framework\Core\System\Config\Config;

/**
 * CdnService - Framework Sovereign CDN Gateway 🛰️🏛️⚓
 * 
 * RBN 3.5 Core: Tüm projenin CDN ekosistemini yönetir.
 */
class CdnService extends BaseService
{
    /**
     * Dinamik CDN kök dizinini döndürür. 🏛️
     */
    public function getBaseUrl(string $context = 'projects'): string
    {
        // 1. Ana Domain (SSoT)
        $baseUrl = FrameworkIdentity::CDN_BASE_URL . $context . '/';

        // 2. Eğer bağlam 'projects' ise klasörü tespit et
        if ($context === 'projects') {
            // 🛡️ RBN 3.5: [DIRECT CONFIG ACCESS] 🏹⚓
            // Discovery Engine hatasından kaçınmak için doğrudan Config::get kullanıyoruz.
            $cdnFolder = Config::get('project-settings.cdn_folder') ?? $this->projectKey ?? 'default';
            $baseUrl .= $cdnFolder . '/';
        }

        return $baseUrl;
    }

    /**
     * Bir yolu CDN adresine dönüştürür. 🏹
     */
    public function resolve(string $path, string $context = 'projects'): string
    {
        if (empty($path)) return '';
        if (str_starts_with($path, 'http')) return $path;

        return $this->getBaseUrl($context) . ltrim($path, '/');
    }

    /**
     * Model veya dizi alanlarını Handler üzerinden otonom çözer. 🎭
     */
    public function resolveFields(object|array &$data, array $fields, string $context = 'projects'): void
    {
        $this->handler('cdn')->processFields($data, $fields, $this->getBaseUrl($context));
    }

    /**
     * Koleksiyonları Handler üzerinden toplu işler. 🛰️⚓
     */
    public function resolveCollection(iterable $collection, array $fields, string $context = 'projects'): iterable
    {
        return $this->handler('cdn')->processCollection($collection, $fields, $this->getBaseUrl($context));
    }
}
