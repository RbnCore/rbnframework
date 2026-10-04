<?php

declare(strict_types=1);

namespace Rbn\Framework\Bundles\Internal\Webhub\Controllers;

use Rbn\Framework\Core\Base\Attributes\SubModule;

/**
 * PolicyController - Thin Sovereign Orchestrator 🛡️🏛️⚓
 * RBN 3.5 Masterpiece Standard.
 */
#[SubModule(
    entity: 'policy',
    service: 'policy',
    provider: 'policy'
)]
class PolicyController extends WebhubController
{
    /**
     * Unified Dashboard: Pages 🛰️🎯
     */
    public function index(): void
    {
        $this->render('Policy/index', [
            'pages' => $this->service->getPages()
        ]);
    }

    /**
     * Remote Modal Context Provider 🎭
     */
    protected function getModalData($id): array
    {
        $id = (int) $id;
        $record = $id ? $this->service->findRecord($id, 'page') : null;

        return [
            'page' => $record ?? [],
            'templates' => $this->service->getTemplates(),
            'entity' => 'page'
        ];
    }

    /**
     * Page Creation or Update (Unified Save Endpoint) 💾
     */
    public function save(): void
    {
        // FW-ALTYAPI-2 / B (adım 2): `form([])` → `rawAll()` (içerik alanları kural
        // dizisinde tanımlı değil; ham yol bilinçli tercih).
        $data = $this->request->rawAll();
        $id = !empty($data['id']) ? (int) $data['id'] : null;

        unset($data['project']);

        if ($id) {
            $result = $this->service->update($data);
        } else {
            $result = $this->service->create($data);
        }

        $this->handleResult($result, null, null, $id ? 'update' : 'create');
    }
}
