# Core/Support/Contracts — Arayüz (interface) sözleşmeleri

> **Doğrulanan kod tabanı:** `d508f5e1` (dal `feat/fw-license-master`) · **Tarih:** 2026-10-05 · **Yayın:** 0.9.6 = bu commit + sonrası; belge yalnız doğrulama anındaki kodu anlatır
> **Kaynak klasör:** `Core/Support/Contracts/` — 21 `*.php` (`Base` 7, `Collections` 1, `Console` 1, `Database` 3, `Discovery` 1, `Http` 3, `Kernel` 2, `Routes` 2, `Services` 1).
> **Envanter:** 21 dosyanın 21'i aşağıda anlatıldı. "Uygulayan" sayıları `implements`/`extends` taraması (`Core`, `Bundles`, `Packages`; sözleşme dosyalarının kendisi hariç) ile ölçüldü.

## 1. Ne işe yarar, kim kullanır

Katmanlar arası **imza sözleşmeleridir**; gövdesiz, yalnız `interface`. Taban sınıflar (`BaseService`, `BaseModel`, `BaseController` …), HTTP motoru (`Request`, `Response`), rota motoru, veritabanı sağlayıcıları ve kernel aşamaları bunlara bağlanır. Amaç: üst katmanın alt katmanı somut sınıf yerine sözleşmeyle çağırabilmesi ve ihlalin `php -l`/çalışma anında erken görünmesi.

## 2. Dosya envanteri

### 2.1 `Base/`

| Sözleşme | Genişlettiği | Yöntemler | Uygulayan |
|---|---|---|---|
| `BaseConfigInterface` | — | `at(string $path): self`, `get(string $key, mixed $default = null): mixed`, `set(string $key, mixed $value): self`, `has(string $key): bool` | **0** (başka dosyada ad geçmiyor) |
| `BaseControllerInterface` | `DiscoveryInterface` | `set(string\|array $key, $value = null): self`, `get(string $key)`, `render(string $module, string $view, array $data = []): View`, `paginate(?array $items): Paginator` | 1 |
| `BaseModelInterface` | `DiscoveryInterface` | `query(): object`, `create(array): int\|bool`, `update($id, array): bool`, `destroy($id): bool`, `save(array): bool\|int`, `toggleStatus($id, string $field = 'is_active'): bool`, `deleteBulk(array $ids): int`, `updateBulk(array $ids, array $data): int`, `toggleStatusBulk(array $ids, bool $status, string $field = 'is_active'): int`, `find($id): ?array`, `all(): array`, `getTable(): string`, `getPrimaryKey(): string` | 31 (modeller) |
| `BaseProviderInterface` | — | (işaretçi: yöntem yok) | 2 |
| `BaseProxyInterface` | — | (işaretçi: yöntem yok) | 1 |
| `BaseRenderInterface` | — | `render(?string $view, array $data = []): mixed` | 11 |
| `BaseServiceInterface` | `DiscoveryInterface` | `boot(): void`, `find(int $id): ?object`, `withId(int $id): self`, `destroy(?int $id = null): self`, `toggleStatus(?int $id = null, $status = null): self`, `clearCache($customKeys = null): self`, `success(): bool` | 9 |

### 2.2 Diğer klasörler

