<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\System\Discovery\Base\Traits;

/**
 * DiscoveryActionsTrait - The Genetic Code of RBN Discovery 🧬🛰️🎡
 * 
 * RBN 3.5 "Masterpiece": [CENTRALIZED SOVEREIGNTY]
 * Encapsulates the core discovery septet and delegates the final 
 * resolution to a context-specific bridge.
 */
trait DiscoveryActionsTrait
{
    /**
     * Internal Bridge: Resolve discovery request based on concrete context. 🌉
     */
    abstract protected function resolveDiscovery(string $type, string $name);

    /**
     * Discover and get a Controller resolution. 🏗️🎯🛰️⚓
     */
    public function controller(string $name)
    {
        return $this->resolveDiscovery('controller', $name);
    }

    /**
     * Discover and get a Service instance. 🏛️
     */
    public function service(string $name)
    {
        return $this->resolveDiscovery('service', $name);
    }

    /**
     * Discover and get a Model instance. 🏺
     */
    public function model(string $name)
    {
        return $this->resolveDiscovery('model', $name);
    }

    /**
     * Discover and get a Helper instance. 🎻
     */
    public function helper(string $name)
    {
        return $this->resolveDiscovery('helper', $name);
    }

    /**
     * Discover and get a Query (Read/View) instance. 🏹
     */
    public function queries(string $name)
    {
        return $this->resolveDiscovery('queries', $name);
    }

    /**
     * Discover and get a Handler (Logic) instance. 🎡
     */
    public function handler(string $name)
    {
        return $this->resolveDiscovery('handler', $name);
    }

    /**
     * Discover and get a Metadata Constant. 🧬
     */
    public function constant(string $name)
    {
        return $this->resolveDiscovery('constant', $name);
    }

    /**
     * Discover and get Validation DNA. 💎
     */
    public function validation(string $path)
    {
        return $this->resolveDiscovery('validation', $path);
    }

    /**
     * Discover and get a Provider instance. 🏗️
     */
    public function provider(string $name)
    {
        return $this->resolveDiscovery('provider', $name);
    }

    /**
     * Discover and get a Repository instance. 📦
     */
    public function repository(string $name)
    {
        return $this->resolveDiscovery('repository', $name);
    }

    /**
     * Discover and get a Preset instance. 🎨
     */
    public function preset(string $name)
    {
        return $this->resolveDiscovery('preset', $name);
    }

    /**
     * Discover and get a Rule instance. 🛡️
     */
    public function rule(string $name)
    {
        return $this->resolveDiscovery('rule', $name);
    }

    /**
     * Discover and get a Prompt instance. 📝
     */
    public function prompt(string $name)
    {
        return $this->resolveDiscovery('prompt', $name);
    }

    /**
     * Discover and get a Manager (System Logic) instance. 🏛️🛰️⚓
     */
    public function manager(string $name)
    {
        return $this->resolveDiscovery('manager', $name);
    }

    /**
     * Discover and get a CLI Command instance. 🏹
     */
    public function command(string $name)
    {
        return $this->resolveDiscovery('command', $name);
    }

    /**
     * Discover and get a Core Cluster (Engine/Architectural Motor) instance. 🪐⚓
     * 
     * RBN 3.5 "Masterpiece": [DECENTRALIZED ENGINES]
     */
    public function cluster(string $name)
    {
        return $this->resolveDiscovery('cluster', $name);
    }

    /**
     * Discover and get a Resolver instance. 🧠🛰️⚓
     */
    public function resolver(string $name)
    {
        return $this->resolveDiscovery('resolver', $name);
    }

    /**
     * Discover and get a Builder instance. 🏗️
     */
    public function builder(string $name)
    {
        return $this->resolveDiscovery('builder', $name);
    }

    /**
     * Discover and get a Job (Sistem/Arka Plan Görevi) instance. 🛠️
     */
    public function job(string $name)
    {
        return $this->resolveDiscovery('job', $name);
    }

    /**
     * Discover and get a Cron Task (Otonom Görev) instance. 🛰️
     */
    public function task(string $name)
    {
        return $this->resolveDiscovery('task', $name);
    }

    /**
     * Discover and get a Queue instance. ⚡
     */
    public function queue(string $name)
    {
        return $this->resolveDiscovery('queue', $name);
    }

    /**
     * Discover and get an Event instance. 🔔
     */
    public function rbnEvent(string $name)
    {
        return $this->resolveDiscovery('event', $name);
    }

    /**
     * Discover and get a Listener instance. 🎧
     */
    public function listener(string $name)
    {
        return $this->resolveDiscovery('listener', $name);
    }

    /**
     * Discover and get an Action instance. 🎬
     */
    public function action(string $name)
    {
        return $this->resolveDiscovery('action', $name);
    }

    /**
     * Discover and get a Driver component instance. 🚗
     */
    public function rbnDriver(string $name)
    {
        return $this->resolveDiscovery('driver', $name);
    }
}
