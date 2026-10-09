<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnPipeline\Rules\Prompt;

use Rbn\Framework\Core\Base\BaseComponent;
use Rbn\Framework\Packages\RbnPipeline\Contracts\PromptRuleInterface;

/**
 * IdentityRule - Dynamically builds and enforces the project identity and custom persona rules 🗣️
 * RBN Framework Standard.
 */
class IdentityRule extends BaseComponent implements PromptRuleInterface
{
    public function compileInstructions(array $context = []): string
    {
        $settings = $context['persona'] ?? [];
        if (empty($settings)) {
            return '';
        }

        // 1. Kimlik ve Rol Kurgusu (Persona)
        $year = date('Y');
        $task = "SENİN KİMLİĞİN:\n";
        $task .= "Sen " . ($settings['identity'] ?? '') . "\n";
        $task .= "GÜNCEL YIL: {$year}. Zaman algısı ve güncellik açısından bu yılı bil, ancak metin içerisinde '{$year} yılı' ifadesini gereksiz yere ve sürekli tekrarlayarak okuma deneyimini bozma. Yasal veya teknik standartlar güncel olsun yeter.\n";
        $task .= "Ana Görevin: " . ($settings['task'] ?? '') . ".\n";
        $task .= "Hitap Ettiğin Kitle: " . ($settings['audience'] ?? '') . ".\n";

        if (!empty($settings['experience'])) {
            $task .= "Senin Saha Deneyimin/Geçmişin: \"{$settings['experience']}\"\n";
            $task .= "ÖNEMLİ KURAL: Kendi tecrübeni, kıdemini veya unvanını ÖVÜCÜ/BİTTİ DİYE ANLATICI CÜMLELERLE KESİNLİKLE YAZMA ('X yılı aşkın tecrübem boyunca...', '10 yıldır bu sektörde çalışan biri olarak...' vb. övünme ifadeleri YASAKTIR). Bu tecrübeni kendinden bahsetmek için değil; okuyucuya doğrudan pratik saha ipuçları, derin teknik analizler ve harika çözümler sunmak için KULLAN!\n";
        }
        $task .= "\n";

        // 2. SEO ve İçerik Kuralları
        $task .= "SEO VE İÇERİK KURALLARI:\n";

        $ruleNum = 1;
        if (!empty($settings['brand_authority'])) {
            $brandName = (string) $settings['brand_authority'];
            $task .= "{$ruleNum}. **Marka Otoritesi ve Kapanış Dokunuşu:** Sen {$brandName} bünyesinde bir uzmansın. {$brandName} marka adını metin içerisinde robota bağlanmış gibi sürekli tekrarlama; sadece doğal akışta ve makale sonunda okuyucuya değer kılan samimi bir {$brandName} uzman tavsiyesi ve danışmanlık kapanış cümlesiyle metni tamamla.\n";
            $ruleNum++;
        }

        if (!empty($settings['corporate_links'])) {
            $task .= "{$ruleNum}. **Kurumsal İç Linkleme (Opsiyonel):** Aşağıdaki kurumsal bağlantılar SADECE metin akışında bu konular %100 doğal olarak geçtiğinde paragraf içinde bağlanabilir; her makalede kullanmak veya metin sonuna eklemek zorunlu değildir:\n";
            foreach ($settings['corporate_links'] as $label => $route) {
                $cleanRoute = '/' . ltrim((string) $route, '/');
                $task .= "   - '{$label}' -> <a href=\"{$cleanRoute}\">{$label}</a>\n";
            }
            $ruleNum++;
        }

        $queriesArray = $context['search_queries'] ?? $settings['search_queries'] ?? [];
        $searchQueries = implode(', ', $queriesArray);
        if (!empty($searchQueries)) {
            $task .= "{$ruleNum}. **Hedef Arama Terimleri (Google Search Console):** Kullanıcıların Google'da aratarak sitemize ulaştığı şu popüler arama öbeklerinden sadece yazının konusuyla %100 uyumlu olanları seçip metne doğal bir akışla yedir (alakasız kelimeleri zorla tıkıştırmaya çalışma): [{$searchQueries}].\n";
            $ruleNum++;
        }

        if (!empty($settings['custom_rules'])) {
            $task .= "{$ruleNum}. **Özel Sektörel Kurallar:**\n";
            foreach ($settings['custom_rules'] as $rule) {
                $task .= "   - {$rule}\n";
            }
            $ruleNum++;
        }

        if (!empty($settings['style_quirks'])) {
            $task .= "{$ruleNum}. **Yazım Alışkanlıkları ve Anlatım Kısıtları:**\n";
            foreach ($settings['style_quirks'] as $quirk) {
                $task .= "   - {$quirk}\n";
            }
            $ruleNum++;
        }

        if (!empty($settings['local_seo'])) {
            $task .= "{$ruleNum}. **Yerel SEO ve Konum Odaklılık (Doğal UX):** {$settings['local_seo']} (ÖNEMLİ: Konum ve yerel kelimeleri KESİNLİKLE abartma! Metin içinde zorlama yerel kelime yığılması yapmaktan kaçın; sadece doğal akışa uyan yerlerde seyrek kullan.)\n\n";
            $ruleNum++;
        }

        return $task;
    }

    public function sanitizeResponse(string $response, array $context = []): string
    {
        return $response;
    }
}
