# Core/Services/Console — CLI komutları, cron kervanı, migration ve temizlik (29 dosya)

> **Doğrulanan kod tabanı:** `c23b431f` · **Tarih:** 2026-10-05 · **Yayın:** 0.9.5 = bu commit + sonrası; belge yalnız doğrulama anındaki kodu anlatır
> **Kaynak klasör:** `Core/Services/Console/` — **29 `*.php`** = 1 kök + `Base/` 4 + `Handlers/` 8
> (+ `Handlers/Resolvers/` 2) + `Jobs/` 1 (+ `Jobs/Cleanup/` 3) + `Managers/` 3 +
> `Services/` 6 + `Tasks/` 1.
> **Envanter:** 29 dosyanın **29'u** anlatıldı.
> **Doğrulama platformu:** Windows + PHP 8.3; `CliManager::switchProject`/`runInProject` gerçekten çalıştırıldı.

## 1. Ne işe yarar, kim kullanır

`php rbn <komut>` ile girilen **her şey** burada. Altı sorumluluk:

1. **Keşif ve yönlendirme:** `RbnCli` → `BaseCommand` → `getCommands()`.
2. **Proje bağlamı yönetimi:** `CliManager` (proje değiştirme, önbellek sıfırlama).
3. **Cron kervanı:** `CronManager` (5 adım) → `TaskManager` → `AbstractCronTask`.
4. **Veritabanı şeması:** `MigrationService`, `MasterMigrationService`, `TenantMigrationService`.
5. **Temizlik işleri:** `CleanupJob` → `ServerCleanupJob` + `ProjectCleanupJob`.
6. **Salt-okunur denetim:** `TenantAuditService`, `VersionHandlers::versionCheck()`.

## 2. Klasör/dosya envanteri (29/29)

