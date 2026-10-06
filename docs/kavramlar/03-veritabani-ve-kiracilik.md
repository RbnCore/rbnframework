# 03 — VERİTABANI VE KİRACILIK

> **Bu belge hangi commit'e göre yazıldı:** `d4af18d` (dal `feat/fw-license-master`)
> **Son doğrulama tarihi:** 2026-10-05
> **Yayın tabanı:** 0.9.4 = bu commit + sonrası; belge yalnız doğrulama anındaki kodu anlatır
> **Kapsam:** Bağlantı aileleri, model koruması, kiracı izolasyonu, migration, master
> tabloları, lisans kapısı

---

## 1. Bağlantı aileleri (`Database`)

`Core/Database/Database.php` — tekil (`private static ?self $instance`,
`Database.php:24-28`), trait tabanlı: `ConnectionTrait`, `ExecutionTrait`,
`TransactionTrait`, `QueryBridgeTrait` (`Database.php:15-20`).

### 1.1 Üç bilinen ad + bir takma ad

`Core/Database/Engine/Traits/ConnectionTrait.php`:

| Sabit | Değer | Satır |
|---|---|---|
| `KNOWN_CONNECTIONS` | `['database_project', 'database_master', 'database_common']` | `ConnectionTrait.php:29` |
| `CONNECTION_ALIASES` | `'default' => 'database_project'` | `ConnectionTrait.php:44` |
| `activeConnection` | `'database_project'` | `ConnectionTrait.php:51` |

### 1.2 Bilinmeyen ad = HATA (fail-closed)

`normalizeConnectionName()` (`ConnectionTrait.php:68-83`): ad küçük harfe iner,
takma ad çözülür, listede yoksa **`InvalidArgumentException`** fırlatılır
(`ConnectionTrait.php:74-80`).

**Neden:** Sessizce bağlanmak, çok kiracılı kurulumda proje A'nın verisini proje B'nin
veritabanına yazmak demektir. Artık bu "yanlış yere yazdım" değil,
"bağlantı adı yanlış, düzelt" olarak durur (`.github/UPGRADING.md:547-569`).

Geriye uyum: `default` hâlâ çalışır ve `database_project` ile **aynı kümesi** gösterir.

### 1.3 Kullanım

```php
$db->connection();                       // aktif bağlantı adını DÖNDÜRÜR (string)
$db->connection('database_master');      // değiştirir, $this döner
$db->connectionScoped('database_master', function () { /* … */ });
```

* `connectionScoped()` (`ConnectionTrait.php:123-…`) kaydet/geri al deseninin
  **tek** yazılışıdır; geri alma `finally` içindedir, istisna yutulmaz
  (`ConnectionTrait.php:109-113`). Kapsamlar iç içe kullanılabilir
  (`ConnectionTrait.php:113-114`).
* `transaction(callable)` (`Database.php:55-73`): iç içe çağrı **ikinci transaction'ı
  açmaz**, dışa katılır (savepoint yok); yalnız en dıştaki commit/rollback yapar.

### 1.4 Kim ne bağlanır (Katman kuralı)

`.agents/rules/core-architecture.md` §8:

* **Repository** = veritabanına bağlanan (okur/yazar).
* **Provider** = dış servise bağlanan (API, SMTP, cPanel, ödeme, GeoIP…).
* Bir Provider'ın DB'ye ihtiyacı varsa Repository'yi çağırır; Repository dış servise
  çağırmaz; ikisini birleştiren katman Service'tir.
* İstisna: `MySqlProvider` gibi **bağlantı sürücüsü** katmanı bu kuralın dışındadır
  (`core-architecture.md` §8.5).
* Controller ve Model sınıfları **doğrudan** `$this->model()` veya SQL çağıramaz
  (`core-architecture.md` §1.15).

---

## 2. Profil katmanı (şema sabitleri ↔ bağlantı çözümü)

Patron kuralı: **"sabitlerin olduğu dosyalarda METOT olmaz; sabitler ayrı dosya,
metotlar ayrı dosya"** (`Core/System/Config/README.md:125`).

