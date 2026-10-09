<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Base\Patterns\Traits\Proxy;

use Rbn\Framework\Core\Base\Services\BaseService;
use Rbn\Framework\Core\System\Registries\ComponentRegistry;

/**
 * DiscoveryProxyTrait - Standardized Magic Logic for Proxies 🪐🦾⚓
 * 
 * RBN Framework: Decoupling.
 * Artık rbn saklamaz, her zaman merkezi Hub (BaseService::get()) üzerinden konuşur.
 */
trait DiscoveryProxyTrait
{
    /** @var string The discovery target type (service|helper|proxy) ⚓ */
    protected string $type;

    /** @var array Internal cache for resolved instances */
    protected array $instances = [];

    /**
     * Initialize the discovery engine ⚙️
     */
    protected function initializeProxy(string $type): void
    {
        $this->type = $type;
    }

    /**
     * Magic Property Resolve 🪄
     */
    public function __get(string $name)
    {
        if (isset($this->instances[$name])) {
            return $this->instances[$name];
        }

        // 🎯 RBN Framework: Merkezi Kayıt Defterinden çöz
        $class = ComponentRegistry::locate($name, $this->type);

        // Class bulunduysa ayağa kaldır
        if ($class && class_exists($class)) {
            // 🎼 RBN Framework: Decoupled Constructor - Sadece tip bilgisini gönderiyoruz.
            // Orkestra şefi her zaman Hub üzerinden çözülür.
            $instance = new $class($this->type);
            return $this->instances[$name] = $instance;
        }

        // --- 2. [FALLBACK] Dispatch back to DiscoveryEngine ---
        $rbn = BaseService::get();
        if (method_exists($rbn, $this->type)) {
            return $rbn->{$this->type}($name);
        }

        throw new \Exception("Proxy Discovery failure: Target [{$name}] not found in [{$this->type}] context.");
    }

    /**
     * Magic Method Resolve 🚀
     *
     * B-64: Önce `return $this->__get($name);` idi — `$proxy->paginate(10)`
     * çağrısı `$proxy->paginate` ÖZELLİĞİNE çözülüyor ve `10` argümanı
     * sessizce yok oluyordu; ayrıca hedef metodun kendi hatası maskeleniyordu.
     *
     * Artık: hedef çözülür, gerçek bir metot varsa ARGÜMANLARIYLA çağrılır.
     * Metot yoksa eski davranış (çözülen nesneyi döndürme) aynen korunur.
     */
    public function __call(string $name, array $args)
    {
        // 1) Hedefi çöz (tüm mevcut keşif zinciri aynen çalışır)
        $hedef = $this->__get($name);

        // 2) Gerçek metot varsa ARGÜMANLARIYLA çağır
        if (is_object($hedef) && is_callable([$hedef, $name])) {
            return $hedef->{$name}(...$args);
        }

        // 3) Metot yok -> geriye uyum: çözülen nesneyi döndür
        return $hedef;
    }
}
