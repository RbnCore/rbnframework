<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnPipeline\Presets;

use Rbn\Framework\Packages\RbnPipeline\Contracts\PromptPresetInterface;
use Rbn\Framework\Packages\RbnPipeline\Builders\PromptBuilder;

/**
 * YoutubePreset - Standard configuration preset for YouTube scripts 🎥
 */
class YoutubePreset implements PromptPresetInterface
{
    public function apply(PromptBuilder $builder, array $context = []): PromptBuilder
    {
        return $builder->json()
            ->youtube();
    }
}