| Sorumluluk | Dosya | Satır |
|---|---|---|
| Veritabanı **şema** kimliği (yalnız `public const`) | `Core/System/Config/Definitions/DbProfiles/{MasterDbData,CommonDbData,ProjectDbData}.php` | `Core/System/Config/README.md:20, 129` |
| **Bağlantı** değerleri | `Core/System/Config/Engine/Database/DbProfileResolver.php` | `DbProfileResolver.php:66-186` |
| SMTP + gönderen kimliği | `Core/System/Config/Engine/Database/SmtpProfileResolver.php` | `Core/System/Config/README.md:131` |
| Proje profili **seçimi** | `Core/System/Config/Engine/Database/ProjectDbProfileResolver.php` | `ProjectDbProfileResolver.php:99-107` |

### 2.1 `DbProfileResolver` fail-closed

`DbProfileResolver::password()` (`DbProfileResolver.php:144-168`): proje profilinde
sıra `DB_USER`/`DB_PASS` ortam değişkeni → `Secrets::optional(...)` → yoksa
**`RuntimeException`** (`158-165`). Sessiz `''` veya `root` fallback'i **yoktur**;
hata metni **alan adını** yazar, değeri yazmaz (`DbProfileResolver.php:36-37, 161-164`).

`DbProfileResolver::port()` proje profili için çağrılırsa **`RuntimeException`** fırlatır
(`DbProfileResolver.php:81-92`): port katman değil, proje dosyasının bilgisidir.

### 2.2 Master profilinin gerekli tabloları

`MasterDbData::REQUIRED_TABLES` (`MasterDbData.php:54-60`):
`developers`, `ip_whitelist`, `ip_blocks`, `projects`, `z_sys_heartbeats`.

`MasterDbData::KEYS_MAP` (`MasterDbData.php:70-76`): `MASTER_DB_HOST`, `MASTER_DB_NAME`,
`MASTER_DB_USER`, `MASTER_DB_PASS`, `MASTER_DB_CHARSET` (değerler artık
`secrets.php`'ten okunur; `MASTER_DB_USER`/`PASS` **ortam değişkeni yolu kodda yok** —
`Core/System/Config/README.md:80-82`).

---

## 3. `BaseModel` — beyan dosyası, davranış trait'lerde

`Core/Base/Data/BaseModel.php`:

| Beyan | Varsayılan | Satır |
|---|---|---|
| `protected bool $scoped` | **`false`** | `BaseModel.php:57` |
| `protected array $projectScopeIncludes` | `[]` | `BaseModel.php:85` |
| `protected array $fillable` | `[]` | `MassAssignmentTrait.php:26` |
| `protected array $guarded` | `[]` | `MassAssignmentTrait.php:29` |

Yardımcılar: `isProjectScoped()` (`BaseModel.php:130-132`), `getProjectScopeIncludes()`
(`140-142`), `resolveScopeProjectKey()` (`155`), `withProjectScope(?string)`
(`172-176`, klonla), `withoutProjectScope()` (`199-202`, klonla),
`getProjectScopeColumn()` (`187`), `getTable()` (`224`) / `getTableOrNull()` (`244`).

`$scoped` **kendi dosyasında** yazılır; istisna listesi modele taşındı
(`protected array $projectScopeIncludes`), merkezi tablo adı sabiti kaldırıldı
(`.github/UPGRADING.md:470-483`).

### 3.1 `$fillable` / `$guarded` — `security.mass_assignment`

`Core/Base/Data/Traits/Model/Engine/MassAssignmentTrait.php`:

* Bayrak: `security.mass_assignment` (`MassAssignmentTrait.php:42`), varsayılan **`on`**
  (`183-185`).
* Uygulama sırası (`MassAssignmentTrait.php:81-98`):
  1. `$fillable` tanımlıysa **beyaz liste** (`array_intersect_key`, `92`);
  2. `$guarded` tanımlıysa **kara liste** — ama `authorizedFields()` çıkarılır
     (yetkili alanlar korunur, `94-98`);
  3. ikisi birden varsa `$fillable` **önce** baskındır (`83-84`).
