# Core/Base — framework'ün soyut tabanı (bileşen, servis, veri, web)

> **Doğrulanan kod tabanı:** `1d89c431` (dal `feat/fw-license-master`) · **Tarih:** 2026-10-05 · **Yayın:** 0.9.4 = bu commit + sonrası; belge yalnız doğrulama anındaki kodu anlatır
> **Kaynak klasör:** `Core/Base/` — 65 `*.php`.
> **Envanter:** 65 dosyanın 65'i aşağıda anlatıldı (alt dal belgelerinde dağınık).

## 1. Ne işe yarar, kim kullanır

Framework'ün **tüm somut sınıflarının türediği kök**: `BaseComponent`
(lazy keşif + bağlam), `BaseService` (tekil orkestratör), `BaseModel` /
`BaseRepository` / `BaseConfig` (veri), `BaseController` / `BaseRender` /
`BaseManager` (web), `BaseChannel` / `BaseProxy` (kalıp). Her şey
`BaseComponent::__get()` ile `$this->service(...)`, `$this->model(...)`,
`$this->repository(...)` gibi uydurma özelliklerini çözer.

**Kimler çağırır:** neredeyse her katman. Somut sınıflar (`Core/Services`,
`Bundles/*`, `Packages/*`) bu hiyerarşiyi kalıtır.

## 2. Klasör/dosya envanteri

| Alt dal belge | Kapsam | Dosya |
|---|---|---|
| (bu belge) | kök (2), `Attributes/` (4), `Concerns/` (12), `Patterns/` (3) | 21 |
| [Data.md](Data.md) | `Data/` ve `Data/Traits/**` | 18 |
| [Services.md](Services.md) | `Services/` ve `Services/Traits/**` | 18 |
| [Web.md](Web.md) | `Web/` ve `Web/Traits/**` | 8 |
| **Toplam** | | **65** |

### 2.1 Kök

| Dosya | Görev | Önemli public yöntemler |
|---|---|---|
| `BaseComponent.php` | Kök soyut sınıf; `ArrayAccess`, `__get()` ile lazy keşif, `beforeBoot/afterBoot` kancaları, özyineleme koruması. | `__construct(?BaseService $rbn = null)`, `const B92_UYARI_ETIKETI`, `__debugInfo(): array`, `component(string $type,?string $target=null,bool $mandatory=true): ?object`, `__get(string $name)` |
| `BaseAttribute.php` | PHP attribute tabanı (alias/metadata/parent + `__get` ile metadata okuma). | `const __construct(array $metadata = [])`, `setParent(object $parent): void`, `__get(string $name)`, `config(string $key,$default=null)` |

### 2.2 `Attributes/` (4)

| Dosya | Görev | Yapıcı imzası / alanlar |
|---|---|---|
| `Bundle.php` | Paket kimliği attribute'u. | `__construct(public ?string $context = null, public array $map = [], public bool $register = true)` |
| `Component.php` | Bileşen (cache bayrağıyla) attribute'u. | `__construct(…, public bool $cache = false)` |
| `Module.php` | Modül kimliği: ikon/açıklama/başlık/SEO. | `__construct(…, ?string $icon = null, ?string $description = null, ?string $title = null, bool $seo = true)` |
| `SubModule.php` | Alt modül kimliği: veri sınıfı, entity adı, bulk girdi anahtarı, modal. | `__construct(…, ?string $data = null, ?string $entityName = null, ?string $bulkInputKey = null, ?string $modal = null)` |

### 2.3 `Concerns/` (12)

