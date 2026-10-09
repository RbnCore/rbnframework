<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Base\Data\Traits\Model;

/* --- Model Engine Components --- */
use Rbn\Framework\Core\Base\Data\Traits\Model\Engine\CrudModelTrait;
use Rbn\Framework\Core\Base\Data\Traits\Model\Engine\BulkModelTrait;
use Rbn\Framework\Core\Base\Data\Traits\Model\Engine\MassAssignmentTrait;
use Rbn\Framework\Core\Base\Data\Traits\Model\Engine\ReadModelTrait;
use Rbn\Framework\Core\Base\Data\Traits\Model\Engine\RelationModelTrait;
use Rbn\Framework\Core\Base\Data\Traits\Model\Engine\QueryModelTrait;
use Rbn\Framework\Core\Base\Data\Traits\Model\Engine\TimestampModelTrait;
use Rbn\Framework\Core\Base\Data\Traits\Model\Engine\TransactionModelTrait;
use Rbn\Framework\Core\Base\Data\Traits\Model\Engine\JsonModelTrait;

/**
 * ActionModelTrait - The Unified Model Powerhouse 🪐🔋
 * 
 * RBN Framework: Strategic Hub that composes all model engines.
 * Centralized under Data hierarchy.
 */
trait ActionModelTrait
{
    use CrudModelTrait,
    BulkModelTrait,
    MassAssignmentTrait,
    ReadModelTrait,
    RelationModelTrait,
    TimestampModelTrait,
    TransactionModelTrait,
    JsonModelTrait,
    QueryModelTrait {
        CrudModelTrait::update insteadof QueryModelTrait;
    }
}
