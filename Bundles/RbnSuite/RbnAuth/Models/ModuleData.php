<?php

namespace Rbn\Framework\Bundles\RbnSuite\RbnAuth\Models;

use Rbn\Framework\Core\Support\Definitions\System\FrameworkIdentity;
use Rbn\Framework\Core\Base\Data\BaseConfig;
use Rbn\Framework\Bundles\RbnSuite\RbnAuth\Services;
use Rbn\Framework\Bundles\RbnSuite\RbnAuth\Handlers;
use Rbn\Framework\Bundles\RbnSuite\RbnAuth\Models\AuthIdentity;
use Rbn\Framework\Bundles\RbnSuite\RbnAuth\Models\AuthRole;

/**
 * ModuleData - RbnAuth Paket Kimliği ve Mimari Veri Merkezi 🛡️🛰️🏛️⚓
 * RBN 3.5 Masterpiece Standard.
 * 
 * Beşinci Satellite: Brain (The Chef).
 * Bu sınıf paketin tüm kayıtlarını (Service, Handler, Provider) yönetirken;
 * Veriyi AuthIdentity (Soul) ve AuthMap (Skeleton) üzerinden orkestre eder.
 */
class ModuleData extends BaseConfig
{
    /**
     * Master Orchestration Hub ⚙️🛰️⚓
     * RBN 3.5: Reference-based configuration for maximum modularity.
     */
    public const CONFIG = [
        // 🏛️ Soul: External Identity & Branding
        'identity' => [
            'name' => FrameworkIdentity::AUTH_NAME,
            'version' => FrameworkIdentity::AUTH_VERSION,
            'seo' => AuthIdentity::AUTH_IDENTITY['seo'],
            'metadata' => AuthIdentity::AUTH_IDENTITY['metadata'],
            'ui' => AuthIdentity::AUTH_IDENTITY['ui']
        ],

        // 🎼 Standard Framework Metadata (Dynamic Discovery)
        'module_name' => FrameworkIdentity::AUTH_NAME,
        'module_version' => FrameworkIdentity::AUTH_VERSION,
        'module_service' => 'auth',

        // 🛠️ Specialized UI & View Configurations
        'configs' => [
            'default_info' => AuthIdentity::DEFAULT_INFO,
            'view_map' => AuthIdentity::VIEW_MAP
        ]
    ];

    /**
     * CENTRALIZED REGISTRATION MAP 🏛️⚓🛰️
     * RBN 3.5 Masterpiece: Single source of truth for all bundle components.
     * Manifest style registration for maximum visibility and controlled discovery.
     */
    public function registerMap(): array
    {
        return [
            'services' => [
                'auth' => Services\AuthService::class,
                'recovery' => Services\RecoveryService::class,
            ],
            'handlers' => [
                'access' => Handlers\AccessHandler::class,
                'session' => Handlers\SessionHandler::class,
                'lifecycle' => Handlers\LifecycleHandler::class,
                'credential' => Handlers\CredentialHandler::class,
                'audit' => Handlers\AuditHandler::class,
            ],
            'constants' => [
                'role' => AuthRole::class,
                'auth' => AuthIdentity::class,
            ]
        ];
    }
}