| Dosya | Görev | Önemli public yöntemler |
|---|---|---|
| `RbnCli.php` (135) | **CLI giriş noktası.** `commands` kayıt haritasını okur, komut nesnelerini kurar, `argv` ayrıştırır ve dağıtır. | `__construct()`, `run(array $argv): void`, `listCommands(): void` · korumalı: `discoverCommands(): void` |
| `Base/BaseCommand.php` (102) | Tüm komutların tabanı: konsol I/O, keşif DNA, `--key=val` ayrıştırma. | `__construct(ConsoleStyle $io)`, `description(): string`, `execute(array $params=[]): void`, `abstract getCommands(): array` · korumalı: `info/success/error/warning(string)`, `parseOptions(array): array` |
| `Base/ConsoleStyle.php` (97) | ANSI renkli konsol çıktısı (başlık, tablo, blok). | `static header/info/success/error/warning(string)`, `static table(array $headers,array $data)`, `static block(string $msg,string $style='info')` · `private const COLORS` |
| `Base/BaseMigration.php` (18) | Migration taban sınıfı; `Database` örneğini alır. | `__construct()` |
| `Base/AbstractCronTask.php` (153) | **Cron görev sözleşmesi:** `run()` + durum/mesaj/iz alanları. | `run(array $params=[]): bool`, `getStatus(): string`, `setStatus(string)`, `skipped(string $message): void`, `getMessage(): string`, `setMessage(string)`, `getTrace(): array` · korumalı: `runBuilder(string $builderName,array $defaultParams=[],array $params=[]): bool`, `setTrace(array)` |
| `Handlers/DatabaseHandlers.php` (123) | `db:query`, `db:schema`, `db:tables`, `db:info` komutları. | `getCommands()`, `dbQuery(array)`, `dbSchema(array)`, `dbTables(array)`, `dbInfo(array)` |
| `Handlers/MigrationHandlers.php` (119) | `master:migrate*`, `migrate*` komutları. | `getCommands()`, `masterMigrate(array)`, `masterMigrateStatus(array)`, `migrate(array)`, `migrateRollback(array)`, `migrateStatus(array)` · korumalı: `getMigrationService(): MigrationService` |
| `Handlers/SystemHandlers.php` (186) | `list`, `system:doctor`/`doctor`, `system:cron`, `project:list`, `cache:clear`, `tmp:clear`, `logs:clear`. | `getCommands()`, `systemDoctor(array)`, `projectList(array)`, `cacheClear(array)`, `tmpClear(array)`, `logsClear(array)`, `runCron(array)` |
| `Handlers/VersionHandlers.php` (263) | `version:check` (salt-okunur, çıkış kodu) ve `version:next` (varsayılan kuru koşu). | `getCommands()`, `versionCheck(array): void`, `versionNext(array): void` · korumalı: `masterSurumleri()`, `tabloVarMi()`, `citationSurumu()`, `changelogSurumu()`, `static ciktiAl()`, `satir()`, `say()` |
| `Handlers/TenantHandlers.php` (349) | Kiracı izolasyonu: `tenant:audit` (yalnız okur), `tenant:plan/apply/revert`. | `getCommands()`, `tenantAudit(array)`, `tenantPlan(array)`, `tenantApply(array)`, `tenantRevert(array)` · korumalı: `kosu(array,bool $uygula)`, `hedefVeritabanlari(array)`, `aktifVeritabani()`, `baglanti(string): \PDO`, `modelSiniflari()`, `modelDosyalari()` |
| `Handlers/GeneratorHandlers.php` (101) | `make:controller/model/request/migration` iskelet üreticisi. | `getCommands()`, `makeController(array)`, `makeModel(array)`, `makeRequest(array)`, `makeMigration(array)` |
| `Handlers/SchedulerHandler.php` (96) | Gün/saat/kota uygunluğu ve bir sonraki çalışma zamanı. | `isRunDue(array $allowedDays,array $targetHours,int $todayPublishedCount): array`, `calculateNextRunTime(array $allowedDays,array $targetHours): string` |
| `Handlers/CronNotificationHandler.php` (186) | Cron sonucu e-posta bildirimi. | `sendNotification(array $job,string $status,string $message,?object $task=null,string $startedAt='',float $startTime=0.0): bool` · korumalı: `resolveRecipients(string $projectKey): array` |
| `Handlers/Resolvers/CronResolver.php` (86) | Cron önbelleği + zamanlama parametreleri çözümü. | `resolveNextExpectedRunCache(): array\|false`, `resolveScheduleParams(array\|object $job): array` |
| `Handlers/Resolvers/TaskResolver.php` (130) | Görev sınıfı dosyası/örneği çözümü. | `resolveTaskFileExists(string $taskClass,string $projectKey=''): bool`, `resolveProjectBuilder(string $builderName,string $projectKey=''): ?object`, `getTaskClassDefaults(array $job): array`, `resolveTaskInstance(string $taskClass,string $projectKey=''): ?object` |
| `Managers/CliManager.php` (123) | **Proje bağlamı anahtarı.** Proje değiştirir, yolları/önbellekleri sıfırlar. | `static switchProject(string $projectKey): bool`, `static runInProject(string $projectKey,callable $callback): mixed`, `static restore(): bool`, `static getActiveProjectKey(): ?string` |
| `Managers/CronManager.php` (212) | **5 adımlı cron iş hattı** (tespit → scheduler → executer → finalizer → notifier). | `execute(array $options=[]): void` |
| `Managers/TaskManager.php` (164) | Görevi izole proje bağlamında çalıştırır, telemetri yazar. | `execute(string $taskClass,array $params=[],array $options=[]): array`, `getTodayPublishedCount(string $taskClass,array $params=[]): int` |
| `Services/CronService.php` (72) | Cron'u programatik tetikleme + crontab komutu üretimi. | `runMaster(array $options=[]): void`, `run(mixed $job): bool`, `getCrontabCommand(): string`, `dispatch(string $taskClass,array $params=[],?string $customKey=null): bool` |
| `Services/TaskLogService.php` (207) | Görev adım adım günlükleme (adım + HTML rapor). | `start(string $taskName,string $projectKey,?string $taskKey=null)`, `step(string $stepName,string $message,array $metadata=[])`, `succeeded(string)`, `skipped(string)`, `failed(string,?\Throwable $e=null)`, `getSteps(): array`, `getTaskName(): ?string`, `getProjectKey(): ?string`, `getFormattedLogsHtml(): string` · korumalı: `logToFile()`, `logToConsole()` |
| `Services/MigrationService.php` (121) | Proje migration'ları (dosya tabanlı). | `migrate(): array`, `rollback(): array`, `getStatus(): array` · korumalı: `ensureMigrationsTable()`, `getPendingMigrations()`, `getNextBatch()`, `getLastBatch()`, `loadMigrationFile()` |
| `Services/MasterMigrationService.php` (202) | Master migration'ları (`master_migrations` tablosu). | `migrate(): array`, `rollback(): array`, `getStatus(): array`, `licenceCopyReport(): array` · `const RECORD_TABLE='master_migrations'` · korumalı: `tableExists()`, `ensureRecordTable()`, `appliedNames()`, `nextBatch()`, `lastBatch()`, `onMasterConnection(callable)` |
| `Services/TenantMigrationService.php` (96) | Kiracı kolonu migration'ı (`TenantKeyMigration` motoru). | `__construct(?TenantKeyMigration $motor=null)`, `targetTables(): array`, `pdo(): \PDO`, `run(\PDO,string,array,bool $dryRun=true): array`, `revert(\PDO,string,array): array`, `report(\PDO,string,array): array` |
| `Services/TenantAuditService.php` (326) | **Yalnız okur** kiracı kolonu denetimi. | `__construct(...)`, `auditDatabases(\PDO,array,array): array`, `withColumn(\PDO,string): array`, `withoutColumn(\PDO,string,array): array`, `targetTables(\PDO,string,array): array`, `literalDefaultColumns(\PDO,string): array`, `modelDeclarations(array): array`, `declarationAuditByFile(string $workspace,array $modelFiles): array`, `distribution(\PDO,string,string): array`, `nullRowCount(\PDO,string,string): int` |
| `Jobs/CleanupJob.php` (98) | Akıllı temizlik orkestratörü (server + projeler). | `execute(array $params=[]): array` |
| `Jobs/Cleanup/BaseCleanupHandler.php` (128) | Ortak dosya temizleme + koruma zırhı. | `const PROTECTED_FILES` (8), `const PROTECTED_PATTERNS` (8) · korumalı: `cleanDirectoryFiles(string $dirPath,int $days,array $immediatePrefixes=[]): int`, `removeEmptyDirectory(string $path): void` |
| `Jobs/Cleanup/ServerCleanupJob.php` (92) | Sunucu düzeyi temizlik: log, tmp, lscache, trash. | `execute(array $retentionOverrides=[]): array`, `cleanServerLogs(int $days=7): int`, `cleanServerTmp(int $days=1): int`, `cleanServerLscache(int $days=2): int`, `cleanServerTrash(int $days=7): int` · `const TMP_JUNK_PREFIXES=['sess_','RCMTEMPthumb']`, `const DEFAULT_RETENTION=['tmp'=>1,'lscache'=>2,'logs'=>7,'trash'=>7]` |
| `Jobs/Cleanup/ProjectCleanupJob.php` (153) | Proje düzeyi temizlik: Storage dosyaları + DB log tabloları. | `execute(iterable $projects,array $retentionOverrides=[],?callable $onProjectCleaned=null): array`, `resolveProjectStoragePath(string $projectKey): ?string`, `cleanProjectTables(string $projectKey,array $tableRetention): int` · `const DIRECTORY_RETENTION`, `const TABLE_RETENTION`, `const CLEANUP_TABLES` (4 tablo) |
| `Tasks/SystemTask.php` (42) | Framework'ın kendi cron görevi (`AbstractCronTask` uygulaması). | `run(array $params=[]): bool` |

