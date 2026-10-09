<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Database\Repositories\Master;

use Rbn\Framework\Core\Base\Data\BaseRepository;

/**
 * MasterSettingsRepository - Central Authority Data Access & Query Repository 🛰️🏛️⚓
 * 
 * RBN Framework: Enterprise Repository Pattern for Master Settings.
 * Located strictly under Core\Database\Repositories\Master for clean architecture.
 * 
 * @property \Rbn\Framework\Core\Database\Models\Master\MasterSettingsModel $masterSettingsModel
 */
class MasterSettingsRepository extends BaseRepository
{
    /** @var string Target primary model alias */
    protected $targetModel = 'master.settings';

    /**
     * Fetch a specific setting from the master authority 🪐
     */
    public function master(string $key, $default = null): ?string
    {
        $model = $this->model('master.settings');

        $result = $model->query()
            ->where('setting_key', $key)
            ->where('is_active', 1)
            ->first();

        return $result ? ($result->setting_value ?? $default) : $default;
    }

    /**
     * Fetch all settings by category 🧬
     */
    public function getByCategory(string $category): array
    {
        $model = $this->model('master.settings');

        return $model->query()
            ->where('category', $category)
            ->where('is_active', 1)
            ->get()->all();
    }

    /**
     * Ayar anahtarının benzersiz olup olmadığını kontrol eder 🔍
     */
    public function isKeyUnique(string $settingKey, ?int $excludeId = null): bool
    {
        $query = $this->model('master.settings')->where('setting_key', $settingKey);
        
        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }
        
        return $query->first() === null;
    }
}
