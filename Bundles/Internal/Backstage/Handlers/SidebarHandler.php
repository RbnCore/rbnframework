<?php

namespace Rbn\Framework\Bundles\Internal\Backstage\Handlers;

use Rbn\Framework\Core\Base\BaseComponent;
use Exception;

/**
 * SidebarHandler - Action specialist for Admin Sidebar 🛰️🛠️⚓
 * RBN Framework Standard.
 * 
 * @property \Rbn\Framework\Core\Database\Models\Project\SidebarMenusModel $SidebarMenusModel
 * @property \Rbn\Framework\Core\Database\Models\Project\SidebarCategoriesModel $SidebarCategoriesModel
 */
class SidebarHandler extends BaseComponent
{
    /**
     * Save/Update Sidebar Category
     */
    public function saveCategory(array $data, ?int $id = null): bool
    {
        if (!empty($data['category_name']) && empty($data['category_slug'])) {
            $data['category_slug'] = $this->helper('text')->turkishSlug($data['category_name']);
        }

        return $id ? $this->SidebarCategoriesModel->update($id, $data) : (bool)$this->SidebarCategoriesModel->create($data);
    }

    /**
     * Save/Update Sidebar Menu Item
     */
    public function saveMenu(array $data, ?int $id = null): bool
    {
        if (isset($data['menu_title']) && empty($data['menu_slug'])) {
            $data['menu_slug'] = $this->helper('text')->turkishSlug($data['menu_title']);
        }

        return $id ? $this->SidebarMenusModel->update($id, $data) : (bool)$this->SidebarMenusModel->create($data);
    }

    /**
     * Atomically reorder items ⛓️
     */
    public function reorder(string $type, array $ids): bool
    {
        $model = ($type === 'category') ? $this->SidebarCategoriesModel : $this->SidebarMenusModel;
        $field = ($type === 'category') ? 'category_order' : 'menu_order';

        $model->beginTransaction();
        
        try {
            foreach ($ids as $index => $id) {
                if (!$model->update((int)$id, [$field => $index + 1])) {
                    $model->rollBack();
                    return false;
                }
            }
            $model->commit();
            return true;
        } catch (Exception $e) {
            $model->rollBack();
            return false;
        }
    }

    public function deleteCategory(int $id): bool
    {
        $menus = $this->SidebarMenusModel->where('category_id', $id)->get();
        if (!empty($menus)) return false;

        return $this->SidebarCategoriesModel->destroy($id);
    }

    public function deleteMenu(int $id): bool
    {
        $childMenus = $this->SidebarMenusModel->where('parent_id', $id)->get();
        if (!empty($childMenus)) return false;

        return $this->SidebarMenusModel->destroy($id);
    }
}
