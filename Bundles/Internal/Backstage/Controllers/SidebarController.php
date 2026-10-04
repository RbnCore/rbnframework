<?php

namespace Rbn\Framework\Bundles\Internal\Backstage\Controllers;

use Rbn\Framework\Bundles\Internal\Backstage\Controllers\BackstageController;
use Rbn\Framework\Core\Base\Attributes\SubModule;
use Rbn\Framework\Bundles\RbnSuite\RbnAuth\Models\AuthRole;

/**
 * SidebarController - Advanced Sidebar Manager 🗺️🛰️⚓
 * RBN 3.5 Masterpiece Standard.
 */
#[SubModule(entity: 'sidebar', service: 'sidebar', provider: 'sidebar')]
class SidebarController extends BackstageController
{

    /**
     * Main Sidebar Management View 📑🛰️⚓
     */
    public function index(): void
    {
        $this->render('Sidebar/index', [
            'categories' => $this->service->categories(),
            'menus' => $this->service->tree(),
        ]);
    }

    /* ==========================================================================
       [ MODAL ORCHESTRATION ] 🪟🛰️⚓
       ========================================================================== */

    /**
     * Data Hook for Modals (Injected automatically by ActionControllerTrait)
     */
    protected function getModalData($id): array
    {
        $view = $this->request->input('view');
        $isMenu = ($view === 'modal_menu');

        $data = [
            'roles' => AuthRole::ROLES,
            'icons' => $isMenu
                ? $this->helper('icons')->category('menu')->toSelect()
                : $this->helper('icons')->category('sidebar')->toSelect()
        ];

        if ($isMenu) {
            $data['menu'] = $id ? $this->service->SidebarProvider->find((int) $id) : null;
            $data['categories'] = $this->service->categories();
            $data['parents'] = $this->service->SidebarProvider->getParentMenus();
        } else {
            $data['category'] = $id ? $this->service->SidebarProvider->find((int) $id) : null;
        }

        return $data;
    }

    /* ==========================================================================
       [ EXPLICIT ACTIONS ] 🚀🛰️⚓
       ========================================================================== */

    /**
     * Category Save Action 📂
     */
    public function categorySave(): void
    {
        $data = $this->request->form([
            'category_name' => 'required'
        ]);

        $result = $this->service->saveCategory($data);
        $this->handleResult($result, 'Kategori', null, 'store');
    }

    /**
     * Menu Save Action 📜
     */
    public function menuSave(): void
    {
        $data = $this->request->form([
            'menu_title' => 'required',
            'menu_url' => 'required',
            'category_id' => 'required'
        ]);

        $result = $this->service->saveMenu($data);
        $this->handleResult($result, 'Menü', null, 'store');
    }
}
