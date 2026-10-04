<?php

namespace Rbn\Framework\Bundles\Internal\Backstage\Providers;

use Rbn\Framework\Core\Base\Services\BaseProvider;

/**
 * SidebarProvider - Admin Sidebar Query Expert 🗺️🏛️⚓
 * RBN 3.5 Masterpiece Standard.
 * 
 * @property \Rbn\Framework\Core\Database\Models\Project\SidebarMenusModel $SidebarMenusModel
 * @property \Rbn\Framework\Core\Database\Models\Project\SidebarCategoriesModel $SidebarCategoriesModel
 */
class SidebarProvider extends BaseProvider
{
    /** @var string The active model context for autonomous CRUD 🎯 */
    protected $targetModel = 'SidebarMenus';

    /**
     * Autonomous Model Initialization 🛰️⚓
     * RBN 3.5: Detects the active model context directly from the URI.
     */
    protected function afterBoot(): void
    {
        $uri = $this->request->path();
        if (str_contains($uri, '/category')) {
            $this->targetModel = 'SidebarCategories';
        } elseif (str_contains($uri, '/menu')) {
            $this->targetModel = 'SidebarMenus';
        }
    }


    /**
     * Builds the full sidebar structure based on user permissions 👤🛰️
     */
    public function getSidebarStructure(string $userRole): array
    {
        // [RBN 3.5] SOVEREIGN CACHING: Önbelleği her seferinde silmek yerine, sadece değişim olduğunda (Service üzerinden) temizliyoruz.
        // $this->cache()->delete("sidebar_{$userRole}");

        // 🎼 RBN 3.5: [ATOMIC SIDEBAR CACHING] ⚡🛰️⚓
        //
        // [FW-ALTYAPI-3 / H · G4] İKİ DÜZELTME:
        //  1) ÖNBELLEK ANAHTARI kiracıyı İÇERMEYECEKİ — "sidebar_{rol}" anahtarı
        //     TÜM kiracılarda ortaktı, yani bir projenin paneli başka projenin
        //     kenar çubuğunu gösterebiliyordu (GÖRÜNMEZLİK değil, SIZINTI).
        //     Anahtara aktif kiraci eklendi.
        //  2) `where('project_key', $activeProject)` ikinci süzgeç olarak
        //     KALDIRILDI — `SidebarCategoriesModel`/`SidebarMenusModel` artık
        //     `scoped = true`, kapsam modelin beyanı.
        $activeProject = active_project_key();
        return $this->cache()->remember("sidebar_{$activeProject}_{$userRole}", function () use ($userRole, $activeProject) {
            $categories = $this->SidebarCategoriesModel
                ->where('is_active', 1)
                ->orderBy('order_num', 'ASC')
                ->get()
                ->toArray();

            $sidebarData = [];

            foreach ($categories as $cat) {
                $categoryMenus = $this->getMenusForCategory($cat['id'], $userRole, $activeProject);

                if (!empty($categoryMenus)) {
                    $sidebarData[] = [
                        'name' => $cat['category_name'],
                        'icon' => $cat['category_icon'] ?? 'bi-folder',
                        'menus' => $categoryMenus
                    ];
                }
            }

            return $sidebarData;
        });
    }

    /**
     * Internal: Categorized menus with role filtering 🛡️
     */
    protected function getMenusForCategory(int $categoryId, string $userRole, string $activeProject): array
    {
        // [FW-ALTYAPI-3 / H · G4] `$activeProject` imzasi KORUNDU (çağıranlar
        // değişmedi) ama `where('project_key', ...)` süzgeci kaldırıldı: kapsam
        // `SidebarMenusModel`'de açık.
        $menus = $this->SidebarMenusModel
            ->where('category_id', $categoryId)
            ->where('parent_id', 0)
            ->where('is_active', 1)
            ->orderBy('order_num', 'ASC')
            ->get()
            ->toArray();

        $result = [];
        foreach ($menus as $menu) {
            if (!$this->checkRoleAccess($menu['required_role'], $userRole)) {
                continue;
            }

            // Fetch children (Submenus)
            $menu['children'] = $this->SidebarMenusModel
                ->where('parent_id', $menu['id'])
                ->where('is_active', 1)
                ->orderBy('order_num', 'ASC')
                ->get()
                ->toArray();

            $result[] = $menu;
        }

        return $result;
    }

    /**
     * Builds hierarchical tree for management views 🌳
     */
    public function getTree(int $parentId = 0, int $depth = 0, ?array $elements = null): array
    {
        if ($elements === null) {
            // [FW-ALTYAPI-3 / H · G4] Elle süzgeç kaldırıldı; kapsam modelde.
            $elements = $this->SidebarMenusModel
                ->orderBy('order_num', 'ASC')
                ->get()
                ->toArray();
        }

        $branch = [];
        foreach ($elements as $element) {
            if ($element['parent_id'] == $parentId) {
                $element['indent_level'] = $depth;
                $branch[] = $element;
                $children = $this->getTree($element['id'], $depth + 1, $elements);
                if ($children) {
                    $branch = array_merge($branch, $children);
                }
            }
        }
        return $branch;
    }

    public function getCategories(): array
    {
        // [FW-ALTYAPI-3 / H · G4] Elle süzgeç kaldırıldı; kapsam modelde.
        return $this->SidebarCategoriesModel
            ->orderBy('order_num', 'ASC')
            ->get()
            ->toArray() ?: [];
    }

    public function getParentMenus(): array
    {
        // [FW-ALTYAPI-3 / H · G4] Elle süzgeç kaldırıldı; kapsam modelde.
        return $this->SidebarMenusModel
            ->where('parent_id', 0)
            ->get()
            ->toArray() ?: [];
    }

    /**
     * Save Category (Explicit Model Targeting) 📂
     */
    public function saveCategory(array $data): bool|int
    {
        // 🎼 RBN 3.5: [AUTO-SLUG GENERATION] 🚀
        if (isset($data['category_name']) && empty($data['category_slug'])) {
            $data['category_slug'] = $this->helper('text')->turkishSlug($data['category_name']);
        }
        // 🛡️ FW-BASE-1 T3: "sadece bossa doldur" semasi TEK merkeze tasindi.
        $data = \Rbn\Framework\Core\Base\Data\BaseModel::fillProjectKeyIfMissing($data);

        return $this->SidebarCategoriesModel->save($data);
    }

    /**
     * Save Menu (Explicit Model Targeting) 📜
     */
    public function saveMenu(array $data): bool|int
    {
        // 🎼 RBN 3.5: [AUTO-SLUG GENERATION] 🚀
        if (isset($data['menu_title']) && empty($data['menu_slug'])) {
            $data['menu_slug'] = $this->helper('text')->turkishSlug($data['menu_title']);
        }
        // 🛡️ FW-BASE-1 T3: "sadece bossa doldur" semasi TEK merkeze tasindi.
        $data = \Rbn\Framework\Core\Base\Data\BaseModel::fillProjectKeyIfMissing($data);

        return $this->SidebarMenusModel->save($data);
    }

    /**
     * Role gate logic (Centralized) 🛡️
     */
    protected function checkRoleAccess(?string $required, string $userRole): bool
    {
        if (empty($required))
            return true;
        if ($userRole === 'developer')
            return true;

        $roles = \Rbn\Framework\Bundles\RbnSuite\RbnAuth\Models\AuthRole::ROLES;
        $userLevel = $roles[$userRole]['level'] ?? 0;
        $requiredLevel = $roles[$required]['level'] ?? 0;

        return $userLevel >= $requiredLevel;
    }
}
