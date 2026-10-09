<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnApi\Providers;

use Rbn\Framework\Core\Base\BaseComponent;

/**
 * GeminiProvider - Google AI Connectivity Hub 🧠🛰️⚓
 * 
 * RBN Framework: Autonomous Google Gemini AI orchestrator.
 * Powered by the centralized RBN Framework Remote Engine.
 */
class GeminiProvider extends BaseComponent
{
    private string $baseUrl = "https://generativelanguage.googleapis.com/v1beta/";

    /**
     * Gemini API'sine güvenli ve otonom çağrı yapar. 🚀
     */
    public function call(string $model, array $payload, bool $isImage = false, ?string $projectKey = null, array $context = []): array
    {
        $apiKey = null;

        // 🛡️ EXCLUSIVE IDENTITY LOGIC: Otonom Güvenlik ve Key Çözümleyici 🏛️🛰️⚓
        $apiMgr = $this->manager('api');
        $apiKey = $apiMgr ? $apiMgr->resolveApiKey('gemini', $projectKey, $context) : null;

        if (empty($apiKey)) {
            return [
                'status' => 'error',
                'message' => 'Yapay Zeka (Gemini API) kullanım yetkisi bulunmamaktadır veya API anahtarı yapılandırılmamıştır! 🛡️⚓'
            ];
        }
        // 🎼 RBN Framework: Dispatcher - Gemini-Native (Nano Banana) models use generateContent
        $method = (str_contains($model, 'gemini')) ? ":generateContent" : ":predict";
        $endpoint = $this->baseUrl . "models/" . $model . $method . "?key=" . $apiKey;

        $maxRetries = 3;
        $retryCount = 0;
        $result = null;

        while ($retryCount < $maxRetries) {
            // 🪐 Use the RBN Framework Remote Engine!
            $result = $this->remote->post($endpoint, $payload, [], true, [
                'timeout' => 60,
                'connect_timeout' => 10,
                'php_timeout' => 180
            ]);

            // Yoğunluk ve Kota Sınırı Hataları (503 Service Unavailable, 429 Too Many Requests)
            if ($result['http_code'] === 503 || $result['http_code'] === 429) {
                $retryCount++;
                usleep($retryCount * 1500000); // 1.5s, 3.0s, 4.5s gibi kademeli bekler
                continue;
            }
            break;
        }

        if ($result['status'] !== 'success') {
            $errorData = $result['data'] ?? null;
            $msg = $errorData['error']['message'] ?? ($result['message'] ?? 'Bilinmeyen API Hatası');
            return [
                'status' => 'error',
                'message' => 'AI Hatası (HTTP ' . ($result['http_code'] ?? 'ERR') . '): ' . $msg,
                'raw' => $result['raw'] ?? null
            ];
        }

        // 🚀 AUTOMATIC AI USAGE TELEMETRY & INCREMENTAL CACHE 📊⚡
        if (!empty($result['data']['usageMetadata']) || $isImage) {
            try {
                $taskKey = $context['task_key'] ?? null;
                $requestType = $context['request_type'] ?? ($isImage ? 'image' : 'text');
                $usageMetadata = $result['data']['usageMetadata'] ?? [];

                $this->manager('aiUsage')->recordUsage(
                    $model,
                    $usageMetadata,
                    $projectKey,
                    $taskKey,
                    $requestType
                );
            } catch (\Throwable $e) {
                // Telemetry errors do not break primary flow
            }
        }

        return [
            'status' => 'success',
            'data' => $result['data']
        ];
    }
}
