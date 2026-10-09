# 01 — MİMARİ HARİTA

> **Bu belge hangi commit'e göre yazıldı:** `d4af18d` (dal `feat/fw-license-master`)
> **Son doğrulama tarihi:** 2026-10-05
> **Yayın tabanı:** 0.9.6 = bu commit + sonrası; belge yalnız doğrulama anındaki kodu anlatır
> **Kapsam:** Açılış zinciri, yönlendirme, render, kavramlar, `Paths`, ortam algısı

---

## 1. Tek cümlelik özet

RBN, **çok kiracılı (multi-tenant)** bir PHP uygulama çatısıdır: **bir** çekirdek
kodon altında **birden çok bağımsız site** çalışır; siteler master veritabanındaki
`projects` satırlarıyla tanımlanır, veri tabanları ve klasörleri birbirinden ayrıdır.

---

## 2. Açılış zinciri (HTTP)

### 2.1 Giriş noktası (`index.php`)

Her site kendi giriş noktasını taşır; framework paketinin parçası **değildir**
(`.github/UPGRADING.md:815-826`). Yerel çalışma örneği:
`domains/customers/<site>/index.php:1-50`.

Sıra:

1. **PHP sürüm kapısı**, `vendor/autoload.php`'den **önce**
   (`domains/customers/<site>/index.php:11-23`).
   Dosya yolu `dirname(__DIR__, 2|3|4) . '/rbnframework/Core/System/Kernel/Base/PhpVersionGate.php'`
   aranır; bulunamazsa kapı **sessizce atlanır** (bulunursa `enforce()` çağrılır).
2. **Composer autoloader keşfi**: iki aday yol sırayla denenir —
   `dirname(__DIR__, 2)` (hosting) ve `dirname(__DIR__, 3)` (yerel)
   (`domains/customers/<site>/index.php:27-38`). Bulunamazsa HTTP 500 + ölüm (`satır 40-43`).
3. `Bootstrap::run(__DIR__, ['project_key' => '<project_key>'])`
   (`domains/customers/<site>/index.php:48-50`).
   `project_key` **açıkça verilir**; `publicPath` = `__DIR__` (o sitenin kendi klasörü).

> **Neden `project_key` açık veriliyor?** Master'da birden çok satır aynı `domain`
> kalıbını taşıyabilir (`*.test`, `*.local` ekleri `ProjectDiscovery` içinde
> `preg_replace('/\.(test|local)$/', '', $host)` ile **son ek** olarak atılır,
> `ProjectDiscovery.php:78`). Anahtar vermek bu belirsizliği tamamen kaldırır.

### 2.2 `Bootstrap::run()`

`Core/System/Kernel/Bootstrap.php:19-60`:

1. `PhpVersionGate::enforce()` tekrar (`Bootstrap.php:26-27`) — savunma derinliği.
2. `configureErrorVisibility()` (`Bootstrap.php:97-104`): `RBN_DEV` **tanımlı değilse**
   sonuç **KAPALI** (fail-closed). `display_errors` 0/1, `log_errors` 1,
   `error_reporting(E_ALL)`.
3. `sendBaseSecurityHeaders()` (`Bootstrap.php:112-134`): `X-Powered-By` kaldırılır;
   `X-Content-Type-Options: nosniff` ve `Referrer-Policy: strict-origin-when-cross-origin`
   **yoksa** eklenir (projeye ait başlıklar **ezilmez**). CLI'de ve
   `headers_sent()` ise hiç çalışmaz. HSTS/CSP **kasıtlı olarak yok** —
   sunucu/proje katmanındadır (`Bootstrap.php:106-110`).
4. `PreBoot::orchestrate()` (`Bootstrap.php:37`).
5. `configureErrorVisibility()` tekrar (`Bootstrap.php:40`) — artık `RBN_DEV` bellidir.
6. `ob_start()` → `KernelFactory::create()` → `$kernel->boot()` (`Bootstrap.php:43-45`).
7. `Route::run()` (`Bootstrap.php:48`).
8. `$kernel->terminate()` (`Bootstrap.php:51`).
9. Tüm gövde `catch (\Throwable)` içinde: `BootSentinel::dispatch($e)` +
   `renderTerminalFailure($e)` (`Bootstrap.php:53-59`).

