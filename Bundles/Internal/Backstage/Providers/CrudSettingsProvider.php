<?php

namespace Rbn\Framework\Bundles\Internal\Backstage\Providers;

use Rbn\Framework\Core\Base\Services\BaseProvider;

/**
 * CrudSettingsProvider - Unified Write Operations for Settings and Groups ✍️⚙️📂
 * 
 * @property \Rbn\Framework\Core\Database\Models\Project\SettingsModel $SettingsModel
 * @property \Rbn\Framework\Core\Database\Models\Project\SettingsGroupModel $SettingsGroupModel
 */
class CrudSettingsProvider extends BaseProvider
{
    /**
     * Yeni Ayar Kaydet 🆕⚙️
     */
    public function createSetting(array $data): int|bool
    {
        // 🛡️ [PARASITE PROTECTION] - Veri tabanında olmayan UI alanlarını temizle
        unset($data['redirect_url'], $data['view'], $data['id']);

        if (empty($data['setting_key']) && !empty($data['label_tr'])) {
            $slug = $this->helper('text')->turkishSlug($data['label_tr']);
            $data['setting_key'] = str_replace('-', '_', $slug);
        }
        $data['is_active'] = $data['is_active'] ?? 1;

        // 🛡️ [JSON PROTECTION] - Boş gelen JSON alanlarını null yap
        if (isset($data['field_options']) && empty($data['field_options'])) {
            $data['field_options'] = null;
        }

        return $this->SettingsModel->create($data);
    }

    /**
     * Ayar Güncelle ⚙️
     */
    public function updateSetting(int $id, array $data): bool
    {
        unset($data['redirect_url'], $data['view'], $data['id']);

        // 🛡️ [JSON PROTECTION] - Boş gelen JSON alanlarını null yap
        if (isset($data['field_options']) && empty($data['field_options'])) {
            $data['field_options'] = null;
        }

        return (bool) $this->SettingsModel->update($id, $data);
    }

    /**
     * Yeni Grup Kaydet 📂🆕
     */
    public function createGroup(array $data): int|bool
    {
        unset($data['redirect_url'], $data['view'], $data['id']);
        return $this->SettingsGroupModel->create($data);
    }

    /**
     * Grup Güncelle 📂⚙️
     */
    public function updateGroup(int $id, array $data): bool
    {
        unset($data['redirect_url'], $data['view'], $data['id']);
        return (bool) $this->SettingsGroupModel->update($id, $data);
    }


    /**
     * Silme İşlemi 🗑️
     */
    public function delete(string $entity, int $id): bool
    {
        $model = (strtolower($entity) === 'settings') ? $this->SettingsModel : $this->SettingsGroupModel;
        return (bool) $model->destroy($id);
    }
}
