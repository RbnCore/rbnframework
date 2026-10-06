# Core/Render — görünüm motoru, şablon derleme, varlık/SEO/crawler üretimi

> **Doğrulanan kod tabanı:** `c23b431f` (dal `feat/fw-license-master`) · **Tarih:** 2026-10-05 · **Yayın:** 0.9.4 = bu commit + sonrası; belge yalnız doğrulama anındaki kodu anlatır
> **Kaynak klasör:** `Core/Render/` — **51 `*.php`** = 2 kök (`View.php`, `ViewEngine.php`) + 49 alt dal.
> **Envanter:** 51 dosyanın **51'i** anlatıldı (2 + 7 + 5 + 5 + 6 + 12 + 10 + 4).
> `Services/` (4 dosya) **bu belgede §3.2'de listelenir** (ayrı alt dal belgesi yoktur).
> **Doğrulama platformu:** Windows 11 + PHP 8.3 (`E:\tmp\_araclar\php83\php.exe`), gerçek `Paths::init` ile.

## 1. Ne işe yarar, kim kullanır

"HTTP isteği geldikten sonra kullanıcıya ne basılır?" sorusunun **tamamını** bu klasör
yanıtlar: şablonu derler (`ViewEngine`), mantıksal adı fiziksel yola çevirir
(`ViewResolver`), `<head>`/`asset`/`SEO`/`<script>` üretir (`SeoProvider`,
`AssetProvider`), `robots.txt`/`sitemap.xml`/`feed`/`llms.txt` basar
(`CrawlerController`).

