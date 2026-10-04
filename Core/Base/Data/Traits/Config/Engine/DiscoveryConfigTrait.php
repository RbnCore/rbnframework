<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Base\Data\Traits\Config\Engine;

use Rbn\Framework\Core\System\Registries\SystemRegistry;
use Rbn\Framework\Core\System\Discovery\Engine\DiscoveryEngine;

/**
 * DiscoveryConfigTrait - Discovery Orchestration & Gate Management 🛰️⚙️🎻
 * 
 * RBN 3.5 Triple Gates: The Grand Orchestrator.
 * Universal name mapping: Uses standardized 'Map' terminology across all gates.
 * Refactored for Pentaric Satellite Architecture & Dynamic Module Discovery.
 */
trait DiscoveryConfigTrait
{
    /**
     * RBN 3.5: Masterpiece Expansion DNA 🧬🛰️⚓
     */
    use \Rbn\Framework\Core\System\Discovery\Base\Traits\NamespaceExpansionTrait;

    /** @var bool Recursion Guard Flag 🔐 */
    private static bool $isLocating = false;

    /**
     * UNIFIED DISCOVERY ENGINE: Locate Registry 🛰️⚓🎻
     */
    protected static function locateRegistry(string $name, string $type): ?string
    {
        if (self::$isLocating) {
            return DiscoveryEngine::instance()->namespace()->find($name, $type);
        }

        self::$isLocating = true;

        try {
            // 🎼 RBN 3.5 Masterpiece Gate Logic: System -> Project -> Sovereign -> Module -> Component
            if ($found = static::searchInGate('System', $name, $type))
                return $found;
            if ($found = static::searchInGate('Project', $name, $type))
                return $found;
            if ($found = static::searchInGate('Sovereign', $name, $type))
                return $found;
            if ($found = static::searchInGate('Module', $name, $type))
                return $found;

            return DiscoveryEngine::instance()->namespace()->find($name, $type);
        } finally {
            self::$isLocating = false;
        }
    }

    /**
     * Internal Guarded Search within a specific Gate 🏰🛰️
     */
    private static function searchInGate(string $gate, string $name, string $type): ?string
    {
        // 🎼 RBN 3.5 GATE RESOLUTION DNA 🧬⚓
        $gateClassMapping = [
            'System' => [SystemRegistry::class],
            'Project' => ["Rbn\\Framework\\Core\\System\\Registries\\AppProjectRegistry"],
            'Sovereign' => SystemRegistry::sovereignBundles()
        ];

        // 🧬 1. Static Resolution (System, Project & Sovereign Gates) 🏛️
        if (isset($gateClassMapping[$gate])) {
            foreach ($gateClassMapping[$gate] as $class) {
                if (class_exists($class)) {
                    static $instances = [];
                    $instances[$class] ??= new $class();
                    if ($found = static::checkRegistry($instances[$class], $name, $type)) {
                        return $found;
                    }
                }
            }
        }

        // 🧬 2. Point-Target Resolution (Module Gate) 🏙️🛰️⚓
        // Zero-Search Policy: Directly check the active module via SystemRegistry.
        if ($gate === 'Module') {
            static $systemRegistry = null;
            $systemRegistry ??= new SystemRegistry();

            // The SystemRegistry::registerMap() already includes the active module's map dynamically.
            return static::checkRegistry($systemRegistry, $name, $type);
        }


        return null;
    }

    /**
     * Check a Registry instance for a specific component 📜🕵️‍♂️
     */
    private static function checkRegistry(object $instance, string $name, string $type): ?string
    {
        if (!method_exists($instance, 'registerMap')) {
            return null;
        }

        $fullMap = $instance->registerMap();

        // 🎼 RBN 3.5: Single Source of Truth Pluralization 🎭📦
        $key = \Rbn\Framework\Core\Support\Definitions\System\ComponentTypes::pluralize($type);
        $found = isset($fullMap[$key]) ? static::searchDeep($name, $fullMap[$key]) : null;

        // 🎼 RBN 3.5: [SOVEREIGN ARRAY RESOLUTION] 🏙️🛰️⚓
        // Recursive merge nedeniyle dizi dönerse, en son eklenen (override) değeri al.
        return is_array($found) ? (string) end($found) : $found;
    }

    /**
     * Identifies the current Registry Type based on class name 🛰️
     */
    protected static function getRegistryType(): string
    {
        $className = (new \ReflectionClass(static::class))->getShortName();
        $type = strtolower(str_replace('Registry', '', $className));

        return \Rbn\Framework\Core\Support\Definitions\System\ComponentTypes::pluralize($type);
    }

    /**
     * Recursive search within mapping arrays 🔍
     */
    protected static function searchDeep(string $key, array $array): mixed
    {
        $lowKey = strtolower($key);

        foreach ($array as $k => $v) {
            if (is_string($k) && strtolower($k) === $lowKey) {
                return $v;
            }

            if (is_int($k) && is_string($v) && strtolower(basename(str_replace('\\', '/', $v))) === $lowKey) {
                return $v;
            }

            if (is_array($v)) {
                $found = self::searchDeep($key, $v);
                if ($found !== null)
                    return $found;
            }
        }

        return null;
    }

    /**
     * Entry point for Triple Gates Discovery 🛰️⚓
     */
    public static function locate(string $name, ?string $type = null): ?string
    {
        return static::locateRegistry($name, $type ?? static::getRegistryType());
    }
}