* `filterFillable(array $data)` (`150-157`) yalnız `$fillable` budar.
* `query()->insert()/update()` bu trait'ten **geçmez**; o yolda beyaz listeyi
  elle uygulamak gerekir (`.github/UPGRADING.md:901-903`).
* Ölçüm anahtarı: `security.protected_field_log` (varsayılan açık) →
  `Storage/logs/security/*.jsonl` içinde `MASS_ASSIGNMENT_BLOCK` (model süzgeci) ve
  `CRUD_INPUT_FIELD_DROPPED` (controller giriş süzgeci) satırları —
  **yalnız alan adları**, değer yok (`.github/UPGRADING.md:907-908`).
* Geçici geri dönüş anahtarı: `Config::set('security.mass_assignment', false)`
  (veya `off`/`log_only`) (`.github/UPGRADING.md:907`).

### 3.2 Kapsam dışı modeller ölçümü

`QueryModelTrait`, kapsamlı olmayan somut model sınıflarını **saatte bir kez**
günlüğe yazar (yalnız ölçüm, davranış değişmez): kod
`MODEL_NOT_SCOPED`, bayrak `security.model_not_scoped_log` (varsayılan açık)
(`QueryModelTrait.php:39, 135-160`).

---

## 4. Kiracı izolasyonu (`project_key`)

### 4.1 Okuma

`QueryModelTrait` (`Core/Base/Data/Traits/Model/Engine/QueryModelTrait.php:88-95`):
`$scoped` boş değilse ve `resolveScopeProjectKey()` varsa filtre **sütun
adıyla niteliklenerek** eklenir:
`whereIn([aktif, 'GLOBAL'])` varsa o küme, değilse `where('project_key', aktif)`
(`QueryModelTrait.php:85-95`). `projectScopeIncludes` bu listeye eklenebilir.

**Önemli daralma:** Kapsamlı bir modelin `query()` çağrısı artık
`WHERE <tablo>.project_key = <aktif proje>` ekler. Kiracının **aktif bağlamı**
(`active_project_key()`) veritabanındaki `project_key` ile aynı değilse o kiracının
satırları **görünmez** (`.github/UPGRADING.md:315-322`).

### 4.2 Yazma

`create()/update()` artık `project_key` değerini **sunucu bağlamından** yazar;
çağıranın gönderdiği değer ezilir (`.github/UPGRADING.md:323-324`).
Bağlam yoksa (`'default'` yedeği) hiçbir `project_key` yazılmaz
(`.github/UPGRADING.md:325`). İlgili yardımcılar:
`applyProjectKeyScope()` (`MassAssignmentTrait.php:283`),
`writeAsProject()` (`256`), `fillProjectKeyIfMissing()` (`336`).

### 4.3 Çakışan elle `where` tuzağı

```php
// ESKİ (artık ÇAKIŞIR → boş sonuç):
$model->query()->where('project_key', $X);

// YENİ:
$model->withProjectScope($X)->query();

// Kapsam DIŞI modelde eski yol AYNEN çalışır.
```

Kaynak: `.github/UPGRADING.md:336-351`.

### 4.4 0.9.x'te açılan kapsam

`.github/UPGRADING.md:290-334` — 13 somut model `protected bool $scoped = true` beyanına
alındı (framework modelleri + `<proje>` modelleri).
`BaseModel::$scoped` **`false` olarak kaldı**; diğer somut modeller kapsam dışıdır ve
davranışları değişmedi (`.github/UPGRADING.md:312-313`).
Geri açma: `withoutProjectScope()` **kaçış kapısıdır**; kalıcı çözüm değildir
(`.github/UPGRADING.md:330-334`).

### 4.5 Önbellek anahtarı

`SidebarProvider` anahtarı `"sidebar_{rol}"` → `"sidebar_{kiracı}_{rol}"`
(`.github/UPGRADING.md:327-328`); eski önbellek girdileri kullanılmaz.

### 4.6 Şema işi (kod DEĞİL, yönetici adımı)

