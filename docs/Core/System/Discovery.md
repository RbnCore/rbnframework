# Core/System/Discovery — Keşif motoru (bileşen/ad alanı/dosya yolu çözümü)

> **Doğrulanan kod tabanı:** `d508f5e1` (dal `feat/fw-license-master`) · **Tarih:** 2026-10-05 · **Yayın:** 0.9.5 = bu commit + sonrası; belge yalnız doğrulama anındaki kodu anlatır
> **Kaynak klasör:** `Core/System/Discovery/` — 24 `*.php`.
> **Envanter:** 24 dosyanın 24'ü aşağıda anlatıldı.

## 1. Ne işe yarar, kim kullanır

Framework'ün "adı verilen şeyi bul" katmanıdır. Kod `service('mail')`, `model('user')`, `constant('X')` ya da `Definition::get('namespace','MAP')` dediğinde:

* adı bir **sınıf adına** (FQCN) çevirir (`NamespaceResolver`, `ComponentContext`),
* bir **dosya/dizin yoluna** çevirir (`FolderContext`, `FolderResolver`, `AssetResolver`),
* **sabit tanımlarını** (ad alanı haritası, klasör matrisi, `DB_*` anahtar eşlemesi) kategori adıyla verir (`Definition`/`DefinitionResolver`),
* **modül/paket** (Bundle) kayıtlarını bulup rotalarını yükler (`ModuleDiscoveryDriver`),
* sonuçları proje dizinindeki **dosya önbelleğine** yazar (`DiscoveryMapper`, `ComponentMapper`) ve açılışta proje verisini master veritabanından toplayıp **önyükleme önbelleğine** koyar (`ProjectDataMapper`).

**Kimler çağırır:** `BaseService`/`BaseController`/`BaseModel` taban sınıfları (`$this->service()`, `->model()`…), `Paths::module()` (`ModuleDiscoveryDriver`), `Kernel/Stages/*` (`Autoload` → `Definition::get`), `Kernel/Base/PreBoot` ve `Kernel/Stages/ProjectDiscovery` (→ `ProjectDataMapper`), `Config/Engine/Database/DatabaseConfig` (→ `Definition::get('database_project','KEYS_MAP')`).

## 2. Klasör/dosya envanteri

### 2.1 `Base/` — ortak soyut katman (ad alanı `…\Discovery\Base`)