**En dar çıkış yolu** `renderTerminalFailure()` (`Bootstrap.php:68-87`): hata
kimliği (4 bayt rastgele, HEX büyük harf) `error_log()`'a yazılır; `RBN_DEV === true`
ise yalnız mesaj basılır, aksi halde `"Sistem şu an kullanılamıyor. Hata kimliği: …"`
(`Bootstrap.php:86`). Dosya/satır **üretimde ekrana hiç dökülmez**.

### 2.3 `PreBoot::orchestrate()` — 0 … 4. adımlar

`Core/System/Kernel/Base/PreBoot.php:406-459`:

| # | Adım | Satır | Ne yapar |
|---|---|---|---|
| — | `PhpVersionGate::enforce()` | 412-413 | Sürüm kapısı (autoload sonrası savunma) |
| — | saat dilimi + locale | 419-420 | `Europe/Istanbul`, `tr_TR.UTF-8` |
| **0** | `detectEnvironment()` | 423 | `RBN_DEV`, `RBN_DEBUG`, `DEFAULT_LANGUAGE`, `APP_NAME`, `APP_URL` sabitlerini tanımlar. **`APP_VERSION` burada tanımlanmaz** (`PreBoot.php:356-362`) |
| **0.5** | Proje keşfi | 426-435 | `ProjectDiscovery::getProjectData($publicPath, $config['project_key'])` → sonuç boş değilse `Bootstrap::setAppContext('project_data', …)` ve `('project_key', …)`; `$config['project_key']` gerçek anahtarla güncellenir |
| **1** | `initPaths()` | 438 | `Paths::init($projectPath, $publicPath)` (§4) |
| **1.5** | `defineAppVersion()` | 444 | `APP_VERSION` = **proje** sürümü (bkz. `04-surumleme-ve-yayin.md`) |
| **2** | `registerSentinel()` | 447 | `BootSentinel::register()` (elle `require`, `Paths` gerekiyor) |
| **2.5** | `Watchdog` | 450-451 | `require .../Guards/Watchdog.php` |
| **3** | `initAutoloader()` | 453 | `Paths::frameworkRoot() . '/vendor/autoload.php'`; yoksa **ölüm** (`PreBoot.php:545`) |
| **4** | `handleFastAssets()` | 456 | `AssetDoctor::check()` — statik varlıklar için bağımlılıksız hızlı yol |

`PreBoot` tek seferliktir: `private static bool $initialized` (`PreBoot.php:20`, 415-416).
Ortam kararı autoloader'dan **önce** verildiği için `Config/Secrets.php` **elle** `require`
 edilir (`PreBoot::appSettings()`; `secrets.php` `app` bölümü, FW-096-D8).

### 2.4 `detectEnvironment()` — `RBN_DEV` nasıl belirlenir?

`PreBoot.php:340-364`. **Güvenli varsayılan: ÜRETİM.** Geliştirme kipi yalnız iki
kaynaktan açılır:

1. **Operatör kararı**: `secrets.php` `app.debug` / `app.dev` = PHP `true`
   (`PreBoot::operatorDebugRequested()`). Değer `bool` olmak zorundadır; `'banal'`
   gibi metin şema tarafından varsayılana (`false`) düşürülür, kapı **kapalı** kalır.
   `app.environment` `'development'` değilse (yazılmamışsa da) 2. madde hiç denenmez.
2. **Yerel istek**: `isTrustedLocalEnvironment($host, $addr)` (`PreBoot.php:317-334`)
   → host normalizasyonu geçerli, **istemci IP'si `isLocalClientAddress()`'tan geçer**
   (loopback + RFC1918 + link-local + ULA, IPv4-eşlemeli IPv6 dahil,
   `PreBoot.php:158-223`) **VE** host ya beyaz listede
   (`localhost`, `127.0.0.1`, `[::1]` — `PreBoot.php:34-38`) ya da **TAM son ek** `.test`
   (`str_ends_with`, `PreBoot.php:333`).

**Alt dize eşlemesi yoktur** (`stripos`/`str_contains` kullanılmaz,
`PreBoot.php:30-32`): `www.tester-attacker.com`, `evil.localhost.attacker.com`,
`example.tr.test.evil.com` **kapsam dışıdır**.

`X-Forwarded-For` **bilerek hiç okunmaz** (`PreBoot.php:153-157`).

`APP_URL` = `http://` + host (yerel) / `https://` + host (`PreBoot.php:363`).

### 2.5 Kernel aşamaları (`KernelFactory`)

