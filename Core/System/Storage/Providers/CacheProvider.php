<?php

namespace Rbn\Framework\Core\System\Storage\Providers;

use Rbn\Framework\Core\System\Storage\Base\BaseStorageProvider;
use Rbn\Framework\Core\System\Paths\Paths;
use Rbn\Framework\Core\Base\Services\BaseService;
use Rbn\Framework\Core\System\Storage\Constants\CacheConstants;
use Exception;

/**
 * CacheProvider - Framework Önbellek Birimi ⚡
 * 
 * Standart Cache operasyonlarını (TTL & Crypto) UniversalFileDriver ile yönetir.
 */
class CacheProvider extends BaseStorageProvider
{
    protected string $storageName = 'cache';
    protected bool $encrypted = true;
    protected string $format = 'serialized';
    protected string $extension = '.cache';

    protected function getStorageDir(): string
    {
        return Paths::project()->storage('cache');
    }

    /**
     * Cache Set (With TTL & Key Validation) 🧠
     */
    public function set(string $key, mixed $value, array $options = []): bool
    {
        $isAllowed = $this->isAllowedKey($key);
        $formatted = $this->formatKey($key);
        $ttl = $options['ttl'] ?? $this->getTtl('default');

        $cacheData = [
            'data' => $value,
            'expires_at' => time() + $ttl,
            'created_at' => time()
        ];

        if (!$isAllowed) {
            return false;
        }

        return parent::set($formatted, $cacheData);
    }

    /**
     * Cache Get (With TTL & Key Validation) 🧠
     */
    public function get(string $key, mixed $default = null): mixed
    {
        if (!$this->isAllowedKey($key))
            return $default;

        $cacheData = parent::get($this->formatKey($key));

        if (!$cacheData || !isset($cacheData['expires_at'])) {
            return $default;
        }

        if (time() > $cacheData['expires_at']) {
            $this->delete($key);
            return $default;
        }

        return $cacheData['data'] ?? $default;
    }

    /**
     * Cache Delete (With Key Validation & Format) 🧠
     */
    public function delete(string $key): bool
    {
        if (!$this->isAllowedKey($key)) {
            return false;
        }

        return parent::delete($this->formatKey($key));
    }

    /**
     * Cache Delete Alias (Laravel style compatibility) 🧠
     */
    public function forget(string $key): bool
    {
        return $this->delete($key);
    }

    /**
     * Remember (Fetch and Cache)
     */
    public function remember(string $key, callable $callback, ?int $ttl = null): mixed
    {
        if (!$this->isAllowedKey($key))
            return $callback();

        $cachedData = $this->get($key);
        if ($cachedData !== null)
            return $cachedData;

        try {
            $data = $callback();
            $this->set($key, $data, ['ttl' => $ttl ?? $this->getTtl('default')]);
            return $data;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Key Validation Engine
     */
    public function isAllowedKey(string $key): bool
    {
        $allPatterns = array_values(CacheConstants::SYSTEM_KEYS);

        foreach ($allPatterns as $pattern) {
            if (strpos($pattern, '%') !== false) {
                $regex = str_replace(['\%d', '\%s', '%d', '%s'], ['\d+', '[a-zA-Z0-9_.-]+', '\d+', '[a-zA-Z0-9_.-]+'], preg_quote($pattern, '/'));
                if (preg_match('/^' . $regex . '$/', $key))
                    return true;
            }
        }

        // 🎼 RBN 3.5: [DYNAMIC PREFIX DISCOVERY] 🛰️⚓
        foreach (CacheConstants::READABLE_PREFIXES as $prefix) {
            if (str_starts_with($key, $prefix)) {
                return true;
            }
        }


        return in_array($key, array_values(CacheConstants::SYSTEM_KEYS), true);
    }

    /**
     * Clear Related Cache (Grouped)
     */
    public function clearRelated(string $type): void
    {
        $type = strtolower($type);

        foreach (CacheConstants::SYSTEM_KEYS as $constKey => $cacheKey) {
            if (strtolower($constKey) === $type || strtolower($cacheKey) === $type)
                $this->delete($cacheKey);
        }

        // 🎼 RBN 3.5: [DYNAMIC GROUP PURGE] - Automatically clears configured prefixes
        foreach (CacheConstants::READABLE_PREFIXES as $prefix) {
            $category = rtrim($prefix, '_');
            if ($type === $category || str_contains($type, $category)) {
                $this->deleteByPrefix($prefix);
            }
        }

        // 🎯 RBN 3.5: Master Discovery Cache Flush
        if ($type === 'discovery' || $type === 'all') {
            BaseService::get()->discovery->clear();
        }
    }


    /**
     * Get TTL by Type Shorthand
     */
    public function getTtl(string $type = 'default'): int
    {
        return match ($type) {
            'short' => CacheConstants::TTL_SHORT,
            'medium' => CacheConstants::TTL_MEDIUM,
            'long' => CacheConstants::TTL_LONG,
            'day' => CacheConstants::TTL_DAY,
            'week' => 604800,
            'month' => 2592000,
            'year' => 31536000,
            default => CacheConstants::DEFAULT_TTL,
        };
    }

    /**
     * Cache key formatlayıcı (MD5 opsiyonel)
     */
    private function formatKey(string $key): string
    {
        $projectKey = $this->projectKey ?: project_key() ?: 'default';
        $isolatedKey = $projectKey . '_' . $key;

        // 🎼 RBN 3.5: [READABLE PERSISTENCE] - MD5 bypass for configured prefixes
        foreach (CacheConstants::READABLE_PREFIXES as $prefix) {
            if (str_starts_with($key, $prefix)) {
                return $isolatedKey . $this->extension;
            }
        }
        return md5($isolatedKey) . $this->extension;
    }

    /**
     * Prefix ile Silme (Örn: comm_ )
     */
    public function deleteByPrefix(string $prefix): bool
    {
        $projectKey = $this->projectKey ?: project_key() ?: 'default';
        $isolatedPrefix = $projectKey . '_' . $prefix;

        $dir = $this->getStorageDir();
        $files = glob($dir . '/' . $isolatedPrefix . '*' . $this->extension);
        if ($files === false)
            return true;

        foreach ($files as $file) {
            if (is_file($file))
                @unlink($file);
        }

        return true;
    }

    /**
     * Tümünü Temizle (Yalnızca bu projeye ait cache dosyalarını siler)
     */
    public function clearAll(?string $projectKey = null): bool
    {
        $projectKey = $projectKey ?: $this->projectKey ?: project_key() ?: 'default';
        $dir = $this->getStorageDir();
        $files = glob($dir . '/' . $projectKey . '_*' . $this->extension);
        if ($files !== false) {
            foreach ($files as $file) {
                if (is_file($file))
                    @unlink($file);
            }
        }
        return true;
    }
}
