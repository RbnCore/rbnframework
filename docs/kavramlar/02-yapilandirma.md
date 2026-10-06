# 02 — YAPILANDIRMA

> **Bu belge hangi commit'e göre yazıldı:** `d4af18d` (dal `feat/fw-license-master`)
> **Son doğrulama tarihi:** 2026-10-05
> **Yayın tabanı:** 0.9.4 = bu commit + sonrası; belge yalnız doğrulama anındaki kodu anlatır
> **Kapsam:** Hangi ayar hangi dosyada, kim okuyor, öncelik sırası, ortam değişkenleri

---

## 1. Tek kapı kuralı (framework'in kendi kuralı)

`Core/System/Config/README.md:6-12` üç kapıyı tanımlar:

| Ne? | Nerede? | Tek okuyucu |
|---|---|---|
| **Gizli değer** (parola, `app_key`, jeton, API anahtarı) | `Secrets/secrets.php` + `Secrets.php` | `Secrets` |
| **Ortam değişkeni / bayrak / kill-switch** | `Env` + `EnvKeys` | `Env` |
| **Kod tanımlı yapılandırma** (sabitler, ayar tabloları) | `Config.php`, `Engine/Config/ConfigResolver.php`, `Engine/Config/ConfigFileLoader.php` | `ConfigResolver` / `ConfigFileLoader` |

`$_ENV` / `$_SERVER` / `getenv` **elle** okumak yasaktır; bu üç kaynak yalnız `Env`
içinde okunur (`Core/System/Config/README.md:30`, kaynak sırası
`$_ENV` → `$_SERVER` → `getenv`, `Core/System/Config/README.md:38`).

> **Kural:** Yeni bir sır okuyucusu yazarken `ConfigFileLoader`/`ConfigFileGuard`
> trait'lerini kullan, doğrulamayı `ConfigFileLoader::validate()`'a bırak,
> bölüm kurallarını `SecretsSchema::SECTION_RULES`'e koy
> (`Core/System/Config/README.md:121`).

---

## 2. `secrets.php` — gizli değerler

