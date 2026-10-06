# Core/Render/Configs — sabit tanım dosyaları (5 dosya)

> **Doğrulanan kod tabanı:** `c23b431f` · **Tarih:** 2026-10-05 · **Yayın:** 0.9.5 = bu commit + sonrası; belge yalnız doğrulama anındaki kodu anlatır
> **Kaynak klasör:** `Core/Render/Configs/` — **5 `*.php`**.
> **Envanter:** 5 dosyanın **5'i** anlatıldı.
> **Doğrulama platformu:** Windows + PHP 8.3, tüm sabitler reflection/erişimle **ölçüldü**.

## 1. Ne işe yarar, kim kullanır

Beş `BaseConfig` türevi, Render katmanının **derleme zamanı sabitlerini** tutar:
asset vekili kurulumu, view yönerge eşlemesi, robots kuralları, SEO varsayılanları,
breadcrumb sözlüğü. Hepsinin tek ortak yanı **statiktir** — örnek üretilmez,
`$service->config(...)` ile okunmaz, doğrudan `Sınıf::SABİT` çağrısıdır.

**Kimler kullanır:** `ViewEngine` (`ViewConfig`), `AssetBuilder` (`AssetConfig`),
`AssetController` (`AssetConfig`), `SystemGuardHandler` (`AssetConfig`),
`RobotsResolver` (`RobotsConfig`), `SeoResolver`/`SeoBuilder` (`SeoConfig`),
`BreadcrumbResolver`/`BreadcrumbBuilder` (`BreadcrumbConfig`).