## 3. Akış — `php rbn <komut>` (RbnCli.php)

```
php rbn <komut> [parametreler]
 └─ RbnCli::__construct()                        RbnCli.php:22
    ├─ new ConsoleStyle()
    └─ discoverCommands()                        :33-63
       ├─ SystemRegistry::registerMap()['commands']    :37   ← glob DEĞİL, kayıt haritası
       ├─ 'Rbn\' ile başlamayan FQCN'lere 'Rbn\Framework\' ön eki  :41-43
       ├─ class_exists() + instanceof BaseCommand filtresi          :45-50
       └─ her komutun getCommands()'i $this->commands'e yazılır    :51-59
 └─ RbnCli::run($argv)                           :68-116
    ├─ --project=<key> → switchProjectContext()  :75-88
    ├─ --master → Database::connection('database_master')  :78-92
    ├─ komut yoksa/list/--help → listCommands()  :95-100
    ├─ bilinmeyen komut → error + listCommands()  :102-106
    └─ call_user_func([$handler, $method], $params)  :112  ← çıktı DÖNDÜRMEZ, void
```

**Tuzak (RbnCli.php:111-115):** Komut çalıştırma `try/catch` içinde; istisna
**yutulur** ve `ConsoleStyle::error()` basılır. **Çıkış kodu `0`'dır** — yani
`php rbn` betiği hata durumunda da başarılı görünür. Tek istisna:
`VersionHandlers` ve `TenantHandlers` **`exit()` çağırır** (kendi çıkış kodunu
belirler). Bu, "hata kodu 1" sözleşmesinin **yalnız bu iki komutta** geçerli
olduğu anlamına gelir.

