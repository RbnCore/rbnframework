<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Base\Services;

use Rbn\Framework\Core\Base\BaseComponent;

/**
 * BaseManager - The Framework System Logic Root 🏛️🛰️⚓
 * 
 * RBN 3.5 Masterpiece: Provides a distinct identity for system-level 
 * management components. Inherits all DNA from BaseComponent.
 */
abstract class BaseManager extends BaseComponent
{
    /**
     * RBN 3.5: [SOVEREIGN LIFECYCLE] 🛫
     * Managers can implement custom boot logic here.
     */
    protected function afterBoot(): void
    {
        // To be implemented by child classes if needed.
    }
}
