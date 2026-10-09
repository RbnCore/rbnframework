<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnApi\Services;

use Rbn\Framework\Core\Base\Services\BaseService;
use Rbn\Framework\Packages\RbnPipeline\Concerns\SanitizesResponseTrait;

/**
 * GeminiService - RBN Framework AI Orchestrator 🧠🛰️⚓
 * 
 * RBN Framework: High-level interface for Google Gemini AI operations.
 * @property \Rbn\Framework\Packages\RbnApi\Providers\GeminiProvider $gemini
 */
class GeminiService extends BaseService
{
    use SanitizesResponseTrait;
    /** @var string Varsayılan Metin Modeli (Google Gemini 3.5 Flash Lite - Hızlı & Yüksek Tasarruflu) ⚡ */
    public string $textModel = "gemini-3.5-flash-lite";

    /** @var string Hızlı ve hafif metin modeli aliası 🚀 */
    public string $flashModel = "gemini-3.5-flash-lite";

    /** @var string Yüksek zekaya sahip metin modeli aliası 🏛️ */
    public string $proModel = "gemini-2.5-pro";

    /** @var string Yüksek Kotayla Ücretsiz Hafif Metin Modeli (Google Gemini 3.5 Flash Lite) ⚡🆓 */
    public string $flashLiteModel = "gemini-3.5-flash-lite";

    /** @var string Varsayılan Görsel Modeli (Google Nano Banana 2) 🎨 */
    public string $imageModel = "gemini-3.1-flash-image";

    /** @var string Hızlı Görsel Modeli (Google Nano Banana 2 Lite) ⚡🎨 */
    public string $flashImageModel = "gemini-3.1-flash-lite-image";

    /** @var string Özel/Farklı İşler İçin Model 🧪 */
    public string $customModel = "";

