<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnPipeline\Presets;

use Rbn\Framework\Packages\RbnPipeline\Builders\PromptBuilder;
use Rbn\Framework\Packages\RbnPipeline\Builders\AbstractPresetBuilder;

/**
 * NewsPreset - Standard configuration preset for News / RSS feeds 📰🚀
 * RBN 3.5 Masterpiece Standard.
 */
class NewsPreset extends AbstractPresetBuilder
{
    public function apply(PromptBuilder $builder, array $context = []): PromptBuilder
    {
        $schema = [
            'title' => 'string (Haber başlığı. Çarpıcı, profesyonel, gereksiz uzatılmamış, en fazla 8-10 kelimelik odaklanmış başlık)',
            'slug' => 'string (URL dostu, kısa ve temiz Türkçe SEO slugı. Maksimum 4-6 anlamlı kelime, küçük harf ve tire ile ayrılmış. Soru işareti veya yarım kalan kelimeler içermeyen net slug. Örn: "yapay-zeka-apple-guvencesi")',
            'summary' => 'string (Haberin kısa özeti / spot cümlesi, max 180 karakter)',
            'content' => 'string (HTML formatında yazılmış haber içeriği. En az 400-600 kelimeden oluşan, derinlemesine teknik detaylar, alt başlıklar (h2, h3), listeler (ul, li) içeren, son derece doyurucu, kapsamlı ve profesyonel bir haber metni olmalıdır. Kesinlikle kısa veya yüzeysel olmamalıdır)',
            'image_prompt' => 'string (Imagen 3 için İngilizce ve detaylı görsel üretim promptu)',
            'faqs' => 'array of objects (tam olarak 3 adet nesne içermeli. Her nesne "question" ve "answer" anahtarlarını içermeli. Hem "question" hem de "answer" tamamen düz metin (plain text) olmalı, <h3>, <p> veya başka bir HTML etiketi İÇERMEMELİDİR. Soru-cevaplar doğrudan bu haberin detaylarıyla ilgili olmalıdır)'
        ];

        if (!empty($context['candidates'])) {
            $schema['selected_key'] = 'integer (Aday haber listesinden seçip yazdığın haberin key değeri)';
            $schema['processed_keys'] = 'array of integers (Seçtiğin haberle aynı veya benzer konuyu işleyen tüm haber adaylarının key değerlerini içeren dizi)';

            $candidatesJson = json_encode($context['candidates'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            $builder->task(
                "AŞAĞIDAKİ ADAY HABER LİSTESİNDEN SEÇİM YAP VE YAZ:\n" .
                "Aday Haber Listesi:\n" .
                "{$candidatesJson}\n\n" .
                "TALİMATLAR:\n" .
                "1. Yukarıdaki listeden sitenin kitlesine en uygun, en ilgi çekici ve en sıcak olan **yalnızca 1 adet haberi seç**.\n" .
                "2. Seçtiğin bu haberi özgünleştirip detaylandırarak yeni bir haber makalesi olarak yaz.\n" .
                "3. `selected_key` alanına seçtiğin adayın `key` değerini gir.\n" .
                "4. `processed_keys` alanına seçtiğin haberle anlamsal olarak aynı konuyu veya olayı işleyen tüm adayların `key` değerlerini ekle (kendi seçtiğin dahil).\n"
            );
        }

        return $this->finalizePreset($builder, $schema, $context);
    }
}
