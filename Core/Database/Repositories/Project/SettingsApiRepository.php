<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Database\Repositories\Project;

use Rbn\Framework\Core\Base\Data\BaseRepository;

/**
 * SettingsApiRepository - Repository for project API keys (z_settings_api) 🏛️🔑
 * 
 * RBN Framework: Safely queries z_settings_api if exists, returns empty if table does not exist.
 */
class SettingsApiRepository extends BaseRepository
{
    /** @var string Target primary model alias */
    protected $targetModel = 'project.settingsApi';

    /**
     * [FW-ALTYAPI-3 / H · G4] Kapsam çakışması ÇÖZÜLDÜ ✅
     *
     * ÖNCE (satır 34 ve 82): `whereIn('project_key', [$X,'shared'])` elle
     * yazılıyordu. `SettingsApiModel` zaten `scoped = true` olduğu için
     * otomatik `project_key = X` de ekleniyor ve sonuç
     * `WHERE project_key = X AND project_key IN (X,'shared')` oluyordu —
     * yani `shared` satırları **GÖRÜNMEZ** oluyordu.
     *
     * ÇÖZÜM: elle süzgeç KALDIRILDI. Kapsam artık TEK yerden (model) gelir:
     *   - kapsam verilmediyse → aktif kiracı (bugünkü görünürlük davranışı
     *     BİREBİR korunur; `shared` görünürlüğü patron kararına bırakıldı,
     *     gerekçesi `SettingsApiModel::$projectScopeIncludes` yorumunda).
     *   - kapsam AÇIKÇA verildiyse → `withProjectScope($key)` yalnızca O anahtar.
     *     ÖNCE bu yol sessizce BOŞ dönüyordu (iki koşul birbirini yok ediyordu);
     *     şimdi işe yarıyor ve fail-CLOSED.
     *
     * İMZA DEĞİŞMEDİ (`?string $projectKey = null`), geri uyumlu.
     */
    private function scopeQuery(object $model, ?string $projectKey): object
    {
        if (!empty($projectKey)) {
            return $model->withProjectScope($projectKey);
        }
        return $model; // kapsam zaten modelde açık
    }

    /**
     * Get all active API keys for the project
     */
    public function getApiKeys(?string $projectKey = null): array
    {
        $projectKey = $projectKey ?? (active_project_key() ?: '');

        try {
            $model = $this->model('project.settingsApi');
            if (!$model) {
                return [];
            }

            $query = $this->scopeQuery($model, $projectKey)->query()->where('is_active', 1);

            $rows = $query->get();
            $result = [];
            if ($rows) {
                // Shared olanları önce, projeye özel olanları sonra yaz ki özel olan shared'ı ezebilsin
                usort($rows, function ($a, $b) {
                    $itemA = is_array($a) ? $a : $a->toArray();
                    $itemB = is_array($b) ? $b : $b->toArray();
                    $pA = ($itemA['project_key'] ?? '') === 'shared' ? 0 : 1;
                    $pB = ($itemB['project_key'] ?? '') === 'shared' ? 0 : 1;
                    return $pA <=> $pB;
                });
                foreach ($rows as $row) {
                    $item = is_array($row) ? $row : $row->toArray();
                    $keyName = $item['setting_key'] ?? '';
                    $val = $item['setting_value'] ?? '';
                    if ($keyName) {
                        $result[$keyName] = $val;
                    }
                }
            }
            return $result;
        } catch (\Throwable $e) {
            // Tablo projede henüz oluşturulmamışsa sessizce boş dizi döner
            return [];
        }
    }

    /**
     * Get a specific API key
     */
    public function getApiKey(string $keyName, $default = null, ?string $projectKey = null): ?string
    {
        $projectKey = $projectKey ?? (active_project_key() ?: '');

        try {
            $model = $this->model('project.settingsApi');
            if (!$model) {
                return $default;
            }

            $query = $this->scopeQuery($model, $projectKey)
                ->query()
                ->where('setting_key', $keyName)
                ->where('is_active', 1);

            $rows = $query->get();
            if (empty($rows)) {
                return $default;
            }

            // Projeye özel olan varsa onu al, yoksa shared olanı al
            $found = null;
            foreach ($rows as $row) {
                $item = is_array($row) ? $row : $row->toArray();
                if (($item['project_key'] ?? '') === $projectKey) {
                    return $item['setting_value'] ?? $default;
                }
                $found = $item['setting_value'] ?? $default;
            }

            return $found ?? $default;
        } catch (\Throwable $e) {
            return $default;
        }
    }

    /**
     * Save an API key
     */
    public function saveApiKey(string $keyName, string $keyValue, string $type = 'api', ?string $settingLabel = null, ?string $groupKey = null): bool
    {
        try {
            $model = $this->model('project.settingsApi');
            if (!$model) {
                return false;
            }

            $existing = $model->query()->where('setting_key', $keyName)->first();

            if ($existing) {
                $id = is_array($existing) ? $existing['id'] : $existing->id;
                return (bool) $model->query()->where('id', $id)->update([
                    'setting_value' => $keyValue,
                    'setting_type'  => $type,
                    'setting_label' => $settingLabel ?? $keyName,
                    'is_active'     => 1,
                ]);
            }

            return (bool) $model->create([
                'group_key'     => $groupKey,
                'setting_type'  => $type,
                'setting_label' => $settingLabel ?? $keyName,
                'setting_key'   => $keyName,
                'setting_value' => $keyValue,
                'is_active'     => 1,
            ]);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
