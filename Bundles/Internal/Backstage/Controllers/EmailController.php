<?php

namespace Rbn\Framework\Bundles\Internal\Backstage\Controllers;

use Rbn\Framework\Core\Base\Attributes\SubModule;

/**
 * EmailController - Email Configuration Management 🛡️🛰️⚓
 * 
 * RBN 3.5 Masterpiece Standard.
 */
#[SubModule(entity: 'email', service: 'backstage', provider: 'backstage', model: 'Settings')]
class EmailController extends BackstageController
{
    /**
     * E-Posta Ayarları Ana Paneli 🛡️🛰️⚓
     */
    public function index()
    {
        // 🎼 DNA-level orchestration handled by BackstageService.
        $emailData = $this->BackstageService->getEmailSettings();
        return $this->render('Emails/index', array_merge($emailData));
    }

    /**
     * E-Posta Mimari Yönetimi (Sıralama ve Detay) 🛡️🛰️⚓
     */
    public function manage()
    {
        // 🎼 Fetching flat settings list for management.
        $allSettings = $this->BackstageService->getSettingsRaw('email');
        return $this->render('Emails/manage', ['settings' => $allSettings]);
    }

    /**
     * Otonom Modal Veri Kaynağı 🏹🛰️⚓
     * RBN 3.5: Veri enjeksiyonu ve dinamik view keşfini burada yönetir.
     */
    protected function getModalData($id)
    {
        // 🎼 Dinamik View Switcher: Otonom request nesnesi üzerinden mülke yansıt.
        if ($view = $this->request->input('view')) {
            $this->modalView = "Emails/Partials/{$view}";
        }

        $id = $id ? (int) $id : null;
        $data = $id ? $this->activeService->provider->find($id) : [];

        if (!$id) {
            // 🎼 Email Ayarlar Grubu ID: 3
            $data['group_id'] = 3;
            $data['required_role'] = 'developer';
            $data['field_type'] = 'text';
        }

        return $data;
    }

    /**
     * Test E-Postası Gönder 📧🛰️
     * RBN 3.5 Custom logic preservation.
     */
    public function testMail()
    {
        $settings = $this->service('settings')->read('email');
        $targetEmail = $settings['email_admin_address'] ?? null;

        if (!$targetEmail) {
            return $this->Route->alert('error', 'Test maili göndermek için önce "Admin Bildirim E-Postası" ayarını kaydedin.');
        }

        $emailService = $this->service('rbnEmail');
        $data = [
            'time' => now('d.m.Y H:i:s'),
            'site_name' => $this->service('seo')?->get('site_title') ?? 'RBN Framework'
        ];

        $result = $emailService->sendTemplate('test_connection', $targetEmail, $data);

        // 🎼 Strategic Dispatch: Using trait-compatible handleResult patterns.
        return $this->Route->handleResult($result, [
            'success_message' => "Test maili başarıyla gönderildi: <b>{$targetEmail}</b>",
            'error_message' => 'Lütfen SMTP ayarlarını kontrol edin.'
        ]);
    }

    /**
     * E-Posta Ayarlarını Güncelle 💾🛰️⚓
     * RBN 3.5: Toplu (Index) ve Tekli (Modal) güncellemeleri tek metotla yönetir.
     */
    public function updateMailSettings()
    {
        $settings = $this->request->input('settings');

        // 🎻 1. Senaryo: Toplu Güncelleme (Index Sayfası)
        if ($settings && is_array($settings)) {
            $result = $this->BackstageService->bulkUpdateSettings($settings);
            return $this->handleResult($result, 'E-Posta ayarları', false);
        }

        // 🎻 2. Senaryo: Tekli Yapısal Güncelleme (Manage Modal)
        $data = $this->request->form([
            'id' => 'required',
            'label_tr' => 'required',
            'label_en' => 'nullable',
            'setting_key' => 'required',
            'field_type' => 'required',
            'required_role' => 'required',
            'help_text_tr' => 'nullable',
            'field_options' => 'nullable'
        ]);

        $result = $this->BackstageService->updateSetting((int)$data['id'], $data);
        return $this->handleResult($result, 'Alan yapılandırması', false);
    }
}
