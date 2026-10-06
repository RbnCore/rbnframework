# Core/Base/Data — model, repository ve ayar soyut sınıfları

> **Doğrulanan kod tabanı:** `1d89c431` (dal `feat/fw-license-master`) · **Tarih:** 2026-10-05 · **Yayın:** 0.9.4 = bu commit + sonrası; belge yalnız doğrulama anındaki kodu anlatır
> **Kaynak klasör:** `Core/Base/Data/` — 18 `*.php`.
> **Envanter:** 18 dosyanın 18'i aşağıda anlatıldı.

## 1. Ne işe yarar, kim kullanır

Veri katmanının üç soyut kökü ve onları kuran **15 trait**:
`BaseModel` (satır + kapsam + toplu atama koruması), `BaseRepository`
(iş kuralı kabuğu), `BaseConfig` (ayar projesi). Trait'ler motorları
ayırır: `Model/Engine/*` sorgu ve yazma, `Config/*` ayar erişimi,
`Repository/*` içerik sorguları ve önbellek.

**Kimler çağırır:** `Core/Database/Models/*` ve `Core/Database/Repositories/*`
doğrudan; içerik servisleri `Repository/ContentQueryTrait` ve
`Services/Traits/Service/Blogcontent/*` üzerinden.

## 2. Dosya envanteri

### 2.1 Kök (3)

| Dosya | Görev | Önemli public yöntemler / alanlar |
|---|---|---|
| `BaseModel.php` | Model kökü; `ArrayAccess`, `BaseModelInterface`. | `$connection='database_project'`, `$queryProvider=QueryBuilder::class`, `$scoped=false`, `$projectScopeIncludes=[]`, `$projectScopeKey=null`; `getActiveProjectKey(): string`, `isProjectScoped(): bool`, `getProjectScopeIncludes(): array`, `resolveScopeProjectKey(): ?string`, `withProjectScope(?string $projectKey): self`, `getProjectScopeColumn(): string`, `withoutProjectScope(): self`, `__construct()`, `getTable(): string`, `getTableOrNull(): ?string`, `getPrimaryKey(): string`, `getConnection(): string`, `getDb(): Database`, `static fromTable(string $name)` |
| `BaseRepository.php` | Repository kökü (boş gövde; `BaseComponent` türevi). | — (yalnız sınıf bildirimi) |
| `BaseConfig.php` | Ayar projesi; `Config::get()` statik geçidi (özyineleme korumalı). | `__construct(?string $rootContext = null)`, `registerMap(): array`, `static get(string $key, mixed $default = null): mixed`, `toArray(): array`, `offset*` |

### 2.2 `Traits/Config/` (3)

| Dosya | Görev | Önemli yöntemler |
|---|---|---|
| `Traits/Config/ActionConfigTrait.php` | Yapılandırma trait'i birleştirici (içeriği yalnız bu dosyada tanımlı). | — (trait tanımı) |
| `Traits/Config/Engine/DataConfigTrait.php` | Ön ekli veri deposu erişimi (`at()`, `get()`, `set()`, `has()`, `static data()`). | `at(string $path): static`, `resolveConfigPath(string $path): string`, `applyResolvedPrefix(string $key): string`, `get(string $key, mixed $default=null): mixed`, `set(string $key, mixed $value): self`, `has(string $key): bool`, `static data(string $key): mixed` |
| `Traits/Config/Engine/DiscoveryConfigTrait.php` | Keşif kayıtlarında ad çözümleme (`locate()`, `locateRegistry()`, `searchDeep()`). | `static locateRegistry(string $name, string $type): ?string`, `static getRegistryType(): string`, `static searchDeep(string $key, array $array): mixed`, `static locate(string $name, ?string $type=null): ?string` |

### 2.3 `Traits/Model/` (1) + `Traits/Model/Engine/` (10)

