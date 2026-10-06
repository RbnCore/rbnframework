# Core/Render/Providers — fiziksel HTML/XML/JSON üretimi (12 dosya)

> **Doğrulanan kod tabanı:** `c23b431f` · **Tarih:** 2026-10-05 · **Yayın:** 0.9.4 = bu commit + sonrası; belge yalnız doğrulama anındaki kodu anlatır
> **Kaynak klasör:** `Core/Render/Providers/` — **12 `*.php`** = 5 kök + `UI/` altında 7.
> **Envanter:** 12 dosyanın **12'si** anlatıldı.
> **Doğrulama platformu:** Windows + PHP 8.3, tümü `provider()`/`resolver()` üzerinden gerçekten çözüldü.

## 1. Ne işe yarar, kim kullanır

`RenderService::render()` son adımda `provider($type)->render($view, $data, $type)`
çağırır (`Services/RenderService.php:62`). Provider'lar **gerçek HTML'i üreten**
tek sınıflardır: `FrontendProvider` header+view+footer'ı birleştirir,
`SeoProvider` `<meta>` bloklarını basar, `CrawlerProvider` `robots.txt`/sitemap
üretir, `AssetProvider` `<link>`/`<script>` etiketlerini basar.

**Kayıt:** `SystemRenderMapTrait.php:47-61` — `providers` altında 10 kayıt.
**Kayıt dışı 2 sınıf:** `UI/ViewProvider` ve `UI/AjaxProvider` aşağıda §4'te.

## 2. Klasör/dosya envanteri (12/12)

| Dosya | Görev | Önemli public yöntemler |
|---|---|---|
| `SeoProvider.php` (236) | **`<head>` meta üreticisi.** Teknik/proje/sosyal/marka/link segmentlerini ayrı ayrı basar, hepsini yorum işaretleriyle çerçeveler. | `render(?string $view=null,array $data=[]): string` · korumalı: `renderTechnical()`, `renderProjectMeta()`, `renderSocial()`, `renderBranding()`, `renderLinks(array $meta,string $context='frontend'): array` |
| `AssetProvider.php` (135) | CSS/JS etiketlerini basar; CDN önceliği uygular. | `render(?string $view,array $data=[]): string`, `decorate(string $content,string $physicalPath,?string $label='RBN Framework'): string` · korumalı: `cdnFirst(array $assets): array`, `renderStyle(array $style): string`, `renderScript(array $script): string` |
| `CrawlerProvider.php` (306) | `robots.txt`, sitemap, feed, `llms.txt` üretir; aktif sayfa envanteri sunar. noindex kararını **kendisi yorumlamaz**, `SeoResolver`'a devreder. | `render(?string $view,array $data=[]): string`, `renderRobots(array $data=[]): string`, `renderSitemap(array $data=[]): string`, `renderFeed(array $data=[]): string`, `renderLlms(array $data=[]): string`, `getActivePages(): array`, `hasActivePage(string $slug): bool`, `hasActiveFaqs(): bool`, `getActivePagesCount(): int`, `isSiteNoindex(): bool`, `isPageNoindex(array\|object $entry): bool`, `hasFeed(): bool`, `getProject(string $type)` |
| `BreadcrumbProvider.php` (104) | Kırıntı HTML'ini basar **ve** veri dizisine enjekte eder. | `resolveAndRender(array &$data,?string $view): string`, `render(?string $view,array $data=[]): string` |
| `SchemaProvider.php` (85) | Biriktirilen JSON-LD şemalarını `<script type="application/ld+json">` olarak basar. | `build(string $name,array $data=[]): self`, `render(): string` |
| `UI/FrontendProvider.php` (137) | **En sık çalışan provider.** Trafik kaydı + ayarlar + SEO + asset + şablon zinciri. | `render(?string $view,array $data=[]): string` · korumalı: `safeSnippet(mixed $value): string` |
| `UI/PanelProvider.php` (112) | Yönetim paneli layout'u. | `render(?string $view,array $data=[]): string` |
| `UI/AuthProvider.php` (70) | Giriş/kayıt ekranı layout'u. | `render(?string $view,array $data=[]): string` |
| `UI/PartialProvider.php` (55) | Parça/fragment basımı; `@import` dizisini toplu basar. | `render(?string $view,array $data=[]): string`, `renderImports(array $imports,array $data=[]): string` |
| `UI/StaticProvider.php` (32) | Sabit (statik) sayfa basımı — dosyanın en küçük provider'ı. | `render(?string $view,array $data=[]): string` |
| `UI/AjaxProvider.php` (54) | **Layout'suz** parça basıcı (modal, tooltip, AJAX güncellemesi). `$data['__imports']` varsa `PartialProvider::renderImports()` ile içe aktarımları da basar. | `render(?string $view,array $data=[]): string` |
| `UI/ViewProvider.php` (81) | **Kayıtsız** çekirdek view koşucusu: `resolve()`/`include()` mantığı. | `render(?string $view,array $data=[],string $appContext='frontend'): mixed`, `include(string $path,array $data=[],string $source='auto',string $appContext='frontend'): void`, `resolve(string $path,array $data=[],string $source='auto',bool $strictRbn=false,string $appContext='frontend'): ?string` |

