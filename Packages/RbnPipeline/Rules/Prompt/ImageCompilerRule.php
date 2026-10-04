<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnPipeline\Rules\Prompt;

use Rbn\Framework\Core\Base\BaseComponent;
use Rbn\Framework\Packages\RbnPipeline\Contracts\PromptRuleInterface;
use Rbn\Framework\Packages\RbnPipeline\Concerns\SanitizesResponseTrait;

/**
 * ImageCompilerRule - Compiles clean image prompts with standardized style templates for Imagen 🎨🛰️
 */
class ImageCompilerRule extends BaseComponent implements PromptRuleInterface
{
    use SanitizesResponseTrait;

    public function compileInstructions(array $context = []): string
    {
        $settings = $context['image_settings'] ?? [];
        $styleList = [];
        $visualElements = [];
        
        if (!empty($settings)) {
            if (!empty($settings['style'])) {
                $styleList[] = trim($settings['style']);
            }
            if (!empty($settings['visual_elements']) && is_array($settings['visual_elements'])) {
                foreach ($settings['visual_elements'] as $element) {
                    $elementClean = trim($element);
                    if (stripos($elementClean, 'style:') === 0) {
                        $styleList[] = trim(substr($elementClean, 6));
                    } elseif (stripos($elementClean, 'composition:') === 0) {
                        $styleList[] = trim(substr($elementClean, 12));
                    } else {
                        $visualElements[] = $elementClean;
                    }
                }
            }
        }
        
        $compiled = "";
        if (!empty($styleList)) {
            $styleList = array_unique($styleList);
            $compiled .= ", style: " . implode(', ', $styleList);
        }
        if (!empty($visualElements)) {
            $compiled .= ", visual elements: " . implode(', ', $visualElements);
        }
        
        return $compiled;
    }
    
    public function sanitizeResponse(string $response, array $context = []): string
    {
        return $this->cleanAndEnglish($response);
    }
}
