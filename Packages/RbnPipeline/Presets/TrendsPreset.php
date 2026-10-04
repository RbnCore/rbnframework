<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnPipeline\Presets;

use Rbn\Framework\Packages\RbnPipeline\Builders\PromptBuilder;
use Rbn\Framework\Packages\RbnPipeline\Builders\AbstractPresetBuilder;

/**
 * TrendsPreset - Sovereign Google Trends AI Generation Preset 📈🧠🚀
 * Location: RbnPipeline/Presets/TrendsPreset.php
 * RBN 3.5 Masterpiece Standard.
 */
class TrendsPreset extends AbstractPresetBuilder
{
    public function apply(PromptBuilder $builder, array $context = []): PromptBuilder
    {
        $categoryName = $context['category_name'] ?? ($context['target_category_hint'] ?? 'Genel');
        $cand = $context['draft'] ?? ($context['candidate'] ?? []);
        $trendTitle = $cand['title'] ?? ($context['topic'] ?? '');
        $summary = $cand['summary'] ?? '';

        $schema = [
            'category_id' => 'integer (Kategori doğrulama: Önerilen kategori konusuna tam uyuyorsa onun ID değerini onayla, uymuyorsa listedeki en uygun kategori ID değerini seç)',
            'title' => 'string (Trend konusuyla ilgili ilgi çekici, tık katsayısı yüksek, gereksiz uzatılmamış profesyonel makale başlığı. Maksimum 8-10 kelime)',
            'slug' => 'string (URL dostu, kısa ve temiz Türkçe SEO slugı. Maksimum 4-6 anlamlı kelime, küçük harf ve tire ile ayrılmış. Soru işareti veya yarım kalan kelimeler içermeyen net slug. Örn: "apple-yapay-zeka-guvencesi")',
            'summary' => 'string (Makalenin ilgi çekici ve doyurucu kısa özeti / spot cümlesi, max 180 karakter)',
            'content' => 'string (HTML formatında yazılmış blog makalesi içeriği. En az 600-800 kelimeden oluşan, H2 ve H3 alt başlıklar, detaylı paragraflar, maddeli listeler içeren, derinlemesine bilgi sunan kapsamlı ve profesyonel bir içerik metni olmalıdır. Kesinlikle kısa veya yüzeysel olmamalıdır)',
            'image_prompt' => 'string (Imagen 3 için İngilizce, yüksek kaliteli ve gerçekçi kapak görseli üretim promptu)',
            'faqs' => 'array of objects (tam olarak 3 adet nesne içermeli. Her nesne "question" ve "answer" anahtarlarını içermeli. Hem "question" hem de "answer" tamamen düz metin (plain text) olmalı, <h3>, <p> veya başka bir HTML etiketi İÇERMEMELİDİR. Soru-cevaplar doğrudan bu trend konusuyla ilgili olmalıdır)'
        ];

        $instructions = "GÜNDEM VE TREND İÇERİK TALİMATLARI:\n";
        $instructions .= "1. **Trend Konusu:** '{$trendTitle}'\n";
        $instructions .= "2. **Önerilen Kategori Tahmini:** '{$categoryName}'\n";
        $instructions .= "3. **Kategori Doğrulama & Üslup Seçimi:** Önce bu haber konusunu ('{$trendTitle}') detaylıca analiz et. Önerilen kategori ('{$categoryName}') bu habere tam uyuyorsa onayla. Eğer haber başka bir sektöre (sinema, oyun, teknoloji, otomobil, genel vb.) daha uygunsa, `category_id` değerini o sektörün doğru ID'si ile güncelle. Ardından makalenin üslubunu, kelime seçimlerini ve uzmanlık tonunu kesinleştirdiğin bu sektöre %100 uygun hale getir.\n";
        if (!empty($summary)) {
            $instructions .= "4. **Özet & Gündem Verileri:** {$summary}\n";
        }
        $instructions .= "5. **SEO & UX Standartları:** Metin içerisinde alt başlıkları (H2, H3), maddeli listeleri ve okunabilirliği yüksek paragrafları cömertçe kullan. Konuyla ilgisiz yapay zorlamalar yapma.";

        $builder->task($instructions);

        return $this->finalizePreset($builder, $schema, $context);
    }
}
