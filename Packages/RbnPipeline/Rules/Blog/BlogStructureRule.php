<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnPipeline\Rules\Blog;

use Rbn\Framework\Core\Base\BaseComponent;
use Rbn\Framework\Packages\RbnPipeline\Contracts\PromptRuleInterface;

/**
 * BlogStructureRule - Enforces standard HTML structure, length, quotes, and FAQs for blog articles 📝🏗️
 * Location: RbnPipeline/Rules/Blog/BlogStructureRule.php
 * RBN Framework Framework Standards.
 */
class BlogStructureRule extends BaseComponent implements PromptRuleInterface
{
    public function compileInstructions(array $context = []): string
    {
        $instructions = "\n\n🏗️ BLOG MİMARİSİ VE METİN YAPI STANDARTLARI (BLOG STRUCTURE RULES):\n";

        // 1. HTML Hiyerarşisi ve Başlık Düzeni 🏷️
        $instructions .= "1. **HTML Hiyerarşisi ve Akıcı Paragraf Düzeni:** Sadece temiz HTML `<h2>`, `###`, `<blockquote>` ve `<p>` etiketlerini kullan. Her paragrafın veya küçük konunun başına başlık yazma zorunluluğu KESİNLİKLE YOKTUR! Başlıkları (`<h2>`) sadece makalenin ana bölümlerini ayırmak için seyrek (toplamda sadece 2-3 adet) kullan. Paragrafların başlık olmadan akıcı şekilde birbirini takip etmesini sağla. Başlık etiketlerinde ('1.', '2.', '3.' gibi) yapay numaralandırmalar ve ana başlıkta geçen '2026' gibi yıl sayılarını robota bağlanmış gibi peş peşe KULLANMA.\n";

        // 2. Makale Uzunluğu ve İçerik Derinliği 📚
        $instructions .= "2. **Yazı Uzunluğu (Kelime Sayısı Zorunluluğu):** Makalenin ana içerik (`content`) alanı KESİNLİKLE yüzeysel veya kısa (3-4 paragraf) OLAMAZ! Okuyucuya kapsamlı, doyurucu ve derinlemesine rehber sunacak şekilde **en az 800 - 1200 kelimeden** oluşmalıdır. Her alt başlığın altında en az 2-3 paragraf detaylı açıklama yer almalıdır.\n";

        // 3. Uzman Alıntısı (Quote) Zorunluluğu 💬
        $instructions .= "3. **Uzman Alıntısı (Quote) Zorunluluğu:** Metin içinde uygun ve dikkat çekici bir noktada, yazının konusuyla ilgili sektörel veya teknik bir gerçeği vurgulayan, HTML `<blockquote>` etiketleri içinde yazılmış en az bir adet dikkat çekici uzman alıntısı (quote) bulundur.\n";

        // 4. Dinamik Soru-Cevap (FAQ) Bölümü ❓
        $instructions .= "4. **Dinamik Soru-Cevap (FAQ) Yapısı:** Yazının konusuyla %100 uyumlu, SEO odaklı ve standart olmayan, tamamen o konuya özel 3 adet dinamik soru-cevap üret. Soru başlıkları (`question`) ve cevaplar (`answer`) tamamen düz metin (plain text) olmalı, kesinlikle `<h3>`, `<p>` veya başka bir HTML etiketi İÇERMEMELİDİR. ÖNEMLİ: Bu soru-cevapları kesinlikle ana içerik (`content`) alanı içine yazma, SADECE JSON şemasındaki `faqs` alanına yaz.\n";

        // 5. Anti-AI Yapısal Doğallık 🚀
        $instructions .= "5. **Doğal Bitiş ve Slogan Yasağı:** Makale sonuna 'X Mümkün mü?' veya 'Sonuç' gibi jenerik yapay zeka özet başlıkları ekleme; son pratik tavsiye ile makaleyi doğal olarak bitir. Tablo veya liste öncesinde yapay geçiş cümleleri KULLANMA. Paragraf olarak tek başına `<p>Hesap ortada.</p>` veya `<p>Doğru adımlarla ilerlemek şart.</p>` gibi yapay TV reklamı/slogan cümleleri kurmaktan KESİNLİKLE kaçın.\n";

        return $instructions;
    }

    public function sanitizeResponse(string $response, array $context = []): string
    {
        return $response;
    }
}
