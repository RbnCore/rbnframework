<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnPipeline\Builders;

use Rbn\Framework\Core\Base\BaseComponent;
use Rbn\Framework\Packages\RbnPipeline\Contracts\PromptRuleInterface;

/**
 * PromptBuilder - Fluid builder to compile task and rules together 🧠🛰️⚓
 */
class PromptBuilder extends BaseComponent
{
    /** @var PromptRuleInterface[] */
    private array $rules = [];
    private string $taskInstruction = '';
    protected array $promptContext = [];

    public function reset(): self
    {
        $this->rules = [];
        $this->taskInstruction = '';
        $this->promptContext = [];
        return $this;
    }

    public function applyRule(PromptRuleInterface $rule): self
    {
        $ruleClass = get_class($rule);
        foreach ($this->rules as $existingRule) {
            if (get_class($existingRule) === $ruleClass) {
                return $this;
            }
        }

        $this->rules[] = $rule;
        return $this;
    }

    public function json(?array $schema = null): self
    {
        $rule = $this->rule('promptRule.json');
        if ($schema !== null) {
            $rule->setSchema($schema);
        }
        return $this->applyRule($rule);
    }

    public function seo(): self
    {
        return $this->applyRule($this->rule('promptRule.seo'));
    }

    public function image(): self
    {
        return $this->applyRule($this->rule('promptRule.image'));
    }

    public function imagePrompt(): self
    {
        $this->withContext(['image_prompt_mode' => 'metadata']);
        return $this->applyRule($this->rule('promptRule.image'));
    }

    public function innerLinking(): self
    {
        return $this->applyRule($this->rule('promptRule.innerLinking'));
    }

    public function blogStructure(): self
    {
        return $this->applyRule($this->rule('promptRule.blogStructure'));
    }

    public function writingTone(): self
    {
        return $this->applyRule($this->rule('promptRule.writingTone'));
    }

    public function identity(): self
    {
        return $this->applyRule($this->rule('promptRule.identity'));
    }

    public function contentQuality(): self
    {
        return $this->applyRule($this->rule('promptRule.contentQuality'));
    }

    /**
     * Apply a preset configuration class (e.g. 'blog', 'youtube') to the builder
     */
    public function preset(string $name, array $context = []): self
    {
        return $this->component('preset', $name)->apply($this, $context);
    }

    public function youtube(): self
    {
        return $this->applyRule($this->rule('promptRule.youtube'));
    }

    public function task(string $taskInstruction): self
    {
        if (!empty($this->taskInstruction)) {
            $this->taskInstruction .= "\n\n" . $taskInstruction;
        } else {
            $this->taskInstruction = $taskInstruction;
        }
        return $this;
    }

    public function withContext(array $context): self
    {
        $this->promptContext = array_merge($this->promptContext, $context);
        return $this;
    }

    /**
     * Compiles all rules and core task instructions into a single prompt string
     */
    public function compile(): string
    {
        $compiled = $this->taskInstruction;

        foreach ($this->rules as $rule) {
            $compiled .= $rule->compileInstructions($this->promptContext);
        }

        return $compiled;
    }

    public function sanitize(string $response): string
    {
        foreach ($this->rules as $rule) {
            $response = $rule->sanitizeResponse($response, $this->promptContext);
        }
        return $response;
    }
}