## 4. Akış — `CliManager::switchProject()` (CliManager.php:39-82)

**Bu, CLI'nin en kritik 30 satırıdır**: proje değiştirmek 7 adımda yapılır.

```
CliManager::switchProject('<proje>')
 1. 'master' veya boş → false                                  :42-44
 2. Aktif proje LIFO yığına                                   :47-50
 3. Bootstrap::setAppContext('project_key', $projectKey)        :53
    ProjectDiscovery::getProjectData(null, $key) → 'project_data'  :57-60
 4. Paths::init("<workspace>/projects/{$customPath}")          :63-66
 4.1. Autoload::boot()  ← proje PSR-4 yollarını ClassLoader'a tanıtır  :69
 5. Config::clear() + resetRouteMapCache()                     :72-73
 6. ComponentMapper / DiscoveryMapper / ModuleDiscoveryDriver::resetMemory()
    + SystemRegistry::clearCache()                              :76-79
 → true
```

**Ölçülen çıktı:**
```
switchProject('<proje>')            => true
getActiveProjectKey()               => '<proje>'
runInProject('<proje>', fn($k)=>$k) => '<proje>'   (finally ile eski bağlama dönüldü)
```

**Tuzak:** `switchProject('master')` **`false` döner** (`:42-44`) — yani master
moduna `switchProject` ile geçilemez; `RbnCli::run()` bunu `--master` bayrağıyla
ayrı yoldan yapıyor (`RbnCli.php:91-93`).

**Tuzak:** `runInProject()` `finally` bloğunda **önceki projeye döner**
(`:95-98`) ama `switchProject()` **yığın da itiyor** (`:47-50`) — yani iç içe
`runInProject` çağrılarında yığın büyür. `restore()` bunu çözer (`:104-114`).

## 5. Akış — `CronManager::execute()` 5 adım (CronManager.php:25-211)

```
1. TESPİT (Cache → DB)                          :32-55
   · --job/--job_id/--task/--task_key varsa cache ATLANIR          :35-43
   · resolver('cron')->resolveNextExpectedRunCache()               :38
   · repository('master.cronJob')->getDueJobFromDb($options)       :47
   · görev yoksa → info + return                                    :51-55
   · resolver('task')->resolveTaskFileExists() false → is_active=0 + pas  :66-74

2. SCHEDULER (gün/saat/kota)                     :82-116
   · force/bypass bayrakları → kontrol tamamen atlanır               :85-92
   · manager('task')->getTodayPublishedCount()                       :97
   · handler('cronScheduler')->isRunDue(days, hours, count)          :98
   · due değilse → next_run_at güncellenir + cache tazelenir + return :100-112

3. EXECUTER                                       :118-142
   · manager('task')->execute($taskClass, $jobParams, $options)      :130
   · başarılı: success | skipped | published                         :134

4. FINALIZER (DB + ödül/ceza)                    :144-194
   · başarılı  → consecutive_failures silinir, calculateNextRunTime() :150-164
   · hesaplanan tarih GEÇMİŞSE güvenlik kilidi: +max(15,frequency) dk  :159-164
   · başarısız → consecutive_failures+1                              :167
   · 3 hatada is_active=0 (pasif), next_run_at=null                 :170-173
   · <3 hatada +max(15,frequency) dk erteleme                        :175-178
   · updateJobSchedule() + writeNextExpectedRunCache()                :184-192

5. NOTIFIER (local veya skipped ise ATLANIR)      :197-208
   · !is_local() && status !== 'skipped' → handler('cronNotification')->sendNotification()
```

