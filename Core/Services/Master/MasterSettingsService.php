<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Services\Master;

use Rbn\Framework\Core\Base\Services\BaseService;

/**
 * MasterSettingsService - The Global Configuration Authority 🏛️🛰️⚓
 * 
 * RBN 3.5 Masterpiece: Centralized service for managing framework-wide 
 * configurations and API keys stored in the rbn_master database.
 * 
 * @property \Rbn\Framework\Core\Database\Repositories\Master\MasterSettingsRepository $masterSettingsRepository
 */
class MasterSettingsService extends BaseService
{
    /**
     * Get a specific setting value with caching 🚀
     */
    public function master(string $key, $default = null): ?string
    {
        return $this->cache()->remember("master_setting_{$key}", function () use ($key, $default) {
            return $this->masterSettingsRepository->master($key, $default);
        });
    }

    /**
     * Get all settings for a specific category 🧬
     */
    public function category(string $category): array
    {
        return $this->cache()->remember("master_settings_cat_{$category}", function () use ($category) {
            return $this->masterSettingsRepository->getByCategory($category);
        });
    }

    /**
     * Force refresh the cache for a setting 🧹
     */
    public function refresh(string $key): void
    {
        $this->cache()->forget("master_setting_{$key}");
    }

    /**
     * Yeni ayar oluşturur 🚀
     */
    public function createSetting(array $data): array
    {
        // 1. Tür (Type) Doğrulaması
        $allowedTypes = array_keys(\Rbn\Framework\Core\Services\Master\Data\MasterSettingsConfig::TYPES);
        if (!in_array($data['type'], $allowedTypes, true)) {
            return [
                'success' => false,
                'errors' => ['Geçersiz ayar türü seçildi.']
            ];
        }

        // 2. Kategori (Category) Doğrulaması
        $category = $data['category'] ?: 'general';
        $allowedCategories = array_keys(\Rbn\Framework\Core\Services\Master\Data\MasterSettingsConfig::CATEGORIES);
        if (!in_array($category, $allowedCategories, true)) {
            return [
                'success' => false,
                'errors' => ['Geçersiz ayar kategorisi seçildi.']
            ];
        }

        // 3. Benzersizlik kontrolü
        if (!$this->masterSettingsRepository->isKeyUnique($data['setting_key'])) {
            return [
                'success' => false,
                'errors' => ['Ayar anahtarı zaten kullanımda.']
            ];
        }

        $res = $this->masterSettingsRepository->create([
            'type'          => $data['type'],
            'category'      => $category,
            'setting_name'  => $data['setting_name'],
            'setting_key'   => $data['setting_key'],
            'setting_value' => $data['setting_value'] ?? null,
            'description'   => $data['description'] ?: null,
            'is_active'     => isset($data['is_active']) ? (int)$data['is_active'] : 1,
        ]);

        return [
            'success' => (bool)$res,
            'errors' => $res ? [] : ['Ayar oluşturulamadı.']
        ];
    }

    /**
     * Ayar günceller 📝
     */
    public function updateSetting(int $id, array $data): array
    {
        // 1. Tür (Type) Doğrulaması
        $allowedTypes = array_keys(\Rbn\Framework\Core\Services\Master\Data\MasterSettingsConfig::TYPES);
        if (!in_array($data['type'], $allowedTypes, true)) {
            return [
                'success' => false,
                'errors' => ['Geçersiz ayar türü seçildi.']
            ];
        }

        // 2. Kategori (Category) Doğrulaması
        $category = $data['category'] ?: 'general';
        $allowedCategories = array_keys(\Rbn\Framework\Core\Services\Master\Data\MasterSettingsConfig::CATEGORIES);
        if (!in_array($category, $allowedCategories, true)) {
            return [
                'success' => false,
                'errors' => ['Geçersiz ayar kategorisi seçildi.']
            ];
        }

        // 3. Benzersizlik kontrolü
        if (!$this->masterSettingsRepository->isKeyUnique($data['setting_key'], $id)) {
            return [
                'success' => false,
                'errors' => ['Ayar anahtarı zaten kullanımda.']
            ];
        }

        $res = $this->masterSettingsRepository->update($id, [
            'type'          => $data['type'],
            'category'      => $category,
            'setting_name'  => $data['setting_name'],
            'setting_key'   => $data['setting_key'],
            'setting_value' => $data['setting_value'] ?? null,
            'description'   => $data['description'] ?: null,
            'is_active'     => isset($data['is_active']) ? (int)$data['is_active'] : 1,
        ]);

        return [
            'success' => $res,
            'errors' => $res ? [] : ['Güncelleme başarısız oldu.']
        ];
    }
}
