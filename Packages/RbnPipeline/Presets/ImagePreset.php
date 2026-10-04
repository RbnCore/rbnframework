<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnPipeline\Presets;

use Rbn\Framework\Core\Base\BaseComponent;
use Rbn\Framework\Packages\RbnPipeline\Contracts\PromptPresetInterface;
use Rbn\Framework\Packages\RbnPipeline\Builders\PromptBuilder;

/**
 * ImagePreset - Clean structured compilation preset for direct image generation 🎨🚀
 */
class ImagePreset extends BaseComponent implements PromptPresetInterface
{
    public function apply(PromptBuilder $builder, array $context = []): PromptBuilder
    {
        return $builder->applyRule($builder->rule('promptRule.imageCompiler'))
            ->withContext($context);
    }
}