`.github/UPGRADING.md:355-439` — sıra **tersine çevrilemez**: yedek → **kod önce** →
`tenant:audit` → `tenant:plan` → çakışma taraması → tablo başına ayrı `ALTER` →
yeniden `tenant:audit` → `NOT NULL` sıkma.

Yerelde ölçülen kapsam: 7 proje veritabanı, 65 `(db,tablo)` çifti;
65/65'te `project_key` **zaten var**, NULL satır **0**, tek kolonlu indeks 28,
`NOT NULL`'a çekilen kolon 43, eklenen `UNIQUE` 26 (çakışan 9 grupta eklenmedi).
Ölçüm **yalnız `SELECT`** ile yapıldı.

MySQL hata kodları karşılıkları: `1138`, `1067`, `1091`, `1048`, `1052`
(`.github/UPGRADING.md:399-407`).

---

## 5. Migration yapısı ve `rbn` komutları

### 5.1 Üç ayrı migration hattı

| Hat | Nerede | Komut | Bağlantı |
|---|---|---|---|
| **Proje** | `Paths::project()->root('database/migrations')`, `*.php` | `migrate`, `migrate:rollback`, `migrate:status` | proje |
| **Master** | `Core/Database/Migrations/Master/*.php` | `master:migrate`, `master:migrate:status` | master |
| **Ortak/kiracı** | `Core/Database/Migrations/Tenant/TenantKeyMigration.php` | `tenant:plan` / `tenant:apply` / `tenant:revert` / `tenant:audit` | hedef DB (--database) |
| (SQL dosyası örneği) | `Core/Database/Migrations/111-z_user_tokens.sql` | — | — |

* Proje migration servisi: `Core/Services/Console/Services/MigrationService.php` —
  tablo `migrations`, `glob($path.'/*.php')`, `batch` numarası
  (`MigrationService.php:19-36, 73-78, 82-90, 99-114`).
* Master migration servisi: `Core/Services/Console/Services/MasterMigrationService.php` —
  kayıt tablosu `master_migrations`, tüm işlemler `onMasterConnection()` içinde
  (`MasterMigrationService.php:53-58, 83-120, 195`).
* `master:migrate` **asla otomatik çalışmaz**; yalnız elle verilir
  (kök `README.md:147-148`).

### 5.2 `rbn tenant:*` (dört komut)

`.github/UPGRADING.md:442-467`:

| Komut | Ne yapar | Şemaya yazar mı? |
|---|---|---|
| `php rbn tenant:audit --database=<db>[,<db2>]` | kolon envanteri, `project_key` dağılımı, NULL sayısı, sabit `DEFAULT` taraması, model beyan envanteri | **HAYIR — yalnız okur** |
| `php rbn tenant:plan --database=<db>` | hedef için kuru koşu migration planı | HAYIR |
| `php rbn tenant:apply --database=<db>` | ekleyici kolon + indeks (idempotent) | **EVET** |
| `php rbn tenant:revert --database=<db> --yes` | önce indeks, sonra kolon | **EVET** |

* `--database` **zorunludur** ve hedef başına **ayrı koşu** gerekir; `plan/apply/revert`
  **tek** hedef alır. `audit` birden çok kabul eder ve `--local` kısayolunu destekler
  (`.github/UPGRADING.md:454-459`).
* `tenant:audit` kaynağında **tek bir yazma SQL'i yoktur**
  (`INSERT/UPDATE/DELETE/ALTER/CREATE/DROP/TRUNCATE/REPLACE` geçmez)
  (`.github/UPGRADING.md:461-463`).
* `tenant:revert` **etkilidir** ve `--yes` olmadan hiçbir şey yapmaz.
* `NOT NULL` sıkma, sabit `DEFAULT` düzeltme ve `UNIQUE` **bu komutların işi DEĞİLDİR**
  (elle `ALTER`) (`.github/UPGRADING.md:465-466`).
* **Güvenlik:** `tenant:apply` verilen `--database` hedefine **uygular**; bu yüzden
  hedef adı komut satırından gelir ve framework'e yazılmaz
  (`.github/UPGRADING.md:456-459`).