## 3. Akış — `FrontendProvider::render()` (Providers/UI/FrontendProvider.php:19-108)

Tam zincir ve satır kanıtları [README §3.1](README.md)'de. Burada yalnız **provider'a
özgü** üç karar:

1. **Trafik kaydı her frontend render'da bir kez** (`:23`
   `$this->storage->traffic()->record()`). Aynı istekte iki kez render edilirse
   (ör. partial + tam sayfa) iki kayıt yazılır — engel yok, ölçülmedi.
2. **Fragment varsa tüm zincir atlanır** (`:92-94`): `resolveFragment()` view'i
   basar, `ob_get_clean()` ile buffer'ı alır, `getSection()` ile tek bölümü
   döndürür. Yani **header/footer basılmaz**.
3. **Ayar okuma önceliği** (`:59-77`): `array_merge([...varsayılanlar], $data)` —
   yani **controller'ın geçtiği veri her zaman kazanır**; ayarlardan gelen veri
   yalnız controller verisi yoksa kullanılır. Yorum satırı `:27-28` bunu
   "0 Duplicate Reads" diye açıklar.

### 3.1 `safeSnippet()` — ikinci katman (FrontendProvider.php:124-136)

4 snippet `RawHtmlGate::sanitize()`'den geçer: `google_analytics_code`,
`google_adsense_code`, `head_scripts`, `body_scripts` (`:73-76`). Bu değerler
view'da `{!! !!}` ile basıldığı için kaçış **gerekmez**; yazma yetkisi
`SettingsConfig::REQUIRED_ROLE` (admin/developer) ile sınırlıdır. Ayrıntı:
[Handlers §4](Handlers.md).

### 3.2 `SeoProvider::render()` — 5 segment (Providers/SeoProvider.php:20-64)

```
render($view, $data)
 ├─ $data['meta'] yoksa resolver('seo')->resolve('project') ile üret   :23-26
 ├─ $view === 'branding' ise SADECE renderBranding()                   :32-34
 └─ segmentler sırayla: technical → project → social → branding → links :36-40
    Her segment "<!-- [ ETİKET ] -->" yorumuyla çerçevelenir;
    baştaki/ sondaki RBN SOVEREIGN SEO ENGINE v<FRAMEWORK_VERSION> işaretleri :44, :61
```

`renderProjectMeta()` `<title>`'ı `htmlspecialchars_decode()` ile basar
(`:91`) — çünkü `SeoResolver` metni önceden temizlemişti (`seoCleanText`);
çift kaçışı önlemek için decode edilir. `robots`/`description`/`keywords`
`ENT_NOQUOTES` ile kaçırılır (`:103-110`).

## 4. Kayıt dışı iki provider — `ViewProvider` ve `AjaxProvider`

`UI/ViewProvider.php` ve `UI/AjaxProvider.php` **`SystemRenderMapTrait.php`
`providers` listesinde yok** (ölçüldü: 10 kayıt, 12 provider sınıfı).

**Ama çalışıyorlar** — çözümleme zincirinin 3. adımında bulunuyorlar:

