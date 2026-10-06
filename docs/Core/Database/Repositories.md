# Core/Database/Repositories — iş kuralı katmanı

> **Doğrulanan kod tabanı:** `1d89c431` (dal `feat/fw-license-master`) · **Tarih:** 2026-10-05 · **Yayın:** 0.9.4 = bu commit + sonrası; belge yalnız doğrulama anındaki kodu anlatır
> **Kaynak klasör:** `Core/Database/Repositories/` — 20 `*.php`.
> **Envanter:** 20 dosyanın 20'si aşağıda anlatıldı.

## 1. Ne işe yarar, kim kullanır

Anayusa §8'in uygulama yeridir: **yalnız repository doğrudan SQL yazar**
(`QueryBuilder`, `$this->db->raw()`). Model yalnız hazır API sunar; repository
iş kuralını, tenant anahtarını ve yazılabilir alan süzgecini içerir.
Her repository `BaseRepository`'den türer ve `targetModel` (veya
`model('…')` çağrısı) ile hedef tabloyu bildirir.

**Kimler çağırır:** `Core/Services` ve `Bundles/*` servis/controller'ları,
CLI handler'ları; repository'ler keşif motorundan `repository('<alias>')`
ile çözülür (`SystemPhysicalMapTrait`).

## 2. Dosya envanteri

### 2.1 `Common/` (4) — `database_common`

| Dosya | Hedef model | Görev / özel yöntemler |
|---|---|---|
| `ContactRepository.php` | `common.contactMessage` | İletişim mesajları ve çöp kutusu. `tenantKey(?string $projectKey=null): string` (protected), `getMessages(?string $type=null,?string $projectKey=null): array`, `getStats()`, `getDetailedStats()`, `markAsRead(int $id,?string=null): bool`, `moveTrash(int,?string): bool`, `restore(int,?string): bool`, `delete(int,?string): bool`, `emptyTrash(?string=null): int`, `getLatest(int $limit=5,?string=null): array` |
| `IpBlockRepository.php` | `common.ipBlock` | IP blok işlemleri. `getBlocks(): array`, `block(string $ip,string $reason,int $durationMinutes=1440): bool`, `unblock(int $id): bool`, `clearAll(): bool`, `getStats(): array` |
| `NotificationRepository.php` | `common.notification` | Bildirim listesi/okundu/istatistik. `tenantKey()`, `getNotifications(?string $type=null,?string=null): array`, `getStatusStats()`, `getDetailedStats()`, `markAsRead(int,?string): bool`, `delete(int,?string): bool`, `clearAllRead(?string=null): bool`, `getLatest(int $limit=5,?string=null): array` |
| `ShieldSettingsRepository.php` | `common.shieldSetting` | RbnShield ayarlarının tek normalize edicisi. `static normalizeSwitch(mixed $raw,bool $default=true): bool`, `static normalizeIpGuardMode(mixed $raw): string`, `static settingsCacheKey(?string $projectKey=null): string`, `forgetSettingsCache(?string=null): void`, `getSetting(string $key,mixed $default=null): mixed`, `getGroupSettings(string $groupKey): array`, `saveSetting(string $key,$value,string $groupKey='general',string $valueType='boolean',?string $projectKey=null): bool` |

### 2.2 `Master/` (6) — `database_master`

