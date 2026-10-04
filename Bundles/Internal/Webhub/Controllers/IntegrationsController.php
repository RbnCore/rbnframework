<?php

declare(strict_types=1);

namespace Rbn\Framework\Bundles\Internal\Webhub\Controllers;

use Rbn\Framework\Core\Base\Attributes\SubModule;

/**
 * IntegrationsController - Webhub Integration & Scripts Orchestrator 🎯🛰️
 * Standard: RBN 3.5 Masterpiece - MIRROR OF IDENTITY
 */
#[SubModule(
    entity: 'integrations',
    entityName: 'setting',
    service: 'integrations',
    bulkInputKey: 'settings'
)]
class IntegrationsController extends WebhubController
{

    /**
     * Integrations Dashboard (Value View) -> Mirrored for Integrations
     */
    public function index(): void
    {
        $settings = $this->service('settings')
            ->withGroup('integrations')
            ->withProject(active_project_key())
            ->active()
            ->all();

        // Keep only generic script settings
        $settings = $this->filterSettings($settings, ['google_analytics_code', 'google_adsense_code', 'head_scripts', 'body_scripts', 'footer_scripts']);

        $this->render('Integrations/index', [
            'settings' => $settings
        ]);
    }

    /**
     * Integrations Management (Structure View) -> Mirrored for Integrations
     */
    public function manage(): void
    {
        $settings = $this->service('settings')
            ->withGroup('integrations')
            ->withProject(active_project_key())
            ->all();

        // Keep only generic script settings
        $settings = $this->filterSettings($settings, ['google_analytics_code', 'google_adsense_code', 'head_scripts', 'body_scripts', 'footer_scripts']);

        $this->render('Integrations/manage', [
            'settings' => $settings
        ]);
    }

    /**
     * Create or Update Integration Structural Requirement (Unified Save Endpoint) 💾
     */
    public function save(): void
    {
        $data = $this->request->form([
            'id' => 'nullable|numeric',
            'label_tr' => 'required',
            'label_en' => 'required',
            'setting_key' => 'required',
            'field_type' => 'nullable',
            'required_role' => 'nullable',
            'help_text_tr' => 'nullable'
        ]);

        $id = !empty($data['id']) ? (int) $data['id'] : null;

        unset($data['project']);

        if ($id) {
            $result = $this->service->withId($id)->update($data);
        } else {
            $result = $this->service->create(array_merge($data, [
                'group_id' => 10,
                'is_active' => 1
            ]));
        }

        $this->handleResult($result, $id ? 'Entegrasyon yapılandırması' : 'Yeni Entegrasyon alanı', 'integrations/manage');
    }

    public function setupAdsense(): void
    {
        $result = $this->service->setupAdsense();
        $this->handleResult($result, 'Google AdSense ayarları başarıyla kuruldu!', 'integrations/adsense');
    }

    public function adsense(): void
    {
        $rawSettings = $this->service('settings')
            ->withGroup('integrations')
            ->withProject(active_project_key())
            ->all();

        // Keep only AdSense specific settings
        $rawSettings = $this->filterSettings($rawSettings, [
            'adsense_status',
            'adsense_client_id',
            'ads_slot_feed',
            'ads_slot_content_top',
            'ads_slot_content_bottom',
            'ads_slot_sidebar',
            'ads_slot_left_skyscraper',
            'ads_slot_right_skyscraper'
        ]);

        $settings = [];
        $settingIds = [];
        $settingActives = [];
        foreach ($rawSettings as $s) {
            $key = is_object($s) ? ($s->setting_key ?? null) : ($s['setting_key'] ?? null);
            $val = is_object($s) ? ($s->setting_value ?? null) : ($s['setting_value'] ?? null);
            $id = is_object($s) ? ($s->id ?? null) : ($s['id'] ?? null);
            $active = is_object($s) ? ($s->is_active ?? null) : ($s['is_active'] ?? null);
            if ($key) {
                $settings[$key] = $val;
                $settingIds[$key] = $id;
                $settingActives[$key] = (bool)$active;
            }
        }

        $this->render('Integrations/adsense', [
            'settings' => $settings,
            'settingIds' => $settingIds,
            'settingActives' => $settingActives
        ]);
    }

    /* Trait Handles: status(), delete(), bulkOrder(), modal(), bulkValueUpdate() */
}
