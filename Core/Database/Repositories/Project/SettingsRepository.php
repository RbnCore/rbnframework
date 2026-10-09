<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Database\Repositories\Project;

use Rbn\Framework\Core\Base\Data\BaseRepository;

/**
 * SettingsRepository - Project Settings Data Access & Query Repository 🏛️⚙️⚓
 * 
 * RBN Framework: Enterprise Repository Pattern for Project Settings.
 * Located strictly under Core\Database\Repositories\Project for clean architecture.
 * 
 * @property \Rbn\Framework\Core\Database\Models\Project\SettingsModel $settingsModel
 * @property \Rbn\Framework\Core\Database\Models\Project\SettingsGroupModel $settingsGroupModel
 */
class SettingsRepository extends BaseRepository
{
    /** @var string Target primary model alias */
    protected $targetModel = 'project.settings';

    /**
     * Universal Fetcher: Settings vs Groups 🔍🏛️⚓
     */
    public function fetch(array $options): array
    {
        $settingsModel = $this->model('project.settings');

        // 1. Target Detection
        if (($options['target'] ?? '') === 'groups') {
            return $this->fetchGroups($options);
        }

        // 2. Build Settings Query (Fluent & Dynamic) 🧬🛰️
        //
        // [FW-ALTYAPI-3 / H · G4] `SettingsModel` artık `scoped = true`.
        // ÖNCE `where('z_settings.project_key', $projectKey)` elle yazılıyordu
        // ve otomatik `project_key = X` ile ikinci kez çalışıyordu. Elle süzgeç
        // KALDIRILDI; kapsam `withProjectScope()` ile TEK yerden veriliyor.
        // DEĞERİNİ KULLANMA SEMASI DEĞİŞMEDİ: `project_key` seçenekten gelir,
        // yoksa aktif bağlam (veya eski hâliyle 'default') kullanılır.
        $projectKey = (string) ($options['project_key'] ?? (active_project_key() ?: 'default'));
        return $settingsModel->withProjectScope($projectKey)->query()
            ->join('z_setting_groups', 'z_setting_groups.id', '=', 'z_settings.group_id')
            ->when($options['group_key'] ?? null, function ($q, $val) {
                return $q->where('z_setting_groups.group_key', $val);
            })
            ->when($options['group_id'] ?? null, fn($q, $val) => $q->where('z_settings.group_id', $val))
            ->when($options['setting_key'] ?? null, fn($q, $val) => $q->where('z_settings.setting_key', $val))
            ->when($options['id'] ?? null, fn($q, $val) => $q->where('z_settings.id', $val))
            ->when($options['role'] ?? null, fn($q, $val) => $q->where('z_settings.required_role', $val))
            ->when($options['is_active'] ?? null, fn($q, $val) => $q->where('z_settings.is_active', $val))
            ->select('z_settings.*', 'z_setting_groups.group_key')
            ->orderBy('z_settings.order_num', 'ASC')
            ->get()->all();
    }

    /**
     * Group Specific Fetcher 🏺
     */
    private function fetchGroups(array $options): array
    {
        $groupModel = $this->model('project.settingsGroup');

        return $groupModel->query()
            ->when($options['is_active'] ?? null, fn($q, $val) => $q->where('is_active', $val))
            ->when($options['id'] ?? null, fn($q, $val) => $q->where('id', $val))
            ->when($options['group_key'] ?? null, fn($q, $val) => $q->where('group_key', $val))
            ->orderBy('order_num', 'ASC')
            ->get()->all();
    }

    /**
     * Update settings in the database for a specific project ⚙️🛰️
     */
    public function updateSettings(array $settings, string $projectKey): bool
    {
        $settingsModel = $this->model('project.settings');
        
        $keys = array_keys($settings);
        if (empty($keys)) {
            return true;
        }

        // Fetch current records for these keys to resolve unique primary IDs
        // [FW-ALTYAPI-3 / H · G4] Açık anahtar → `withProjectScope()` (kapsam
        // O anahtara daralır); elle `where('project_key', ...)` ikinci süzgeçti.
        $records = $settingsModel->withProjectScope($projectKey)->query()
            ->whereIn('setting_key', $keys)
            ->get()->all();

        $batchData = [];
        foreach ($records as $record) {
            $key = is_object($record) ? ($record->setting_key ?? '') : ($record['setting_key'] ?? '');
            $id = is_object($record) ? ($record->id ?? null) : ($record['id'] ?? null);
            
            if ($id && isset($settings[$key])) {
                $batchData[] = [
                    'id' => $id,
                    'setting_value' => $settings[$key]
                ];
            }
        }

        if (empty($batchData)) {
            return true;
        }

        return $settingsModel->updateBatch($batchData, 'id');
    }

    /**
     * Save (Insert or Update) a setting record 💾
     */
    public function save(array $data): bool|int
    {
        $settingsModel = $this->model('project.settings');
        return $settingsModel->save($data);
    }

    /**
     * Toggle active state of a setting 🔄
     */
    public function toggleStatus(int $id, string $field = 'is_active'): bool
    {
        $settingsModel = $this->model('project.settings');
        $setting = $settingsModel->find($id);

        if (!$setting) {
            return false;
        }

        $isActive = is_array($setting) ? ($setting['is_active'] ?? 0) : ($setting->is_active ?? 0);
        $newStatus = empty($isActive) ? 1 : 0;
        return (bool) $settingsModel->update($id, ['is_active' => $newStatus]);
    }

    /**
     * Destroy a setting 🗑️
     */
    public function destroy(int $id): bool
    {
        $settingsModel = $this->model('project.settings');
        return (bool) $settingsModel->where('id', $id)->delete();
    }
}
