# Core/Render/Controllers — HTTP giriş kapıları (5 dosya)

> **Doğrulanan kod tabanı:** `c23b431f` · **Tarih:** 2026-10-05 · **Yayın:** 0.9.3 = bu commit + sonrası; belge yalnız doğrulama anındaki kodu anlatır
> **Kaynak klasör:** `Core/Render/Controllers/` — **5 `*.php`** = 4 kök + `Api/` altında 1.
> **Envanter:** 5 dosyanın **5'i** anlatıldı.
> **Doğrulama platformu:** Windows + PHP 8.3, `AssetController::guvenliProxyHedefi()` ve `RawHtmlGate` gerçekten çalıştırılarak.

## 1. Ne işe yarar, kim kullanır

Bu klasör **framework'ün HTTP yüzeyidir**: 5 controller'ın 4'ü doğrudan
`Core/Routes/Mappings/core.php` içinden rotalanır, 1'i (`Api/ExternalApiController`)
`Mappings/web.php` üzerinden `Route::any('/api/v1/external', …)`. Kullanıcıya
HTML değil; **dosya, XML/metin dosyası veya JSON** basarlar.

```
Mappings/core.php:29-55   namespace('Rbn\Framework\Core\Render\Controllers')
  feed                          → CrawlerController@feed
  sitemap-{type}.xml            → CrawlerController@subSitemap
  sitemap.xml                   → CrawlerController@sitemap
  sitemap.xsl                   → CrawlerController@sitemapXsl
  robots.txt                    → CrawlerController@robots
  humans.txt                    → CrawlerController@humans
  security.txt                  → CrawlerController@security
  .well-known/security.txt      → CrawlerController@security
  llms.txt / ai.txt             → CrawlerController@llms
  {key:[a-f0-9]{64}}.txt        → CrawlerController@indexNowKey
  prefix('framework-assets')    → AssetController@serve
  prefix('project-assets')      → AssetController@serveProject
  prefix('fw-proxy')/upload/…   → FileProxyController@serveUpload
  prefix('fw-proxy')/export/…   → FileProxyController@serveExport

Mappings/web.php:17  Route::any('/api/v1/external', ExternalApiController@index)
```

## 2. Klasör/dosya envanteri (5/5)

| Dosya | Görev | Önemli public yöntemler |
|---|---|---|
| `AssetController.php` (454) | **Varlık vekili.** Sanal font/medya yönlendirmeleri, fiziksel dosya servisi, MIME, önbellek başlıkları. | `serve(string $path,bool $isProject=false): void`, `serveProject(string $path): void` · korumalı: `getMimeType(string $ext): string` · özel: `guvenliProxyHedefi(string $url): ?string`, `projectAllowedProxyHosts(): array`, `guvenliBaslikDegeri(string $deger): string`, `serveVirtualOgImage(string $name)`, `serveVirtualResource(string $name)` |
| `CrawlerController.php` (196) | Arama motoru/AI dosyalarını basar: robots, sitemap, feed, xsl, humans, security.txt, llms.txt, IndexNow anahtarı. | `robots()`, `sitemap()`, `subSitemap(string $type)`, `feed()`, `sitemapXsl()`, `humans()`, `security()`, `llms()`, `indexNowKey(string $key)` · korumalı: `serve(string $type,string $contentType,string $cachePrefix,?string $subType=null)` |
| `FileProxyController.php` (86) | `fw-proxy/upload` ve `fw-proxy/export` ile **güvenli dosya indirme**. | `serveUpload(string $path=''): void`, `serveExport(string $path=''): void` · özel: `serveFile(string $fullPath,bool $forceDownload=false): void` |
| `FrontendBaseController.php` (258) | **Soyut taban.** Proje/müşteri controller'larının ortak atası: varlık ekleme, footer kategorileri, paylaşım linkleri, reklam ayarları, `render()` ve SEO/schema bağlama. | `addAsset($assets,...$moreAssets): self`, `footerCategories(array $categories,string $showInKey='show_in'): array`, `footerPages(array $pages,string $showInKey='show_in_footer'): array`, `getShareLinks(string $url,string $title=''): array`, `loadAdSettings(): self`, `render(string $view,$data=[],$mergeData=[]): \Rbn\Framework\Core\Render\View` · korumalı: `afterBoot()`, `onGroupBoot()`, `onAfterBoot()`, `viewExists(string $view): bool` |
| `Api/ExternalApiController.php` (113) | `/api/v1/external` — dış istemcilere açık JSON ucu. | `index(): void` |

## 3. Akış — `AssetController::serve()` (AssetController.php:46-…)

```
GET /framework-assets/<path>   veya   /project-assets/<path>
 1. Sürüm son eki atılır:  'css/a.css=v1712…' → 'css/a.css'        :49-51
 2. 'fonts/<slug>' → AssetFonts::FONT_LIBRARY'de slug aranır            :55-77
       bulunursa: kontrol karakteri + şema denetimi → header("Location: $url", true, 307); exit
 3. 'media/<kategori>/<b64>' → base64_decode(strtr($encodedUrl,'-_','+/'))   :80-84
       guvenliProxyHedefi($rawUrl):
         NULL  → abort(404) — Location ÜRETİLMEZ                    :88-95
         geçerli → Cache-Control: private, max-age=300 + 307 Location  :99-100
 4. serveVirtualOgImage() / serveVirtualResource() — sanal SVG kaynakları  :376, :409
 5. Fiziksel dosya: DiscoveryEngine ile bulunur, getMimeType() ile sunulur
 6. Olmayan dosya → 404
```