`Core/System/Kernel/Base/KernelFactory.php:74-89`. `new Kernel(...)` sonrası altı
aşama zincirlenir. Sıra ve **bağımlılık gerekçeleri** kodun docblock'ındadır
(`KernelFactory.php:24-73`):

| # | Aşama | Neye bağımlı | Ne yapar |
|---|---|---|---|
| 1 | `Autoload` | — | Composer + proje PSR-4 kayıtları |
| 2 | `ShieldSentinel` | 1 | Zorunlu sağlık/güvenlik denetimi; başarısızsa erken çıkış |
| 3 | `DatabaseGuardStage` | 1, 2 | Ortam ve aktif proje DB üzerinden çözülür; **proje veri önbelleği burada kesinleşir** |
| 4 | `ComponentRegistry` | 1, 2, 3 | Exception servisi + sınıf alias'ları |
| 5 | `SessionSandboxStage` | 3, 4 | Oturum çerezi bayrakları, ömür, "proje kutusu" senkronu |
| 6 | `Routing` | 1-5 | `Route::loadRoutes()`, bakım modu ve ön-ek denetimi |

Özet (koddan, `KernelFactory.php:66-68`):
`Autoload → Tanılama → Veritabanı → Servisler → Oturum → Yönlendirme`.

> **Sıra kuralı:** Yeni aşama eklenirse docblock'a **"neye bağımlı"** satırı da
> eklenmelidir (`KernelFactory.php:70-72`).

`Kernel::boot()` (`Core/System/Kernel/Kernel.php:58-90`) aşamaları sırayla çalıştırır;
`RBN_DEV` açıksa süre ölçümü yapar (`Kernel.php:62, 66, 70-73`).
`PreflightException` yakalanır ve `PreflightProvider::renderFatal(...)` ile ekrana
basılıp `exit` edilir (`Kernel.php:77-87`). **Diğer istisnalar yakalanmaz** —
`Bootstrap::run()`'un `catch` bloğuna düşer.

---

## 3. Yönlendirme

### 3.1 `Route::run()` — `RedirectManager` ÖNCE, dispatch SONRA

`Core/Routes/Route.php:235-250`:

1. `BaseService::get()->manager('redirect')` → varsa `->process()`
   (`Route.php:238-241`).
2. `REQUEST_URI` yol parçası alınır, `trim(..., '/')` edilir; boş veya `index.php`
   ise `/` olur (`Route.php:243-247`).
3. `self::dispatch($uri)` (`Route.php:249`).

`RedirectManager::process()` `Route::dispatch()`'ten **önce** çalışır; bu sıralama
R-02 tuzak kontrolünün dayanağıdır — `collectHoneypotRoutes()` yorumu:
`RedirectManager.php:498-506` ("`Routing` aşaması `Route::loadRoutes()`'i çoktan
bitirdiği için koleksiyon hazırdır").

### 3.2 `RedirectManager` — tek 301 kuralı

`Core/Routes/RedirectManager.php`:

* `process()` (`RedirectManager.php:105-140`):
  * CLI'de **tamamen atlanır** (`satır 108-110`).
  * `shouldBypassRedirect()` doğruysa hiç kural çalışmaz (`121-123`, `588-601`):
    yol segmentlerinden **biri tam olarak** `api`, `webhook`, `bot-sync`, `bot-data`
    ise (`hasSegment()`, `616-619`).
  * Kurular sırayla çalışır (`128-132`), sonra **tek** karşılaştırma yapılır:
    ilk URL ≠ son URL ise **tek** `redirect()` + `exit` (`137-139`).
* Kural sırası (`RedirectManager.php:45-54`):
  `enforceCanonicalDomain`, `forceHttps`, `removeWww`, `removeTrailingSlash`,
  `forceLowercaseUrl`, `redirectOldUrls`, `redirectHome`, `selfHealingAssets`.
* **Güvenlik kapısı:** `redirect()` (`663-681`) önce `guvenliYonlendirmeHedefi()`
  (`156-167`) üzerinden aynı-origin doğrulaması yapar; **beyaz liste boşken ve hedef
  isteğin kendi host'u ise hiç `Location` yazılmaz, `exit` de olmaz** (`667-669`,
  `642-658`). Bu, tarayıcıda kalıcı 301 birikmesini (B-5) engeller.

### 3.3 `RedirectManager` vs `Config` önceliği

