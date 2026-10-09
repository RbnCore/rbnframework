# Core/Render/Builders — durum biriktiriciler (varlık, SEO, schema, breadcrumb, sitemap, feed, llms)

> **Doğrulanan kod tabanı:** `c23b431f` · **Tarih:** 2026-10-05 · **Yayın:** 0.9.6 = bu commit + sonrası; belge yalnız doğrulama anındaki kodu anlatır
> **Kaynak klasör:** `Core/Render/Builders/` — **7 `*.php`**.
> **Envanter:** 7 dosyanın **7'si** anlatıldı.
> **Doğrulama platformu:** Windows + PHP 8.3, `AssetBuilder` gerçekten çalıştırılarak.

## 1. Ne işe yarar, kim kullanır

Yedi sınıfın ortak işi **veri toplamak ve bir çıktı dizisine/stringsine dönüştürmek**;
hiçbiri HTTP yanıtı yazmaz, hiçbiri `ViewEngine`'i çağırmaz. Hepsi
`BaseComponent`/`BaseRender`'dan türer ve **kayıt altında `handlers`** olarak durur
(`SystemRenderMapTrait.php:37-43`) — yani `$this->handler('seoBuilder')` ile
erişilir. Çıktıyı asıl basan sınıflar [Providers](Providers.md)'tadır.

**Tuzak:** `handler('seoBuilder')` bir **Builder**'ı döndürür, `resolver('seo')`
ise **Resolver**. İkisi karıştırılmamalı; `SeoBuilder` override'ları biriktirir,
`SeoResolver` bunları `SettingsService` verisiyle birleştirir.

## 2. Klasör/dosya envanteri (7/7)

| Dosya | Görev | Önemli public yöntemler |
|---|---|---|
| `AssetBuilder.php` (378) | Varlık kuyruğu + derleyici + sıralayıcı. `prepare()` çekirdek yığını ve özel kaynakları toplar, `build()` sıralayıp `styles`/`scripts` dizisi döndürür. | `reset(): self`, `addStyle(string $path,int $priority=10): self`, `addScript(string $path,array $attrs=[],bool $inHead=false,int $priority=10): self`, `addFont(string $name,int $priority=15): self`, `prepare(string $appContext,$source=null): self`, `build(): array`, `compile($item,string $appContext='frontend'): ?array` · korumalı: `collect()`, `injectCoreStack()`, `generateWebUrl()`, `resolveToken()`, `detectType()` |
| `SeoBuilder.php` (195) | Sayfa bazlı SEO **override** biriktirici. `prepare()` resolver çıktısıyla birleştirip `payload()` üretir. | `reset(): self`, `setTitle(?string)`, `setDescription(?string)`, `setKeywords(?string)`, `setCanonical(?string)`, `noIndex(string $directives='noindex, nofollow')`, `setImage(string)`, `getOverrides(): array`, `payload(): array`, `hasPayload(): bool`, `prepare(string $context='project',array $overrides=[]): self`, `build(array $resolved): array` |
| `SchemaBuilder.php` (527) | schema.org JSON-LD üreticisi. 15 tip hazır metot + `schema()` geçici tip. | `schema(string $type,array $data): self`, `addRaw(array $schema): self`, `reset(): self`, `getSchemas(): array`, `faq(array $faqs)`, `breadcrumbs(array $steps)`, `product(array $data)`, `event(array $data)`, `jobPosting(array $data)`, `localBusiness(array $company)`, `course(array $data)`, `custom(array $data)`, `geo(?string $coordinates): array`, `contact(array $data)`, `blogPost(array $data)`, `seriesDetail(array $data)`, `movieDetail(array $data)`, `prosCons(array $data,array $meta=[])` |
| `BreadcrumbBuilder.php` (130) | Kırıntı (breadcrumb) adımlarını biriktirir; ana adımı korpustan, alt adımları `BreadcrumbResolver`'dan alır. | `reset(): self`, `build(…)`, `add(string $title,string $url,string $icon='',bool $active=false): self` |
| `SitemapBuilder.php` (114) | Sitemap `<url>` girdilerini biriktirir ve `<urlset>`/`<sitemapindex>` XML'i üretir. | `reset(): self`, `addUrl(string $loc,string $priority='0.5',string $changefreq='monthly',?string $lastmod=null): self`, `addUrls(array $urls): self`, `getEntries(): array`, `hasEntries(): bool`, `buildXml(bool $isIndex=false): string` |
| `FeedBuilder.php` (92) | RSS 2.0 feed XML'i üretir. | `setChannel(array $channel): self`, `addPosts(array $posts): self`, `buildXml(): string` · korumalı: `clean(string $string,bool $stripNewlines=false): string` |
| `LlmsBuilder.php` (94) | `llms.txt` (Markdown) üretir. | `reset(): self`, `setSiteInfo(string $siteName,string $siteUrl): self`, `addSection(string $title,array $items): self`, `buildMarkdown(): string` · statik: `sanitizeText(string $text): string`, `link(string $title,string $url): string` (içerik kaynaklı metni tek satıra / güvenli bağlantı biçimine indirir) |

