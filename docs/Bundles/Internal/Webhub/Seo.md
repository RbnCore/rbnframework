# Bundles/Internal/Webhub/Handlers — SEO tarama pipeline'ı (3 tarayıcı + 1 danışman)

> **Doğrulanan kod tabanı:** `d49b4413` (dal `feat/fw-license-master`) · **Tarih:** 2026-10-05 · **Yayın:** 0.9.4 = bu commit + sonrası; belge yalnız doğrulama anındaki kodu anlatır
> **Kaynak klasör:** `Bundles/Internal/Webhub/Handlers/` — 5 `*.php`.
> **Envanter:** 5 dosyanın 5'i anlatıldı.

## 1. Ne işe yarar, kimler kullanır

Webhub'ın SEO puanlama motoru. `SeoScannerService` üç "tarayıcı" handler'ı
sırayla çalıştırır, puanlarını toplar ve bir **danışman** handler'ı ile hem
kural tabanlı hem de yapay zekâ destekli tavsiye üretir. Handler'lar birbirinin
yerine geçen bağımsız ölçücülerdir; ortak taban sınıfları
`BaseSeoHandler`'dır.

**Kimler çağırır:** yalnız `SeoScannerService` (`SeoScannerService.php:30-41`).
Alias'lar `ModuleData.php:38-43`'te kayıtlıdır; dolayısıyla bu handler'lar
**kayıt dışından çağrılamaz**.

## 2. Klasör/dosya envanteri

| Dosya | Görev | Önemli yöntemler / puan |
|---|---|---|
| `BaseSeoHandler.php` | Soyut taban. İki korumalı puanlama yöntemi ve iki durum alanı (`$report`, `$totalScore`) sağlar. `execute()` **tanımlanmamıştır** — alt sınıf zorunlu. | `award(string $criteria,bool $condition,int $successPoints,int $failPoints=0): void` (:21 *protected*), `awardLength(string $criteria,string $text,int $min,int $max,int $successPoints,int $failPoints=0): void` (:37 *protected*) |
| `SettingsScannerHandler.php` | Ayar tabanlı ölçüm (alias `seo.settings`). Site başlığı, meta description, keywords uzunluklarını puanlar. **Maksimum 25.** | `execute(array $params=[]): array` (:15) |
| `CoreScannerHandler.php` | Ağ katmanı ölçümü (alias `seo.core`). HTTPS, `robots.txt`, `sitemap.xml` — **canlı HTTP GET**. **Maksimum 30.** | `execute(array $params=[]): array` (:15), `checkUrlExists(string $url): bool` (:44 *private*) |
| `DomScannerHandler.php` | Canlı HTML ölçümü (alias `seo.dom`). TTFB, favicon, HTML etiketleri, H1, alt etiketleri, Open Graph, Twitter Cards, Schema. **Maksimum 45.** | `execute(array $params=[]): array` (:15) |
| `AdviceHandler.php` | Puan + rapordan tavsiye üretir (alias `seo.advice`). `BaseSeoHandler`'ı **genişletmez** — `BaseComponent`'tir, çünkü puanlamaz. | `execute(array $params=[]): array` (:18) |

**Kapsama:** 5/5.

## 3. Akış — puanlama tamamı

```
SeoScannerService::scan()                                SeoScannerService.php:18
 ├─ executePipeline(['seo.settings','seo.core','seo.dom'], $params)   :30-34
 │    her handler için: result = handler->execute($params)
 │                       totalScore += result['score']
 │                       fullReport = array_merge(fullReport, result['report'])   :105-111
 │    score = min(100, totalScore)                          :114
 └─ handler('seo.advice')->execute([score, report])         :37-41
      → ['advice' => …, 'gemini' => …]                      AdviceHandler.php:75-78
```

### 3.1 `SettingsScannerHandler` (25 puan)

```
settings = service('settings')->withProject(active_project_key())->all()   :20-22
map[setting_key] = setting_value                                          :23-28

awardLength('Site Başlığı (10-65 karakter)',  meta-title ?? site-title, 10, 65, 10, 5)  :32
awardLength('Meta Description (50-160 karakter)', meta-description ?? site-description, 50, 160, 10, 5)  :36
award('Meta Keywords Tanımlaması', !empty(meta-keywords), 5)              :39
```

### 3.2 `CoreScannerHandler` (30 puan)

```
award('SSL / HTTPS Güvenli Bağlantı', protocol === 'https://', 5)        :24
award('Robots.txt … (Canlı HTTP Kontrolü)', checkUrlExists(base_url.'/robots.txt'), 10)   :27
award('Sitemap.xml … (Canlı HTTP Kontrolü)', checkUrlExists(base_url.'/sitemap.xml'), 15) :31

checkUrlExists(): $this->remote->get($url, …, ['timeout'=>3,'connect_timeout'=>2,
                     'curl'=>[SSL_VERIFYPEER=>false, SSL_VERIFYHOST=>false]])   :46-53
başarı = 200 <= http_code < 400                                          :55
```

