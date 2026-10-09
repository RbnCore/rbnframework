<?php
declare(strict_types=1);

namespace Rbn\Framework\Core\Services\Exception\Concerns;

/**
 * ErrorHandlingTrait - Unified Resilience & State Capability 🧰🛡️
 * 
 * RBN Framework: Standard (Shield Integrated).
 * Standardizes error collection and provides a bridge to the central Shield Hub.
 * 
 * Part of the Core Service Exception Concerns layer.
 */
trait ErrorHandlingTrait
{
    /**
     * @var array Validation/Internal errors
     */
    protected array $errors = [];

    /**
     * Add a validation or logic error 📝
     */
    public function addError(string $field, string $message): void
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        $this->errors[$field][] = $message;
    }

    /**
     * Get all collected errors 🔍
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Check if there are any errors 🚦
     */
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Clear the error state 🧹
     */
    public function clearErrors(): void
    {
        $this->errors = [];
    }

    public function fail(string $message = 'The operation has failed.'): void
    {
        shield()->validation($this->getErrors(), $message);
    }

    /**
     * Terminate the request with a semantic status code (DNA Gateway) 🏛️🛡️⚓
     * RBN Framework: Direct bridge to the Shield Hub.
     */
    public function abort(int $code = 404, string $message = ''): void
    {
        shield()->abort($code, $message);
    }

    /**
     * Helper: Success response (Standard RBN Array) 🟢
     */
    public function sendSuccess(string $msg, array $data = []): array
    {
        return array_merge([
            'success' => true,
            'message' => $msg
        ], $data);
    }

    /**
     * Helper: Manual error response (Standard RBN Array) 🔴
     */
    public function sendError(string $msg, array $extra = []): array
    {
        return array_merge([
            'success' => false,
            'message' => $msg
        ], $extra);
    }
}
