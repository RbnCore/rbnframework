<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Base\Services\Traits\Provider;

/* --- Provider Engine Components --- */
use Rbn\Framework\Core\Base\Services\Traits\Provider\Engine\CrudProviderTrait;
use Rbn\Framework\Core\Base\Services\Traits\Provider\Engine\BulkProviderTrait;
use Rbn\Framework\Core\Base\Services\Traits\Provider\Engine\ReadProviderTrait;

/**
 * ActionProviderTrait - The Unified Provider Action Hub 🪐🏎️
 * 
 * RBN Framework: Strategic Hub that composes only the provider's mutation and retrieval engines.
 * Keeps the core context logic (HTTP, Service, Storage) separate.
 */
trait ActionProviderTrait
{
    use CrudProviderTrait,
        BulkProviderTrait,
        ReadProviderTrait;


}
