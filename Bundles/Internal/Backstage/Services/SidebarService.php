<?php

namespace Rbn\Framework\Bundles\Internal\Backstage\Services;

use Rbn\Framework\Core\Base\Services\BaseService;

/**
 * SidebarService - Admin Sidebar Orchestrator 🗺️🏛️⚓
 * RBN Framework Standard.
 * 
 * @property \Rbn\Framework\Bundles\Internal\Backstage\Providers\SidebarProvider $SidebarProvider
 */
class SidebarService extends BaseService
{
    /** @var string Primary cache key for sidebar */
    protected $cacheKey = 'navigation_sidebar';

    /** @var string Active Model Context 🎯 */
    protected $targetModel = 'SidebarMenus';

    /**
     * Get categories list 📂
     */
    public function categories(): array
    {
        return $this->SidebarProvider->getCategories();
    }

    /**
     * Get the categorized sidebar structure 🛰️⚓
     * Orchestrates the final navigation data for the layout.
     */
    public function getStructure(string $userRole): array
    {
        return $this->SidebarProvider->getSidebarStructure($userRole);
    }

    /**
     * Get full tree structure 🌳
     */
    public function tree(): array
    {
        return $this->SidebarProvider->getTree();
    }

    /**
     * Save Category (Explicit) 📂
     */
    public function saveCategory(array $data): bool|int
    {
        $result = $this->SidebarProvider->saveCategory($data);
        if ($result) {
            $this->clearCache();
        }
        return $result;
    }

    /**
     * Save Menu (Explicit) 📜
     */
    public function saveMenu(array $data): bool|int
    {
        $result = $this->SidebarProvider->saveMenu($data);
        if ($result) {
            $this->clearCache();
        }
        return $result;
    }

    /**
     * Clear All Sidebar Caches across all system roles 🧹🌍
     */
    public function clearSidebarCache(): self
    {
        $roles = array_keys(\Rbn\Framework\Bundles\RbnSuite\RbnAuth\Models\AuthRole::ROLES);
        $keys = array_map(fn($r) => "sidebar_{$r}", $roles);
        $keys[] = $this->cacheKey;

        return $this->clearCache($keys);
    }
}
