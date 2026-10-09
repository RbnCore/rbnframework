<?php

declare(strict_types=1);

namespace Rbn\Framework\Bundles\Internal\Webhub\Services;

use Rbn\Framework\Core\Services\System\SettingsService;
use Rbn\Framework\Core\Services\System\Models\SettingsConfig;

/**
 * IntegrationsService - RBN Framework Orchestrator for Webhub Integrations Submodule 🛰️⚓
 */
class IntegrationsService extends SettingsService
{
    /** --- Infrastructure DNA --- */
    protected $targetModel = 'settings';
    protected array $cacheKeys = ['settings'];

    /**
     * Seed AdSense settings templates into settings database.
     */
    public function setupAdsense(): bool
    {
        $projectKey = active_project_key();
        $settingsService = $this->service('settings');

        // 1. Find the existing 'integrations' group ID using SettingsService
        $groups = $settingsService->groups()->withGroup('integrations')->all();
        $groupId = 5; // Default fallback to system integrations group ID
        if (!empty($groups)) {
            $group = reset($groups);
            $groupId = is_object($group) ? ($group->id ?? 5) : ($group['id'] ?? 5);
        }

        // 2. Fetch default settings from SettingsConfig
        $defaultSettings = SettingsConfig::DEFAULT_SETTINGS;

        // 3. Create settings in DB using SettingsService
        foreach ($defaultSettings as $setting) {
            $key = $setting['setting_key'] ?? '';
            if (str_starts_with($key, 'adsense_') || str_starts_with($key, 'ads_')) {
                $exists = $settingsService
                    ->withKey($key)
                    ->withProject($projectKey)
                    ->all();

                if (empty($exists)) {
                    $settingsService->save(null, [
                        'group_id' => $groupId,
                        'project_key' => $projectKey,
                        'setting_key' => $key,
                        'setting_value' => $setting['setting_value'],
                        'label_tr' => $setting['label_tr'],
                        'label_en' => $setting['label_en'],
                        'field_type' => $setting['field_type'],
                        'help_text_tr' => $setting['help_text_tr'],
                        'help_text_en' => $setting['help_text_en'],
                        'required_role' => $setting['required_role'],
                        'is_active' => $setting['is_active'],
                        'order_num' => $setting['order_num']
                    ]);
                }
            }
        }

        // Purge settings cache
        try {
            $settingsService->cache()->deleteByPrefix('settings_group_');
        } catch (\Throwable $e) {}

        return true;
    }
}
