<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Services\System;

use Rbn\Framework\Core\Base\Services\BaseService;
use Rbn\Framework\Core\System\Discovery\Engine\Drivers\ModuleDiscoveryDriver;
use Rbn\Framework\Core\System\Discovery\Engine\Drivers\ModuleDataDriver;

/**
 * ModuleService - The Central Framework Orchestrator 🏛️🚀⚓
 * 
 * RBN 3.5 Masterpiece: Core service for discovering and registering 
 * framework-wide modules and their route hierarchies.
 */
class ModuleService extends BaseService
{
    /** @var array Track loaded module routes to prevent duplication 🛡️⚓ */
    private static array $loadedRoutes = [];

    /** @var ModuleDiscoveryDriver|null Core discovery driver instance */
    private ?ModuleDiscoveryDriver $discoveryDriver = null;

    /** @var ModuleDataDriver|null Core module data driver instance */
    private ?ModuleDataDriver $dataDriver = null;

    private function getDiscovery(): ModuleDiscoveryDriver
    {
        return $this->discoveryDriver ??= new ModuleDiscoveryDriver();
    }

    private function getDataDriver(): ModuleDataDriver
    {
        return $this->dataDriver ??= new ModuleDataDriver($this->rbn);
    }

    /**
     * Registers and maps all active bundles (Delegated to Discovery Engine) 🏛️🛰️⚓
     */
    public function registerBundles(string $type = 'map', ?array $bundles = null): mixed
    {
        return $this->getDiscovery()->registerBundles($type, $bundles);
    }

    /**
     * Fully resolve a module's metadata and configuration 🏺✨
     */
    public function resolve(string $module, string $moduleSource = 'auto'): array
    {
        return $this->getDataDriver()->getModuleInfo($module, $moduleSource);
    }

    /**
     * Deep search and resolve a sub-module configuration within a module metadata tree 🔍
     */
    public function findSubModule(array $moduleMetadata, string $targetSub): array
    {
        $tree = $moduleMetadata['sub_modules'] ?? [];
        return $this->getDataDriver()->deepSearch($tree, $targetSub) ?? [];
    }
}
