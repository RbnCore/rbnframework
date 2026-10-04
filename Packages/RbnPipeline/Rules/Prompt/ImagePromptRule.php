<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnPipeline\Rules\Prompt;

use Rbn\Framework\Core\Base\BaseComponent;
use Rbn\Framework\Packages\RbnPipeline\Contracts\PromptRuleInterface;
use Rbn\Framework\Packages\RbnPipeline\Concerns\SanitizesResponseTrait;

/**
 * ImagePromptRule - Enforces English, non-Turkish characters image prompt generation for Imagen 3 🖼️
 */
class ImagePromptRule extends BaseComponent implements PromptRuleInterface
{
    use SanitizesResponseTrait;

    public function compileInstructions(array $context = []): string
    {
        $safetyClass = $this->rule('promptRule.imageSafety');
        $alphabetRule = $safetyClass->getPromptAlphabetInstructions();
        $safetyRule = $safetyClass->getSafetyInstructions();
        $forbiddenVisualsRule = $this->getForbiddenVisualsInstructions();

        $settings = $context['image_settings'] ?? [];
        $settingsRule = "";
        if (!empty($settings)) {
            $style = $settings['style'] ?? 'Cinematic photography';
            $settingsRule = "\n[STYLE CONCEPT]\n- Core Style: \"{$style}\"\n";
            if (!empty($settings['visual_elements'])) {
                $settingsRule .= "- Visual Elements to incorporate:\n";
                foreach ($settings['visual_elements'] as $element) {
                    $settingsRule .= "  * {$element}\n";
                }
            }
            $settingsRule .= "\n";
        }

        return "\n[IMAGE PROMPT GENERATION RULES]\n" .
            "Generate a highly detailed, authentic, photojournalistic image prompt in English for the generation engine (Imagen 3), directly representing the core essence of the topic.\n" .
            "OPTICAL & AESTHETIC DIRECTIVES:\n" .
            "- Camera & Lens: Shot on 35mm / 50mm f/1.8 prime lens, realistic depth of field, sharp subject focus with subtle natural bokeh.\n" .
            "- Lighting: Soft directional daylight or professional editorial studio lighting, natural dynamic range, authentic shadows (avoid flat lighting).\n" .
            "- Texture & Realism: Hyper-realistic organic textures, authentic skin tones/materials, subtle film grain (avoid plastic/airbrushed CGI look).\n" .
            "- Anti-AI-Slop Visual Rules: ABSOLUTELY NO generic glowing purple/cyan neon lines, NO floating glowing brains, NO cliché robotic mannequins, NO synthetic stock-photo smiles.\n" .
            "{$settingsRule}" .
            "The prompt MUST describe a full-bleed, edge-to-edge, screen-filling visual.\n" .
            "{$forbiddenVisualsRule}\n" .
            "{$alphabetRule}\n" .
            "{$safetyRule}\n";
    }

    public function sanitizeResponse(string $response, array $context = []): string
    {
        $data = $this->decodeJson($response);
        if ($data !== null) {
            if (isset($data['image_prompt']) && is_string($data['image_prompt']) && trim($data['image_prompt']) !== '') {
                $data['image_prompt'] = $this->cleanAndEnglish($data['image_prompt']);
            } else {
                $titleKey = $context['seo_title_key'] ?? 'title';
                $title = $data[$titleKey] ?? $this->appName ?? 'Content';
                $data['image_prompt'] = "A cinematic, high-detail, realistic photograph representing " . $this->cleanAndEnglish($title) . ", professional lighting, 8k resolution, no text, full-bleed, edge-to-edge, screen-filling, no borders, no frames, no margins, no letterboxing";
            }
            return $this->encodeJson($data);
        }

        return $this->cleanAndEnglish($response);
    }

    /**
     * Görsel içinde istenmeyen yerleşim ve sunum biçimlerini yöneten liste 🚫📺🖼️
     */
    private function getForbiddenVisualsInstructions(): string
    {
        $forbiddenItems = [
            'device frames around the canvas (no outer phone/laptop bezel enclosing the whole image)',
            'mockups (no laptop/phone mockup frames enclosing the visual)',
            'drop shadows',
            'white borders',
            'black borders',
            'canvas frames',
            'margins',
            'padding',
            'letterboxing',
            'text, writing, words, typography, letters, brand logos, or trademarked logos on the image itself'
        ];

        $forbiddenList = implode(', ', $forbiddenItems);

        return "CRITICAL: The generated image MUST be a full-bleed, edge-to-edge photograph/visual filling the entire canvas. FORBIDDEN: {$forbiddenList}. NOTE: Generic, unbranded, noname devices (like a sleek generic smartphone, generic laptop, or generic UI screen) CAN be depicted in the scene if relevant to the topic, but the visual ITSELF must not be framed or placed inside a device mockup frame.";
    }
}