### 3.3 `DomScannerHandler` (45 puan)

```
$response = remote->get(base_url, …, ['timeout'=>5]);  $ttfb = elapsed   :22-24
award('Sunucu Yanıt Hızı (TTFB < 1sn)', $ttfb < 1.0, 5)                  :27

HTML yoksa: award('Canlı HTML DOM Taraması Yapılabildi mi?', false, 40) → ERKEN DÖNÜŞ  :29-35
   (kalan 40 puan iptal edilir; en yüksek olası skor 5'e düşer)

favicon  (5)   :40   <link rel="shortcut icon|icon">
lang     (2)   :44   <html lang="…">
viewport (2)   :45   <meta name="viewport">
canonical(3)   :46   <link rel="canonical">
h1       (10)  :49-50 tek h1 tam, birden çok h1 → 5
alt      (5)   :53-63 alt doluluğu ≥%80 → 5, ≥%50 → 2, imaj yoksa 5
OG       (8)   :66-71 og:title+description+image üçü → 8, biri var → 4
Twitter  (5)   :74-78 twitter:card+title → 5, yalnız card → 2
Schema   (5)   :81-85 ld+json | schema.org | itemscope | @schema
```

### 3.4 `AdviceHandler`

```
başarısız kriterler toplanır → $failedText                            :25-30
score >= 80 → "mükemmel" · >= 50 → "iyi" · < 50 → "kritik"              :32-38

Gemini istemi (tek seferlik HTTP çağrısı):
  $this->service('gemini')->ask($prompt, $geminiService->textModel, true)   :64-65
  başarısızsa veya exception → hata metni döner, akış BOZULMAZ          :66-73
```

## 4. Puan tablosu (tek doğruluk kaynağı)

| Kriter | Handler | Tam | Kısmi | Koşul |
|---|---|---:|---:|---|
| Site Başlığı uzunluğu | `seo.settings` | 10 | 5 | 10-65 karakter (`SettingsScannerHandler.php:32`) |
| Meta Description uzunluğu | `seo.settings` | 10 | 5 | 50-160 karakter (:36) |
| Meta Keywords | `seo.settings` | 5 | 0 | boş değilse (:39) |
| SSL/HTTPS | `seo.core` | 5 | 0 | `$protocol === 'https://'` (`CoreScannerHandler.php:24`) |
| robots.txt | `seo.core` | 10 | 0 | HTTP 200-399 (:27,55) |
| sitemap.xml | `seo.core` | 15 | 0 | HTTP 200-399 (:31,55) |
| TTFB | `seo.dom` | 5 | 0 | < 1.0 sn (`DomScannerHandler.php:27`) |
| Favicon | `seo.dom` | 5 | 0 | `<link rel=…icon>` (:40-41) |
| `html lang` | `seo.dom` | 2 | 0 | (:44) |
| Viewport | `seo.dom` | 2 | 0 | (:45) |
| Canonical | `seo.dom` | 3 | 0 | (:46) |
| H1 tekil | `seo.dom` | 10 | 5 | `h1Count === 1` / `>1` (:49-50) |
| Alt etiketleri | `seo.dom` | 5 | 2 | oran ≥0.8 / ≥0.5; imaj yoksa **5 korunur** (:60,62) |
| Open Graph | `seo.dom` | 8 | 4 | 3/3 etiket / en az 1 (:67-71) |
| Twitter Cards | `seo.dom` | 5 | 2 | 2/2 / 1 (:75-78) |
| Schema/JSON-LD | `seo.dom` | 5 | 0 | (:81-85) |
| **Toplam** | | **100** | | `SeoScannerService.php:114` `min(100, Σ)` |

## 5. Tuzaklar ve kurallar

1. **`BaseSeoHandler::execute()` tanımsızdır.** Soyut sınıf (`BaseSeoHandler.php:13`) yalnız
   `award`/`awardLength` sunar; alt sınıflar `execute()` uygulamak zorundadır. Yanlışlıkla
   taban çağrılırsa `report`/`score` dönmeyen boş sonuç gelir.
2. **Handler durumu her `execute()` başında sıfırlanır.** Üçü de `$this->totalScore = 0;
   $this->report = [];` ile başlar (`SettingsScannerHandler.php:17-18`,
   `CoreScannerHandler.php:17-18`, `DomScannerHandler.php:17-18`). Aynı örnek yeniden
   çağrılırsa puan **birikmez** — bu, `BaseComponent`'in örnek başına yeniden üretilmesine
   da uygundur.