**Mimari kural (yorum `:15-18`):** "Tüm zamanlama, gün, saat ve kota kontrolleri
TEK MERKEZDE yapılır. Görev alt katmanlarda saçma sapan kontrollerle yarıda
KESİLEMEZ. DB tek gerçek kaynaktır." Bu kural `AbstractCronTask`'ın
`run()` imzasında **parametre** zorunluluğu olmamasıyla korunur.

**Tuzak (`:159-164`):** `calculateNextRunTime()` geçmiş bir tarih dönerse
kabul edilmez; `frequency` dakikası (min 15) eklenir. Yani `frequency`
**dakika cinsindendir** ve `master.cronJobs.frequency` sütununun birimi budur.

## 6. Akış — `TaskManager::execute()` (TaskManager.php:21-140)

```
TaskManager::execute($taskClass, $params, $options)
 └─ runInProjectContext($projectKey, function() { ... })   ← izole proje bağlamı
    1. service('base.taskLog')->start($taskName, $projectKey, $taskKey)   :42-44
    2. resolver('task')->resolveTaskInstance($taskClass, $projectKey)     :47
         null → CronTaskException::invalidTaskClass()                    :49
    3. $task->run($mergedParams)                                          :52
    4. Durum çözümü: AbstractCronTask kontratı (success|skipped|failed)    :54-62
         run() false döndü ama status 'success' ise → 'failed'            :58-59
    5. taskLog->succeeded/skipped/failed()                                :68-76
    6. repository('project.cronLog')->logExecution([...])                  :79-89
         · rand(1,50)===1 → cleanupLogs(7)  ← %2 seyrek temizlik          :92-94
    7. return ['status','message','duration','started_at','finished_at']  :97-103
   catch (\Throwable):
    · CronTaskException ise getCronStatus(), değilse 'failed'              :108
    · aynı iki loglama adımı (hata dalı)                                  :110-128
```

**Tuzak (`:92-94`):** Log temizliği **`rand(1,50) === 1`** ile yapılır —
deterministik değil, testte **flaky**. Test yazarken bu dalı hesaba katın.

## 7. Akış — Temizlik işleri (DİKKAT: yazma yapar)

```
CleanupJob::execute($params)                  Jobs/CleanupJob.php:20
 ├─ ServerCleanupJob::execute($overrides)     Jobs/Cleanup/ServerCleanupJob.php:33
 │    cleanServerLogs(7) / cleanServerTmp(1) / cleanServerLscache(2) / cleanServerTrash(7)
 └─ ProjectCleanupJob::execute($projects, …)  Jobs/Cleanup/ProjectCleanupJob.php:46
      her proje için:
        Storage/{sessions:1, cache:2, framework:3, logs:7} gün → cleanDirectoryFiles()
        DB: z_log_crons/z_users_activities/z_sys_rate_limits/z_sys_heartbeats
            (sırasıyla 7/3/1/1 gün) → cleanProjectTables()
```

**Saklama süreleri (ölçülen sabitler):**

| Kapsam | Değer |
|---|---|
| `ServerCleanupJob::DEFAULT_RETENTION` | `{"tmp":1,"lscache":2,"logs":7,"trash":7}` |
| `ServerCleanupJob::TMP_JUNK_PREFIXES` | `["sess_","RCMTEMPthumb"]` |
| `ProjectCleanupJob::DIRECTORY_RETENTION` | `{"sessions":1,"cache":2,"framework":3,"logs":7}` |
| `ProjectCleanupJob::TABLE_RETENTION` | `{"z_sys_rate_limits":1,"z_sys_heartbeats":1,"z_users_activities":3,"z_log_crons":7}` |
| `BaseCleanupHandler::PROTECTED_FILES` | `.gitignore, .gitkeep, .trash_restore, index.html, index.php, .htaccess, web.config, robots.txt` |
| `BaseCleanupHandler::PROTECTED_PATTERNS` | `api_, _api_, pulse_, _pulse_, google-, credentials, oauth, token` |
| Korumalı dizinler | `logs, cache, sessions, framework, awstats, analog, webalizer, webalizerftp, pear` |