| Dosya | Görev | Önemli yöntemler |
|---|---|---|
| `BaseDiscoveryContext.php` | Bütün bağlam sınıflarının atası (`BaseComponent`'ten türer): `$this->rbn` ve `DiscoveryEngine::instance()` bağlar; tanılama + önbellek yardımcısı. | `static setGlobalDNA(?object $rbn)`, `protected triggerDiagnostic(string $name, string $type)`, `protected cacheDiscovery(string $key, callable $resolver)` |
| `BaseDiscoveryResult.php` | Bir keşif sonucunun değer nesnesi: fiziksel yol, kaynak türü, modül. | `getPath()`, `getSourceType()`, `getModule()`, `getModifiedTime(): int` |
| `BaseResolver.php` | `DiscoveryInterface`'i uygulayan soyut çözücü. | `abstract resolve(string $name, ?string $context = null): ?object` |
| `Traits/DiscoveryActionsTrait.php` | Tür-adlı kısayol yöntemleri; hepsi `resolveDiscovery($tür,$ad)`'a gider. | `controller()`, `service()`, `model()`, `helper()`, `queries()`, `handler()`, `constant()`, `validation()`, `provider()`, `repository()`, `preset()`, `rule()`, `prompt()`, `manager()`, `command()`, `cluster()`, `resolver()`, `builder()`, `job()`, `task()`, `queue()`, `rbnEvent()` (tür `event`), `listener()`, `action()`, `rbnDriver()` (tür `driver`) — 25 yöntem |
| `Traits/NamespaceExpansionTrait.php` | Kayıt defterinden gelen kısa sınıf adını tam adına tamamlar. | `protected static expandRegistryResult(?string): ?string` |

### 2.2 `Engine/` — ana motor ve sürücüler

| Dosya | Görev | Yöntemler |
|---|---|---|
| `DiscoveryEngine.php` | Tekil (singleton) giriş kapısı: çözücüleri/sürücüleri tembel kurar, aynı anahtarın özyinelemesini kesen koruma tutar. | `static instance(?object $rbn = null)`, `resolve(object $context, string $type, ?string $name = null, bool $mandatory = true)`, `configs()`, `views()`, `assets()`, `folders()`, `namespace()`, `definitions()`, `getResolver(string $type)`, `getDriver(string $type)`, `getMapper()`, `cluster(string $name)` |
| `Drivers/ComponentDriver.php` | Tür+ad → örnek: `NamespaceResolver::find()` ile sınıfı bulur, `new` ile örnekler, örneği bellekte tutar. | `resolveDiscovery()` (trait), `private discover()` |
| `Drivers/MetadataDriver.php` | Sınıf olmayan keşifler: `constant`, `config`, `validation`. | `constant($name)`, `config($key)`, `validation($path)` |
| `Drivers/PathDriver.php` | Takma ada (`framework`, `project`, `Paths::<ad>()`) göre kök yol. | `resolve(string $alias): ?string`, `resolveFolder(string $key): ?string` |
| `Drivers/ModuleDiscoveryDriver.php` | Proje modüllerini ve çatı paketlerini (`ModuleData` sınıfları) bulur, rotalarını yükler, bileşenleri (`#[Component]`) tarar. | `registerBundles(string $type = 'map', ?array $bundles = null)`, `discoverBundles(): array`, `discoverComponents(string $folder): array`, `resolveModuleContext(string $name, string $source = 'auto'): ModuleContext`, `static resetMemory()` |
| `Drivers/ModuleDataDriver.php` | Bir modülün kimlik/başlık/ikon/alt-modül bilgisini `#[Bundle]`/`#[Module]` öznitelikleri ya da `CONFIG` sabitinden çıkarır. | `resolveFromClass(string $bundleClass): array`, `getModuleInfo(string $module, string $moduleSource = 'auto'): array`, `deepSearch(array $tree, string $target, ?string $normalizedTarget = null): ?array` |
| `Cache/DiscoveryMapper.php` | Çözüm haritası (anahtar → FQCN) için bellek + dosya önbelleği; kapanışta yazar. | `get($key)`, `set($key,$value)`, `clear(): bool`, `saveMap()`, `static resetMemory()` |
| `Cache/ComponentMapper.php` | Klasör → bileşen listesi için JSON önbelleği. | `get(string $folder)`, `set(string $folder, array $components)`, `clear()`, `saveMap()`, `static resetMemory()` |
| `Cache/ProjectDataMapper.php` | Açılışta proje verisini master/common/proje veritabanlarından toplar; `BootCacheProvider`'a yazar. | `static build(string $column, string $value, string $publicPath, string $prefix): array`; sabitler `MASTER_QUERIES`, `COMMON_QUERIES`, `PROJECT_QUERIES`, `EXTRA_CACHE_KEYS` |

### 2.3 `Clusters/` — alan çözücüleri

| Dosya | Görev | Yöntemler |
|---|---|---|
| `Logic/Component/ComponentContext.php` | `$context` nesnesi için bileşen çözer: `#[SubModule]` özniteliği → `target<Tür>` özelliği → ad; sonra `NamespaceResolver` ve "deterministik yol". | `resolve(object $context, string $type, ?string $targetProperty = null, bool $mandatory = true)`, `resolveSuffix(object $context, string $name)` |
| `Logic/Component/ComponentResolver.php` | `ComponentContext`'e statik ön yüz. | `static resolve(...)`, `static resolveSuffix(...)` |
| `Logic/Definition/Definition.php` | `Definition::get(kategori, anahtar)` statik kapısı; çözüm sırasında "hayatta kalma" (survival) yedeği. | `static get(string $category, string $key): mixed` |
| `Logic/Definition/DefinitionResolver.php` | Kategori adı → sabit sınıfı eşlemesi; `Core/Support/Definitions` altını tarar. | `get(string $category, string $key)`, `resolveCategory(string $category)`, `resolve(string $name, ?string $context = null)` |
| `Logic/Validation/ValidationResolver.php` | `Core/Support/Blueprints/Validations` altındaki doğrulama sabit sınıflarını kategori adıyla verir. | `get(string $category, string $key)`, `resolveCategory()`, `resolve()` |
| `Resources/AssetResolver.php` | Bir varlık yolunu proje/çatı klasörlerinde arar. | `resolve(string $name, ?string $context = null)`, `findPhysicalPath(string $path, &$detectedModule = null)` |
| `Structure/Folder/FolderContext.php` | `FolderMatrix` ağacı üzerinde anahtar → klasör adı/yol çözümü. | `static name(string $key, bool $returnFull = false)`, `static path(string $key, ?string $sub = null)`, `static discoverDirectories(array $tree, string $prefix = '')`, `static recursiveSearch(...)`, `static hasSpecializedFolder(string $namespace, string $subFolder)`; sabitler `DATA`, `MODELS`, `MODULE_DATA` |
| `Structure/Folder/FolderResolver.php` | Takma ad ve ad alanı → fiziksel dizin. | `path(string $alias)`, `resolveLayerDirectory(string $baseNamespace)` |
| `Structure/Namespace/NamespaceContext.php` | Bir modülün taban ad alanı için sınıf adı üretir. | `getBase()`, `resolveClass($layer,$className)`, `resolveRootClass($className)`, `resolveVariations($layer,$className)`, `moduleDataClass()` |
| `Structure/Namespace/NamespaceResolver.php` | `(ad, tür)` → FQCN: önce `SystemRegistry::locate`, sonra `NamespaceMap::MAP` taraması. | `find(string $name, string $type): ?string`, `resolveLayer(string $key): string` |

## 3. Akışlar

### 3.1 `service('x')` / `model('x')` ... (tür-adlı çağrı)

1. `DiscoveryActionsTrait::service($ad)` → `resolveDiscovery('service',$ad)` (`DiscoveryActionsTrait.php:32-35`).
2. `DiscoveryEngine::resolveDiscovery()` aynı `tür:ad` anahtarı zaten çözülüyorsa `null` döner (özyineleme kesici, `DiscoveryEngine.php:49-51`); `constant` ve `validation` `MetadataDriver`'a, diğerleri `ComponentDriver`'a gider (`:56-59`).
3. `ComponentDriver::discover()` bellek önbelleğine bakar, yoksa `DiscoveryEngine::instance()->namespace()->find($ad,$tür)` ile FQCN'i bulur ve `new $class()` ile örnekler (`ComponentDriver.php:53-71`). `queries` türü iç anahtarda `query` olur (`:30`).
4. `NamespaceResolver::find()` önce `DiscoveryMapper` önbelleğine bakar (`cacheDiscovery`), sonra `SystemRegistry::locate($ad,$tür)`; bulursa `NamespaceExpansionTrait::expandRegistryResult()` ile `Rbn\Framework\`/`Rbn\Project\` önekini tamamlar, bulamazsa `resolve()` → `resolveCoreLayer()` içinde `NamespaceMap::MAP`'teki her taban ad alanı için sırayla şu kalıplara bakar (`NamespaceResolver.php:109-178`): `<Taban><Türler>\<Ad><Tür>` · `<Taban><Modül>\<Türler>\<Ad><Tür>` · `<Taban><Modül>\<Türler>\<Alt>\<Hedef><Tür>` · `…\<KlasörAdı>\<Hedef><Tür>` · `<Taban><Services|Tasks|UI|Frontend|Project|Master>\<Türler>\…` · `<Taban><Türler>\<Project|Master>\<Kök>\…`.

### 3.2 Bileşen çözümü bağlamdan (`ComponentContext::resolve`, `ComponentContext.php:43-112`)

Sıra: `#[SubModule]` özniteliğindeki `$type` alanı → bağlam nesnesinin `target<Tür>` ya da `<tür>` özelliği → verilen `$targetProperty` → `NamespaceResolver::find("{tür}.{ad}")` → `find($ad)` → `calculateDeterministicPath()` (bağlamın kendi ad alanından `<Taban>\<Türler>\<Ad><Tür>` ve kardeş `<Taban>\<Ad><Tür>`). Bulunursa `new $fqcn()`; bulunamazsa ve `$mandatory` ise `triggerDiagnostic()` ile `shield()->diagnostic(...)` çağrılır, `null` döner.

### 3.3 `Definition::get(kategori, anahtar)` (`Definition.php:28-73`)

1. Çözüm sürüyorsa: kategori `namespace` ise `NamespaceMap`'ten yedek, değilse `null` (`:33-35`).
2. `identity` kategorisi doğrudan `AssetConfig::PROXY_SETUP`'tan `PROXY_PATHS`/`PROXY_TOKENS` üretir (`:54-66`).
3. Diğer kategoriler `DiscoveryEngine::instance()->definitions()->get()` → `DefinitionResolver::get()`; sabit adı `strtoupper($anahtar)`; sabit yoksa `get<Anahtar>` statik yöntemi; hiçbiri yoksa `LogicException` (`DefinitionResolver.php:28-49`).
4. Kategori eşlemesi (`ensureDiscovery`, `:72-120`): önce sabit dört kayıt (`namespace` → `NamespaceMap`, `database_master|common|project` → `*DbData`), sonra `Core/Support/Definitions` altındaki her `*.php` taranıp `getDefinitionCategory()` / `$definitionCategory` / dosya adından türetilen anahtarla eklenir.

### 3.4 Proje verisi toplama (`ProjectDataMapper::build`, `ProjectDataMapper.php:74-153`)

1. Master PDO ile `projects` satırı: `status IN ('active','maintenance','suspended')` ve `{column} = ?` (`:85-89`; `$column` yalnız `ProjectDiscovery` içinde sabit `project_key` ya da `domain` olarak verilir).
2. `MASTER_QUERIES` (`settings`, `cron_jobs`), grup projeleri (`project_group`), `COMMON_QUERIES` (`shield`), projenin kendi DB'si (`buildProjectPdo`) üzerinde `PROJECT_QUERIES` (`options`) çalışır; her sorgu kendi `try/catch`'inde hata olursa `[]` döner (`runQueryMatrix`, `:167-183`).
3. `assemble()` (`:192-326`): `project-routemap.php` içindeki `view_mapping[<proje>]` ile `dashboard_prefix`/`admin_panel_disabled`; shield ayarlarından `bot_activity`, `use_master_api`, `required_login_role`, `admin_panel_disabled`; API anahtarları; `EXTRA_CACHE_KEYS` (`cron_notification_emails` e-posta listesi); `cron_tasks`.
4. `sanitizeResult()` boş anahtarları atar (`:337-370`).
5. Sonuç `BootCacheProvider::set($value,$result,$publicPath,$prefix)` ile yazılır; grup varsa ayrıca `DISCOVERY_PREFIX_GROUP` önekiyle (`:137-147`). Herhangi bir `Throwable` `[]` döndürür (`:151-153`).

### 3.5 Modül/paket keşfi (`ModuleDiscoveryDriver`)

`discoverBundles()` (`:120-191`) iki kaynaktan `ModuleData` sınıflarını toplar ve **işlem içi** önbellekler:

1. **Proje modülleri:** `Paths::project()->module()` altında `<bağlam>/<modül>/Data/ModuleData.php` (yoksa `Models/ModuleData.php`) dosyası olan her modül; sınıf adı `Rbn\Project\Modules\<Bağlam>\<Modül>\Data|Models\ModuleData`. Anahtarlar `"<bağlam>.<modül>"` ve `"<modül>"` (küçük harf).
2. **Çatı paketleri:** `SystemRegistry::sovereignBundles()` listesi; anahtarlar `"<bağlam>.<ad>"`, `"<bağlam>.<adSonrasındaRbnYok>"`, `<ad>`, `<adRbnYok>` (bağlam = ad alanındaki `Bundles\<X>` parçası, yoksa `suite`).

`resolveModuleContext($ad,$kaynak='auto')` (`:324-343`): `auto` iken `frontend.<ad>` varsa onu, yoksa `backend.<ad>`'ı dener; bulunamazsa düz `<ad>` anahtarına düşer; hiçbiri yoksa `ModuleContext($temiz,'Unknown','')` döner. `Paths::module()` bunu çağırır (`Core/System/Paths/Paths.php:160-169`).

`registerBundles('routes')` her benzersiz `ModuleData` sınıfı için `Route::module($kaynak,$ad)->load()` çağırır; `backend` kaynaklıları `Route::middleware('admin')->panel('admin')->group()` içine alır; `frontend` kaynaklı modülde proje için ayarlı `module` farklıysa atlar (`:62-93`).

## 4. Yapılandırma ve varsayılanlar

| Anahtar | Değer | Kaynak |
|---|---|---|
| Çözüm önbelleği dosyası | `<proje>/Storage/framework/discovery_map_<proje>.php` (PHP `return` dizisi) | `DiscoveryMapper.php:147` |
| Bileşen önbelleği dosyası | `<proje>/Storage/framework/components_map_<proje>.json` | `ComponentMapper.php:139` |
| Önbellek yazma zamanı | `register_shutdown_function([$this,'saveMap'])`; yalnız `isDirty` iken | `DiscoveryMapper.php:101,107-110` |
| Proje PDO zaman aşımı | `PDO::ATTR_TIMEOUT = 2` sn | `ProjectDataMapper.php:467` |
| Taranan bileşen kökleri | proje `App`, proje `Core`, `<workspace>/Packages`, `<workspace>/Bundles` (+ her modülün `<klasör>`'ü) | `ModuleDiscoveryDriver.php:213-218,241-269` |
| Alias → çözücü | `config`,`configs`,`definition` → `DefinitionResolver`; `view` → `ViewResolver`; `asset` → `AssetResolver`; `folder`; `namespace`; `validation`; bilinmeyen → `NamespaceResolver` | `DiscoveryEngine.php:159-179` |

## 5. Tuzaklar ve kurallar

1. **`discoverBundles()` ve bileşen önbelleği süreç içinde kalıcıdır**; test/CLI içinde proje değişirse `ModuleDiscoveryDriver::resetMemory()`, `DiscoveryMapper::resetMemory()`, `ComponentMapper::resetMemory()` çağrılmalıdır.
2. **Sınıf yüklenir, çalıştırılır:** `ModuleDiscoveryDriver::processComponentFile()` yalnız içinde `#[Component` geçen dosyaları `require_once` eder; `DefinitionResolver`/`ValidationResolver` ise `Support/Definitions` ve `Support/Blueprints/Validations` altındaki **tüm** `*.php` dosyalarını `require_once` eder (yan etkisiz sabit sınıfları olmaları gerekir).
3. **`DiscoveryMapper` anahtarları küçük harfe çevirir**; `null` sonuçlar önbelleğe yazılmaz (`cacheDiscovery()` yalnız `null` olmayan değeri `set` eder, `BaseDiscoveryContext.php:85-87`); yüklenirken de `null` değerler süzülür (`DiscoveryMapper.php:91`). Önbellek okuma/yazma hatası sessizce yutulur.
4. **`Definition::get` bilinmeyen anahtarda `LogicException` atar** (`DefinitionResolver.php:48`), ama `Definition` çözüm sürerken `null` döner: hata sessiz `null`'a dönüşebilir.
5. **Özyineleme kesicileri bilerek `null` döner:** `DiscoveryEngine::$resolvingKeys`, `NamespaceResolver::$resolving`, `Definition::$isResolving` — aynı anahtarı çözerken tekrar istenirse sonuç `null`'dır, hata değildir.
6. **`ProjectDataMapper` çıplak SQL kullanır** (§8 "DB yalnız Repository" kuralının bilinçli istisnası: Repository katmanı açılıştan sonra kurulur). `projects` sorgusunda yalnız `status` ∈ {`active`,`maintenance`,`suspended`} satırlar bulunur; `inactive`/başka durum "proje yok" gibi görünür.
7. **`DiscoveryEngine::cluster()` trait'teki `cluster()`'ı ezer** (`DiscoveryEngine.php:204-207` ↔ `DiscoveryActionsTrait.php:146-149`): motor üzerinde `cluster('x')` bir **çözücü** döndürür, bileşen değil.
8. **`ModuleDiscoveryDriver::discoverComponents()` ilk ziyarette dosyaları yükler**; sonuç `ComponentMapper` ile JSON'a yazılır, sonraki istekte dosya taranmaz. Yeni `#[Component]` ekledikten sonra önbellek dosyası silinmelidir (`ComponentMapper::clear()`).
9. **Modül kökleri:** keşif yalnız projenin `Modules` klasörünü (`Paths::project()->module()`) ve `SystemRegistry::sovereignBundles()` listesini tarar; çatı `Bundles/` klasörü doğrudan taranmaz (yalnız `discoverComponents` için `<workspace>/Bundles/<klasör>`). *(Eski `acik-sorular.md` §2.1 birinci kısım — kodla çözüldü.)*
10. **Proje rota dosyası yükletmek:** `Definition::get('route','FILES')` → `RouteBlueprint::FILES` (`definitionCategory='route'`, `Core/Support/Definitions/Route/RouteBlueprint.php:19,57-64`); `'project'` listesi boştur. `Route::loadRoutes()` bu listedeki her dosya adı için `Paths::project()->routes()/<ad>.php` varsa `require_once` eder (`Core/Routes/Route.php:265-279`); yoksa **sessizce atlar**. Yani ekleme yeri `RouteBlueprint::FILES['project']` (çatı kodu) — proje başına ayarlanabilir bir yol yoktur. *(Eski `acik-sorular.md` §2.1 ikinci kısım.)*

## 6. Örnek (gerçek koddan)

```php
// Core/System/Kernel/Stages/Autoload.php:41,50
$projectPrefix   = Definition::get('namespace', 'PROJECT_PREFIX')   ?: 'Rbn\Project\\';
$frameworkPrefix = Definition::get('namespace', 'FRAMEWORK_PREFIX') ?: 'Rbn\Framework\\';
```

```php
// Core/System/Config/Engine/Database/DatabaseConfig.php:71
$map = Definition::get($category, 'KEYS_MAP');   // $category = 'database_project'
```

## 7. İlgili belgeler

* [Core/System genel bakış](README.md) · [Kernel](Kernel.md) · [Paths](Paths.md) · [Registries](Registries.md) · [Storage](Storage.md)
* [Support/Definitions](../Support/Definitions.md) (`NamespaceMap`, `FolderMatrix`, `ComponentTypes`, `RouteBlueprint`)
* [Kavram: mimari harita](../../kavramlar/01-mimari-harita.md) · [Açık sorular](../../acik-sorular.md)