| Dosya | Görev | Önemli yöntemler |
|---|---|---|
| `BaseContextTrait.php` | Bağlamın kökü: `SovereignIdentity` + `IdentityExtractorTrait` + `ComponentHydratorTrait` + `ResolvesProjectConfigTrait`; `discover()`, `bootBaseContext()`, `map()`. | `discover(): DiscoveryEngine`, `bootBaseContext(): void`, `map(string $type, array $manual = []): array` |
| `ConcernsContextTrait.php` | Tüm alt bağlamları birleştirir ve boot sırasını yürütür. | `bootConcernsContext(): array` |
| `Contexts/ServicesContextTrait.php` | Servis/keşif/veritabanı/oturum kısayolları. | `resolveDiscovery(string $type,string $name)`, `initServicesContext(): array`, `config(string $key,$default=null)`, `db(): Database`, `session(): SessionManager` |
| `Contexts/StorageContextTrait.php` | Paylaşılan depolama motoru (cache/log/oturum). | `initStorageContext(): array`, `resolveStorage(): ?object`, `static forgetStorage(): void`, `cache()/logs()/sessions()`, `logWorkspace(?string $channel=null)` |
| `Contexts/HttpContextTrait.php` | İstek/yanıt motoru bağlamı. | `initHttpContext(): array` |
| `Contexts/ResourceResolverTrait.php` | Hedef repository/model/proje anahtarı çözümü ve standart 301. | `resolveTargetRepository(?string $fallbackKey=null): ?object`, `resolveTargetModel(?string=null): ?object`, `resolveCurrentProjectKey(?string $passedKey=null): string`, `perform301Redirect(string $pathOrUrl): void` |
| `Data/HybridAccessTrait.php` | Dizi erişimi + `toArray()`. | `offsetExists/offsetGet/offsetSet/offsetUnset(mixed)`, `toArray(): array` |
| `Data/InteractsWithProjectContextTrait.php` | Proje bağlamı değiştirme (CLI). | `activeProjectKey(): ?string`, `isValidProject(string): bool`, `switchProjectContext(string): bool`, `runInProjectContext(string,callable): mixed`, `restoreProjectContext(): bool` |
| `Data/ResolvesProjectConfigTrait.php` | Proje rota haritası/ayar/veri/yol/ad alanı çözümleme. | `static resetRouteMapCache()/resetAllRouteMapCaches()`, `getRouteConfig(string $projectKey,?string $key=null)`, `targetProjectUrl(string $projectKey,string $path=''): string`, `sanitizeCustomPath(mixed): string`, `resolveProjectConfig(string $configFile,?string $projectKey=null): array`, `resolveProjectData(string $key,?string $projectKey=null): mixed`, `resolveProjectSetting(string $key,?string $projectKey=null): mixed`, `resolveProjectPath(?string $projectKey=null,string $subPath=''): string`, `resolveProjectNamespace(?string $projectKey=null,string $subNamespace=''): string` |
| `Hydration/ComponentHydratorTrait.php` | Aktif controller'ın bileşenlerini somutlaştırır. | `hydrateComponents(): void` |
| `Identity/IdentityExtractorTrait.php` | Aktif controller'dan kimlik alanlarını (module/panel/sub_module/hub) kopyalar. | `extractIdentity(): void` |
| `Identity/SovereignIdentity.php` | Tüm bileşenlerin paylaştığı **durum alanları** (`$rbn`, `$discover`, `$module`, `$projectKey`, …) — metot içermez. | — (yalnız alanlar: `rbn`, `discover`, `shield`, `service`, `model`, `storage`, `http`, `request`, `response`, `module`, `sub_module`, `hub`, `panel`, `context`, `appName`, `projectKey`, `appVersion`, …) |

### 2.4 `Patterns/` (3)

| Dosya | Görev | Önemli public yöntemler |
|---|---|---|
| `BaseChannel.php` | Sorgu/kanal akıcı arayüzü (filtre, arama, önbellek, hidrasyon, sayfalama). | `__construct(string $cachePrefix)`, `status()/unread()/latest()/oldest()/active(): self`, `limit()/offset(int): self`, `where(string,$value,string $operator='='): self`, `search(string): self`, `cache(?int $ttl=null): self`, `asArray()/asObject()/asCollection(): self`, `hydrate(mixed): mixed`, `paginate(int $perPage,?string $url=null): object`, `toPackage(): array`, `getFromCache(string $key,callable $callback)` |
| `BaseProxy.php` | Keşif proxy'si: hafif kurulum, iki aşamalı `__get`. | `__construct(string $type)`, `__get(string $name)`, `resolveDiscovery(string $type,string $name)` |
| `Traits/Proxy/DiscoveryProxyTrait.php` | Proxy davranışını taşıyan trait. | `initializeProxy(string $type): void`, `__get(string $name)`, `__call(string $name, array $args)` |

**Kapsama (bu belge):** 21/21 · **Alt dallarla toplam:** 65/65.

## 3. Akış — bir bileşenin doğuşu

```
new UsersModel()  (veya herhangi bir BaseComponent türevi)
 └─ BaseComponent::__construct(?BaseService $rbn)      BaseComponent.php:90
     ├─ DI verilmiş ama kullanılmıyorsa saatlik UYARI (LogThrottle::once)  :100-109
     ├─ beforeBoot()                                   :112
     ├─ bootConcernsContext()                          ConcernsContextTrait.php:41
     │    ├─ bootBaseContext()                          BaseContextTrait.php:53
     │    │    ├─ $this->rbn = BaseService::get()       :56
     │    │    ├─ controller ise setActiveController($this)  :59-61
     │    │    ├─ extractIdentity() + hydrateComponents()  :64-72
     │    │    ├─ $this->discover = $this->discover()  :82
     │    │    └─ Bootstrap::getAppContext('project_data') → appName/projectKey/appVersion  :91-99
     │    ├─ initServicesContext()  (discovery + db + session)  :48
     │    ├─ initStorageContext()   (paylaşılan StorageManager) :49
     │    └─ initHttpContext()      (request + response)        :50
     └─ afterBoot()                                    :118
```

