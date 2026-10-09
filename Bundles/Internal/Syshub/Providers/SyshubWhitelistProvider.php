<?php

declare(strict_types=1);

namespace Rbn\Framework\Bundles\Internal\Syshub\Providers;

use Rbn\Framework\Core\Base\Services\BaseProvider;

/**
 * SyshubWhitelistProvider - Otonom Güvenli Liste Veri Katmanı ✅🛡️
 * RBN Framework Standard.
 * 
 * Bu provider Master tablodan OKUMA yapar ve Shield Ayarlarındaki IP'leri harmanlar.
 * @property \Rbn\Framework\Core\Database\Models\Master\MasterIpWhitelistModel $masterIpWhitelistModel
 * @property \Rbn\Framework\Core\Database\Models\Common\CmSysSettingsShieldModel $shieldSettingModel
 */
class SyshubWhitelistProvider extends BaseProvider
{
    /**
     * Güvenli listeyi (Master + Settings) harmanlayarak getirir. 🎼
     */
    public function getList(): array
    {
        // 1. Master Tablodan Gelenler (Global Whitelist)
        $masterModel = $this->model('master.ipWhitelist');
        $masterList = $masterModel ? ($masterModel->orderBy('id', 'DESC')->get()->toArray() ?? []) : [];

        foreach ($masterList as &$item) {
            $item['source'] = 'Global (Master)';
        }

        // 2. Proje Ayarlarından Gelenler (Shield Settings)
        $settingsIps = $this->getIpsFromSettings();

        return array_merge($masterList, $settingsIps);
    }

    /**
     * Proje ayarlarına (Shield Settings) yeni IP ekler. 🛰️
     */
    public function add(string $ip, string $label): bool
    {
        $settingsModel = $this->model('common.shieldSetting');
        $settings = $settingsModel ? $settingsModel->where('setting_key', 'maintenance_ips')->first() : null;

        $currentIps = $settings ? $settings['setting_value'] : '';
        $ipList = !empty($currentIps) ? explode(',', $currentIps) : [];

        // Zaten varsa ekleme
        if (in_array($ip, $ipList)) {
            return true;
        }

        $ipList[] = $ip;
        $newValue = implode(',', $ipList);

        if ($settings) {
            return (bool) $this->model('common.shieldSetting')
                ->where('setting_key', 'maintenance_ips')
                ->update(['setting_value' => $newValue]);
        }

        return (bool) $this->model('common.shieldSetting')->create([
            'setting_key' => 'maintenance_ips',
            'setting_value' => $newValue
        ]);
    }

    /**
     * Proje ayarlarından IP siler.
     */
    public function remove(string $ip): bool
    {
        $settings = $this->model('common.shieldSetting')
            ->where('setting_key', 'maintenance_ips')
            ->first();

        if (!$settings || empty($settings['setting_value'])) {
            return false;
        }

        $ipList = explode(',', $settings['setting_value']);
        $newIpList = array_filter($ipList, fn($item) => trim($item) !== trim($ip));

        $settingsModel = $this->model('common.shieldSetting');
        return $settingsModel ? (bool) $settingsModel
            ->where('setting_key', 'maintenance_ips')
            ->update(['setting_value' => implode(',', $newIpList)]) : false;
    }

    /**
     * Shield ayarlarındaki virgülle ayrılmış IP'leri diziye çevirir.
     */
    protected function getIpsFromSettings(): array
    {
        $settingsModel = $this->model('common.shieldSetting');
        $settings = $settingsModel ? $settingsModel
            ->where('setting_key', 'maintenance_ips')
            ->first() : null;

        if (!$settings || empty($settings['setting_value'])) {
            return [];
        }

        $ips = explode(',', $settings['setting_value']);
        $formatted = [];

        foreach ($ips as $index => $ip) {
            $ip = trim($ip);
            if (empty($ip))
                continue;

            $formatted[] = [
                'id' => 's_' . $index, // Sanal ID
                'ip_address' => $ip,
                'label' => 'Bakım Modu İzni (Settings)',
                'source' => 'Project Settings',
                'created_at' => null
            ];
        }

        return $formatted;
    }
}
