<?php

declare(strict_types=1);

namespace Rbn\Framework\Bundles\Internal\Webhub\Controllers;

use Rbn\Framework\Core\Base\Attributes\SubModule;

/**
 * FaqController - SSS Yönetim Merkezi 🎻🛰️⚓
 * 
 * RBN 3.5 "Masterpiece" Architecture.
 * Sovereignty is defined via the SubModule attribute.
 */
#[SubModule(
    entity: 'faq',
    service: 'faq',
    provider: 'faq'
)]
class FaqController extends WebhubController
{
    /**
     * FAQ List (Main View) 🔍
     */
    public function index()
    {
        // 🎼 RBN 3.5: Masterpiece Veri Akışı 🎻🛰️⚓
        $faqs = $this->service->all();
        
        $paginator = $this->paginate($faqs)->to('faqs');
        
        return $this->render('Faq/index', [
            'faqs' => $paginator->items()
        ]);
    }

    /**
     * Validation Rules (Automated via Trait) 🛡️
     */
    protected function rules(): array
    {
        return [
            'id'       => 'nullable',
            'question' => 'required|min:5',
            'answer'   => 'required|min:10',
            'status'   => 'required'
        ];
    }

    /**
     * FAQ Creation or Update (Unified Save Endpoint) 💾
     */
    public function save(): void
    {
        $data = $this->request->form($this->rules());
        $id = !empty($data['id']) ? (int) $data['id'] : null;

        unset($data['project']);

        $result = $this->activeService->save($id, $data);

        $this->handleResult($result, null, null, $id ? 'update' : 'create');
    }
}