| Sözleşme | Yöntemler | Uygulayan |
|---|---|---|
| `Collections/CollectionInterface` | `all`, `where($key,$operator,$value=null)`, `map`, `filter`, `pluck`, `sortBy`, `sortByDesc`, `groupBy`, `first`, `last`, `count`, `merge`, `add`, `push`, `toArray` (15) | 1 |
| `Console/CommandInterface` | `execute(array $params = []): void`, `description(): string`, `getCommands(): array` | 1 |
| `Database/DbProviderInterface` | `getPdo(): PDO`, `beginTransaction()`, `commit()`, `rollback()`, `inTransaction()`, `getLastInsertId(): int` | 2 |
| `Database/QueryProviderInterface` | `select`, `where`, `orWhere`, `whereIn`, `join`, `orderBy`, `limit`, `get`, `first`, `insert`, `update`, `delete`, `toSql`, `getEagerLoads`, `increment`, `decrement`, `exists`, `count` | 1 |
| `Database/SchemaProviderInterface` | `drop`, `truncate`, `optimize`, `convert(string $collation)`, `rename(string $newName)`, `exists`, `getColumnListing`, `getDatabaseName`, `getPrimaryKey`, `getCreateSql`, `getStatus(?string $table = null)` | 1 |
| `Discovery/DiscoveryInterface` | 23 yöntem: `service, model, helper, queries, handler, constant, validation, provider, command, manager, cluster, resolver, builder, job, task, preset, rule, prompt, queue, rbnEvent, listener, action, rbnDriver` (hepsi `(string $name)`; `validation(string $path)`) | 2 (`BaseResolver`, `DiscoveryEngine` dahil; üst sözleşmeler üzerinden daha çok sınıf) |
| `Http/AlertServiceInterface` | `success/error/info/warning(string $message, ?string $redirectUrl = null, ?string $message2 = null, array $data = []): self`, `viaCookie()`, `viaSession()`, `send(string $type, …): void` | 1 |
| `Http/RequestInterface` | 35 yöntem: `all, rawAll, input, query, has, filled, missing, only, except, method, isMethod, isPost, isGet, isAjax, wantsJson, wantsXml, isJson, isFormData, prefers, isSecure, ip, userAgent, path, url, fullUrl, root, bearerToken, header, file, hasFile, allFiles, shield, validate, form, filter` | 1 |
| `Http/ResponseInterface` | `status, header, contentType, cookie(…), json(array, int), success, error, alertJson, body, send, redirect(string $url, int $code = 302), noContent` | 1 |
| `Kernel/GuardInterface` | `static check(): void` | 1 (`BaseGuard`) |
| `Kernel/StageInterface` | `handle(Kernel $kernel): void` | 1 (`BaseStage`) |
| `Routes/RouteInterface` | `get(string $path, $handler): self`, `post(…)`, `match(?string $method = null, ?string $uri = null): ?array`, `dispatch(): void`, `url(): object` | 1 |
| `Routes/RouteHandleInterface` | `current(): string`, `url($nameOrPath, $params = [], ?string $panel = null, ?string $module = null): string`, `to(…)`, `isActive(string $path, …, bool $exact = false): bool`, `redirect(…)`, `alert(string $type, string $message, ?string $path = null, ?string $title = null, array $data = [])`, `handleResult($resultOrCondition, $optionsOrPath = '/')` | 1 |
| `Services/IpGuardProviderInterface` | `fetch(array $options): array`, `save(array $data): bool`, `destroy(int $id): bool`, `isWhitelisted(string $ip): bool` | **0** (başka dosyada ad geçmiyor) |

## 3. Akış

Sözleşmeler çağrı zinciri üretmez; kullanım noktaları:

* **Kernel:** `KernelFactory` `Kernel::addStage(StageInterface)` ile aşamaları alır ([Kernel §3.3](../System/Kernel.md)); `BaseGuard` `GuardInterface`'i uygular, `BaseGuard::fail()` istisna fırlatır.
* **Keşif:** `DiscoveryInterface` taban sınıflara `service()/model()/…` kısayollarını **zorunlu** kılar; uygulaması `DiscoveryActionsTrait`'tir ([Discovery §2.1](../System/Discovery.md)).
* **Modeller:** her model `BaseModelInterface`'in 13 yöntemini taşır (`query()`'den `getPrimaryKey()`'e).

## 4. Yapılandırma

Yok.

## 5. Tuzaklar ve kurallar

1. **`DiscoveryInterface` 23 yöntem tanımlar; `DiscoveryActionsTrait` 25 yöntem sunar:** sözleşmede `controller()` ve `repository()` **yoktur**, trait'te vardır (`DiscoveryActionsTrait.php:24,96`). Yani `repository()` sözleşme tipiyle (`DiscoveryInterface $x`) çağrılamaz; somut sınıf üzerinden çağrılır.
2. **İki sözleşme ölü:** `BaseConfigInterface` ve `IpGuardProviderInterface` `Core/Bundles/Packages` içinde hiçbir yerde anılmaz. Silinmeleri ya da uygulanmaları bir kod kararıdır; bu belge yalnız durumu raporlar.
3. **`RequestInterface::shield()`, `validate()`, `form()`, `filter()` HTTP sözleşmesine güvenlik/doğrulama davranışı ekler** (`Request` somut sınıfı bunları `FormRequest`/`ValidationTrait` ile sağlar); başka bir `RequestInterface` uygulaması bu dört yöntemi (`shield`, `validate`, `form`, `filter`) de vermek zorundadır.
4. **`BaseProviderInterface` ve `BaseProxyInterface` işaretçi (marker) arayüzlerdir**; tür denetimi dışında bir şey zorlamazlar.
5. **`ResponseInterface::json/success/error/redirect/noContent` dönüş tipi `void`'dir** (yanıtı gönderip kesmek beklenir); zincirleme yalnız `status/header/contentType/cookie/body` ile mümkündür.

## 6. Örnek (gerçek koddan)

```php
// Core/Support/Contracts/Kernel/StageInterface.php:17
public function handle(Kernel $kernel): void;
```

```php
// Core/Support/Contracts/Kernel/GuardInterface.php:17
public static function check(): void;
```

## 7. İlgili belgeler

* [Core/Support genel bakış](README.md) · [Exceptions](Exceptions.md) · [System/Kernel](../System/Kernel.md) · [System/Discovery](../System/Discovery.md)
* [Açık sorular](../../acik-sorular.md)
