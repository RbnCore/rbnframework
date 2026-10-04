<?php

namespace Rbn\Framework\Core\Support\Contracts\Kernel;

use Rbn\Framework\Core\System\Kernel\Kernel;

/**
 * StageInterface - Contract for all boot stages.
 */
interface StageInterface
{
    /**
     * Executes the specific boot stage logic.
     * 
     * @param Kernel $kernel The kernel instance for context sharing.
     */
    public function handle(Kernel $kernel): void;
}
