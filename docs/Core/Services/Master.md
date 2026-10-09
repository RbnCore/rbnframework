# Core/Services/Master — lisans doğrulama, proje/ayar/uygulama yönetimi (7 dosya)

> **Doğrulanan kod tabanı:** `c23b431f` · **Tarih:** 2026-10-05 · **Yayın:** 0.9.6 = bu commit + sonrası; belge yalnız doğrulama anındaki kodu anlatır
> **Kaynak klasör:** `Core/Services/Master/` — **7 `*.php`** = 5 kök + `Data/` 2.
> **Envanter:** 7 dosyanın **7'si** anlatıldı.
> **Doğrulama platformu:** Windows + PHP 8.3; `LicenceAccessRule` 4 kayıtla,
> `MasterLicencesService::verify()` sahte repository ile **gerçekten çalıştırıldı**.

## 1. Ne işe yarar, kim kullanır

**Master veritabanına** (`projects`, `licences`, `applications`, `settings`)
konuşan tek katman. Dört servis + bir saf kural sınıfı + iki sabit dosyası:

| Sınıf | Sorumluluk |
|---|---|
| `MasterProjectsService` | Proje oluşturma/güncelleme, sürüm sayacı, keşif önbelleği temizliği |
| `MasterLicencesService` | Lisans anahtarı üretme/doğrulama/iptal |
| `MasterApplicationsService` | Uygulama kaydı, sürüm kapısı |
| `MasterSettingsService` | Master ayar CRUD + önbellek yenileme |
| `LicenceAccessRule` | **Kural SSoT** — "geçerli lisans mı?" sorusunun tek cevabı |

**Kimler çağırır:** `DatabaseGuardHandler` (`isUnrestricted()` →
`MasterLicencesService::forSubject()`), `VersionHandlers` (`version:next` →
`nextVersion()`), yönetim paneli (`createProject/updateProject`).

**Anayasa §8 uyumu:** Hiçbiri doğrudan `Database` kullanmaz; hepsi
repository üzerinden yazar (`masterProjectsRepository`, `masterLicenceRepository`,
`masterApplicationRepository`).

## 2. Klasör/dosya envanteri (7/7)

| Dosya | Görev | Önemli public yöntemler |
|---|---|---|
| `LicenceAccessRule.php` (184) | **Saf kural sınıfı** (`final`). Durum tutmaz, DB'ye dokunmaz, `new` gerektirmez. | `static isUnrestricted(?array $record): bool`, `static isAnomalous(?array $record): bool`, `static isStatusBlocking(mixed $status): bool`, `static blockingReason(mixed $status): ?string`, `static isRecordUsable(?array $record): bool`, `static isKnownStatus(mixed): bool`, `static isKnownTier(mixed): bool`, `static isExpired(mixed $expiresAt): bool` · korumalı: `hasValue()`, `text()`, `statuses()`, `tiers()` |
| `MasterProjectsService.php` (277) | Proje yaşam döngüsü + sürüm sayacı. `protected $targetModel = 'master.projects'`. | `createProject(array $data): array`, `updateProject(int $id,array $data): array`, `getProjectsByGroup(string $groupName): array`, `nextVersion(string $projectKey,bool $apply=false): array` · korumalı: `generateUniqueLicenseKey(?string $group=null,string $tier='FREE')`, `formatCustomPath(?string $path,string $projectKey)`, `clearDiscoveryCache(?string $projectKey,?string $domain,?string $group=null)` |
| `MasterLicencesService.php` (248) | Lisans karar katmanı (fail-closed). `const MAX_KEY_ATTEMPTS = 5`. | `setLicenceRepository(object)`, `verify(string $key,?string $deviceHash=null): array`, `forSubject(string $type,int $id): ?array`, `issue(string $type,int $id,string $tier,?string $expiresAt): array`, `revoke(string $key): bool` · korumalı: `checkDevice()`, `generateKey()`, `result()`, `warn()`, `static subjectTypes()`, `static tiers()` |
| `MasterApplicationsService.php` (248) | Uygulama kaydı + sürüm kapısı. | `setApplicationRepository(object)`, `getByAppKey(string $key): ?array`, `list(): array`, `versionGate(?string $version): ?string`, `register(array $data): array`, `setCurrentVersion(string $appKey,string $version,?string $url,?string $sha256): bool` · korumalı: `nullable()`, `warn()` |
| `MasterSettingsService.php` (142) | Master ayar CRUD. | `master(string $key,$default=null): ?string`, `category(string $category): array`, `refresh(string $key): void`, `createSetting(array $data): array`, `updateSetting(int $id,array $data): array` |
| `Data/MasterLicenceConfig.php` (58) | Lisans alan/enum sabitleri. | `STATUS_*` (4), `TIER_*` (3), `SUBJECT_*` (2), `KEY_PREFIX='RBN'`, `DEFAULT_MAX_ACTIVATIONS=1`, `REASON_*` (7) |
| `Data/MasterSettingsConfig.php` (35) | Master ayar sözlükleri. | `const TYPES` (5), `const CATEGORIES` (6) |

