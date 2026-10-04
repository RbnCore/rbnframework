<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnPipeline\Presets;

use Rbn\Framework\Packages\RbnPipeline\Builders\PromptBuilder;
use Rbn\Framework\Packages\RbnPipeline\Builders\AbstractPresetBuilder;

/**
 * BlogPreset - Standard configuration preset for Blog / Articles 🏷️📝
 */
class BlogPreset extends AbstractPresetBuilder
{
    public function apply(PromptBuilder $builder, array $context = []): PromptBuilder
    {
        $schema = [
            'title' => 'string (Yazı başlığı. Çarpıcı, profesyonel, gereksiz uzatılmamış, en fazla 8-10 kelimelik odaklanmış başlık)',
            'slug' => 'string (URL dostu, kısa ve temiz Türkçe SEO slugı. Maksimum 4-6 anlamlı kelime, küçük harf ve tire ile ayrılmış. Soru işareti veya yarım kalan kelimeler içermeyen net slug. Örn: "yapay-zeka-apple-guvencesi")',
            'summary' => 'string (Yazının kısa özeti / spot cümlesi, max 200 karakter. ZORUNLUDUR, kesinlikle boş bırakılamaz.)',
            'short_desc' => 'string (Yazının kısa özeti / spot cümlesi, max 200 karakter)',
            'content' => 'string (HTML formatında yazılmış, <h2> ve <h3> alt başlıklarıyla zenginleştirilmiş, okuyucuya en az 800-1200 kelimelik detaylı, kapsamlı ve doyurucu blog makalesi. Kısa veya yüzeysel 3-4 paragraflık içerik yazılması KESİNLİKLE YASAKTIR.)',
            'image_prompt' => 'string (Imagen 3 için İngilizce ve detaylı görsel üretim promptu)',
            'faqs' => 'array of objects (Her nesne "question" ve "answer" anahtarlarını içermeli. ÖNEMLİ: hem "question" hem de "answer" tamamen düz metin (plain text) olmalı, <h3>, <p> veya başka bir HTML etiketi İÇERMEMELİDİR. SADECE ve doğrudan bu yazının ana başlığı, spesifik konusu ve içeriğiyle birebir uyumlu en sık sorulan 3-4 soru ve cevap)'
        ];

        return $this->finalizePreset($builder, $schema, $context);
    }
}
