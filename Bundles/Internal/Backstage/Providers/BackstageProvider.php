<?php

namespace Rbn\Framework\Bundles\Internal\Backstage\Providers;

use Rbn\Framework\Core\Base\Services\BaseProvider;
use Rbn\Framework\Core\Database\Models\Project\SettingsModel;

/**
 * BackstageProvider - Data orchestration for System Management 🏛️⚓
 * 
 * RBN Framework Standard.
 * 🎼 RBN Framework Data Bridge: Models are resolved lazily via magic discovery.
 * 
 * @property \Rbn\Framework\Core\Database\Models\Project\SettingsModel $SettingsModel
 * @property \Rbn\Framework\Core\Database\Models\Project\SettingsGroupModel $SettingsGroupModel
 */
class BackstageProvider extends BaseProvider
{
    /** --- Strategic DNA --- */
    protected $targetModel = 'Settings';

    /**
     * RBN Framework Model Targeting 🎯🛰️⚓
     * 🎼 RBN Framework: Dynamically switches the provider's context to a different model.
     * Returns $this for fluent chaining.
     */
    public function target(string $modelName): self
    {
        $this->targetModel = $modelName;
        return $this;
    }

    /**
     * Fetch all setting groups ⚙️🏛️
     * 🎼 RBN Framework: Returns all groups ordered by order_num.
     */
    public function getGroups(): array
    {
        return $this->SettingsGroupModel->query()->orderBy('order_num', 'ASC')->get()->toArray();
    }

    /**
     * Find a group by ID ⚙️🔍
     */
    public function findGroup(int $id): ?array
    {
        $group = $this->SettingsGroupModel->query()->where('id', $id)->first();
        return $group ? $group->toArray() : null;
    }

    /**
     * Fetch settings by their group/type identifier ⚙️⚓
     */
    public function getSettingsGroup(string $type, ?string $role = null, bool $onlyActive = true): array
    {
        $query = $this->SettingsModel->query()
            ->select('z_settings.*') // 🎼 RBN Framework: Authoritative selection prevents ID collision.
            ->join('z_setting_groups', 'z_setting_groups.id', '=', 'z_settings.group_id')
            ->where('z_setting_groups.group_key', $type);

        if ($onlyActive) {
            $query->where('z_settings.is_active', 1);
        }

        if ($role) {
            $query->where('z_settings.required_role', $role);
        }

        return $query->orderBy('z_settings.order_num', 'ASC')
            ->get()->toArray();
    }


}
