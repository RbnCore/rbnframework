<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Base\Data\Traits\Config;

use Rbn\Framework\Core\Base\Data\Traits\Config\Engine\DataConfigTrait;
use Rbn\Framework\Core\Base\Data\Traits\Config\Engine\DiscoveryConfigTrait;

/**
 * ActionConfigTrait - Configuration Management Hub ⚙️🛰️
 * 
 * RBN Framework: Configuration logic.
 * Orchestrates Data, Discovery and Mapping engine traits.
 */
trait ActionConfigTrait
{
    use DataConfigTrait,
        DiscoveryConfigTrait;
}
