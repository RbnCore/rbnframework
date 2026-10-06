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
    public const FRAMEWORK_VERSION = '0.9.4';
    public const FRAMEWORK_SLOGAN = 'Modüler ve çok kiracılı PHP uygulama çatısı';
    public const FRAMEWORK_DESCRIPTION = 'RBN Framework, çok kiracılı (multi-tenant) modüler bir PHP uygulama çatısıdır: yönetim paneli, kimlik doğrulama, SEO, güvenlik ve içerik modüllerini aynı çatı altında sunar.';
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
    // [FW-SURUMLEME-2] Bilesen surumleri de `.agents/rules/versioning.md`
    // kuralina uyar: `A.B.C` uc parcali, `v` oneki YOK, B/C basamagi 9'u
    // asmaz. Gosterim yerleri (`PanelHandler`, `AuthHandler`, `sidebar.rbn.php`,
    // `RbnCli` basligi) bu SABITLERI okur; degerler eklendiigi icin
    // hicbir gosterim yeri degismedi.
    public const FRAMEWORK_CLI_VERSION = '2.3.0';

    // -------------------------------------------------------------
    // Bileşen kimlikleri (ad / sürüm / slogan) - TEK GÜNCELLEME YERİ
    // Okuma: `RbnSystemInfo::get('ADMIN_VERSION')` ya da bu sabitler.
    // -------------------------------------------------------------
    public const SHIELD_NAME = 'RbnShield';
    public const SHIELD_VERSION = '2.1.0';
    public const SHIELD_SLOGAN = 'Professional Error & Security Service';

    public const ADMIN_NAME = 'RBN Admin Pro';
    public const ADMIN_VERSION = '1.2.0';
    public const ADMIN_TITLE = 'Yönetim Paneli';
    public const ADMIN_SLOGAN = 'Gelişmiş Yönetim ve Kontrol Merkezi';

    public const AUTH_NAME = 'RbnAuth';
    public const AUTH_VERSION = '2.2.0';
    public const AUTH_SLOGAN = 'Professional Identity Service';
}
