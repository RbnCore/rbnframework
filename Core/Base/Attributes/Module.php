<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Base\Attributes;

use Attribute;
use Rbn\Framework\Core\Base\BaseAttribute;

/**
 * Module - RBN Native PHP 8.1 Attribute 🛰️⚓
 * 
 * RBN 3.5 [BASE]: Declares the identity and metadata of a module at the controller level.
 */
#[Attribute(Attribute::TARGET_CLASS)]
class Module extends BaseAttribute
{
    /**
     * RBN 3.5: Constructor with backward compatibility and zero conflict. 🛡️⚓
     * Maps original names to the internal $metadata hub to avoid BaseComponent property collisions.
     */
    public function __construct(
        public string $name,
        ?string $data = null,
        ?string $service = null,
        ?string $panel = null,
        ?string $context = null,
        public ?string $icon = null,
        public ?string $description = null,
        public ?string $title = null,
        public bool $seo = true
    ) {
        // 🎼 DNA Injection: Delegate to the Sovereign Ancestor 🏛️🛰️⚓
        parent::__construct([
            'data'    => $data,
            'service' => $service,
            'panel'   => $panel,
            'context' => $context
        ]);
    }
}
