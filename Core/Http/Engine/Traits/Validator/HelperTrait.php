<?php
/**
 * RBN Framework 3.0: High-Performance Http Engine 🎻⚓
 */
namespace Rbn\Framework\Core\Http\Engine\Traits\Validator;

/**
 * HelperTrait - The Support Hub 🛰️
 * 
 * Handles error messaging, field naming, and utility functions.
 */
trait HelperTrait
{
    /**
     * Add a validation error for a specific field ❌
     */
    protected function addError(string $field, string $rule, array $params = []): void
    {
        $messageKey = "{$field}.{$rule}";
        $message = $this->messages[$messageKey] ?? $this->messages[$rule] ?? $this->getDefaultMessage($rule, $field, $params);

        $this->errors[$field][] = $message;
    }

    /**
     * Resolve the default error message for a rule 📝
     */
    protected function getDefaultMessage(string $rule, string $field, array $params = []): string
    {
        $fieldDisplayName = $this->getFieldDisplayName($field);

        // Fetch specialized DNA from the Discovery Bridge (e.g. form.REQUIRED) 🔎⚡
        $msg = $this->validation('form.' . strtoupper($rule)) ?? "{field} alanı geçersiz.";

        // Replace placeholders
        $msg = str_replace('{field}', $fieldDisplayName, $msg);

        if (isset($params[0])) {
            $msg = str_replace(['{min}', '{max}', '{param}'], $params[0], $msg);
        }

        return $msg;
    }

    /**
     * Get the display-friendly name of a field 🏷️
     */
    public function getFieldDisplayName(string $field): string
    {
        $fConst = $this->constant('fieldType');
        $fieldNames = $fConst ? $fConst::FIELD_DISPLAY_NAMES : [];
        return $fieldNames[$field] ?? ucfirst($field);
    }

    /**
     * Sanitize input data (Clean HTML and trim) 🧼
     */
    public function sanitizeInput($input): string
    {
        if (!is_string($input))
            return (string) $input;

        $cleaned = strip_tags($input);
        $cleaned = htmlspecialchars($cleaned, ENT_QUOTES, 'UTF-8');
        return trim($cleaned);
    }
}
