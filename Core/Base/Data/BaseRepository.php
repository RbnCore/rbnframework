<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Base\Data;

use Rbn\Framework\Core\Base\BaseComponent;
use Rbn\Framework\Core\Base\Services\Traits\Provider\ActionProviderTrait;
use Rbn\Framework\Core\Base\Data\Traits\Repository\ContentQueryTrait;
use Rbn\Framework\Core\Base\Data\Traits\Repository\ContentCacheTrait;

/**
 * BaseRepository - Abstract Foundation for Enterprise Data Repositories 🏛️📦⚓
 * 
 * RBN 3.5 Masterpiece: Base foundation for all database repositories.
 * Located strictly under Core\Base\Data right beside BaseModel for architecture purity.
 */
abstract class BaseRepository extends BaseComponent
{
    use ActionProviderTrait, ContentQueryTrait, ContentCacheTrait;
}
