<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnPipeline\Presets;

use Rbn\Framework\Packages\RbnPipeline\Builders\PromptBuilder;
use Rbn\Framework\Packages\RbnPipeline\Builders\AbstractPresetBuilder;

/**
 * CustomPreset - A dynamic preset allowing custom rules to be applied on the fly 🎛️
 */
class CustomPreset extends AbstractPresetBuilder
{
    public function apply(PromptBuilder $builder, array $context = []): PromptBuilder
    {
        $schema = $context['schema'] ?? [];
        if (!is_array($schema)) {
            $schema = [];
        }

        // Ortak şema ve pipeline kurallarını yükle 🛡️
        $builder = $this->finalizePreset($builder, $schema, $context);

        // Eğer ekstra kurallar belirtilmişse onları ekle (PromptBuilder mükerrerliği çekirdekte engeller) 🚀
        if (!empty($context['rules']) && is_array($context['rules'])) {
            foreach ($context['rules'] as $ruleName) {
                if (method_exists($builder, $ruleName)) {
                    $builder->{$ruleName}();
                } else {
                    $builder->applyRule($builder->rule('promptRule.' . $ruleName));
                }
            }
        }

        return $builder;
    }
}
