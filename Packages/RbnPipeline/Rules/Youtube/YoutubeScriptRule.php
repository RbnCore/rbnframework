<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnPipeline\Rules\Youtube;

use Rbn\Framework\Packages\RbnPipeline\Contracts\PromptRuleInterface;

/**
 * YoutubeScriptRule - Appends standards for YouTube scripts 🎥
 */
class YoutubeScriptRule implements PromptRuleInterface
{
    public function compileInstructions(array $context = []): string
    {
        return "\n[YOUTUBE SCRIPT RULES]\nStructure the script with a strong 5-second hook introduction, structured sections with visual cue markers (e.g. [Visual: ...]), and a clear call-to-action (CTA) at the end. Keep the tone friendly, conversational, and energetic.\n";
    }

    public function sanitizeResponse(string $response, array $context = []): string
    {
        return $response;
    }
}
