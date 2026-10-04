<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnPipeline\Rules\Prompt;

use Rbn\Framework\Core\Base\BaseComponent;
use Rbn\Framework\Packages\RbnPipeline\Concerns\SanitizesResponseTrait;
use Rbn\Framework\Packages\RbnPipeline\Contracts\PromptRuleInterface;

/**
 * JsonResponseRule - Sovereign JSON Formatting & Autonomous Repair Hub 🧱🧼🛠️
 * 
 * RBN 3.5 Masterpiece Standard.
 * Centralizes all markdown JSON wrapper stripping, unnesting, control character sanitization,
 * unescaped quote repairing, and array decoding for all AI operations across the framework.
 */
class JsonResponseRule extends BaseComponent implements PromptRuleInterface
{
    use SanitizesResponseTrait;

    private ?array $schema = null;

    public function setSchema(array $schema): self
    {
        $this->schema = $schema;
        return $this;
    }

    public function compileInstructions(array $context = []): string
    {
        $instruction = "\n[FORMATTING RULE]\nReturn the output strictly as a valid JSON object or array. Do not include markdown code block formatting (like ```json ... ```) or any trailing explanations. Return only raw JSON.\n";
        if ($this->schema) {
            $instruction .= "Expected JSON Schema Structure:\n" . json_encode($this->schema, JSON_PRETTY_PRINT) . "\n";
        }
        return $instruction;
    }

    public function sanitizeResponse(string $response, array $context = []): string
    {
        $clean = trim($response);
        if (str_starts_with($clean, '```')) {
            $clean = preg_replace('/^```(?:json)?\s*/i', '', $clean);
            $clean = preg_replace('/\s*```$/', '', $clean);
        }
        return trim($clean);
    }
}