`enforceCanonicalDomain()` resmi alan adını `Bootstrap::getAppContext('project_data')['domain']`
alanından okur (`RedirectManager.php:206-207`); yoksa erken döner (`209-211`).

### 3.4 Rotaların yüklenmesi

`Route::loadRoutes()` (`Route.php:255-281`):

1. Framework rotaları: `Paths::framework()->core('Routes/Mappings')` altından
   `core.php`, `auth.php`, `web.php` **`require_once`** (`Route.php:260-262`).
2. Proje rotaları: `Definition::get('route','FILES')['project']` listesi
   (`Route.php:265-266`). Liste `RouteBlueprint::FILES` sabitinde **şu an boştur**
   (`Core/Support/Definitions/Route/RouteBlueprint.php:57-65`), yani bugün proje
   rotaları otomatik yüklenmez; bir proje bu listeye dosya adı ekleyerek açar.
   Dosyalar `Paths::project()->routes()` altında, proje ad alanı
   (`Definition::get('namespace','PROJECT_PREFIX')`) içinde gruplanır (`Route.php:269-279`).

### 3.5 Dispatch → Controller

`Core/Routes/Engine/Dispatcher.php:24-33`: önce middleware'ler (`runMiddleware`, `38`),
sonra `callController()` (`32`).

* Middleware **çözülemezse veya yetki reddi** → `response()->redirect('/')`
  (`Dispatcher.php:49`); middleware koruma uygulamadan controller'a gitmek **fail-OPEN
  rotadır** ve bu yol bilinçli olarak kapalıdır (`Dispatcher.php:75-80`).
* Controller çözümü `ControllerResolver::resolve()` (`Dispatcher.php:157`):
  sırayla dener ve ilk bulan adı döner — modül, proje modül ortak hub,
  proje çekirdeği vb. (`ControllerResolver.php:18-58`). Ad alanında `\Controllers`
  yoksa otomatik enjekte edilir (`ControllerResolver.php:63-78`), sonuna
  `Controller` soneki eklenir (`85`).
* `new $class` ** burada yapılır** (`Dispatcher.php:162`); §1 yasağı model/repository/
  service içindir, yönlendirici bu tek istisnadır.
* Dönüş değeri: controller `return` etmezse `getActiveView()` denenir
  (`Dispatcher.php:165-168`); çıktı `echo` edilir (`170-172`).

### 3.6 Panel ön eki (`dashboard_prefix`)

Sıra: `project-routemap.php` → BootCache → `project_data('dashboard_prefix')`;
o da boşsa framework sabiti `RouteBlueprint::DASHBOARD_PREFIX` = `dashboard`
(`RouteBlueprint.php:99`). `SystemGuardHandler` **artık `project-settings` dosyasından
`dashboard_prefix` okumaz** (`SystemGuardHandler.php:50-53`).
`RouteManager` okurken `project_data['dashboard_prefix']`'i kullanır
(`RouteManager.php:159, 192, 223, 295`).

---

## 4. `Paths` — yol çözümleme

`Core/System/Paths/Paths.php`:

* `Paths::init(?string $project, ?string $public)` (`Paths.php:37-58`):
  `ProjectContext` + `FrameworkContext` kurar, ardından
  `Core/Support/Bridges/Helpers/rbn_helpers.php` dosyasını `require_once` eder
  (`Paths.php:50`) — **global helper'lar burada tanımlanır**, yani `Paths::init()`
  sonrasında `project_data()`, `is_local()`, `app_version()` kullanılabilir.
  RBN_DEV açıkken `?rbn_diag` tanılama çıktısı basılır (`Paths.php:55-57`).
* `workspace()` (`Paths.php:63-85`): `__DIR__` yukarı doğru yürütülür, ilk
  `basename === 'rbnframework'` bulunan dizin **framework kökü**, onun üstü
  **workspace** olur. Bulunamazsa yedek: `dirname(__DIR__,3)` çerçeve,
  `dirname(...)` workspace (`Paths.php:79-82`).
* `frameworkRoot()` (`Paths.php:90-95`), `projectRoot()` (`107`),
  `publicRoot()` (`109-122`): `project_data('public_path')` doluysa
  `workspace + '/domains/' + public_path` **gerçekten dizinse** (`is_dir`) o kullanılır,
  değilse `init()` ile verilen yol (`Paths.php:112-121`).
* `module(string $name, string $source = 'auto')` (`Paths.php:160-169`):
  `ModuleDiscoveryDriver::resolveModuleContext()` ile modül bağlamı, sonuç
  süreç içi önbelleğe alınır.