### 5.3 Keşif standardı

`.agents/rules/database-and-cli.md` §4 — üç adım:

1. `php rbnframework/rbn db:tables --project=<proje>` (veya `--master`)
2. `php rbnframework/rbn db:schema <tablo> --project=<proje>`
3. `php rbnframework/rbn db:query "<SQL>" --project=<proje>` (veya `--master`)

**Kural:** tablo adı asla tahmin edilmez; projelerde özel önekler vardır.

---

## 6. Master tabloları

**Ölçülen liste** (`php rbn db:tables --master`):

`applications`, `cron_jobs`, `developers`, `ip_blocks`, `ip_whitelist`, `licences`,
`master_migrations`, `migrations`, `projects`, `projects_version_backup`, `settings`,
`z_sys_heartbeats`.

### 6.1 `projects`

Şema [01-mimari-harita.md §5.1](01-mimari-harita.md)'de. Öne çıkanlar:
`project_key` UNIQUE; `status` üç değerli enum; `version varchar(20) DEFAULT '1.0'`
(**şema değişmedi**, yalnız satır değerleri değişti — `.github/UPGRADING.md:63-66`).

### 6.2 `applications` ve `licences`

`Core/Database/Migrations/Master/CreateLicenceAndApplicationTables.php`:
migration adı `2026_10_02_000001_create_licence_and_application_tables`
(`CreateLicenceAndApplicationTables.php:43`); DDL üreticileri `applicationsDdl()`
(`87`) ve `licencesDdl()` (`109`); `down()` `52`, `copyProjectLicences()` `66`.

Yerel ölçüm: `applications` **boş** (0 satır) — `.github/UPGRADING.md:68-70` ile tutarlı.

Model → tablo eşlemesi (ölçüldü):

| Model | Tablo |
|---|---|
| `MasterApplicationModel` | `applications` |
| `MasterLicenceModel` | `licences` |
| `MasterProjectsModel` | `projects` |
| `MasterCronJobsModel` | `cron_jobs` |
| `MasterDevelopersModel` | `developers` |
| `MasterIpBlocksModel` | `ip_blocks` |
| `MasterIpWhitelistModel` | `ip_whitelist` |
| `MasterSettingsModel` | `settings` |
| `MasterRbnHeartbeatsModel` | `rbn_heartbeats` ← **bkz. ../acik-sorular.md** |

---

## 7. Lisans kapısı

### 7.1 Kapı nerede çalışır?

`DatabaseGuardStage` (`Core/System/Kernel/Stages/DatabaseGuardStage.php:20-25`) →
`databaseGuard` servisinin `check()`. Lisans kontrolü bunun içinde, proje durumu
denetiminden sonra yapılır — karar **doğrudan `licences` tablosundan** gelir; önceki
metin kontrolü (`projects.license_key` içinde `FREE` arama ve beyaz liste sınıfı)
**kaldırılmıştır** (`Core/Services/Gatekeepers/Handlers/DatabaseGuardHandler.php:170-186`;
`satır 170-172` yorumu).

### 7.2 Karar kuralları TEK yerde

`Core/Services/Master/LicenceAccessRule` — durum/ süre/cihaz kurallarının **tek** tanımı
(`DatabaseGuardHandler.php:190-197`). `MasterLicencesService::verify()`
(`Core/Services/Master/MasterLicencesService.php:41-85`) sırasıyla:

1. anahtar yoksa `REASON_UNKNOWN_KEY` (`47`)
2. depolama hatası `REASON_STORAGE_ERROR` (`54`)
3. kayıt okunamazsa `REASON_UNKNOWN_KEY` (`58`)
4. `LicenceAccessRule::isRecordUsable($row)` değilse `REASON_INVALID_RECORD` (`66-68`)
5. `LicenceAccessRule::blockingReason($status)` döndürürse durum engeli (`72-74`)
6. `LicenceAccessRule::isExpired($expiresAt)` → `REASON_EXPIRED` (`77-79`)
7. cihaz uyuşmazlığı → `REASON_DEVICE_MISMATCH` (`82`)

