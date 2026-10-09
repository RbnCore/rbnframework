<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Base\Services;

use Rbn\Framework\Core\Support\Contracts\Base\BaseServiceInterface;
use Rbn\Framework\Core\Base\Services\Traits\Service\ActionServiceTrait;
use Rbn\Framework\Core\Base\BaseComponent;

/**
 * BaseService - Foundation for all Framework Services 🏛️⚓
 * 
 * RBN Framework: Master Orchestrator.
 * Artık BaseComponent hiyerarşisine tam uyumludur ve Root DNA (bootBaseContext) sistemini kullanır.
 */
abstract class BaseService extends BaseComponent implements BaseServiceInterface
{
    use ActionServiceTrait;

    /**
     * Standard Boot Sequence ⚓
     * Default implementation to satisfy BaseServiceInterface.
     */
    public function boot(): void
    {
        // Service is ready.
    }

    /** --- Core Vitals (RBN Framework Singleton Hub) 🧬🏛️ --- */
    protected static ?self $instance = null;

    /**
     * Set the current active controller 🎮⚓
     */
    public function setActiveController(object $controller): void
    {
        $this->activeController = $controller;
    }

    /**
     * Get the current active controller 🎮⚓
     */
    public function activeController(): ?object
    {
        return $this->activeController;
    }

    /**
     * Construct the Service (Synchronize with Orchestra) ⚓🧬🚀
     */
    public function __construct(?BaseService $rbn = null)
    {
        // 1. Singleton Management (DNA başlamadan önce Hub kaydı yapılmalı!) 🎻
        if (self::$instance === null) {
            self::$instance = $this;
        }

        // 2. Kök DNA'yı (BaseComponent) ayağa kaldır 🚀🚩
        parent::__construct($rbn);

        // 3. Boot the Service (Özel servis hazırlığı)
        $this->boot();
    }

    /**
     * Get the Grand Orchestrator instance 🎻🏙️⚓
     */
    public static function get(): self
    {
        if (self::$instance === null) {
            // RBN Framework: Anonymous Hub Awakening 🎻🏛️⚓
            self::$instance = new #[\AllowDynamicProperties] class extends BaseService {
                public function boot(): void
                {
                }
            };
        }
        return self::$instance;
    }

    /**
     * RBN Framework Delegation Engine (RBN Framework Proxy) 🛰️🎻⚓
     * 
     * RBN Framework: [ATOMIC DELEGATION]
     * Serviste bulunmayan metodları otonom olarak bağlı olduğu Provider'a iletir.
     * Bu sayede servisler 'Zero-Code Proxy' olarak davranabilir.
     */
    /**
     * Singleton ornegini SIFIRLA (reset API'si)
     *
     * B-18: `$instance` TEK statik slot (`static::class` DEGIL) oldugu icin
     * test izolasyonu imkansizdi. Bu metot YALNIZCA test/teardown tarafindan
     * cagrilir.
     *
     * DAVRANIS ETKISI: cagrilmadigi surece singleton AYNEN calisir.
     * Ornekleri `static::class` ile tip bazli anahtarlamak DAVRANIS
     * DEGISTIRICI bir karardir ve bilincli olarak YAPILMADI (karar: team member).
     */
    public static function forgetInstance(): void
    {
        self::$instance = null;
    }

    public function __call(string $method, array $args)
    {
        // 1. Otonom Kimlik Çıkarımı (Entity Name) 🏷️
        $entityName = (new \ReflectionClass($this))->getShortName();
        $entityName = str_replace('Service', '', $entityName);
        $providerKey = "{$entityName}Provider";

        // 2. Birincil Provider'ı Keşfet ve Mühürle 🛰️⚓
        // Provider zorunlu olmadığı için diagnostic fırlatılmadan sessizce çözümlenir
        $provider = $this->component('provider', $providerKey, false);

        // 3. Delegasyon (RBN Framework Proxying) 🚀
        if ($provider && method_exists($provider, $method)) {
            return call_user_func_array([$provider, $method], $args);
        }

        throw new \Exception("Service Error: [{$method}] metodu ne [" . static::class . "] servisinde ne de bağlı [" . ($provider ? get_class($provider) : 'N/A') . "] provider'ında bulunamadı! ❌");
    }
}
