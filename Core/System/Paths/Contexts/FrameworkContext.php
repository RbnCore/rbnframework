<?php

namespace Rbn\Framework\Core\System\Paths\Contexts;

use Rbn\Framework\Core\System\Paths\Paths;
use Rbn\Framework\Core\System\Discovery\Clusters\Logic\Definition\Definition;
use Rbn\Framework\Core\System\Discovery\Clusters\Structure\Folder\FolderContext;
use Rbn\Framework\Core\System\Discovery\Clusters\Structure\Namespace\NamespaceContext;

/**
 * Framework Context
 * Provides paths related to the RBN Framework engine.
 */
class FrameworkContext extends FolderContext
{
    /*
     | --------------------------------------------------------------------------
     | Phase 5: NAMESPACE RESOLUTION 🛰️
     | --------------------------------------------------------------------------
     */

    /**
     * Get the logical Namespace Context for the Framework Core.
     * This brings "Core" in line with the modular architecture's resolution rules.
     */
    public function namespaces(): NamespaceContext
    {
        // Rbn\Framework\ + Core = Rbn\Framework\Core
        $prefix = Definition::get('namespace', 'FRAMEWORK_PREFIX');
        return new NamespaceContext($prefix . 'Core', 'Core');
    }

    /*
     | --------------------------------------------------------------------------
     | Phase 1: ROOT RESOLVERS (Absolute Framework Anchors) ⚓
     | --------------------------------------------------------------------------
     */

    /**
     * Get the absolute root directory of the RBN Framework.
     */
    public function root(?string $path = null): string
    {
        $root = Paths::frameworkRoot();
        return $root . ($path ? DIRECTORY_SEPARATOR . ltrim($path, '/\\') : '');
    }

    /**
     * Get the standard Composer vendor directory.
     */
    public function vendor(?string $path = null): string
    {
        $vendor = defined('DIR_VENDOR') ? DIR_VENDOR : $this->root(self::path('FRAMEWORK.VENDOR'));
        return $vendor . ($path ? DIRECTORY_SEPARATOR . ltrim($path, '/\\') : '');
    }

    /*
     | --------------------------------------------------------------------------
     | Phase 1.5: FRAMEWORK TOP-LEVEL DIRECTORIES 📁
     | --------------------------------------------------------------------------
     */

    /**
     * Get the Core engine directory (The Framework Heart 🧠)
     */
    public function core(?string $path = null): string
    {
        return $this->root(self::path('FRAMEWORK.CORE', $path));
    }

    /**
     * Get the centralized Bundles directory (Modular Root 📦)
     */
    public function bundles(?string $path = null): string
    {
        return $this->root(self::path('FRAMEWORK.BUNDLES', $path));
    }

    /**
     * Get the centralized Packages directory (RBN Framework Service Hub 📦🛰️)
     */
    public function packages(?string $path = null): string
    {
        return $this->root(self::path('FRAMEWORK.PACKAGES', $path));
    }

    /**
     * Get the absolute Resources directory of the RBN Framework.
     */
    public function resources(?string $path = null): string
    {
        return $this->root(self::path('FRAMEWORK.RESOURCES', $path));
    }


    /*
     | --------------------------------------------------------------------------
     | Phase 1.6: RESOURCES & ASSETS 🎨
     | --------------------------------------------------------------------------
     */


    /**
     * Get the absolute Assets directory of the RBN Framework (inside Resources).
     */
    public function assets(?string $path = null): string
    {
        return $this->root(self::path('FRAMEWORK.RESOURCES.ASSETS', $path));
    }

    /**
     * Get the absolute Views directory of the RBN Framework (inside Resources).
     */
    public function views(?string $path = null): string
    {
        return $this->root(self::path('FRAMEWORK.RESOURCES.VIEWS', $path));
    }

    /**
     * Get the absolute Layouts directory of the RBN Framework (inside Resources/Views).
     */
    public function layouts(?string $path = null): string
    {
        return $this->root(self::path('FRAMEWORK.RESOURCES.VIEWS.Layouts', $path));
    }

    /*
     | --------------------------------------------------------------------------
     | Phase 1.7: CORE SPECIALIZED DIRECTORIES ⚙️
     | --------------------------------------------------------------------------
     */

    /**
     * Get the System configuration and kernel directory (Core/System)
     */
    public function system(?string $path = null): string
    {
        return $this->root(self::path('FRAMEWORK.CORE.SYSTEM', $path));
    }

    /*
     | --------------------------------------------------------------------------
     | Phase 2: BUNDLES & HUBS RESOLVERS 🛰️
     | --------------------------------------------------------------------------
     */

    /**
     * Resolve paths within the RbnSuite ecosystem (Bundles/RbnSuite)
     */
    public function suite(string $moduleName, ?string $path = null): string
    {
        // Flattened: Direct resolution via root to avoid Bundles/Bundles duplication
        return $this->root(self::path('FRAMEWORK.BUNDLES.SUITE.' . strtoupper($moduleName), $path));
    }

    /**
     * Resolve paths within Internal framework modules (Bundles/Internal)
     */
    public function internal(?string $path = null): string
    {
        return $this->root(self::path('FRAMEWORK.BUNDLES.INTERNAL', $path));
    }

    /*
     | --------------------------------------------------------------------------
     | Phase 4: RBNSUITE QUICK SHORTCUTS ⚡
     | --------------------------------------------------------------------------
     */

    /**
     * High-speed shortcut for the RbnShield security suite.
     */
    public function shield(?string $path = null): string
    {
        return $this->suite('RbnShield', $path);
    }
}
