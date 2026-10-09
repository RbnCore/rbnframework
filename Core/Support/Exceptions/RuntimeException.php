<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Support\Exceptions;

/**
 * RuntimeException - Operational Framework Failure 🌪️🩹
 * 
 * RBN Framework: Strategic Exception Layer.
 * Part of the RBN "RBN Framework" Error Management system.
 * Used for errors that occur during the actual execution of the framework logic.
 */
class RuntimeException extends DiagnosticException
{
    /**
     * RuntimeException Constructor
     * 
     * @param string $message The error message
     * @param string $hint A helpful diagnostic hint for the user
     * @param int $code The HTTP or System error code
     */
    public function __construct(string $message, string $hint = 'Sistem operasyonu sırasında beklenmedik bir hata oluştu.', int $code = 500)
    {
        // RBN Standard: Map to the RUNTIME_ERROR diagnostic type.
        parent::__construct('RUNTIME_ERROR', $message, $hint, $code);
    }
}
