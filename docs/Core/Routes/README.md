# Core/Routes — URL yönlendirme, rota toplama, eşleştirme ve 301 normalizasyonu

> **Doğrulanan kod tabanı:** `1d89c431` (dal `feat/fw-license-master`) · **Tarih:** 2026-10-05 · **Yayın:** 0.9.4 = bu commit + sonrası; belge yalnız doğrulama anındaki kodu anlatır
> **Kaynak klasör:** `Core/Routes/` — 13 `*.php` (3'ü rota eşleme dosyası, 10'u sınıf).
> **Envanter:** 13 dosyanın 13'ü aşağıda anlatıldı.

## 1. Ne işe yarar, kim kullanır

İstek URI'sini toplanan rota tablosuyla eşleştirir, middleware zincirini
çalıştırır, controller metodunu çağırır; ayrıca yanıt üretilmeden **önce**
tek bir kural zinciriyle URL'yi kanonikleştirip gerekirse **tek** 301 yazar.

**Kimler çağırır:** `Kernel/Base/PreBoot` → Routing aşaması → `Route::run()`
(`Core/Routes/Route.php:235`); controller içinden `Route::url()`,
`$this->Route->to()/isActive()/alert()/handleResult()`; CLI'de
`Route::loadRoutes()`.

## 2. Klasör/dosya envanteri

| Dosya | Görev | Önemli public yöntemler (imza) |
|---|---|---|
| `Route.php` | Statik **cephe** (facade). Rota bildirimini `Router`'a, çalıştırmayı `RouteManager`'a yönlendirir; `self::$attributes` üzerinde group niteliklerini biriktirir. | `prefix/middleware/role/controller/namespace/module/panel(): self`, `load(): self`, `group(callable): void`, `get/post/any(string,$a): self`, `name(string): self`, `url(string,$p=[],?string $panel=null,?string $module=null): string`, `dispatch(string $uri)`, `run()`, `loadRoutes(): void` |
| `RedirectManager.php` | URL normalizasyon motoru (kanonik alan adı, HTTPS, `www`, sondaki `/`, küçük harf, eski URL'ler, tuzak yollar, kendini iyileştiren asset'ler). Kuralları sırayla işletir ve **tek** 301 yazar. | `process(): void`, kural metotları `enforceCanonicalDomain/forceHttps/removeWwww/removeTrailingSlash/forceLowercaseUrl/redirectOldUrls/redirectHome/selfHealingAssets(): void` (protected), `guvenliYonlendirmeHedefi(string): string`, `buildUrl(): string`, `isHttps(): bool`, `shouldBypassRedirect(): bool`, `redirect(string,int $statusCode=301): void` |
| `Engine/Collector.php` | Rota deposu + group yığını. Prefix/namespace birleştirme ve `role:` middleware üretimi burada olur. | `pushGroup(array): void`, `popGroup(): void`, `addRoute(array $methods,string $uri,$action,array $extra=[]): void`, `nameLastRoute(string): void`, `getRoutes(): array`, `getNamedRoutes(): array`, `getRouteByName(string): ?string` |
| `Engine/Matcher.php` | URI → regex derleyip eşleştirir. `{param}`, `{param?}`, `{param:regex}` destekler; desen önbelleği vardır. | `__construct(array $routes=[])`, `setRoutes(array): void`, `match(string $uri,string $method): ?array` |
| `Engine/Dispatcher.php` | Middleware çalıştırır + controller'ı çağırır. Çözülemeyen middleware'de **fail-closed**. | `__construct(array $config=[])`, `dispatch(array $route): void`, `resolveMiddleware(string): array`, `setConfig(array): void` |
| `Engine/ControllerResolver.php` | Rota hedefini FQCN'ye çevirir (akıllı `\Controllers\` enjeksiyonu, `namespace` `MAP` sözlüğü). | `resolve(string $action, ?string $default = null): string` (statik) |
| `Engine/UrlGenerator.php` | Adlı rota veya yol tabanlı URL üretir; menü "aktif mi" denetimi yapar. | `setBasePrefix(string): void`, `setContext(?string,?string): void`, `url(string,array=[],?string $panel=null,?string $module=null): string`, `to(string,?string,?string): string`, `getPrefix(?string,?string,string $path=''): string`, `isActive(string,?string,?string,bool $exact=false): bool` |
| `Engine/Providers/RouteManager.php` | Merkezi servis: motor altıklarını kurar, panel ön eki (`dashboard_prefix`) yükler, **Prefix Guard** ve **Panel Kapalı Guard**'ı çalıştırır, `match()`+`dispatch()` yapar. | `__construct()`, `get/post(string,RouteInterface $h): RouteInterface`, `match(?string $m=null,?string $u=null): ?array`, `dispatch(?string $u=null,?string $m=null): void`, `collector()/matcher()/dispatcher()/url()`, `resolvePrefixGuardRedirect(string,string,string): ?string`, `getBasePrefix(): string`, `setContext(string,?string): void`, `to()/isActive()` (köprü) |
| `Engine/Providers/Router.php` | Singleton **köprü**: `RouteManager`'ın collector/matcher API'sini yalın, akıcı bir yüzeyle sunar. | `getInstance(): Router`, `group(array,callable): void`, `addRoute(array,string,$a,array=[]): void`, `nameLastRoute(string): void`, `getRouteByName(string): ?string`, `match(string,string)`, `dispatch(string,string)`, `getRoutes(): array` |
| `Engine/Providers/RouteHandle.php` | Controller içi rota asistanı (`$this->Route`). Sonuç nesnesini (servis/dizi/bool) mesaj + yönlendirmeye çevirir. | `__construct($controller)`, `current(): string`, `url(string,$params=[],?string,?string): string`, `to(string,?string,?string): string`, `isActive(string,?string,?string,bool): bool`, `redirect(string,?string,?string): void`, `alert(string,string,?string,?string,array): void`, `handleResult($resultOrCondition,$optionsOrPath='/'): void` |
| `Mappings/core.php` | Çekirdek rotalar: framework controller ad alanı, `403`/`500` sıfır-controller rotaları, SEO/sitemap/robots/feed rotaları (`CrawlerController`), framework asset proksi. | — (bildirim dosyası; `Route::get(...)` çağrıları) |
| `Mappings/auth.php` | Kimlik rotaları: giriş/kayıt/parola sıfırlama; `Route::module('Suite','RbnAuth')` ile modül enjeksiyonu; çıkış için POST+CSRF esas yol. | — (bildirim dosyası) |
| `Mappings/web.php` | Uygulama rotaları: `/api/v1/external` (`ApiGuard` middleware), frontend sayfa/SSS rotaları, `Route::module(...)->load()` ile modül rotaları, panel açık/kapalı denetimi. | — (bildirim dosyası) |

