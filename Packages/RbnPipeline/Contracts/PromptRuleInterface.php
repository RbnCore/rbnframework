<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnPipeline\Contracts;

/**
 * PromptRuleInterface - Contract for modular Prompt rules 🛡️⚓
 */
interface PromptRuleInterface
{
    /**
     * Get instructions to append to the system or task prompt
     */
    public function compileInstructions(array $context = []): string;

    /**
     * Sanitize or post-process the AI response
     */
    public function sanitizeResponse(string $response, array $context = []): string;
}
