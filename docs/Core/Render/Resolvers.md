# Core/Render/Resolvers — ad → yol/meta/payload çözümleyiciler (10 dosya)

> **Doğrulanan kod tabanı:** `c23b431f` · **Tarih:** 2026-10-05 · **Yayın:** 0.9.6 = bu commit + sonrası; belge yalnız doğrulama anındaki kodu anlatır
> **Kaynak klasör:** `Core/Render/Resolvers/` — **10 `*.php`** = 6 kök + `Sub/` altında 4.
> **Envanter:** 10 dosyanın **10'u** anlatıldı.
> **Doğrulama platformu:** Windows + PHP 8.3, `ViewResolver`/`LayoutResolver`/`RobotsResolver` gerçekten çalıştırılarak.

## 1. Ne işe yarar, kim kullanır

Resolver'lar **okuma tarafıdır**: veritabanı/ayar/dosya sistemi bilgisini alıp
"bu mantıksal ad neye karşılık gelir?" sorusunu tek yerde yanıtlarlar. Hiçbiri
HTML basmaz (basma [Providers](Providers.md)'ın işidir) ve hiçbiri istek
başlatmaz.

**Erişim:** `$this->resolver('<ad>')` (`DiscoveryActionsTrait.php:154`) —
tümü `SystemRenderMapTrait.php:69-80`'de `resolvers` altında kayıtlı.

**Tuzak:** `handler('schema')` **değil** `resolver('schema')` doğrudur
(`SystemLogicMapTrait.php:114` yanlış kayıt; bkz. [README §4](README.md)).

## 2. Klasör/dosya envanteri (10/10)

| Dosya | Görev | Önemli public yöntemler |
|---|---|---|
| `ViewResolver.php` (126) | **Mantıksal view adı → fiziksel dosya yolu.** 7 hedef dizini sırayla tarar, `.php` ve `.rbn.php` uzantılarını dener. | `resolve(string $view,array $options=[]): ?string` · statik: `$resolveCache` |
| `LayoutResolver.php` (97) | Layout zinciri + **bölüm yığını** (`@section`/`@yield`). `ob_start()` tabanlı. | `setExtends(string $layout): void`, `getExtends(): ?string`, `resetExtends(): void`, `startSection(string $name): void`, `endSection(): void`, `getSection(string $name,string $default=''): string`, `getAllSections(): array`, `clear(): void` |
| `SeoResolver.php` (479) | **Meta birleştirici.** `SettingsService` grupları + `SeoConfig::defaults()` + controller override'ları → `meta`/`og` paketi. **noindex'in tek yorumlayıcısı** (bkz. §7 noindex maddesi). | `resolve(string $context='project',array $overrides=[]): array`, `resolveFaviconRaw(array $hub,array $overrides=[]): string`, `resolveAsset(?string $path,string $virtualName='favicon.svg'): ?string`, `getVirtualResourceRaw(string $name,string $context='project'): string`, `webContext(): array`, `getSeoConfig(): array`, `isSiteNoindex(): bool`, `isPageNoindex(array\|object $entry): bool` · statik: `directiveHasNoindex(mixed $directives): bool` · özel: `siteRobotsDirective(): mixed` · korumalı: `formatFaviconData()`, `resolveSocial()` |
| `SchemaResolver.php` (152) | Şema için site/breadcrumb/görsel bağlamı üretir. | `meta(): array`, `breadcrumbs(?string $currentTitle=null): array`, `resolveDefaultImage(): string` |
| `BreadcrumbResolver.php` (85) | Kırıntı adımlarını modül metadata'sından türetir. | `resolve(array $options=[]): array`, `resolveInitialMetadata(array\|object $moduleData,string $module,array $options): array`, `resolveSegmentMatch(string $segment,array $subModules): ?array`, `normalizeIcon(string $icon): string` |
| `CrawlerResolver.php` (146) | `robots/sitemap/feed` yapılandırmasını proje kaynaklarından okur, servis metotlarını çağırır. | `resolveCrawlerMap(): ?array`, `resolveProjectSources(): array`, `resolveDirectModel(string $identifier,string $moduleNamespace): string`, `callServiceMethod(array $config,string $methodKey,string $defaultMethod,...$args)` |
| `Sub/RobotsResolver.php` (137) | `robots.txt` metnini ve **yol kısıtlamasını** üretir; site noindex ise "üretim değil" ile AYNI erken çıkış (`Disallow: /`, sitemap satırı yok). | `resolvePayload(): array`, `isPathRestricted(string $path): bool` · korumalı: `panelRoots(): array` |
| `Sub/SitemapResolver.php` (365) | Sitemap girdilerini sayfalar + model verisinden toplar. | `resolvePayload(string $view): array`, `getSummary(): array`, `getData(string $type,int $page=1,bool $onlyCount=false)`, `resolveCorePages(): array` · korumalı: `hasRouteContent(string $cleanUri): bool`, `sourcesSatisfied(array $kaynaklar): bool` |
| `Sub/FeedResolver.php` (108) | RSS kanal + gönderi listesini hazırlar; **sayfa noindex** kayıtları gönderi listesine girmez. | `resolvePayload(): array` |
| `Sub/LlmsResolver.php` (129) | `llms.txt` bölüm/girdi listesini hazırlar. `resolveCorePages()` çıktısını doğrudan kullanır — sitemap kuralları `llms.txt` için de geçerlidir. | `resolvePayload(): array` |

## 3. Akış — `ViewResolver::resolve()` (ViewResolver.php:34-125)

Sırayla denenen **7 hedef dizin** (`:49-90`):

```
resolve('auth/login', ['context'=>'frontend'])
 ├─ TARGET 0: Paths::module($module)->views()          (aktif modül)        :55-60
 ├─ TARGET 1: Paths::project()->views()                (proje — her şeyi ezerer) :63
 ├─ TARGET 2: Paths::module($panel,'suite')->views()    (aktif panel paketi)   :66-71
 ├─        : Paths::module($context)->views()           (bağlam keşfi)         :74-76
 ├─ TARGET 3: Paths::framework()->views()                                      :79-80
 ├─        : frameworkViews . '/RbnCommon'                                    :81
 ├─        : frameworkViews . '/<ucfirst($activePanel)>'                        :84-86
 └─        : frameworkViews . '/<ucfirst($context)>'   (context≠frontend)      :88-90
```

**Önce iki hızlı yol denenir, dizin taraması yapılmadan:**
1. `RbnCommon/<dosya adı>` doğrudan dosya kontrolü (`:99-105`) — en sık kullanılan
   ortak şablon için.
2. Keşif haritası (`$mapper->get("view:{$cacheKey}")`) — önceki çözümün **kalıcı**
   kaydı (`:107-111`).

Sonra dizinler sırayla taranır; bulunan ilk yol **kalıcı haritaya yazılır**
(`:113-122`) ve statik `$resolveCache`'e konur.

**Ölçülen sonuç:** `Paths::init('E:/localhost/projects/<proje>', …)` ile
`resolve('errors/404'|'layouts/master'|'auth/login', ['context'=>'frontend'])`
→ **üçü de `NULL`**. Neden: framework `Resources/Views/` altındaki gerçek dosya
adları `Errors/development.php`, `RbnAuth/auth.rbn.php` gibi **büyük harfli klasör
adı + farklı dosya adı** taşıyor; mantıksal ad ile fiziksel yol eşleşmiyor.
`ViewResolver::resolve()` bu ikisini bağlayan bir kural içermiyor —
çağıran (`FrontendProvider`) `Layouts/header`, `Layouts/footer` gibi adları
deniyor ve `file_exists()` kontrolü yok dönüşünde `handleMissing()` çağrılıyor
(`Providers/UI/FrontendProvider.php:85-87`). Ölçülen çerçeve `projects/<proje>`
üzerinde, `framework` tarafındaki `Resources/Views` (29 dosya) tarandı; ancak
`Resources/Views/RbnCommon/header.rbn.php` gibi dosyalar **`RbnCommon/`
önekiyle** eşleşir, düz `header` ile değil.
Ayrıntı: [acik-sorular.md §1.10](../../acik-sorular.md).

## 4. Akış — `LayoutResolver` bölüm yığını (LayoutResolver.php:53-78)

`@section('ad')` → `$layout->startSection('ad')` → `ob_start()` + yığına adı
(iterasyon `:55-57`). `@endsection` → `endSection()` → `array_pop` + `ob_get_clean`
(`:63-70`). `@yield('ad')` → `getSection('ad')` (`:75-78`).

Ölçülen yığın davranışı (V6):

```
startSection('a'); echo 'A1'; startSection('b'); echo 'B1'; endSection(); echo 'A2'; endSection();
getSection('a') = 'A1A2'      ← iç içe doğru birleşti
getSection('b') = 'B1'
getAllSections() anahtarları = 'b,a'   ← önce kapanan (LIFO) önce
```

**Tuzak (asıl önemli nokta):** `View::result()` içindeki
`$layoutResolver->getExtends()` + `ViewEngine::render()` zinciri (§ README 3.2)
**bu bölüm yığınını otomatik doldurmaz.** Çünkü iç içe render'da `ob_start()`
çağrıları iç içe geçer ve `@section` çağrıları yalnız **view dosyasının kendi
buffer'ında** toplanır. Yani `@extends` + `@section` + `@yield` üçlüsü teorik
olarak var ama **mevcut korpusta kullanılmıyor**:

* Ölçüm: `projects/` + `domains/` + framework `Resources/Views/` altındaki
  **1040 `.php` dosyasında** `@extends(` **0**, `@section(` **0**,
  `@yield(` **1** (`projects/<proje>/Modules/Frontend/Email/Views/app.rbn.php:10`),
  `@import(` çok sayıda.
* Tek `@yield('content')` kullanımı bir **panel e-posta istemcisi ekranıdır** ve
  `@section` üreten bir dosya yoktur → `getSection('content')` her zaman `''`
  döner ve o `<div>` **boş basılır**.

**Sonuç:** Layout sarmalama pratikte **`@import` ile parça birleştirme**
üzerinden yapılıyor (parçalı landing sayfaları:
`projects/customers/<grup-z>/Modules/Frontend/<bolum>/Views/home.rbn.php:1-7`),
`FrontendProvider` ise `Layouts/header` / `Layouts/footer`'ı **ayrı `render()`
çağrılarıyla** basıyor (`Providers/UI/FrontendProvider.php:97-105`).
`.agents/rules/core-architecture.md` §6.2'nin `$content` iddiası kodla
çelişiyor ve bu ölçümle **üçüncü kez** doğrulanmış oldu
([acik-sorular.md §1.3](../../acik-sorular.md)).

## 5. Akış — `SeoResolver::resolve()` öncelik zinciri (SeoResolver.php:21-138)

```
SeoResolver::resolve($context, $overrides)
 ├─ settings->read('seo'|'company'|'social'|'contact'|'appearance')  :26-31
 │    ($isLive = context ∈ {project, frontend, panel}; değilse seoDb boş)
 ├─ $hub = {config, seo, company, social, contact, appearance, context}  :33-41
 ├─ Başlık: overrides.title > seo['meta-title']                       :46
 │    · siteName: appName > config.app_name > company-name > FRAMEWORK_NAME :47-53
 │    · boşsa/site adıysa tam SEO başlığı; değilse "Başlık | SiteAdı" :55-68
 ├─ Açıklama: overrides > meta-description > company-slogan > config.app_slogan > defaults :70-78
 ├─ keywords: overrides > meta-keywords > defaults                    :80-82
 ├─ robots: overrides > meta-robots > defaults                        :84-86
 ├─ module-name: overrides > appName > company-name > config.app_name > 'RBN Project' :90-98
 ├─ module-version: overrides > ProjectVersionResolver::resolve(...)   :104-106
 ├─ resolveFaviconRaw($hub, $overrides)                                :109
 └─ ['context','meta','og','label','copyright']                        :131-137
```

**Kritik:** `$overrides['title']` **her zaman** önceliklidir — `View::metaseo()`
tam olarak bu yolu kullanır (`View.php:88-96` → `SeoService::prepare` →
`SeoBuilder::prepare` → `SeoResolver`).

## 6. Yapılandırma ve varsayılanlar

| Sabit/değer | Değer | Kaynak |
|---|---|---|
| `SeoConfig::NAME` / `VERSION` | `RbnSeo` / `1.3` | `Configs/SeoConfig.php:18-19` (ölçüldü) |
| `SeoConfig::SLOGAN` | `'Professional SEO and Metadata Service'` | `SeoConfig.php:20` |
| `RobotsConfig::DEFAULT_CONFIG` | `{"crawl_delay":1,"bing_crawl_delay":1,"yandex_crawl_delay":2,"ecommerce_enabled":false,"google_news_enabled":false,"social_media_crawl":true,"china_market":false,"yandex_clean_param":true,"custom_allowed_paths":[],"custom_disallowed_paths":[],"custom_banned_bots":[],"cms_version":"1.3"}` | `Configs/RobotsConfig.php:132` (ölçüldü) |
| `RobotsConfig::SYSTEM_DISALLOWS` / `CORE_DISALLOWS` / `ASSET_DISALLOWS` | sistem/çekirdek/varlık yasakları | `RobotsConfig.php:33, :63, :109` |
| `ViewResolver` uzantı sırası | `['.php', '.rbn.php']` | `ViewResolver.php:43` |
| `ViewResolver` keşif anahtarı | `"view:".md5($view.serialize($options))` | `ViewResolver.php:36, :95` |
| `LayoutResolver` sistem korumalı bölüm | yok — `clear()` elle çağrılır | `LayoutResolver.php:91` |

## 7. Tuzaklar ve kurallar (ölçülmüş)

1. **`ViewResolver` hedef 1 (proje) her şeyi ezer** (`:63` yorumu: "Overrides
   everything else"). Yani modül/paket içindeki bir şablon adı, proje
   `Resources/` altında aynı adla varsa **proje kazanır**.
2. **`isPathRestricted()` ölçülen sonuçlar** (V5):

   | Yol | Sonuç |
   |---|---|
   | `/admin` | `true` |
   | `/storage` | `true` |
   | `/vendor/x` | `true` |
   | `/.env` | **`false`** |
   | `/index.php` | `false` |
   | `/rbn-admin/panel` | **`false`** |
   | `/cpanel` | **`false`** |
   | `/` | `false` |

   Yani `RobotsConfig` **`.env`, panel öneki (`dashboard_prefix`) ve `/cpanel`
   yollarını kapsamıyor**. `ALLOWED_PATHS` (`RobotsConfig.php:117`) yalnız
   `SYSTEM_DISALLOWS`+`CORE_DISALLOWS`+`ASSET_DISALLOWS` ile birleşiyor;
   panel öneki `RouteBlueprint::DASHBOARD_PREFIX` üzerinden ayrı yönetiliyor
   ve bu resolver'a **girmiyor**. `.env` dosyası `public` kökünde değilse sorun
   yoktur; panel öneği ise gerçek bir boşluktur.
   *(Kod değiştirilmedi — salt okunur.)*
3. **`RobotsResolver::resolvePayload()` `Paths` kullanır** (`RobotsResolver.php:9`)
   → `Paths::project()` **init öncesi** çağrılırsa `LogicException` verir
   ([System/Paths.md §5.4](../System/Paths.md)).
4. **`CrawlerResolver::callServiceMethod()` değişken metot adı çağırır**
   (`:72`) — güvenlik sınırı **yapılandırma katmanındadır**, kodda allow-list yok.
   `crawler` yapılandırması `projects` tablosundan geldiği için güvenlik
   `crawler_map` yazan kişinin yetkisine bağlıdır.
5. **`LayoutResolver::$sections` ve `$sectionStack` statik DEĞİL**, örnek
   başına yaşar; `ViewEngine` `cluster('view_engine')` **tekil** olduğu için
   aynı istek içinde bölümler birikir. İstek sonu temizlenmez → uzun süreli CLI
   süreçlerinde birikim mümkündür (`clear()` çağrılmıyor, arama sonucu boş).
6. **`SchemaResolver::meta()` DB'ye gider** (`Core\Base\Services\BaseService`
   kullanır) → schema render'ı aktif proje DB'si açık değilse boş döner.
7. **noindex tek kaynak (FW-094-NOINDEX).** Robots/sitemap/llms/feed AYNI iki yardımcıya
   bakar: `SeoResolver::isSiteNoindex()` (site) ve `SeoResolver::isPageNoindex($kayıt)` (sayfa);
   `CrawlerProvider` yalnızca devreder. **Site noindex** = `view_mapping[site]['robots']['noindex'] = true`
   (`getSeoConfig()['robots']['noindex']`) VEYA panel `seo.meta-robots` değeri `noindex` içeriyor.
   Sonuç: sitemap/llms/feed 404, `robots.txt` `Disallow: /` (sitemap satırı yok). **Sayfa noindex**
   = kayıt satırında `robots` / `meta_robots` / `meta-robots` `noindex` içerir ya da `noindex`
   bayrağı doludur; site normal yayın yapar, yalnız o kayıt sitemap + llms.txt + feed'e girmez.
   ⚠️ Denetleyicide çalışma anında verilen `$this->noIndex()` çağrısı meta etiket ve
   `X-Robots-Tag` başlığını sağlar ama tarayıcı rotalarının (sitemap vb.) GÖREBİLECEĞİ kalıcı kayıt
   DEĞİLDİR; site genelini kapatmak için yukarıdaki yapılandırma anahtarı yazılmalıdır.
   Önbellek: bkz. `Controllers.md` (site noindex önbellekten önce karar verilir); sayfa noindex
   değişimi çıktıya orta katman önbellek süresi (TTL) kadar gecikmeyle yansır.
8. **Sitemap girdisi iki katmanlı bir filtreden geçer.** Katman 1 mevcut
   süzgeçlerdir (GET · `{`/`(` yok · `modal` değil · modül namespace'i ·
   `isPathRestricted()` · `/` değil). Katman 2 **`hasRouteContent()`**'dir:
   *içeriğe bağlı* standart rotalar (`CONTENT_BACKED_ROUTES`) yalnızca
   gerçekten içerik varken listelenir. Bunun iki çekirdek kuralı vardır:
   * **`blocked-by:shadowed-page`** (engelleyici) — slug'ı DB'de aktif sayfa
     olarak varsa `RedirectManager::redirectOldUrls()` o yolu kalıcı olarak
     `/sayfa/{slug}` adresine 301'ler; rota **hiçbir zaman 200 dönmez** ve
     kanonik adres zaten listelendiği için rota listelenmez.
   * **`satisfied-by:faq`** (olumlu) — aktif SSS satırı varsa rota kendi
     içeriğini 200 ile render eder ve listelenir. Sayfa da SSS de yoksa
     denetleyici ana sayfaya yönlendirir (302) → listelenmez.

   **Kutup kuralı** (`sourcesSatisfied()`): kaynaklar `|` ile ayrılır ve her
   biri `blocked-by:` ya da `satisfied-by:` önekiyle kutbunu söyler. Tetiklenen
   herhangi bir engelleyici rotayı dışlar; olumlu kaynak tanımlıysa en az biri
   sağlanmalıdır; yalnız engelleyici tanımlıysa ve hiçbiri tetiklenmediyse rota
   listelenir. Yani engelleyici kaynak tek başına "listele" demez ama yokluğu
   da rotayı dışlamaz.
   Kural **çekirdek standardıdır**; projeler kendi başına özelleştirmez.
   Sorgu hata verirse rota listelenmez (güvenli taraf: eksik sayfa, bozuk adres
   değil). `resolveCorePages()` ayrıca **sessiz `catch` kullanmaz**:
   rota tablosu okunamazsa `error_log('[RBN-CRAWLER] …')` yazar.
9. **`isPathRestricted()` panel/giriş köklerini de kısıtlı sayar**
   (`panelRoots()`): `RouteBlueprint::AUTH_ROOTS` + çekirdek web rotasının da
   yönlendirme altına aldığı `giris`. Bu aynı metot üç yerden çağrılır —
   sitemap girdisi, robots disallow listesi ve trafik istatistiği — dolayısıyla
   panel yollarının üçünde de tutarlı biçimde elenir.

## 8. Örnek (gerçek koddan)

```php
// Core/Render/Resolvers/LayoutResolver.php:53-70
public function startSection(string $name): void
{
    ob_start();
    $this->sectionStack[] = $name;
}

public function endSection(): void
{
    if (empty($this->sectionStack)) {
        return;
    }
    $name = array_pop($this->sectionStack);
    $this->sections[$name] = ob_get_clean();
}
```

```php
// Ölçülen çıktı — gerçek korpusta @yield tek kullanım
// projects/<proje>/Modules/Frontend/Email/Views/app.rbn.php:8-11
//   <div class="rm-main-workspace" id="mainWorkspace">
//       @yield('content')
//   </div>   ← 'content' bölümü hiç doldurulmuyor → boş basılır
```

## 9. İlgili belgeler

* [Core/Render genel](README.md) · [Providers](Providers.md) · [Builders](Builders.md) ·
  [Configs](Configs.md) · [Handlers](Handlers.md)
* [Core/System/Discovery.md](../System/Discovery.md) (keşif motoru) ·
  [Core/System/Paths.md](../System/Paths.md) · [Core/Base/Data.md](../Base/Data.md) (`SettingsService`)
* [Kavram: yapılandırma](../../kavramlar/02-yapilandirma.md) ·
  [Açık sorular §1.3 / §1.10](../../acik-sorular.md)