**Kapsama:** 13/13.

## 3. Akış — istekten çıktıya

### 3.1 Rotaların yüklenmesi

```
Kernel Routing aşaması
 └─ Route::loadRoutes()                                Route.php:255
     ├─ require Core/Routes/Mappings/core.php           Route.php:260
     ├─ require .../auth.php                            Route.php:261
     ├─ require .../web.php                             Route.php:262
     └─ Definition::get('route','FILES')['project']     Route.php:265-267  (bugün BOŞ dizi)
         └─ Definition::get('route','FILES') = RouteBlueprint::FILES
            (Definition::get → DefinitionResolver::get, sabit önce
             DefinitionResolver.php:37-40)
```

`Route::loadRoutes()` proje rotalarını yalnız `RouteBlueprint::FILES['project']`
içine dosya adı eklendiğinde yükler (`Route.php:265-280`).

### 3.2 Tek istek zinciri

```
Route::run()                                       Route.php:235
 ├─ BaseService::get()->manager('redirect')->process()   RedirectManager.php:105
 │    ├─ PHP_SAPI === 'cli' → return                  RedirectManager.php:108
 │    ├─ shouldBypassRedirect() (api/webhook/bot-sync/bot-data)  :609
 │    ├─ $initialUrl = buildUrl()                     :125 / :185
 │    ├─ 8 kural sırayla çalışır                     :128-132
 │    └─ $initialUrl !== $finalUrl → redirect($finalUrl) (TEK 301)  :137-139
 ├─ URI: parse_url(REQUEST_URI, PATH), trim '/'        Route.php:243-247
 └─ Route::dispatch($uri)                             Route.php:216
     └─ RouteManager::dispatch()                      RouteManager.php:86
         ├─ match() → matcher->setRoutes(collector->getRoutes())  :81
         │            matcher->match($uri,$method)   Matcher.php:30
         ├─ panel/module: uriParts[0]===basePrefix ? [1]:[2]  :100-107
         ├─ middleware rol kısayolu ile panel bulunamazsa  :110-117
         ├─ setContext($panel,$module)                :120
         ├─ Dispatcher::dispatch($route)              Dispatcher.php:24
         │    ├─ her middleware → runMiddleware()     :38
         │    │    ├─ 'role:X' → auth->check(), handler('access')->can()  :45-59
         │    │    ├─ hub->handler($mw) ya da class_exists → handle()  :63-93
         │    │    └─ hiçbiri çözülemezse RuntimeException (fail-closed) :107
         │    └─ callController()                     :137
         │         ├─ ControllerResolver::resolve()   :157
         │         ├─ new $class; call_user_func_array  :162-163
         │         ├─ dönüş null ise getActiveView()  :166-168
         │         └─ echo $response                  :171
         └─ eşleşme yoksa PageNotFoundException($uri) :127
```