## 3. Akış — `createProject()` (MasterProjectsService.php:26-72)

```
createProject(['project_name'=>…, 'domain'=>…, …])
 1. project_key = helper('text')->turkishSlug($data['project_name'])        :29-30
 2. isKeyUnique() false → {success:false, errors:['Proje anahtarı zaten kullanımda.']}  :33-38
 3. license_key = generateUniqueLicenseKey($group)   (tier: FREE)          :41-42
 4. custom_path = formatCustomPath($data['custom_path'] ?? null, $key)    :45
 5. repository->createProjectIdentity([... 8 alan ...])                   :49-61
      · version = $data['version'] ?: Version::initial()                 :59
      · 'license_key' ve 'status' KORUMALI alan → yetkili yol            :47-48
 6. başarılıysa clearDiscoveryCache(key, domain, group)                  :63-66
 → {success, errors}
```

`updateProject()` (`:77-129`) aynı akışı izler **tek farkla**: `license_key`
korunur, yoksa üretilir (`:99-105`) — yani güncelleme **anahtarı üretmez**.

**Tuzak:** `isKeyUnique($key)` güncellemede **kendi `$id`'si hariç** kontrol
eder (`:92` `isKeyUnique($key, $id)`) — yani aynı anahtarla güncelleme
engellenmez.

## 4. Akış — `nextVersion()` (varsayılan KURU KOŞU) (:198-251)

Dönüş sözleşmesi (`:196`):
`array{success:bool, project_key:string, current:string, next:string, written:bool, errors:string[]}`

```
nextVersion('<proje>', $apply = false)
 ├─ boş anahtar → errors[] + return                                   :206-209
 ├─ repo->findByProjectKey($key) null → errors[] + return               :213-217
 ├─ current = Version::isValid($mevcut) ? $mevcut : Version::initial() :219-221
 │    ← GEÇERSİZ kayıt güvenli başlangıca düşer, sayaç ORADAN devam eder
 ├─ next = Version::next(current)                                     :222
 ├─ !$apply → {success:true, written:false} ve DÖN                    :224-227   ← varsayılan
 ├─ repo->updateProjectVersion($id, $next)                             :230
 │    InvalidArgumentException → errors[] + return                     :231-234
 └─ yazıldıysa clearDiscoveryCache(key, domain, group)                 :243-247
```

**Tuzak (`:242` yorumu):** Önbellek temizlenmezse eski sürüm okunmaya devam eder.
`clearDiscoveryCache()` (`:256-276`) **üç ayrı anahtar** siler:
`project_<key>`, `domain_<alan adı>`, `group_<grup>`. Alan adı anahtarında
`.test`/`.local` **soyulur** ve port (`:`) kesilir (`:268`) — yerel ortamda
`.test` **olmadan** anahtar üretmek için.

## 5. Akış — `MasterLicencesService::verify()` (fail-closed) (:41-86)

```
verify($key, $deviceHash = null)
 1. boş anahtar → REASON_UNKNOWN_KEY                                  :46-48
 2. repo->findByKey() → istisna → REASON_STORAGE_ERROR (uyarı + geçersiz)  :50-55
 3. satır yok/boş → REASON_UNKNOWN_KEY                                 :57-59
 4. !LicenceAccessRule::isRecordUsable($row) → REASON_INVALID_RECORD    :66-68
 5. LicenceAccessRule::blockingReason($status) → revoked|suspended|expired  :72-74
 6. LicenceAccessRule::isExpired($expires_at) → REASON_EXPIRED          :77-79
 7. deviceHash verilmiş ve checkDevice() false → REASON_DEVICE_MISMATCH :81-83
 → {valid:true, status, tier, expires_at, reason:null}
```

