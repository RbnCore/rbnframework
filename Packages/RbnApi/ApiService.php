<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnApi;

use Rbn\Framework\Core\Base\Services\BaseService;
use Rbn\Framework\Packages\RbnApi\Services\YoutubeService;
use Rbn\Framework\Packages\RbnPipeline\Concerns\SanitizesResponseTrait;

/**
 * ApiService - The Unified RBN API Gateway Hub 📡🛰️⚓
 * 
 * RBN Framework: Type-safe, explicit single-point gateway for all API integrations.
 */
class ApiService extends BaseService
{
    use SanitizesResponseTrait;
    /**
     * Gemini Yapay Zeka Servisi 🧠
     */
    public function gemini(string $type, string $task, array $context = []): array
    {
        $projectKey = $context['project_key'] ?? null;

        if ($type === 'image') {
            $prompt = $this->builder('prompt')->reset()->preset('image', $context)->task($task)->compile();
            $prompt = $this->builder('prompt')->sanitize($prompt);
            return $this->service('gemini')->askImage($prompt, null, $projectKey, $context);
        }

        // Determine preset for text generation
        $preset = $type;
        $supportedPresets = ['blog', 'news', 'trends', 'custom', 'youtube'];

        if (!in_array($preset, $supportedPresets, true)) {
            if (str_contains($preset, 'news')) {
                $preset = 'news';
            } elseif (str_contains($preset, 'blog')) {
                $preset = 'blog';
            } elseif (str_contains($preset, 'trend')) {
                $preset = 'trends';
            } else {
                $preset = 'custom';
            }
        }

        try {
            $promptBuilder = $this->builder('prompt')->reset()->preset($preset, $context)->task($task);
        } catch (\Throwable $e) {
            $promptBuilder = $this->builder('prompt')->reset()->preset('custom', $context)->task($task);
        }

        $prompt = $promptBuilder->compile();
        $response = $this->service('gemini')->ask($prompt, null, $projectKey, $context);

        // 🧼 Sanitize and post-process AI response through PromptBuilder rules (SeoMetaRule, InnerLinkingRule, etc.)
        if (is_array($response) && ($response['success'] ?? true) !== false) {
            $rawJson = $this->encodeJson($response);
            $sanitizedJson = $promptBuilder->sanitize($rawJson);
            $sanitizedData = $this->decodeJson($sanitizedJson);
            if (is_array($sanitizedData)) {
                $response = $sanitizedData;
            }
        }

        return $response;
    }

    /**
     * Google Entegrasyon Servisi (Analytics, Maps vb.) 🌐🛰️⚓
     */
    public function google(string $type, array $params = [], ?string $projectKey = null): mixed
    {
        if ($type === 'analytics') {
            $startDate = $params['start_date'] ?? null;
            $endDate = $params['end_date'] ?? null;
            return $this->service('google')->getAnalyticsReport($projectKey, $startDate, $endDate);
        }

        if ($type === 'analytics_map') {
            $startDate = $params['start_date'] ?? null;
            $endDate = $params['end_date'] ?? null;
            return $this->service('google')->getAnalyticsReport($projectKey, $startDate, $endDate, 'map');
        }

        if ($type === 'analytics_active') {
            return $this->service('google')->isAnalyticsActive($projectKey);
        }

        if ($type === 'maps') {
            $placeId = $params['place_id'] ?? '';
            $referer = $params['referer'] ?? '';
            return $this->service('google')->getCompanyGoogleData($placeId, $referer, $projectKey);
        }

        throw new \InvalidArgumentException("Invalid Google request type [{$type}]. Supported types: 'analytics', 'analytics_active', 'maps'. ❌");
    }
}
