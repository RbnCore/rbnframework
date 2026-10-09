<?php
declare(strict_types=1);

namespace Rbn\Framework\Core\Support\Exceptions;

/**
 * ValidationException - Unified Validation Response 🧬⚖️
 * 
 * RBN Framework: Standard (Modernized).
 * Centralized exception for carrying validation error arrays and redirect URLs.
 * 
 * Part of the Layer 4 (Functional) and Layer 2 (Development) shield.
 */
class ValidationException extends DiagnosticException
{
    protected array $errors;
    protected string $redirectUrl;

    public function __construct(string $message, array $errors = [], ?string $redirectUrl = null)
    {
        $this->errors = $errors;
        $this->redirectUrl = $redirectUrl ?? ($_SERVER['HTTP_REFERER'] ?? '/');

        parent::__construct(
            "Form Validation Failure",
            $message,
            "Please check the user input against the defined Rule sets. Ensure that the required fields are present and follow the expected format.",
            422
        );
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getRedirectUrl(): string
    {
        return $this->redirectUrl;
    }
}