```
$probe->provider('view') → Rbn\Framework\Core\Render\Providers\UI\ViewProvider   (ölçüldü)
```

Nasıl? `ComponentContext::resolve()`
(`Core/System/Discovery/Clusters/Logic/Component/ComponentContext.php:84-99`):

1. `DiscoveryEngine::instance()->namespace()->find("provider.view", 'provider')`
   → `SystemRegistry::locate('view','provider')` → **`'Rbn\Framework\Core\Render\Providers\UI\ViewProvider'`**
   (ölçülen çıktı; tam yollu, RenderMap'teki kısa yollardan farklı olarak).
2. `NamespaceResolver::resolveCoreLayer()` (`Namespace/NamespaceResolver.php:109-181`)
   `NamespaceMap` taban adları + `Providers\ViewProvider` kombinasyonunu dener;
   `Render => Rbn\Framework\Core\Render\` girdisi listede olduğu için
   `Rbn\Framework\Core\Render\Providers\ViewProvider` **yok**, ama
   `Framework => Rbn\Framework\` + `Render` modül öneki varyantı **var**.

**Ölçülen sonuç:** `NamespaceResolver::find('view','provider')` →
`'Rbn\Framework\Core\Render\Providers\UI\ViewProvider'` döndü. Yani `ViewProvider`
**fiziksel yolundan** (`UI/` altı) çözülüyor, kayıt haritasından değil.

**Sonuç ve risk:**

* `ViewProvider` **çalışıyor**, ama kayıt tek doğruluk kaynağı değil —
  `SystemRenderMapTrait`'e eklenirse aynı sınıf iki yerden gelir.
* `UI/AjaxProvider.php` **hiçbir yerden çözülmüyor.** `aliases` haritasında
  `'ajax' => 'partials'` **var** (`SystemRenderMapTrait.php:100`) yani `ajax`
  tipi `PartialProvider`'a gider; `AjaxProvider` sınıfı **ölü kod**dur.
  `View.php:53` yorumu "Shift to AjaxProvider" der ama `type = 'ajax'` ataması
  yapar ve `RenderService::render('ajax', …)` → `provider('ajax')` → alias →
  `PartialProvider`. Ölçüldü: `provider('ajax')` çözümlemesi
  `SystemRenderMapTrait.php:100` alias'ı üzerinden **PartialProvider**'dır.
  *(Kod değiştirilmedi — salt okunur belge.)*

## 5. Yapılandırma ve varsayılanlar

| Değer | Kaynak |
|---|---|
| `SeoProvider` çerçeve sürümü | `FrameworkIdentity::FRAMEWORK_VERSION` (`Providers/SeoProvider.php:44`) |
| Varsayılan viewport | `width=device-width, initial-scale=1.0` (`SeoProvider.php:73`) |
| `<title>` kaçırma | `htmlspecialchars_decode()` (`SeoProvider.php:91`) |
| meta kaçırma | `htmlspecialchars(..., ENT_NOQUOTES, 'UTF-8')` (`SeoProvider.php:103-110`) |
| Frontend varsayılan site adı | `'RBN Core'` (`UI/FrontendProvider.php:30`) |
| Frontend snippet sayısı | 4 (`UI/FrontendProvider.php:73-76`) |
| `AssetProvider::decorate()` etiketi | `?string $label = 'RBN Framework'` (`AssetProvider.php:108`) |

## 6. Tuzaklar ve kurallar (kodda görülen + ölçülen)

1. **`Fragment istenince header/footer basılmaz** (`:92-94`). Bir partial
   isteğinde sayfa başlığı/asset bloğu gelmez — view bu bloklara bağlıysa
   boş kalır.
2. **`Layouts/header` ve `Layouts/footer` çözülemezse render yine devam eder**
   (`:97-105` `if ($headerPath && file_exists(...))`). Yalnız **asıl** view
   zorunludur (`:85-87` `handleMissing()`). Yani eksik header **sessiz** eksik kalır.
3. **`SchemaProvider::render()` çıktısı `appSchemaHtml`** olarak frontend'e
   enjekte edilir (`:70`), yani `<head>` içine view'dan basılır — `render()`
   çağrısında `context` bilgisi taşınmaz.
4. **`CrawlerProvider::render()` tek giriş noktasıdır** ve `renderRobots()` /
   `renderSitemap()` / `renderFeed()` / `renderLlms()` ayrı ayrı da çağrılabilir
   (`:39, :93, :187, :206`). `CrawlerController::feed()` önce `hasFeed()`
   kontrolü yapar ve değilse `shield()->abort(404)` (`Controllers/CrawlerController.php:51-53`)
   — ölçülen `hasFeed()` mantığı `getProject('feed')`'e dayanır.
5. **`BreadcrumbProvider::resolveAndRender(array &$data, …)` diziyi **by-reference**
   değiştirir** (`:19`) — view verisine `breadcrumbs` ekler. `render()` saf basım.
   Bu yüzden `resolveAndRender()` **mutlaka view render'dan önce** çağrılmalıdır.
6. **`StaticProvider` 32 satırdır** ve `render()` imzası diğerlerinden farklı
   değildir; pratikte yalnız `type='static'` ile çağrılır.
7. **`PartialProvider::renderImports()`** `@import` dizisini toplu basar
   (`:46`); her içe aktarma `ViewEngine::import()` → `path()` + `compile()`
   gider, yani **her biri kök sınırından geçer** ([README §3.6](README.md)).
8. **`CrawlerProvider` yasal sayfaları `model('project.page')` ile okur.**
   Registry'deki anahtar **`project.page`**'dir
   (`Core/System/Registries/RegistryMap/SystemPhysicalMapTrait.php:55`).
   `page` diye bir kayıt **yoktur**; öyle bir anahtarla çağrıldığında model
   `NULL` döner, `->where()` istisna atar ve `catch` bloğu boş liste verir —
   yani yasal sayfalar **sessizce** sitemap'ten düşer. Bu yüzden dört metot
   (`getActivePages`, `getActivePagesCount`, `hasActivePage`, `hasActiveFaqs`)
   `catch` bloklarında `error_log('[RBN-CRAWLER] …')` yazar: sessizlik, aynı
   hatanın aylarca fark edilmemesine yol açmıştır.
9. **`hasActivePage()` / `hasActiveFaqs()` içerik varlığını DB'den sorar**
   (yalnız `status = active` / `is_active = 1`). Sitemap üretimi bunları
   kullanır: içeriğe bağlı standart rotalar yalnızca gerçekten içerik
   varken listelenir, aksi halde denetleyici ana sayfaya yönlendirir ve
   sitemap'te 301/302 dönen bir adres kalır. Sorgu hata verirse
   `false` döner (güvenli taraf: adres listelenmez).

## 7. Örnek (gerçek koddan)

```php
// Core/Render/Providers/SeoProvider.php:44-61  (çerçeve + segment birleştirme)
$output = "\n    <!-- 🔱 RBN SOVEREIGN SEO ENGINE [v" . FrameworkIdentity::FRAMEWORK_VERSION . " ] -->\n";
$output .= $techHtml . "\n";
$output .= "\n    <!-- [ {$contextLabel} SEO ] -->\n" . $projectHtml . "\n";
$output .= $socialHtml . "\n";
$output .= "\n    <!-- [ RESOURCE LINKS ] -->\n" . implode("\n", $linkHtml) . "\n";
$output .= "\n    <!-- ⚓ SOVEREIGN SEO ENGINE - END -->\n";
```

```php
// Ölçülen çözümleme — kayıt haritasında olmayan provider
//   provider('view') → Rbn\Framework\Core\Render\Providers\UI\ViewProvider
//   provider('ajax') → aliases['ajax'] = 'partials' → PartialProvider  (AjaxProvider ölü kod)
```

## 8. İlgili belgeler

* [Core/Render genel](README.md) · [Resolvers](Resolvers.md) · [Builders](Builders.md) ·
  [Handlers](Handlers.md) · [Controllers](Controllers.md)
* [Core/Base/Web.md](../Base/Web.md) · [Core/System/Registries.md](../System/Registries.md) ·
  [Core/System/Discovery.md](../System/Discovery.md) (çözümleme zinciri)
* [Kavram: asset](../../kavramlar/05-asset-sistemi.md) · [Açık sorular §1.11](../../acik-sorular.md)