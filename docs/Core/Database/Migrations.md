# Core/Database/Migrations — master ve kiraci şema geçişleri

> **Doğrulanan kod tabanı:** `1d89c431` (dal `feat/fw-license-master`) · **Tarih:** 2026-10-05 · **Yayın:** 0.9.4 = bu commit + sonrası; belge yalnız doğrulama anındaki kodu anlatır
> **Kaynak klasör:** `Core/Database/Migrations/` — 3 `*.php` + 1 `*.sql`.
> **Envanter:** 3 php dosyasının 3'ü anlatıldı; `111-z_user_tokens.sql` aşağıda not edilmiştir.

## 1. Ne işe yarar, kim kullanır

Master veritabanında merkezi lisans/uygulama tablolarını kuran migration
(`Master/`) ve kiraci (proje) veritabanlarında `project_key` kolonu ile
benzersiz indeks ekleyen/geri alan migration (`Tenant/`). İkisi de
**idempotent** ve geri alınabilir çalışır; `Tenant` sınıfı plan/uygula/geri al
üçlüsünü ayırır.

**Kimler çağırır:** CLI migration komutları (`rbn migrate:*`, `rbn tenant:plan`,
`rbn tenant:apply`, `rbn tenant:revert`) ve kurulum sihirbazı.

## 2. Dosya envanteri

| Dosya | Görev | Önemli public yöntemler |
|---|---|---|
| `Master/CreateLicenceAndApplicationTables.php` | `applications` ve `licences` tablolarını oluşturur, mevcut proje lisanslarını merkezi tabloya kopyalar. | `const NAME`, `up(): void`, `down(): void`, `copyProjectLicences(): void`, `applicationsDdl(): string`, `licencesDdl(): string` (protected) |
| `Master/NormalizeProjectVersions.php` | `projects.version` değerlerini tek biçime indirger; yedek tablo yazar ve değişen satır sayısını raporlar. | `const …` (`BACKUP_TABLE`), `up(): void`, `down(): void`, `static rapor(): array` |
| `Tenant/TenantKeyMigration.php` | Kiraci kolonu (`project_key`) + benzersizlik indeksini ölçer, ekler, geri alır. `final`. | `__construct(private readonly string $column = ProjectDbData::TENANT_COLUMN, private readonly string $columnType = ProjectDbData::TENANT_COLUMN_TYPE)`, `columnDefinition(): string`, `plan(PDO $pdo,string $database,array $tables): array`, `apply(PDO $pdo,string $database,array $tables,bool $dryRun=false): array`, `revert(PDO $pdo,string $database,array $tables): array`, `tableExists/columnExists/columnState/indexExists/indexNames/uniqueIndexMap/hasUniqueIndex/duplicateGroupCount`: (…), `applyUnique(...)`, `revertUnique(...)`, `uniqueIndexName(string $table,string $slugColumn): string`, `indexName(string $table): string` |

> **SQL dosyası:** `111-z_user_tokens.sql` — `z_user_tokens` tablosunun DDL'i
> (her proje veritabanına ayrı uygulanır). PHP sınıfı yoktur; migration
> motoru `.sql` dosyalarını sırayla çalıştırır.

**Kapsama:** 3/3 php.

## 3. Akış

```
rbn tenant:plan  → TenantKeyMigration::plan($pdo,$db,TENANT_TABLES)
   └─ her tablo için: tableExists() + columnState()   (yalnız SELECT)   :91-105

rbn tenant:apply → apply($pdo,$db,$tables, dryRun)
   ├─ tablo yoksa skipped[] (uygulama sessizce atlanır)  :123-126
   ├─ kolon yoksa  → ALTER TABLE … ADD COLUMN columnDefinition()  :130-135
   ├─ kolon var    → duplicateGroupCount() ile yinelenen kayıt ölçülür
   └─ applyUnique() → UNIQUE INDEX ekler (dryRun'da yalnız SQL üretilir)  :369-…

rbn tenant:revert → revert($pdo,$db,$tables)
   ├─ kolonu kaldırır
   └─ revertUnique() → benzersiz indeksleri kaldırır
```

`CreateLicenceAndApplicationTables::up()` sırası:
`applications` DDL → `licences` DDL → `copyProjectLicences()`
(`CreateLicenceAndApplicationTables.php:45-50`).

## 4. Yapılandırma

| Sabit | Yer | Not |
|---|---|---|
| `NAME` | `CreateLicenceAndApplicationTables.php:43` | `2026_10_02_000001_create_licence_and_application_tables` |
| `TENANT_COLUMN` / `TENANT_COLUMN_TYPE` / `TENANT_INDEX_PREFIX` | `ProjectDbData` | `TenantKeyMigration` varsayılan olarak bunları kullanır (`TenantKeyMigration.php:68-70`) |
| `TENANT_TABLES` | `ProjectDbData` | `tenant:plan/apply` yalnız bu listedeki tabloları işler (bkz. [../../acik-sorular.md](../../acik-sorular.md) §2.2) |

## 5. Tuzaklar ve kurallar

1. **`down()` yalnız kendi yarattığı tabloyu düşürür.** `projects` tablosuna
   dokunulmaz (`CreateLicenceAndApplicationTables.php:52-57`).
2. **Anahtar tahmini yapılmaz.** Proje lisansları `LIFETIME` + `active` +
   süresiz olarak kopyalanır; boş/NULL anahtar atlanır (`:62-82`).
3. **`dryRun` yalnız SQL üretir**, hiçbir şey çalıştırmaz
   (`TenantKeyMigration.php:113`).
4. **Tablo adı güvenli karakterlere indirgenir** (`guvenliTabloAdi()`,
   `RbnHeartbeatsModel.php:145-149` benzeri desen) ve `ref()` ile
   kırpılır — SQL enjeksiyonu yüzeyi kapatılmıştır.
5. **`asw_*` tablolarına dokunulmaz** (gece görevi kuralı; migration da bu
   tabloları `TENANT_TABLES` listesinden almaz).

## 6. Örnek (gerçek koddan)

```php
// Plan çıktısı biçimi (TenantKeyMigration.php:104)
['schema' => 'db_adi', 'items' => [
    ['table' => 'z_users', 'exists' => true,  'column' => ['name' => 'project_key', 'exists' => true]],
]];
```

## 7. İlgili belgeler

* [../README.md](README.md), [Models.md](Models.md)
* [../../kavramlar/03-veritabani-ve-kiracilik.md](../../kavramlar/03-veritabani-ve-kiracilik.md) — migration ve tenant sözleşmesi
* [../System/Config.md](../System/Config.md) — `ProjectDbData::TENANT_*` sabitleri
* [../../acik-sorular.md](../../acik-sorular.md) §2.2