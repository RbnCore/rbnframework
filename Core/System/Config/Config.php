<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\System\Config;

use Rbn\Framework\Core\System\Config\Engine\Config\ConfigResolver;

/**
 * Config - The "Strategic Proxy Hub" ✨🛰️⚓
 * 
 * RBN Framework Hub:
 * Flattened architecture. Directly orchestrates Environment and Schema resolvers.
 * Acts as a singleton proxy for all dynamic and static configuration requests.
 */
class Config
{
    /** @var array Loaded configuration cache */
    private static array $items = [];

    /**
     * Clear loaded configuration items cache 🧹
     */
    public static function clear(?string $key = null): void
    {
        if ($key !== null) {
            unset(self::$items[$key]);
        } else {
            self::$items = [];
        }
    }

    /**
     * Enable or disable Survival Mode (Proxy to Resolver) 🛡️🛰️
     */
    public static function setSurvivalMode(bool $status): void
    {
        ConfigResolver::setSurvivalMode($status);
    }

    /**
     * Check if currently resolving (Proxy to Resolver) 🛡️
     */
    public static function isResolving(): bool
    {
        return ConfigResolver::isResolving();
    }

    /* ==========================================================================
       [ STATIC API ] - The Elite Proxy Interface 🏛️🏙️⚓
       ========================================================================== */

    /**
     * Dynamic Config retrieval (Dot-notation support) 🪄
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        // 🎼 RBN Framework: [RECURSION GUARD] 🛡️
        if (ConfigResolver::isResolving()) {
            return $default;
        }

        ConfigResolver::setResolving(true);

        try {
            // 1. Specialized Categories (Direct Resolve via Engine) 🏛️⚙️
            if (in_array($key, ['database', 'database_master', 'database_common', 'database_project'])) {
                return ConfigResolver::resolveContext($key);
            }

            // 2. Standard Dot-Notation Access 🧠
            $parts = explode('.', $key);
            $filename = array_shift($parts);

            if (!isset(self::$items[$filename])) {
                $path = ConfigResolver::resolve($filename);
                self::$items[$filename] = (file_exists($path)) ? (array) (include $path) : [];

                // RBN Framework Dynamic Multi-Tenant Settings Override 🧬🛰️⚓
                // [FW-ROUTEMAP-SSOT-2 · PATRON KARARI 05.10.2026] KAPI KALDIRILDI.
                // Önceden `project-settings.php` içindeki `has_route_map` bayrağı bu
                // birleştirmeyi şart koşuyordu; anahtar olmayınca `view_mapping`
                // HİÇ UYGULANMIYORDU (canlıda `has_route_map` yazılmayan projelerde
                // olduğu gibi). Karar: `project-routemap.php` TEK KAYNAK; kapı
                // kaldırıldı, birleştirme AÇIK `project_key` için HER ZAMAN çalışır.
                // GERİYE UYUM: dosyada `has_route_map` kalsa bile motor çalışır —
                // anahtar artık OKUNMAZ, yalnız YOK SAYILIR (gövde değişmedi).
                if ($filename === 'project-settings') {
                    $activeKey = function_exists('active_project_key') ? active_project_key() : '';
                    if (!empty($activeKey)) {
                        $instance = new class { use \Rbn\Framework\Core\Base\Concerns\Data\ResolvesProjectConfigTrait; };
                        $overrides = (array) ($instance->getRouteConfig($activeKey) ?? []);
                        foreach ($overrides as $overrideKey => $overrideVal) {
                            self::$items[$filename][$overrideKey] = $overrideVal;
                        }
                    }
                }
            }

            $current = self::$items[$filename];
            foreach ($parts as $part) {
                if (!is_array($current) || !isset($current[$part])) {
                    return $default;
                }
                $current = $current[$part];
            }

            return $current;
        } catch (\Throwable $e) {
            return $default;
        } finally {
            ConfigResolver::setResolving(false);
        }
    }

    /**
     * Dynamic Config setter 🛡️
     */
    public static function set(string $key, mixed $value): void
    {
        $parts = explode('.', $key);
        $filename = array_shift($parts);

        if (!isset(self::$items[$filename])) {
            self::$items[$filename] = [];
        }

        $current = &self::$items[$filename];
        foreach ($parts as $part) {
            if (!isset($current[$part]) || !is_array($current[$part])) {
                $current[$part] = [];
            }
            $current = &$current[$part];
        }

        $current = $value;
    }
}
