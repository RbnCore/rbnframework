<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\System\Discovery\Base;

use Rbn\Framework\Core\Support\Contracts\Discovery\DiscoveryInterface;

/**
 * BaseResolver - Abstract Hierarchical Search Engine 🏛️🛰️⚓
 * 
 * RBN Framework: Direct Lookup Engine.
 * Provides the absolute contract for resource discovery.
 */
abstract class BaseResolver extends BaseDiscoveryContext implements DiscoveryInterface
{
    /**
     * Absolute Resolution Contract 🔍
     * Must return a single metadata object or throw an exception if fatal.
     */
    abstract public function resolve(string $name, ?string $context = null): ?object;
}
