<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Support\Contracts\Base;

/**
 * BaseConfigInterface - The Grand Contract for RBN Framework Configs ⚙️🎻🛰️
 * 
 * RBN Framework: Otonom Discovery (Keşif) mimarisine göre modernize edildi.
 * Artık manuel kayıt (services, models vb.) metotlarını barındırmaz.
 */
interface BaseConfigInterface
{
    /**
     * Set the current registry context (Fluent Navigation) 🛰️⚓
     */
    public function at(string $path): self;

    /**
     * Get a configuration value (Dot-Notation Support) 🎯⚓
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Set a configuration value 🖊️⚓
     */
    public function set(string $key, mixed $value): self;

    /**
     * Check if a configuration key exists ✅⚓
     */
    public function has(string $key): bool;
}