Dönüş biçimi: `{valid, status, tier, expires_at, reason}`
(`MasterLicencesService.php:39`).

`expires_at` NULL ise **süresizdir**; süresi dolan kayıtta `status` alanı
**değişmez**, yalnız sonuç döner (`MasterLicencesService.php:38`).

`isUnrestricted()` (`DatabaseGuardHandler.php:197-224`): kayıt yoksa
`LicenceAccessRule::isAnomalous()` uyarısı verilir ve sonuç `isUnrestricted($satir)`
olur (`218-222`). Servis okunamazsa yalnız uyarı yazılır, karar çağırandadır
(`DatabaseGuardHandler.php:206-209`).

### 7.3 Günlükleme

`Core/Services/Gatekeepers/Concerns/LogsLicenceWarnings.php`:
`licenceWarn()` (`27-34`), `licenceWarnOnce()` (`37-43`), statik `$anormalGunluklendi`
(`24`). Loglama **kararı asla değiştirmez** (`LogsLicenceWarnings.php:19`).

> **Satır sınırı notu:** `fw_licence_kapi.php` kabul testi `DatabaseGuardHandler`'ı
> **≤ 250 satırda** tutuyor; loglama bu yüzden ayrı trait'e taşındı
> (`DatabaseGuardHandler.php:25`, `LogsLicenceWarnings.php:9-17`).

### 7.4 Bilinçli sınırlar

* **Lisans doğrulaması veritabanı kaydına dayanır**; çevrimdışı (imzalı) doğrulama
  **kullanılmaz** (`.github/KNOWN-LIMITATIONS.md:130-133`).
* **Masaüstü uygulaması için lisans/onay tablosu tasarlanmadı ve uygulanmadı**
  (`.github/KNOWN-LIMITATIONS.md:135-138`).
* Lisans veren taraf **doğrudan DB yazımıdır**; CLI'da lisans anahtarı üretimi
  `MasterLicencesService::issue()` (`MasterLicencesService.php:109-151`) ve
  iptali `revoke()` (`155-168`) ile yapılır.

---

## 8. Özet kontrol listesi (model yazarken)

1. Model **kendi dosyasında** `$table`, `$connection`, `$scoped`, `$fillable`/`$guarded`
   beyan eder; davranış `ActionModelTrait` + Engine trait'lerinden gelir
   (`.agents/rules/core-architecture.md` §3).
2. Doğrudan `new Model()` **yasak**; Repository içinde `$this->model(...)`
   (`core-architecture.md` §1.10).
3. DB'ye dokunan sınıf **Repository** adıyla yazılır (§8.1).
4. Ham superglobal (`$_SERVER`, `$_GET`, `$_POST`, `$_FILES`) **yasak**;
   `$this->request` kullanılır (`core-architecture.md` §7).
5. Kapsamlı modelde elle `where('project_key', …)` **yazma** — `withProjectScope()` kullan.
6. `query()->insert()/update()` `$fillable` süzmez — `filterFillable()` uygula.
7. Ham yazma gerekiyorsa `query()->insert()` yolu `CrudModelTrait`'ten geçmediği için
   `.github/UPGRADING.md:901-903`'teki yol izlenir.

---

## 9. Bilinmeyenler

* `MasterRbnHeartbeatsModel::$table = 'rbn_heartbeats'` (`MasterRbnHeartbeatsModel.php:21`)
  ama ölçülen master tablosu `z_sys_heartbeats` ve `MasterDbData::REQUIRED_TABLES` da
  `z_sys_heartbeats`'i sayıyor (`MasterDbData.php:54-60`). Bu modelin canlıda
  kullanılıp kullanılmadığı **bu belgede doğrulanmadı**.
* `ProjectDbData::TENANT_TABLES` listesinin **tam içeriği** burada sıralanmadı;
  motor yalnız bu listeyi işler (`.github/UPGRADING.md:419-422`) ve `asw_*`
  tablolar listede **değildir**.