**Tuzak:** Dizin adı **`Configs`** (çoğul). Kayıt haritasında iki yerde **tekil**
`Config` yazılmıştır: `SystemResourceMapTrait` içinde
`resource.metadata['KIT_'] => 'Core\Render\Config\AssetConfig'` ve
`'SEO_' => 'Core\Render\Config\SeoConfig'` — bu iki yol **gerçek değil**
([System/Registries.md §5](../System/Registries.md) §5.3'te ölçülmüştür).

## 2. Klasör/dosya envanteri (5/5)

| Dosya | Görev | Sabitler / yöntemler |
|---|---|---|
| `AssetConfig.php` (91) | Varlık vekili kurulumu, küme kaydı, proxy host beyaz listesi, uzantı→grup haritası. | `NAME='RbnKit'`, `VERSION='1.0'`, `PROXY_SETUP`, `CLUSTER_REGISTRY`, `PROXY_ALLOWED_HOSTS`, `EXTENSION_MAP` |
| `ViewConfig.php` (51) | **View yönerge eşlemesi** (10 yönerge). Yapısal yönergeler `ViewEngine`'de. | `static directives(): array` |
| `RobotsConfig.php` (146) | `robots.txt` kuralları: bot kuralları, 4 disallow listesi, izinli yollar, varsayılan yapılandırma. | `NAME='RbnRobots'`, `VERSION='1.0'`, `BOT_RULES=[]`, `ROBOTS_DISALLOWS`, `SYSTEM_DISALLOWS`, `CORE_DISALLOWS`, `ASSET_DISALLOWS`, `ALLOWED_PATHS`, `DEFAULT_CONFIG` |
| `SeoConfig.php` (45) | SEO varsayılanları ve marka görselleri (data-URI SVG). | `NAME='RbnSeo'`, `VERSION='1.3'`, `SLOGAN`, `OG_IMAGE`, `LOGO_SVG`, `static defaults(): array` |
| `BreadcrumbConfig.php` (83) | Kırıntı sözlüğü: gizlenen alan adları, aksiyon→ikon/etiket haritası, terminal aksiyonlar. | `NAME_BLACKLIST`, `ACTION_ICON_MAP`, `TERMINAL_ACTIONS` |

## 3. `AssetConfig` — ölçülen değerler (AssetConfig.php)

```
PROXY_SETUP anahtarları            = ['version','project','framework']
PROXY_SETUP['version']             = '1.0'
PROXY_SETUP['project']             = {"path":"project-assets","token":"@project/"}
PROXY_SETUP['framework']           = {"path":"framework-assets","token":"@fw/"}
PROXY_ALLOWED_HOSTS                = []            ← BOŞ (dış CDN kullanımı için elle eklenmeli)
CLUSTER_REGISTRY anahtarları        = ['core','project','kit']
  core    => ['label'=>'Core',    'folder'=>'']
  project => ['label'=>'Project', 'folder'=>null]     ← null = Public Root referansı
  kit     => ['label'=>'RbnKit',  'folder'=>'RbnKit']
EXTENSION_MAP = css→css, js→js, mjs→js, less→css, scss→css,
                png→image, jpg→image, svg→image, json→data
```

`PROXY_SETUP` doğrudan HTML'e yansır: `View::result()` `:193-194` bu değerlerden
`/project-assets/` ve `/framework-assets/` öneklerini alıp `normalizeHtmlUrls()`
'a verir.

## 4. `RobotsConfig::DEFAULT_CONFIG` — ölçülen tam değer (RobotsConfig.php:132)

```json
{
  "crawl_delay": 1,
  "bing_crawl_delay": 1,
  "yandex_crawl_delay": 2,
  "ecommerce_enabled": false,
  "google_news_enabled": false,
  "social_media_crawl": true,
  "china_market": false,
  "yandex_clean_param": true,
  "custom_allowed_paths": [],
  "custom_disallowed_paths": [],
  "custom_banned_bots": [],
  "cms_version": "1.3"
}
```

**Ölçülen tutarsızlık:** `cms_version = "1.3"` ile `SeoConfig::VERSION = "1.3"`
aynı değeri taşıyor. Bu iki sürüm **farklı sistemlere** ait (SEO cms sürümü ≠
framework sürümü) ve şu an çakışıyor; `SeoConfig::VERSION` değişirse
`RobotsConfig::cms_version` **elle güncellenmek zorunda** (çapraz bağı yok).
`RobotsConfig::VERSION` ise `'1.0'`'dır. Yani aynı dosyada iki farklı sürüm
sabitleri yan yana duruyor.

`BOT_RULES` **boş dizi** (`:20`) — bot kuralları projeye göre tanımlanıyor.

## 5. `BreadcrumbConfig` — ölçülen değerler (BreadcrumbConfig.php)

`TERMINAL_ACTIONS = ["view","show","preview","download"]` (`:77`).
Bu liste, aksiyon adı bunlardan biriyse kırıntının **son adım** olduğunu
belirler (alt sayfa üretilmez).

`NAME_BLACKLIST` (17 alan, `:17`):
`id, parent_id, user_id, created_at, updated_at, deleted_at, slug, is_active,
status, type, order, sort, hit_count, password, token, csrf_token, remember_token,
view_source`

**Güvenlik açısından önemli:** `password`, `token`, `csrf_token`, `remember_token`
listedir → kırıntı etiketi olarak **asla basılmaz**. Bu, `BreadcrumbResolver`'ın
`normalizeIcon()`/`build()` akışındaki ilk savunmadır (veritabanı sütun
adlarından etiket üretildiği için).

`ACTION_ICON_MAP` — **27 aksiyon**, her biri `{icon, label}`:
`index, logs, log, edit, update, delete, remove, create, add, view, show,
settings, config, list, search, history, stats, analytics, report, users,
profile, developer, unread, read, trash, group, manage, wizard`
Örnekler: `edit => {icon:'bi bi-pencil-square', label:'Düzenle'}`,
`trash => {icon:'bi bi-trash', label:'Çöp Kutusu'}`.

**Tuzak:** İkonlar **Bootstrap Icons** sınıflarıdır (`bi bi-*`). `AssetBuilder`
panel/auth bağlamında `bootstrap_icons`'ı **atlar** (Builders.md §5.5) — yani
panel/auth'ta kırıntı ikonları **stil olarak yüklü değildir**, ikon yerine boş
kutu çizilir. Bu, iki katmanın birbirinden habersiz kararıdır.

## 6. `ViewConfig::directives()` — **10** yönerge (tam liste)

Ölçüldü: `count(ViewConfig::directives())` = **10**.

[README §3.4](README.md) tablosunda birebir karşılaştırmalı liste var. Özet:

| Yönerge | Kaynak |
|---|---|
| `@hasrole` / `@endhasrole` | `ViewConfig.php:28-29` |
| `@csrfToken`, `@csrf`, `@method('…')` | `:32-34` |
| `@sys('…')` | `:37` |
| `@path(…)`, `@import(…)` | `:43-44` |
| `@php` / `@endphp` | `:47-48` |

**Güvenlik notu (kod yorumu `:40-42`):** `@import` → `ViewEngine::import()` →
`path()` + `compile()`; **iki katman da kök sınırı kontrolü yapar**, yani
`@import('../../evil.php')` `require` edilemeden reddedilir.

**Yapısal yönergeler `ViewConfig`'te DEĞİLDİR:** `@if/@elseif/@else/@endif`,
`@foreach/@endforeach`, `@for/@endfor`, `@extends/@section/@endsection/@yield`
→ `ViewEngine::parseDirectives()` içinde sabit dizi (`ViewEngine.php:216-233`),
`ViewConfig::directives()` bunlara `array_merge` ile eklenir (`:236-237`).
**Sonuç:** `ViewConfig`'i düzenlemek yeni yönerge ekler; yapısal yönergeleri
değiştirmek için `ViewEngine` düzenlenmelidir.

## 7. Tuzaklar ve kurallar

1. **`ViewConfig::directives()` her çağrıda yeni dizi kurar** (`:23-49`) —
   sabit dizi değil. `ViewEngine` bunu **bir kez** çağırıp
   `self::$cachedDirectives` içinde saklar (`ViewEngine.php:236-237`), yani
   `ViewConfig`'i çalışma zamanında değiştirmek `ViewEngine` önbelleğini
   geçersiz kılmaz.
2. **`@csrf` ve `@csrfToken` `ViewHelperTrait` DNA'sına bağlıdır**
   (`ViewConfig.php:31` yorumu) — `csrfToken()`/`csrfField()` metotları view
   nesnesinde **yoksa** derleme çalışma zamanında fatal verir. Bu metotlar
   `BaseManager`/`BaseController` public listesinde **ölçüldü** (`csrfToken`,
   `csrfField`, `csrfMeta`, `methodField` — hepsi public). `View` `BaseRender`
   → `BaseComponent` → `BaseContextTrait` zinciri üzerinden erişir.
3. **`PROXY_ALLOWED_HOSTS` boş olduğu için dış CDN 404 verir**
   (Controllers.md §3.1, ölçüldü).
4. **`SeoConfig::OG_IMAGE` ve `LOGO_SVG` data-URI SVG'dir** (`:23, :26`) —
   dosya sistemi bağımlılığı yoktur, ama **`<` karakteri ham** yazılmıştır;
   `htmlspecialchars` uygulayan bir katmandan geçerse bozulur. Ölçüldü: değerler
   `data:image/svg+xml,<svg …>` biçiminde, `&quot;` **yok** (ham tırnak).
5. **`CLUSTER_REGISTRY['project']['folder'] = null`** (`:47`) — "Public Root
   referansı" anlamına gelir; `AssetBuilder` bu anahtarı kullanmaz, `null`
   değer `strtolower(null)` gibi bir yerde çağrılırsa PHP 8.3'te **deprecated
   uyarısı** verir (kodda bu yol ölçülmedi).

## 8. Örnek (gerçek koddan)

```php
// Core/Render/Configs/AssetConfig.php:24-34
public const PROXY_SETUP = [
    'version' => self::VERSION,
    'project' => [
        'path' => 'project-assets',
        'token' => '@project/'
    ],
    'framework' => [
        'path' => 'framework-assets',
        'token' => '@fw/'
    ]
];
```

```php
// Core/Render/View.php:189-202  (sabitler doğrudan HTML'e yansır)
$setup = AssetConfig::PROXY_SETUP;
$source = in_array($this->type, ['framework','core'], true) ? 'framework' : 'project';
$proxyPath  = $setup[$source]['path'] ?? 'project-assets';
$prPrefix   = '/' . ($setup['project']['path']   ?? 'project-assets') . '/';
$fwPrefix   = '/' . ($setup['framework']['path'] ?? 'framework-assets') . '/';
return $this->renderedHtml = $this->normalizeHtmlUrls($html, null, [
    'pr_prefix' => $prPrefix, 'fw_prefix' => $fwPrefix,
    'proxy_path' => $proxyPath, 'media_prefix' => '/media/'
]);
```

## 9. İlgili belgeler

* [Core/Render genel](README.md) · [Builders](Builders.md) · [Providers](Providers.md) ·
  [Resolvers](Resolvers.md) · [Controllers](Controllers.md)
* [Core/Base/Data.md](../Base/Data.md) (`BaseConfig` tabanı) ·
  [Core/Support/Definitions.md](../Support/Definitions.md) (`AssetBundles`, `AssetConvention`, `AssetFonts`) ·
  [Kavram: asset](../../kavramlar/05-asset-sistemi.md) ·
  [Açık sorular §1.12](../../acik-sorular.md) (`cms_version`/`VERSION` çakışması, `Config`/`Configs` yolu)