# Core/Database/Models — tablo sınıfları (Master / Common / Project)

> **Doğrulanan kod tabanı:** `1d89c431` (dal `feat/fw-license-master`) · **Tarih:** 2026-10-05 · **Yayın:** 0.9.3 = bu commit + sonrası; belge yalnız doğrulama anındaki kodu anlatır
> **Kaynak klasör:** `Core/Database/Models/` — 34 `*.php`.
> **Envanter:** 34 dosyanın 34'ü aşağıda anlatıldı.

## 1. Ne işe yarar, kim kullanır

Her model bir tabloyu temsil eder; `BaseModel`'dan gelen kapsam, beyaz liste,
zaman damgası ve ilişki davranışlarını **konkrete bağlar** (`$table`,
`$connection`, `$scoped`, `$timestamps`, `$fillable`, `$guarded`). Model
SQL yazmaz; yalnız `BaseModel`/trait API'sini kullanır (Anayasa §8).

**Kimler çağırır:** `Core/Database/Repositories/*`, doğrudan
`$this->model('project.user')` çağıran controller'lar.

## 2. Dosya envanteri

### 2.1 Kök

| Dosya | Tablo | Bağlantı | `$scoped` | Görev / özel yöntemler |
|---|---|---|---|---|
| `SchemaDoctorModel.php` | **tablosuz** (`$table = null`) | `database_project` | — | Sistem geneli şema bakımı. `getTableStatus(): array`, `getCurrentDbName(): string`, `getTablePrimaryKey(string): string`, `getTableSchema(string): string`, `optimize(string): bool`, `convertCollation(string,string): bool`, `dropTable(string): bool`, `truncateTable(string): bool`, `executeRaw(string): array`, `getRows(string,int $limit=100): array`, `deleteRows(string,array $ids,?string $pk=null): bool`, `getTableData(string,?array $ids=null): array`, `getPdo()`. `getTable(): string` yerine `getTableOrNull()` kullanılmalıdır. |

### 2.2 `Common/` (6) — `database_common`

| Dosya | Tablo | `$scoped` | `$timestamps` | Görev / özel yöntemler |
|---|---|---|---|---|
| `CmLogAiUsagesModel.php` | `cm_log_ai_usages` | true | true | AI kullanım sayaçları |
| `CmLogContactsModel.php` | `cm_log_contacts` | true | false | İletişim formu mesajları |
| `CmLogNotificationsModel.php` | `cm_log_notifications` | true | false | Sistem bildirimleri |
| `CmSysIpBlocksModel.php` | `cm_sys_ip_blocks` | true | false | IP blok kayıtları; `projectScopeIncludes = ['GLOBAL']` |
| `CmSysRateLimitsModel.php` | `cm_sys_rate_limits` | true | false | Hız sınırı sayaçları: `recordAttempt(string $identifier,string $action,string $ip,string $userAgent,array $metadata=[],string $countryCode='XX'): bool`, `getAttemptsCount(string,string,int $windowStart): int`, `getLastAttempt(string,string,int): ?array`, `cleanExpired(string,string,int $window): int` |
| `CmSysSettingsShieldModel.php` | `cm_sys_settings_shield` | true | true | RbnShield ayarları |

### 2.3 `Master/` (9) — `database_master`

| Dosya | Tablo | `$scoped` | `$guarded` | Görev / özel yöntemler |
|---|---|---|---|---|
| `MasterApplicationModel.php` | `applications` | false | — | Uygulama kaydı + sürüm |
| `MasterCronJobsModel.php` | `cron_jobs` | false | — | Zamanlayıcı iş tanımları |
| `MasterDevelopersModel.php` | `developers` | false | `role, password` | Master geliştirici hesapları |
| `MasterIpBlocksModel.php` | `ip_blocks` | false | — | IP blok kayıtları |
| `MasterIpWhitelistModel.php` | `ip_whitelist` | false | — | IP beyaz liste |
| `MasterLicenceModel.php` | `licences` | false | `status` | Merkezi lisans kaydı |
| `MasterProjectsModel.php` | `projects` | false | `license_key, status` | Proje kimliği ve alan adı |
| `MasterRbnHeartbeatsModel.php` | `rbn_heartbeats` | false | — | Sistem telemetri nabzı. `$primaryKey = 'heartbeat_key'`, `incrementing = false`, `keyType = 'string'`, `timestamps = false` |
| `MasterSettingsModel.php` | `settings` | false | — | Master ayarları |

### 2.4 `Project/` (18) — `database_project`

