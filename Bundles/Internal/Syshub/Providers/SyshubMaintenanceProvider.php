<?php

declare(strict_types=1);

namespace Rbn\Framework\Bundles\Internal\Syshub\Providers;

use Rbn\Framework\Core\Base\BaseComponent;
use Rbn\Framework\Core\Database\Models\Master\MasterIpWhitelistModel;
use Rbn\Framework\Core\Database\Repositories\Common\ShieldSettingsRepository;

/**
 * SyshubMaintenanceProvider - Bakım Modu ve IP Yönetimi Veri Sağlayıcısı 🚧🛡️⚓
 * RBN Framework Standard.
 */
class SyshubMaintenanceProvider extends BaseComponent
{
    /**
     * Master veritabanından izinli IP adreslerini getirir.
     */
    public function getWhitelistedIps(): array
    {
        $records = $this->model('master.ipWhitelist')?->all() ?? [];
        return is_array($records) ? array_column($records, 'ip_address') : [];
    }

    /**
     * Bakım modunun aktif olup olmadığını kontrol eder.
     *
     * [G-03] Aynı değer iki zıt karara dönüşüyordu: zorlayıcı katman
     * (`SystemGuardHandler`) anahtarı `ShieldSettingsRepository::normalizeSwitch()`
     * ile yorumluyor, bu panel ise `=== 'on'` ile. `maintenance_mode='1'`
     * yazan operatör siteyi AÇIK görmesine rağmen zorlayıcı katman ENGELLİYORDU.
     * Artık ikisi de TEK doğruluk kaynağından (`ShieldSettingsRepository`) okur;
     * varsayılan da zorlayıcı katmanla aynıdır: anahtar yoksa KAPALI
     * (aksine yorum 18 siteyi kilitlerdi).
     */
    public function isMaintenance(): bool
    {
        /** @var ShieldSettingsRepository $settings */
        $settings = $this->repository('common.shieldSetting');

        return ShieldSettingsRepository::normalizeSwitch(
            $settings->getSetting(ShieldSettingsRepository::KEY_MAINTENANCE_MODE, '0'),
            false
        );
    }

    /**
     * Aktif bakım modu mesajını getirir.
     */
    public function getMaintenanceMessage(): string
    {
        /** @var ShieldSettingsRepository $settings */
        $settings = $this->repository('common.shieldSetting');

        return (string) $settings->getSetting(
            ShieldSettingsRepository::KEY_MAINTENANCE_MESSAGE,
            'Sistem şu anda bakımda. Lütfen daha sonra tekrar deneyin.'
        );
    }



    /**
     * Ayarları Kaydet (Otonom Standart) 💾
     */
    public function save(array $data): bool
    {
        $result = true;

        /** @var ShieldSettingsRepository $settings */
        $settings = $this->repository('common.shieldSetting');

        // Mesaj Güncelleme
        // [G-03 / Anayasa §8] `cm_sys_settings_shield` tablosuna dokunan TEK sınıf
        // Repository'dir; Provider `model()` ile yazmaz. `saveSetting()` zaten vardı,
        // yeni bir yazma metodu EKLENMEDİ.
        if (isset($data['maintenance_message'])) {
            $update = $settings->saveSetting(
                ShieldSettingsRepository::KEY_MAINTENANCE_MESSAGE,
                (string) $data['maintenance_message'],
                'maintenance',
                'text'
            );
            $result = $result && $update;
        }

        // `getSetting()` `settings_shield` cache'ini kullanır; yazma sonrasi
        // düşürülmezse panel ve zorlayıcı katman ESKİ değeri okumaya devam eder.
        if ($result && isset($data['maintenance_message'])) {
            try {
                $this->cache()->delete(ShieldSettingsRepository::settingsCacheKey());
            } catch (\Throwable $e) {
                // Cache düşürülemezse ayar bir sonraki istekte eski görünür;
                // bu bir güvenlik açığı değil, yalnızca gecikmedir.
            }
        }

        // IP Ekleme (Eğer formda yeni bir IP geldiyse)
        if (!empty($data['ip_address'])) {
            // Master listeye ekleme (Read-Only değil, burası yetkili alan)
            // [G-02] `model('masterIpWhitelist')` registry'de KAYITLI DEGILDI ->
            // cozumleyici NULL donerdi ve bakim moduna VIP IP ekleme ekrani
            // sessizce calismiyordu. Tek-merkez ilkesi: alias'i burada uydurmak
            // yerine kayitli/FQCN cagri kullanilir (IpGuardHandler ile ayni desen).
            /** @var MasterIpWhitelistModel $whitelistModel */
            $whitelistModel = $this->model(MasterIpWhitelistModel::class);
            $exists = $whitelistModel
                ->where('ip_address', $data['ip_address'])
                ->first();

            if (!$exists) {
                $create = $whitelistModel->create([
                    'ip_address' => $data['ip_address'],
                    'label' => $data['label'] ?? 'Maintenance Access'
                ]);
                $result = $result && (bool)$create;
            }
        }

        return $result;
    }
}
