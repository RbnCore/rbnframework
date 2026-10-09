<?php

declare(strict_types=1);

namespace Rbn\Framework\Bundles\Internal\Webhub\Services;

use Rbn\Framework\Core\Base\Services\BaseService;

/**
 * FaqService - RBN Framework Orchestrator for FAQ Submodule 🎻🛰️⚓
 * 
 * RBN Framework Standard.
 * Bridges the control layer to the specialized FaqProvider.
 * 
 * @property \Rbn\Framework\Core\Database\Repositories\Project\FaqRepository $projectFaqRepository
 */
class FaqService extends BaseService
{
    /** --- Infrastructure DNA --- */
    protected $targetModel = 'project.faq';
    protected array $cacheKeys = ['faq'];
}
