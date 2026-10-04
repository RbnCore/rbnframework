<?php
declare(strict_types=1);

namespace Rbn\Framework\Core\Services\Gatekeepers;

use Rbn\Framework\Core\Base\Services\BaseService;
use Rbn\Framework\Core\Support\Contracts\Base\BaseServiceInterface;

/**
 * ShieldSettingsService - Security & Global System Flags Service 🛡️⚙️⚓
 * 
 * @property-read \Rbn\Framework\Core\Database\Repositories\Common\ShieldSettingsRepository $shieldSettingsRepository
 */
class ShieldSettingsService extends BaseService implements BaseServiceInterface
{
    /**
     * Get a specific shield setting by key
     */
    public function getSetting(string $key, $default = null)
    {
        return $this->repository('common.shieldSetting')->getSetting($key, $default);
    }

    /**
     * Get a group of shield settings
     */
    public function getGroup(string $groupKey): array
    {
        return $this->repository('common.shieldSetting')->getGroupSettings($groupKey);
    }

    /**
     * Set/Update a shield setting and clear its cache
     */
    public function set(string $key, $value, string $groupKey = 'general', string $valueType = 'boolean', ?string $projectKey = null): bool
    {
        $result = $this->repository('common.shieldSetting')->saveSetting($key, $value, $groupKey, $valueType, $projectKey);

        if ($result) {
            try {
                $resolvedKey = $projectKey ?: (function_exists('project_key') ? project_key() : '');
                // Target the specific project and delete its settings_shield cache
                $this->cache()->delete(\Rbn\Framework\Core\Database\Repositories\Common\ShieldSettingsRepository::settingsCacheKey($resolvedKey));
            } catch (\Throwable $e) {
                // Ignore cache errors
            }
        }

        return $result;
    }
}
