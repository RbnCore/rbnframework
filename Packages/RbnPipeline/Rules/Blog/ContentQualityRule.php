<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnPipeline\Rules\Blog;

use Rbn\Framework\Core\Base\BaseComponent;
use Rbn\Framework\Packages\RbnPipeline\Contracts\PromptRuleInterface;

/**
 * ContentQualityRule - Master Enforcer for Article Quality, Anti-AI Slop & Topic Adherence 🧠🎯
 * Location: RbnPipeline/Rules/Blog/ContentQualityRule.php
 * RBN 3.5 Sovereign Framework Standards.
 */
class ContentQualityRule extends BaseComponent implements PromptRuleInterface
{
    public function compileInstructions(array $context = []): string
    {
        $targetTopic = $context['strict_topic'] ?? ($context['topic'] ?? ($context['title'] ?? ''));

        $instructions = "\n\n📋 İÇERİK KALİTE VE KONU BAĞLILIĞI KURALLARI (CONTENT QUALITY & TOPIC RULES):\n";

        // 1. Konu Bağlılığı ve Sapma Engelleme 🎯
        $instructions .= "1. **Konu Bütünlüğü ve Sapma Yasağı:** Sana verilen ana başlık veya konu ne ise SADECE VE SADECE O KONU HAKKINDA YAZ! Verilen konu dışında KESİNLİKLE başka, alakasız bir konuya (örneğin SEO stratejileri, dijital pazarlama, genel rehberler vb.) sapma, konuyu değiştirme veya farklı bir başlık uydurma!\n";
        $instructions .= "2. **Başlık ve Slug Uyumu:** Döneceğin 'title', 'seo_title' ve 'slug' alanları KESİNLİKLE sana verilen orijinal konu ile %100 doğrudan uyumlu ve ilişkili olmalıdır.\n";

        if (!empty($targetTopic)) {
            $instructions .= "   - **HEDEF ANA KONU:** '{$targetTopic}' -> Üreteceğin tüm metin, başlık ve özet KESİNLİKLE '{$targetTopic}' konusunu işlemek zorundadır!\n";
        }

        // 2. Üslup, Özgünlük ve Kalite Standartları 🛡️
        $instructions .= "3. **%100 Özgünlük ve Özlü Anlatım (Kavram Şişirme Yasağı):** Bir fikri, kavramı veya uyarıyı farklı kelimelerle 2-3 cümle boyunca üst üste TEKRARLAMA (semantic padding)! Bir mesajı tek bir net ve vurucu cümleyle ifade et, ardından hemen bir sonraki YENİ teknik/pratik bilgiye geç. Kelime sayısını doldurmak için aynı fikri evirip çevirip yeniden yazmak KESİNLİKLE YASAKTIR.\n";
        $instructions .= "4. **Paragraf ve Liste Düzeni (Vurgu Dengesi):** Metinleri HTML `<p>...</p>` etiketleriyle düzenli paragraflara böl. Madde listelerinde (`<ul>`/`<ol>`) SEO ve okunabilirlik açısından gerekli görülen anahtar terimlerde `<strong>...</strong>` vurgusu yapabilirsin; ancak robota bağlanmış gibi makaledeki tüm listeleri %100 aynı mekanik kalıba sokma, doğal insansı varyasyonlar kullan.\n";
        $instructions .= "5. **Doğrudan Giriş ve Vurucu Başlangıç (Front-Loading / No Throat-Clearing):** Hem makalenin ilk cümlesinde hem de her alt başlığın (`<h2>`/`<h3>`) ilk paragraflarında genel geçer tanım cümleleri, felsefi girişler ve ansiklopedik açılış kalıpları KULLANMA! Eğer bir giriş cümlesi okuyucuya yeni bir bilgi katmıyorsa doğrudan konunun odağından, en kritik sonuçtan veya pratik adımdan başla.\n";
        $instructions .= "6. **Övünme ve Sahte Uzmanlık Yasağı (Faux-Insight Setups):** Kendi tecrübeni ÖVÜCÜ cümlelerle ('X yılı aşkın tecrübemle...', 'Uzman olarak...') yazma! Ayrıca 'İşte çoğu insanın atladığı kısım...', 'Kimsenin size söylemediği şey...', 'Herkesin kaçırdığı kritik detay...' gibi yapay bilgelik taslayan klişeleri KULLANMA; doğrudan somut tespiti ve iddiayı aktar.\n";

        // Yasaklı AI Kelimeleri Listesini Dinamik Metne Dönüştürme (Genel + Projeye Özel) 🚫
        $customBanned = $context['persona']['banned_phrases']
            ?? ($context['banned_phrases'] 
            ?? ($context['custom_banned_phrases'] 
            ?? ($context['generation_options']['banned_phrases'] 
            ?? ($context['options']['banned_phrases'] ?? []))));
        $allBanned = array_values(array_unique(array_merge($this->bannedAiPhrases, (array) $customBanned)));

        $bannedListStr = "'" . implode("', '", $allBanned) . "'";
        $instructions .= "7. **Yapay Zeka & Pazarlama Klişelerinden Kaçınma (Anti-AI Slop & Humanize):** Metin içerisinde KESİNLİKLE şu jenerik AI ve pazarlama kalıplarını KULLANMA: [{$bannedListStr}]. Bu klişeler %100 YASAKTIR!\n";
        $instructions .= "8. **Etken Fiil ve Güçlü Eylem Standartı (Make Verbs Do the Work):** Soğuk AI dili oluşturan edilgen çatıları ('yapılmaktadır', 'sağlanmaktadır', 'önerilmektedir') kullanma! Ayrıca zayıf yardımcı fiil kalıplarından ('karar alma sürecine girdi' yerine 'karar verdi', 'sağlama kapasitesine sahiptir' yerine 'sağlar/yapabilir') kaçın. Doğrudan net, canlı ve etken eylemler kullan.\n";
        $instructions .= "9. **Asimetrik Paragraf Boyutları ve İnsansı Ritim (Asymmetric Paragraphs):** Paragrafları KESİNLİKLE aynı cümle sayısına (3-4 cümle) sabitleme! Paragraf uzunluklarını asimetrik ve düzensiz kurgula: Bazı paragraflar tek bir vurucu cümleden (1 cümle) oluşsun, bazı paragraflar 2 kısa cümleden, bazı paragraflar ise 5-6 cümlelik derinlemesine bir analiz bloğundan oluşsun. İnsanların doğal yazım ritmi simetrik değil, asimetriktir.\n";
        $instructions .= "10. **Parantez İçi İnsansı Yorumlar:** Metin akışını bozmayacak şekilde, yazarın anlık küçük gözlemlerini, teknik detayları veya arka plan notlarını parantez içinde belirt (örn: 'Alınan bu karar (ki sektör yetkilileri tarafından da bekleniyordu) dengeleri tamamen değiştirdi.').\n";
        $instructions .= "11. **Noktalama İşareti Çeşitliliği:** Yalnızca nokta ve virgülle sınırlı kalma; düşünce aralarında açıklama tiresi ('—') ve vurgulu anlatımlar için iki nokta (':') kullanarak metnin insansı ritmini zenginleştir.\n";
        $instructions .= "12. **Jenerik AI Sonuç Paragrafı Yasağı:** Yazıların veya makalelerin sonuna 'Gelecekteki projeleri merakla beklenmektedir', 'Özetle bu durum böyledir' gibi tipik AI bitiş klişeleri koyma. Direkt bir detay veya son tahlille doğal olarak bitir.\n";
        $instructions .= "13. **Doğruluk ve İnsansı İhtiyatlılık (Nuanced Flexibility):** İçeriklerin Google AdSense ve kalite politikalarına %100 uyumlu olmasını sağla. Teknik, finansal veya yasal konularda 'saniyeler içinde ceza keser', 'otomatik reddedilir' gibi robotik ve keskin yapay zeka hükümleri KURMA! Gerçek bir uzmanın kullanacağı ihtiyatlı ve esnek ifadeleri ('gündeme gelebilir', 'mümkün olabiliyor', 'önem taşıyor', 'genel kural olarak') tercih et.\n";
        $instructions .= "14. **Ham Markdown Yasağı ve Vurgu Kutusu (`blockquote`):** Yazı içeriğinde (`content` alanı) ASLA ham Markdown formatlama işaretleri (`**`, `###`, `-`, `*`, `_`) kullanma! Biçimlendirme için SADECE temiz HTML etiketleri (`<h2>`, `<h3>`, `<p>`, `<blockquote>`, `<strong>`, `<ul>`, `<li>`) kullan. Ayrıca kritik bir püf noktasını vurgulamak için metin içinde 1 adet `<blockquote>` etiketi kullan. Kutunun başına 'Editör Notu:' veya 'İpucu:' gibi mekanik etiket başlıkları KULLANMA; doğrudan vurgulanacak tecrübeyi veya tespiti yaz.\n";
        $instructions .= "15. **Tıklama Odaklı Çekici Başlıklar (CTR Booster Titles):** Ana başlık (`title`, `seo_title`) ve alt başlıkları (`<h2>`, `###`) asla soğuk/sıradan yazma! Başlıklara **Net Fayda + Zaman/Güncellik + Merak Parantezi** ekleyerek tıklama oranını (CTR) yükselt (örn: '2026 Rehberi: Kaç Günde Tamamlanır? (Eksiksiz Adım Adım Liste)').\n";
        $instructions .= "16. **Doğal Geçişler (Soru Tiyatrosu Yasağı):** Metin içinde 'Peki X ne anlama geliyor?', 'Cevap oldukça net.', 'Unutmayın' gibi yapay soru-cevap tiyatrosu kurmaktan KESİNLİKLE kaçın. Düşünceleri sorularla değil, doğal insansı fikir akışıyla birbirine bağla.\n";
        $instructions .= "17. **Gösterme, Kanıtla & Sahte Otorite Sığınma Yasağı (Show Don't Tell & No Weasel Attribution):** Okuyucuya ne düşüneceğini dikte etme ('Bu çok şaşırtıcı bir sonuçtur' gibi yorumları sil). Ayrıca somut bir isim veya kurum veremiyorsan 'Uzmanlar hemfikir ki...', 'Sektör raporları gösteriyor ki...' gibi kaynaksız yapay otorite sığınakları KULLANMA! Vurguyu ve önemi somut sayılar, tarihler, süreler ve doğrudan sonuçlar taşısın.\n";
        $instructions .= "18. **İkili Karşıtlık Yasağı (Binary Contrasts):** 'Mesele sadece X değil, mesele Y' veya 'Bu sadece bir araç değil, bir devrim' gibi yapay zeka ikilik ve kontrast cümlelerini KESİNLİKLE kurma; düşünceyi dolaysız ve doğal aktar.\n";
        $instructions .= "19. **Taşınabilirlik Testi (Portability Rule):** Yazdığın herhangi bir genel cümle başka bir firmaya, sektöre veya ürüne de aynen kopyalanıp yapıştırılabiliyorsa o cümle boştur. Onu derhal sil ve yerine projeye/konuya özel somut bir ölçü, süre, adım, mekanizma veya vaka koy.\n";
        $instructions .= "20. **Yapay İki Nokta (Colon Reveals) Yasağı:** 'Asıl gerçek şudur:', 'Cevap tek bir kelimede gizli:' gibi yapay zeka ifşa ve şov kalıplarından kaçın.\n";
        $instructions .= "21. **Yapay Eş Anlamlı Döngüsü Yasağı (Synonym Cycling):** Doğru, yalın ve net kelime neyse onu tekrar etmekten çekinme; üslup kasmak adına aynı kavram için paragraf boyunca yapay biçimde sürekli terim değiştirme (araç, yazılım, asistan, mekanizma gibi zorlama döngüler yapma).\n";
        $instructions .= "22. **Dramatik Parçalama Yasağı (No Dramatic Fragmentation):** 'X değil. Y değil. Tamamen Z.' veya 'Bu kadar. Bütün olay bu.' gibi teatral ve parçalanmış yapay cümleler kurma; düşünceleri akıcı ve tam Türkçe cümle yapılarıyla aktar.\n";

        // 3. Ek Dinamik Kurallar 🧩
        if (!empty($context['custom_rules']) && is_array($context['custom_rules'])) {
            $ruleIndex = 23;
            foreach ($context['custom_rules'] as $rule) {
                $instructions .= "{$ruleIndex}. " . trim((string) $rule) . "\n";
                $ruleIndex++;
            }
        }

        return $instructions;
    }