**Dönüş şekli (`:39`):**
`array{valid: bool, status: ?string, tier: ?string, expires_at: ?string, reason: ?string}`

### 5.1 Ölçülen çıktı (sahte repository ile gerçek `verify()` çağrısı)

```
verify('RBN-TEST-KEY')
  → {"valid":true,  "status":"active", "tier":"PRO", "expires_at":null, "reason":null}
verify('RBN-TEST-KEY', 'cihash-besik')
  → {"valid":false, "status":"active", "tier":"PRO", "expires_at":null, "reason":"device_mismatch"}
```

### 5.2 Ölçülen `LicenceAccessRule` tablosu (4 kayıt)

| Kayıt | `isStatusBlocking` | `isKnownStatus` | `isRecordUsable` |
|---|---|---|---|
| `null` | `false` | `false` | `false` |
| `{status:'active', tier:'FREE'}` | `false` | `true` | **`true`** |
| `{status:'revoked', tier:'PRO'}` | **`true`** | `true` | **`true`** |
| `{status:'bilinmeyen', tier:'X'}` | `false` | `false` | **`false`** |

**Kritik ayrım:** `isRecordUsable()` yalnız **tanınabilirlik** kontrol eder —
`revoked` bir kayıt **kullanılabilir tanınıyor** (tanınabilir), ama
`isStatusBlocking()` engel bildiriyor ve `verify()` `:72-74`'te geçersiz döner.
Yani iki metot **farklı sorulara** cevap verir; `isRecordUsable()` tek başına
"lisans geçerli" demek **değildir**.

### 5.3 Kural metni (sınıf docblock `:17-24`)

> Kısıtsız = tier **FREE** **VE** status **active** **VE**
> (`expires_at` NULL/boş → SÜRESİZ)
> (`expires_at` DOLU **VE** `strtotime` ile ÇÖZÜLEMEYEN → KISITLI = fail-closed)
> (`expires_at` DOLU **VE** çözülüp geçmiş → KISITLI)

**Neden ayrı sınıf (docblock `:10-15`, B-05):** "geçerli lisans" sorusu iki
katmanda soruluyordu ve **iki yerde farklı yazılmıştı**
(`DatabaseGuardHandler::isUnrestricted()` ve `MasterLicencesService::verify()`).
Kural bir kez yazılır; kapı ve servis aynı cevabı alır.

**Neden `new` yok (docblock `:26-32`):** Saf kural sınıfı — durum tutmaz, DB'ye
dokunmaz, yalnız statik metot. Bu yüzden keşif/IoC kaydı gerekmez (Anayasa §1
`new` yasağı ve §5 "yalnız bileşen kaydı").

## 6. Akış — `MasterApplicationsService::versionGate()`

`versionGate(?string $version): ?string` (`:108`) sürüm eşiğini denetler:
uygulamanın `min_version` değeri istemci sürümünden yüksekse **reddedilen
sürümü** döner. `register()` (`:131`) yeni uygulama kaydı üretir;
`setCurrentVersion()` (`:192`) `url` + `sha256` ile birlikte günceller —
yani **bütünlük doğrulaması** veri olarak saklanır.

## 7. Yapılandırma ve sabitler (ölçülen)

`MasterLicenceConfig` (tümü ölçüldü):

| Sabit | Değer |
|---|---|
| `STATUS_ACTIVE` / `SUSPENDED` / `REVOKED` / `EXPIRED` | `active` / `suspended` / `revoked` / `expired` |
| `TIER_FREE` / `LIFETIME` / `PRO` | `FREE` / `LIFETIME` / `PRO` |
| `SUBJECT_PROJECT` / `SUBJECT_APPLICATION` | `project` / `application` |
| `KEY_PREFIX` | `RBN` |
| `DEFAULT_MAX_ACTIVATIONS` | `1` |
| `REASON_UNKNOWN_KEY` | `unknown_key` |
| `REASON_EXPIRED` / `REVOKED` / `SUSPENDED` | `expired` / `revoked` / `suspended` |
| `REASON_DEVICE_MISMATCH` | `device_mismatch` |
| `REASON_STORAGE_ERROR` | `storage_error` |
| `REASON_INVALID_RECORD` | `invalid_record` |

`MasterSettingsConfig` (ölçüldü):

| Sabit | Değer |
|---|---|
| `TYPES` | `{global:"Global", system:"Sistem", project:"Proje", api:"API", other:"Diğer"}` |
| `CATEGORIES` | `{general:"Genel", api:"API Anahtarları", mail:"E-posta Ayarları", seo:"SEO Ayarları", system:"Sistem Ayarları", other:"Diğer"}` |

