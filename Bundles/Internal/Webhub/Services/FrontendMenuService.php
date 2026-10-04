<?php

namespace Rbn\Framework\Bundles\Internal\Webhub\Services;

use Rbn\Framework\Core\Base\Services\BaseService;

/**
 * FrontendMenuService - Public Website Menu Orchestrator 🌐🏛️⚓
 * RBN 3.5 Masterpiece Standard.
 * 
 * @property \Rbn\Framework\Bundles\Internal\Webhub\Providers\FrontendMenuProvider $FrontendMenuProvider
 */
class FrontendMenuService extends BaseService
{
    protected $targetModel = 'FrontendMenus';

    /**
     * Get parent menus for hierarchy selection 🕊️
     */
    public function parents(?int $excludeId = null): array
    {
        return $this->FrontendMenuProvider->getParents($excludeId);
    }

    /**
     * Get frontend menus with smart active state computed automatically 🧭🌐⚓
     */
    public function getList(?int $parentId = null, bool $onlyActive = true): array
    {
        $menus = $this->FrontendMenuProvider->getFrontendMenus($parentId, $onlyActive);
        $path = rtrim($this->request->path(), '/') ?: '/';

        foreach ($menus as &$menu) {
            $menuUrl = rtrim((string) ($menu['url'] ?? ''), '/') ?: '/';
            $menu['is_current'] = ($menuUrl === '/') 
                ? ($path === '/') 
                : ($path === $menuUrl || str_starts_with($path, $menuUrl . '/'));
            $menu['active'] = $menu['is_current'];
        }
        unset($menu);

        return $menus;
    }

    /**
     * Get full tree structure (Specialized logic not covered by standard CrudTrait)
     */
    public function getTree(): array
    {
        return $this->FrontendMenuProvider->getTree();
    }

    /**
     * Get full nested tree for frontend navigation 🌳🌐⚓
     */
    public function getNested(int $parentId = 0): array
    {
        return $this->FrontendMenuProvider->getTree($parentId, 0, null, true);
    }


}
