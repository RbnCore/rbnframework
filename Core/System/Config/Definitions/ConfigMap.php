<?php
declare(strict_types=1);

namespace Rbn\Framework\Core\System\Config\Definitions;

use Rbn\Framework\Core\Base\Data\BaseConfig;

/**
 * ConfigMap - High-Level Configuration Definitions 🛰️⚙️
 * 
 * RBN Framework: Decentralized discovery hub for global app configurations.
 */
class ConfigMap extends BaseConfig
{
    /**
     * Explicit Definition Identity 🧬🏛️
     */
    protected static ?string $definitionCategory = 'config';

    /**
     * Standard Debug Gateway 🩺🛰️
     */
    public static function getAppDebug(): bool
    {
        return defined('RBN_DEBUG') ? RBN_DEBUG : (self::get('app.debug') ?? false);
    }

    /**
     * Standard Logging Policy 📓
     */
    public static function getAppLogging(): bool
    {
        return self::get('app.logging') ?? true;
    }

    /**
     * Calisma ortami (`production` | `development`).
     *
     * [FW-096-D8 / TK-03] Ikinci karar yolu YOKTUR: TEK karar noktasi
     * `PreBoot::isProductionDeclared()` (`secrets.php` `app.environment`;
     * yoksa production). Eski `app.env` ayar anahtari okunmaz.
     */
    public static function getAppEnv(): string
    {
        return \Rbn\Framework\Core\System\Kernel\Base\PreBoot::isProductionDeclared() ? 'production' : 'development';
    }


    /**
     * Standard CLI Detection 🏹
     */
    public static function getAppIsCli(): bool
    {
        return defined('RBN_CLI') ? RBN_CLI : (PHP_SAPI === 'cli');
    }

    /**
     * SECURITY: Unified Session Timeout Fallback (Minutes) 🛡️🛰️⚓
     * RBN Framework: Single source of truth for framework-level session protection.
     * Prioritizes RBN_SESSION_TIMEOUT constant, fallbacks to 30 minutes.
     */
    public static function getAppSessionTimeout(): int
    {
        return defined('RBN_SESSION_TIMEOUT') ? (int)RBN_SESSION_TIMEOUT : 30;
    }
}
