<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnPipeline\Rules\Prompt;

use Rbn\Framework\Core\Base\BaseComponent;
use Rbn\Framework\Packages\RbnPipeline\Contracts\PromptRuleInterface;

/**
 * WritingToneRule - Enforces project-specific writing tone, narrator perspective, and brand persona guidelines 🗣️
 * Location: RbnPipeline/Rules/Prompt/WritingToneRule.php
 * RBN 3.5 Masterpiece Standard.
 */
class WritingToneRule extends BaseComponent implements PromptRuleInterface
{
    public function compileInstructions(array $context = []): string
    {
        $settings = $context['persona'] ?? [];
        $brandTones = $settings['writing_tone'] ?? ['samimi', 'dürüst', 'güven veren', 'profesyonel'];
        $brandTonesString = is_array($brandTones) ? implode(', ', $brandTones) : (string) $brandTones;

        $instructions = "\n[WRITING TONE & NARRATION RULES]\n";
        $instructions .= "Projenin tanımlı marka kimliği ve anlatım üslubunu metnin TAMAMINA (content, summary, author_comment vb.) eksiksiz uygula:\n";
        
        if (!empty($brandTonesString)) {
            $instructions .= "1. **Dinamik Ton Geçişleri ve Üslup Uyumu:** Metni baştan sona tek düze ve monoton bir kalıpla yazma! Proje için tanımlanan [{$brandTonesString}] anlatım tonlarını metnin farklı bölümlerine (giriş, teknik detaylar, örnek anlatımları) doğal biçimde dağıtarak harmanla.\n";
        }

        // Dinamik Anlatıcı (Narrator) Tanımlamaları ve Fallback
        $narratorInstructions = [
            'first_singular' => "Anlatımı HER ZAMAN birinci tekil şahıs (Ben kipi / 'denedim', 'gördüm', 'bence', 'inceledim') ağzından yap. 'Biz', 'ekibimiz' veya 'yapılmıştır' gibi ifadelerden kaçın.",
            'first_plural'   => "Anlatımı HER ZAMAN birinci çoğul şahıs (Biz kipi / 'görüyoruz', 'ekibimiz', 'öneriyoruz', 'inceledik') ağzından, canlı bir ekip olarak yap.",
            'third_person'   => "Anlatımı HER ZAMAN tarafsız üçüncü şahıs (O kipi / 'sağlar', 'sunar', 'tasarlanmıştır') ağzından nesnel bir dille yap."
        ];

        $narrator = $settings['narrator'] ?? 'third_person';
        $narratorRule = $narratorInstructions[$narrator] ?? $narratorInstructions['third_person'];
        
        $instructions .= "2. **Anlatım Bakış Açısı (Narrator):** {$narratorRule}\n";
        $instructions .= "3. **Cümle Akıcılığı:** Cümle yapıları canlı, etken ve akıcı olmalı; okumayı kolaylaştırmak için gereksiz resmi uzatmalardan kaçınılmalıdır.\n";
        $instructions .= "4. **Evrensel Tarafsızlık ve Anti-AI Abartı Yasakları (KESİNLİKLE UYULMALIDIR):**\n";
        $instructions .= "   - Metinlerde pazarlama dili, abartılı övgü kalıpları ve yapay AI klişeleri (örn: 'küresel başarı', 'prestijli', 'tarihî eşik', 'sektörün öncüsü', 'vazgeçilmez adresi', 'büyük sıçrama', 'benzersiz deneyim' vb.) KESİNLİKLE KULLANILMAYACAKTIR!\n";
        $instructions .= "   - Anlatımı nesnel, tarafsız ve gazetecilik/ansiklopedi standartlarında tut. Konuyu, kişiyi, hizmeti veya ürünü abartılı sıfatlarla süslemek yerine; sadece somut veriler, tarihler, gerçekler ve yalın bir Türkçe ile aktar.\n";
        $instructions .= "5. **İnsansı Keskinlik ve Kontrollü Belirsizlik (Human Edge & Natural Voice):** Yazarın kendine has üslubunu, hafif şüphelerini veya samimi gözlemlerini ('bize göre', 'deneyimlerimize göre', 'ilk başta tereddüt yaratsa da') robot gibi %100 her şeyi kesin biliyormuş havasına sokmadan, doğal bir insan dürüstlüğüyle yansıt.\n";

        $forbiddenPhrases = $settings['forbidden_phrases'] ?? ($context['forbidden_phrases'] ?? []);
        if (!empty($forbiddenPhrases)) {
            $forbiddenStr = is_array($forbiddenPhrases) ? implode("', '", $forbiddenPhrases) : (string) $forbiddenPhrases;
            $instructions .= "   - **Projeye Özel Yasaklı İfadeler:** ['{$forbiddenStr}'] kalıplarını metnin hiçbir yerinde kullanma.\n";
        }

        return $instructions;
    }

    public function sanitizeResponse(string $response, array $context = []): string
    {
        return $response;
    }
}
