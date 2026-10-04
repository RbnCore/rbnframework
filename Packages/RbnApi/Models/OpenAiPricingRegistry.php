<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnApi\Models;

/**
 * OpenAiPricingRegistry - OpenAI GPT Models Pricing Reference 🏷️📊
 * RBN 3.5 Masterpiece Standard.
 */
class OpenAiPricingRegistry
{
    /**
     * OpenAI Sürüm Fiyatlandırma Tablosu (1M Tokens Başına USD Cinsinden Maliyetler) 💵
     */
    public const MODELS = [
        'gpt-5.6-sol' => [
            'name' => 'GPT-5.6 Sol',
            'input' => 5.00,
            'cached_input' => 0.50,
            'cache_writes' => 6.25,
            'output' => 30.00,
            'short_context_input' => 10.00,
            'long_context_output' => 45.00
        ],
        'gpt-5.6-terra' => [
            'name' => 'GPT-5.6 Terra',
            'input' => 2.00,
            'cached_input' => 0.20,
            'cache_writes' => 2.50,
            'output' => 12.00,
            'short_context_input' => 4.00,
            'long_context_output' => 18.00
        ],
        'gpt-5.6-luna' => [
            'name' => 'GPT-5.6 Luna (Ekonomik)',
            'input' => 0.20,
            'cached_input' => 0.02,
            'cache_writes' => 0.25,
            'output' => 1.20,
            'short_context_input' => 0.40,
            'long_context_output' => 1.80
        ],
        'gpt-5.5' => [
            'name' => 'GPT-5.5 Standard',
            'input' => 5.00,
            'cached_input' => 0.50,
            'cache_writes' => 0.00,
            'output' => 30.00,
            'short_context_input' => 10.00,
            'long_context_output' => 45.00
        ],
        'gpt-5.5-pro' => [
            'name' => 'GPT-5.5 Pro',
            'input' => 30.00,
            'cached_input' => 0.00,
            'cache_writes' => 0.00,
            'output' => 180.00,
            'short_context_input' => 60.00,
            'long_context_output' => 270.00
        ],
        'gpt-5.4' => [
            'name' => 'GPT-5.4 Standard',
            'input' => 2.50,
            'cached_input' => 0.25,
            'cache_writes' => 0.00,
            'output' => 15.00,
            'short_context_input' => 5.00,
            'long_context_output' => 22.50
        ],
        'gpt-5.4-mini' => [
            'name' => 'GPT-5.4 Mini',
            'input' => 0.75,
            'cached_input' => 0.075,
            'cache_writes' => 0.00,
            'output' => 4.50,
            'short_context_input' => 0.00,
            'long_context_output' => 0.00
        ],
        'gpt-5.4-nano' => [
            'name' => 'GPT-5.4 Nano (Ultra Hafif)',
            'input' => 0.20,
            'cached_input' => 0.02,
            'cache_writes' => 0.00,
            'output' => 1.25,
            'short_context_input' => 0.00,
            'long_context_output' => 0.00
        ],
        'gpt-5.4-pro' => [
            'name' => 'GPT-5.4 Pro',
            'input' => 30.00,
            'cached_input' => 0.00,
            'cache_writes' => 0.00,
            'output' => 180.00,
            'short_context_input' => 60.00,
            'long_context_output' => 270.00
        ]
    ];
}
