# Packages/RbnUtility — RSS/Atom ayrıştırıcı ve Google Trends okuyucu

> **Doğrulanan kod tabanı:** `d49b4413` (dal `feat/fw-license-master`) · **Tarih:** 2026-10-05 · **Yayın:** 0.9.3 = bu commit + sonrası; belge yalnız doğrulama anındaki kodu anlatır
> **Kaynak klasör:** `Packages/RbnUtility/` — **2 `*.php`** (ikisi de `Services/`).
> **Envanter:** 2 dosyanın **2'si** aşağıda anlatıldı.

## 1. Ne işe yarar, kim kullanır

Paket, framework'ün **içerik toplama** iki kaynağını sarmalar: RSS/Atom
akışları (`RssParserService`) ve Google Trends günlük trend listesi
(`GoogleTrendsService`). Her ikisi de `RemoteRequest` (Core/Http) üzerinden
çıkar ve `Packages/RbnPipeline/Services/ExternalFetchManager.php` tarafından
tüketilir.

**Kimler çağırır:** kayıt `PackageData.php:35-36` → `$this->service('rssParser')`
ve `$this->service('googleTrends')`. Tüketici:
`ExternalFetchManager::fetchRssCandidate()` / `fetchTrendsCandidate()`.

## 2. Klasör/dosya envanteri (2/2)

| Dosya | Görev | Önemli public yöntemler |
|---|---|---|
| `Services/RssParserService.php` | RSS 2.0 ve Atom akışlarını çeker ve normalize eder; işlenmemiş adayları ve kara listeyi yönetir. | `parse(string $url): array` (`:17`), `getUnprocessedCandidates(array $params)`, `blacklistCandidate(string $projectKey, string $guid)` |
| `Services/GoogleTrendsService.php` | Google Trends RSS'ini çeker, `ht:` namespace'inden trafik tahmini ve haber öğelerini ayıklar; niş alakalılığına göre süzer. | `fetchTrends(string $geo = 'TR'): array` (`:28`), `filterByNiche(array $trends, array\|string $nicheKeywords)` |

**Kapsama:** 2/2.

## 3. Akış

### 3.1 RSS / Atom

```
ExternalFetchManager::fetchRssCandidate($params)
 └─ RssParserService::parse($url)                       RssParserService.php:17
     ├─ $this->remote->get($url, [], [], [
     │     'timeout' => 15,
     │     'curl' => [CURLOPT_FOLLOWLOCATION => true,
     │                CURLOPT_USERAGENT => '... RbnFrameworkRssBot/1.0'] ])   :20-27
     ├─ status !== 'success' veya raw boşsa → []         :28-30
     ├─ libxml_use_internal_errors(true) → simplexml_load_string()  :34-38
     │     (bozuk XML sessizce [] döner, hata fırlatmaz)
     ├─ RSS 2.0: channel/item → guid,title,description,link,pub_date   :42-53
     └─ Atom:  entry        → id, link[href], …                        :55-62
```

### 3.2 Google Trends

```
ExternalFetchManager::fetchTrendsCandidate($params)
 └─ GoogleTrendsService::fetchTrends('TR')                GoogleTrendsService.php:28
     ├─ URL: https://trends.google.com/trending/rss?geo=TR  :20, 30
     ├─ $this->remote->get(..., timeout 15, UA '... RbnGoogleTrendsBot/1.0')  :33-39
     ├─ channel->item yoksa → []                            :49-51
     └─ her item için:                                       :54-…
         ├─ guid  = md5(mb_strtolower($title))   ← kararlı kimlik  :60
         ├─ approx_traffic (ht: namespace)                    :61
         ├─ pubDate                                          :62
         └─ news_item[] → title, snippet, url, source         :65-72
     → filterByNiche($trends, $keywords) ile nişe göre elenir
```

## 4. Yapılandırma / ayar anahtarları

| Anahtar / sabit | Yer | Varsayılan / not |
|---|---|---|
| `$rssBaseUrl` | `GoogleTrendsService.php:20` | `https://trends.google.com/trending/rss` (protected; alt sınıf değiştirebilir) |
| `$geo` | `:28-30` | `'TR'`; `strtoupper()` ile büyütülür |
| Timeout | `RssParserService.php:21`, `GoogleTrendsService.php:34` | 15 sn (her ikisi) |
| `CURLOPT_USERAGENT` | `RssParserService.php:24`, `GoogleTrendsService.php:37` | `Mozilla/5.0 (Windows NT 10.0; Win64; x64) RbnFrameworkRssBot/1.0` / `…RbnGoogleTrendsBot/1.0` |

## 5. Tuzaklar ve kurallar

1. **Bozuk XML hata fırlatmaz, boş dizi döner.** `libxml_use_internal_errors(true)`
   + `simplexml_load_string() === false` → `[]` (`RssParserService.php:34-38`,
   `GoogleTrendsService.php:121-126`). Çağıran "boş sonuç" ile "parse hatası"
   ayrımını yapamaz; log yalnız dışarıda yazılabilir.
2. **Trend `guid`'i başlıktan türetilir** (`md5(mb_strtolower($title))`,
   `GoogleTrendsService.php:60`): aynı trend her gün **aynı** kimliği alır,
   dolayısıyla kara liste kalıcıdır.
3. **Kullanıcı aracısı bilerek tarayıcı taklidi yapar** (`Mozilla/5.0
   (Windows NT 10.0; Win64; x64) …`); bu, kaynak sitenin bu isteği bot
   olarak reddetmesini engeller. `Windows NT` ifadesi **yalnız HTTP başlığı**
   içindeki metindir, kodun çalıştığı işletim sistemiyle ilgisi yoktur.
4. **`CURLOPT_FOLLOWLOCATION => true`** iki serviste de açıktır: yönlendirme
   zinciri takip edilir, dolayısıyla uzak sunucu isteği istediği adrese
   taşıyabilir (bu, `RemoteRequest` katmanının varsayılanıdır).
5. **Bu paket hiçbir veritabanına doğrudan yazmaz.** Kara liste ve "işlenmemiş
   aday" mantığı `Packages/RbnPipeline/Services/ExternalFetchManager.php`
   içindedir; bu iki servis yalnız **okur** ve **döndürür** (Anayasa §8:
   DB yalnız Repository katmanından).

## 6. Örnek (gerçek koddan)

```php
$items = $this->service('rssParser')->parse('https://site.example/rss.xml');
$trends = $this->service('googleTrends')->fetchTrends('TR');
$uygun  = $this->service('googleTrends')->filterByNiche($trends, ['<niche-a>', '<niche-b>']);
```

## 7. İlgili belgeler

* [PackageData.md](PackageData.md) — `services.rssParser`, `services.googleTrends` kayıtları
* [RbnPipeline.md](RbnPipeline.md) — `ExternalFetchManager` bu servisleri tüketen tek yer
* [../Core/Http/README.md](../Core/Http/README.md) — `RemoteRequest` (her iki servisin taşıyıcısı)
* [../Core/Database/README.md](../Core/Database/README.md) — Repository katmanı (yazma yolu)
