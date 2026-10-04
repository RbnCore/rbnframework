<?php

declare(strict_types=1);

namespace Rbn\Framework\Bundles\Internal\Webhub\Models;

use Rbn\Framework\Core\Base\Data\BaseConfig;

/**
 * SeoConfig - Configuration and View Helpers for SEO Module
 */
class SeoConfig extends BaseConfig
{
    /**
     * Skora göre arayüz renk, ikon ve metinlerini döner.
     */
    public static function getScoreDisplay(int $score): array
    {
        if ($score === 0) {
            return [
                'color' => '#6b7280', // Gri
                'icon' => 'bi-activity',
                'text' => 'Taranmadı'
            ];
        }

        if ($score >= 80) {
            return [
                'color' => '#10b981', // Yeşil
                'icon' => 'bi-emoji-smile',
                'text' => 'Mükemmel'
            ];
        }

        if ($score >= 50) {
            return [
                'color' => '#f59e0b', // Sarı
                'icon' => 'bi-emoji-neutral',
                'text' => 'Geliştirilmeli'
            ];
        }

        return [
            'color' => '#ef4444', // Kırmızı
            'icon' => 'bi-emoji-frown',
            'text' => 'Kritik Durum'
        ];
    }
}
