# 02 — YAPILANDIRMA

> **Bu belge hangi commit'e göre yazıldı:** `d4af18d` (dal `feat/fw-license-master`)
> **Son doğrulama tarihi:** 2026-10-09 (FW-096-D8: ortam değişkeni katmanı kaldırıldı)
> **Yayın tabanı:** 0.9.6 = bu commit + sonrası; belge yalnız doğrulama anındaki kodu anlatır
> **Kapsam:** Hangi ayar hangi dosyada, kim okuyor, öncelik sırası, ortam bayrağı (`app` bölümü)

---

## 1. Tek kapı kuralı (framework'in kendi kuralı)

`Core/System/Config/README.md` ("Tek cümle kural") üç kapıyı tanımlar:

| Ne? | Nerede? | Tek okuyucu |
|---|---|---|
| **Gizli değer** (parola, `app_key`, jeton, API anahtarı) | `Secrets/secrets.php` + `Secrets.php` | `Secrets` |
| **Ortam bayrağı / kill-switch / çalışma ayarı** | `secrets.php` → `app` bölümü | `Secrets::app()` |
| **Kod tanımlı yapılandırma** (sabitler, ayar tabloları) | `Config.php`, `Engine/Config/ConfigResolver.php`, `Engine/Config/ConfigFileLoader.php` | `ConfigResolver` / `ConfigFileLoader` |

Framework işletim sistemi ortam değişkeni **okumaz** (ADR: `Core/System/Config/README.md`
→ "Ortam değişkeni YOKTUR"). `getenv()` / `$_ENV` / dotenv dosyası / web sunucusu ortam
yönergesi bu framework'ün mekanizması değildir; ortam bayrağı dahil bütün anahtarlar
TEK dosyada: `Core/System/Config/Secrets/secrets.php`.

> **Kural:** Yeni bir sır okuyucusu yazarken `ConfigFileLoader`/`ConfigFileGuard`
> trait'lerini kullan, doğrulamayı `ConfigFileLoader::validate()`'a bırak,
> bölüm kurallarını `SecretsSchema::SECTION_RULES`'e koy
> (`Core/System/Config/README.md`, "`Secrets.php` neden bu kadar kısa?").

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
| `auth_registration` | `AuthPolicy::registrationEnabled()` (`Core/Http/Security/AuthPolicy.php`) → `Core/Routes/Mappings/auth.php`, `RouteManager::checkProjectQueryGuard()` | Anahtar yoksa açık. Açan değerler yalnız `true`, `1`, `on`, `yes`, `evet`, `acik`, `açık` (`AuthPolicy::REGISTRATION_OPEN_VALUES`); başka her değer (`false`, `kapalı`, `disabled`, yazım hatası) ve okunamayan ayar **kapalı** → `register`, `kayit`, `POST auth/register` rotaları kaydedilmez (404). Okuma hatası `security` günlüğüne `AUTH_REGISTRATION_SETTING_UNREADABLE` |
| `auth_user_home` | `AuthPolicy::userHomePath()` → `AuthService::login()` | Yönetici olmayan girişin dönüş yolu (302 ve JSON `redirect`); varsayılan `/user`. Yalnız `/` ile başlayan yerel yol kabul edilir |
| `session_absolute_timeout` | `AuthPolicy::sessionAbsoluteMinutes()` → `AuthMiddleware` | `/user/*` için sunucu tarafı mutlak süre (dakika), varsayılan 720. Boşta süresi bu dosyada değil: panel "Oturum Süresi" (`security.session_timeout`, DB) → `AuthPolicy::sessionIdleMinutes()`; ayar yoksa 30 (`ConfigMap::getAppSessionTimeout()`). |

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

## 6. Ortam bayrağı ve çalışma ayarları (`secrets.php` → `app`)

Ortam değişkeni katmanı (`Env.php` + `EnvKeys.php`, 16 ad) FW-096-D8 ile **kaldırıldı**.
Şema: `Core/System/Config/Definitions/SecretsSchema.php` (`APP_DEFAULTS`, `APP_ALLOWED`);
okuyucu: `Secrets::app()` → `Engine/Secrets/SecretsSections::app()`.

### 6.1 Alanlar

| Alan | Tip · güvenli varsayılan | Okuyan | Eski ortam değişkeni |
|---|---|---|---|
| `environment` | `'production'` \| `'development'` · **production** | `PreBoot::isProductionDeclared()` | `APP_ENV`, `RBN_ENV` |
| `debug`, `dev` | `bool` · `false` | `PreBoot::detectEnvironment()` | `RBN_DEBUG`, `RBN_DEV` |
| `guard_failclosed` | `bool` · `true` | `SystemGuardHandler::resolveFailClosed()` | `RBN_GUARD_FAILCLOSED` |
| `log_throttle` | `bool` · `true` | `LogThrottle::enabled()` | `RBN_LOG_THROTTLE` |
| `db_profile` | `''` \| `'local'` \| `'production'` · `''` | `ProjectDbProfileResolver::activeProfile()` | `RBN_DB_PROFILE` |
| `tg_send_delay_ms` | `int` · `0` (yalnız test) | sroweb Telegram test router'ı | `TG_SEND_DELAY_MS` |
| `allow_legacy_salt` | `bool` · `false` | `CryptoHelper::legacySaltApproval()` | `RBN_ALLOW_LEGACY_SALT` |

