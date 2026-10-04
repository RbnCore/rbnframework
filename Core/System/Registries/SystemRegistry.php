<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\System\Registries;

use Rbn\Framework\Core\Base\Data\BaseConfig;
use Rbn\Framework\Core\System\Kernel\Bootstrap;
use Rbn\Framework\Core\System\Registries\RegistryMap\SystemAccessMapTrait;
use Rbn\Framework\Core\System\Registries\RegistryMap\SystemLogicMapTrait;
use Rbn\Framework\Core\System\Registries\RegistryMap\SystemPhysicalMapTrait;
use Rbn\Framework\Core\System\Registries\RegistryMap\SystemResourceMapTrait;
use Rbn\Framework\Core\System\Registries\RegistryMap\SystemRenderMapTrait;
use Rbn\Framework\Core\Base\Data\Traits\Config\Engine\DiscoveryConfigTrait;

/**
 * SystemRegistry - The Framework Core Population Office 🏛️⚓🛰️
 * 
 * RBN 3.5 "Masterpiece": [PENTARIC SATELLITE ARCHITECTURE] 🌌
 * Now refactored into categoric satellite maps for scalability and core purity.
 * Orchestrates Access, Logic, Physical, Resource and Ecosystem layers.
 */
class SystemRegistry extends BaseConfig
{
    /** @var array<string, array> Static merged map cache 🧠⚡ */
    private static array $mergedMapCache = [];

    /** --- Pentaric Satellite Maps 🧬🎡 --- */
    use SystemAccessMapTrait,
        SystemLogicMapTrait,
        SystemPhysicalMapTrait,
        SystemResourceMapTrait,
        SystemRenderMapTrait,
        DiscoveryConfigTrait;

    /**
     * SOVEREIGN BUNDLE LIST 🚀🎯
     * RBN 3.5: Centralized list of core modules for global discovery.
     */
    public static function sovereignBundles(): array
    {
        return [
            \Rbn\Framework\Packages\PackageData::class,
            \Rbn\Framework\Bundles\RbnSuite\RbnAdmin\Models\ModuleData::class,
            \Rbn\Framework\Bundles\RbnSuite\RbnAuth\Models\ModuleData::class,
            \Rbn\Framework\Bundles\RbnSuite\RbnStudio\Models\ModuleData::class,
            \Rbn\Framework\Bundles\Internal\Backstage\Models\ModuleData::class,
            \Rbn\Framework\Bundles\Internal\Syshub\Models\ModuleData::class,
            \Rbn\Framework\Bundles\Internal\Webhub\Models\ModuleData::class,
        ];
    }

    /**
     * UNIFIED REGISTRATION ORCHESTRATOR 🏛️⚓🛰️
     * RBN 3.5 Masterpiece: Merges specialized satellite maps into one global truth.
     * Memoized per request execution context for 0ms repeated speed.
     */
    public function registerMap(): array
    {
        $projectKey = Bootstrap::getAppContext('project_key') ?: (function_exists('active_project_key') ? active_project_key() : 'default');
        if (isset(self::$mergedMapCache[$projectKey])) {
            return self::$mergedMapCache[$projectKey];
        }

        $registry = new \Rbn\Framework\Core\System\Registries\AppProjectRegistry();

        $map = array_merge_recursive(
            $this->accessMap(),    // 🏹 Aliases & Commands
            $this->logicMap(),     // 🧠 Services & Handlers
            $this->physicalMap(),  // 🏺 Models & Providers
            $registry->registerMap(), // 🌉 Project Component Map (Autonomous Discovery)
            $this->resourceMap(),  // 🛠️ Helpers & Metadata
            $this->renderMap()     // 🛰️🎨 Render Hub
        );

        // 🎼 RBN 3.5: [SOVEREIGN HIERARCHICAL AUTO-MERGE] 🏙️🛰️⚓
        // Önce Framework (Sovereign), sonra Proje paketlerini BaseService üzerinden otonom olarak birleştir.
        $moduleService = \Rbn\Framework\Core\Base\Services\BaseService::get()->service('module');
        if ($moduleService) {
            // 1. Framework Core Bundles
            $map = array_merge_recursive($map, $moduleService->registerBundles('map', static::sovereignBundles()));

            // 2. Project Modules
            $map = array_merge_recursive($map, $moduleService->registerBundles('map'));
        }

        // 🎼 RBN 3.5: [SOVEREIGN VIRTUAL REGISTRATION] 🏙️🛰️⚓
        // Aktif Controller'ın modül verilerini de ekle (Geriye dönük uyumluluk için)
        $controller = \Rbn\Framework\Core\Base\Services\BaseService::get()?->activeController();
        $moduleData = $controller->moduleData ?? null;

        if (is_string($moduleData) && class_exists($moduleData)) {
            $moduleData = new $moduleData();
        }

        if (is_object($moduleData) && method_exists($moduleData, 'registerMap')) {
            $map = array_merge_recursive($map, $moduleData->registerMap());
        }

        return self::$mergedMapCache[$projectKey] = $map;
    }

    /**
     * Clear cached registry maps across project contexts 🧹
     */
    public static function clearCache(): void
    {
        self::$mergedMapCache = [];
    }
}
