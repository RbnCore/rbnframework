<?php

declare(strict_types=1);

namespace Rbn\Framework\Bundles\Internal\Webhub\Controllers;

use Rbn\Framework\Core\Base\Attributes\SubModule;

/**
 * NavigationController - Frontend Menu Orchestrator 🌐🏛️⚓
 * RBN 3.5 Masterpiece Standard.
 */
#[SubModule(
    entity: 'navigation',
    service: 'frontendMenu'
)]
class NavigationController extends WebhubController
{
    /**
     * Menu Management Dashboard 🛰️🎯
     */
    public function index(): void
    {
        // 🎼 RBN 3.5: [SOVEREIGN HIERARCHY DISCOVERY] 🏹🛰️⚓
        $menus = $this->service->getList();

        $this->render('Navigation/index', [
            'menus' => $menus
        ]);
    }

    /**
     * Strategic Data Injection for the Modal (Hierarchy Support) 🌳🏛️⚓
     */
    protected function getModalData($id): array
    {
        $data = [
            'parents' => $this->service->parents($id ? (int) $id : null),
            'showInOptions' => $this->service->FrontendMenuProvider->getShowInOptions()
        ];

        if ($id > 0) {
            $data['navigation'] = $this->service->find((int) $id);
        }

        return $data;
    }

    /**
     * Menu Creation or Update (Unified Save Endpoint) 💾
     */
    public function save(): void
    {
        // FW-ALTYAPI-2 / B (adım 2): `form([])` → `rawAll()`. Menü alanları kural
        // dizisinde tanımlı değil; ham yol bilinçli tercih (beyaz liste `T5`
        // `crudInput()` yalnız `CrudControllerTrait` yolunda çalışır).
        $data = $this->request->rawAll();
        $id = !empty($data['id']) ? (int) $data['id'] : null;

        unset($data['project']);

        $result = $this->activeService->save($id, $data);

        $this->handleResult($result, null, null, $id ? 'update' : 'create');
    }
}
