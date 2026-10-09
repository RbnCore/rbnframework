<?php

declare(strict_types=1);

namespace Rbn\Framework\Bundles\Internal\Webhub\Providers;

use Rbn\Framework\Core\Base\Services\BaseProvider;

/**
 * WebhubProvider - RBN Framework Data Bridge for Webhub Bundle 🛰️⚓
 * 
 * RBN Framework: Specialized provider for handling settings and metadata
 * specifically optimized for the Webhub context.
 * 
 * @property \Rbn\Framework\Core\Database\Models\Project\SettingsModel $SettingsModel
 * @property \Rbn\Framework\Core\Database\Models\Project\SettingsGroupModel $SettingsGroupModel
 */
class WebhubProvider extends BaseProvider
{
    /**
     * Settings Model Name (RBN Framework Standard) 🎯
     */
    protected $targetModel = 'settings';


    /**
     * Specialized Persistence: Settings Auto-Mapper 💾🛰️⚓
     * Maps 'value' key to 'setting_value' and handles updates by 'setting_key'.
     */
    public function save(array $data): bool|int
    {
        $payload = [];

        // 🎼 RBN Framework: [AUTONOMOUS KEY GENERATION] 🛰️⚓
        if (empty($data['setting_key'])) {
            $source = $data['label_en'] ?? ($data['label_tr'] ?? ($data['setting_name'] ?? null));
            if ($source) {
                $data['setting_key'] = $this->helper('text')->turkishSlug($source);
            }
        }

        $key = $data['setting_key'] ?? null;

        // 🎯 RBN Framework: [RBN Framework MAPPING]
        if (isset($data['value'])) {
            $payload['setting_value'] = $data['value'];
        } else {
            $payload = $data;
        }

        // 🛡️ [RBN Framework JSON GUARD] ⚓
        if (isset($payload['field_options']) && (empty($payload['field_options']) || $payload['field_options'] === '')) {
            $payload['field_options'] = '[]';
        } elseif (!isset($payload['field_options'])) {
            $payload['field_options'] = $payload['field_options'] ?? '[]';
        }

        // [FW-ALTYAPI-3 / H · G4] `SettingsModel` artık `scoped = true`.
        // `where('project_key', active_project_key())` okuma süzgeci ve
        // `$payload['project_key'] = $projectKey` yazma ataması KALDIRILDI:
        // kapsam modelin beyanı, `project_key` sunucu bağlamından yazılır.
        $result = false;

        // 🎯 RBN Framework: [RBN Framework PERSISTENCE]
        if ($key) {
            // 1. Check if a project-specific record already exists
            $exists = $this->SettingsModel->query()
                ->where('setting_key', $key)
                ->first();

            if ($exists) {
                $updatePayload = $payload;
                unset($updatePayload['setting_key']);
                $result = (int) $this->SettingsModel->update((int) ($exists['id'] ?? $exists->id), $updatePayload);
            } else {
                // 2. Create from scratch
                $payload['setting_key'] = $key;
                $result = (bool) $this->SettingsModel->create($payload);
            }
        } else {
            // For NEW records
            $result = (bool) $this->SettingsModel->create($payload);
        }

        if ($result) {
            $this->purgeCache();
        }

        return $result;
    }

    /**
     * Purge settings cache to reflect updates immediately 🧹🚀
     */
    private function purgeCache(): void
    {
        try {
            $this->service('settings')->cache()->deleteByPrefix('settings_group_');
        } catch (\Throwable $e) {
            // Silence cache errors
        }
    }

    /**
     * Strategic Action Engine 🕊️🛰️⚓
     * Handles specialized operations like SEO scan persistence.
     */
    public function actionScan(string $name, array $payload): mixed
    {
        // 🎼 RBN Framework: [RBN Framework SEO PERSISTENCE]
        if ($name === 'save_seo_scan') {
            $this->save(['setting_key' => 'seo-score', 'value' => $payload['score']]);
            $this->save(['setting_key' => 'seo-report', 'value' => json_encode($payload['report'], JSON_UNESCAPED_UNICODE)]);
            $this->save(['setting_key' => 'seo-advice', 'value' => $payload['advice']]);
            $this->save(['setting_key' => 'seo-gemini-advice', 'value' => $payload['gemini']]);

            $this->purgeCache();

            return true;
        }

        return null;
    }
}
