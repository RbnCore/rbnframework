<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Services\System;

use Rbn\Framework\Core\Base\Services\BaseService;
use Rbn\Framework\Core\Support\Contracts\Base\BaseServiceInterface;

/**
 * SettingsApiService - Global API Keys Configuration Service ⚙️🔑🛰️⚓
 * 
 * @property \Rbn\Framework\Core\Database\Repositories\Project\SettingsApiRepository $settingsApiRepository
 */
class SettingsApiService extends BaseService implements BaseServiceInterface
{
    /** --- Infrastructure DNA --- */
    protected $targetModel = 'project.settingsApi';

    /**
     * Get all API options
     */
    public function getOptions(): array
    {
        return $this->repository('project.settingsApi')->getApiKeys();
    }

    /**
     * Save an API key
     */
    public function saveOption(array $data): bool
    {
        $keyName  = $data['setting_key'] ?? '';
        $keyValue = $data['setting_value'] ?? '';
        $type     = $data['setting_type'] ?? 'api';
        $label    = $data['setting_label'] ?? $keyName;
        $group    = $data['group_key'] ?? null;

        return $this->repository('project.settingsApi')->saveApiKey($keyName, $keyValue, $type, $label, $group);
    }

    /**
     * Get bot activity status
     */
    public function getBotActivityStatus(): bool
    {
        return (int) $this->service('shieldSettings')->getSetting('bot_activity', 0) === 1;
    }

    /**
     * Toggle bot activity status
     */
    public function setBotActivityStatus(bool $status): bool
    {
        $value = $status ? '1' : '0';
        return $this->service('shieldSettings')->set('bot_activity', $value, 'general', 'boolean');
    }
}