`HEAD` istekleri `GET`e düşürülür ve gövde `ob_start()/ob_end_clean()` ile
yutulur (`Route.php:218-232`).

### 3.3 URL üretimi

`UrlGenerator::url()` önce **adlı rotayı** arar (`Collector::getRouteByName()`),
bulunamazsa `to()` ile yol tabanlı üretime düşer
(`UrlGenerator.php:44-79`). Adlı rotada kullanılmayan parametreler sorgu
dizesine eklenir (`UrlGenerator.php:50-64`). Panel ön eki
`getPrefix()`'te kurulur: `guest → /auth/`, `user → /`, `frontend → /` veya
başka projenin alan adı (`//domain/`), diğer paneller
`/<basePrefix>/<panel>[/<module>]/` (`UrlGenerator.php:149-193`).

## 4. Yapılandırma / ayar anahtarları

| Anahtar | Nerede okunur | Varsayılan / not |
|---|---|---|
| `RouteBlueprint::DASHBOARD_PREFIX` | `UrlGenerator.php:22,141,154` | `'dashboard'` |
| `RouteBlueprint::FILES` | `Route.php:265` | `framework: [core,auth,web]`, `project: []` — **proje listesi boş** |
| `RouteBlueprint::MIDDLEWARE` (`aliases`/`groups`) | `RouteManager::loadMiddlewareConfig()` `:172-175` → `Dispatcher::setConfig()` | gruplar: `user/admin/superadmin/developer → [auth, role:X]` (`RouteBlueprint.php:45-52`) |
| `RouteBlueprint::PANELS` | `UrlGenerator.php:109,130,203` | `['user','admin','developer','guest','frontend']` (`:73`) |
| `RouteBlueprint::LOGIN_PATH` | `RouteManager.php:298` | `'rbn-admin'` (`:75`) |
| `RouteBlueprint::AUTH_ROOTS` | `UrlGenerator.php:116-120` | `LOGIN_PATH` dahil liste (`:80-…`) |
| `RouteBlueprint::HONEYPOT_PATHS` / `_KEYWORDS` / `_ROUTE_EXEMPT` | `RedirectManager::redirectHome()` `:438-448`, `isApplicationRoute()` `:484` | tuzak → 301 `/` |
| `RouteBlueprint::FALLBACK_FAVICONS` / `_MOBILE_PREFIXES` / `_MOBILE_MANIFESTS` / `_FALLBACK_OG_IMAGES` | `RedirectManager::selfHealingAssets()` `:571-598` | gerçek proje dosyası yoksa uydurma adres **yazılmaz** |
| `RouteBlueprint::LEGACY_ALIASES` / `LEGACY_FONTS_KEYWORDS` | `RedirectManager::redirectOldUrls()/redirectHome()` `:411,452` | eski slug → yeni yol |
| `project_data('dashboard_prefix')` | `RouteManager::loadSettings()` `:159-163` | boşsa `dashboard` |
| `project_data('admin_panel_disabled')` / `disabled_options['is_adminpanel_disabled']` | `RouteManager::checkProjectQueryGuard()` `:311-314` | panel kapalıysa panel + auth GET'leri `/`ye 302 |
| `RedirectManager::LOWERCASE_EXEMPT_PREFIXES` | `RedirectManager::isLowercaseExemptPath()` `:79-88,335-346` | `/framework-assets, /project-assets, /media, /fw-proxy, /api, /webhook, /bot-sync, /bot-data` |

## 5. Tuzaklar ve kurallar

1. **Middleware fail-closed.** Çözülemeyen middleware `RuntimeException` fırlatır;
   controller **hiç çağrılmaz** (`Dispatcher.php:106-109`). Çözülen ama `handle()`
   metodu olmayan nesne yalnız loglanıp atlanır (`:98-104`) — bu bir koruma
   değildir, rotayı kırmamak için böyledir.
2. **Rol kısayolu gerçek bir middleware değildir.** `role:admin` önce
   `service('auth')->check()` ister; giriş yoksa `/`ye yönlendirip `exit`.
   Giriş var ama yetki yoksa `shield()->forbidden(...)` (`Dispatcher.php:44-59`).
3. **Rota deseni bozuksa eşleşmez.** `compilePattern()` `@preg_match` başarısızsa
   `~^(?!)~` yazar (fail-closed) (`Matcher.php:196-199`).
4. **Desen ayracı `~`.** Özel regex içindaki `~` kaçışlanır
   (`Matcher.php:82`); literal bölgede yalnız `.` ve `~` kaçışlanır
   (`escapeLiteral()`, `:96-99`).
