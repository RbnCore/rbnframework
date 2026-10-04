<?php

namespace Rbn\Framework\Bundles\Internal\Backstage\Controllers;

use Rbn\Framework\Core\Base\Attributes\SubModule;

/**
 * SettingsArchitectController - Architectural Control over Global Settings 🛡️🏗️⚓
 * 
 */
#[SubModule(entity: 'settings', service: 'backstage', provider: 'backstage')]
class SettingsArchitectController extends BackstageController
{
    /**
     * Ayar Grupları Listesi 🛡️🏗️⚓
     */
    public function index(): void
    {
        $this->render('Settings/index', [
            'groups' => $this->BackstageService->getGroups()
        ]);
    }

    /**
     * Gruba Özel Ayar Listesi 🛡️📊⚓
     */
    public function group($id): void
    {
        $data = $this->BackstageService->getGroupWithSettings((int) $id);

        if (empty($data)) {
            $this->Route->alert('error', 'Ayar grubu bulunamadı.');
            return;
        }

        $this->render('Settings/group_settings', $data);
    }

    /* ==========================================================================
       [ AUTONOMOUS MODAL DISPATCHER ] 🏹🛰️⚓
       ========================================================================== */

    /**
     * Otonom Modal Veri Kaynağı
     * 1. modal.rbn.php: Ayar ekle/düzenle (Add-Edit)
     * 2. modal_group.rbn.php: Grup ekle/düzenle (Add-Edit)
     */
    protected function getModalData($id)
    {
        $view = $this->request->input('view');
        $id = $id ? (int) $id : null;

        $settingsDb = $this->repository('project.settings');

        // 🎼 Dynamic Context Discovery & Provider Targeting
        if ($view === 'modal_group') {
            $this->modalView = "Settings/Partials/modal_group";
            $group = $id ? $settingsDb->fetch(['target' => 'groups', 'id' => $id]) : [];
            $data = !empty($group) ? $group[0] : [];
            // 🎨 RBN 3.5: [SOVEREIGN HELPER] Accessing IconLibrary via the official 'icons' alias.
            $data['icons'] = $this->helper('icons')->category('popular')->toSelect();
            return $data;
        }

        // Default: Settings (modal.rbn.php)
        $this->modalView = "Settings/Partials/modal";
        $setting = $id ? $settingsDb->fetch(['id' => $id]) : [];
        $data = !empty($setting) ? $setting[0] : [];
        $data['groups'] = $this->BackstageService->getGroups(); // 📂 Aktif grupları enjekte et

        if (!$id) {
            $data['group_id'] = $this->request->input('groupId');
            $data['required_role'] = 'developer';
            $data['field_type'] = 'text';
        }

        return $data;
    }

    /* ==========================================================================
       [ EXPLICIT ACTIONS ] 🚀🛰️⚓
       ========================================================================== */

    /**
     * Yeni Ayar Kaydet 🆕⚙️🛰️⚓
     */
    public function createSetting(): void
    {
        // 🛡️ [SECURITY & INTEGRITY] - Sadece DB'de karşılığı olan alanları al.
        // redirect_url gibi UI alanlarını burada filtreliyoruz.
        $data = $this->request->form([
            'group_id' => 'required',
            'label_tr' => 'required',
            'label_en' => 'nullable',
            'setting_key' => 'nullable',
            'setting_value' => 'nullable',
            'field_type' => 'required',
            'field_options' => 'nullable',
            'help_text_tr' => 'nullable',
            'help_text_en' => 'nullable',
            'required_role' => 'required',
            'order_num' => 'nullable'
        ]);

        $result = $this->BackstageService->createSetting($data);
        $this->handleResult($result, 'Ayar', false);
    }

    /**
     * Mevcut Ayarı Güncelle ⚙️🛰️⚓
     */
    public function updateSetting(): void
    {
        $data = $this->request->form([
            'id' => 'required',
            'label_tr' => 'required',
            'label_en' => 'nullable',
            'setting_value' => 'nullable',
            'field_type' => 'required',
            'field_options' => 'nullable',
            'help_text_tr' => 'nullable',
            'help_text_en' => 'nullable',
            'required_role' => 'required',
            'order_num' => 'nullable'
        ]);

        $result = $this->BackstageService->updateSetting((int) $data['id'], $data);
        $this->handleResult($result, 'Ayar', false);
    }

    /**
     * Yeni Ayar Grubu Kaydet 📂🆕🛰️⚓
     */
    public function createGroup(): void
    {
        $data = $this->request->form([
            'group_label' => 'required',
            'group_icon' => 'required'
        ]);

        $result = $this->BackstageService->createGroup($data);
        $this->handleResult($result, 'Ayar grubu', false);
    }

    /**
     * Mevcut Ayar Grubunu Güncelle 📂⚙️🛰️⚓
     */
    public function updateGroup(): void
    {
        $data = $this->request->form([
            'id' => 'required',
            'group_label' => 'required',
            'group_icon' => 'required'
        ]);

        $result = $this->BackstageService->updateGroup((int) $data['id'], $data);
        $this->handleResult($result, 'Ayar grubu', false);
    }
}