| Dosya | Hedef model | Görev / özel yöntemler |
|---|---|---|
| `CronJobsRepository.php` | `master.cronJob` | Zamanlayıcı işleri. `getDueJobFromDb(array $options=[]): ?array`, `getJobById(int): ?array`, `updateJobSchedule(int,array): bool`, `createJob(array): int\|false`, `getJobsByProject(string $projectKey): array`, `getAllJobs(): array`, `getScheduleSummaryData(string $projectKey): array`, `writeNextExpectedRunCache(): void` |
| `IpGuardRepository.php` | `master.ipBlock` (+ `common.ipBlock`, `master.ipWhitelist`) | IP koruma düzlemi. `fetch(array $options=[]): array`, `save(array): bool`, `blockIp(string $ip,string $reason,int $durationMinutes=1440,?int $projectId=null,string $countryCode='XX'): int`, `destroy(?int $id=null): bool`, `isWhitelisted(string $ip): bool`, `addToWhitelist(string $ip,string $label='',string $countryCode='XX'): int` |
| `MasterApplicationRepository.php` | `master.application` | Uygulama kaydı ve sürüm. `findByAppKey(string): ?array`, `list(): array`, `create(array): int`, `updateVersion(int,string $version,?string $url,?string $sha256): bool`, `satir(mixed): ?array`, `yazilabilir(array): array` (protected) |
| `MasterLicenceRepository.php` | `master.licence` | Lisans kaydı. `findByKey(string): ?array`, `findForSubject(string $type,int $id): ?array`, `create(array): int`, `updateStatus(int,string $status): bool`, `bindDevice(int,string $deviceHash): bool`, `isKeyUnique(string): bool`, `list(int $limit=100,int $offset=0): array`, `satir()`, `yazilabilir()` |
| `MasterProjectsRepository.php` | `master.projects` | Proje kimliği. `isKeyUnique(string $projectKey,?int $excludeId=null): bool`, `isLicenseKeyUnique(string,?int=null): bool`, `getProjectsByGroup(string $groupName): array`, `createProjectIdentity(array): int`, `updateProjectIdentity(int,array): bool`, `findByProjectKey(string): ?array`, `findVersionByProjectKey(string): ?string`, `updateProjectVersion(int,string $version): bool` |
| `MasterSettingsRepository.php` | `master.settings` | Master ayarları. `master(string $key,$default=null): ?string`, `getByCategory(string $category): array`, `isKeyUnique(string $settingKey,?int $excludeId=null): bool` |

### 2.3 `Project/` (10) — `database_project`

| Dosya | Hedef model | Görev / özel yöntemler |
|---|---|---|
| `ContentCategoryRepository.php` | `app.contentCategory` | Kategori sorgu/kaydetme/silme/sıra. `getCategories(array $options=[]): array\|null`, `saveCategory(array): array\|bool`, `deleteCategory(int): bool`, `bulkUpdateOrder(array $order): bool` |
| `ContentDraftRepository.php` | `app.contentDraft` | Taslak yönetimi. `getDrafts(array $options=[]): array`, `saveDraft(array)`, `destroyDraft(int): bool`, `bulkUpdateOrder(array): bool` |
| `CronLogRepository.php` | `project.cronLog` | Zamanlayıcı günlüğü. `logExecution(array): bool`, `getLogs(int $limit=100,?string $projectKey=null): array`, `getLog(int): ?array`, `deleteLog(int): bool`, `cleanupLogs(int $days=7): int` |
| `FaqRepository.php` | `project.faq` | SSS kaydı. `fetch(array $options=[]): array`, `save(array): bool\|int` |
| `SettingsApiRepository.php` | `project.settingsApi` | API anahtarı ayarları. `getApiKeys(?string $projectKey=null): array`, `getApiKey(string $keyName,$default=null,?string $projectKey=null): ?string`, `saveApiKey(string $keyName,string $keyValue,string $type='api',?string $settingLabel=null,?string $groupKey=null): bool` |
| `SettingsRepository.php` | `project.settings` (+ `project.settingsGroup`) | Ayar okuma/yazma. `fetch(array $options): array`, `updateSettings(array $settings,string $projectKey): bool`, `save(array): bool\|int`, `toggleStatus(int,string $field='is_active'): bool`, `destroy(int): bool` |
| `UserActivityRepository.php` | `project.userActivity` | Etkinlik günlüğü. `logFailedActivity(string $type,string $identity,string $reason='',string $accountType='user',?string $countryCode=null): bool`, `logSuccessfulActivity(string $type,int $userId,string $identity,string $accountType='user',?string $countryCode=null): bool`, `getActivities(array $filters=[]): array`, `stats(): array`, `hasUserActivities(): bool`, `clear(): bool` |
| `UserRepository.php` | `project.user` (+ `master.developer`, `project.userActivity`, `project.userSecurity`) | Kullanıcı ve kimlik işlemleri. `findByIdentity(string): mixed`, `findMasterDeveloper(string): mixed`, `setOnlineStatus(int,bool): bool`, `isActive(int): bool`, `getProfileData(?int $id=null): ?array`, `update(int,array): bool`, `updatePassword(int,string $password,string $role): bool`, `setRole(int,string $role): bool`, `updateEmail(int,string $email): bool`, `updateProfile(int,array): bool`, `store(array): bool`, `allUsers(array $filters=[]): array`, `stats(): array`, `getUser(int): array`, `destroy(int): bool` |
| `UserSecurityRepository.php` | `project.userSecurity` (+ `common.ipBlock`) | Kullanıcı güvenlik verisi + IP blok. `isIpBlocked(string $ip): ?array`, `blockIp(string $ip,string $reason,int $hours=1): bool`, `unblockIp(string $ip): bool`, `saveSecurityData(int $userId,array $data): bool`, `getSecurityData(int $userId): ?array` |
| `UserTokenRepository.php` | `project.userToken` | Tek kullanımlık jeton kasa. `issue(int $userId,string $purpose,int $ttlMinutes,?string $ip=null): string`, `consume(string $rawToken,string $purpose): array`, `peek(string $rawToken,string $purpose): ?array`, `revokePending(int $userId,string $purpose): int`, `purgeExpired(int $graceHours=72): int`, `sealToken(int $id): bool`, `sealRejectionReason(int $id): string` (protected) |