5. **`{param}` blokları iç içe parantez kümesi taşıyabilir.** Dengeli sayaçla
   ayrıştırılır; `{key:[a-f0-9]{64}}` gibi desenler bu yüzden çalışır
   (`Matcher.php:139-152`). Kullanılmıyorsa `rbn/{key}` + `.txt` rotası
   (`Mappings/core.php:44`) bu mekanizmaya dayanır.
6. **Optional parametre yalnız `/` ile eklenirse açılır.** `(?:\/(...))?`
   üretilir; aksi hâlde segment gömülü kalır (`Matcher.php:175-183`).
7. **Prefix Guard yönlendirmesi şemaya bağlı değildir.** Üretilen hedef
   `response()->guvenliHedef()` süzgecinden geçer, kök yol değilse yönlendirme
   yapılmaz (`RouteManager.php:266-283`).
8. **Beyaz liste boşken 301 yazılmaz** (kök çözülemedi ve hedef kendi
   host'sa) — tarayıcıda kalıcı 301 birikmesi önlenir
   (`RedirectManager.php:686-690`).
9. **Tuzak kontrolü gerçek sayfaları kurtarır.** Yalnız `HONEYPOT_ROUTE_EXEMPT`
   girdileri için, ve gerçekten eşleşen bir rota varsa muafiyet verir;
   ölçüm/arka plan katmanı hataya düşerse `false` (eski davranış)
   (`RedirectManager.php:482-517`).
10. **`hasSegment()` alt dize değil segment karşılaştırır.** `/apifoo` ve
    `/blog/yazi-webhook-nasil` bypass olmaz (`RedirectManager.php:637-640`).
11. **Panel ön eki `user` muaf.** Guard'da `'user'` önekleri muaf sayılır
    (`RouteManager.php:302-308`).
12. **API/webhook 301'e uğramaz** ve `enforceCanonicalDomain`'u da atlar;
    kanal başındaki `Host` zorlaması bu yüzden oradan atlanamaz
    (`RedirectManager.php:120-123, 609-622`).
13. **Bir modülün rotaları TAM OLARAK BİR KEZ yüklenir.** `registerRoutes()`
    iki ayrı yoldan çağrılır: `Core/Routes/Mappings/web.php:33` ve
    `Kernel/Stages/Routing.php:38` → `ModuleDiscoveryDriver:89`. "Rotalar
    yüklendi" işareti (`ModuleDiscoveryDriver::$loadedRoutes`) **yalnız
    `registerBundles()` içinde** tutulduğu için ilk yol işarete dokunmuyor ve
    ikinci yükleme engellenemiyordu — statik adresler iki kez kaydediliyor,
    sitemap'e iki kez yazılıyordu (ölçüm: 17/17 proje, 63 fazladan `<loc>`).
    Artık `Route::load()` işareti `registerRoutes()` **çağrıldıktan sonra**
    koyar (önce konursa rota hiç yüklenmeden "yüklendi" görünürdü) ve anahtar
    `routeKey()` ile kanonikleştirilir — `Route::load()` sınıf adını
    **baştaki `\` ile**, `discoverBundles()` ise **onsuz** üretir;
    `class_exists()` ikisini de kabul eder ama dizi anahtarı olarak farklı
    oldukları için işaret yine tutmazdı.

## 6. Örnek (gerçek koddan)

Grup + middleware + adlandırma (`Mappings/auth.php:10-13, 22`):

```php
Route::module('Suite', 'RbnAuth')->prefix('')->group(function () {
    Route::get(RouteBlueprint::LOGIN_PATH, 'AuthViewController@showLogin')->name('login');
});

Route::post('auth/logout', 'AuthController@logoutSubmit')->name('auth.logout');
```

`prefix('rbn-admin')` çağrısı bir sonraki bildirime kadar biriktirilir ve
`Route::get()`/`post()`/`any()` **tüketir** (attributes sıfırlanır)
(`Route.php:47-50, 168-190`). Modül kaynak imzası esnek: hem
`Route::module('RbnAdmin')` hem `Route::module('suite','RbnAdmin')` hem
`Route::module('RbnAdmin','suite')` çalışır (`Route.php:79-93`).

Adlı rotadan URL üretimi:

```php
$url = Route::url('login');                       // Route.php:198
$url = $this->Route->to('panel', 'admin');       // RouteHandle.php:48 → url()
```

## 7. İlgili belgeler

* [../System/Discovery.md](../System/Discovery.md) — `Definition::get()` ve keşif motoru
* [../Base/README.md](../Base/README.md) — `BaseService`/`BaseManager` tabanı
* [../Http/README.md](../Http/README.md) — `Response::redirect()` / `guvenliHedef()`
* [../../kavramlar/01-mimari-harita.md](../../kavramlar/01-mimari-harita.md) — açılış zinciri ve Routing aşaması
* [../../acik-sorular.md](../../acik-sorular.md) §2.1 (modül keşfi ve `FILES['project']`)