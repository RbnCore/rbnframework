<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Support\Exceptions;

/**
 * ProjectSuspendedException - The Platform Level Access Shield 🛡️🚫
 * 
 * Specifically thrown when a project is found in the master registry 
 * but has a 'passive' or 'suspended' status.
 */
class ProjectSuspendedException extends DiagnosticException
{
    public function __construct(string $message, string $hint = '', string $type = 'RbnShield Project Status', int $code = 403)
    {
        parent::__construct($type, $message, $hint, $code);
    }
}