## 3. Akış — `AssetBuilder` (en karmaşık zincir)

`FrontendProvider::render()` → `AssetService::prepareContext($context)`
(`Services/AssetService.php:28-31`) → `AssetBuilder::prepare($appContext, $source)`.

```
AssetService::prepareContext('frontend')            Services/AssetService.php:28
  └─ AssetBuilder::prepare('frontend', 'frontend')  Builders/AssetBuilder.php:79
     1. injectCoreStack($payload, $appContext)      :87 → :245-283
        ├─ route config 'css_engine' okunur (bootstrap | rbn)     :250-252
        ├─ AssetBundles::STACK_MAP['universal'] paketleri        :257-263
        │    · auth/panel ya da rbn motoru ise bootstrap,
        │      bootstrap_icons, font_awesome ATLANIR (0 KB bypass) :259-261
        ├─ auth/panel ya da (rbn motoru + frontend) → 'rbn_master' :266-268
        ├─ STACK_MAP[$appContext] paketleri                       :270-274
        └─ auth/panel/rbn → common.css + rbnAlert.css filtrelenir  :277-282
     2. collect($payload, $source, $appContext)      :90 → :207-240
        · AssetBundles::BUNDLES ile ad→paket açılımı             :215-219
        · Özyineleme derinliği 8 ile sınırlı                     :209-213
        · '.css' veya '@font/' ile başlayan → styles, diğer → scripts :232-237
     3. frontend özel font varsa varsayılan Inter temizlenir        :93-113
     4. Her girdi compile() edilip kuyruğa eklenir               :116-134
     5. return $this
AssetService::render('header') → AssetBuilder::build()  Services/AssetService.php:51-66
  ├─ frontend & hiç font yoksa '@font/Inter' + priority 5           :154-162
  ├─ uasort() × $sortFn (proje son, çekirdek motor ilk, sonra prio) :164-196
  └─ ['styles'=>…, 'scripts'=>…]
```

### 3.1 Sıralama kuralı — ölçüldü

`build()` içindeki `$sortFn` üç kademeli (`AssetBuilder.php:164-192`):
1. `/project-assets/` ile başlayan **daima en son**;
2. `bootstrap` / `rbn-master.css` / `rbn-core.css` içeren **daima ilk**;
3. sonra `priority` artan.

Ölçülen çıktı (`DOC-AGAC-3-dogrula3.php` V4):

```
/framework-assets/css/rbn-core.css         prio=1
/framework-assets/css/bootstrap.min.css    prio=5
http://localhost/framework-assets/fonts/inter   prio=5   ← otomatik Inter
/project-assets/css/site.css               prio=99
```

### 3.2 Token çözümleme ve web URL'i (AssetBuilder.php:289-377)

`compile($item)`:
1. `resolveToken($path)` → `@project/` veya `@fw/` öneki soyulur; kalan
   kaynaksızsa `null` hint döner; `@font/` → hint `font` (`:350-369`).
2. `DiscoveryEngine::instance()->assets()->resolve($cleanPath, $sourceHint)`
   fiziksel dosyayı bulur; bulunamazsa ve `http` içermiyorsa **`null` döner**
   (kuyruğa eklenmez — `:297-299`).
3. Sürüm: `AssetConfig::VERSION` (`1.0`), fiziksel dosya varsa `filemtime()`
   (`:301-304`).
