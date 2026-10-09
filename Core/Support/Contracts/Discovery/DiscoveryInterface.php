<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Support\Contracts\Discovery;

/**
 * DiscoveryInterface - The Universal Contract for Discovery Engine 📽️🛰️🛡️⚓
 * 
 * RBN Framework: [UNIFIED STANDARD]
 * Defines the standard API for resolving framework components, 
 * services, and metadata across all layers of the architecture.
 */
interface DiscoveryInterface
{
    /**
     * Discover and get a Service instance. 🏛️
     */
    public function service(string $name);

    /**
     * Discover and get a Model instance. 🏺
     */
    public function model(string $name);

    /**
     * Discover and get a Helper instance. 🎻
     */
    public function helper(string $name);

    /**
     * Discover and get a Query (Read/View) instance. 🏹
     */
    public function queries(string $name);

    /**
     * Discover and get a Handler (Logic) instance. 🎡
     */
    public function handler(string $name);

    /**
     * Discover and get a Metadata Constant. 🧬
     */
    public function constant(string $name);

    /**
     * Discover and get Validation DNA. 💎
     */
    public function validation(string $path);

    /**
     * Discover and get a Provider instance. 🏗️
     */
    public function provider(string $name);

    /**
     * Discover and get a CLI Command instance. 🏹
     */
    public function command(string $name);

    /**
     * Discover and get a Manager (System Logic) instance. 🏛️
     */
    public function manager(string $name);

    /**
     * Discover and get a Core Cluster instance. 🪐
     */
    public function cluster(string $name);

    /**
     * Discover and get a Resolver instance. 🧠
     */
    public function resolver(string $name);

    /**
     * Discover and get a Builder instance. 🏗️
     */
    public function builder(string $name);

    /**
     * Discover and get a Job instance. 🛠️
     */
    public function job(string $name);

    /**
     * Discover and get a Cron Task instance. 🛰️
     */
    public function task(string $name);

    /**
     * Discover and get a Preset instance. 🎨
     */
    public function preset(string $name);

    /**
     * Discover and get a Rule instance. 🛡️
     */
    public function rule(string $name);

    /**
     * Discover and get a Prompt instance. 📝
     */
    public function prompt(string $name);

    /**
     * Discover and get a Queue instance. ⚡
     */
    public function queue(string $name);

    /**
     * Discover and get an Event instance. 🔔
     */
    public function rbnEvent(string $name);

    /**
     * Discover and get a Listener instance. 🎧
     */
    public function listener(string $name);

    /**
     * Discover and get an Action instance. 🎬
     */
    public function action(string $name);

    /**
     * Discover and get a Driver component instance. 🚗
     */
    public function rbnDriver(string $name);
}
