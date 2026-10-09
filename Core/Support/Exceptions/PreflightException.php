<?php
declare(strict_types=1);

namespace Rbn\Framework\Core\Support\Exceptions;

/**
 * PreflightException - The Framework's Initialization Guard 🛡️🚀
 * 
 * Part of the RBN Framework Layer 1 (Diagnostic) shield.
 * Specifically used for errors occurring during the Pre-flight/Early Boot phase
 * (Missing configuration, incorrect paths, environment setup issues).
 */
class PreflightException extends DiagnosticException
{
    public function __construct(string $message, string $hint = '', string $type = 'RbnShield Pre-Flight', int $code = 500)
    {
        parent::__construct($type, $message, $hint, $code);
    }
}