Diğer:

| Sabit | Değer | Kaynak |
|---|---|---|
| `MasterLicencesService::MAX_KEY_ATTEMPTS` | `5` | `:20` (ölçüldü) |
| `MasterProjectsService::$targetModel` | `'master.projects'` | `:21` |
| Varsayılan proje sürümü | `Version::initial()` | `:59, :117` |
| Tier varsayılanı (anahtar üretimi) | `FREE` | `:136` |
| `.test`/`.local` soyma (önbellek anahtarı) | `str_replace(['.test','.local'], '', …)` | `:268` |

## 8. Tuzaklar ve kurallar

1. **`verify()` anahtar DEĞERİNİ hiçbir yere yazmaz.**
   `DatabaseGuardHandler` docblock'ı (`:195`): "Anahtar DEĞERİ hiçbir yere
   yazılmaz." Yalnız `reason` günlüğe yazılır.
2. **`expires_at` bozuk = süresi dolmuş sayılır** (`isExpired()`, `:141-150`).
   `strtotime() === false` **fail-closed** çalışır (Y-1).
3. **`isAnomalous()` rutin kısıtlamadan AYRI tutulur** (docblock `:57-63`):
   * **Rutin** = kayıt okundu, alanlar tanınabilir, tarih çözülebiliyor
     (PRO/LIFETIME, status≠active, süresi dolmuş) → **günlüğe yazılmaz**;
   * **Anormal** = kayıt yok/boş, alan NULL ya da şemada yok, tarih bozuk →
     **günlüğe yazılır** (B-04, günlük gürültüsü kontrolü).
4. **`blockedReason()` tek metottadır** (docblock `:95-97`): "sebep ile karar
   AYRI yazılırsa ikisi zamanla ayrışır." — karar ve sebep birlikte döner.
5. **`MasterLicencesService` ve `MasterApplicationsService` test için açık
   repository yuvası sunar** (`setLicenceRepository()`, `:26`;
   `setApplicationRepository()`, `:29`). Bu, saf kural sınıfının `new`
   yasağından muaftır: **DI yuvası, keşif kaydı değildir.**
6. **`issue()` ve `revoke()` bu çalışmada ölçülmedi** (yazma yolu — master
   tablosuna dokunulmadı). İmza ve kural mantığı koddan okundu.
7. **`MasterApplicationsService::warn()` `LogThrottle` kullanır** (`:9`)
   — yani sürüm kapısı reddi günlüğe **saatlik bir kez** yazılır.

## 9. Örnek (gerçek koddan)

```php
// Core/Services/Master/LicenceAccessRule.php:89-106
public static function isStatusBlocking(mixed $status): bool
{
    return self::blockingReason($status) !== null;
}

public static function blockingReason(mixed $status): ?string
{
    return match (trim((string) self::text($status))) {
        MasterLicenceConfig::STATUS_REVOKED   => MasterLicenceConfig::REASON_REVOKED,
        MasterLicenceConfig::STATUS_SUSPENDED => MasterLicenceConfig::REASON_SUSPENDED,
        MasterLicenceConfig::STATUS_EXPIRED   => MasterLicenceConfig::REASON_EXPIRED,
        default => null,
    };
}
```

```php
// Ölçülen çıktı — gerçek verify() çağrısı (sahte repository)
// verify('RBN-TEST-KEY')                          → valid:true,  reason:null
// verify('RBN-TEST-KEY', 'cihash-besik')          → valid:false, reason:'device_mismatch'
```

## 10. İlgili belgeler

* [Core/Services genel](README.md) · [Gatekeepers](Gatekeepers.md) ·
  [System](System.md) · [Console](Console/README.md)
* [Core/Database/Repositories.md](../Database/Repositories.md) (`masterLicenceRepository`,
  `masterProjectsRepository`) · [Core/System/Storage.md](../System/Storage.md) (`BootCacheProvider`) ·
  [Core/System/Kernel.md](../System/Kernel.md)
* [Kavram: veritabanı ve kiracılık](../../kavramlar/03-veritabani-ve-kiracilik.md) ·
  [Kavram: sürümleme](../../kavramlar/04-surumleme-ve-yayin.md) ·
  [Açık sorular §1.2 / §1.7](../../acik-sorular.md)