<?php

declare(strict_types=1);

namespace Rbn\Framework\Bundles\Internal\Webhub\Controllers;

use Rbn\Framework\Core\Base\Attributes\SubModule;

/**
 * IdentityController - Corporate Identity & Brand Orchestrator 👤
 * Standard: RBN Framework
 */
#[SubModule(
    entity: 'identity',
    entityName: 'setting',
    bulkInputKey: 'settings'
)]
class IdentityController extends WebhubController
{

    /**
     * Identity Dashboard (Value View)
     */
    public function index(): void
    {
        $settings = $this->service('settings')
            ->withGroup('company')
            ->withProject(active_project_key())
            ->active()
            ->all();

        $this->render('Identity/identity', [
            'settings' => $settings
        ]);
    }

    /**
     * Identity Management (Structure View)
     */
    public function manage(): void
    {
        $settings = $this->service('settings')
            ->withGroup('company')
            ->withProject(active_project_key())
            ->all();

        $this->render('Identity/manage', [
            'settings' => $settings
        ]);
    }

    /**
     * Create New Structural Requirement (Override for Group ID)
     */
    public function create(): void
    {
        $data = $this->request->form([
            'label_tr' => 'required',
            'setting_key' => 'nullable',
            'field_type' => 'required',
            'required_role' => 'developer'
        ]);

        $result = $this->service->action()->save(array_merge($data, [
            'group_id' => 1,
            'is_active' => 1
        ]));

        $this->handleResult($result, 'Yeni kimlik alanı', 'identity');
    }

    /**
     * Update Structural Requirement
     */
    public function update(): void
    {
        $data = $this->request->form([
            'id' => 'required|numeric',
            'label_tr' => 'required',
            'setting_key' => 'required',
        ]);

        $result = $this->service->action()->withId((int) $data['id'])->save($data);

        $this->handleResult($result, 'Alan yapılandırması', 'identity');
    }

    /* Trait Handles: status(), delete(), bulkOrder(), modal() */
}




