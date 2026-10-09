<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\System\Kernel\Base;

use Rbn\Framework\Core\Support\Contracts\Kernel\StageInterface;
use Rbn\Framework\Core\System\Kernel\Kernel;

/**
 * BaseStage - The Foundation for all Kernel Boot Stages 🏛️🎭⚓
 * 
 * RBN Framework: Provides common utilities for all orchestrators in the boot pipeline.
 */
abstract class BaseStage implements StageInterface
{
    /**
     * Strategic Service Bridge 🏛️🛰️⚓
     * Provides a lightweight access to the Grand Orchestrator without BaseComponent overhead.
     */
    protected function getStageService(): \Rbn\Framework\Core\Base\Services\BaseService
    {
        return \Rbn\Framework\Core\Base\Services\BaseService::get();
    }

    /**
     * Executes the specific boot stage logic.
     * Must be implemented by children.
     */
    abstract public function handle(Kernel $kernel): void;
}