Sır niteliğindekiler `app` bölümünde **değildir**: `app_key` (eski `APP_KEY`/`ENCRYPTION_KEY`),
`legacy_salt` (eski `RBN_LEGACY_SALT`) üst düzey; ortak DB kimliği `master_db`
(eski `COMMON_DB_*`); proje DB kimliği `project-settings.php`, yedek `db_user`/`db_pass`
(eski `DB_*`).

* Bölümün **tamamı opsiyoneldir**; yazılmayan alan varsayılanı alır.
* Değer tipi PHP tipidir; tipi tutmayan ya da izinli olmayan değer varsayılana düşer ve
  loglanır. Metin yorumlayan "kapalı listesi" **yoktur** (`'banal'` debug açmaz).
* Hata/uyarı metni değer yazmaz, yalnız alan adını yazar.

### 6.2 Ortam ve hata ayıklama kapısı

| `app.environment` | Hata ayıklama (`RBN_DEBUG` sabiti) |
|---|---|
| `'production'` | **İstekten açılmaz** (Host/IP bakılmaz; ters vekil `Host: localhost` iletse de kapalı). Yalnız `app.debug`/`app.dev` = `true` açar |
| yazılmamış / geçersiz / dosya okunamadı | **production** kabul edilir; `error_log`'a süreç başına bir kez uyarı |
| `'development'` | Yalnız güvenilir yerel ortam (`localhost`/`.test` + yerel IP) açar; gerçek alan adında kapalı |

Canlıda `secrets.php`: `'app' => ['environment' => 'production']`. Yerel makinede
`'app' => ['environment' => 'development']`.

### 6.3 `ConfigMap` ayrımı

`ConfigMap` her değeri **sabit öncelikli** okur:
`getAppDebug()` → `defined('RBN_DEBUG') ? RBN_DEBUG : (get('app.debug') ?? false)`;
`getAppEnv()` → `RBN_DEV`; `getAppIsCli()` → `defined('RBN_CLI')`;
`getAppSessionTimeout()` → `RBN_SESSION_TIMEOUT`, varsayılan `30` **dakika**
(`ConfigMap.php`). `RBN_CLI`, `RBN_SESSION_TIMEOUT`, `RBN_PANIC_ACTIVE` **PHP sabitidir**
(`define()`), ortam değişkeni değildir.

### 6.4 Yeni çalışma anahtarı

`SecretsSchema::APP_DEFAULTS`'a bir satır (+ metinse `APP_ALLOWED`) ve
`secrets.example.php` `app` bölümüne örnek. Başka dosya açılmaz.

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
| Ortak DB kullanıcı/parola | `secrets.php` → `master_db` (master ile aynı hesap) | `DbProfileResolver::user/password` | Ortam değişkeni yolu **yok** (FW-096-D8) |
| Proje DB bağlantısı | `project-settings.php` → `DB_PROFILES` veya düz `DB_*` | `ProjectDbProfileResolver::resolve()` → `DatabaseConfig` | Kimlik yedeği `secrets.php` `db_user`/`db_pass` (`Secrets::optional`); parola yoksa `RuntimeException`; ortam değişkeni yolu **yok** |
| SMTP | `secrets.php` → `smtp` | `Engine/Database/SmtpProfileResolver.php` (`Core/System/Config/README.md:131`) | `enabled` string `'1'/'0'` |
| cPanel | `secrets.php` → `cpanel` | `Secrets::cpanel*` (`Secrets.php:210-222`) | — |
| Uygulama anahtarı | `secrets.php` → `app_key` **önce**, `APP_KEY`/`ENCRYPTION_KEY` sıradan | `CryptoHelper::resolveKey()` (`Core/System/Config/README.md:61-62`) | — |
| Site modülü, alan adı, favicon/og, panel ön eki, CSS motoru, robots, dış CDN beyaz listesi | `project-routemap.php` → `view_mapping[<project_key>]` | §4.1 | `project-settings` üstüne **tam anahtar** |
| Panel ön eki (boşsa) | Framework sabiti | `RouteBlueprint::DASHBOARD_PREFIX = 'dashboard'` (`RouteBlueprint.php:99`) | — |
| Panel ön eki (bakım modu muafiyeti) | routemap → BootCache → `project_data` → sabit | `SystemGuardHandler.php:50-53` | `project-settings` **okunmuyor** |
| API anahtarları | `secrets.php` → `api` (`Secrets::api($sağlayıcı)`) veya proje `options` tablosu | `ProjectDataMapper.php:246-274` | `bot_activity` **ve** `use_master_api` açıksa master API yedeği devreye girer |
| Proje sürümü | master DB `projects.version` → önbellek → `project_data('version')` | `ProjectVersionResolver` → `app_version()` / `APP_VERSION` | [04-surumleme-ve-yayin.md](04-surumleme-ve-yayin.md) |
| `app.debug`, `app.is_cli`, oturum süresi (`ConfigMap` getter'ları) | `ConfigMap` (sabit öncelikli) | `ConfigMap.php` | §6.3 |
| Karma (sahte) `Options`'dan gelen anahtarlar | `options` tablosu (`group_key` = `api`/`bot`) | `ProjectDataMapper.php:246-254` | `bot_activity` açık değilse **okunmaz** |

---

## 9. Sır, ortam değişkeni ve ayar dosyası **değil** olanlar

Salt-okunur tanımlar ortam değişkeni **değildir**: `HOST`, `CHARSET`, `ENV_FILE`, `APP_NAME`,
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