**Sıra önemli:** `PreBoot::initPaths()` (adım 1) `defineAppVersion()`'dan (adım 1.5)
önce gelir; proje sürümü okunabilsin diye. Gerekçe `PreBoot.php:356-362, 440-444`.

---

## 5. Kavramlar: `project_key`, site, modül, proje klasörü

### 5.1 Veritabanı tanımı (master)

Master `projects` tablosu (`db:schema projects --master` ile ölçüldü):

`id`, `project_group`, `project_key` (**UNIQUE**), `project_name`, `domain`,
`public_path`, `custom_path`, `status` (`enum('active','maintenance','suspended')`,
varsayılan `active`), `options` (json), `version`, `license_key`, `created_at`,
`updated_at`.

### 5.2 Tek proje klasörü = birden çok site

`custom_path` = `projects/` altındaki **klasör**; birden çok `project_key` **aynı**
`custom_path`'i paylaşabilir. Ölçülen örnekler:

| `project_key` | `custom_path` | `domain` |
|---|---|---|
| `<anahtar-1>` | `customers/<proje>` | `site.example` |
| `<anahtar-2>` | `customers/<proje>` | `alt.site.example` |
| `<anahtar-3>` | `customers/<proje>` | `ofis.site.example` |

Yani **bir klasör birden çok siteyi barındırır**; "site" burada bir **proje kaydıdır**
(ayrı DB kimliği, ayrı modül, ayrı panel ön eki olabilir).

### 5.3 `public_path` bir **göreli** yoldur

`projects/<...>` değil, `domains/` altına göre yazılır; `Paths::publicRoot()` bunu
`workspace + '/domains/' + public_path` olarak birleştirir (`Paths.php:115`).
`db:query` ile ölçüldü: **19 kaydın 18'i `domains/` öneksiz**, 1'i `domains/`
önekiyle yazılmış → bu kayıt için `is_dir()` denetimi başarısız olur ve
`publicRoot()` init değerine düşer (bkz. `../acik-sorular.md`).

### 5.4 Keşif önbelleği

`ProjectDiscovery::getProjectData()` (`Stages/ProjectDiscovery.php:42-93`):

* Anahtar verilmişse `project_key` kolonuyla, verilmemişse `domain` kolonuyla aranır.
* Domain aramasında host **tek merkezden** normalleştirilir
  (`PreBoot::normalizeHost`, `ProjectDiscovery.php:73`) ve **yalnız sondaki**
  `.test` / `.local` atılır (`preg_replace('/\.(test|local)$/','',$host)`,
  `ProjectDiscovery.php:78`). `str_replace` kullanılmaz — alt dizge silme hatasının
  düzeltmesidir (açıklama `ProjectDiscovery.php:62-68`).
* Önbellek **hit** ise anında döner (`ProjectDiscovery.php:86-89`);
  **miss** ise tüm hydration `ProjectDataMapper::build()`'e devredilir
  (`ProjectDiscovery.php:92`).
* Grup listesi ayrıca `group_` önekiyle önbelleğe yazılır
  (`ProjectDataMapper.php:141-145`).

**Önbellek nerede?** `BootCacheProvider::getCacheDir()` →
`Paths::workspace() . '/.cache'` (`Core/System/Storage/Providers/BootCacheProvider.php:20-23`).
Dosya adı: `.cache/<önek><değer>.json` (`BootCacheProvider.php:28-30`).

| Önek | Sabit | Dosya biçimi |
|---|---|---|
| `project_` | `CacheConstants::DISCOVERY_PREFIX_PROJECT` | `.cache/project_<project_key>.json` |
| `domain_` | `CacheConstants::DISCOVERY_PREFIX_DOMAIN` | `.cache/domain_<domain>.json` |
| `group_` | `CacheConstants::DISCOVERY_PREFIX_GROUP` | `.cache/group_<project_group>.json` |

(Sabitler: `Core/System/Storage/Constants/CacheConstants.php:37-39`.)

**Ne zaman yenilenir?** Önbellek **kalıcı dosyadır**; TTL kavramı yoktur.
`BootCacheProvider::clearProjectCache($projectKey)` üç dosyayı birlikte siler
(`BootCacheProvider.php:79-94`: domain → group → project). Elle yenileme:
`php rbn cache:clear` (kök `README.md:135`).

### 5.5 Proje verisi nasıl birleşir?