4. `generateWebUrl()`:
   * hint `font` → `/framework-assets/fonts/<slug>` (`:320-322`);
   * `http` içeriyorsa olduğu gibi döner (`:324-326`);
   * framework kaynağı ve çok parçalı yol → `<küme>/<dosya>` (küme = ilk parça,
     `images` hariç tam yol küçük harfle) (`:335-342`);
   * sonuç: `url('/<proxy>/<yol>=v<sürüm>')` (`:347`).
5. `detectType()` → uzantı → `AssetConfig::EXTENSION_MAP`, varsayılan `js` (`:373-377`).

## 4. Yapılandırma ve varsayılanlar

| Sabit | Değer | Kaynak |
|---|---|---|
| `AssetConfig::VERSION` | `1.0` | `Configs/AssetConfig.php:18` |
| `AssetConfig::PROXY_SETUP['project']` | `{"path":"project-assets","token":"@project/"}` | `AssetConfig.php:26-29` (ölçüldü) |
| `AssetConfig::PROXY_SETUP['framework']` | `{"path":"framework-assets","token":"@fw/"}` | `AssetConfig.php:30-33` (ölçüldü) |
| `AssetConfig::EXTENSION_MAP` | `css→css, js→js, mjs→js, less→css, scss→css, png/jpg/svg→image, json→data` | `AssetConfig.php:80-90` |
| `SeoConfig::VERSION` | `1.3` | `Configs/SeoConfig.php:19` (ölçüldü) |
| Varsayılan font önceliği | `5` (en üstte yüklensin) | `AssetBuilder.php:159` |
| Varsayılan font | `@font/Inter` (yalnız `frontend` bağlamında) | `AssetBuilder.php:154-155` |
| `addFont` önceliği | `15` | `AssetBuilder.php:70` |
| Özyineleme sınırı | `8` | `AssetBuilder.php:210` |
| CSS motoru tercihi | route config `css_engine`: `bootstrap` (varsayılan) \| `rbn` | `AssetBuilder.php:250-252` |
| `BreadcrumbConfig::TERMINAL_ACTIONS` | `["view","show","preview","download"]` | `Configs/BreadcrumbConfig.php:77` (ölçüldü) |

## 5. Tuzaklar ve kurallar (ölçülmüş)

1. **`addStyle()` ve `addScript()` ikisi de yinelenen yolu eler — koruma
   çalışıyor.** `addStyle()` `!isset($this->styles[$path])` kullanır
   (`AssetBuilder.php:41`), `addScript()` dizi taraması yapar (`:52-56`).
   Ölçüldü: `addStyle('x.css',1)`, `addStyle('y.css',2)`, `addStyle('x.css',9)`
   → `$styles` **anahtarları `["x.css","y.css"]`** (2 eleman) ve `x.css`
   önceliği **1'de kalır** (ikinci çağrının `9` değeri yok sayılır).
   `addScript('/a.js')` iki kez → `build()['scripts']` **1 eleman**.
   *(Not: `build()['styles']` bu durumda **3** eleman döndürür — fark, otomatik
   Inter fontundur (`§5.2`), çoğalma değildir. İlk ölçüm yanlış yorumlanmıştı;
   `acik-sorular.md` §1.8 bu düzeltmeyle güncellendi.)*
2. **⚠️ `addFont()` gerçek bir çift-font riski taşıyor (ölçüldü).**
   `build()`'in "hiç font yoksa Inter ekle" kontrolü **derlenmiş** yola bakar
   (`str_contains($path, '/fonts/')`, `AssetBuilder.php:148`), ama `addFont()`
   kuyruğa **derlenmemiş** `@font/<ad>` yazar (`:72`). Ölçülen sonuç:

   | Çağrı | `$styles` anahtarları | `build()['styles']` |
   |---|---|---|
   | `addFont('Inter')` | `["@font/Inter"]` | **1** — `…/fonts/inter` (prio 5) |
   | `addFont('Roboto')` | `["@font/Roboto"]` | **2** — `…/fonts/inter` (prio 5) **+** `@font/Roboto` (prio 15) |

   Yani **`addFont('Roboto')` çağrısında varsayılan Inter de eklenir**: kontrol
   `@font/Roboto` yolunda `/fonts/` **bulamaz** (yol henüz derlenmemiş), font
   yok sayar ve Inter'i ekler. `prepare()` akışında bu **düzelir**
   (`AssetBuilder.php:93-113` temizliği `payload` üzerinde çalışır ve `@font/`
   ile başlayan ham yolları görür); ama **`addFont()` doğrudan
   `AssetService`/controller üzerinden** çağrılırsa çift font basılır
   (`FrontendBaseController::addAsset()` → `AssetService::addStyle()` yolu).
   *(Beklenen: `build()` içindeki font kontrolü `str_contains($path, '/fonts/')`
   **veya** `str_starts_with($path, '@font/')` ile iki biçimi de kabul etsin.
   Kod değiştirilmedi — belge salt okunurdur.)*
