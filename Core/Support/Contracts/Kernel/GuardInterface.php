<?php
declare(strict_types=1);

namespace Rbn\Framework\Core\Support\Contracts\Kernel;

/**
 * GuardInterface - Standard for Kernel Survival Guards 🛡️🧬
 * 
 * Part of the RBN 3.5 Industrial Strength Architecture.
 * Ensures every diagnostic guard has a standard check and failure mechanism.
 */
interface GuardInterface
{
    /**
     * Executes the specific diagnostic check for the guard.
     */
    public static function check(): void;
}
