<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Base\Attributes;

use Attribute;
use Rbn\Framework\Core\Base\BaseAttribute;

/**
 * Component Attribute - Unified Discovery Engine 🏛️🛰️⚓
 * RBN 3.5 Masterpiece Standard.
 * 
 * Used to declare any project-level component (Model, Service, Handler, Provider, etc.)
 * and its system identity (alias) for autonomous registration.
 */
#[Attribute(Attribute::TARGET_CLASS)]
class Component extends BaseAttribute
{
    /**
     * @param string $alias   Bileşenin sistemdeki takma adı (örn: blog, user_service)
     * @param string $type    Bileşen tipi (model, service, provider, handler, manager)
     * @param bool   $cache   Cache desteği aktif mi?
     */
    public function __construct(
        public ?string $alias,
        public string $type,
        public bool $cache = false
    ) {
        parent::__construct(['alias' => $alias]);
    }
}
