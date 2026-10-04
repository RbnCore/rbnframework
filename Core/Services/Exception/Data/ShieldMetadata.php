<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Services\Exception\Data;

use Rbn\Framework\Core\Support\Definitions\System\FrameworkIdentity;

/**
 * ShieldMetadata - The RBN Shield Identity & Policy 🏗️🛡️🏺
 * 
 * RBN 3.5: Masterpiece SSoT (Single Source of Truth).
 * Contains branding metadata and the global exception-to-layer policy.
 */
class ShieldMetadata
{
    // --- Branding & Identification ---
    public const SEO_TITLE = FrameworkIdentity::SHIELD_NAME . ' | Sistem Koruması ve Hata Yönetimi';
    public const SEO_DESC = FrameworkIdentity::SHIELD_NAME . ' güvenli hata yönetimi ve sistem koruma servisidir.';
    public const FAVICON = 'data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🛡️</text></svg>';
    public const ICON = '<span style="color: #ef4444; font-size: 32px; display: inline-block; vertical-align: middle;">⚠️</span>';

    /**
     * Master Error Mapping 🗺️🏛️
     * 
     * RBN 3.5: Clean, Semantic Strings for Layers and Designs.
     * Maps Throwables to their respective HIERARCHY stages.
     */
    public const ERROR_MAP = [
        // 🚀 Layer 1: Doktor Teşhisi (Pre-flight)
        \Rbn\Framework\Core\Support\Exceptions\PreflightException::class => [
            'level' => 'pre_flight',
            'design' => 'pre_flight',
            'view' => 'pre_flight',
            'label' => 'Sistem Başlatma Hatası'
        ],

        // 🛡️ Layer 2: Kritik Veritabanı Çöküşü (Fatal)
        \PDOException::class => [
            'level' => 'fatal',
            'design' => 'fatal',
            'view' => 'internal', // SurvivalProvider handles this (Layer 0/2) 🆘🛡️
            'label' => 'Veritabanı Çöküşü'
        ],

        // 🧪 Layer 3: Teşhis Edilebilir Sistem Hatası (Development)
        \Rbn\Framework\Core\Support\Exceptions\DiagnosticException::class => [
            'level' => 'development',
            'design' => 'development',
            'view' => 'development',
            'label' => 'Sistem Teşhis Hatası'
        ],

        // 🎭 Layer 4: Kullanıcı Dosyaları (Production)
        \Rbn\Framework\Core\Support\Exceptions\ValidationException::class => [
            'level' => 'user',
            'design' => 'user',
            'label' => 'Form Doğrulama Hatası'
        ],

        \Rbn\Framework\Core\Support\Exceptions\PageNotFoundException::class => [
            'level' => 'user',
            'design' => 'user',
            'view' => '404',
            'label' => 'Sayfa Bulunamadı'
        ],

        // 🔇 Varsayılan (Fallback)
        'default' => [
            'level' => 'development',
            'design' => 'development',
            'view' => 'development',
            'label' => 'Sistem Hatası'
        ]
    ];
}
