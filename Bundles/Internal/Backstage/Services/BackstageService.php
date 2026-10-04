<?php

namespace Rbn\Framework\Bundles\Internal\Backstage\Services;

use Rbn\Framework\Core\Base\Services\BaseService;

/**
 * BackstageService - Core Service for Developer Hub 🛡️🛰️⚓
 * 
 * RBN 3.5 Masterpiece Standard.
 * 🎼 Sovereign Service: Model and Provider resources are resolved dynamically.
 */
class BackstageService extends BaseService
{
    /** --- Strategic DNA --- */
    protected $targetModel = 'Settings';

    /**
     * Get and Prepare Email Settings 🛡️🛰️⚓
     */
    public function getEmailSettings(): array
    {
        // 🎼 Fetch settings using the central system SettingsRepository
        $allSettings = $this->repository('project.settings')->fetch([
            'group_key' => 'email',
            'project_key' => active_project_key()
        ]);

        $groups = [
            'smtp' => [],
            'sender' => [],
            'general' => [],
            'enabled' => null,
            'mapped' => []
        ];

        foreach ($allSettings as $s) {
            $groups['mapped'][$s['setting_key']] = $s['setting_value'];
            if ($s['setting_key'] === 'email_enabled') {
                $groups['enabled'] = $s;
                continue;
            }
            if ((int) $s['is_active'] === 1) {
                if (in_array($s['setting_key'], ['smtp_host', 'smtp_port', 'smtp_username', 'smtp_password'])) {
                    $groups['smtp'][] = $s;
                } elseif (in_array($s['setting_key'], ['email_from_name', 'email_from_address'])) {
                    $groups['sender'][] = $s;
                } else {
                    $groups['general'][] = $s;
                }
            }
        }
        return $groups;
    }

    /**
     * Get All Setting Groups 📂🔍
     */
    public function getGroups(): array
    {
        return $this->repository('project.settings')->fetch([
            'target' => 'groups'
        ]);
    }

    /**
     * Get Raw Settings for Management ⚙️🔍
     */
    public function getSettingsRaw(string $groupKey): array
    {
        return $this->repository('project.settings')->fetch([
            'group_key' => $groupKey,
            'project_key' => active_project_key()
        ]);
    }

    /**
     * Get Group with its Settings 📂📊
     */
    public function getGroupWithSettings(int $id): array
    {
        $settingsDb = $this->repository('project.settings');
        $groups = $settingsDb->fetch([
            'target' => 'groups',
            'id' => $id
        ]);
        
        if (empty($groups)) {
            return [];
        }
        $group = $groups[0];

        $settings = $settingsDb->fetch([
            'group_id' => $id
        ]);

        return [
            'group' => $group,
            'settings' => $settings
        ];
    }

    /**
     * Persist Operations 💾🛰️⚓ (Delegated to Crud Hub)
     */
    public function createSetting(array $data)
    {
        return $this->provider('CrudSettingsProvider')->createSetting($data);
    }
    public function updateSetting(int $id, array $data)
    {
        return $this->provider('CrudSettingsProvider')->updateSetting($id, $data);
    }
    public function createGroup(array $data)
    {
        return $this->provider('CrudSettingsProvider')->createGroup($data);
    }
    public function updateGroup(int $id, array $data)
    {
        return $this->provider('CrudSettingsProvider')->updateGroup($id, $data);
    }
    public function bulkUpdateSettings(array $settings)
    {
        $projectKey = active_project_key();
        $result = $this->repository('project.settings')->updateSettings($settings, $projectKey);

        try {
            $cache = $this->cache();
            if ($cache) {
                $cache->forget("settings_group_seo");
                $cache->forget("settings_group_system");
            }
        } catch (\Throwable $e) {
            // Silence cache errors
        }

        return $result;
    }

    /**
     * Delete Operations 🗑️🛰️⚓
     */
    public function remove(string $entity, int $id): bool
    {
        return (bool) $this->provider('CrudSettingsProvider')->delete($entity, $id);
    }
}
