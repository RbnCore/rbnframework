<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnApi\Models;

/**
 * AiData - Sovereign AI Models & Pricing Knowledge Hub 🤖⚡
 * Central dictionary for Gemini models, pricing, capabilities and specs.
 * RBN 3.5 Masterpiece Standard.
 */
class AiData
{
    /**
     * Google Gemini Modellerinin Genel Katalog ve Özellik Haritası 🗺️
     */
    public const MODELS = [
        // --- GOOGLE GEMINI FLASH (STABLE & FAST) ---
        'gemini-3.5-flash' => [
            'provider'     => 'google',
            'display_name' => 'Gemini 3.5 Flash',
            'type'         => 'text',
            'pricing'      => ['input' => 0.0750, 'output' => 0.3000],
            'capabilities' => ['text', 'vision', 'json_mode', 'function_calling']
        ],
        'gemini-2.5-flash' => [
            'provider'     => 'google',
            'display_name' => 'Gemini 2.5 Flash',
            'type'         => 'text',
            'pricing'      => ['input' => 0.0750, 'output' => 0.3000],
            'capabilities' => ['text', 'vision', 'json_mode']
        ],
        'gemini-3-flash-preview' => [
            'provider'     => 'google',
            'display_name' => 'Gemini 3 Flash (Preview)',
            'type'         => 'text',
            'pricing'      => ['input' => 0.0750, 'output' => 0.3000],
            'capabilities' => ['text', 'vision', 'json_mode']
        ],

        // --- GOOGLE GEMINI FLASH-LITE (HIGH SPEED & FREE QUOTA) ---
        'gemini-3.5-flash-lite' => [
            'provider'     => 'google',
            'display_name' => 'Gemini 3.5 Flash Lite',
            'type'         => 'text',
            'pricing'      => ['input' => 0.0375, 'output' => 0.1500],
            'capabilities' => ['text', 'json_mode']
        ],
        'gemini-3.1-flash-lite' => [
            'provider'     => 'google',
            'display_name' => 'Gemini 3.1 Flash Lite',
            'type'         => 'text',
            'pricing'      => ['input' => 0.0375, 'output' => 0.1500],
            'capabilities' => ['text', 'json_mode']
        ],
        'gemini-2.5-flash-lite' => [
            'provider'     => 'google',
            'display_name' => 'Gemini 2.5 Flash Lite',
            'type'         => 'text',
            'pricing'      => ['input' => 0.0375, 'output' => 0.1500],
            'capabilities' => ['text', 'json_mode']
        ],

        // --- GOOGLE GEMINI PRO (DEEP REASONING & CODING) ---
        'gemini-3.1-pro-preview' => [
            'provider'     => 'google',
            'display_name' => 'Gemini 3.1 Pro (Preview)',
            'type'         => 'text',
            'pricing'      => ['input' => 1.2500, 'output' => 5.0000],
            'capabilities' => ['text', 'vision', 'reasoning', 'json_mode']
        ],
        'gemini-2.5-pro' => [
            'provider'     => 'google',
            'display_name' => 'Gemini 2.5 Pro',
            'type'         => 'text',
            'pricing'      => ['input' => 1.2500, 'output' => 5.0000],
            'capabilities' => ['text', 'vision', 'reasoning', 'json_mode']
        ],

        // --- GOOGLE GEMINI NANO BANANA (IMAGE GENERATION) ---
        'gemini-3.1-flash-image' => [
            'provider'     => 'google',
            'display_name' => 'Nano Banana 2 (Gemini 3.1 Flash Image)',
            'type'         => 'image',
            'pricing'      => ['per_image' => 0.0300],
            'capabilities' => ['image_generation']
        ],
        'gemini-3.1-flash-lite-image' => [
            'provider'     => 'google',
            'display_name' => 'Nano Banana 2 Lite (Gemini 3.1 Flash Lite Image)',
            'type'         => 'image',
            'pricing'      => ['per_image' => 0.0150],
            'capabilities' => ['image_generation']
        ],
        'gemini-3-pro-image' => [
            'provider'     => 'google',
            'display_name' => 'Nano Banana Pro (Gemini 3 Pro Image)',
            'type'         => 'image',
            'pricing'      => ['per_image' => 0.0500],
            'capabilities' => ['image_generation']
        ],
        'gemini-2.5-flash-image' => [
            'provider'     => 'google',
            'display_name' => 'Nano Banana 1 (Gemini 2.5 Flash Image)',
            'type'         => 'image',
            'pricing'      => ['per_image' => 0.0300],
            'capabilities' => ['image_generation']
        ]
    ];

    /**
     * Model ismine göre tüm veriyi döner 🔍
     */
    public static function get(string $model): ?array
    {
        $key = strtolower(trim(str_replace(['models/', 'v1beta/'], '', $model)));
        return self::MODELS[$key] ?? null;
    }

    /**
     * Model ismine göre fiyatlandırma verisini döner 💰
     */
    public static function getPricing(string $model): ?array
    {
        $data = self::get($model);
        return $data['pricing'] ?? null;
    }

    /**
     * Belirli bir sağlayıcıya (google vb.) ait modelleri filtreler 🏢
     */
    public static function getByProvider(string $provider = 'google'): array
    {
        $provider = strtolower(trim($provider));
        return array_filter(self::MODELS, fn($item) => ($item['provider'] ?? '') === $provider);
    }

    /**
     * Tüm modellerin resmi fiyatlandırma haritasını döner 💵
     */
    public static function getPricingMap(): array
    {
        $map = [];
        foreach (self::MODELS as $key => $data) {
            $map[$data['display_name'] ?? $key] = $data['pricing'] ?? [];
        }
        return $map;
    }
}
