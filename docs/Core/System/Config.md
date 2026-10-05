# Core/System/Config — Yapılandırma, ortam değişkeni ve sır okuyucuları

> **Doğrulanan kod tabanı:** `d508f5e1` (dal `feat/fw-license-master`) · **Tarih:** 2026-10-05 · **Yayın:** 0.9.3 = bu commit + sonrası; belge yalnız doğrulama anındaki kodu anlatır
> **Kaynak klasör:** `Core/System/Config/` — 22 `*.php` + 3 `README.md` (sır dosyası `Secrets/secrets.php` bilerek okunmadı/anlatılmadı; yalnız adı ve kuralları var).
> **Envanter:** 22 php dosyasının 22'si ve 3 README aşağıda anlatıldı (`secrets.php` dahil; içeriği hariç).

## 1. Ne işe yarar, kim kullanır

Çatının "ayar" kapısıdır; üç ayrı kaynağı üç ayrı **tek okuyucuya** böler:

| Ne | Tek okuyucu | Kaynak |
|---|---|---|
| Gizli değer (parola, `app_key`, jeton, API anahtarı) | `Secrets` | `Secrets/secrets.php` (bölümlü tek dosya) |
| Ortam değişkeni / bayrak / kill-switch / dizin | `Env` | `EnvKeys` sabit kaydı |
| Dosya tabanlı ayar (`project-settings.php` vb.) | `Config` (+ `ConfigResolver`) | proje `Core/Config/` klasörü |

Veritabanı bağlantı bilgisi bu üçünün birleşimidir: `DbProfileResolver` master/common için `Secrets`, proje için `Env` → `Secrets` → sabit varsayılan sırasını izler.

**Kimler çağırır:** kernel aşamaları (`Kernel/Base/PreBoot`, `Stages/*`), `Core/Database` bağlantı sağlayıcıları, `Core/Services/Gatekeepers` (veritabanı bekçisi), `Packages/RbnEmail` (SMTP), CLI komutları. Ölçülen çağrı sayıları (yalnız `Core`, `Bundles`, `Packages`, `Resources` altındaki `*.php`): `Config::get(` 21, `Env::flag(` 9, `Env::string(` 10, `Secrets::` 38, `DbProfileResolver::` 21, `SmtpProfileResolver::` 9, `ProjectDbProfileResolver::` 5.

## 2. Klasör/dosya envanteri

### 2.1 Kök (`Rbn\Framework\Core\System\Config`)