**Kimler çağırır:** her `BaseController` (`render()` → `View`), `Base/Routes`
(`Mappings/core.php` bu klasördeki 4 controller'ı rotalar), panel/frontend
layout'ları, `Packages/RbnEmail` (aynı motor), ajan uçları (`/api/v1/external`).

## 2. Alt dallar ve belgeleri

| Klasör | `*.php` | Belge | Kısa görev |
|---|---:|---|---|
| *(kök)* | 2 | bu belge §3 | `View` (fluent facade) + `ViewEngine` (derleyici) |
| `Builders/` | 7 | [Builders.md](Builders.md) | Durum biriktiriciler: varlık, SEO, schema, breadcrumb, sitemap, feed, llms |
| `Configs/` | 5 | [Configs.md](Configs.md) | Sabitler: proxy kurulumu, view yönergeleri, robots kuralları |
| `Controllers/` | 5 | [Controllers.md](Controllers.md) | HTTP giriş kapıları: asset proxy, crawler dosyaları, dosya vekili |
| `Handlers/` | 6 | [Handlers.md](Handlers.md) | Veri hazırlayıcı handler'lar + 3 güvenlik kapısı |
| `Providers/` | 12 | [Providers.md](Providers.md) | Fiziksel HTML/XML/JSON üretimi |
| `Resolvers/` | 10 | [Resolvers.md](Resolvers.md) | Ad → yol/meta/payload çözümleyiciler (`Sub/` = 4 alt çözümleyici) |
| `Services/` | 4 | (aşağıda §3.2) | Render/SEO/varlık/crawler servisleri |
| **Toplam** | **51** | | `Get-ChildItem Core/Render -Recurse -Filter *.php` = 51 |

## 3. Kök dosyalar — `View` ve `ViewEngine` (2/2)

| Dosya | Görev | Önemli public yöntemler |
|---|---|---|
| `View.php` (232) | Kullanıcıya görünen **fluent facade**. `render()` çağrısı bir `View` nesnesi döndürür; zincirleme `with()/metaseo()/schema()/fragment()/import()` veri toplar, `__toString()` ile basar. | `static render(string $view, array $data=[], string $type='frontend'): self`, `with($key,$value=null): self`, `metaseo(string $title,string $description='',string $keywords=''): self`, `schema(string $type,array $data=[]): self`, `fragment(string $name): self`, `import($path): self`, `viewHarmony(): array`, `result(): string`, `__toString(): string`, `getViewPath(): string` |
| `ViewEngine.php` (404) | **Derleyici.** Sablonu PHP'ye çevirir, `Storage/framework/views` altına yazar, önbelleğe alır, layout zincirini yürütür. Kök sınırı ve ifade güvenliği burada. | `render(string $viewPath, array $data=[]): void`, `compile(string $viewPath): string`, `path(string $type, ...$args): string`, `import(string ...$args): string`, `protected parse(string $content): string`, `protected parseDirectives(string $content): string` |

### 3.1 Tam render zinciri (ölçüldü)

```
$controller->render('home', $data)
  └─ FrontendBaseController::render()            Controllers/FrontendBaseController.php:215
     ├─ legalPage content içinde {appName}/{year} değişimi        :218-224
     ├─ parent::render() → View::render() (static)               View.php:106
     │    └─ metaseo()/schema() yalnızca KAYITLER (henüz basmaz)  View.php:88, :79
     └─ (dönen View nesnesine) $seoData sınıfından metaseo + schema('localBusiness')  :229-254

(string) $view            → View::__toString()                    View.php:216
  └─ View::result()                                           View.php:146
     1. metaseo varsa → service('seo')->prepare(type, pendingMetaseo)   :153-158
     2. schema varsa  → provider('schema')->build(t,d) ( SIRAYLA )     :161-168
     3. data['__fragment'] / data['__imports'] ekleme                 :172-178
     4. service('render')->render(type, ['view'=>…, 'data'=>…])       :180
     5. str_replace('{year}', date('Y'))                              :186
     6. normalizeHtmlUrls() → @project/ @fw/ /media/ önekleri         :197-202

RenderService::render(type, params)                     Services/RenderService.php:36
  ├─ prepareContext() → handler(type)->prepare(type, view, data)     :68-83
  │    ├─ type=asset  → assetService->prepare(view, appContext)      :71-74
  │    └─ aksi halde  → $this->handler($type)->prepare(...)          :78-79
  ├─ provider = $this->provider($type)  (yoksa abort 500)            :45-48
  ├─ type=asset → assetService->render(view, data)                  :52-54
  ├─ type=seo|meta → seoService->render(view, data)                  :57-59
  └─ provider->render(view, data, type)                              :62

FrontendProvider::render(view, data)                    Providers/UI/FrontendProvider.php:19
  ├─ storage->traffic()->record()      ← trafik kaydı HER frontend render'da  :23
  ├─ settings->read('site'|'company'|'social'|'contact'|'integrations')       :29-38
  ├─ handler('seoBuilder')->prepare(context) + service('seo')->render()      :41-45
  ├─ service('asset')->prepareContext(context)                             :48-53
  ├─ headerAssets = assetService->render('header') / footerAssets='footer'   :55-56
  ├─ appSchemaHtml = provider('schema')->render()                          :70
  ├─ 4 snippet safeSnippet() (RawHtmlGate)                                 :73-76
  ├─ resolveViewPath(view) + 'Layouts/header' + 'Layouts/footer'           :81-83
  ├─ ob_start()
  ├─ resolveFragment() → varsa ERKEN DÖNÜŞ                               :92-94
  ├─ viewEngine()->render(header) → view → footer                         :97-105
  └─ return ob_get_clean()                                                :107
```

### 3.2 `ViewEngine::render()` — layout zinciri (ViewEngine.php:56-103)

1. **Yol önbelleği:** `self::$resolvedViewPaths[$viewPath]` varsa kullanılır; yoksa
   `DiscoveryEngine::instance()->views()->resolve()` ile keşif, sonuç **kalıcı
   diziye** yazılır (`:59-70`).
2. `compile()` → derlenmiş PHP yolu (`:72`).
3. `bootHarmony()` + `$data` birleşimi (`:75`) — `Base/Web/BaseRender.php:49-119`.
4. `includeCompiled()` — `extract()` + `require` (`:78`, `:112-123`).
5. `$layoutResolver->getExtends()` doluysa: `resetExtends()` → layout yolu
   çözülür (keşif, yoksa `Paths::project()->root("views/{$layout}")`) →
   **`$this->render($layout, $data)` ÖZEL ÇAĞRI** (`:81-102`).

**Tuzak:** Layout `render()` çağrısı iç içedir. `resetExtends()` çağrıldığı için
ikinci turda sonsuz döngü oluşmaz; ama layout da `@extends` verirse yine
kuyruğa girer.

### 3.3 `ViewEngine::parse()` — iki ifade biçimi (ViewEngine.php:170-197)

Ölçülen çıktı (`E:\tmp\_gecici\DOC-AGAC-3-dogrula3.php`, V1):

```
girdi : A={{ $x }} B={!! $y !!} C={year} D=@if(true)X@endif
çıktı : A=<?php echo htmlspecialchars((string)($x ?? ""), ENT_QUOTES, "UTF-8"); ?>
        B=<?php echo $y ?? ""; ?>
        C={year}                        ← dokunulmaz (View::result()'te date('Y') ile değişir)
        D=<?php if (true): ?>X<?php endif; ?>
```

1. `{{-- yorum --}}` silinir (`:172`).
2. `parseDirectives()` — yapısal + `@extends/@section/@yield` (`:212-241`).
3. `{{ }}` → **`htmlspecialchars` ile kaçırılır** (`:182`).
4. `{!! !!}` → **kaçırma YOK** (`:191`); koruma `TemplateExpressionGuard`'dır.

### 3.4 Yapılandırma — `ViewConfig::directives()` (Configs/ViewConfig.php:21-49)

Ölçülen tam eşleme (**10** yönerge; `count()` ile doğrulandı):

| Yönerge | Üretilen PHP |
|---|---|
| `/@hasrole\s*\((.*?)\)/` | `<?php if(($this->handler("access") ?? BaseService::get()?->handler("access"))?->can($1)): ?>` |
| `/@endhasrole/` | `<?php endif; ?>` |
| `/@csrfToken/i` | `<?php echo $this->csrfToken(); ?>` |
| `/@csrf/i` | `<?php echo $this->csrfField(); ?>` |
| `/@method\s*\(\s*'(.+?)'\s*\)/i` | `<?php echo $this->methodField("$1"); ?>` |
| `/@sys\s*\(\s*'(.+?)'\s*\)/i` | `<?php echo RbnSystemInfo::get('$1'); ?>` |
| `/@path\s*\((.*?)\)/i` | `<?php echo $this->path($1); ?>` |
| `/@import\s*\((.*?)\)/i` | `<?php require $this->import($1); ?>` |
| `/@php/`, `/@endphp/` | `<?php ` / ` ?>` |

Yapısal yönergeler `ViewEngine::parseDirectives()` içinde **sabit** tanımlıdır
(`@if/@elseif/@else/@endif`, `@foreach/@endforeach`, `@for/@endfor`,
`@extends/@section/@endsection/@yield` — `ViewEngine.php:216-233`); `ViewConfig`
bunlara **ek** sağlar.

### 3.5 `ViewEngine::path()` — 5 tip ve kök sınırı (ViewEngine.php:244-283)

Ölçülen çıktı (Windows, `Paths::init('E:/localhost/projects/<proje>', …)`):

| Çağrı | Sonuç |
|---|---|
| `path('framework', 'Core','Render')` | `E:\localhost\rbnframework\Core` |
| `path('project', 'Modules','Frontend')` | `E:\localhost\projects\<proje>\Modules` |
| `path('public','css')` | `E:/localhost/domains/customers\css` |
| `path('suite','RbnAdmin')` | `E:\localhost\rbnframework\Bundles\RbnSuite\RbnAdmin` |
| `path('module','Modules','Frontend')` | **reddedildi**: `RBN Guvenlik: ViewEngine::path('module') izin verilen kok disina cikiyor (reddedildi): Frontend (kok disi)` |

**Tuzak (kod yorumu, ViewEngine.php:246-253):** `$args` **hiç kırpılmaz**.
Eski sürümde `array_shift($args)` referansla çalıştığı için `$args` kalıcı olarak
kısalıyor, `path('module', …)` her çağrıda "modül adı" sanılan yanlış kök
buluyor ve **fail-closed reddediyordu** (canlı pilot: HTTP 200, gövde
`[Render Error]`). Modül adı ve kalan yol **önceden** ayrılır (`:256-261`).

### 3.6 Kök sınırı (fail-closed) — `guardRoots()` (ViewEngine.php:308-403)

`compileRoots()` = `[Paths::project()->root(), Paths::framework()->root()]`.
Sıralama: `realpath()` → sözlüksel normalizasyon → `str_starts_with($path, $root.'/')`.

Ölçülen reddedilmeler:

```
compile('C:/Windows/system32/drivers/etc/hosts')
  → RBN Guvenlik: ViewEngine::compile() izin verilen kok disina cikiyor (reddedildi): hosts (kok disi)
import('framework','../../../../evil.php')
  → RBN Guvenlik: ViewEngine::path('framework') kok disi yol reddedildi: evil.php (kok disi)
```

**Tuzak:** `normalizeForCompare()` kökün **üstüne** çıkan yolu `''` döndürür
(`:380`), bu da reddi tetikler. Hata mesajına **tam yol yazılmaz**, yalnız
`basename()` + `(kok disi)` (`:334`) — mesaj HTTP gövdesine düşer, dizin yapısını
sızdırmasın diye.

**Platform notu:** `isInsideRoot()` Windows'ta `strtolower()` ile karşılaştırır
(`:395-398`). Linux/POSIX büyük-küçük harf **duyarlıdır**; aynı kod orada
`E:/Root` ile `e:/root` farklı sayılır. `Paths` katmanının matris dışı anahtarları
BÜYÜK harfle ürettiği bilinen sınırlama için bkz. [System/Paths.md §5](../System/Paths.md).

### 3.7 `includeCompiled()` — ayrılmış adlar (ViewEngine.php:112-123)

`__rbnFile`, `__rbnData`, `this`, `GLOBALS` anahtarları veride varsa **silinir** ve
sistem günlüğüne yazılır (`:114-119`). Motorun yerel değişkenleri bu kapsama girmez.

## 4. `Services/` — render/SEO/varlık/crawler servisleri (4/4)

Bu alt dal 20 `*.php` eşiğinin altında kaldığı için **ayrı belge yok**; dört dosya
burada anlatılır. Hepsi `BaseService` türevidir ve `service('render'|'seo'|'asset'|'crawler')`
diyse keşif adıyla erişilir. Servisler **fiziksel üretimi yapmaz** — onu
[Providers.md](Providers.md)'deki provider'lar yapar; servisler veriyi toplar,
hazırlar ve provider'a devreder.

| Dosya | Görev | Önemli public yöntemler |
|---|---|---|
| `RenderService.php` (126) | **Giriş servisi.** `render($type,$params)` hangi view/sağlam bağlamın açılacağına karar verir, fiziksel yolu çözer, `viewEngine()->render()` çağırır. | `boot(): void`, `render(string $type,array $params=[]): mixed`, `resolveViewPath(string $path,array $data=[],string $source='auto',bool $checkFile=false,string $appContext='frontend'): ?string`, `viewEngine()`, `engine()`; korumalı: `prepareContext(string $type,string $view,array $data): array` |
| `SeoService.php` (129) | SEO veri toplayıcı + **fluent** ayarlayıcı. `prepare()` ayar havuzunu kurar, `render('meta')` üstbilgiyi basar. | `prepare(string $context='project',array $overrides=[]): self`, `setting(string $group,?string $key=null)`, `render(?string $view='meta',array $data=[]): string`, `payload(): array`, `setTitle(string): self`, `setDescription(string): self`, `setCanonical(string): self`, `noIndex(string $directives='noindex, nofollow'): self`, `setImage(string): self` |
| `AssetService.php` (112) | Varlık (CSS/JS/font) kuyruğu. `addStyle/addScript/addFont` ile biriktirir, `renderHeader()` `<head>` bloğunu, `renderFooter()` gövde sonu bloğunu basar. | `prepare($source,string $appContext='frontend',int $priority=10): self`, `prepareContext(string $appContext,array $options=[]): self`, `render(?string $view=null,array $data=[]): string`, `renderHeader(): string`, `renderFooter(): string`, `addStyle(string $path,int $priority=10): void`, `addScript(string $path,array $attrs=[],bool $inHead=false,int $priority=10): void`, `addFont(string $name,int $priority=15): self` |
| `CrawlerService.php` (61) | `robots.txt` / `sitemap.xml` / `feed` / `llms.txt` üreticileri; `Sub/SitemapResolver` ve `Sub/FeedResolver` bunları çağırır. | `generateRobots(): string`, `generateSitemapXml(): string`, `generateSubSitemap(string $type): string`, `generateFeed(): string`, `generateLlms(): string` |

## 5. Kayıt haritası — `SystemRenderMapTrait` (Core/System/Registries/RegistryMap)

Render kayıtlarının tamamı `SystemRenderMapTrait.php:19-107`'de:
`services` (4), `handlers` (3 UI + 7 builder), `providers` (10), `presets` (1),
`resolvers` (10), `clusters` (1), `aliases` (17).

**Ölçülen çözümleme tablosu** (`Paths::init` sonrası, `provider()/resolver()/handler()/service()` çağrılarıyla):

| İstek | Çözülen sınıf |
|---|---|
| `provider('view')` | `Render\Providers\UI\ViewProvider` |
| `provider('seo'/'schema'/'asset'/'crawler'/'breadcrumb')` | `Render\Providers\{Seo,Schema,Asset,Crawler,Breadcrumb}Provider` |
| `provider('frontend'/'auth'/'panel'/'partials'/'static')` | `Render\Providers\UI\{Frontend,Auth,Panel,Partial,Static}Provider` |
| `resolver('view'/'seo'/'schema'/'breadcrumb'/'layout'/'crawler'/'sitemapResolver')` | eşleşen `Render\Resolvers\*` |
| `handler('frontend'/'auth'/'panel'/'backend')` | `Render\Handlers\UI\{Frontend,Auth,Panel}Handler` (backend→Panel) |
| `handler('assetBuilder'/'schemaBuilder'/'seoBuilder'/'breadcrumbBuilder'/'sitemapBuilder'/'feedBuilder'/'llmsBuilder')` | eşleşen `Render\Builders\*` |
| `service('asset'/'seo'/'render'/'crawler')` | `Render\Services\{Asset,Seo,Render,Crawler}Service` |
| `cluster('view_engine')`, `preset('schema')` | `Render\ViewEngine`, `Render\Handlers\SchemaPreset` |

**Bulgu (yeni, `handler('seo')` = NULL):** `SystemLogicMapTrait.php:114` içinde
`'seo' => 'Core\Render\Handlers\Seo\SeoHandler'` kayıtlı ama
**`Core/Render/Handlers/Seo/` dizini yok** (`Handlers/` altında yalnız `UI/` var) ve
`class_exists()` false. Ölçüldü: `handler('seo') = NULL`. Bu, kayıt haritasının
`Core/Render` dışına taşınmış artığıdır; Render tarafında `seo` işi
`Handlers/UI/*` + `Resolvers/SeoResolver` + `Providers/SeoProvider` üçlüsünde
yapılıyor, `SeoHandler` gereksiz. Ayrıntı: [System/Registries.md §5](../System/Registries.md),
[acik-sorular.md §1.7](../../acik-sorular.md).

## 6. Örnek (gerçek koddan)

```php
// Core/Render/Controllers/FrontendBaseController.php:215-226
public function render(string $view, $data = [], $mergeData = []): \Rbn\Framework\Core\Render\View
{
    if (isset($data['legalPage']['content'])) {
        $data['legalPage']['content'] = str_replace(
            ['{appName}', '{year}'],
            [$this->appName, date('Y')],
            $data['legalPage']['content']
        );
    }
    $viewObj = parent::render($view, (array) $data, (array) $mergeData);
    // … SeoData sınıfından metaseo/schema bağlanır (`:229-254`)
    return $viewObj;
}
```

```php
// Gerçek şablon kullanımı — @import ile parça dahil etme
// (E:\localhost\projects\customers\<grup-z>\Modules\Frontend\Antalya\Views\home.rbn.php:1)
@import('project', 'Modules/Frontend/<bolum>/Views/sections/hero.rbn.php')
```

## 7. İlgili belgeler

* [Builders](Builders.md) · [Configs](Configs.md) · [Controllers](Controllers.md) ·
  [Handlers](Handlers.md) · [Providers](Providers.md) · [Resolvers](Resolvers.md)
* [Core/System/Registries.md](../System/Registries.md) (kayıt haritaları) ·
  [Core/System/Paths.md](../System/Paths.md) (yol kökleri) ·
  [Core/Base/Web.md](../Base/Web.md) (`BaseRender`/`BaseController`) ·
  [Core/Routes/README.md](../Routes/README.md) (`Mappings/core.php` rotaları)
* [Kavram: mimari harita](../../kavramlar/01-mimari-harita.md) ·
  [Kavram: asset](../../kavramlar/05-asset-sistemi.md) · [Açık sorular](../../acik-sorular.md)