| Dosya | Görev | Önemli yöntemler |
|---|---|---|
| `Traits/Model/ActionModelTrait.php` | Model trait'lerini tek noktadan birleştirir (CRUD + sorgu + ilişki + toplu atama). | — (trait bileşimi) |
| `Engine/QueryModelTrait.php` | Sorgu başlatma, kapsam süzgeci, kapsam dışı ölçümü, CRUD köprüsü. | `const MODEL_NOT_SCOPED_THROTTLE_TAG`, `const MODEL_NOT_SCOPED_LOG_FLAG`, `query(): object`, `staticQuery(): object`, `logModelScopeVisibility(): void`, `where/orWhere/whereIn/whereNotIn/whereNull/whereNotNull/whereRaw/orWhereRaw/orderByRaw/with/select/join/leftJoin/rightJoin/innerJoin/orderBy/groupBy/having/limit/offset/count/exists/when`, `insert(array): bool\|int`, `update(array): bool`, `delete(): bool`, `truncate(): bool` |
| `Engine/CrudModelTrait.php` | Satır düzeyi yazma (beyaz/kara liste süzgeci içinden). | `create(array $data): int\|bool`, `update($id, array $data): bool`, `destroy($id): bool`, `save(array $data): bool\|int`, `toggleStatus($id, string $field='is_active'): bool`, `increment/decrement($id,string $column,int $amount=1)` |
| `Engine/BulkModelTrait.php` | Toplu yazma. | `deleteBulk(array $ids): int`, `updateBulk(array $ids, array $data): int`, `toggleStatusBulk(array $ids,bool $status,string $field='is_active'): int`, `insertBatch(array $data): bool`, `updateBatch(array $data,string $index='id'): bool` |
| `Engine/ReadModelTrait.php` | Tekil/tüm kayıt okuma + JSON doldurma. | `find($id): ?array`, `all(): array`, `forceJsonFill(array $row): void` |
| `Engine/RelationModelTrait.php` | İlişki kurucular. | `hasMany/hasOne/belongsTo(string $relatedModel, ?string $foreignKey=null, ?string $localKey=null)`, `getForeignKey(): string`, `loadRelationships(array $results, array $relations): array` |
| `Engine/MassAssignmentTrait.php` | Beyaz/kara liste, korumalı alan logu, `project_key` yazma kapısı. | `const MASS_ASSIGNMENT_FLAG='security.mass_assignment'`, `const PROTECTED_FIELD_LOG_FLAG='security.protected_field_log'`, `authorizeFields(array $fields): self`, `filterFillable(array $data): array`, `getFillableFields(): array`, `applyMassAssignmentGuard(array $data): array`, `applyProjectKeyScope(array $data): array`, `writeAsProject(string $projectKey): self`, `static fillProjectKeyIfMissing(array $data,?string $projectKey=null): array`, `protectedFieldLogEnabled(): bool`, `logProtectedFieldAttempt(array $data,string $operation): void` |
| `Engine/TimestampModelTrait.php` | `created_at`/`updated_at` ekleme. | `$timestamps = true`, `prepareTimestampForStorage(array $data, bool $isNew = true): array` |
| `Engine/JsonModelTrait.php` | JSON alanlarını serileştirme/ayrıştırma. | `prepareJsonForStorage(array $data): array`, `fillJsonFields(array $row): array` |
| `Engine/TransactionModelTrait.php` | Model düzeyinde transaction yüzeyi. | `transaction(callable $callback)`, `beginTransaction(): void`, `commit(): void`, `rollBack(): void` |

### 2.4 `Traits/Repository/` (2)

| Dosya | Görev | Önemli yöntemler |
|---|---|---|
| `Repository/ContentQueryTrait.php` | İçerik (blog) sorguları: liste, kategori sayaçları, slug benzerliği, görüntülenme artırma. | `getPosts(array $options=[]): array`, `categoriesWithCounts(?string $projectKey=null): array`, `getPostBySlug(string $slug,?string $projectKey=null): ?array`, `getPostBySimilarSlug(string $slug,?string $projectKey=null): ?array`, `incrementViews(int $id): bool`, `rowsToArray($sonuc): array`, `fallbackProjectKey(): string` |
| `Repository/ContentCacheTrait.php` | Proje bazlı içerik önbelleği. | `getCacheItem(string $key): mixed`, `setCacheItem(string $key,mixed $value,int $ttl=3600): void`, `flushProjectCache(?string $key=null): void`, `resolveActiveProjectKey(?string $explicitKey=null): string` |

**Kapsama:** 18/18.

## 3. Akış — yazma yolunda koruma zinciri

