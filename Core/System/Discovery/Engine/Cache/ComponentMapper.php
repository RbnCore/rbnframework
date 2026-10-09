<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\System\Discovery\Engine\Cache;

use Rbn\Framework\Core\System\Paths\Paths;
use Rbn\Framework\Core\Base\Concerns\Data\ResolvesProjectConfigTrait;
use Rbn\Framework\Core\System\Storage\Drivers\UniversalFileDriver;

/**
 * ComponentMapper - Autonomous Component Cache & Persistence Manager 🧩⚡🛰️
 * 
 * RBN Framework: Centralized storage engine for discovered components map (JSON format).
 * Ensures ultra-fast disk persistence and in-memory caching for Attribute-discovered components.
 */
class ComponentMapper
{
    use ResolvesProjectConfigTrait;

    /** @var array Shared components map per folder (In-Memory) */
    private static array $componentsMap = [];

    /** @var bool Flag to check if map is loaded from disk */
    private static bool $mapLoaded = false;

    /**
     * Get cached component map for a specific folder 🔍
     */
    public function get(string $folder): ?array
    {
        $this->ensureMapLoaded();
        $key = strtolower($folder);
        return self::$componentsMap[$key] ?? null;
    }

    /**
     * Store components map for a specific folder 💾
     */
    public function set(string $folder, array $components): void
    {
        $this->ensureMapLoaded();
        $key = strtolower($folder);
        self::$componentsMap[$key] = $components;
        $this->saveMap();
    }

    /**
     * Clear physical component map cache 🧹
     */
    public function clear(): bool
    {
        $filePath = $this->getMapFilePath();
        if ($filePath && file_exists($filePath)) {
            $driver = new UniversalFileDriver();
            $driver->delete($filePath);
        }
        self::$componentsMap = [];
        self::$mapLoaded = false;
        return true;
    }

    /**
     * Reset in-memory cached map state for dynamic project context switches 🔄
     */
    public static function resetMemory(): void
    {
        self::$componentsMap = [];
        self::$mapLoaded = false;
    }

    /**
     * Ensures component map is loaded from disk ⚡
     */
    private function ensureMapLoaded(): void
    {
        if (self::$mapLoaded || !Paths::isInitialized()) {
            return;
        }

        $path = $this->getMapFilePath();
        if ($path && file_exists($path)) {
            try {
                $driver = new UniversalFileDriver();
                $data = $driver->read($path, 'json');
                if (is_array($data)) {
                    self::$componentsMap = $data;
                }
            } catch (\Throwable $e) {
                // Silently fallback to live scanning on read error
            }
        }

        self::$mapLoaded = true;
    }

    /**
     * Saves the current components map to disk 💾
     */
    public function saveMap(): void
    {
        if (!Paths::isInitialized()) {
            return;
        }

        $filePath = $this->getMapFilePath();
        if (!$filePath) {
            return;
        }

        try {
            $driver = new UniversalFileDriver();
            $driver->write($filePath, self::$componentsMap, 'json');
        } catch (\Throwable $e) {
            // Silently protect on write error
        }
    }

    /**
     * Get the absolute path to the components map JSON file 📂
     */
    private function getMapFilePath(): ?string
    {
        if (!Paths::isInitialized()) {
            return null;
        }

        $projectKey = function_exists('active_project_key') ? active_project_key() : null;
        if (empty($projectKey) || $projectKey === 'default') {
            $projectKey = function_exists('project_key') ? project_key() : null;
        }

        if (empty($projectKey) || $projectKey === 'default') {
            return null;
        }

        $projectPath = $this->resolveProjectPath($projectKey);
        if (!empty($projectPath) && is_dir($projectPath) && self::scansProjectRoot($projectPath)) {
            return $projectPath . '/Storage/framework/components_map_' . $projectKey . '.json';
        }

        return null;
    }

    /**
     * Tarama kökü (`Paths::project()`) bu projenin kökü mü?
     *
     * CLI ana kipinde (cron) proje anahtarı (`project_key()`) bir projeyi gösterirken
     * `Paths::project()` henüz `tmp/` ya da `projects/default` olabilir; o bağlamdaki
     * BOŞ tarama sonucu projenin harita dosyasına yazılırsa web isteği de boş haritayı
     * okur (`model('alias')` → null → 500). Kökler eşleşmiyorsa harita okunmaz/yazılmaz,
     * sonuç yalnız bellekte kalır.
     */
    public static function scansProjectRoot(string $projectPath): bool
    {
        $scanned = realpath(Paths::project()->root());
        $expected = realpath($projectPath);

        if ($scanned === false || $expected === false) {
            return false;
        }

        // Windows dosya sistemi büyük/küçük harf duyarsız; Linux/macOS'ta tam eşitlik.
        return DIRECTORY_SEPARATOR === '\\'
            ? strcasecmp($scanned, $expected) === 0
            : $scanned === $expected;
    }
}
