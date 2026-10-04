<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Base\Attributes;

use Attribute;
use Rbn\Framework\Core\Base\BaseAttribute;

/**
 * SubModule Attribute - The Sovereign Identity Anchor ⚖️🏛️⚓
 * 
 * RBN 3.5 Masterpiece: Enables point-target component discovery.
 * Eliminates redundant properties in controllers and services.
 */
#[Attribute(Attribute::TARGET_CLASS)]
class SubModule extends BaseAttribute
{
    /**
     * RBN 3.5: Constructor with backward compatibility and zero conflict. 🛡️⚓
     * Maps original names to the internal $metadata hub to avoid BaseComponent property collisions.
     */
    public function __construct(
        ?string $module = null,
        ?string $entity = null,
        ?string $service = null,
        ?string $provider = null,
        ?string $repository = null,
        ?string $model = null,
        ?string $handler = null,
        ?string $manager = null,
        public ?string $data = null,
        public ?string $entityName = null,
        public ?string $bulkInputKey = null,
        public ?string $modal = null
    ) {
        // 🎼 DNA Injection: Delegate to the Sovereign Ancestor 🏛️🛰️⚓
        parent::__construct([
            'module'     => $module,
            'entity'     => $entity,
            'service'    => $service,
            'provider'   => $provider,
            'repository' => $repository,
            'model'      => $model,
            'handler'    => $handler,
            'manager'    => $manager
        ]);
    }

}
