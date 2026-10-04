<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\System\Registries;

use Rbn\Framework\Core\Base\Data\BaseConfig;
use Rbn\Framework\Core\System\Discovery\Engine\Drivers\ModuleDiscoveryDriver;

/**
 * AppProjectRegistry - Unified Project-Level Discovery Hub 🏛️🛰️⚓
 * 
 * RBN 3.5 "Masterpiece" Standard.
 * This is the SINGLE gate for all non-module project registrations.
 */
class AppProjectRegistry extends BaseConfig
{
    public function bundles(): array
    {
        // 🎼 RBN 3.5: [SOVEREIGN AUTONOMOUS DISCOVERY] 🚀🛰️⚓
        $discovery = new ModuleDiscoveryDriver();
        $discovered = $discovery->discoverBundles();
        $projectKey = (string) ($this->resolveProjectData('project_key') ?: '');

        if (!empty($projectKey)) {
            $allowedBundles = (array) $this->getRouteConfig($projectKey, 'bundles');
            if (!empty($allowedBundles)) {
                return array_filter($discovered, function ($class, $name) use ($allowedBundles) {
                    return in_array(strtolower($name), array_map('strtolower', $allowedBundles), true);
                }, ARRAY_FILTER_USE_BOTH);
            }
        }

        return $discovered;
    }

    /**
     * Autonomous Component Mapping 🗺️🛰️⚓
     */
    public function registerMap(): array
    {
        // 🎼 RBN 3.5: [SOVEREIGN AUTONOMOUS DISCOVERY] 🚀🛰️⚓
        $discovery = new ModuleDiscoveryDriver();

        // 1. Projeye ait modül haritalarını otonom olarak topla 🏛️
        $map = $discovery->registerBundles('map', $this->bundles()) ?? [];

        // 2. ComponentTypes içerisindeki tüm bileşen türlerini otonom olarak tara! 🧬
        foreach (\Rbn\Framework\Core\Support\Definitions\System\ComponentTypes::PLURAL_MAP as $singular => $plural) {
            $folderName = ucfirst($plural); // Örn: Services, Models, Handlers, Queues...
            $discovered = $discovery->discoverComponents($folderName);
            if (!empty($discovered)) {
                $map[$plural] = array_merge($map[$plural] ?? [], $discovered);
            }
        }

        return $map;
    }
}