**Kapsama:** 20/20.

## 3. Akış

```
Service/Controller → $this->repository('project.user')
 └─ keşif motoru → RegistryMap 'repositories' kaydı → new UserRepository()  SystemPhysicalMapTrait.php:93+
     └─ BaseComponent::__construct → bootBaseContext()
 └─ repository->findByIdentity($identity)
     ├─ $this->model('project.user')   → UserSecurity/UserActivities vb. alt modellerle birlikte
     ├─ $model->query()               → kapsam süzgeci (bkz. ../Models.md §3)
     └─ $this->db->raw(...)            → YALNIZCA burada ham SQL (Anayusa §8)
```

## 4. Yapılandırma

| Öğe | Yer | Not |
|---|---|---|
| `targetModel` | repository dosyası | `ContentCategoryRepository`/`ContentDraftRepository` bu alanı kullanır; diğerleri `model('…')` çağırır |
| `shieldSetting` anahtar normalizasyonu | `ShieldSettingsRepository.php:91,124` | `normalizeSwitch()`, `normalizeIpGuardMode()` — tek merkez |
| `ip_guard_mode` | `normalizeIpGuardMode()` | Bu görevde **değiştirilmedi**; kural gereği dokunulmadı |
| `allow_query_token` vb. | `external-api.php` (proje ayarı) | `ApiGuard` okur, bu katman değil |

## 5. Tuzaklar ve kurallar

1. **Repository tek SQL yazar; model yazmaz.** Model katmanında `$db->raw()`
   çağrısı kural ihlalidir.
2. **`tenantKey()` fail-closed çalışır.** `ContactRepository` ve
   `NotificationRepository` tenant anahtarını kendi üretir; anahtar
   çözülemezse liste boş döner, tüm kayıtlar görünmez.
3. **Beyaz liste (`$fillable`) modelde, kara liste (`$guarded`) modelde**;
   repository `authorizeFields()` kullanarak korumalı alanı açar ve her çağrı
   loglanır (`../Base/Data.md`).
4. **`UserRepository` master tablosuna da yazar.** `findMasterDeveloper()`,
   `updatePassword()`, `setRole()` içinde `model('master.developer')` çağrısı
   vardır; master bağlantısı yanlış seçilirse kullanıcı yazımı yanlış
   veritabanına düşer (`UserRepository.php:42,137,158,191,283`).
5. **Jeton kasası tek kullanımlıktır.** `consume()` jetonu mühürler
   (`sealToken()`); `purgeExpired()` varsayılan 72 saat.
6. **`IpGuardRepository` üç tabloya birden dokunur** (`master.ipBlock`,
   `common.ipBlock`, `master.ipWhitelist`); bu yüzden `blockIp()` imzası
   `projectId` ve `countryCode` taşır.

## 6. Örnek (gerçek koddan)

```php
// Jeton üretip tüketme (UserTokenRepository.php:51, 99)
$token = $this->repository('project.userToken')->issue($userId, 'verify-email', 60, $ip);
$sonuc = $this->repository('project.userToken')->consume($rawToken, 'verify-email');
```

## 7. İlgili belgeler

* [../README.md](README.md), [Models.md](Models.md), [Engine.md](Engine.md)
* [../Base/Data.md](../Base/Data.md) — `BaseRepository` ve içerik trait'leri
* [../../kavramlar/03-veritabani-ve-kiracilik.md](../../kavramlar/03-veritabani-ve-kiracilik.md)
* [../../acik-sorular.md](../../acik-sorular.md) §2.2 (`TENANT_TABLES`)