<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Services\Console\Managers;

use Rbn\Framework\Core\Base\Services\BaseManager;
use Rbn\Framework\Core\Base\Concerns\Data\ResolvesProjectConfigTrait;
use Rbn\Framework\Core\System\Kernel\Bootstrap;
use Rbn\Framework\Core\System\Config\Config;
use Rbn\Framework\Core\System\Config\Engine\Config\ConfigResolver;
use Rbn\Framework\Core\Database\Database;
use Rbn\Framework\Core\System\Discovery\Engine\Cache\ComponentMapper;
use Rbn\Framework\Core\System\Discovery\Engine\Cache\DiscoveryMapper;
use Rbn\Framework\Core\System\Discovery\Engine\Drivers\ModuleDiscoveryDriver;
use Rbn\Framework\Core\System\Registries\SystemRegistry;
use Rbn\Framework\Core\System\Kernel\Stages\ProjectDiscovery;
use Rbn\Framework\Core\System\Kernel\Stages\Autoload;
use Rbn\Framework\Core\System\Paths\Paths;

/**
 * CliManager - Sovereign CLI Lifecycle, Project Context & Memory Manager 🛰️🔄⚓
 * 
 * RBN 3.5 Masterpiece Standard.
 * Orchestrates complete project switching, memory reset, discovery caches,
 * configuration reloading and database connection binding for CLI & Cron tasks.
 */
class CliManager extends BaseManager
{
    /** @var array<string> Stores project context stack (LIFO) for nested restoration 📚 */
    private static array $projectContextStack = [];

    /** @var string|null Stores currently active project key */
    private static ?string $currentProjectKey = null;

    /**
     * Boots or switches the active CLI/Console context to the given project 🚀
     */
    public static function switchProject(string $projectKey): bool
    {
        $projectKey = trim($projectKey);
        if (empty($projectKey) || $projectKey === 'master') {
            return false;
        }

        // 1. O anki aktif projeyi yığına (stack) kaydet
        $current = self::$currentProjectKey ?? (function_exists('active_project_key') ? active_project_key() : null);
        if ($current !== null && $current !== $projectKey) {
            self::$projectContextStack[] = $current;
        }

        // 2. Kernel AppContext Sealing (Evrensel proje bağlamı) 🔒
        Bootstrap::setAppContext('project_key', $projectKey);
        self::$currentProjectKey = $projectKey;

        // 3. Proje Verisini (project_data) Önbellekten Mühürle 🧬
        $pData = ProjectDiscovery::getProjectData(null, $projectKey);
        if (!empty($pData)) {
            Bootstrap::setAppContext('project_data', $pData);
        }

        // 4. Projenin kesin fiziki yolunu çöz ve Paths Hub'a mühürle 🏛️🛰️⚓
        $customPath = $pData['custom_path'] ?? $projectKey;
        $projectPath = Paths::workspace() . "/projects/{$customPath}";
        Bootstrap::setAppContext('project_path', $projectPath);
        Paths::init($projectPath);

        // 4.1. SSoT: Projenin PSR-4 Namespace Yollarını Composer ClassLoader'a Tanıt 🚀
        Autoload::boot();

        // 5. Config ve Yol Temizliği 🧠
        Config::clear();
        self::resetRouteMapCache();

        // 6. Discovery Engine & Harita Önbelleklerini Tamamen Sıfırla 🧩⚡
        ComponentMapper::resetMemory();
        DiscoveryMapper::resetMemory();
        ModuleDiscoveryDriver::resetMemory();
        SystemRegistry::clearCache();

        return true;
    }

    /**
     * Executes a callback within an isolated project context and safely restores the original context ⏳
     */
    public static function runInProject(string $projectKey, callable $callback): mixed
    {
        $previous = self::$currentProjectKey ?? (function_exists('active_project_key') ? active_project_key() : null);

        try {
            self::switchProject($projectKey);
            return $callback($projectKey);
        } finally {
            if ($previous !== null && $previous !== $projectKey) {
                self::switchProject($previous);
            }
        }
    }

    /**
     * Restores context back to the previous project on the stack 🔙
     */
    public static function restore(): bool
    {
        if (!empty(self::$projectContextStack)) {
            $prev = array_pop(self::$projectContextStack);
            if (!empty($prev) && $prev !== '__none__') {
                return self::switchProject($prev);
            }
        }

        return false;
    }

    /**
     * Returns the currently active project key 🔑
     */
    public static function getActiveProjectKey(): ?string
    {
        return self::$currentProjectKey ?? (function_exists('active_project_key') ? active_project_key() : null);
    }
}
