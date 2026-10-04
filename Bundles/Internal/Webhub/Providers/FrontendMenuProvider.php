<?php

namespace Rbn\Framework\Bundles\Internal\Webhub\Providers;

use Rbn\Framework\Core\Base\Services\BaseProvider;

/**
 * FrontendMenuProvider - Public Website Menu Query Expert 🌐🏛️⚓
 * RBN 3.5 Masterpiece Standard.
 * 
 * @property \Rbn\Framework\Core\Database\Models\Project\FrontendMenusModel $FrontendMenusModel
 */
class FrontendMenuProvider extends BaseProvider
{
    protected $targetModel = 'FrontendMenus';

    public function getFrontendMenus(?int $parentId = null, bool $onlyActive = true, bool $onlyParents = false): array
    {
        // [FW-ALTYAPI-3 / H · G4] `FrontendMenusModel` artık `scoped = true`;
        // `where('project_key', active_project_key())` ikinci süzgeç olarak
        // KALDIRILDI (aktif bağlamla birebir aynıydı).
        $query = $this->FrontendMenusModel->query()->orderBy('order_num', 'ASC');

        if ($onlyActive) {
            $query->where('is_active', 1);
        }

        if ($parentId !== null) {
            $query->where('parent_id', $parentId);
        }

        if ($onlyParents) {
            // RBN 3.5: Use explicit zero or actual null check if needed
            $query->where('parent_id', 0);
        }

        return $query->get()->all();
    }

    /**
     * Builds hierarchical tree for management or frontend views 🌳🛰️⚓
     * RBN 3.5: Hybrid mode supporting both flat indents and nested children.
     */
    public function getTree(int $parentId = 0, int $depth = 0, ?array $elements = null, bool $nested = false): array
    {
        if ($elements === null) {
            // [FW-ALTYAPI-3 / H · G4] Elle süzgeç kaldırıldı; kapsam modelde.
            $elements = $this->FrontendMenusModel->query()
                ->orderBy('order_num', 'ASC')->get()->toArray();
        }

        $branch = [];
        foreach ($elements as $element) {
            if ((int) $element['parent_id'] === $parentId) {
                if ($nested) {
                    // 🎼 RBN 3.5: Nested Mode (Masterpiece UI) 🧬
                    $children = $this->getTree((int) $element['id'], $depth + 1, $elements, true);
                    if ($children) {
                        $element['children'] = $children;
                    }
                    $branch[] = $element;
                } else {
                    // 🏛️ Legacy Mode: Flat with indent (Admin select boxes)
                    $element['indent_level'] = $depth;
                    $branch[] = $element;
                    $children = $this->getTree((int) $element['id'], $depth + 1, $elements, false);
                    if ($children) {
                        $branch = array_merge($branch, $children);
                    }
                }
            }
        }
        return $branch;
    }



    public function save(array $data): bool|int
    {
        // 🛡️ FW-BASE-1 T3: "sadece bossa doldur" semasi TEK merkeze tasindi.
        $data = \Rbn\Framework\Core\Base\Data\BaseModel::fillProjectKeyIfMissing($data);
        
        return parent::save($data);
    }

    /**
     * Get options for where the menu should be displayed 🧭
     */
    public function getShowInOptions(): array
    {
        return [
            0 => 'Üst ve Alt Menü (Header & Footer)',
            1 => 'Yalnızca Alt Menü (Footer Only)',
            2 => 'Alt Menü Sütun Başlığı (Footer Column Header)',
            3 => 'Gizli / Pasif (Hidden)',
        ];
    }

    /**
     * Smart Destruction with Child-Check Guard 🛡️
     */
    public function destroy(int $id): bool
    {
        // Guard: Prevent deletion if menu has sub-menus
        if ($this->FrontendMenusModel->where('parent_id', $id)->first()) {
            return false;
        }

        return (bool) $this->FrontendMenusModel->destroy($id);
    }

    /**
     * Retrieves hierarchical parents for dropdown selection 🌳🛰️⚓
     * RBN 3.5: Prevents circular hierarchy by excluding the branch of $excludeId.
     */
    public function getParents($excludeId = null): array
    {
        $tree = $this->getTree();
        $options = [];

        // 🛡️ Discovery: If we are editing, identify the forbidden branch (self + descendants)
        $forbiddenIds = [];
        if ($excludeId) {
            $forbiddenIds[] = (int) $excludeId;
            $this->findDescendants((int) $excludeId, $tree, $forbiddenIds);
        }

        foreach ($tree as $item) {
            if (in_array((int) $item['id'], $forbiddenIds)) {
                continue;
            }

            $prefix = $item['indent_level'] > 0 ? str_repeat(' ', $item['indent_level'] * 2) . '↳ ' : '';
            $options[] = [
                'id' => $item['id'],
                'title' => $prefix . $item['title']
            ];
        }

        return $options;
    }

    /**
     * Internal: Find all recursively connected children to prevent circular selection 🕵️‍♂️⚓
     */
    private function findDescendants(int $parentId, array $tree, array &$forbiddenIds): void
    {
        foreach ($tree as $item) {
            if ((int) $item['parent_id'] === $parentId) {
                $forbiddenIds[] = (int) $item['id'];
                $this->findDescendants((int) $item['id'], $tree, $forbiddenIds);
            }
        }
    }
}