**Tek dosya:** `Core/System/Config/Secrets/secrets.php`
(`Core/System/Config/Definitions/SecretsSchema.php:31` → `BASE_DIR . FILE`;
depoya **giremez**, `.gitignore`'ludur).

Şablon: `Core/System/Config/Secrets/secrets.example.php` (depoda **kalır**;
`.github/UPGRADING.md:878-880`).

### 2.1 Bölümler ve zorunlu alanlar

`SecretsSchema::SECTION_RULES` (`SecretsSchema.php:61-74`):

| Bölüm | `required` | `nonEmpty` |
|---|---|---|
| `master_db` | `host`, `port`, `name`, `user`, `pass` | `host`, `port`, `name`, `user` (**`pass` hariç**) |
| `smtp` | `enabled`, `host`, `port`, `secure`, `user`, `pass`, `from_address`, `from_name` | hepsi (`pass` dahil) |
| `cpanel` | `host`, `user`, `token` | hepsi |

`pass`in `nonEmpty` listesinde olmaması bilinçlidir: boş master parolası gerçek bir
kurulumda geçerlidir (`SecretsSchema.php:55`). `smtp.enabled` **string** tutulur
(`'1'`/`'0'`), çünkü `normalize()` skalerleri metne çevirir ve `false` → `''` olurdu
(`SecretsSchema.php:56-57`).

Ek bölümler: `app_key` (64 karakter onaltılık metin;
`Core/System/Config/README.md:82`), `api` (servis başına anahtar/değer,
`Secrets::api('iyzico')`, `Core/System/Config/README.md:83`).

`master_db` ve `smtp` **opsiyonel** okuma yüzeyinde de düz anahtar üretir
(`SecretsSchema::OPTIONAL_SECTIONS = ['master_db','smtp']`, `SecretsSchema.php:81`;
`SecretsFlatApi`, `Core/System/Config/README.md:113`).

### 2.2 Okuma yüzeyi (`Secrets`)

`Core/System/Config/Secrets.php` **yalnız imzaları** taşır:

| Metot | Satır |
|---|---|
| `section(string $name): array` | `Secrets.php:105` |
| `masterDb(): array` | `Secrets.php:121` |
| `smtp(): array` | `Secrets.php:132` |
| `cpanel(): array` | `Secrets.php:148` |
| `api(string $name): array` | `Secrets.php:166` |
| `appKey(): string` | `Secrets.php:190` |
| `cpanelHost/cpanelUser/cpanelToken()` | `Secrets.php:210, 216, 222` |
| `setTestDirectory()` (yalnız test) · `resetCache()` | `Secrets.php:233, 245` |

Görev dağılımı (`Core/System/Config/README.md:107-115`):

| Sorumluluk | Dosya |
|---|---|
| Sır şeması (yalnız `public const`) | `Definitions/SecretsSchema.php` |
| Yol çözümü + okuma (TEK kapı) | `Engine/Secrets/SecretsLoader.php` |
| Doğrulama | `Engine/Secrets/SecretsValidator.php` |
| Bölüm kurgusu | `Engine/Secrets/SecretsSections.php` |
| Düz anahtar yüzeyi (geriye uyum) | `Engine/Secrets/SecretsFlatApi.php` |
| Ortak dosya tekniği | `Engine/Config/ConfigFileLoader.php` |
| 0600 izin + sızıntı kontrolü | `Engine/Config/ConfigFileGuard.php` |

### 2.3 Güvenlik sözleşmesi

* **Fail-closed:** dosya yoksa, bozuksa, zorunlu alan eksikse ya da içinde
  `CHANGE_ME` kaldıysa **açık hata** fırlatılır; sessiz varsayılana düşülmez
  (kök `README.md:87-89`; `CHANGE_ME` sabiti `SecretsSchema.php:41`).
* Hata mesajı **alan adını** söyler, **değeri asla yazmaz**
  (`Core/System/Config/README.md:119`).
* İzin tavanı `0600` (`SecretsSchema.php:49`).
* **Paketleme tuzağı:** `Core/System/Config/Secrets.php` (sınıf dosyası) ile
  `Core/System/Config/Secrets/secrets.php` (veri dosyası) **yalnız harf büyüklüğüyle**
  ayrılır. macOS/Windows'ta büyük/küçük harf duyarsız paketleme sınıf dosyasını da
  eleyebilir (`.github/UPGRADING.md:855-888`). Doğrulama komutları aynı yerde.

---

## 3. `project-settings.php` — proje ayarları

**Yol:** `projects/<custom_path>/Core/Config/project-settings.php`
(`ConfigResolver::resolve()` → `Paths::project()->configs('project-settings.php')`,
`Core/System/Config/Engine/Config/ConfigResolver.php:99-101`).

Dosya **tek düz dizi** döner (`return [...]`).

### 3.1 Ölçülen anahtar blokları

Yerel ölçüm (bir projenin dosyası): `DB_PROFILES` (`local` + `production`), düz API
anahtarı türü kayıtlar. `DB_PROFILES` yoksa düz `DB_HOST`/`DB_NAME`/`DB_USER`/
`DB_PASS`/`DB_CHARSET` anahtarları **aynen** çalışır (geriye uyum,
`ProjectDbProfileResolver.php:123-128`).

### 3.2 `DB_PROFILES` — ortam profili

```php
'DB_PROFILES' => [
    'local'      => ['DB_HOST' => …, 'DB_NAME' => …, 'DB_USER' => …, 'DB_PASS' => …, 'DB_CHARSET' => …],
    'production' => ['DB_HOST' => …, 'DB_NAME' => …, 'DB_USER' => …, 'DB_PASS' => …, 'DB_CHARSET' => …],
],
```

Zorunlu anahtarlar `ProjectDbData::KEYS_MAP` değerlerinden gelir
(`ProjectDbProfileResolver.php:65-68`). **Sessiz düşme yok:**

| Durum | Sonuç | Satır |
|---|---|---|
| `DB_PROFILES` yok/boş | Dosya **aynen** döner (eski davranış) | `ProjectDbProfileResolver.php:126-128` |
| Seçilen profil yok | `RuntimeException` — diğer profile düşülmez | `132-140` |
| Zorunlu anahtar eksik | `RuntimeException` — `root`/boş parola varsayılanı **yok** | `146-154` |
| Değer `__DOLDUR__` yer tutucusu | `RuntimeException` — bağlantı denenmez | `157-165` |

Yer tutucu sabiti: `ProjectDbProfileResolver::PLACEHOLDER = '__DOLDUR__'`
(`ProjectDbProfileResolver.php:58`).

Profil **seçimi** (tek merkez — `ProjectDbProfileResolver::activeProfile()`,
`ProjectDbProfileResolver.php:99-107`):

1. `RBN_DB_PROFILE` tam `local`/`production`; başka değer = **karar yok** (`180-196`).
2. Framework kökünde tam `localhost` yol segmenti → `local` (`205-216`).
3. Belirsizlik → `production` (fail-closed).

Bu seçimin `is_local()` ile **farkı** ve gerekçesi: [01-mimari-harita.md §8](01-mimari-harita.md).

> **TEK MERKEZ kuralı:** Profil seçme mantığı yalnız `ProjectDbProfileResolver`'da
> yazılıdır; çağıranlar: `DatabaseConfig::fromRaw()`
> (`Engine/Database/DatabaseConfig.php:68`), `DatabaseGuardProvider::loadCredentials()`,
> `ProjectDataMapper::buildProjectPdo()`, `ProjectCleanupJob::cleanProjectTables()`
> (`ProjectDbProfileResolver.php:35-39`).

### 3.3 `security.*` anahtarları

| Anahtar | Nerede okunuyor | Varsayılan |
|---|---|---|
| `security.mass_assignment` | `MassAssignmentTrait::MASS_ASSIGNMENT_FLAG` (`Core/Base/Data/Traits/Model/Engine/MassAssignmentTrait.php:42`), `massAssignmentMode()` (`183-185`) | `on` |
| `security.protected_field_log` | `protectedFieldLogEnabled()` (`MassAssignmentTrait.php:205-207`) | `on` |
| `security.model_not_scoped_log` | `QueryModelTrait::MODEL_NOT_SCOPED_LOG_FLAG` (`QueryModelTrait.php:39`) | `on` |
| `security.form_input_mode` | `request->form($kurallar)` daraltma anahtarı (`off`/`log`/`enforce`); varsayılan `enforce`, `.github/UPGRADING.md:486-546` | `enforce` |

### 3.4 `proxy_allowed_hosts`

`Config::get('project-settings.proxy_allowed_hosts')` → host dizisi
(`Core/Render/Controllers/AssetController.php:275`; açıklama `223, 252`).
Kurallar: eleman **yalnız string**; şema/port/yol/boşluk/kontrol karakteri içeren
elemanlar **sessizce yok sayılır**; dosya okunamazsa liste **boş** kalır (fail-closed)
(`.github/UPGRADING.md:846-853`). Motor genel kalır: framework'e liste yazılmaz.

---

## 4. `project-routemap.php` — site (view_mapping) haritası

**Yol:** `projects/<custom_path>/Core/Config/project-routemap.php`
(`ResolvesProjectConfigTrait::getRouteConfig()` → `Paths::project()->configs('project-routemap.php')`).

Yapı:

```php
return [
    'view_mapping' => [
        '<project_key>' => [ /* site ayarları */ ],
        // … aynı klasördeki diğer site anahtarları
    ],
];
```

`view_mapping` **üst düzey anahtardır**; site ayarları o alt dizinin içindedir.
Aynı `custom_path`e sahip her `project_key` **kendi** bloğunu alır (ölçülen örnek:
bir proje klasöründe 3 anahtar, 3 blok).

### 4.1 Ölçülen anahtarlar ve okuyanları

| Anahtar | Okuyan (kod) | Not |
|---|---|---|
| `module` | modül çözümü (`view_mapping[…]['module']`) | Modül adı; örn. `<Modul>` |
| `domain` | `getRouteConfig($key,'domain')` → `targetProjectUrl()` (`ResolvesProjectConfigTrait.php:116-118`) | Alan adı |
| `dashboard_prefix` | `ProjectDataMapper.php:214, 220-221` → `project_data('dashboard_prefix')`; tüketiciler: `RouteManager.php:159, 192, 223, 295`, `SessionSandboxStage.php:366`, `PanelHandler.php:46`, `ActionControllerTrait.php:126`, `ViewHelperTrait.php:84`, `AuthViewController.php:36`, `SystemGuardHandler.php:53` | Boşsa framework sabiti `dashboard` (`RouteBlueprint.php:99`) |
| `css_engine` | `AssetBuilder.php:251` (`getRouteConfig($projectKey, 'css_engine')`) | |
| `robots` | `SeoResolver::getSeoConfig()` (`Core/Render/Resolvers/SeoResolver.php:378-382`) → `RobotsResolver.php:49-52, 89-91` | `disallow` / `allow` dizileri; `noindex` (bool) = SİTE düzeyi noindex: sitemap/llms/feed 404, `robots.txt` `Disallow: /` (FW-094-NOINDEX; `Resolvers.md` madde 7) |
| `proxy_allowed_hosts` | `AssetController.php:275` | §3.4 |
| `favicon`, `og-image` / `og_image` | **toler edilir ama önceliksizdir** (`.github/UPGRADING.md:100-103`) | Kural `AssetConvention` ile gelir |
| `bundles`, `api_google`, `api_gemini`, `content`, `user_dash_controller`, `cookie`, `admin_panel_disabled` | proje/modül kodu | `admin_panel_disabled` ayrıca `ProjectDataMapper.php:215, 354-359` |

### 4.2 ⚠️ En sık yapılan hata: **boş dizi yazmayı unutmak**

`Config.php`, `view_mapping[<project_key>]` değerlerini `project-settings` üstüne
**tam anahtar olarak** yazar — **derin birleştirme yoktur**
(`Core/System/Config/Config.php:88-97`):

```php
if ($filename === 'project-settings') {
    $activeKey = function_exists('active_project_key') ? active_project_key() : '';
    if (!empty($activeKey)) {
        $instance = new class { use \Rbn\Framework\Core\Base\Concerns\Data\ResolvesProjectConfigTrait; };
        $overrides = (array) ($instance->getRouteConfig($activeKey) ?? []);
        foreach ($overrides as $overrideKey => $overrideVal) {
            self::$items[$filename][$overrideKey] = $overrideVal;   // TAM anahtar
        }
    }
}
```

Sonuç: `robots` gibi bir anahtarı **yazmayan** site, proje klasöründe **başka bir
sitenin** değerini devralır. Bu yüzden o modülde yolu olmayan site için
`'robots' => ['disallow' => []]` **boş dizi olarak açıkça yazılır**
(ölçülen örnek: `projects/customers/<proje>/Core/Config/project-routemap.php`
içindeki iki alt-site bloğunda bu gerekçeyle yazılmış).

`has_route_map` bayrağı **artık okunmuyor** (`Config.php:80-87`;
`.github/UPGRADING.md:105-127`). Dosyada kalması davranışı değiştirmez.

### 4.3 Rotorut okuma adresi

`ResolvesProjectConfigTrait::getRouteConfig($projectKey, $key = null)`
(`Core/Base/Concerns/Data/ResolvesProjectConfigTrait.php:71-86`): dosya
`require` edilir ve `static::$routeMaps[$routeMapPath]` içinde **süreç önbelleğine**
alınır. Önbelleği temizleme: `resetRouteMapCache()` (tek kopya) ve
`resetAllRouteMapCaches()` (tüm sınıflar) (`ResolvesProjectConfigTrait.php:45-64`).

---

## 5. Ayar öncelik / birleştirme sırası

`Config::get($key, $default)` (`Core/System/Config/Config.php:56-114`):

1. **Özyineleme koruması**: çözüm sürüyorsa **varsayılan** döner (`59-61`).
2. **Özel kategoriler**: `database`, `database_master`, `database_common`,
   `database_project` → `ConfigResolver::resolveContext()` (`67-69`).
3. **Nokta gösterimi**: `a.b.c` → dosya `a`, kalan yol `b.c` (`72-73`).
4. Dosya **yoksa** boş dizi (`75-77`).
5. `project-settings` ise ve `active_project_key()` boş değilse routemap
   **tam anahtar** birleştirmesi yapılır (`88-97`) — §4.2.
6. Anahtar yoksa **varsayılan** döner (`101-106`).
7. **Tüm `Throwable` yakalanır ve varsayılan döner** (`109-111`).
   Yani okunamayan/bozuk bir ayar dosyası **sessizce** varsayılana düşer —
   ayar katmanında fail-closed **yoktur** (sır ve DB katmanında vardır).

**`Config::set($key, $value)`** (`Config.php:119-137`) yalnız **belleği** değiştirir
(`self::$items`); dosyaya yazmaz.

**Öncelik özeti:**

```
routemap view_mapping[<project_key>][X]      (tam anahtar; X'in üstüne yazar)
        ↓
project-settings.php'deki X
        ↓
kod varsayılanı (ConfigMap / RouteBlueprint / RobotsConfig …)
```

`project_key` verilmemişse `Config::get('project-settings')` **routemap uygulaması
olmadan** okunur (`Config.php:89-90` → boş anahtar → blok atlanır).

---

## 6. Ortam değişkenleri (`EnvKeys`)

`Core/System/Config/Definitions/EnvKeys.php` — **metot yok, yalnız sabitler**
(`EnvKeys.php:12`).

### 6.1 API

```php
Env::string('APP_ENV');                    // ?string  (tanımsız/boş -> null)
Env::string('APP_ENV', 'production');     // ?string  (varsayılanlı)
Env::flag('RBN_GUARD_FAILCLOSED', true);  // bool     (kill-switch: varsayılan true)
Env::int('TG_SEND_DELAY_MS', 0);          // ?int
```

* **Tembel:** değer ilk okunduğunda bir kez çözülür; `Env::reset()` **yalnız test**
  (`Core/System/Config/README.md:51`).
* **Kayıtsız ad = hata:** `EnvKeys::ALL_KEYS` dışındaki ad `RuntimeException` ile
  reddedilir (`Core/System/Config/README.md:52`).
* **Gizli ad:** hata mesajı yalnız **adı** taşır, **değeri** değil
  (`Core/System/Config/README.md:53`); liste `EnvKeys::SECRET_KEYS` (`EnvKeys.php:159`).
* **Bayrak yorumu TEK merkezden:** `0 | false | off | no | hayir` = **kapalı**,
  diğer her şey **açık** (fail-closed) (`EnvKeys.php:152` `OFF_VALUES`).

### 6.2 Kayıtlı adlar (koddan)

`EnvKeys.php:49-138`:

`APP_KEY`, `ENCRYPTION_KEY`, `RBN_LEGACY_SALT`, `RBN_ALLOW_LEGACY_SALT`,
`COMMON_DB_USER`, `COMMON_DB_PASS`, `DB_USER`, `DB_PASS`, `RBN_DB_PROFILE`,
`RBN_GUARD_FAILCLOSED`, `RBN_DEBUG`, `RBN_DEV`, `APP_ENV`, `RBN_LOG_THROTTLE`,
`TG_SEND_DELAY_MS`.

### 6.3 `EnvKeys` ≠ `ConfigMap`

| | `Definitions/EnvKeys.php` | `Definitions/ConfigMap.php` |
|---|---|---|
| Konusu | Ortam değişkeni olarak **okunan** adlar | Framework'ün **global tanımladığı** ayar anahtarları |
| Örnek | `APP_ENV`, `TG_SEND_DELAY_MS` | `app.debug`, `app.logging`, `app.env`, `app.is_cli`, `app.session_timeout` |
| Okuyan | `Env` | `ConfigMap::getAppDebug()` vb. |

İkisi de **kalır**, birleştirilmez (`Core/System/Config/README.md:93-102`).

`ConfigMap` her değeri **sabit öncelikli** okur:
`getAppDebug()` → `defined('RBN_DEBUG') ? RBN_DEBUG : (get('app.debug') ?? false)`
(`ConfigMap.php:23-25`); `getAppEnv()` → `RBN_DEV` (`ConfigMap.php:41`);
`getAppIsCli()` → `defined('RBN_CLI')` (`ConfigMap.php:50`);
`getAppSessionTimeout()` → `RBN_SESSION_TIMEOUT`, varsayılan `30` **dakika**
(`ConfigMap.php:56-60`).

`RBN_CLI`, `RBN_SESSION_TIMEOUT`, `RBN_PANIC_ACTIVE` **PHP sabitidir**
(`define()`), ortam değişkeni **değildir**; bu yüzden `EnvKeys`'e yazılamaz
(`Core/System/Config/README.md:83-87`).

### 6.4 Kayıt kanıtı şartı

`EnvKeys`'e eklenen her ad için kodda **gerçekten** `Env::string()/flag()/int()` ile
okunduğu kanıtlanmalıdır; okunmayan ya da `define()` sabiti olan ad kaydedilmez
(`Core/System/Config/README.md:39`).

---

## 7. Master DB `settings` tablosu

**Kolonlar (ölçüldü, `db:schema settings --master`):**
`id`, `type` (varsayılan `system`), `category`, `setting_name`, `setting_key` (indeksli),
`setting_value` (text), `description`, `key_type` (`enum('free','paid')`),
`is_active` (varsayılan `1`), `created_at`, `updated_at`.

> **Düzeltme:** "gruplar" adı iki kolonla temsil edilir: **`type`** ve **`category`**
> (bir `group`/`group_name` kolonu **yoktur** — `SELECT … group_name` ölçümü
> `Unknown column` hatası verdi).

Ölçülen dağılım (yerel master): `type=system, category=api` → 6 satır;
`type=system, category=notification` → 1 satır; `type=api, category=api` → 1 satır.

**Okuma yolu:** `ProjectDataMapper::assemble()` master `settings` satırlarını
`setting_key → setting_value` haritasına çevirip `project_data` içine koyar
(`Core/System/Discovery/Engine/Cache/ProjectDataMapper.php:226-228`).
Yani master `settings` **doğrudan `Config::get()` okumaz**; önbellekten gelen
`project_data` üzerinden okunur.

---

## 8. "Bir ayar nereden okunur?" — karar tablosu

| Ayar | Kaynak | Okuyan | Öncelik notu |
|---|---|---|---|
| Master DB host/port/ad/kullanıcı/parola | `secrets.php` → `master_db` | `DbProfileResolver::host/port/databaseName/user/password` (`Engine/Database/DbProfileResolver.php:66-168`) | Ortam değişkeni yolu **yok** (`Core/System/Config/README.md:80-82`) |
| Ortak DB kullanıcı/parola | `secrets.php` (`master_db`) + `COMMON_DB_USER`/`COMMON_DB_PASS` | `DbProfileResolver.php:124-131, 150-154` | Ortam değişkeni **yedek** |
| Proje DB bağlantısı | `project-settings.php` → `DB_PROFILES` veya düz `DB_*` | `ProjectDbProfileResolver::resolve()` → `DatabaseConfig` | Ortam `DB_USER`/`DB_PASS` **yedek** (`DbProfileResolver.php:129-131, 155-156`) |
| SMTP | `secrets.php` → `smtp` | `Engine/Database/SmtpProfileResolver.php` (`Core/System/Config/README.md:131`) | `enabled` string `'1'/'0'` |
| cPanel | `secrets.php` → `cpanel` | `Secrets::cpanel*` (`Secrets.php:210-222`) | — |
| Uygulama anahtarı | `secrets.php` → `app_key` **önce**, `APP_KEY`/`ENCRYPTION_KEY` sıradan | `CryptoHelper::resolveKey()` (`Core/System/Config/README.md:61-62`) | — |
| Site modülü, alan adı, favicon/og, panel ön eki, CSS motoru, robots, dış CDN beyaz listesi | `project-routemap.php` → `view_mapping[<project_key>]` | §4.1 | `project-settings` üstüne **tam anahtar** |
| Panel ön eki (boşsa) | Framework sabiti | `RouteBlueprint::DASHBOARD_PREFIX = 'dashboard'` (`RouteBlueprint.php:99`) | — |
| Panel ön eki (bakım modu muafiyeti) | routemap → BootCache → `project_data` → sabit | `SystemGuardHandler.php:50-53` | `project-settings` **okunmuyor** |
| API anahtarları | `secrets.php` → `api` (`Secrets::api($sağlayıcı)`) veya proje `options` tablosu | `ProjectDataMapper.php:246-274` | `bot_activity` **ve** `use_master_api` açıksa master API yedeği devreye girer |
| Proje sürümü | master DB `projects.version` → önbellek → `project_data('version')` | `ProjectVersionResolver` → `app_version()` / `APP_VERSION` | [04-surumleme-ve-yayin.md](04-surumleme-ve-yayin.md) |
| `app.debug`, `app.env`, `app.is_cli`, oturum süresi | `ConfigMap` (sabit öncelikli) | `ConfigMap.php:23-60` | §6.3 |
| Karma (sahte) `Options`'dan gelen anahtarlar | `options` tablosu (`group_key` = `api`/`bot`) | `ProjectDataMapper.php:246-254` | `bot_activity` açık değilse **okunmaz** |

---

## 9. Sır, ortam değişkeni ve ayar dosyası **değil** olanlar

Salt-okunur tanımlar ortam değişkeni **değildir**, `EnvKeys`'e yazılmaz
(`Core/System/Config/README.md:91`): `HOST`, `CHARSET`, `ENV_FILE`, `APP_NAME`,
`APP_VERSION`, `APP_URL`, `DEFAULT_LANGUAGE`, `AssetDefinition` içindeki
`RBN_*_CSS` / `RBN_*_JS` varlık sabitleri, `ApiKeysRegistry` içindeki isim eşlemeleri.

---

## 10. Bilinmeyenler

* `Core/System/Config/README.md:20-21` PSR-4 tablosu `Engine/Config/` içinde
  `DatabaseConfig` ve `DbProfileResolver` listeliyor; **kodda** bu sınıflar
  `Engine/Database/` altındadır (`Get-ChildItem` ile ölçüldü). Belge **eskidir**;
  yol tahmini yapılmamalıdır.
* `project-settings.php` içinde **hangi** anahtarların geçerli olduğuna dair tek bir
  şema sabiti **yoktur** — dosya düz dizi döner ve `Config::get()` anahtarı
  tanımaz, yalnız **yoksa varsayılan** döner.
* `Config::get()` sırasında `ConfigResolver::guard()` **her zaman `null`** döner
  (`Config::get()` `setResolving(true)` yapar, `Config.php:63`; `guard()`
  `isResolving` iken null döner, `ConfigResolver.php:53-58`). Bu yüzden
  `database` dışındaki her ad **survival fallback**'e düşer:
  `projects/<custom_path>/Core/Config/<ad>.php` (`ConfigResolver.php:107-109`).
  Yani `database_master` **dışında** hiçbir ad `DatabaseGuardHandler`'ın
  bütünlük denetimine uğramaz.
