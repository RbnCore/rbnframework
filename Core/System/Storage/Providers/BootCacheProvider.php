<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\System\Storage\Providers;

use Rbn\Framework\Core\System\Paths\Paths;

/**
 * BootCacheProvider - Erken Başlatma Önbellek Birimi ⚡🛡️
 * 
 * PreBoot aşamasındaki (Paths & Autoloader henüz tam kurulmadan önce) 
 * önbellek ihtiyaçlarını güvenli ve izole şekilde yönetir.
 */
class BootCacheProvider
{
    /**
     * Cache dizin yolunu döner (domains/.cache veya dinamik tespit edilen yol) 🪐
     */
    public static function getCacheDir(?string $publicPath = null): string
    {
        return Paths::workspace() . DIRECTORY_SEPARATOR . '.cache';
    }

    /**
     * Belirtilen anahtara ait önbellek dosya yolunu döner (Önek özelleştirilebilir)
     */
    public static function getFilePath(string $key, ?string $publicPath = null, string $prefix = 'discovery_'): string
    {
        return self::getCacheDir($publicPath) . DIRECTORY_SEPARATOR . $prefix . $key . '.json';
    }

    /**
     * Önbellekten veri okur 📥
     */
    public static function get(string $key, ?string $publicPath = null, string $prefix = 'discovery_'): ?array
    {
        $file = self::getFilePath($key, $publicPath, $prefix);
        if (file_exists($file)) {
            $content = @file_get_contents($file);
            if ($content) {
                $decoded = json_decode($content, true);
                if (is_array($decoded)) {
                    return $decoded;
                }
            }
        }
        return null;
    }

    /**
     * Önbelleğe veri yazar 💾
     */
    public static function set(string $key, array $data, ?string $publicPath = null, string $prefix = 'discovery_'): bool
    {
        $dir = self::getCacheDir($publicPath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        $file = self::getFilePath($key, $publicPath, $prefix);
        return @file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) !== false;
    }

    /**
     * Önbellekten veri siler 🧹
     */
    public static function delete(string $key, ?string $publicPath = null, string $prefix = 'discovery_'): bool
    {
        $file = self::getFilePath($key, $publicPath, $prefix);
        if (file_exists($file)) {
            return @unlink($file);
        }
        return false;
    }

    /**
     * Bir projeye ait hem proje hem de ilişkili domain önbelleğini siler 🧹
     */
    public static function clearProjectCache(string $projectKey, ?string $publicPath = null): void
    {
        $projectPrefix = \Rbn\Framework\Core\System\Storage\Constants\CacheConstants::DISCOVERY_PREFIX_PROJECT;
        $domainPrefix = \Rbn\Framework\Core\System\Storage\Constants\CacheConstants::DISCOVERY_PREFIX_DOMAIN;
        $groupPrefix = \Rbn\Framework\Core\System\Storage\Constants\CacheConstants::DISCOVERY_PREFIX_GROUP;

        $cached = self::get($projectKey, $publicPath, $projectPrefix);
        if ($cached) {
            if (!empty($cached['domain'])) {
                self::delete($cached['domain'], $publicPath, $domainPrefix);
            }
            if (!empty($cached['project_group'])) {
                self::delete($cached['project_group'], $publicPath, $groupPrefix);
            }
        }
        self::delete($projectKey, $publicPath, $projectPrefix);
    }
}