`ProjectDataMapper::build()` (`Core/System/Discovery/Engine/Cache/ProjectDataMapper.php:74-145`):

1. **Master PDO**: `SELECT * FROM projects WHERE <kolon> = ? AND status IN ('active','maintenance','suspended') LIMIT 1`
   (`ProjectDataMapper.php:85-87`). Satır yoksa **`[]`** döner (`91-93`).
2. `MASTER_QUERIES` matrisi çalışır (`98`) ve **grup satırları** çekilir
   (`project_group = ? OR project_key = ?`, `104-108`).
3. **Workspace** çözülür, `custom_path` `projects/` altına bağlanır (`115-118`).
4. **Common PDO** (`CommonDbData`) → `COMMON_QUERIES` (`121-123`).
5. **Proje PDO** → `PROJECT_QUERIES` (`125-129`); bağlantı kurulamazsa boş dizi.
6. Birleştirme `assemble()` (`192-…`) ve **BootCache'e yazma** (`137`, `141-145`).

`assemble()` içinde, **routemap'ten gelen iki alan** özel işlenir
(`ProjectDataMapper.php:202-222`):

* `view_mapping[<project_key>]['dashboard_prefix']` → `project_data('dashboard_prefix')`
  (`214`, `220-221`).
* `view_mapping[<project_key>]['admin_panel_disabled']` → `admin_panel_disabled`
  (`215`, `219`); panel kapalıysa `dashboard_prefix` **silinir** (`354-359`).

Ayrıca master `settings` satırları `setting_key → setting_value` haritasına
çevrilir (`ProjectDataMapper.php:226-228`) ve **ek önbellek alanları** bildirimsel
listedendir (`EXTRA_CACHE_KEYS`, `ProjectDataMapper.php:58-64`) — yeni alan = 1 satır.

### 5.6 Modül

`Route::module()` (`Route.php:77`); çözüm `ModuleDiscoveryDriver` üzerinden
`Paths::module()` (`Paths.php:160-169`). Modül adı **routemap'te tanımlanır**
(`view_mapping[<key>]['module']`), örn. `<modul>` → modül klasörü ve
`ModuleData.php` (rotalar, yetkiler, menüler tek merkezde; §5 kuralları).
Modül bulunamazsa `ModuleDiscoveryDriver` kaynak taramasına düşer; kodda
"yoksa ana sayfaya 301" gibi bir davranış **RedirectManager**'ın işidir, modül
çözümleyicinin değil (`RedirectManager.php:379-381` tek segmentli yollar içindir).

---

## 6. Render zinciri

`Dispatcher` → controller → `$this->render(...)` →
`RenderService::render($type, $params)` (`Core/Render/Services/RenderService.php:36-63`):

1. **Bağlam hazırlığı**: `$this->handler($type)->prepare(...)` (`RenderService.php:42`, `68-83`).
   Handler yoksa/istisna olursa **sessizce** `$data` döner (`80-82`).
2. **Provider çözümü**; yoksa HTTP 500 (`45-48`).
3. Özel durumlar: `asset` → `AssetService` (`52-54`); `seo`/`meta` → `SeoService` (`57-59`).
4. Diğer → `$provider->render($view, $preparedData, $type)` (`62`).
   **Render `return` eder, `echo` etmez** (`RenderService.php:50`).

Şablon motoru `Core/Render/ViewEngine.php`:

* **Derleme**: `.rbn.php` → saf PHP → `Paths::project()->storage('framework/views')`
  (`ViewEngine.php:47`). Dosya adı `<project_key>_<md5(view yolu)>.php`
  (`ViewEngine.php:140`). Kaynak daha yeni değilse önbellek kullanılır (`142-144`).
* **Kök koruması**: derlenecek görünüm **her zaman** proje veya framework kökünde
  olmalıdır; ikinci savunma hattı `guardRoots()`'tır (`ViewEngine.php:130-133`).
* **Yönerge→PHP** (`ViewEngine.php:212-241`): `@if/@elseif/@else/@endif`,
  `@foreach/@endforeach`, `@for/@endfor`, ve **layout yönerge**leri:
  `@extends('…')` → `$layout->setExtends(…)`,
  `@section('…')` / `@endsection` / `@yield('…')`
  (`ViewEngine.php:229-232`). Uygulama ek yönergeleri yapılandırmadan gelir
  (`ViewConfig::directives()`, `ViewEngine.php:236-237`).