### 3.1 `guvenliProxyHedefi()` — 3 katmanlı açık yönlendirme kapısı (`:180-271`)

Sınıf yorumu (`:18-34`) güvenlik yamasasını anlatır: `media/<base64>` dalı
kullanıcıdan gelen base64'ı **çözüp `Location:` başlığına yazıyordu** → açık
yönlendirme + `javascript:`/`data:` şemaları üzerinden XSS. Eklenen kapı:

1. kontrol karakteri / CRLF yok;
2. şema `http`/`https` (aynı origin'e göreli yol kabul);
3. host beyaz listede: **TEK KAYNAK** `RedirectTrait::guvenliHedef()` (aktif proje
   alan adı + grup alan adları + doğrulanmış istek host'u) **VEYA**
   `AssetConfig::PROXY_ALLOWED_HOSTS`.

**Ölçülen çıktı** (`DOC-AGAC-3-dogrula3.php` V8; `PROXY_ALLOWED_HOSTS = []`):

| Girdi | Sonuç |
|---|---|
| `https://evil.example.com/x.png` | `NULL` → 404 |
| `//evil.com/x` | `NULL` → 404 |
| `javascript:alert(1)` | `NULL` → 404 |
| `https://cdn.example.com/x.js` | `NULL` → 404 (beyaz liste **boş**) |
| `/media/aHR0cHM6Ly9ldmlsLmNvbS9hLnBuZw==` | aynı değer (göreli yol, aynı origin → geçerli) |

**Tuzak (yorum `:35-38`):** `Cache-Control: … immutable` **kaldırıldı**. `immutable`
tarayıcıya "asla değişmez" diyordu; bir kez önbelleğe alınan 307 hedefi yeni
hedefle **kalıcı** değiştirilemiyordu (cache poisoning kalıcı hâle geliyordu).
Şimdi `private, max-age=300`.

**Tuzak:** `PROXY_ALLOWED_HOSTS` **varsayılan olarak boş** (`AssetConfig.php:70-74`).
Dış CDN kullanılacaksa host elle eklenmelidir; eklenmezse dış varlık istekleri
**404** döner.

## 4. Akış — `CrawlerController::serve()` (CrawlerController.php:108-144)

Tüm dosya uçları tek `serve()` korumalı metodundan geçer:

```
serve($type, $contentType, $cachePrefix, $subType = null)      :108
 ├─ service('crawler')->generate{SitemapXml|Robots|SubSitemap|Feed|Llms}()
 ├─ cache()->remember($cachePrefix . <ölçüt>, …)              ← önbellek
 └─ response->contentType($contentType)->body($content)->send()
```

* `feed()` — `provider('crawler')->hasFeed()` false ise `shield()->abort(404)`;
  true ise `X-Robots-Tag: noindex, follow` başlığı ekler (`:51-57`).
* `sitemapXsl()` — `Resources/Views/System/sitemap.xsl` dosyasını okur,
  `{{APP_NAME}}`, `{{FRAMEWORK_NAME}}`, `{{FRAMEWORK_URL}}`, `{{NAV_BUTTONS}}`
  yer tutucularını değiştirir (`:86-96`); alt sitemap butonlarını
  `provider('crawler')->getProject('source')` ile dinamik üretir (`:74-83`).
* `indexNowKey(string $key)` — rota deseni `{key:[a-f0-9]{64}}.txt`
  (`Mappings/core.php:43`) **64 heks karakter** ile sınırlıdır.

## 5. Akış — `FrontendBaseController::render()` (FrontendBaseController.php:215-257)

1. `legalPage.content` içinde `{appName}` ve `{year}` değiştirilir (`:218-224`).
2. `parent::render()` → `View::render()` nesnesi döner (`:226`).
3. `$this->seoData` sınıfı tanımlıysa `SeoData::get($view)` ile başlık/açıklama
   `metaseo()` olarak bağlanır; `pageTitle`/`pageSubtitle`/`pageEyebrow`
   boşsa **otomatik doldurulur** (`:229-249`).
4. `$this->localBusinessData` doluysa `schema('localBusiness', …)` eklenir (`:252-254`).

`viewExists(string $view)` (`:198-210`) `provider('view')->viewResolver()->resolve()`
kullanır ve **`try/catch` içinde sessizce `false` döner**. Ölçüm:
`provider('view')` `ViewProvider` çözülür, ama `ViewProvider`'ın `viewResolver()`
metodu `BaseRender::viewResolver()`'dan gelir (`Core/Base/Web/BaseRender.php:148-151`)
ve `method_exists($viewProvider,'viewResolver')` kontrolü `protected` metot için
**true döner** ama çağrı `FrontendBaseController` bağlamında yetkisizdir → PHP
`Error` fırlatır → `catch (\Throwable)` yutar → **`viewExists()` her zaman `false`
döner**. Ölçülen `ViewResolver::resolve()` de zaten `NULL` dönüyordu
([Resolvers §3](Resolvers.md)), yani iki ayrı katman aynı sonuca varıyor.

## 6. Yapılandırma ve varsayılanlar

| Değer | Kaynak |
|---|---|
| `AssetConfig::PROXY_ALLOWED_HOSTS` | `[]` (boş) — `Configs/AssetConfig.php:70-74` |
| Varlık önbellek süresi | `private, max-age=300` — `Controllers/AssetController.php:99` |
| Sanal font yönlendirme | `307` — `AssetController.php:73` |
| `feed` robots başlığı | `X-Robots-Tag: noindex, follow` — `CrawlerController.php:55` |
| MIME listesi | `getMimeType(string $ext)` — `AssetController.php:323` |
| IndexNow anahtar deseni | `[a-f0-9]{64}` — `Mappings/core.php:43` |
| `footerCategories` görünürlük anahtarı | `'show_in'` — `FrontendBaseController.php:137` |
| `footerPages` görünürlük anahtarı | `'show_in_footer'` — `FrontendBaseController.php:149` |
| Reklam slot sayısı | 7 (`adsense_status`, `adsense_client_id`, 5 slot) — `FrontendBaseController.php:173-185` |

## 7. Tuzaklar ve kurallar (kodda görülen + ölçülen)

1. **`AssetController::serve()` ikinci parametre `$isProject`** (`:46`) —
   `serveProject()` bunu `true` ile çağırır (`:315-322`). Tek metot iki yol
   hizmet eder; yol doğrulaması bu bayrağa bağlıdır.
2. **`AssetController` özel (private) metot adları Türkçedir**
   (`guvenliProxyHedefi`, `guvenliBaslikDegeri`) ve `public` olanlar İngilizce
   (`serve`, `serveProject`, `getMimeType`). Anayasa §10 uyumlu; dokümana
   **olduğu gibi** yazıldı.
3. **`CrawlerController::sitemapXsl()` `dirname(__DIR__, 3)` kullanır**
   (`:66`) → `Core/Render/Controllers` → `rbnframework/`. `Resources/Views/System/sitemap.xsl`
   yolunu **sabit kodlanmış** olarak arar; `Paths` kullanmaz. Framework klasör
   adı değişirse `__DIR__` tabanlı olduğu için sorun yok, ama **yol kırılganlığı**
   `Resources/` adına bağlıdır.
4. **`serve()` önbellek anahtarı `$cachePrefix` + ölçüt** (`:108`); `subSitemap`
   ölçütü `$type` içerir (`"sitemap_xml_{$type}_"`, `:42`), `sitemap` içermez —
   yani ana sitemap ve alt sitemap'ler **farklı anahtarlar** kullanır (doğru).
5. **`FileProxyController::serveFile()` `$forceDownload` bayrağı** (`:38`)
   `Content-Disposition` için; `BaseManager::deliverFile()` yardımcısı da var
   (`Core/Base/Services/BaseManager.php` korumalı metotlar listesinde).
6. **`Api/ExternalApiController::index(): void`** tek metottur (113 satır).
   `Route::any()` ile bağlı olduğu için **tüm HTTP metotları** bu uca düşer;
   içeride yetki kontrolü yapılıp yapılmadığı bu dosyadan anlaşılmaz (gövde
   `getContent()`/benzeri bir yardımcıya devrediyor — bu çalışmada ölçülmedi).

## 8. Örnek (gerçek koddan)

```php
// Core/Render/Controllers/CrawlerController.php:49-58
public function feed()
{
    if (!$this->provider('crawler')->hasFeed()) {
        shield()->abort(404);
    }
    header('X-Robots-Tag: noindex, follow');
    return $this->serve('feed', 'application/rss+xml; charset=utf-8', 'rss_feed_');
}
```

```php
// Ölçülen çıktı — açık yönlendirme kapısı (beyaz liste boşken)
$rm = new ReflectionMethod(AssetController::class, 'guvenliProxyHedefi');
$rm->setAccessible(true);
// 'https://evil.example.com/x.png'  => NULL
// 'javascript:alert(1)'             => NULL
// '/media/aHR0cHM6Ly9ldmlsLmNvbS9hLnBuZw==' => aynen (göreli yol)
```

## 9. İlgili belgeler

* [Core/Render genel](README.md) · [Providers](Providers.md) · [Configs](Configs.md)
* [Core/Routes/README.md](../Routes/README.md) (rota eşlemesi) ·
  [Core/Http/Engine.md](../Http/Engine.md) (`RedirectTrait::guvenliHedef`) ·
  [Core/Base/Web.md](../Base/Web.md) · [Core/Support/Definitions.md](../Support/Definitions.md) (`AssetConvention`, `AssetFonts`)
* [Kavram: asset](../../kavramlar/05-asset-sistemi.md) · [Açık sorular](../../acik-sorular.md)