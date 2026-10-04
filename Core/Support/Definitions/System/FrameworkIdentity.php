<?php
declare(strict_types=1);

namespace Rbn\Framework\Core\Support\Definitions\System;

use Rbn\Framework\Core\Base\Data\BaseConfig;

/**
 * FrameworkIdentity - The System Core Identity Blueprint 🧬🏛️
 * 
 * RBN 3.5: Centralizes all hardcoded metadata about the framework.
 * This is the Single Source of Truth (SSoT) for versioning, 
 * authorship, and core service mappings.
 */
class FrameworkIdentity extends BaseConfig
{
    /**
     * Explicit Definition Identity 🧬🏛️
     */
    protected static ?string $definitionCategory = 'identity';

    // -------------------------------------------------------------
    // Core Identity
    // -------------------------------------------------------------
    public const FRAMEWORK_NAME = 'RBN Core - Framework';
    public const FRAMEWORK_VERSION = '0.9.0';
    public const FRAMEWORK_SLOGAN = 'The Sovereign Web Technologies & "Masterpiece" Architectures';
    public const FRAMEWORK_DESCRIPTION = 'RBN Framework provides high-performance digital transformation, enterprise software solutions, and sovereign web technologies.';
    public const FRAMEWORK_URL = 'https://rbncore.tr';
    public const CDN_BASE_URL = 'https://cdn.rbncore.tr/';

    // -------------------------------------------------------------
    // Authorship & Links
    // -------------------------------------------------------------
    public const DEVELOPER_NAME = 'RbnBilisim';
    public const DEVELOPER_URL = 'https://rbnbilisim.com.tr';
    public const DEVELOPER_EMAIL = 'info@rbncore.tr';

    // -------------------------------------------------------------
    // Public Repository Links
    // -------------------------------------------------------------
    public const REPO_URL = 'https://github.com/RbnCore/rbnframework';
    public const ISSUES_URL = 'https://github.com/RbnCore/rbnframework/issues';
    public const CHANGELOG_URL = 'https://github.com/RbnCore/rbnframework/blob/main/.github/CHANGELOG.md';
    public const UPGRADING_URL = 'https://github.com/RbnCore/rbnframework/blob/main/.github/UPGRADING.md';
    public const SECURITY_URL = 'https://github.com/RbnCore/rbnframework/security/policy';
    public const SECURITY_EMAIL = 'info@rbncore.tr';

    // -------------------------------------------------------------
    // CLI Version
    // -------------------------------------------------------------
    public const FRAMEWORK_CLI_VERSION = '2.3';

    // -------------------------------------------------------------
    // Bileşen kimlikleri (ad / sürüm / slogan) - TEK GÜNCELLEME YERİ
    // Okuma: `RbnSystemInfo::get('ADMIN_VERSION')` ya da bu sabitler.
    // -------------------------------------------------------------
    public const SHIELD_NAME = 'RbnShield';
    public const SHIELD_VERSION = 'v2.1';
    public const SHIELD_SLOGAN = 'Professional Error & Security Service';

    public const ADMIN_NAME = 'RBN Admin Pro';
    public const ADMIN_VERSION = '1.2';
    public const ADMIN_TITLE = 'Yönetim Paneli';
    public const ADMIN_SLOGAN = 'Gelişmiş Yönetim ve Kontrol Merkezi';

    public const AUTH_NAME = 'RbnAuth';
    public const AUTH_VERSION = '2.2';
    public const AUTH_SLOGAN = 'Professional Identity Service';
}