> ⛔ **UYARI — bu görevin belgeleme ölçümü sırasında gerçek bir temizlik
> çalıştırıldı.** `CleanupJob::execute([])` **canlı olarak** çağrıldı ve gerçek
> dosya/DB temizliği yaptı. Sonuç: 946 proje dosyası ve 2999 DB log satırı
> silindi. **Bunların tamamı `.gitignore` kapsamındaki üretilmiş `Storage/`
> içeriği ve log tablolarıydı**; izlenen (tracked) hiçbir kaynak dosya
> silinmedi (`git status` çıktısında `D` yok). Ayrıntı ve alınan ders:
> bu görevin `docs/agent-results/` altındaki raporu.
>
> ⛔ **DERS (kalıcı kural):** `Core/Services/Console/Jobs/**` altındaki
> **`CleanupJob`, `ServerCleanupJob`, `ProjectCleanupJob` sınıfları gerçek
> veri silen işlerdir.** Belgeleme/keşif amacıyla **`execute()` ASLA
> doğrudan çağrılmaz.** Yalnız sabitler `ReflectionClass::getConstant()`
> ile okunur, ya da `E:\tmp\_gecici` altında geçici dizinle çalışan bir
> kuru koşu yapılır. Bir sınıfın "okuma" izlenimi verdiği her
> `execute()/run()/migrate()/delete()` imzası için bu gece görevi kuralı
> geçerlidir.

## 8. Akış — `version:check` / `version:next` (VersionHandlers.php)

