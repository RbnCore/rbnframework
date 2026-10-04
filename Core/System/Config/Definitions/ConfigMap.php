<?php
declare(strict_types=1);

namespace Rbn\Framework\Core\System\Config\Definitions;

use Rbn\Framework\Core\Base\Data\BaseConfig;

/**
 * ConfigMap - High-Level Configuration Definitions 🛰️⚙️
 * 
 * RBN 3.5: Decentralized discovery hub for global app configurations.
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
     * Environment Detection DNA 🌍
     */
    public static function getAppEnv(): string
    {
        return defined('RBN_DEV') && RBN_DEV ? 'development' : (self::get('app.env') ?? 'production');
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
     * RBN 3.5: Single source of truth for framework-level session protection.
     * Prioritizes RBN_SESSION_TIMEOUT constant, fallbacks to 30 minutes.
     */
    public static function getAppSessionTimeout(): int
    {
        return defined('RBN_SESSION_TIMEOUT') ? (int)RBN_SESSION_TIMEOUT : 30;
    }
}
