<?php
declare(strict_types=1);

namespace Rbn\Framework\Core\Support\Exceptions;

/**
 * DiagnosticException - The Doctor's Diagnosis 🩺🏛️
 * 
 * Part of the RBN Framework Survival Layer.
 * A specialized exception used by Guards (Doctors) to pass 
 * diagnostic metadata to the Global Exception Service (Nurse).
 */
class DiagnosticException extends \Exception
{
    protected string $type;
    protected string $hint;

    public function __construct(string $type, string $message, string $hint = '', int $code = 500)
    {
        parent::__construct($message, $code);
        $this->type = $type;
        $this->hint = $hint;
    }

    /** --- getters --- */
    public function getType(): string { return $this->type; }
    public function getHint(): string { return $this->hint; }

    /**
     * Standardized render data for the Nurse.
     */
    public function getRenderData(): array
    {
        return [
            'type' => $this->type,
            'message' => $this->getMessage(),
            'hint' => $this->hint,
            'code' => $this->getCode()
        ];
    }
}
