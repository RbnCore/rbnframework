<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnPipeline\Builders;

use Rbn\Framework\Packages\RbnPipeline\Contracts\PromptPresetInterface;
use Rbn\Framework\Packages\RbnPipeline\Builders\PromptBuilder;

/**
 * AbstractPresetBuilder - Base preset builder providing central common schema & pipeline enrichment 🛡️⚓
 * Location: RbnPipeline/Builders/AbstractPresetBuilder.php
 */
abstract class AbstractPresetBuilder implements PromptPresetInterface
{
    abstract public function apply(PromptBuilder $builder, array $context = []): PromptBuilder;

    /**
     * Presets can call this central helper to automatically inject standard common schema fields:
     * - category_id
     * - author_comment
     * - cta
     * - pros_cons
     * - seo_title, seo_description, seo_keywords
     * - existing_blogs duplicate prevention instruction
     */
    protected function enrichCommonSchema(PromptBuilder $builder, array &$schema, array $context = []): void
    {
        // 1. Otomatik Kategori Enjeksiyonu 🏷️
        if (!isset($schema['category_id'])) {
            $targetCatId = (int) ($context['target_category_id'] ?? ($context['draft']['category_id'] ?? 0));
            if ($targetCatId > 0) {
                $schema['category_id'] = "integer (Bu içerik için hedef kategori ID'si belirlenmiştir: {$targetCatId}. Yanıtta tam olarak {$targetCatId} değerini dönmelisin.)";
            } elseif (!empty($context['categories'])) {
                $catListJson = json_encode($context['categories'], JSON_UNESCAPED_UNICODE);
                $builder->task(
                    "KATEGORİ SEÇİM KURALI (MANDATORY CATEGORY SELECTION):\n" .
                    "Yazdığın içeriğin ana konusunu ve başlığını dikkatle değerlendir. Aşağıda verilen `kategoriler` listesindeki `name` ve `slug` değerlerini inceleyerek konuya %100 anlamsal ve mantıksal olarak en uygun olan kategorinin `id` değerini seç.\n" .
                    "Konuyla ilgisiz veya rastgele bir kategori kesinlikle seçilmemeli, listedeki en doğru eşleşen kategori ID'si döndürülmelidir.\n" .
                    "Kullanılabilir Kategoriler: {$catListJson}\n"
                );
                $schema['category_id'] = 'integer (İçeriğin konusuna ve amacına %100 en uygun olan kategorinin ID\'si. Yukarıdaki kategoriler listesinden seçilmelidir.)';
            }
        }

        // 2. Opsiyonel Özellikler Enjeksiyonu (author_comment, cta, pros_cons) 🎨
        if (!isset($schema['author_comment']) && (!empty($context['has_author_comment']) || !empty($context['options']['has_author_comment']))) {
            $schema['author_comment'] = 'string (Editörün/Yazarın konu hakkındaki samimi, öznel yorumu veya eklemek istediği kişisel not/tavsiye, max 250 karakter)';
        }

        if (!isset($schema['cta']) && (!empty($context['has_cta']) || !empty($context['options']['has_cta']))) {
            $schema['cta'] = 'object (İçeriğin sonuna eklenecek eyleme çağrı bannerı. "title", "description" ve "url" anahtarlarını içermeli)';
        }

        if (!isset($schema['pros_cons']) && (!empty($context['has_pros_cons']) || !empty($context['options']['has_pros_cons']))) {
            $schema['pros_cons'] = 'object (OPSİYONEL ALAN: Konunun türüne ve içeriğine göre bu alanı doldurup doldurmama kararını SEN VER. Eğer konu kıyaslama, bölge/ürün incelemesi veya tercih/karar analizi içeriyorsa doldur. Ancak konu yasal mevzuat, vergi/harç hesabı veya resmi prosedür bilgilendirmesi ise zorlama maddeler üretme, KESİNLİKLE boş nesne {} dön!)';
        }

        // 2B. Sosyal Medya İçerik Enjeksiyonu (Evrensel: X / Twitter, LinkedIn, Instagram, Facebook vb.) 📱🌐
        $wantsSocial = !empty($context['social_share']) || !empty($context['has_social']) || !empty($context['options']['has_social']);
        if ($wantsSocial) {
            if (!isset($schema['social_summary'])) {
                $schema['social_summary'] = 'string (Sosyal medya paylaşımları için profesyonel, akıcı ve çok paragraflı gönderi metni. Tek cümlelik kuru bir özet yazma! Konunun doğasına uygun olarak 2 veya 3 kısa paragraftan oluşmalı ve paragraflar arasında çift satır başı (\n\n) bulunmalıdır. Girişte konunun dikkat çeken yönünü merak uyandırıcı bir dille aktar; gelişme/sonuç kısmında konunun önemini, sektörel etkisini veya kullanıcıya faydasını özetleyip okuyucuyu detayları incelemeye ve fikirlerini paylaşmaya davet eden doğal bir çağrı ekle. Emojileri metni boğmadan, sadece kilit noktalarda ölçülü, dengeli ve yerinde kullan. Ortalama 400-600 karakter uzunluğunda olsun.)';
            }
            if (!isset($schema['social_hashtags'])) {
                $fixedHashtags = $context['fixed_hashtags'] ?? ($context['default_hashtags'] ?? []);
                if (!empty($fixedHashtags)) {
                    $fixedListStr = is_array($fixedHashtags) ? implode(' ', $fixedHashtags) : (string) $fixedHashtags;
                    $schema['social_hashtags'] = 'array of strings (Konuyla ilgili viral olabilecek popüler sektörel etiketler. DİKKAT: Proje kuralı gereği şu sabit etiketleri KESİNLİKLE listeye dahil et: ' . $fixedListStr . '. Toplam 5-8 adet etiket dön.)';
                } else {
                    $schema['social_hashtags'] = 'array of strings (Konuyla ve sektörle doğrudan ilgili en popüler ve etkileşim getirebilecek 4-6 adet hashtag. Örnek: ["#sektor", "#gundem", "#teknoloji"])';
                }
            }
        }

        // 3. Otomatik SEO Kolon Enjeksiyonu 📈
        $seoFields = $builder->rule('promptRule.seo')->getSeoSchemaFields();
        foreach ($seoFields as $key => $description) {
            if (!isset($schema[$key])) {
                $schema[$key] = $description;
            }
        }

        // 4. Mükerrer İçerik & İç Linkleme Kontrolü 🔗
        if (!empty($context['published_articles']) && is_array($context['published_articles'])) {
            $builder->task(
                "GEÇMİŞ İÇERİK KONTROLÜ (MÜKERRER ENGELLEME & İÇ LİNKLEME):\n" .
                "Sana verilen `published_articles` listesindeki makale başlıklarını aynı zamanda bir 'yayınlanmış içerik geçmişi' olarak gör.\n" .
                "Yeni içerik yazarken veya adaylar arasından seçim yaparken, bu geçmiş listedeki başlıklarla anlamsal olarak örtüşen (aynı gelişmeyi/konuyu işleyen) içerikleri doğrudan ele, onlar hakkında kesinlikle tekrar yazma.\n"
            );
        }

        // 5. Kaynak Özet ve Olay Bağlamı Enjeksiyonu (RSS / Trends / Taslak Detayı) 📰🎯
        $sourceSummary = $context['draft']['summary'] ?? ($context['summary'] ?? ($context['snippet'] ?? ''));
        $sourceName = $context['draft']['source_name'] ?? ($context['source_name'] ?? '');
        $sourceType = $context['draft']['source_type'] ?? ($context['source_type'] ?? '');

        if (!empty($sourceSummary) && trim($sourceSummary) !== '') {
            $labelParts = array_filter([$sourceName, $sourceType ? strtoupper((string) $sourceType) : '']);
            $sourceInfo = !empty($labelParts) ? " (Kaynak: " . implode(' - ', $labelParts) . ")" : "";
            $builder->task(
                "HABER / İÇERİK HAM BİLGİ VE KAYNAK ÖZETİ{$sourceInfo}:\n" .
                "\"{$sourceSummary}\"\n\n" .
                "TALİMAT: Yazacağın makalede yukarıdaki kaynak özetinde geçen somut olayları, verileri, isimleri ve teknik detayları temel al. Konudan veya olay akışından sapmadan bu bilgileri kapsamlı, derinlemesine ve özgün bir makaleye dönüştür.\n"
            );
        }
    }

    /**
     * Complete standard pipeline finalization for text-based presets 🚀
     */
    protected function finalizePreset(PromptBuilder $builder, array $schema, array $context): PromptBuilder
    {
        $builder->withContext($context);

        if (!empty($context['schema']) && is_array($context['schema'])) {
            $schema = array_merge($schema, $context['schema']);
        }

        $this->enrichCommonSchema($builder, $schema, $context);

        $builder->json($schema)
            ->identity()
            ->contentQuality()
            ->seo()
            ->innerLinking();

        // Sadece şemada image_prompt varsa ve kapalı değilse görsel prompt kuralını yükle 🎨
        $hasImagePrompt = isset($schema['image_prompt']) && ($context['has_image'] ?? true) !== false && ($context['has_image_prompt'] ?? true) !== false;
        if ($hasImagePrompt) {
            $builder->imagePrompt();
        }

        return $builder->blogStructure()
            ->writingTone()
            ->withContext([
                'seo_title_key' => $context['seo_title_key'] ?? ($context['title_key'] ?? 'title'),
                'seo_content_key' => $context['seo_content_key'] ?? ($context['content_key'] ?? 'content'),
                'published_articles' => $context['published_articles'] ?? []
            ]);
    }
}