```
$model->update($id, $data)
 ├─ CrudModelTrait::update()                     CrudModelTrait.php:57
 ├─ TimestampModelTrait::prepareTimestampForStorage()   (timestamps=true ise)
 ├─ JsonModelTrait::prepareJsonForStorage()
 ├─ MassAssignmentTrait::applyMassAssignmentGuard()    MassAssignmentTrait.php:86
 │    ├─ massAssignmentMode(): 'on' değilse dokunma  :183-191
 │    ├─ $fillable beyaz listesi (varsa)              :91-93
 │    └─ $guarded kara listesi − authorizeFields()    :94-101
 ├─ MassAssignmentTrait::applyProjectKeyScope()   (writeAsProject ile açıldıysa)
 └─ QueryModelTrait::query()->update()  → QueryBuilder → Database
```

Kapsam açık modelde okuma:

```
$model->where(...)->get()
 └─ QueryModelTrait::query()                      QueryModelTrait.php:44
     └─ $scoped && resolveScopeProjectKey() !== null/''  → where/whereIn  :88-108
```

## 4. Yapılandırma

| Sabit / anahtar | Yer | Varsayılan |
|---|---|---|
| `security.mass_assignment` | `MassAssignmentTrait.php:42` | `on` (yalnız `on|enforce` zorlar) |
| `security.protected_field_log` | `MassAssignmentTrait.php:50` | `on` |
| `security.model_not_scoped_log` | `QueryModelTrait.php:39` | `on` |
| `BaseModel::$scoped` | `BaseModel.php:57` | `false` |
| `ContentCacheTrait` TTL | `ContentCacheTrait.php:52` | `3600` sn |

## 5. Tuzaklar ve kurallar

1. **`BaseConfig::get()` özyineleme koruması globaldir.** Koruma
   tetiklendiğinde **önce log yazılır**, sonra `$default` döner
   (`BaseConfig.php:64-91`; B-32: düşen anahtar görünmezdi).
2. **`$fillable` ve `$guarded` birlikte tanımlıysa `$fillable` baskındır**;
   `$guarded` yalnız kalan kümede uygulanır (`MassAssignmentTrait.php:80-84`).
3. **`$fillable` tanımlı değilse süzme uygulanmaz** (geriye uyum kilidi).
4. **Yetkili yazma klondur ve loglar.** `authorizeFields()` ve
   `writeAsProject()` kaynak modeli değiştirmez, her çağrı
   `MASS_ASSIGNMENT_AUTHORIZED_WRITE` kaydı üretir
   (`MassAssignmentTrait.php:105-134, 256-282`).
5. **Kapsam genişletmez, yalnız daraltır** (`BaseModel.php:164-186`).
   `withProjectScope()` klon döner.
6. **`resolveScopeProjectKey()` boş bağlamda `null` döner**; `null` dönen yerde
   süzgeç **hiç uygulanmaz** — CLI'da `project_key=''` ile tüm tabloyu silme
   tuzağı böyle kapanır (`BaseModel.php:146-162`).
7. **`query()` kapsam dışı model için saatlik ölçüm satırı yazar** (yalnız
   ölçüm; hiçbir koşulu değiştirmez) (`QueryModelTrait.php:113-131`).
8. **JSON alanlarında `forceJsonFill()` ayrıştırır**; model sınıfı bu metodu
   sunmuyorsa builder satırları doğrudan özellik olarak atar
   (`../../Core/Database/Engine.md` §3.2).

## 6. Örnek (gerçek koddan)

```php
// Kapsam dışı (scoped=false) modelde elle süzgeç — eski yol korunur
$rows = $this->model('project.user')->where('project_key', $key)->get();

// Kapsamlı (scoped=true) modelde süzgeç otomatik; daraltmak için:
$rows = $this->model('project.page')->withProjectScope($key)->get();

// Korumalı alana yetkili yazma (klon + log)
$this->model('project.user')->authorizeFields(['role'])->update($id, ['role' => 'admin']);
```

## 7. İlgili belgeler

* [../README.md](README.md) — `BaseComponent`/`BaseService` kökü
* [../Database/Models.md](../Database/Models.md), [../Database/Engine.md](../Database/Engine.md)
* [Services.md](Services.md) — içerik servis trait'leri
* [../../kavramlar/03-veritabani-ve-kiracilik.md](../../kavramlar/03-veritabani-ve-kiracilik.md)