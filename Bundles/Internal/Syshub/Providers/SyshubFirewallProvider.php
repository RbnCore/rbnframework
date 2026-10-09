<?php

declare(strict_types=1);

namespace Rbn\Framework\Bundles\Internal\Syshub\Providers;

use Rbn\Framework\Core\Base\Services\BaseProvider;
use Rbn\Framework\Core\Database\Repositories\Common\ShieldSettingsRepository;

/**
 * SyshubFirewallProvider - Otonom Güvenlik Ayarları Veri Katmanı 🛡️🛰️
 * RBN Framework Standard.
 * @property \Rbn\Framework\Core\Database\Models\Common\CmSysSettingsShieldModel $shieldSettingModel
 */
class SyshubFirewallProvider extends BaseProvider
{
    /**
     * Tüm güvenlik ayarlarını getirir.
     */
    public function getSettings(): array
    {
        $settings = $this->model('common.shieldSetting')?->all() ?? [];
        $formatted = [];

        foreach ($settings as $setting) {
            $formatted[$setting['setting_key']] = $setting['setting_value'];
        }

        return $formatted;
    }

    /**
     * Ayarları Güncelle (Tekli veya Toplu) ⚙️💾
     */
    public function updatesetting($keyOrData, $value = null): bool
    {
        // 🔄 Toplu Güncelleme (Array)
        if (is_array($keyOrData)) {
            $success = true;
            foreach ($keyOrData as $k => $v) {
                if (!$this->performUpdate($k, $v)) $success = false;
            }
            return $success;
        }

        return $this->performUpdate((string)$keyOrData, $value);
    }

    /**
     * Gerçek Güncelleme İşlemi (Internal) 🎯
     */
    protected function performUpdate(string $key, $value): bool
    {
        $model = $this->model('common.shieldSetting');
        if (!$model) return false;
        $exists = $model->where('setting_key', $key)->first();
        $formattedValue = is_array($value) ? json_encode($value) : (string) $value;

        $result = false;
        if ($exists) {
            $result = (bool) $model->where('setting_key', $key)->update(['setting_value' => $formattedValue]);
        } else {
            $result = (bool) $model->create([
                'group_key'     => 'firewall',
                'setting_key'   => $key,
                'setting_value' => $formattedValue,
                'value_type'    => is_array($value) ? 'json' : 'boolean'
            ]);
        }

        if ($result) {
            try {
                $this->cache()->delete(ShieldSettingsRepository::settingsCacheKey());
            } catch (\Throwable $e) {
                // Ignore cache errors
            }
        }

        return $result;
    }

    /**
     * Güvenlik durumunu (Health) hesaplar.
     */
    public function getHealthStatus(): array
    {
        $settings = $this->getSettings();

        return [
            'is_active' => ($settings['firewall_enabled'] ?? '0') === '1',
            'mode' => $settings['firewall_mode'] ?? 'standard',
            'score' => 98 // İleride dinamik bir algoritma bağlanabilir
        ];
    }
}