| Dosya | Görev | Önemli public yöntemler |
|---|---|---|
| `Config.php` | Statik "proxy hub": noktalı anahtarla dosya ayarı okur, bellek önbelleği tutar. | `get(string $key, mixed $default = null): mixed`, `set(string $key, mixed $value): void`, `clear(?string $key = null): void`, `setSurvivalMode(bool)`, `isResolving(): bool` |
| `Env.php` | Ortam değişkeni TEK okuyucusu; kayıtsız adı `RuntimeException` ile reddeder (fail-closed). | `string(string $name, ?string $default = null): ?string`, `flag(string $name, bool $default = true): bool`, `int(string $name, ?int $default = null): ?int`, `isSecretKey(string): bool`, `reset()`, `temizle()` (reset'in Türkçe takma adı) |
| `Secrets.php` | Sır dosyasının ince cephesi (bölümlü API + düz API). `ConfigFileLoader` ve `SecretsFlatApi` trait'lerini kullanır. | `section(string $name): array`, `masterDb(): array`, `smtp(): array`, `cpanel(): array`, `api(string $name): array`, `appKey(): string`, `cpanelHost()/cpanelUser()/cpanelToken(): string`, `setTestDirectory(?string)`, `resetCache()`; trait'ten: `all()`, `get($key)`, `masterDbPass()`, `masterSmtpPass()`, `optional($key): ?string` |

### 2.2 `Definitions/` — yalnız sabit/tanım (ad alanı `…\Config\Definitions`)

| Dosya | Görev |
|---|---|
| `EnvKeys.php` | Ortam değişkeni adlarının tek kaydı. **İçinde metot yok**: `public const` adlar + `OFF_VALUES`, `SECRET_KEYS`, `ALL_KEYS` listeleri. |
| `ConfigMap.php` | `BaseConfig`'ten türeyen küçük çekirdek ayar kısayolları: `getAppDebug()`, `getAppLogging()`, `getAppEnv()`, `getAppIsCli()`, `getAppSessionTimeout()` (hepsi `static`). |
| `SecretsSchema.php` | Sır dosyasının şeması: yol, bölüm kuralları, düz anahtar adları (`final class`, yalnız `public const`). |

### 2.3 `Definitions/DbProfiles/` — veritabanı profil sabitleri (yalnız sabit)

| Dosya | Görev |
|---|---|
| `MasterDbData.php` | Master veritabanı: `PREFIX='MASTER_DB'`, `CHARSET`, `REQUIRED_TABLES` (5 tablo), `KEYS_MAP`. Bağlantı değerleri sır dosyasındandır. |
| `CommonDbData.php` | Ortak (common) veritabanı: `DB_NAME='rbncore_common'`, `USER_ENV='COMMON_DB_USER'`, `PASS_ENV='COMMON_DB_PASS'`, `REQUIRED_TABLES` (6), `CLEANUP_ALLOWED_TABLES` (4), `KEYS_MAP`. |
| `ProjectDbData.php` | Proje veritabanı: `HOST='127.0.0.1'`, `DEFAULT_DB_USER='root'`, `USER_ENV='DB_USER'`, `PASS_ENV='DB_PASS'`, `DB_USER_KEY='db_user'`, `DB_PASS_KEY='db_pass'`, `REQUIRED_TABLES` (11), `CLEANUP_ALLOWED_TABLES` (2), kiracı sözleşmesi (`TENANT_COLUMN='project_key'`, `TENANT_COLUMN_TYPE='varchar(64)'`, `TENANT_INDEX_PREFIX='ix_'`, `TENANT_TABLES` — 12 tekil tablo), `KEYS_MAP`. |

(Ayrıca `DbProfiles/README.md` bu üç sabit dosyasını anlatan yerel nottur.)

### 2.4 `Engine/Config/` — dosya yükleme tekniği (ad alanı `…\Engine\Config`)

| Dosya | Görev | Yöntemler |
|---|---|---|
| `ConfigResolver.php` | Ayar adı → dosya yolu çözümü; özyineleme (`isResolving`) ve "survival" bayrağı. | `resolve($name, ?$projectKey): string`, `resolveContext($type, ?$name, ?$projectKey): mixed`, `guard(): mixed`, `setSurvivalMode()`, `isResolving()`, `setResolving()` |
| `ConfigFileLoader.php` | Ortak dosya tekniği (trait): bul → izin/içerik koru → `require` → normalize → doğrula; sonuç bellekte önbelleklenir. | `includeArray($dir,$file,$displayPath): array`, `normalize(array): array`, `validate($flat,$required,$nonEmpty,$label,$displayPath)`, `load(...)`, `resetCache()` |
| `ConfigFileGuard.php` | Sır dosyası koruması (trait): izin tavanı 0600 ve `<?php`/`return` sızıntı denetimi. | `guardFileMode()`, `guardFileContent()`, `examplePath()`, `hint()` |

### 2.5 `Engine/Database/` — bağlantı profili çözücüleri (ad alanı `…\Engine\Database`)

| Dosya | Görev | Yöntemler |
|---|---|---|
| `DatabaseConfig.php` | Değişmez (`readonly`) bağlantı değer nesnesi: `host`, `database`, `user`, `password`, `charset`. | `fromFile(string $path, string $name): self`, `fromRaw(array $data, string $name): self` |
| `DbProfileResolver.php` | Profil sınıfı (`MasterDbData`/`CommonDbData`/`ProjectDbData`) → bağlantı değerleri. | `credentials($profile): array`, `host()`, `port()`, `databaseName()`, `user()`, `password()`, `charset()`, `keysMap()` |
| `ProjectDbProfileResolver.php` | `project-settings.php` içindeki `DB_PROFILES` (`local`/`production`) seçici. | `activeProfile(): string`, `resolve(array $data, ?string $profile = null): array`, `requiredKeys(): array`; sabitler `PROFILES_KEY='DB_PROFILES'`, `PROFILE_LOCAL`, `PROFILE_PRODUCTION`, `PLACEHOLDER='__DOLDUR__'` |
| `SmtpProfileResolver.php` | SMTP alanlarını sır dosyasının `smtp` bölümünden okur. | `enabled(): bool`, `host()`, `port(): int`, `secure()`, `user()`, `pass()`, `fromAddress()`, `fromName()` |

### 2.6 `Engine/Secrets/` — sır okuma motoru (ad alanı `…\Engine\Secrets`)

| Dosya | Görev | Yöntemler |
|---|---|---|
| `SecretsLoader.php` | Yol çözümü + ham dosya okuma (TEK okuma kapısı); test için dizin değiştirilebilir. | `raw(): array`, `rootDirectory()`, `fileName()`, `displayPath()`, `fullPath()`, `isFileMissing()`, `setDirectory(?string)`, `resetCache()` |
| `SecretsSections.php` | Bölüm okuma + şema doğrulama + düz anahtar haritaları. | `read($name): array`, `readAll(): array`, `topLevelScalars(): array`, `topLevel($key): ?string`, `optionalFlat(): array` |
| `SecretsValidator.php` | Bölüm adı ve okunabilirlik doğrulaması. | `sectionName(string): string` (yalnız `[A-Za-z0-9_]+`), `readable($fullPath,$displayPath,$fileName)` |
| `SecretsFlatApi.php` | Eski "düz anahtar" okuma yüzeyi (trait; `Secrets` üzerinde kalır). | `all()`, `get($key)`, `masterDbPass()`, `masterSmtpPass()`, `optional($key)` |

### 2.7 `Secrets/` — veri klasörü

| Dosya | Görev |
|---|---|
| `secrets.example.php` | Şablon (depoya girer): `master_db`, `smtp`, `cpanel` bölümleri, üst düzey `app_key`, `api` {hizmet ⇒ alanlar}; her alan `CHANGE_ME`. |
| `secrets.php` | Gerçek sır dosyası; `.gitignore`'lu. **Bu belge içeriğini okumaz, yazmaz.** |
| `README.md` | Klasörün kısa kullanım notu (45 satır). |

## 3. Akışlar

### 3.1 `Config::get('dosya.anahtar.alt')` (`Config.php:56-114`)

1. `ConfigResolver::isResolving()` doğruysa varsayılan döner (özyineleme koruması, `Config.php:59-61`).
2. `setResolving(true)` yapılır (`:63`); `finally` bloğu `false`'a çevirir (`:111-113`).
3. Anahtar `database`, `database_master`, `database_common` ya da `database_project` ise dosyaya bakılmaz; `ConfigResolver::resolveContext()` döner (`:67-69`).
4. Aksi halde anahtar `.` ile bölünür; ilk parça dosya adıdır. `ConfigResolver::resolve($dosya)` yolu verir, dosya varsa `include` edilip `self::$items[$dosya]` içine alınır (`:72-77`).
5. `project-settings` için aktif proje anahtarı (`active_project_key()`) varsa, `ResolvesProjectConfigTrait::getRouteConfig()` çıktısı dosya dizisinin ÜZERİNE yazılır (`:88-97`).
6. Kalan parçalar iç içe dizide yürütülür; bulunamazsa varsayılan döner (`:100-108`). Her `Throwable` varsayılana çevrilir (`:109-110`).

### 3.2 Yol çözümü (`ConfigResolver::resolve`, `ConfigResolver.php:71-112`)

* `database_master` → `<frameworkRoot>/Core/System/Config/Definitions/DbProfiles/MasterDbData.php`; `database_common` → `CommonDbData.php` (`:75-94`).
* `database_project` → proje anahtarı verildiyse `resolveProjectPath($key, 'Core/Config/project-settings.php')`; yoksa `Paths::project()->configs('project-settings.php')` (`:96-102`).
* Diğer adlar: `guard()` bir `databaseGuard` servisi döndürürse onun `resolvePath()` çağrılır; **dönmezse** `Paths::project()->configs($ad.'.php')` (`:104-111`).

### 3.3 Veritabanı bağlantı değeri üretimi

`DatabaseConfig::fromRaw($data, $ad)` (`DatabaseConfig.php:52-85`):

* `database_master` → `new self(...DbProfileResolver::credentials(MasterDbData::class))` (`:55-57`).
* `database_common` → aynı, `CommonDbData` ile (`:60-62`).
* diğer → `ProjectDbProfileResolver::resolve($data)` (profil seçimi) sonra `Definition::get('database_project','KEYS_MAP')` ile alan eşlemesi; eşleme yoksa boş varsayılan nesne (`:64-84`).

`DbProfileResolver` çözüm sırası:

| Alan | master | common | proje |
|---|---|---|---|
| host | `Secrets::masterDb()['host']` | aynı | `ProjectDbData::HOST` (`127.0.0.1`) |
| database | `master_db.name` | `CommonDbData::DB_NAME` | `''` (DatabaseConfig'te `DB_NAME` anahtarından) |
| user | `master_db.user` | env `COMMON_DB_USER` → `master_db.user` | env `DB_USER` → sır `db_user` → `root` |
| password | `master_db.pass` | env `COMMON_DB_PASS` → `master_db.pass` | env `DB_PASS` → sır `db_pass` → **hata** (sessiz boş parola yok, `DbProfileResolver.php:155-165`) |

`port()` proje için bilerek `RuntimeException` fırlatır (`:83-89`): proje portu profil katmanında yoktur.

### 3.4 `DB_PROFILES` seçimi (`ProjectDbProfileResolver.php`)

`activeProfile()` (`:99-107`): önce `RBN_DB_PROFILE` ortam değişkeni (`local`/`production`, başka her değer yok sayılır, `:191-195`); yoksa framework kökünde tam `localhost` yol segmenti varsa `local` (`:205-215`); aksi `production`. Karar **istemciden bağımsızdır** (sunucu kimliği); `is_local()` kullanılmaz (gerekçe docblock `:66-98`).
`resolve()` (`:121-171`): `DB_PROFILES` yoksa/boşsa dosya aynen döner (geriye uyum). Seçilen profil yoksa, profilde zorunlu anahtar (`DB_HOST`,`DB_NAME`,`DB_USER`,`DB_PASS`,`DB_CHARSET`) eksikse ya da değer `__DOLDUR__` ise `RuntimeException`; başka profile düşülmez.

### 3.5 Sır okuma (`Secrets::section` → `SecretsSections::read`)

1. Bölüm adı `SecretsValidator::sectionName()` ile doğrulanır.
2. `SecretsLoader::raw()` → `ConfigFileLoader::includeArray()`: dosya var mı (`is_file`/`is_readable`), `guardFileMode` (Linux'ta mod `& ~0600` ≠ 0 ise hata; **Windows'ta atlanır**, `ConfigFileGuard.php:79-81`), `guardFileContent` (ilk 8 KiB: `<?php` ile başlamalı, `return` içermeli, `return`'den önce `?>` olmamalı), sonra `require`; sonuç `array` değilse hata.
3. Değerler `normalize()` ile string'e çevrilir (skaler olmayan `''` olur, `ConfigFileLoader.php:106-114`).
4. `SecretsSchema::SECTION_RULES` ile `validate()`: eksik alan, `CHANGE_ME` şablon değeri, boş zorunlu alan → hata (`:129-156`).
5. Hata metinleri yalnız dosya/bölüm/alan **adını** yazar; değer yazmaz.

## 4. Ayar anahtarları ve varsayılanlar

### 4.1 Ortam değişkenleri (`EnvKeys`) — 24 kayıtlı ad (`ALL_KEYS`), 9'u gizli (`SECRET_KEYS`)

| Ad | Gizli | Not |
|---|:--:|---|
| `APP_KEY`, `ENCRYPTION_KEY`, `RBN_LEGACY_SALT` | ✔ | şifreleme anahtarı / eski tuz |
| `RBN_ALLOW_LEGACY_SALT` | | eski tuz onayı; `CryptoHelper::legacySaltApproval()` kendi `1\|true\|on\|yes` yorumunu yapar (`Env::flag` DEĞİL) |
| `COMMON_DB_USER` | | common DB kullanıcı adı (sır değil) |
| `COMMON_DB_PASS`, `DB_PASS` | ✔ | |
| `DB_USER` | | |
| `RBN_DB_PROFILE` | | `local`\|`production`; **`Env` üzerinden değil** doğrudan `$_ENV/$_SERVER/getenv` ile okunur (bkz. §5) |
| `RBN_GUARD_FAILCLOSED` | | kill-switch; varsayılan fail-closed |
| `RBN_DEBUG` | | `PreBoot::envOverrideRequested()` kendi `1\|true\|on\|yes\|development` listesiyle; varsayılan KAPALI |
| `RBN_DEV`, `APP_ENV` | | |
| `RBN_LOG_THROTTLE` | | tanılama günlüğü saatlik kapısı; `0\|false\|off\|no\|hayir` ile kapanır; okuyan `LogThrottle` (`Core/Support/Bridges/Helpers/Library/LogThrottle.php`) |
| `RBN_WORKER_TOKEN`, `RBN_CREW_TOKEN`, `REDIRECT_RBN_WORKER_TOKEN`, `REDIRECT_RBN_CREW_TOKEN` | ✔ | işçi/ekip jetonları (`REDIRECT_` önekli olanlar Apache'nin yeniden yazma sonrası adıdır) |
| `RBN_UPLOADS_DIR`, `RBN_UPLOADS_URL_BASE`, `RBN_CREW_INBOX_DIR`, `RBN_CREW_DATA_DIR`, `REDIRECT_RBN_CREW_DATA_DIR` | | dizin/URL tabanları |
| `TG_SEND_DELAY_MS` | | yalnız test router'ı |

`SECRET_KEYS` ve `ALL_KEYS` listeleri `EnvKeys.php:194` ve `:210`'dadır; `OFF_VALUES = ['0','false','off','no','hayir']` (`:187`) ile `ShieldSettingsRepository::normalizeSwitch` aynı kümeyi kullanmak **zorundadır** (birim testiyle sabitli).

### 4.2 Sır dosyası şeması (`SecretsSchema`)

* `BASE_DIR='Core/System/Config'`, `FILE='/Secrets/secrets.php'`, `SECRET_FILE_MODE=0600`, `CHANGE_ME='CHANGE_ME'`, `APP_KEY_NAME='app_key'`, `API_SECTION='api'`.
* `SECTION_RULES`: `master_db` (host, port, name, user, pass; `pass` boş olabilir), `smtp` (enabled, host, port, secure, user, pass, from_address, from_name), `cpanel` (host, user, token — hepsi dolu).
* `OPTIONAL_SECTIONS = ['master_db','smtp']`; düz anahtar adları `master_db_pass`, `master_smtp_pass`, `rbncore_master_user`.

### 4.3 `ConfigMap` (`Definitions/ConfigMap.php:23-61`)

| Yöntem | Kural | Varsayılan |
|---|---|---|
| `getAppDebug()` | `RBN_DEBUG` sabiti tanımlıysa o, yoksa `app.debug` | `false` |
| `getAppLogging()` | `app.logging` | `true` |
| `getAppEnv()` | `RBN_DEV` sabiti doğruysa `development`, yoksa `app.env` | `production` |
| `getAppIsCli()` | `RBN_CLI` sabiti, yoksa `PHP_SAPI === 'cli'` | — |
| `getAppSessionTimeout()` | `RBN_SESSION_TIMEOUT` sabiti | `30` (dakika) |

## 5. Tuzaklar ve kurallar (kodda görülen)

1. **`Config::get()` bütünlük denetimine uğramaz.** `get()` içeride `setResolving(true)` yaptığı için `ConfigResolver::guard()` hep `null` döner (`Config.php:63`, `ConfigResolver.php:53-58`). Sonuç: `database_*` dışındaki her ad `Paths::project()->configs($ad.'.php')` yoluna düşer; `DatabaseGuardHandler::validateIntegrity` bu yolda devreye girmez. Guard'ı yalnız `ConfigResolver::resolve()` doğrudan (resolving kapalıyken) çağrılırsa kullanabilirsiniz. *(Eski `acik-sorular.md` §1.4 — kodla doğrulandı, buraya taşındı.)*
2. **`Env` yalnız kayıtlı adı okur.** Kayıtsız ad `RuntimeException`. Okuma sırası `$_ENV` → `$_SERVER` → `getenv`; boş/metin olmayan değer atlanır; sonuç önbelleklenir (`Env::reset()` yalnız test içindir, `Env.php:118`).
3. **`Env`'in tek kapı olduğu iddiası tam doğru değil:** `ProjectDbProfileResolver::fromEnvironment()` `RBN_DB_PROFILE`'ı `Env` yerine `$_ENV ?? $_SERVER ?? getenv` zinciriyle okur (`ProjectDbProfileResolver.php:183`). Kodda bunun için yazılı bir gerekçe yok; okuma sırası `Env` ile aynıdır, fark yalnız değeri küçük harfe çevirip `local`/`production` dışındakini yok saymasıdır (`:189-195`).
4. **`Env::int()` hiçbir yerde çağrılmıyor** (`Core/Bundles/Packages/Resources` taramasında 0 çağrı); **`Config::set()` de 0 çağrı**. Yani çalışma anında ayar yazmak fiilen kullanılmıyor.
5. **`ConfigMap` neredeyse kullanılmıyor:** tek çağrı `Core/Render/Handlers/UI/PanelHandler.php:111` (`getAppSessionTimeout`).
6. **Sır dosyası izin denetimi Windows'ta atlanır** (`ConfigFileGuard.php:79-81`); Linux'ta 0600'den geniş izin `RuntimeException`'dır. Sır dosyasında `?>` kapanışı `return`'den önceyse reddedilir (aksi halde dosya `require` edilince ekrana basılır).
7. **`cpanel()` anahtarları dönüşür:** bölümde `host/user/token`, ama `Secrets::cpanel()` `cpanel_host/cpanel_user/cpanel_token` döndürür (kabul testleriyle sabit, `Secrets.php:152-156`).
8. **`master_db.pass` ve `smtp.pass` boş olabilir**, `cpanel` ve `api` değerleri boş olamaz (`SecretsSchema::SECTION_RULES`, `SecretsSections::readAll`).
9. **Geriye uyum yok:** eski iki ayrı sır dosyası kaldırıldı; `secrets.php` yoksa uygulama açık hata verir, sessiz `root`/`''` fallback yoktur.
10. **Sıra:** `Secrets` kernel öncesi çalıştığı için IoC/Paths kullanmaz, komşu dosyaları `require_once` eder (`Secrets.php:17-23`).
11. **Düzeltilen bayat bilgi:** `Core/System/Config/README.md` PSR-4 tablosu `DatabaseConfig`, `DbProfileResolver`, `SmtpProfileResolver`'ı `Engine/Config/` altında gösteriyor; gerçekte `Engine/Database/` altındadır, `ProjectDbProfileResolver` tabloda hiç yoktur (§2.4–2.5 doğrusudur). *(Eski `acik-sorular.md` §1.1.)*
12. **`TENANT_TABLES`**: tam liste 12 tekil tablo (11 paylaşılan `z_*`/`app_*` + 1 ürün tablosu); `asw_*` listede **yoktur** (`ProjectDbData.php:138-163`). *(Eski `acik-sorular.md` §2.2 — kodla doğrulandı.)*
13. **`MasterDbData::REQUIRED_TABLES` `z_sys_heartbeats` sayar** (`MasterDbData.php:54-60`) ama `MasterRbnHeartbeatsModel` `rbn_heartbeats` tablosunu bildirir; `Registry`'de yalnız `master.rbnHeartbeat` anahtarıyla kayıtlıdır ve ölçüm taramasında çağıran bulunmadı. Gerçek master'da hangisinin var olduğu `acik-sorular.md`'de duruyor.


### Ölçüm: yerel-ortam kararı ile DB profili aynı değil (PHP 8.3, Windows)

`PreBoot::isTrustedLocalEnvironment(host, REMOTE_ADDR)` ve `ProjectDbProfileResolver::activeProfile()` aynı süreçte çalıştırıldı:

| Host | REMOTE_ADDR | `isTrustedLocalEnvironment` |
|---|---|---|
| `rbncore.tr.test` | `127.0.0.1` | true |
| `rbncore.tr.test` | `203.0.113.9` (doküman IP'si) | **false** |
| `x.test.evil.com` | `127.0.0.1` | false |
| `localhost:8080` ve `[::1]` | `::1` | true |
| `a.test` | `::ffff:10.0.0.5` (IPv4-eşlenmiş) | true |
| `A.TEST.` | `192.168.1.2` | true |
| (boş) | `127.0.0.1` | false |

Aynı süreçte `REMOTE_ADDR=203.0.113.9`, `HTTP_HOST=rbncore.tr.test` iken `ProjectDbProfileResolver::activeProfile()` = **`local`**: DB profili istemci adresinden etkilenmez; `is_local()` ve `RBN_DEV` etkilenir. Bu, `ProjectDbProfileResolver` docblock'ındaki tasarım gerekçesini (aynı sunucuda istek kimliğine göre farklı veritabanına bağlanılmaması) kodla doğrular. *(Eski `acik-sorular.md` §2.6 — karar ayrımı yeniden ölçüldü. Docblock'taki "8 dosyada hata" sayısı kabul harness'inin ölçümüdür; harness bu görevin kapsamı dışında olduğundan o sayı yeniden üretilmedi.)*

## 6. Örnek (gerçek koddan)

```php
// Packages/RbnEmail/Handlers/EmailConfigHandler.php:64-65
'enabled' => $useMaster ? SmtpProfileResolver::enabled() : ...,
'host'    => $useMaster ? SmtpProfileResolver::host()    : ...,
```

```php
// DB profili: project-settings.php içinde tek dosyada iki profil
'DB_PROFILES' => [
    'local'      => ['DB_HOST' => …, 'DB_NAME' => …, 'DB_USER' => …, 'DB_PASS' => …, 'DB_CHARSET' => …],
    'production' => ['DB_HOST' => …, 'DB_NAME' => …, 'DB_USER' => …, 'DB_PASS' => …, 'DB_CHARSET' => …],
]
// (ProjectDbProfileResolver.php docblock'undan; "…" yerine gerçek değer yazılır, "__DOLDUR__" reddedilir)
```

## 7. İlgili belgeler

* [Kavram: yapılandırma](../../kavramlar/02-yapilandirma.md) · [Kavram: veritabanı ve kiracılık](../../kavramlar/03-veritabani-ve-kiracilik.md)
* [Core/System genel bakış](README.md) · [Kernel](Kernel.md) (`PreBoot` Env/Secrets'ı kullanır) · [Discovery](Discovery.md) (`Definition::get`, `ProjectDataMapper`)
* Kaynak içi notlar: `Core/System/Config/README.md`, `Secrets/README.md`, `Definitions/DbProfiles/README.md`
* [Açık sorular](../../acik-sorular.md)
