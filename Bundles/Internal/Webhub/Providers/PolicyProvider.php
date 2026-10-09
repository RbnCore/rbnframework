<?php

namespace Rbn\Framework\Bundles\Internal\Webhub\Providers;

use Rbn\Framework\Core\Base\Services\BaseProvider;

/**
 * PolicyProvider - RBN Framework Data Delivery 🕵️‍♂️🛰️⚓
 * RBN Framework Standard: Synchronized access for Pages and Redirects.
 * 
 * @property \Rbn\Framework\Core\Database\Models\Project\PagesModel $PagesModel
 */
class PolicyProvider extends BaseProvider
{
    /** @var string Primary Model Target (Default) 🎯 */
    protected $targetModel = 'Pages';

    /**
     * RBN Framework Persistence Discovery 🕵️‍♂️🛰️
     */
    protected function afterBoot(): void
    {
        $this->targetModel = 'Pages';
    }

    /**
     * Standard Save Override 💾
     * RBN Framework: Ensures beforeSave hook is explicitly triggered.
     */
    public function save(array $data): bool|int
    {
        // 🛡️ FW-BASE-1 T3: "sadece bossa doldur" semasi TEK merkeze tasindi.
        $data = \Rbn\Framework\Core\Base\Data\BaseModel::fillProjectKeyIfMissing($data);
        $this->beforeSave($data);
        return parent::save($data);
    }

    /**
     * Standard List of Available Templates 🎨
     */
    public function getTemplates(): array
    {
        return [
            'default'    => ['name' => 'Varsayılan'],
            'full-width' => ['name' => 'Tam Genişlik'],
            'sidebar'    => ['name' => 'Sidebar'],
            'landing'    => ['name' => 'Landing Page']
        ];
    }

    /* --- [ DOMAIN PROXY ENGINE ] --- */

    public function getPages(): array
    {
        // [FW-ALTYAPI-3 / H · G4] `PagesModel` artık `scoped = true`; elle
        // `where('project_key', active_project_key())` ikinci süzgeç olarak
        // KALDIRILDI (aktif bağlamla birebir aynıydı).
        return $this->PagesModel->query()->orderBy('order_num')->get()->all();
    }


    /* --- [ RBN Framework HOOKS ] --- */

    /**
     * Strategic Persistence Guard 🛡️
     */
    protected function beforeSave(array &$data): void
    {
        // 🎼 RBN Framework: [RBN Framework SANITIZATION] 🚿
        // Remove metadata that doesn't exist in DB schema.
        unset($data['entity']);

        // 🛡️ Handle Checkbox Logic for Pages
        if ($this->targetModel === 'Pages') {
            $data['show_in_footer'] = isset($data['show_in_footer']) ? 1 : 0;
        }
    }

    /**
     * RBN Framework Status Toggle 🔄
     */
    public function toggleStatus(int $id, string $field = 'status'): bool
    {
        $model = $this->component('model');
        if (!$model) return false;

        $record = $model->find($id);
        if (!$record) return false;

        $current = $record[$field] ?? 'draft';
        $newStatus = ($current === 'active') ? 'draft' : 'active';

        return (bool) $model->update($id, [$field => $newStatus]);
    }
}