3. **`prepare()` font temizliği yalnız `frontend` bağlamında çalışır**
   (`:93`). `panel`/`auth` bağlamında özel font eklenirse temizlenmez.
4. **`compile()` çözülemeyen yolu `NULL` döndürür** → kuyruğa **eklenmez**
   (`:297-299`). Ölçüldü:
   | Girdi | Sonuç |
   |---|---|
   | `/framework-assets/css/yok.css` | `NULL` |
   | `@project/css/a.css` | `NULL` (proje diskinde yok) |
   | `@font/Inter` | `{"path":"…/framework-assets/fonts/inter","type":"js", …}` |
   | `https://cdn.example.com/x.js` | `{"path":"https://cdn.example.com/x.js","type":"js", …}` (olduğu gibi) |

   Yani **bulunamayan varlık sessizce düşer** (404 değil, hiç `<link>`/`<script>`
   bile basılmaz) ve dış CDN adresleri doğrulamasız geçer.
5. **`injectCoreStack()` panel/auth bağlamında `rbn_master` yalnız `auth`
   için yüklenir** (`:266`: `$isAuthContext || ($isRbnEngine && $appContext === 'frontend')`).
   Yani **panel (`auth` değil) + `rbn` motoru** kombinasyonunda `rbn_master`
   yüklenmez; yalnız `bootstrap`'lar atlanır (`:259`). Panelin `rbn` motoruyla
   stil eksikliği yaşaması bu satırdan gelir.
6. **`SchemaBuilder::geo()` dönüş tipi `array`, diğerleri `self`** —
   `schema(string $type,array $data)` zincirine `geo()` doğrudan eklenemez
   (`:376`).
7. **`SitemapBuilder::buildXml(bool $isIndex=false)`** — `<sitemapindex>` üretimi
   için `true` gerekir; `SitemapResolver` hangisini seçtiğine göre çağırır.
8. **`SitemapBuilder::addUrl()` öncelik/changefreq parametreleri string'dir**
   (`'0.5'`, `'monthly'`); XML'e olduğu gibi yazılır, doğrulama yok.
9. **`BreadcrumbBuilder::build()` imzası çok parametreli** (`:31-…`);
   kaynak `BreadcrumbResolver::resolve()`'dır. Kırıntı akışı:
   `BreadcrumbProvider::resolveAndRender()` → `BreadcrumbResolver` → `BreadcrumbBuilder`.

## 6. Örnek (gerçek koddan)

```php
// Core/Render/Services/AssetService.php:98-100  (kuyruğa ekleme)
public function addScript(string $path, array $attrs = [], bool $inHead = false, int $priority = 10): void
{
    $this->handler('assetBuilder')->addScript($path, $attrs, $inHead, $priority);
}
```

```php
// Ölçülen çıktı — addFont + otomatik Inter davranışı
$ab->reset(); $ab->addFont('Inter');
// build()['styles'] => [ ['path' => 'http://localhost/framework-assets/fonts/inter', 'priority' => 5] ]
```

## 7. İlgili belgeler

* [Core/Render genel](README.md) · [Providers](Providers.md) ·
  [Configs](Configs.md) · [Resolvers](Resolvers.md)
* [Kavram: asset sistemi](../../kavramlar/05-asset-sistemi.md) ·
  [Support/Definitions](../../Core/Support/Definitions.md) (`AssetBundles`, `AssetConvention`) ·
  [Açık sorular §1.8](../../acik-sorular.md) (`addStyle` çoğalması)