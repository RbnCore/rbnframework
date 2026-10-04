<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Base\Services;

use Rbn\Framework\Core\Base\BaseComponent;

/**
 * BasePrompt - Framework level base for all AI Prompts/Orchestrators 🧠🛰️⚓
 */
class BasePrompt extends BaseComponent
{
    /**
     * Default Schema implementation
     */
    public function getSchema(array $context): array
    {
        return [];
    }

    /**
     * Default Text Prompt compilation
     */
    public function getContentText(string $title, array $context): string
    {
        return "";
    }

    /**
     * PROMPT METİN AYARLARI SÖZLÜĞÜ VE KULLANIM REHBERİ 🧠📜
     * Alt prompt sınıflarında (örn: NewsPrompt, SdPrompt) bu metod ezilirken (override)
     * aşağıdaki standart diziler ve anahtarlar (keys) MUTLAKA KULLANILMALIDIR:
     * 
     *  - 'identity'        => (string) Yapay zekanın editörlük/gazetecilik kimliği
     *  - 'narrator'        => (string) Anlatıcı şahıs kipi ('third_person', 'first_singular', 'first_plural')
     *  - 'audience'        => (string) Hedef okuyucu kitle tanımı
     *  - 'writing_tone'    => (array)  Editörün genel yazar tonları (örn: ['akıcı', 'güncel', 'analitik'])
     *  - 'experience'      => (string) Markanın/Editörün uzmanlık tecrübe cümlesi
     *  - 'brand_authority' => (string) Marka adı (örn: 'MarkaAdi')
     *  - 'author_comment'  => (string) Şahsi yazar yorumu kuralı ve özel üslubu
     *  - 'cta'             => (string) Eyleme çağrı (Call To Action) üslubu ve yönlendirmesi
     *  - 'custom_rules'    => (array)  Göreveden bağımsız özel içerik kuralları dizisi
     *  - 'style_quirks'    => (array)  Biçimlendirme ve üslup kısıtlamaları dizisi
     *  - 'banned_phrases'  => (array)  Yapay zeka için projeye veya göreve özel ek yasaklı kelimeler/klişeler dizisi
     */
    public function getContentTextSettings(array $context): array
    {
        return [
            'custom_rules' => [],
            'style_quirks' => [],
            'banned_phrases' => []
        ];
    }

    /**
     * PROMPT GÖRSEL (IMAGEN AI) AYARLARI SÖZLÜĞÜ VE KULLANIM REHBERİ 🎨🖼️
     * 
     *  - 'aspect_ratio'    => (string) Görsel en-boy oranı ('16:9', '1:1', '4:3')
     *  - 'style'           => (string) Görsel stil tercihi ('cinematic', 'photorealistic', 'editorial')
     *  - 'negative_prompt' => (array)  Görselde bulunması istenmeyen unsurlar dizisi
     */
    public function getImageSettings(array $context): array
    {
        return [];
    }

    /**
     * Smart Merge: Merges custom project prompt configurations over base values recursively 🪐
     */
    public function mergeSettings(array $base, array $custom): array
    {
        $result = $base;
        foreach ($custom as $key => $value) {
            if (is_array($value) && isset($result[$key]) && is_array($result[$key])) {
                $result[$key] = array_merge($result[$key], $value);
            } else {
                $result[$key] = $value;
            }
        }
        return $result;
    }
}