    /**
     * Gemini API'sine çağrı yapar ve içeriği soyup döner. 🚀
     */
    public function call(string $model, array $payload, bool $isImage = false, ?string $projectKey = null, array $context = []): array
    {
        // 🛡️ TEK GERÇEKLİK KAYNAĞI: ApiManager'a sor (Free Key mi kullanılıyor?)
        $context['is_image'] = $isImage;
        $apiMgr = $this->manager('api');
        $keyType = $apiMgr ? $apiMgr->resolveApiKeyType('gemini', $projectKey, $context) : 'paid';

        // Eğer Free Key kullanılıyorsa VE görsel üretimi değilse:
        // Otonom olarak 500 günlük yüksek kotalı flashLiteModel (gemini-3.1-flash-lite) kullan!
        if ($keyType === 'free' && !$isImage) {
            $model = $this->flashLiteModel;
        }

        $result = $this->provider('apiGemini')->call($model, $payload, $isImage, $projectKey, $context);

        if ($result['status'] === 'success') {
            if (isset($result['data']['generatedImages'][0]['image']['imageBytes'])) {
                $result['data'] = $result['data']['generatedImages'][0]['image']['imageBytes'];
            } elseif (isset($result['data']['predictions'][0]['bytesBase64Encoded'])) {
                $result['data'] = $result['data']['predictions'][0]['bytesBase64Encoded'];
            } else {
                $parts = $result['data']['candidates'][0]['content']['parts'] ?? [];

                // 🧼 Stripping Logic: Extract Text or Image Data
                foreach ($parts as $part) {
                    if (isset($part['text'])) {
                        $result['data'] = $part['text'];
                        break;
                    }
                    if (isset($part['inlineData']['data'])) {
                        $extractedData = $part['inlineData']['data'];
                        $result['data'] = is_array($extractedData) ? ($extractedData[0] ?? '') : $extractedData;
                        break;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Basit bir prompt ile hızlı ve temiz yanıt alır.
     * Eğer dönen veri bir JSON sarmalıysa (veya JSON olabilecek yapıdaysa) otomatik olarak parse edip dizi döner. 🧠
     */
    public function ask(string $prompt, ?string $model = null, ?string $projectKey = null, array $context = []): array
    {
        try {
            $model = $model ?: $this->textModel;
            $payload = [
                "contents" => [["parts" => [["text" => $prompt]]]]
            ];

            $isJsonRequested = false;
            // 🧱 Native JSON Mode Shield: Force Gemini to output strictly valid JSON if JSON is requested
            if (stripos($prompt, '[FORMATTING RULE]') !== false || stripos($prompt, 'json') !== false) {
                $payload['generationConfig'] = [
                    'responseMimeType' => 'application/json'
                ];
                $isJsonRequested = true;
            }

            $response = $this->call($model, $payload, false, $projectKey, $context);

            // 🔄 YEDEK MODEL / FALLBACK MEKANİZMASI
            if ($response['status'] !== 'success') {
                $fallbacks = [
                    $this->flashLiteModel,
                    $this->textModel,
                    $this->flashModel
                ];

                // Zaten denenmiş olan ana modeli listeden çıkarıyoruz
                $fallbacks = array_values(array_unique(array_filter($fallbacks, fn($m) => $m !== $model)));

                foreach ($fallbacks as $fallbackModel) {
                    $response = $this->call($fallbackModel, $payload, false, $projectKey, $context);
                    if ($response['status'] === 'success') {
                        break; // Başarılı olunca döngüden çık
                    }
                }
            }

            if ($response['status'] !== 'success') {
                return [
                    'success' => false,
                    'message' => $response['message'] ?? 'Gemini API call failed.'
                ];
            }

            $rawText = trim($response['data'] ?? '');

            $decoded = $this->cleanJsonWrapper($rawText);

            if ($decoded !== null) {
                return $decoded;
            }

            // Eğer JSON talep edilmiş ve parse edilememişse hata dön
            if ($isJsonRequested) {
                $this->manager('api')->log('gemini', 'ERROR', 'Gemini API JSON yanıtı doğrulanamadı veya parse edilemedi.', [
                    'type' => 'json_parse_failure',
                    'model' => $model,
                    'prompt' => $prompt,
                    'raw_response' => $rawText
                ], $projectKey);

                return [
                    'success' => false,
                    'message' => 'Gemini API JSON yanıtı doğrulanamadı veya parse edilemedi.'
                ];
            }

            // Normal metin yanıtı ise doğrudan başarı durumuyla beraber metni dön
            return [
                'success' => true,
                'status' => 'success',
                'data' => $rawText
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Gemini Imagen modelini kullanarak bir görsel üretir ve base64 string olarak döner. 🎨🧠
     */
    public function askImage(string $prompt, ?string $model = null, ?string $projectKey = null, array $context = []): array
    {
        try {
            $model = $model ?: $this->imageModel;
            $isImagen = str_contains($model, 'imagen');

            if ($isImagen) {
                $payload = [
                    "instances" => [
                        ["prompt" => $prompt]
                    ],
                    "parameters" => [
                        "sampleCount" => 1,
                        "aspectRatio" => "16:9",
                        "outputMimeType" => "image/jpeg"
                    ]
                ];
            } else {
                $payload = [
                    "contents" => [
                        [
                            "parts" => [
                                ["text" => $prompt]
                            ]
                        ]
                    ],
                    "generationConfig" => [
                        "response_modalities" => ["IMAGE"],
                        "temperature" => 0.7
                    ],
                    "safetySettings" => [
                        ["category" => "HARM_CATEGORY_HATE_SPEECH", "threshold" => "BLOCK_NONE"],
                        ["category" => "HARM_CATEGORY_HARASSMENT", "threshold" => "BLOCK_NONE"],
                        ["category" => "HARM_CATEGORY_SEXUALLY_EXPLICIT", "threshold" => "BLOCK_NONE"],
                        ["category" => "HARM_CATEGORY_DANGEROUS_CONTENT", "threshold" => "BLOCK_NONE"]
                    ]
                ];
            }

            $res = $this->call($model, $payload, true, $projectKey, $context);

            if ($res['status'] !== 'success') {
                $msg = $res['message'] ?? 'Resim üretilemedi.';
                $this->manager('api')->log('gemini', 'ERROR', $msg, [
                    'type' => 'image_api_error',
                    'model' => $model,
                    'prompt' => $prompt,
                    'raw_response' => $res['raw'] ?? $res
                ], $projectKey);
                return [
                    'success' => false,
                    'message' => $msg
                ];
            }

            $rawResponse = $res['data'] ?? [];

            if (!$isImagen) {
                // 🛡️ Check if the prompt itself was blocked by safety filters
                $blockReason = $rawResponse['promptFeedback']['blockReason'] ?? null;
                if ($blockReason) {
                    $details = '';
                    if (!empty($rawResponse['promptFeedback']['safetyRatings'])) {
                        $triggered = [];
                        foreach ($rawResponse['promptFeedback']['safetyRatings'] as $rating) {
                            $prob = $rating['probability'] ?? 'NEGLIGIBLE';
                            if ($prob !== 'NEGLIGIBLE') {
                                $triggered[] = "{$rating['category']}: {$prob}";
                            }
                        }
                        if (!empty($triggered)) {
                            $details = ' [Tetiklenen: ' . implode(', ', $triggered) . ']';
                        }
                    }
                    $msg = 'RBN API Hatası: Görsel talebi güvenlik filtresi (Prompt Block) tarafından engellendi. Gerekçe: ' . $blockReason . $details;
                    $this->manager('api')->log('gemini', 'ERROR', $msg, [
                        'type' => 'image_safety_blocked',
                        'model' => $model,
                        'prompt' => $prompt,
                        'raw_response' => $rawResponse
                    ], $projectKey);
                    return [
                        'success' => false,
                        'message' => $msg
                    ];
                }

                // 🛡️ Check if the generation finished due to safety/content blocks
                $finishReason = $rawResponse['candidates'][0]['finishReason'] ?? null;
                if ($finishReason && !in_array($finishReason, ['STOP', 'MAX_TOKENS'])) {
                    $details = '';
                    if (!empty($rawResponse['candidates'][0]['safetyRatings'])) {
                        $triggered = [];
                        foreach ($rawResponse['candidates'][0]['safetyRatings'] as $rating) {
                            $prob = $rating['probability'] ?? 'NEGLIGIBLE';
                            if ($prob !== 'NEGLIGIBLE') {
                                $triggered[] = "{$rating['category']}: {$prob}";
                            }
                        }
                        if (!empty($triggered)) {
                            $details = ' [Tetiklenen: ' . implode(', ', $triggered) . ']';
                        }
                    }
                    $msg = 'RBN API Hatası: Görsel üretimi yarıda kesildi. Gerekçe: ' . $finishReason . $details;
                    $this->manager('api')->log('gemini', 'ERROR', $msg, [
                        'type' => 'image_generation_interrupted',
                        'model' => $model,
                        'prompt' => $prompt,
                        'raw_response' => $rawResponse
                    ], $projectKey);
                    return [
                        'success' => false,
                        'message' => $msg
                    ];
                }
            }

            $base64Data = $rawResponse;

            // 🛡️ Robust Base64 Extraction (Handle array/object structures from legacy predict endpoints)
            if (is_array($base64Data)) {
                if (isset($base64Data['inlineData']['data'])) {
                    $base64Data = $base64Data['inlineData']['data'];
                } elseif (isset($base64Data['generatedImages'][0]['image']['imageBytes'])) {
                    $base64Data = $base64Data['generatedImages'][0]['image']['imageBytes'];
                } elseif (isset($base64Data['predictions'][0]['bytesBase64Encoded'])) {
                    $base64Data = $base64Data['predictions'][0]['bytesBase64Encoded'];
                } elseif (isset($base64Data[0]) && is_string($base64Data[0])) {
                    $base64Data = $base64Data[0];
                }
            }

            if (!is_string($base64Data) || empty($base64Data)) {
                $sample = is_array($rawResponse) ? substr(json_encode($rawResponse, JSON_UNESCAPED_UNICODE), 0, 400) : gettype($rawResponse);
                $msg = 'RBN API Hatası: Görsel verisi (base64) çözümlenemedi veya boş döndü. Ham API Yanıtı: ' . $sample;
                $this->manager('api')->log('gemini', 'ERROR', $msg, [
                    'type' => 'image_base64_empty',
                    'model' => $model,
                    'prompt' => $prompt,
                    'raw_response' => $rawResponse
                ], $projectKey);
                return [
                    'success' => false,
                    'message' => $msg
                ];
            }

            return [
                'success' => true,
                'base64' => $base64Data,
                'prompt' => $prompt
            ];
        } catch (\Throwable $e) {
            $this->manager('api')->log('gemini', 'ERROR', $e->getMessage(), [
                'type' => 'image_exception',
                'model' => $model,
                'prompt' => $prompt,
                'raw_response' => ''
            ], $projectKey);
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}
