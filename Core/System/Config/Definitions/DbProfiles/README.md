# Core/System/Config/Definitions/DbProfiles — Veritabanı Kimlik Profilleri (SABİT dosyaları)

## Ne

Üç **kimlik/yapılandırma** sınıfı (hiçbir sınıftan TÜREMEZ: `final class`). İçlerinde **yalnız `public const`** vardır —
**metot YOKTUR**. Koruma (gatekeeper) mantığı değildirler.

| Sınıf | Sabitler | `definitionCategory` |
|---|---|---|
| `MasterDbData.php` | `PREFIX`, `CHARSET`, `REQUIRED_TABLES`, `KEYS_MAP` | `database_master` |
| `CommonDbData.php` | `PREFIX`, `CHARSET`, `DB_NAME`, `REQUIRED_TABLES`, `CLEANUP_ALLOWED_TABLES`, `KEYS_MAP` | `database_common` |
| `ProjectDbData.php` | `PREFIX`, `ENV_FILE`, `HOST`, `CHARSET`, `DEFAULT_DB_USER`, `DB_USER_KEY`, `DB_PASS_KEY`, `REQUIRED_TABLES`, `CLEANUP_ALLOWED_TABLES`, `KEYS_MAP` | `database_project` |

Namespace: `Rbn\Framework\Core\System\Config\Definitions\DbProfiles`.

## Kim ne yapar (PATRON KURALI)

> "Sabitlerin olduğu dosyalarda METOT olmaz; sabitler ayrı dosya, metotlar ayrı dosya."

| Sorumluluk | Nerede? | Ne yapar? |
|---|---|---|
| Veritabanı **şema** kimliği | `Definitions/DbProfiles/*.php` | **Yalnız `public const`** — `KEYS_MAP`, `REQUIRED_TABLES`, tablo listeleri, ön ekler. Metot YOK. |
| **Bağlantı** değerleri (host/port/ad/kullanıcı/parola) | `Engine/Database/DbProfileResolver.php` | `host()`, `port()`, `databaseName()`, `user()`, `password()`, `charset()`, `keysMap()`, `credentials()` |
| **SMTP** + gönderen kimliği | `Engine/Database/SmtpProfileResolver.php` | `enabled()`, `host()`, `port()`, `secure()`, `user()`, `pass()`, `fromAddress()`, `fromName()` |

## Neden burada, `Gatekeepers/Models` altında değil

Bu sınıflar kimlik/yapılandırma verisidir; koruma mantığı değil. `Core/System`
(`DatabaseConfig`, `ConfigResolver`, `Discovery/Definition`) bunları **üst katmandan**
çağırıyordu — yani `Core` → `Core/Services/Gatekeepers` yönünde ters bağımlılık vardı.
`Gatekeepers/` klasörünün **kalanı yerinde kalır**: `DatabaseGuardService`,
`DatabaseGuardProvider`, `DatabaseGuardHandler`, `SystemGuard*`.

## Kim kullanır

- `Engine/Database/DatabaseConfig.php` — `DbProfileResolver::credentials(...)`
- `Engine/Config/ConfigResolver.php` — `database_master` / `database_common` **sabit yol yetkisi**
- `Discovery/Clusters/Logic/Definition/{Definition,DefinitionResolver}.php` — `KEYS_MAP`, `REQUIRED_TABLES` (sabit)
- `Discovery/Engine/Cache/ProjectDataMapper.php` — `KEYS_MAP` (sabit) + `DbProfileResolver::credentials()`
- `Services/Gatekeepers/Providers/DatabaseGuardProvider.php` — `DbProfileResolver::credentials(...)`
- `Database/Repositories/Project/CronLogRepository.php`, `Services/Console/Handlers/SystemHandlers.php` — `DbProfileResolver::databaseName(MasterDbData::class)`
- `Packages/RbnEmail/Handlers/EmailConfigHandler.php` — `SmtpProfileResolver::*`

## Klasör standardı (ne girer / girmez)

- **Ne GİRER:** veritabanı **şema** kimliği (`REQUIRED_TABLES`, `KEYS_MAP`, charset) — sır DEĞİLDİR.
- **Ne GİRMEZ:** bağlantı DEĞERİ **ve metot**. Master DB'nin host/port/ad/kullanıcı/parolası
  ve SMTP ayarları **yalnız** `Core/System/Config/Secrets/secrets.php` (gitignore'lu,
  0600, depoya girmez) üzerinden, **fail-closed** okunur. Bu klasöre parola/host/
  kullanıcı sabiti veya `phpinfo`/log ile parola yazan hiçbir şey eklenmez.

## Değer nereden geliyor?

| Değer | Okuma yolu |
|---|---|
| Master host/port/ad/kullanıcı/parola | `DbProfileResolver` → `Secrets::masterDb()` |
| SMTP (8 alan) | `SmtpProfileResolver` → `Secrets::smtp()` |
| Ortak DB | `DbProfileResolver` → `Secrets::masterDb()` (master ile aynı sunucu/hesap) |
| Proje DB | **bu klasörde değil** — `projects/<proje>/Core/Config/project-settings.php` |

- Ortam değişkeni yolu **yoktur** [FW-096-D8]: master/ortak yalnız `Secrets::masterDb()`,
  proje yedeği yalnız `secrets.php` `db_user`/`db_pass`.
- `HOST` yalnız **proje** profili için bir varsayılandır; master/common `Secrets`'ten okur.
- Sessiz `''`/`root` fallback yoktur: alan eksikse `RuntimeException` fırlatılır ve mesaj
  yalnız **alan adını** yazar (`secrets.php: master_db.user tanimli degil`).
- `KEYS_MAP` **literal** `public const`'tır; `Definition::get('database_master','KEYS_MAP')`
  doğrudan bu sabiti döndürür (`DefinitionResolver` sabitleri metottan önce arar).
- **Nereye yazılır:** yeni bir DB kimliği ekranın bu klasördeki
  ilgili profile (`definitionCategory` ile kayıt) eklenir; **çözücü metotları**
  `Engine/Database/DbProfileResolver` içine gider. `ConfigResolver`'daki yol yetkisi yeni
  bir profil ise **AYNI dosyada** güncellenir.
- Taşıma sonrası **keşif haritaları** (`Storage/framework/discovery_map_*.php`,
  `components_map_*.json`) bayat kalır: namespace değişti. Harita önbelleği **tüm PHP
  dosyaları yüklendikten SONRA** silinip yeniden üretilir (`DEPLOY-PROSEDURU.md`
  §"Servis haritası önbelleği").

## Kırıcı değişiklik (yayın notu)

`git revert` ile geri alınabilir; eski yol `Core/Services/Gatekeepers/Models/`
**dahiL paket ile birlikte** sunucudan silinmelidir (MANIFEST `SİL:` satırı).
Eski namespace için `class_alias`/shim **yoktur**.

**[CONFIG-D-BARAN]** Profil sınıflarındaki `host()/dbName()/user()/pass()/
getCredentials()/getKeysMap()/smtp*()/emailFrom*()` metotları **KALDIRILDI**;
yerlerine `Engine/Database/DbProfileResolver` + `Engine/Database/SmtpProfileResolver`
çözücüleri geldi. Bağlantı şeması (anahtar adları ve sırası) ve tüm değer kaynakları
**değişmedi**.