* **İfade sözdizimi** (`ViewEngine.php:170-196`):
  `{{ expr }}` → `htmlspecialchars((string)(expr ?? ""), ENT_QUOTES, "UTF-8")` (`182`),
  `{!! expr !!}` → ham çıktı (`191`).
  İkisi de **`TemplateExpressionGuard::assertSafe()`**'dan geçer (`181`, `190`;
  `Core/Render/Handlers/TemplateExpressionGuard.php:114`).
* **Layout sarmalama**: görünüm `includeCompiled()` ile basılır, **sonra**
  `LayoutResolver::getExtends()` dönen layout yeniden `render()` edilir
  (`ViewEngine.php:77-102`). Yani içerik **bölümler (`@section/@yield`) ile** taşınır,
  `$content` değişkeni ile **değil** (bkz. `../acik-sorular.md`).
* Veri izolasyonu: `includeCompiled()` ayrılmış adları (`__rbnFile`, `__rbnData`,
  `this`, `GLOBALS`) **siler ve günlüğe yazar** (`ViewEngine.php:112-123`).

---

## 7. CLI yolu (`rbn`)

`rbnframework/rbn` (giriş noktası):

1. `define('RBN_CLI', true)` (`rbn:9`).
2. `PhpVersionGate::enforce()` — **autoload'dan önce** (`rbn:14-15`).
3. Autoloader yoksa ölüm (`rbn:21-23`).
4. `--project=<anahtar>` ve `--master` ayrıştırılır (`rbn:41-48`).
5. **Otomatik master modu**: `list`, `system:cron`, `project:list`, `db:*`,
   `cache:clear`, `logs:clear`, `tmp:clear`, `system:doctor`, `doctor`,
   `version:check`, `version:next`, `master:migrate`, `master:migrate:status`
   `--master` olmayı **kendisi** açar (`rbn:52-77`).
6. Yol çözümü (`rbn:80-111`):
   * `--project` verilmişse proje verisi master'dan okunur; yoksa **hata + çıkış 1**
     (`rbn:86-91`). Proje kökü `custom_path` üzerinden kurulur (`93-97`).
   * `--project` **yoksa**: çalışma dizini `public` ise o, değilse
     **`<workspace>/tmp`** proje kökü ve framework dizini public olarak kullanılır
     (`rbn:99-107`). **Bu yüzden projesiz komutlarda `Paths::project()->storage()`
     çalışma anında `<workspace>/tmp` altında oluşur.**
7. `Paths::init()` (`rbn:111`) → `Autoload::boot()` (`rbn:114`) →
   `rbn_helpers.php` `require` (`rbn:116`) → `rbncli` servisinin `run($argv)`
   çağrılır (`rbn:117-121`).
8. İstisna: `[CLI Error]` + log + `secrets.php` `app.environment = 'development'` ise kaynak satırı,
   çıkış kodu 1 (`rbn:124-138`).

**Komut listesi (ölçüldü, `php rbn list`):**
`cache:clear`, `db:info`, `db:query`, `db:schema`, `db:tables`, `doctor`, `list`,
`logs:clear`, `make:controller`, `make:migration`, `make:model`, `make:request`,
`master:migrate`, `master:migrate:status`, `migrate`, `migrate:rollback`,
`migrate:status`, `project:list`, `system:cron`, `system:doctor`, `tenant:apply`,
`tenant:audit`, `tenant:plan`, `tenant:revert`, `tmp:clear`, `version:check`,
`version:next`. Sürüm satırı: **CLI v2.3.0** (`rbn list` çıktısı;
`FrameworkIdentity::FRAMEWORK_CLI_VERSION = '2.3.0'`).

**Kısıt:** CLI'da HTTP isteği yoktur; `$this->request` **kullanılamaz**.
Web controller sınıfları CLI'da doğrudan çalıştırılamaz
(`.agents/rules/database-and-cli.md` §6). Çok kiracı işlemlerde
`$this->tenant->resolve()` ile bağlam kesinleştirilir (aynı kural §6).

---

## 8. Ortam algısı: `is_local()` ↔ DB profili kararı

Bu ikisi **bilinçli olarak farklıdır** ve karıştırılmamalıdır.

