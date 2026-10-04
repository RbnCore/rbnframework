<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnPipeline\Rules\Blog;

use Rbn\Framework\Core\Base\BaseComponent;
use Rbn\Framework\Packages\RbnPipeline\Contracts\PromptRuleInterface;
use Rbn\Framework\Packages\RbnPipeline\Concerns\SanitizesResponseTrait;

/**
 * InnerLinkingRule - Standard SEO requirement for dynamic inner-linking with strict limits 🔗🏷️
 * Location: RbnPipeline/Rules/Blog/InnerLinkingRule.php
 * RBN 3.5 Sovereign Framework Standards.
 */
class InnerLinkingRule extends BaseComponent implements PromptRuleInterface
{
    use SanitizesResponseTrait;

    public function compileInstructions(array $context = []): string
    {
        $publishedArticles = $context['published_articles'] ?? [];
        if (empty($publishedArticles)) {
            return "";
        }

        $publishedArticlesJson = json_encode($publishedArticles, JSON_UNESCAPED_UNICODE);

        $taskType = strtolower((string) ($context['task_type'] ?? ($context['preset'] ?? 'blog')));
        $typePrefix = (str_contains($taskType, 'news') || str_contains($taskType, 'haber')) ? 'haber' : 'blog';

        $instructions = "\n\n🔗 SEO İÇ LİNKLEME VE UYGULAMA STANDARTLARI (INNER LINKING RULES):\n";
        $instructions .= "Metin içerisinde okuyucunun ilgisini çekebilecek konular geçtiğinde, bu kelimeleri aşağıdaki kurallara göre iç linke dönüştür:\n";
        $instructions .= "1. **Dinamik URL Yapısı:** Linkleri her zaman `{{ url('URL_YOLU') }}` helper yapısı içinde üret (Yayın haritasında belirtilen URL yolunu aynen kullan). Kesinlikle doğrudan alan adı (domain) veya sabit URL yazma.\n";
        $instructions .= "2. **Mevcut Yayın Haritası (Başlık => URL Yolu):** {$publishedArticlesJson}\n";
        $instructions .= "3. **Linkleme Formatı:** Metin içinde yukarıdaki yayın haritasındaki bir başlık veya konu geçerse, kelimeyi `<a href=\"{{ url('yayin_haritasindaki_url_yolu') }}\">Anahtar Kelime</a>` formatında bağla.\n";
        $instructions .= "4. **Sınır ve Doğallık (Doğal UX):** İç linklemeyi abartma! Metin genelinde iç link sayısı **en fazla 2 veya 3 adet** olmalıdır. Sadece en alakalı kelimelere/başlıklara link ver, metni link çöplüğüne çevirme.\n";
        $instructions .= "5. **Link Kümelenmesi ve Son Paragraf Yasağı:** İç linkleri KESİNLİKLE makalenin en sonuna peş peşe zincirleme paragraf halinde dizme! İç linkleri metnin akışına uygun biçimde orta paragraflardaki ilgili konuların ve cümlelerin içine doğal olarak dağıt.\n";

        return $instructions;
    }

    public function sanitizeResponse(string $response, array $context = []): string
    {
        $sweep = $this->helper('datasweep');
        $taskType = strtolower((string) ($context['task_type'] ?? ($context['preset'] ?? 'blog')));
        $typePrefix = (str_contains($taskType, 'news') || str_contains($taskType, 'haber')) ? 'haber' : 'blog';

        $data = $this->decodeJson($response);
        if ($data !== null) {
            $contentKey = $context['seo_content_key'] ?? 'content';
            if (isset($data[$contentKey]) && is_string($data[$contentKey])) {
                $data[$contentKey] = $sweep->sweepTemplateUrls($data[$contentKey], $typePrefix);
            }
            return $this->encodeJson($data);
        }

        // Düz metin (raw text) response durumunda
        return $sweep->sweepTemplateUrls($response, $typePrefix);
    }
}