| Dosya | Tablo | `$scoped` | `$timestamps` | `$guarded` / `$fillable` | Görev / özel yöntemler |
|---|---|---|---|---|---|
| `ContentCategoryModel.php` | `app_content_categories` | true | true | `$fillable`: `project_key, type, name, slug, icon, order_num, description, is_active, …` | İçerik kategorileri |
| `ContentDraftModel.php` | `app_content_drafts` | true | true | — | İçerik taslakları |
| `CronLogsModel.php` | `z_log_crons` | true | true | — | Proje zamanlayıcı günlükleri |
| `FaqsModel.php` | `z_app_faqs` | true | true | — | SSS kayıtları; `getFaqs(array $filters=[]): array` |
| `FrontendMenusModel.php` | `z_app_menus_frontend` | true | true | — | Frontend menü tanımları |
| `PagesModel.php` | `z_app_pages` | true | true | — | Statik sayfalar; `getPages(array $filters=[]): array` |
| `RbnHeartbeatsModel.php` | **dinamik** (`z_sys_heartbeats` varsayılan) | false | false | — | Telemetri nabzı; `DEFAULT_TABLE`, `KEY_COLUMN='heartbeat_key'`, `TABLE_CANDIDATES`. `resolveTableName(): string`, `isAvailable(): bool`, `probeTable(string): bool`, `beat(string $anahtar,array $payload=[]): bool`, `static onbellegiTemizle(): void`, `guard(): bool`; CRUD yolları `guard()` ile korunur |
| `RssBlacklistModel.php` | `app_rss_blacklist` | true | false | — | RSS kara liste |
| `RssSourceModel.php` | `app_rss_sources` | true | true | — | RSS kaynakları |
| `SettingsApiModel.php` | `z_settings_api` | true | true | `projectScopeIncludes = []` | API anahtarı ayarları |
| `SettingsGroupModel.php` | `z_setting_groups` | false | true | — | Ayar grupları |
| `SettingsModel.php` | `z_settings` | true | true | — | Proje ayarları |
| `SidebarCategoriesModel.php` | `z_bs_sidebar_categories` | true | true | — | Panel kenar çubuğu kategorileri |
| `SidebarMenusModel.php` | `z_bs_sidebar_menus` | true | true | — | Panel kenar çubuğu menüleri |
| `UserActivitiesModel.php` | `z_users_activities` | false | true | — | Kullanıcı etkinlik günlüğü |
| `UserSecurityModel.php` | `z_users_security` | false | — | `guarded: user_id` | Kullanıcı güvenlik verileri |
| `UsersModel.php` | `z_users` | false | true | `guarded: role, email, username` | Kullanıcı hesapları |
| `UserTokenModel.php` | `z_user_tokens` | false | false | — | Tek kullanımlık jeton kasası (e-posta doğrulama, parola sıfırlama) |

**Kapsama:** 34/34.

## 3. Akış — modelin kuruluşu ve kapsam

```
$this->model('project.user')  → keşif → RegistryMap: 'project.user' => Models\Project\UsersModel  SystemPhysicalMapTrait.php:58
 └─ new UsersModel()
     └─ BaseModel::__construct()                       BaseModel.php:210
         ├─ $connection = 'database_project'
         └─ parent::__construct() → BaseComponent → bootConcernsContext()/bootBaseContext()
 └─ ->where(...)
     └─ QueryModelTrait::query()                       QueryModelTrait.php:44
         ├─ logModelScopeVisibility() (kapsam dışıysa saatlik ölçüm satırı)  :49
         ├─ new QueryBuilder($this->db, $this->table)  :67
         ├─ $builder->connection($this->connection)    :71-73
         └─ $scoped ise: where(project_key, aktif) ya da whereIn(project_key, [aktif, …projectScopeIncludes])  :88-108
```

Kapsam anahtarı çözümü `BaseModel::resolveScopeProjectKey()` →
`getActiveProjectKey()`; `withProjectScope()/withoutProjectScope()` klon
üretir (`BaseModel.php:155-208`).

## 4. Yapılandırma

| Öğe | Yer | Not |
|---|---|---|
| `security.mass_assignment` | `Base/Data/Traits/Model/Engine/MassAssignmentTrait.php:42` | `on` ise `$fillable` beyaz liste, `$guarded` kara liste |
| `security.model_not_scoped_log` | `.../QueryModelTrait.php:39` | `on` → kapsam dışı model logu |
| `$projectScopeIncludes` | model dosyası | Örn. `CmSysIpBlocksModel`: `['GLOBAL']` |
| `@tenant-scope` etiketi | model docblock'ı | Her modelin kararını yazdığı beyan zorunluluğu |

## 5. Tuzaklar ve kurallar

1. **Varsayılan kapsam KAPALI.** `BaseModel::$scoped = false`
   (`Base/Data/BaseModel.php:57`); her model kararını kendi dosyasında yazar.
2. **Tipli alan yeniden bildirilemez.** `BaseModel::$primaryKey` tipsiz
   bildirilmiştir; alt sınıf `string` tipiyle yeniden bildirirse PHP **fatal**
   verir ve sınıf hiç yüklenmez (`MasterRbnHeartbeatsModel.php:37-51`).
3. **`$guarded` alanları doğrudan yazılamaz.** Yetki gerekiyorsa
   `authorizeFields([...])` klonu kullanılır ve her çağrı
   `MASS_ASSIGNMENT_AUTHORIZED_WRITE` olarak loglanır
   (`MassAssignmentTrait.php:105-134`).
4. **`$fillable` tanımlı değilse süzme uygulanmaz** (geriye uyum kilidi).
5. **Boş proje bağlamında kapsam uygulanmaz** (bkz. `Engine.md`).
6. **Heartbeat modeli şema oluşturmaz.** Tablo yoksa tüm veri yolları güvenli
   varsayılana döner (`RbnHeartbeatsModel.php:15-17, 227-236`).
7. **`SchemaDoctorModel` tablosuzdur**; `getTable(): string` yerine
   `getTableOrNull()`.

## 6. Örnek (gerçek koddan)

```php
// Kapsamlı (scoped = true) model: proje süzgeci builder'a otomatik eklenir
$pages = $this->model('project.page')->where('is_active', 1)->get();

// Kapsam dışı (scoped = false) modelde süzgeç KENDİN yazılır
$all = $this->model('master.projects')->where('is_active', 1)->get();

// Korumalı alana yetkili yazma (klon döner, loglar)
$this->model('project.user')->authorizeFields(['role'])->update($id, ['role' => 'admin']);
```

## 7. İlgili belgeler

* [../README.md](README.md), [../Engine.md](Engine.md), [Repositories.md](Repositories.md)
* [../Base/Data.md](../Base/Data.md) — `BaseModel` ve model trait'leri
* [../../kavramlar/03-veritabani-ve-kiracilik.md](../../kavramlar/03-veritabani-ve-kiracilik.md)
* [../../acik-sorular.md](../../acik-sorular.md) §1.2 — `MasterRbnHeartbeatsModel` tablo adı