| | `is_local()` | DB profili (`ProjectDbProfileResolver`) |
|---|---|---|
| Kod | `Core/Support/Bridges/Helpers/Global/system_helpers.php:57-97` | `Core/System/Config/Engine/Database/ProjectDbProfileResolver.php:99-107` |
| Soru | "Bu istek/oturum **yerel mi**?" | "Bu **sunucu** hangi DB profilini kullanacak?" |
| HTTP'de girdi | `HTTP_HOST` + `REMOTE_ADDR` → `PreBoot::isTrustedLocalEnvironment()` (`system_helpers.php:64-76`) | **`HTTP_HOST`/`REMOTE_ADDR` BİLEREK KULLANILMAZ** |
| CLI'da girdi | `DOCUMENT_ROOT`, `__DIR__`, `getcwd()` içinde **tam** `localhost` segmenti (`system_helpers.php:78-96`) | **framework kökünün** yolunda tam `localhost` segmenti (`ProjectDbProfileResolver.php:205-216`) |
| Karşılaştırma | sonuç `bool`, önbellekli (`static $isLocal`, `system_helpers.php:59-62`) | `local` \| `production` (fail-closed) |
| Tipik kullanım | robots.txt muafiyeti (`RobotsResolver.php:19`), IndexNow/e-posta atlama | `DB_PROFILES` seçimi (`ProjectDbProfileResolver.php:121-171`) |

**Neden farklı?** Gerekçe kodun içinde yazılıdır
(`ProjectDbProfileResolver.php:73-83`): `is_local()` bir **istemci tarafı** yardımcısıdır
ve `Host` başlığı **saldırgan kontrollüdür**. Veritabanı seçimi böyle bir karara
bağlanırsa aynı sunucuda istek kimliğine göre **farklı veritabanlarına** bağlanılır —
yerelde dış ağdan gelen istek `production` seçer, üretimde `local` denenir.
Ölçüm kanıtı aynı yerde: `REMOTE_ADDR` doküman IP'si ile kabul harness'i 8 dosyada
hata üretti.

**Karar sırası** (`ProjectDbProfileResolver.php:85-96, 99-107`):

1. `secrets.php` `app.db_profile` = tam `local` ya da tam `production`
   (`declaredProfile()`, `180-189`). Başka değer (`yerel`, `1`, boş) **karar yok** sayılır, sıradakine geçilir. Ortam değişkeni okunmaz (FW-096-D8).
2. Framework kökünde **tam** `localhost` yol segmenti → `local`.
3. Hiçbiri okunamazsa/belirsizlik → **`production`** (fail-closed).

`getcwd()` ve alan adı **bilerek kullanılmaz** (cron'un çalışma dizini sunucuda
`/root` da olabilir; `ProjectDbProfileResolver.php:92-94`).

---

## 9. Güvenlik sınırları (burada tekrar etmeye değmez)

* `.github/SECURITY.md` — bildirim kanalları, desteklenen sürümler, koordineli ifşa.
* `.github/KNOWN-LIMITATIONS.md` — **bilinçli olarak sıkılaştırılmamış** tercihler.
* `Core/System/Config/README.md` — sır **tek kapı** kuralı (`secrets.php`; ortam değişkeni yok).
* `Core/System/Kernel/Base/PreBoot.php` — `RBN_DEV` fail-closed kapısı.
* `Core/Render/Handlers/TemplateExpressionGuard.php` — şablon ifadesi güvenliği.
* `RedirectManager` `guvenliHedef()` zinciri — `Location` başlığı güvenliği.
* `.agents/rules/core-architecture.md` — `new` yasağı, Repository/Provider ayrımı,
  §9 proje adı yasağı, §10 İngilizce metot adı.

---

## 10. Bilinmeyenler

Bu belgede kodda doğrulanamayan veya çelişen noktalar
[ `../acik-sorular.md` ](../acik-sorular.md) dosyasında listelidir. Özetle:

* `Core/System/Config/README.md:20-21` `Engine/Config/` yolunu yazıyor; kodda bu
  dosyalar `Engine/Config/` **ve** `Engine/Database/` altında dağınık.
* `MasterRbnHeartbeatsModel::$table = 'rbn_heartbeats'`, ancak master'da ölçülen tablo
  adı `z_sys_heartbeats` (ve `MasterDbData::REQUIRED_TABLES` da `z_sys_heartbeats`'i
  sayıyor — `MasterDbData.php:54-60`).
* `ModuleContext`/`ModuleDiscoveryDriver` kaynak taramasının **hangi dizinleri**
  taradığı bu belgede doğrulanmadı.