Uydurma özellik erişimi:

```
$this->model('project.user')                 (service/repo vb. de aynı yol)
 └─ BaseComponent::__get('model')            BaseComponent.php:194
     ├─ önbellekte çözülmüşse doğrudan dön  :196-203
     ├─ ComponentTypes::typeMap() son eklerini dener  :206-218
     │    'user' → 'model' → component('model','user')
     └─ eşleşme yoksa null                  :220
```

## 4. Yapılandırma / ayarlar

| Öğe | Yer | Not |
|---|---|---|
| `RESOLVED_SUFFIX_CACHE_LIMIT = 512` | `BaseComponent.php:48` | Son ek çözümleme önbelleği sınırı; aşınca tamamen boşaltılır |
| `component()` derinlik sınırı `> 20` | `BaseComponent.php:165-182` | Aşılırsa `rbn_panic` (yoksa `RuntimeException`) |
| `B92_UYARI_ETIKETI = 'b92-di-yok-sayildi'` | `BaseComponent.php:85` | Saatlik, sınıf başına tek DI uyarısı |

## 5. Tuzaklar ve kurallar

1. **`__construct(?BaseService $rbn)` imzası korunmuştur ama DI bağı kurmaz.**
   Parametre yalnız uyarı üretir (B-92). Gerçek erişim `BaseService::get()`
   singleton'ıdır (`BaseComponent.php:68-109`).
2. **Saatlik uyarı `LogThrottle::once()` kullanır.** Süreç içi "bir kez"
   koruması PHP-FPM'de işe yaramaz (her istek yeni süreç); ölçülen gürültü
   162 isteklik koşuda 596 satırdı (`BaseComponent.php:76-81`).
3. **Çerçeve kendi uyarısını tetiklememelidir.** `HttpContextTrait` ve `DiscoveryEngine`, parametreyi gövdede kullanmayan `RemoteRequest` / `ViewEngine` nesnelerini **argümansız** açar (`new RemoteRequest()`, `new ViewEngine()`); B-92 uyarısı yalnız çağıranın bilerek verdiği DI nesnesi için yazılır. Yeni bir iç çağrıda `$hub`/`$rbn` geçirmeyin.
3. **Özyineleme koruması 20 derinliktedir** ve `finally` ile daima geri
   alınır (`BaseComponent.php:163-188`).
4. **Aktif controller DNA'sı önce gelir.** Bileşenler kendi `module/panel`
   değerlerini önce controller'dan alır, sonra servisten tamamlar
   (`BaseContextTrait.php:64-88`).
5. **Controller'a view bağlamı otomatik verilir:** `appName`, `projectKey`,
   `projectGroup`, `appVersion` (`BaseContextTrait.php:102-107`).
6. **`BaseProxy` `parent::__construct()` çağırmaz**; `rbn` ve `discover`
   lazy kalır (`BaseProxy.php:64-73`).
7. **Proxy birincil keşfi ölçümde çalışmıyor:** `DiscoveryProxyTrait` yanlış
   namespace'i (`System\Registries\…`) çağırıyor; hata yutuluyor ve
   süreç başına bir kez `security` kanalına düşüyor
   (`BaseProxy.php:77-99`). Düzeltme bilinçli olarak **yapılmadı** (Aşama 2).
8. **`perform301Redirect()` `guvenliHedef()` süzgecinden geçmez** — kendi
   `header()`'ını yazar (`ResourceResolverTrait.php:60-67`). Bu yol
   kullanılacaksa açık yönlendirme riski göz önünde bulundurulmalıdır.

## 6. Örnek (gerçek koddan)

Özellik çözümleme (son ek → tip → hedef), `BaseComponent.php:206-218`:

```php
// $this->user  →  typeMap() içinde 'user' son eki 'model' tipine karşılık gelir
//              →  component('model', 'user')  →  UsersModel örneği
$rows = $this->user->where('is_active', 1)->get();
```

Proje bağlamı değiştirme (`InteractsWithProjectContextTrait.php:71-74`):

```php
$sonuc = $this->runInProjectContext('ornek-proje', function () {
    return $this->user->count();
});
$this->restoreProjectContext();
```

## 7. İlgili belgeler

* [Data.md](Data.md), [Services.md](Services.md), [Web.md](Web.md)
* [../System/Discovery.md](../System/Discovery.md) — `ComponentResolver`, `ComponentTypes::typeMap()`
* [../Database/README.md](../Database/README.md) — `BaseModel`/`BaseRepository` tüketicileri
* [../../kavramlar/01-mimari-harita.md](../../kavramlar/01-mimari-harita.md) — Kernel aşamaları ve bileşen keşfi