<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\System\Registries\RegistryMap;

/**
 * SystemResourceMapTrait - The Universal Resource Library 🛠️📋⚓
 * 
 * RBN 3.5 Masterpiece: Centralized authority for Helpers, Metadata and Constants.
 * Consolidates all Internal DNA and Utility maps.
 */
trait SystemResourceMapTrait
{
    /**
     * Map of Utility Resources & Metadata 📋🛠️
     * DİKKAT: Bu haritaya (ResourceMap) SADECE 'helpers', 'validations', 'constants' ve 'metadata' eklenecektir.
     */
    protected function resourceMap(): array
    {
        return [
            /* --- Core Utility Helpers 🛠️ --- */
            'helpers' => [
                'format' => 'Core\\Support\\Bridges\\Helpers\\Library\\FormatHelper',
                'text' => 'Core\\Support\\Bridges\\Helpers\\Library\\TextHelper',
                'meta.seo' => 'Core\\Support\\Bridges\\Helpers\\Library\\MetaSeoHelper',
                'datasweep' => 'Core\\Support\\Bridges\\Helpers\\Library\\DataSweepHelper',
                'path' => 'Core\\Support\\Bridges\\Helpers\\Library\\PathHelper',
                'crypto' => 'Core\\Support\\Bridges\\Helpers\\Library\\CryptoHelper',
                'debug' => 'Core\\Support\\Bridges\\Helpers\\Library\\DebugHelper',
                'icons' => 'Core\\Support\\Bridges\\Helpers\\Library\\IconLibrary',
                'color' => 'Core\\Support\\Bridges\\Helpers\\Library\\ColorHelper',  // 🎨 Dynamic Theme Engine
                'renderField' => 'Core\\Support\\Bridges\\Helpers\\Library\\RenderFieldHelper', // 🎭 Form Field Renderer
                'Geo' => 'Core\\Support\\Bridges\\Helpers\\Library\\GeoHelper', // 🌍 Geographic Intelligence Hub
                'data' => 'Core\\Support\\Bridges\\Helpers\\Library\\DataHelper', // 🗃️ Unified Resource Data Helper
            ],

            /* --- Validation Blueprints ✅ --- */
            'validations' => [
                'email' => 'Core\Support\Blueprints\Validations\EmailValidations',
                'file' => 'Core\Support\Blueprints\Validations\FileValidations',
                'form' => 'Core\Support\Blueprints\Validations\FormValidations',
                'password' => 'Core\Support\Blueprints\Validations\PasswordValidations',
                'phone' => 'Core\Support\Blueprints\Validations\PhoneValidations',
                'security' => 'Core\Support\Blueprints\Validations\SecurityValidations',
            ],

            /* --- System Constants & DNA ⚖️ --- */
            'constants' => [],

            /* --- Global Framework Metadata 📋 --- */
            'metadata' => [
                'ADMIN_' => 'Bundles\\RbnSuite\\RbnAdmin\\Models\\PanelIdentity',
                'KIT_' => 'Core\\Render\\Config\\AssetConfig',
                'SEO_' => 'Core\\Render\\Config\\SeoConfig',
            ],
        ];
    }
}
