<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnApi\Managers;

use Rbn\Framework\Core\Base\Services\BaseManager;
use Rbn\Framework\Core\Base\Attributes\Component;
use Rbn\Framework\Packages\RbnApi\Models\ApiKeysRegistry;

/**
 * ApiManager - Central Gateway Manager for API Security & Logs 📡🛡️⚓
 * RBN Framework Standard.
 */
#[Component(alias: 'api', type: 'manager')]
class ApiManager extends BaseManager
{
    /**
     * Projenin API anahtarını/anahtarlarını yerel veya merkezi ayarlardan otonom olarak çözer ve döner. 🛡️
     */
    public function resolveApiKey(string $apiName, ?string $projectKey = null, array $context = []): string|array|null
    {
        $apiNameLower = strtolower($apiName);
        $keyName = ApiKeysRegistry::MAP[$apiNameLower] ?? null;
        if (!$keyName) {
            return null; // Tanımlanmamış veya whitelist dışı API
        }

        $projectKey = $projectKey ?: (function_exists('project_key') ? project_key() : null);

        // 1. ÜCRETLİ (PAID) / ÜCRETSİZ (FREE) API TÜRÜ ÇÖZÜMLEMESİ 🛡️
        $keyType = $this->resolveApiKeyType($apiNameLower, $projectKey, $context);

        // Eğer Free Key açıkça talep edilmişse:
        // Önce projenin kendi önbelleğine (api_keys -> GEMINI_API_KEY_FREE vb.) bak!
        if ($keyType === 'free') {
            $apiKeys = $this->resolveProjectData('api_keys', $projectKey) ?: [];
            $freeKeyName = is_string($keyName) ? $keyName . '_FREE' : null;
            if ($freeKeyName && !empty($apiKeys[$freeKeyName])) {
                return $apiKeys[$freeKeyName];
            }

            // Proje önbelleğinde yoksa Master DB'den key_type = 'free' olan ortak anahtarı getir!
            if (is_string($keyName)) {
                $setting = $this->model('masterSettings')
                    ->where('setting_key', $keyName)
                    ->where('key_type', 'free')
                    ->where('is_active', 1)
                    ->first();

                return (!empty($setting['setting_value'])) ? $setting['setting_value'] : null;
            }

            return null;
        }

        // 2. ÜCRETLİ (PAID) ANAHTAR: SIFIR DB SORGUSU! Sadece Project Discovery Cache (project_data) verisinden oku 🚀
        $apiKeys = $this->resolveProjectData('api_keys', $projectKey) ?: [];
        if (is_string($keyName) && !empty($apiKeys[$keyName])) {
            return $apiKeys[$keyName];
        } elseif (is_array($keyName)) {
            $dbResolved = [];
            $hasAnyKey = false;
            foreach ($keyName as $field => $configKey) {
                $val = $apiKeys[$configKey] ?? null;
                $dbResolved[$field] = $val;
                if (!empty($val)) {
                    $hasAnyKey = true;
                }
            }
            if ($hasAnyKey) {
                return $dbResolved;
            }
        }

        return null;
    }

    public function resolveApiKeyType(string $apiName, ?string $projectKey = null, array $context = []): string
    {
        // 🎨 Görsel üretimi Google Gemini'de (Imagen) asla ücretsiz olamaz; görsel çağrılarında her zaman paid anahtar kullanılır 🛡️
        if (!empty($context['is_image']) || ($context['type'] ?? '') === 'image') {
            return 'paid';
        }

        // 🚀 Task Parametre Esnekliği: Yalnızca Task veya Context içinden açıkça 'api' => 'free' geçildiyse free kullanır:
        if (
            ($context['api'] ?? '') === 'free' ||
            ($context['api_key'] ?? '') === 'free' ||
            ($_GET['api'] ?? '') === 'free' ||
            ($_GET['api_key'] ?? '') === 'free'
        ) {
            return 'free';
        }

        // Varsayılan olarak her zaman projenin tanımlı ücretli (paid) API anahtarını kullanır 💎
        return 'paid';
    }

    /**
     * API işlemlerini esnek bağlam (context) yapısıyla merkezi log sistemine yazar.
     * Ham veriler (raw responses/prompts) hiçbir değişikliğe uğramadan doğrudan kaydedilir.
     */
    public function log(
        string $service,
        string $level,
        string $message,
        array $context = [],
        ?string $projectKey = null
    ): void {
        try {
            $workspace = \Rbn\Framework\Core\System\Paths\Paths::workspace();
            $path = $workspace . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . $service . DIRECTORY_SEPARATOR . date('Y-m-d') . '_' . ($projectKey ?? 'unknown') . '.jsonl';

            $logData = array_merge([
                'timestamp' => date('c'),
                'level' => strtoupper($level),
                'message' => $message,
                'project_key' => $projectKey ?? 'unknown',
            ], $context);

            $this->service('storage')->driver()->write($path, $logData, 'json', true);
        } catch (\Throwable $logEx) {
            // Loglama hataları ana akışı bozmasın
        }
    }

    /**
     * Get all whitelisted keys flattened as a key-label dictionary 🗺️
     */
    public function getFlattenedKeys(): array
    {
        $flatKeys = [];
        foreach (ApiKeysRegistry::MAP as $platform => $value) {
            if (is_array($value)) {
                foreach ($value as $subKey => $envKey) {
                    $label = ucfirst($platform) . ' ' . ucwords(str_replace('_', ' ', $subKey));
                    $flatKeys[$envKey] = $label;
                }
            } else {
                $flatKeys[$value] = ucfirst($platform) . ' API Key';
            }
        }
        return $flatKeys;
    }

    /**
     * Get human-readable label for a specific whitelisted key 🏷️
     */
    public function getLabelForKey(string $key): string
    {
        $flat = $this->getFlattenedKeys();
        return $flat[$key] ?? ucwords(str_replace('_', ' ', strtolower($key)));
    }
}