3. **HTML alınamazsa 45 puanlık dal 5'e düşer.** `DomScannerHandler` erken döner
   (:29-35): `award(…, false, 40)` rapora 0/40 yazar ve kalan kriterler hiç ölçülmez.
   Panelde "DOM taraması yapılabildi mi?" satırı bu yüzden **her zaman** kırmızı görünür
   ve toplam en yüksek 30+25+5 = 60 olabilir.
4. **Ölçüm kendi sunucusundan dışarı HTTP atar.** `base_url` = `$_SERVER['HTTP_HOST']`
   üzerinden kurulur (`SeoScannerService.php:21`). `Host` başlığı değiştirilmiş bir istek
   **başka bir siteyi** tarar ve sonucu kaydeder.
5. **SSL doğrulaması iki handler'da da kapalı.** `CURLOPT_SSL_VERIFYPEER => false`
   (`CoreScannerHandler.php:50-51`, `DomScannerHandler.php:23`). Yanlış `base_url`
   ayarı sessizce "başarılı" sayılabilir.
6. **HEAD yerine GET kullanılıyor — bilinçli.** `checkUrlExists()` yorumu bunu açıklar:
   framework dinamik dosyaları HEAD'de 500 dönebiliyor (`CoreScannerHandler.php:40-43`).
   Bedeli: `robots.txt`/`sitemap.xml` **tamamen indirilir**.
7. **Zaman aşımı sabitleri.** `checkUrlExists`: 3 sn / bağlantı 2 sn. `DomScannerHandler`:
   5 sn. `CoreScannerHandler`'ın 30 puanı bu zaman aşımlarına bağlıdır — yavaş sunucuda
   sessizce 25 puan kaybedilir.
8. **`$failPoints` varsayılanı 0'dır.** Kısmi puan yazan üç kriter (`h1`, alt, OG, Twitter)
   kısmi değeri **açıkça geçirir**; `awardLength` ise kısmi puanı varsayılan 0'a düşürür
   (`BaseSeoHandler.php:45`) — bu yüzden başlık/description uzunluğu ya tam ya hiç puan alır.
9. **Boş metin kısmi puanı da alamaz.** `awardLength()` metin boşsa `$failPoints` yerine
   **0** yazar (`BaseSeoHandler.php:40`) — "kısa metin" ile "metin yok" ayrımı raporda görünmez.
10. **Gemini çağrısı `try/catch` içindedir ve akışı değiştirmez.** `catch (\Exception $e)`
    (`AdviceHandler.php:71`) — `Error`/`TypeError` yakalanmaz, ama `throw` edilmediği için
    sonuç yine de döner. `gemini` alanı view'da düz HTML olarak basılır
    (`WebhubMap.php:41` "AI SEO Raporu"), bu yüzden istem **HTML dönmesini zorunlu kılar**
    (`AdviceHandler.php:58-62`).
11. **Rapor JSON'u settings'e yazılır, tabloda değil.** `actionScan()` `seo-report` değerine
    `json_encode(..., JSON_UNESCAPED_UNICODE)` yazar (`WebhubProvider.php:113`);
    `getReport()` onu geri çözüp geçen/başarısız sayımı **kayıtta** yapar (:72-78).
    Yani raporun doğruluğu son taramaya bağlıdır, anlık ölçüm değildir.

## 6. Örnek (gerçek koddan)

```php
// Bundles/Internal/Webhub/Handlers/BaseSeoHandler.php:21-32
protected function award(string $criteria, bool $condition, int $successPoints, int $failPoints = 0): void
{
    $earned = $condition ? $successPoints : $failPoints;
    $this->totalScore += $earned;

    $this->report[] = [
        'criteria' => $criteria,
        'passed'   => $condition,
        'earned'   => $earned,
        'max'      => $successPoints,
    ];
}
```

```php
// Bundles/Internal/Webhub/Services/SeoScannerService.php:100-117
private function executePipeline(array $handlers, array $params): array
{
    $totalScore = 0;
    $fullReport = [];

    foreach ($handlers as $alias) {
        $handler = $this->handler($alias);
        $result  = $handler->execute($params);

        $totalScore += $result['score'] ?? 0;
        $fullReport = array_merge($fullReport, $result['report'] ?? []);
    }

    return [
        'score'  => (int) min(100, $totalScore),
        'report' => $fullReport,
    ];
}
```

## 7. İlgili belgeler

* [Webhub/README.md](README.md) — modül haritası, ayar anahtarları, `actionScan()` yazma zinciri
* [Core/Support/README.md](../../../Core/Support/README.md) — `remote` bileşeni
* [kavramlar/05-asset-sistemi.md](../../../kavramlar/05-asset-sistemi.md)
* [acik-sorular.md](../../../acik-sorular.md)