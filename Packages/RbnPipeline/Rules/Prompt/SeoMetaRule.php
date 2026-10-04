<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnPipeline\Rules\Prompt;

use Rbn\Framework\Core\Base\BaseComponent;
use Rbn\Framework\Packages\RbnPipeline\Contracts\PromptRuleInterface;
use Rbn\Framework\Packages\RbnPipeline\Concerns\SanitizesResponseTrait;

/**
 * SeoMetaRule - Standard SEO meta requirements, keyword phrases, and autonomous fallback 🏷️📈
 * Location: RbnPipeline/Rules/Prompt/SeoMetaRule.php
 * RBN 3.5 Sovereign Framework Standards.
 */
class SeoMetaRule extends BaseComponent implements PromptRuleInterface
{
    use SanitizesResponseTrait;

    public function getSeoSchemaFields(): array
    {
        return [
            'seo_title' => 'string (Arama motoru uyumlu, arama niyetine odaklı, tıklama oranını (CTR) artıracak özgün başlık, max 60 karakter)',
            'seo_description' => 'string (Arama motoru uyumlu, tıklamaya teşvik eden meta açıklaması, max 155 karakter)',
            'seo_keywords' => 'string (ÖNEMLİ: Tek kelimelik jenerik terimler uydurma. Virgülle ayrılmış, 2-4 kelimelik anlamlı arama öbeklerinden oluşan anahtar kelimeler. Örn: "konuya özel anahtar kelime, detaylı arama terimi" gibi, max 100 karakter)'
        ];
    }

    public function compileInstructions(array $context = []): string
    {
        $instructions = "\n\n🏷️ SEO META VE ARAMA MOTORU UYUM STANDARTLARI (SEO META RULES):\n";
        $instructions .= "Arama motoru optimizasyonu (SEO) için aşağıdaki kurallara tam uyumlu meta verileri üret:\n";
        $instructions .= "1. **SEO Başlığı (seo_title):** Arama niyetine (Search Intent) odaklı, Google sonuçlarında dikkat çeken ve tıklama oranını (CTR) artıran en fazla 60 karakterlik özgün başlık üret.\n";
        $instructions .= "2. **SEO Açıklaması (seo_description):** Okuyucuyu tıklamaya teşvik eden, ana konuyu özetleyen en fazla 155 karakterlik net meta açıklaması yaz.\n";
        $instructions .= "3. **Anlamlı Anahtar Kelime Öbekleri (seo_keywords):** Tek kelimelik jenerik kelimeler ('oyun', 'kulaklık' vb.) KESİNLİKLE YASAKTIR! Virgülle ayrılmış, 2-4 kelimelik anlamlı arama öbekleri (örn: 'oyuncu kulaklığı önerisi, gözlük sıkanlar için kulaklık') üret. Dizi değil, virgülle ayrılmış düz metin dön olmalısın.\n";
        $instructions .= "4. **Anahtar İsim İzolasyonu:** Anahtar isimlerinin birebir `seo_title`, `seo_description` ve `seo_keywords` olarak JSON şemasında yer aldığından emin ol.\n";

        return $instructions;
    }

    public function sanitizeResponse(string $response, array $context = []): string
    {
        $data = $this->decodeJson($response);
        if ($data !== null) {
            $titleKey = $context['seo_title_key'] ?? 'title';
            $contentKey = $context['seo_content_key'] ?? 'content';

            $title = $data[$titleKey] ?? '';
            $content = $data[$contentKey] ?? '';

            // Auto-generate description using framework's MetaSeoHelper if missing
            if (!empty($content) && empty($data['seo_description'])) {
                $data['seo_description'] = $this->helper('meta.seo')->generateMetaDescription(strip_tags($content));
            }

            // Auto-generate/clean slug using framework's MetaSeoHelper
            $slugSource = !empty($data['slug']) ? (string) $data['slug'] : (string) $title;
            if (!empty($slugSource)) {
                $data['slug'] = $this->helper('meta.seo')->seoSlug($slugSource);
            }

            // Auto-generate title using framework's MetaSeoHelper if missing
            if (!empty($title) && empty($data['seo_title'])) {
                $seoData = $this->helper('meta.seo')->autonomousSeo($title);
                $data['seo_title'] = $seoData['seo_title'] ?? $title;
            }

            // Auto-generate keywords using framework's MetaSeoHelper if missing
            if (empty($data['seo_keywords']) && empty($data['keywords'])) {
                $seoData = $this->helper('meta.seo')->autonomousSeo($title ?: $content);
                $data['seo_keywords'] = $seoData['seo_keywords'] ?? '';
                $data['keywords'] = $data['seo_keywords'];
            }

            // Clean with format helper to prevent ampersands/special quotes
            $format = $this->helper('format');
            if (!empty($data['seo_title'])) {
                $data['seo_title'] = $format->seoCleanText((string) $data['seo_title']);
            }
            if (!empty($data['seo_description'])) {
                $data['seo_description'] = $format->seoCleanText((string) $data['seo_description']);
            }
            if (!empty($data['seo_keywords'])) {
                $data['seo_keywords'] = $format->seoCleanText((string) $data['seo_keywords']);
            }

            return $this->encodeJson($data);
        }

        return $response;
    }
}
