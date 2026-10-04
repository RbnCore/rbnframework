<?php

namespace Rbn\Framework\Core\System\Storage\Constants;

/**
 * LogConstant - Centralized Enums and Constants for the Logging System
 */
class LogConstant
{
    /**
     * System-wide categories for classifying log files
     */
    public const CATEGORIES = [
        'auth' => [
            'title' => 'Kimlik Doğrulama',
            'keywords' => ['auth'],
            'icon' => 'bi-shield-lock',
            'class' => 'primary'
        ],
        'email' => [
            'title' => 'Email Logları',
            'keywords' => ['email'],
            'icon' => 'bi-envelope',
            'class' => 'info'
        ],
        'error' => [
            'title' => 'Hata Logları',
            'keywords' => ['error', 'critical'],
            'icon' => 'bi-exclamation-octagon',
            'class' => 'danger'
        ],
        'ajax' => [
            'title' => 'Ajax Logları',
            'keywords' => ['ajax'],
            'icon' => 'bi-cpu',
            'class' => 'warning'
        ],
        'application' => [
            'title' => 'Uygulama Logları',
            'keywords' => ['app', 'debug'],
            'icon' => 'bi-app-indicator',
            'class' => 'secondary'
        ],
        'cli' => [
            'title' => 'CLI İşlemleri',
            'keywords' => ['clilog', 'cli'],
            'icon' => 'bi-terminal',
            'class' => 'dark'
        ],
        'traffic' => [
            'title' => 'Trafik & IP',
            'keywords' => ['traffic'],
            'icon' => 'bi-graph-up-arrow',
            'class' => 'success'
        ],
        'other' => [
            'title' => 'Diğer',
            'keywords' => [],
            'icon' => 'bi-file-earmark-code',
            'class' => 'dark'
        ],
        'total' => [
            'title' => 'Toplam Kayıt',
            'keywords' => [],
            'icon' => 'bi-pie-chart-fill',
            'class' => 'dark'
        ]
    ];

    /**
     * Helper to get a config by type directly from the constant
     */
    public static function getConfig(string $type): array
    {
        return self::CATEGORIES[$type] ?? self::CATEGORIES['other'];
    }
}
