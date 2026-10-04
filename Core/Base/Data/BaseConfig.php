<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Base\Data;

use Rbn\Framework\Core\Base\BaseComponent;
use Rbn\Framework\Core\Base\Data\Traits\Config\ActionConfigTrait;

/**
 * BaseConfig - The Foundation for all Configuration Gateways 🏛️⚓
 * 
 * RBN 3.5: Masterpiece Refactoring.
 * Fully autonomous and trait-driven DNA management.
 * Purely inherits Universal map() Orchestrator from the BaseComponent root.
 */
class BaseConfig extends BaseComponent implements \ArrayAccess
{
    /** @var bool Recursion Guard Flag 🛡️ */
    private static bool $isResolving = false;

    /**
     * Configuration Hub ⚙️🛰️⚓
     * Orchestrates Data, Discovery and Mapping systems.
     */
    use ActionConfigTrait;

    /**
     * Standard Repository for active configuration state 🧠
     */
    protected array $repository = [];

    /**
     * Root context for dot-notation resolution 🌍
     */
    protected ?string $rootContext = null;

    /**
     * Boot the Config DNA ⚓
     */
    public function __construct(?string $rootContext = null)
    {
        parent::__construct();
        $this->rootContext = $rootContext;
    }
    /**
     * CENTRALIZED REGISTRATION MAP (SSOT) 🏛️⚓
     * RBN 3.5 Unified Standard: Single source of truth for all components.
     * To be overridden by registries and modules.
     */
    public function registerMap(): array
    {
        return [];
    }

    /**
     * Masterpiece Proxy: Static gateway to project settings 🌉🛰️⚓
     * 
     * RBN 3.5: Bridges Definition category classes to the central Config hub.
     * Includes a recursion guard to prevent 'God Loops' during infrastructure resolution.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        // 🛡️ RBN 3.5: [RECURSION GUARD] - Eğer zaten bir çözümleme içindeysek döngüyü kır!
        //
        // 🛡️ B-32: Koruma KORUNDU (false -> $default), ama DÜŞEN ANAHTAR
        // GÖRÜNMEZDI: `self::$isResolving` global olduğu için bir çözümleme
        // sırasında HERHANGİ bir iç içe çağrı sessizce default dönüyordu.
        // Artık anahtar + çağrı yeri loglanır. (Aşağıda `MySqlProvider` bu
        // sessiz null'u alıp yanıltıcı bir `PreflightException` atıyordu.)
        if (self::$isResolving) {
            error_log('[RBN] BaseConfig::get() ozineleme korumasina takildi, '
                . 'anahtar="' . $key . '" default olarak donuyor. '
                . 'Bekleyen ust cagri: ' . (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 4)[2]['function'] ?? '?'));

            return $default;
        }

        self::$isResolving = true;

        try {
            return \Rbn\Framework\Core\System\Config\Config::get($key, $default);
        } catch (\Throwable $e) {
            // B-32: bu da sessizce yutuluyordu; aynı gerekçeyle loglanır.
            error_log('[RBN] BaseConfig::get() cozumleme hatasi, anahtar="' . $key . '" -> '
                . get_class($e) . ': ' . $e->getMessage() . ' (default donuluyor)');

            return $default;
        } finally {
            self::$isResolving = false;
        }
    }

    /**
     * Export the configuration state to a standard array. 📦⚓
     */
    public function toArray(): array
    {
        return $this->repository;
    }

    /* ==========================================================================
       [ ARRAYACCESS COMPATIBILITY ] - RBN 3.5 Bridge 🌉
       ========================================================================== */

    public function offsetExists($offset): bool { return $this->has((string)$offset); }
    public function offsetGet($offset): mixed { return $this->get((string)$offset); }
    public function offsetSet($offset, $value): void { $this->set((string)$offset, $value); }
    public function offsetUnset($offset): void { $this->set((string)$offset, null); }
}
