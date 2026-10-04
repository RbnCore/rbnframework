<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnPipeline\Contracts;

use Rbn\Framework\Packages\RbnPipeline\Builders\PromptBuilder;

/**
 * PromptPresetInterface - Contract for reusable prompt presets 🛡️⚓
 */
interface PromptPresetInterface
{
    /**
     * Apply the preset rules and context configurations to the PromptBuilder
     */
    public function apply(PromptBuilder $builder, array $context = []): PromptBuilder;
}