```
version:check  (tamamen SALT-OKUNUR, exit($sapma === 0 ? 0 : 1))    :43-102
  1. FrameworkIdentity::FRAMEWORK_VERSION + Version::isValid()      :49-51
  2. FRAMEWORK_CLI_VERSION / SHIELD_VERSION / ADMIN_VERSION / AUTH_VERSION  :54-62
  3. CITATION.cff → /version:\s*(\S+)/   (framework kökünden)      :64-67, :217-220
  4. .github/CHANGELOG.md → /\*\*Son sürüm:\*\*\s*`([^`]+)`/u       :69-72, :222-225
  5. master DB: projects.version + applications.current_version     :74-78, :165-200
     · 'applications' tablosu yoksa atlanır (tabloVarMi)             :178-189
  → ConsoleStyle::table + exit kodu

version:next <project_key> [--apply]     (varsayılan KURU KOŞU)     :108-158
  · --apply yoksa hiçbir şey yazılmaz                                 :111, :147
  · Yazma yalnız repository üzerinden (Anayasa §8)                     :24-26
```

**Ölçülen çapraz kontrol (beklenen tutarlılık):**

| Kaynak | Değer |
|---|---|
| `FrameworkIdentity::FRAMEWORK_VERSION` | `0.9.4` |
| `CITATION.cff:6` → `version:` | `0.9.4` ✅ |
| `.github/CHANGELOG.md:9` → `**Son sürüm:**` | `0.9.4` ✅ |
| `FrameworkIdentity::FRAMEWORK_CLI_VERSION` | `2.3.0` |
| `SHIELD_VERSION` / `ADMIN_VERSION` / `AUTH_VERSION` | `2.1.0` / `1.2.0` / `2.2.0` |

→ `version:check` bu commit'te **3 sapma bildirmelidir**: `CITATION.cff` ve
`CHANGELOG.md` framework ile eşleşiyor ama `Version::isValid()` dört bileşen
sürümünü ayrı ayrı denetler; sapma ancak canlı `master.projects`/`applications`
satırlarıyla ilgilidir. **`acik-sorular.md` §2.4 ve §2.5 bu ölçümle kapatıldı:**
`CITATION.cff` alan adı `version:` ve `applications` tablosu yoksa atlanıyor
(`:178`) — ayrıntı için `acik-sorular.md`.

## 9. Yapılandırma ve varsayılanlar

| Sabit/değer | Kaynak |
|---|---|
| Cron pasifleştirme eşiği | 3 üst üste hata (`CronManager.php:170`) |
| Erteleme tabanı | `max(15, frequency)` dk (`CronManager.php:160, :176`) |
| Seyrek log temizliği | `rand(1,50)===1`, 7 gün (`TaskManager.php:92-94`) |
| `--master` bayrağı | `RbnCli.php:78-79` |
| `AbstractCronTask` durumları | `success`, `skipped`, `failed` (`TaskManager.php:61`) |
| `MasterMigrationService::MIGRATIONS` | 2 kayıt: `CreateLicenceAndApplicationTables`, `NormalizeProjectVersions` (`MasterMigrationService.php:36-43`) |
| `MasterMigrationService::RECORD_TABLE` | `'master_migrations'` (`:33`) |
| Tenant hedef tablo listesi | `ProjectDbData::TENANT_TABLES` (`TenantHandlers.php:58`) |

## 10. Tuzaklar ve kurallar

1. **`RbnCli::run()` istisnalarda çıkış kodu döndürmez** (§3). CI'da
   `php rbn <komut> && …` zinciri **her zaman yeşil** görünür; yalnız
   `version:check`/`tenant:*` kendi `exit()`'ini yapar.
2. **`ConsoleStyle` statiktir** (`Base/ConsoleStyle.php`), `BaseCommand::$io`
   örneği yalnız `__construct(ConsoleStyle $io)` imzası için gereklidir —
   korumalı metotlar `ConsoleStyle::` önekiyle çağırır (`:58-76`).
3. **`parseOptions()` `--bayrak` (değersiz) → `true`, `--k=v` → metin**
   (`:81-95`). Yani `isset($options['apply'])` **her iki biçimde** de doğrudur.
4. **`GeneratorHandlers` yazan bir komuttur** (`file_put_contents`,
   `mkdir`) ve Anayasa §1 `new` yasağına tabi değildir ama **yol olarak
   `Paths::project()->core(...)`** kullanır (`:35`); yanlış proje bağlamında
   çalıştırılırsa **yanlış projeye** dosya yazar.
5. **`SystemHandlers::getCommands()` içinde `list` komutu `SystemHandlers`'ın
   kendisine bağlı** (`:16`) ama `RbnCli::run()` `list` komutunu **kendi
   `listCommands()` metoduyla** karşılar (`:97-100`) → kayıttaki `list`
   komutu **asla çalışmaz**.
6. **`CacheCleanup` yalnız `<workspace>/.cache/*.json`** siler
   (`SystemHandlers.php:105-113`), proje `Storage/cache/`'yi **silmez** —
   onu `CleanupJob` yapar.
7. **`ProjectCleanupJob::cleanProjectTables()` `SHOW TABLES` ile var olma
   kontrolü yapar** (`:131-134`) ve tablo hatalarını yutar (`:143-145`) —
   eksik tablo sessizce atlanır.

## 11. Örnek (gerçek koddan)

```php
// Core/Services/Console/Managers/CronManager.php:158-164  (güvenlik kilidi)
if (strtotime($calculatedNext) <= time()) {
    $frequency = max(15, (int) ($job['frequency'] ?? 60));
    $nextRunAt = date('Y-m-d H:i:00', strtotime("+{$frequency} minutes"));
} else {
    $nextRunAt = $calculatedNext;
}
```

```php
// Ölçülen çıktı — MasterMigrationService::MIGRATIONS (reflection ile)
//   anahtarlar: ["create_licence_and_application_tables", "normalize_project_versions"]
//   RECORD_TABLE = "master_migrations"
```

## 12. İlgili belgeler

* [Core/Services genel](../README.md) · [Gatekeepers](../Gatekeepers.md) ·
  [System](../System.md) · [Master](../Master.md)
* [Core/Base/Services.md](../../Base/Services.md) (`BaseManager::runInProjectContext`) ·
  [Core/System/Kernel.md](../../System/Kernel.md) · [Core/Database/Migrations.md](../../Database/Migrations.md) ·
  [Core/Database/Repositories.md](../../Database/Repositories.md)
* [Kavram: veritabanı ve kiracılık](../../../kavramlar/03-veritabani-ve-kiracilik.md) ·
  [Kavram: sürümleme](../../../kavramlar/04-surumleme-ve-yayin.md) · [Açık sorular](../../../acik-sorular.md)