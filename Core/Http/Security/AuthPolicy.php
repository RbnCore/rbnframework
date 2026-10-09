<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Http\Security;

use Rbn\Framework\Core\Base\Services\BaseService;
use Rbn\Framework\Core\System\Config\Config;
use Rbn\Framework\Core\System\Config\Definitions\ConfigMap;

/**
 * AuthPolicy - Project-level authentication policy (single reader).
 *
 * Every project-level auth decision (registration open/closed, user landing
 * path, user session lifetimes) is read HERE and nowhere else. The values come
 * from the existing settings chain: `project-settings.php` with the site's
 * `project-routemap.php` `view_mapping[<project_key>]` block merged on top
 * (`Config::get('project-settings.<key>')`). No parallel registry is kept.
 *
 * Keys (all optional, written in the site's `view_mapping` block):
 *   - `auth_registration`        bool   default true  (absent key = open; see registrationEnabled())
 *   - `auth_user_home`           string default '/user'
 *   - `session_absolute_timeout` int    minutes, default 720 (12 hours)
 *
 * The idle limit is a panel setting, not a file key: DB `security.session_timeout`
 * (settings service), default ConfigMap::getAppSessionTimeout(). The server-side
 * check and the panel countdown both read it through sessionIdleMinutes().
 */
final class AuthPolicy
{
    /** Registration route paths (GET form + aliases + POST submit). */
    public const REGISTER_PATHS = ['register', 'kayit', 'auth/register'];

    /** Default landing path after a non-admin login. */
    public const DEFAULT_USER_HOME = '/user';

    /** Absolute user session lifetime when the project does not set one (minutes). */
    public const DEFAULT_SESSION_ABSOLUTE_MINUTES = 720;

    /** Upper bound for any configured lifetime (minutes): 7 days. */
    private const MAX_SESSION_MINUTES = 10080;

    /**
     * Text/number values that OPEN registration, compared after trim, Turkish
     * letter folding and lower-case ('açık', 'AÇIK' and 'acik' all match 'acik').
     */
    public const REGISTRATION_OPEN_VALUES = ['1', 'true', 'on', 'yes', 'evet', 'acik'];

    private const TR_ASCII_FOLD = [
        'ç' => 'c', 'Ç' => 'c', 'ğ' => 'g', 'Ğ' => 'g', 'ı' => 'i', 'İ' => 'i',
        'ö' => 'o', 'Ö' => 'o', 'ş' => 's', 'Ş' => 's', 'ü' => 'u', 'Ü' => 'u',
    ];

    /**
     * Is self-registration open for the active project?
     *
     * Allow-list (fail-closed): an absent key keeps the existing behaviour
     * (open), a bool is used as is, and a text/number opens registration ONLY
     * when it is one of REGISTRATION_OPEN_VALUES. Anything else ('kapalı',
     * 'disabled', a typo, an array) closes it. If the setting cannot be read
     * at all, registration is closed and a `security` log line is written.
     */
    public static function registrationEnabled(): bool
    {
        // `Config::get()` swallows its own errors and returns the default, so
        // an unreadable file would look like an absent key (= open). The whole
        // settings array is read with a sentinel default to tell them apart.
        $unreadable = new \stdClass();
        try {
            $settings = Config::get('project-settings', $unreadable);
        } catch (\Throwable) {
            $settings = $unreadable;
        }

        if (!is_array($settings)) {
            self::securityLog('AUTH_REGISTRATION_SETTING_UNREADABLE', ['type' => get_debug_type($settings)]);

            return false;
        }

        return self::registrationValueOpen($settings['auth_registration'] ?? null);
    }

    /**
     * Pure decision for a raw `auth_registration` value (unit-tested).
     */
    public static function registrationValueOpen(mixed $raw): bool
    {
        if ($raw === null) {
            return true;
        }
        if (is_bool($raw)) {
            return $raw;
        }
        if (is_int($raw) || is_float($raw)) {
            $raw = (string) $raw;
        }
        if (!is_string($raw)) {
            return false;
        }

        // Turkish letters fold to ASCII first, so 'açık' / 'AÇIK' / 'ACIK' match
        // without mbstring (mb_strtolower would turn 'I' into 'i', not 'ı').
        $value = strtolower(strtr(trim($raw), self::TR_ASCII_FOLD));

        return in_array($value, self::REGISTRATION_OPEN_VALUES, true);
    }

    /**
     * Is the given request path one of the registration routes?
     */
    public static function isRegisterPath(string $path): bool
    {
        $path = strtolower(trim(substr($path, 0, strcspn($path, '?#')), '/'));

        return in_array($path, self::REGISTER_PATHS, true);
    }

    /**
     * Landing path for a non-admin user after login.
     *
     * Only a local absolute path is accepted (`/x`); `//host`, `\`, schemes and
     * control characters fall back to the default (no open redirect).
     */
    public static function userHomePath(): string
    {
        $raw = self::setting('auth_user_home');
        if (!is_string($raw)) {
            return self::DEFAULT_USER_HOME;
        }

        $path = trim($raw);
        if (
            $path === ''
            || $path[0] !== '/'
            || str_starts_with($path, '//')
            || str_contains($path, '\\')
            || preg_match('/[\x00-\x1F\x7F]/', $path) === 1
        ) {
            return self::DEFAULT_USER_HOME;
        }

        return $path;
    }

    /**
     * Idle limit for sessions (minutes): panel setting `security.session_timeout`.
     */
    public static function sessionIdleMinutes(): int
    {
        return self::minutes(self::securitySetting('session_timeout'), ConfigMap::getAppSessionTimeout());
    }

    /**
     * Server-side absolute limit for user sessions (minutes).
     */
    public static function sessionAbsoluteMinutes(): int
    {
        return self::minutes(self::setting('session_absolute_timeout'), self::DEFAULT_SESSION_ABSOLUTE_MINUTES);
    }

    private static function minutes(mixed $raw, int $default): int
    {
        $value = is_numeric($raw) ? (int) $raw : $default;

        if ($value < 1) {
            $value = $default;
        }

        return min($value, self::MAX_SESSION_MINUTES);
    }

    private static function securityLog(string $event, array $context): void
    {
        try {
            BaseService::get()->logs()?->channel('security')->warning($event, $context);
        } catch (\Throwable) {
            error_log('RBN Guvenlik: ' . $event . ' (kayit kapali tutuldu)');
        }
    }

    private static function setting(string $key): mixed
    {
        try {
            return Config::get('project-settings.' . $key);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Panel-managed security setting (DB `security.<key>`, cached by the settings service).
     */
    private static function securitySetting(string $key): mixed
    {
        try {
            $security = BaseService::get()->service('settings')?->read('security');
        } catch (\Throwable) {
            return null;
        }

        return is_array($security) ? ($security[$key] ?? null) : null;
    }
}
