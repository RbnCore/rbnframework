<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\System\Kernel\Base;

use Rbn\Framework\Core\Support\Contracts\Kernel\GuardInterface;
use Rbn\Framework\Core\Support\Exceptions\PreflightException;

/**
 * BaseGuard - The Foundation of all Survival Guards 🏛️🛡️⚓
 * 
 * RBN 3.5 "Masterpiece": Centralizes exception handling
 * for the absolute core diagnostics layer.
 */
abstract class BaseGuard implements GuardInterface
{
    /**
     * Strategic Service Bridge 🏛️🛰️⚓
     * Provides a lightweight access to the Grand Orchestrator without BaseComponent overhead.
     */
    protected static function getGuardService(): \Rbn\Framework\Core\Base\Services\BaseService
    {
        return \Rbn\Framework\Core\Base\Services\BaseService::get();
    }

    /**
     * Executes the specific diagnostic check for the guard.
     * Must be implemented by children.
     */
    abstract public static function check(): void;

    /**
     * Strategic Shortcut: Throw a localized Preflight Exception 🩺🚀
     * 
     * @param string $msg The descriptive error message.
     * @param string $hint The suggested solution for the user.
     * @param string $type The error category (Default: RbnShield)
     * @param int $code The HTTP status code
     */
    protected static function fail(string $msg, string $hint, string $type = 'RbnShield', int $code = 500): void
    {
        throw new PreflightException($msg, $hint, $type, $code);
    }
}
