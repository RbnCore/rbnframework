# Core/Database — bağlantı yönetimi, sorgu motoru, model ve repository katmanı

> **Doğrulanan kod tabanı:** `1d89c431` (dal `feat/fw-license-master`) · **Tarih:** 2026-10-05 · **Yayın:** 0.9.4 = bu commit + sonrası; belge yalnız doğrulama anındaki kodu anlatır
> **Kaynak klasör:** `Core/Database/` — 74 `*.php`.
> **Envanter:** 74 dosyanın 74'ü aşağıda anlatıldı (alt dal belgelerinde dağınık).

## 1. Ne işe yarar, kim kullanır

Framework'ün veri katmanıdır. Üç katmanı vardır ve katmanlar arasına
taşmak **kuraldır**:

| Katman | Soyut sınıf | Görev |
|---|---|---|
| Bağlantı | `Database` (singleton) | `database_project` / `database_master` / `database_common` bağlantılarını yönetir, PDO verir, transaction açar |
| Sorgu | `QueryBuilder` (+ 7 query trait'i) | SQL üretimi; `Database` üzerinde **kendi bağlantısında** çalışır |
| Veri | `BaseModel` / `BaseRepository` | Sorgu builder'ı sarmalar; model satır, repository iş kuralıdır |

Anayasa §8 gereği **yalnız Repository katmanı doğrudan SQL yazabilir**;
model katmanı şemaya değil, hazır builder API'sine dayanır.

**Kimler çağırır:** `Core/Base/Data/BaseModel`, tüm `Core/Services` ve
`Bundles/*` repository/model sınıfları, CLI handler'ları (`rbn db:*`).

## 2. Klasör/dosya envanteri

Tam dosya listesi ve her dosyanın tek cümlelik görevi alt dal belgelerindedir:

| Alt dal belge | Kapsam | Dosya sayısı |
|---|---|---|
| [Engine.md](Engine.md) | `Database.php`, `Engine/` (Collection, Providers, Traits, Traits/Query) | 16 |
| [Models.md](Models.md) | `Models/` (kök + Common + Master + Project) | 34 |
| [Repositories.md](Repositories.md) | `Repositories/` (Common + Master + Project) | 20 |
| [Migrations.md](Migrations.md) | `Migrations/` (Master + Tenant) | 3 |
| `Services/DatabaseService.php` | (aşağıda, bu belge) | 1 |
| **Toplam** | | **74** |

Bu ana belgese ait tek dosya:

| Dosya | Görev | Önemli public yöntemler |
|---|---|---|
| `Services/DatabaseService.php` | Panelin/CLI'nin veritabanı yönetim yüzü: tablo listesi, satır gösterme, satır silme, optimize, collation dönüşümü, SQL dışa aktarım, temiz kurulum, admin seed. | `static get(): self`, `table(string $name): self`, `entire(): self`, `getDashboardInfo(): array`, `optimize(): bool`, `convert(string $collation='utf8mb4_unicode_ci'): array`, `cleanInstall(): bool`, `seedAdmin(): bool`, `executeQuery(string $sql): array`, `getTableContent(string $table,int $limit=100): array`, `getTables(): array`, `getRows(?string $table=null,int $limit=100): array`, `getTableMetadata(?string $table=null): array`, `deleteRow(?string $table=null,$id=null): bool`, `bulkDeleteRows(?string $table=null,array $ids=[]): bool`, `dropTable(?string $table=null): bool`, `changeTableCollation(?string $table=null,string $collation=...): bool`, `exportTableSql(?string $table=null,?array $ids=null): string`, `sanitizeTableName(string $table): string` (protected) |

## 3. Akış — bir model okumasından satıra

```
Controller/Service → $this->model('project.user')->where(...)->get()
 ├─ BaseComponent::__get('user'/'model') → component('model', …) keşif motoru   BaseComponent.php:194
 │   → Core/Base/Data/BaseModel örneği
 ├─ BaseModel::__construct() → BaseComponent → bootBaseContext()            BaseModel.php:210
 └─ QueryModelTrait::query()                                   QueryModelTrait.php:44
     ├─ logModelScopeVisibility()  (LOG-ONLY ölçüm)             :49
     ├─ new QueryBuilder($this->db, $this->table)              :67
     ├─ $builder->connection($this->connection)                :71-73
     ├─ $builder->setModelClass(static::class)                 :77-79
     └─ $scoped ise → where(project_key, aktif) veya whereIn(..., projectScopeIncludes)  :88-108
        (aktif anahtar boşsa kapsam UYGULANMAZ)
 └─ where/orderBy/limit... → get()
     └─ Query\ExecutionTrait::get()                            ExecutionTrait.php:17
         └─ onConnection(fn) → Database::connectionScoped($connectionName, …)  QueryBuilder.php:97
             └─ Database::query()/raw()/execute()              ExecutionTrait.php:46,131,151
                 └─ ConnectionTrait::getProvider($name) → MySqlProvider → getPdo()  MySqlProvider.php:60
```

Yazma yolu aynıdır; ayrıca `MassAssignmentTrait` süzgeci
(`create/update/save` üzerinden) devreye girer.

## 4. Yapılandırma / ayar anahtarları

| Anahtar / sabit | Yer | Not |
|---|---|---|
| `KNOWN_CONNECTIONS` | `ConnectionTrait.php:29` | `database_project`, `database_master`, `database_common` |
| `CONNECTION_ALIASES` | `ConnectionTrait.php:43-45` | `default → database_project` |
| `security.mass_assignment` | `MassAssignmentTrait.php:42` | `on|enforce` zorlar; diğer her şey `off` (varsayılan `on`) |
| `security.protected_field_log` | `MassAssignmentTrait.php:50` | `on` → korumalı alan denemesi loglanır |
| `security.model_not_scoped_log` | `QueryModelTrait.php:39` | `on` → kapsam dışı model görünürlük logu (saatlik) |
| `MASTER_DB_*` / `DB_*` ortam anahtarları | `MasterDbData::KEYS_MAP`, `ProjectDbData::KEYS_MAP` | Değerler `Secrets` üzerinden gelir |
| `MasterDbData::REQUIRED_TABLES` | `MasterDbData.php:54-60` | `developers, ip_whitelist, ip_blocks, projects, z_sys_heartbeats` |

## 5. Tuzaklar ve kurallar

1. **Geçersiz bağlantı adı istisna atar (fail-closed).** `MySqlProvider`'ın
   önceki "bilinmeyen her adı sessizce `database_project`'e düşür" davranışı
   kaldırıldı; artık `normalizeConnectionName()` `InvalidArgumentException`
   atar (`ConnectionTrait.php:68-83`). Geriye uyum yalnız `default` alias'ıyla.
2. **`default` ve `database_project` aynı sağlayıcıyı paylaşır** — aynı
   veritabanına iki PDO açılmaz (`ConnectionTrait.php:43-45`).
3. **Sorgu kendi bağlantısında çalışır, global durum kirlenmez.**
   `QueryBuilder::onConnection()` → `Database::connectionScoped()`
   (`QueryBuilder.php:97-100`, `ConnectionTrait.php:123-133`).
4. **Boş proje bağlamında kapsam uygulanmaz.** `$activeKey === ''` iken
   `where('project_key','')` yazmak tüm tabloyu sıfıra indirirdi; bu yüzden
   kapsam atlanır (`QueryModelTrait.php:90-92`).
5. **Kiraci izolasyonu varsayılanı KAPALI.** `BaseModel::$scoped = false`
   (`BaseModel.php:57`); her model kendi kararını dosyasında yazar
   (`@tenant-scope` etiketiyle). Açmak 7 veritabanında kırılma ölçülmüştür.
6. **PDO, ayar değiştiğinde düşürülür.** `getPdo()` her çağrıda `Config`'i
   okur; host/veritabanı/kullanıcı/**parola** farklıysa bağlantı yenilenir
   (`MySqlProvider.php:60-79`).
7. **CLI'da ölü bağlantı yoklanır.** 10 sn'den eskiyse `SELECT 1` atılır;
   `PDOException` **yetmez**, `Throwable` yakalanır (`MySqlProvider.php:81-95`).
8. **`SchemaDoctorModel` tablosuzdur.** `getTable(): string` yerine
   `getTableOrNull()` kullanılmalıdır (`strict_types` altında `null` dönmek
   `TypeError`'dir) (`SchemaDoctorModel.php:11-25`).
9. **İç içe transaction dış yüzeyi kullanmaz.** `Database::transaction()`
   zaten transaction içindeyse içeriği çalıştırır ve commit/rollback yapmaz
   (`Database.php:57-60`).
10. **Sıfırlama API'leri yalnız test içindir.** `BaseService::forgetInstance()`,
    `StorageContextTrait::forgetStorage()` üretimde çağrılmaz.

## 6. Örnek (gerçek koddan)

Repository katmanından okuma/yazma (`Repositories/Project/UserRepository.php`
imzası):

```php
$user = $this->repository('project.user')->findByIdentity('ornek@site.example');
$ok   = $this->repository('project.user')->updatePassword($id, $hash, 'user');
```

Model katmanında kiracı kapsamını daraltma (`QueryModelTrait.php:88-108`):

```php
$model = $this->model('project.user')->withProjectScope('proje-anahtari');
$rows  = $model->where('is_active', 1)->orderBy('id', 'DESC')->limit(20)->get();
```

## 7. İlgili belgeler

* [../Base/Data.md](../Base/Data.md) — `BaseModel` / `BaseRepository` ve tüm trait'ler
* [../System/Config.md](../System/Config.md) — bağlantı ayarlarının tek okuyucusu
* [../../kavramlar/03-veritabani-ve-kiracilik.md](../../kavramlar/03-veritabani-ve-kiracilik.md) — bağlantı aileleri ve kiracılık
* [../../acik-sorular.md](../../acik-sorular.md) §1.2 / §3 (heartbeat tablosu)