    public function sanitizeResponse(string $response, array $context = []): string
    {
        return $response;
    }

    /**
     * 🚫 Evrensel Yasaklı AI Kelimeleri & Klişe Kalıplar (Anti-AI Slop Blacklist)
     * Yeni yasaklı kelimeleri bu dizinin en altına kolayca ekleyebilirsin.
     */
    protected array $bannedAiPhrases = [
        'açıkçası',
        'işin aslı',
        'dürüst olmak gerekirse',
        'somut bir senaryo üzerinden',
        'günümüz dijital dünyasında',
        'gelişen teknolojiyle birlikte',
        'son zamanlarda sıklıkla karşılaştığımız',
        'şüphesiz ki',
        'özetle',
        'önemli bir rol oynamaktadır',
        'unutulmamalıdır ki',
        'bunun yanı sıra',
        'öte yandan',
        'geniş kitlelerce tanınan',
        'adından sıkça söz ettiren',
        'dönüm noktası olan',
        'sonuç olarak',
        'büyük bir başarıya imza attı',
        'kariyerine başarıyla devam etmektedir',
        'ezber bozuyor',
        'devrim niteliğinde',
        'sınırları yeniden çiziyor',
        'kaya gibi sağlam',
        'göz kamaştırıyor',
        'bununla birlikte',
        'dolayısıyla',
        'özellikle de',
        'önemli bir yere sahiptir',
        'büyük önem taşımaktadır',
        'dikkat çekmektedir',
        'göz önünde bulundurulduğunda',
        'bu noktada',
        'ön plana çıkmaktadır',
        'hayati önem taşımaktadır',
        'kilit bir role sahiptir',
        'adeta',
        'dijital çağda',
        'günümüz koşullarında',
        'sonuç itibariyle',
        'tüm detaylarıyla ele aldığımız',
        'unutulmaması gereken bir diğer husus',
        'büyüleyici bir atmosfer',
        'hayatınızın en heyecanlı',
        'finansal açıdan en büyük',
        'sürecin ana omurgasını',
        'azami dikkat gösterilmelidir',
        'vazgeçilmez unsur',
        'en sık yaşanan gecikmeler',
        'bu riski bertaraf etmek adına',
        'büyük riskler taşır',
        'keyfini çıkarmaya hazırsınız',
        'son rötuşlar yapıldığında',
        'birebir eşleşmesi şarttır',
        'oldukça net',
        'kritik koşul',
        'ciddi oranda',
        'titizlikle',
        'sürpriz yaşamamak için',
        'hayal kırıklıklarını önler',
        'cevap oldukça net',
        'tamamen engeller',
        'kapı aralar',
        'kesin çözüm sunar',
        'somut bir finansal veri üzerinden',
        'aşağıdaki tabloda derledik',
        'somut bir rakamsal örnek üzerinden',
        'son derece mümkün',
        'son tahlilde',
        'hayati önem taşır',
        'riskleri sıfırlayarak',
        'belirtmek gerekir ki',
        'şunu vurgulamak gerekir',
        'söz konusu olduğunda',
        'işin özünde',
        'temelinde yatan',
        'bu makalede inceleyeceğiz',
        'hadi başlayalım',
        'derinlemesine incelemek gerekirse',
        'oyunun kurallarını değiştiren',
        'ezber bozan dönüşüm',
        'yelken açmak',
        'güçlendirmek adına',
        'adeta bir köprü görevi',
        'merkezi bir rol üstlenir',
        'her geçen gün değişen'
    ];
}
