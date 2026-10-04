<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnPipeline\Rules\Prompt;

use Rbn\Framework\Core\Base\BaseComponent;

/**
 * ImageSafetyRule - Core safety boundaries and forbidden words for image prompts.
 * RBN 3.5 Masterpiece Standard.
 */
class ImageSafetyRule extends BaseComponent
{
    /**
     * Yasaklı kelimeler listesi. Yeni bir kural ekleneceğinde sadece bu diziye eklenmesi yeterlidir. 🛡️
     */
    public const FORBIDDEN_WORDS = [
        'tree of knowledge',
        'eden',
        'adam',
        'eve',
        'god',
        'angel',
        'bacteria',
        'virus',
        'disease',
        'toxicity',
        'hazardous chemicals',
        'warning',
        'bomb',
        'explosion',
        'weapon',
        'gun',
        'blood',
        'wound',
        'injury',
        'danger',
        'death',
        'kill',
        'devil',
        'demon',
        'satan',
        'hell',
        'heaven',
        'poison',
        'toxic',
        'chemical',
        'drug',
        'addiction',
        'illegal',
        'brand name',
        'trademark',
        'logo',
        'copyright',
        'watermark',
        'sex',
        'sexy',
        'nude',
        'nudity',
        'naked',
        'erotic',
        'porn',
        'nsfw',
        'adult content',
        'youtube',
        'instagram',
        'reels',
        'shorts',
        'tiktok',
        'facebook',
    ];

    /**
     * Merkezi Görsel Güvenlik ve Politika Kuralları 🛡️
     * Yeni bir politika kuralı ekleneceğinde sadece bu diziye yeni bir satır eklenmesi yeterlidir.
     */
    public const SAFETY_RULES = [
        'general' => "The prompt MUST be safe, positive, and commercial-friendly.",
        'brands' => "DO NOT include any specific commercial brand names, company names, or social media platform names (e.g. YouTube, Instagram, Reels, Shorts, TikTok, Facebook). Use generic terms instead (e.g. 'video platform', 'social media', 'online video interface').",
        'forbidden' => "Avoid any sensitive, negative, hazardous, or policy-violating concepts/words (like {forbidden_list}) to comply with Google's safety guidelines.",
        'quotes' => "Do NOT use any single or double quotes (', \") inside the image prompt.",
        'documents' => "Do NOT request any paper sheets, legal documents, contracts, certificates, signatures, or official government papers.",
        'concepts' => "Focus on clean, professional, and positive visual representations, abstract concepts (like keys, digital charts without text, modern buildings), or realistic photography that match the topic."
    ];

    /**
     * Yapay zeka promptları için Türkçe karakter kullanmama talimatı üretir. 🧠
     */
    public function getPromptAlphabetInstructions(): string
    {
        $textHelper = $this->helper('text');
        $turkish = $textHelper::TURKISH_CHARS;
        $english = $textHelper::ENGLISH_CHARS;
        
        $pairs = [];
        $limit = 6; // ç, ğ, ı, ö, ş, ü
        for ($i = 0; $i < $limit; $i++) {
            $pairs[] = "'" . $english[$i] . "' for '" . $turkish[$i] . "'";
        }
        $pairsStr = implode(', ', $pairs);
        return "Do not use Turkish characters. Replace them with their English equivalents: write {$pairsStr}.";
    }

    /**
     * Görsel üretimi için merkezi güvenlik ve politika talimatlarını döner. 🛡️
     */
    public function getSafetyInstructions(): string
    {
        $forbiddenList = implode(', ', array_map(fn($w) => "'$w'", self::FORBIDDEN_WORDS));
        
        $compiledRules = [];
        foreach (self::SAFETY_RULES as $key => $rule) {
            // Dinamik değişkenleri yerine koy (örneğin yasaklı kelimeler listesi)
            if ($key === 'forbidden') {
                $rule = str_replace('{forbidden_list}', $forbiddenList, $rule);
            }
            $compiledRules[] = "- " . $rule;
        }

        return "CRITICAL SAFETY & POLICY RULES:\n" . implode("\n", $compiledRules);
    }
}
