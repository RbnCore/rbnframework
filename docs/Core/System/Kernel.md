# Core/System/Kernel — Açılış zinciri (PreBoot → aşamalar → rota)

> **Doğrulanan kod tabanı:** `d508f5e1` (dal `feat/fw-license-master`) · **Tarih:** 2026-10-05 · **Yayın:** 0.9.5 = bu commit + sonrası; belge yalnız doğrulama anındaki kodu anlatır
> **Kaynak klasör:** `Core/System/Kernel/` — 19 `*.php` (kök 2, `Base/` 5, `Guards/` 5, `Stages/` 7).
> **Envanter:** 19 dosyanın 19'u aşağıda anlatıldı.

## 1. Ne işe yarar, kim kullanır

Her HTTP isteğinin açılış omurgasıdır. Giriş dosyası (`index.php`) `Bootstrap::run($publicPath, $config)` çağırır; sıra: PHP sürüm kapısı → hata görünürlüğü ve temel güvenlik başlıkları → `PreBoot::orchestrate()` (ortam, proje verisi, `Paths`, otoload) → `KernelFactory::create()` ile 6 aşamalı `Kernel` → `Kernel::boot()` → `Route::run()` → `Kernel::terminate()`. Hata olursa `BootSentinel` ve son çare `renderTerminalFailure()` devreye girer.

**Kimler çağırır:** projelerin `index.php` dosyaları (`Bootstrap::run`), CLI girişi (yalnız `PhpVersionGate`), `Config`/`Paths`/`Discovery` (erken boot sabitleri için).

## 2. Klasör/dosya envanteri

### 2.1 Kök

| Dosya | Görev | Önemli public yöntemler |
|---|---|---|
| `Bootstrap.php` | Web isteği giriş noktası; hata/başlık/uygulama bağlamı. | `static run(string $publicPath, array $config = []): void`, `renderTerminalFailure(\Throwable)`, `configureErrorVisibility()`, `sendBaseSecurityHeaders()`, `getAppContext(?string $key = null, mixed $default = null)`, `setAppContext(string $key, mixed $value)` |
| `Kernel.php` | Aşama çalıştırıcı + olay kancaları + bağlam deposu. | `__construct(string $publicPath, array $config = [])`, `addHook(string $name, callable)`, `triggerHook(string $name, ...$args)`, `addStage(StageInterface): self`, `boot()`, `terminate()`, `getConfig()`, `set(string $key, mixed $value)`, `get(string $key, mixed $default = null)` |

### 2.2 `Base/`

| Dosya | Görev | Yöntemler |
|---|---|---|
| `PreBoot.php` | Kernel'den önceki zincir: ortam sabitleri, proje keşfi, `Paths`, sürüm, sentinel, otoload, hızlı varlık. Ayrıca host/IP/hassas-anahtar yardımcıları. | `orchestrate(string $publicPath, array $config = [])`, `detectEnvironment()`, `defineAppVersion()`, `normalizeHost(string): string`, `isLocalClientAddress(string): bool`, `isLoopbackAddress(string): bool`, `isTrustedLocalEnvironment(string $host, string $addr): bool`, `isSensitiveKey(string): bool`, `maskSensitiveData(array): array`; sabitler `LOCAL_HOST_ALLOWLIST`, `MASK='***'` |
| `KernelFactory.php` | Aşama sırasını kuran tek yer. | `static create(string $publicPath, array $config = []): Kernel` |
| `PhpVersionGate.php` | PHP ≥ 8.3 kapısı (autoload'dan önce çalışabilen bağımsız sınıf). | `isSupported($versionId)`, `versionLabel($versionId)`, `message($versionId)`, `enforce($versionId = null, $sapi = null)`; sabitler `MIN_VERSION_ID=80300`, `MIN_VERSION_LABEL='8.3'` |
| `BaseGuard.php` | Bekçi soyut sınıfı (`GuardInterface`). | `abstract static check(): void`, `protected static fail(string $msg, string $hint, string $type = 'RbnShield', int $code = 500)` (→ `PreflightException`) |
| `BaseStage.php` | Aşama soyut sınıfı (`StageInterface`). | `abstract handle(Kernel $kernel): void` |

### 2.3 `Guards/` — bekçiler (`BaseGuard`'tan türer, Watchdog hariç)

| Dosya | Görev | Yöntemler |
|---|---|---|
| `SystemDoctor.php` | `vendor/autoload.php` var mı, proje klasörü var mı, `PermissionDoctor`, Apache'de `.htaccess` var mı. | `static check()`, `static setExpectedKey(?string)` |
| `PermissionDoctor.php` | `FolderMatrix::PROJECT` ağacındaki klasörleri gerektiğinde oluşturur, `Storage`/`uploads`/`public/images` yazılabilir mi denetler. | `static check()`, `static skippedFolders(): array` |
| `MaintenanceGuard.php` | `systemGuard` servisine devreder (bakım kontrolü). | `static check()` |
| `AssetDoctor.php` | `framework-assets/…` ve `project-assets/…` isteklerini erken karşılar (hızlı varlık yolu). | `static check()` |
| `Watchdog.php` | Sınıf dosyası ama içeride yalnız koşullu bildirim: `Watchdog` (yalnız `$inPanic` bayrağı) ve global `rbn_panic()` işlevi. | `rbn_panic(string $message, ?string $type, ?array $stack): void` |

### 2.4 `Stages/` — açılış aşamaları (`BaseStage`'ten türer)

| Dosya | Görev | Yöntemler |
|---|---|---|
| `Autoload.php` | Composer yükleyicisine proje ve çatı PSR-4 önekleri ekler. | `handle(Kernel)`, `static boot(?Kernel = null): ClassLoader` |
| `ShieldSentinel.php` | `SystemDoctor` + `MaintenanceGuard` + `shield()` varlığı + `ipGuard` servisi. | `handle(Kernel)` |
| `DatabaseGuardStage.php` | `databaseGuard` servisinin `check()`'ini çağırır. | `handle(Kernel)` |
| `ComponentRegistry.php` | Exception servisini kaydeder ve `SystemRegistry` takma adlarını `class_alias` ile kurar. | `handle(Kernel)` |
| `SessionSandboxStage.php` | Oturum başlatma, çerez bayrakları, oturum ilkesi muafiyeti, proje değişimi senkronu. | `handle(Kernel)` |
| `Routing.php` | Çatı rota dosyalarını ve paket/proje rotalarını yükler. | `handle(Kernel)` |
| `ProjectDiscovery.php` | Host/proje anahtarından proje verisini önbellek→`ProjectDataMapper` ile verir. | `static discover(): ?string`, `static getProjectData(?string $publicPath = null, ?string $projectKey = null): array`; sabit `EXTRA_CACHE_KEYS` |

(`ProjectDiscovery` aşama değildir; `PreBoot` ve `SessionSandboxStage` çağırır, `KernelFactory`'de **yoktur**.)

**Sayım:** kök 2 + `Base/` 5 + `Guards/` 5 + `Stages/` 7 = **19** (`find Core/System/Kernel -name '*.php' | wc -l` = 19).

## 3. Akış

### 3.1 `Bootstrap::run` (`Bootstrap.php:19-60`)

1. `PhpVersionGate::enforce()` — sürüm < 8.3 ise HTTP 503 (CLI'da stderr + `exit(1)`).
2. `configureErrorVisibility()` (ortam bilinmeden **güvenli varsayılan: ekranda hata kapalı**, `log_errors=1`, `error_reporting(E_ALL)`; `:97-103`) ve `sendBaseSecurityHeaders()` (`X-Powered-By` kaldırılır; `X-Content-Type-Options: nosniff`, `Referrer-Policy: strict-origin-when-cross-origin` yalnız yoksa eklenir; CLI'da atlanır; `:112-132`).
3. `PreBoot::orchestrate()`.
4. `configureErrorVisibility()` ikinci kez: artık `RBN_DEV` biliniyorsa `display_errors=1`.
5. `ob_start()`, `KernelFactory::create()`, `$kernel->boot()`, `Route::run()`, `$kernel->terminate()`.
6. Her `Throwable`: `BootSentinel::dispatch()` → `renderTerminalFailure()`: hata kimliği (`bin2hex(random_bytes(4))`) üretir, `error_log('[RBN-BOOTGUARD] id=… type=… message=… file=… line=…')` yazar; `RBN_DEV` doğruysa kaçışlı mesajı, değilse yalnız "Sistem şu an kullanılamıyor. Hata kimliği: <ID>" basar (`:68-87`).

### 3.2 `PreBoot::orchestrate` (`PreBoot.php:406-458`)

1. `PhpVersionGate::enforce()`; ikinci çağrıda (`$initialized`) hemen döner.
2. `date_default_timezone_set('Europe/Istanbul')`, `setlocale(LC_ALL, 'tr_TR.UTF-8', …)`.
3. `detectEnvironment()`: `RBN_DEV` zaten tanımlıysa çıkar; `isLocal = envOverrideRequested() || isTrustedLocalEnvironment(HTTP_HOST, REMOTE_ADDR)`. Sabitler: `RBN_DEV`, `RBN_DEBUG` (başlangıçta `isLocal`), `DEFAULT_LANGUAGE='tr'`, `APP_NAME='RBN CORE'`, `APP_URL` (yerelde `http://<host>`, aksi `https://<host>`) (`:340-363`).
4. `ProjectDiscovery::getProjectData($publicPath, $config['project_key'] ?? null)`; doluysa `Bootstrap::setAppContext('project_data'|'project_key')` ve `$config['project_key']` güncellenir.
5. `initPaths()` → `Paths::init()`.
6. `defineAppVersion()`: `APP_VERSION` tek kaynağı master `projects.version`; yoksa/geçersizse `0.1.1`.
7. `registerSentinel()` (`BootSentinel::register()`), `Watchdog.php`'yi `require_once`, `initAutoloader()` (`vendor/autoload.php` yoksa `die` ile composer uyarısı), `handleFastAssets()` (`AssetDoctor::check()`).

### 3.3 Aşama sırası (`KernelFactory.php:81-86`)

`Autoload` → `ShieldSentinel` → `DatabaseGuardStage` → `ComponentRegistry` → `SessionSandboxStage` → `Routing`. Bağımlılık gerekçeleri `KernelFactory::create()` docblock'ındadır (Autoload her şeyden önce; Sentinel otoload'a; DB bekçisi proje bağlamına; ComponentRegistry'nin alias kayıtları DB'den sonra; oturum proje bağlamına; Routing hepsine bağlıdır).

`Kernel::boot()` (`Kernel.php:58-90`): `kernel.boot.start` kancası; her aşama için `handle()` ve `kernel.boot.stage.after`; `RBN_DEV` ya da `config['dev_mode']` doğruysa aşama süreleri ms cinsinden `context['profiling']['stages'][<sınıf>]` içine yazılır; `PreflightException` yakalanırsa `PreflightProvider::renderFatal(tip, mesaj, ipucu, kod)` çağrılır ve `exit`; sonunda `kernel.boot.complete`.

### 3.4 `SessionSandboxStage::handle` (`SessionSandboxStage.php:23-115`)

CLI'da hiçbir şey yapmaz. `Paths::project()->sessions()` dizini var ve yazılabilirse: `storage` servisinin `sessions()` işleyicisini `session_set_save_handler`'a verir (ad `getName()`), `session_save_path()`; çerez bayrakları `use_strict_mode=1`, `cookie_httponly=1`, `use_only_cookies=1`, `use_trans_sid=0`, `cookie_samesite=Lax`, `cookie_secure` yalnız HTTPS'te `1`, `cookie_path=/`. **Oturum ilkesi muafiyeti** (`isSessionPolicyExempt`): proje `Core/Config/session-policy.php` dosyasında `['<proje>' => ['skip_no_cookie' => [yol önekleri], 'hosts' => ['<host>' => ['skip_no_cookie' => […]]]]]`; oturum çerezi gelmiyorsa ve istek yolu (normalize edilmiş, segment sınırlı önek) listede ise `session_start` yapılmaz. Aksi halde `session_start()`; %10 olasılıkla (`random_int(1,10)===1`) `handler->gc(86400)`; sonra `syncActiveProjectContext()` (yönetim paneli yollarında `$_SESSION['active_project_key']` ile proje bağlamı değiştirilir).

## 4. Yapılandırma ve varsayılanlar

| Ayar | Değer | Kaynak |
|---|---|---|
| PHP alt sınırı | 8.3 (`80300`) | `PhpVersionGate.php:49-52` |
| Yerel host listesi | `localhost`, `127.0.0.1`, `[::1]` (+ `*.test`) | `PreBoot.php:34-38,333` |
| Yerel istemci adresi sayılanlar | `127.*`, `10.*`, `192.168.*`, `172.16–31.*`, `169.254.*`, IPv6 `::1`, `fc/fd`, `fe8–feb` | `PreBoot.php:158-222` |
| `RBN_DEV` zorlaması | ortam `RBN_DEBUG` ya da `RBN_DEV` ∈ {`1`,`true`,`on`,`yes`,`development`} | `PreBoot.php:91-107` |
| Saat dilimi | `Europe/Istanbul` | `PreBoot.php:419` |
| Hassas anahtar maskesi | `***`; tam eşleşme (`password`, `token`, `iban`, `cvv`, `tc_no`…) ya da alt dize (`password`, `token`, `secret`, `kart`, `card`, `credential`…) | `PreBoot.php:41-77,471-509` |
| Oturum çöp toplama | %10 olasılık, 86400 sn | `SessionSandboxStage.php:90-92` |
| Autoload önekleri | `Rbn\Project\`, `…\Core\`, `…\App\`, `…\Modules\`; `Rbn\Framework\`, `…\Core\`, `…\Resources\`, `…\Packages\`, `…\Bundles\RbnSuite\`, `…\Bundles\Internal\` | `Autoload.php:41-59` |

## 5. Tuzaklar ve kurallar

1. **Yerel ortam kararı istemciye bağlıdır:** `RBN_DEV` = ortam bayrağı **veya** (geçerli host `localhost`/`127.0.0.1`/`[::1]`/`*.test` **ve** `REMOTE_ADDR` özel/loopback adres). İkisi birden gerekir; `*.test` adlı bir host'a **dışarıdan** gelen istek `RBN_DEV=false` olur (`isTrustedLocalEnvironment`, `PreBoot.php:317-334`). Bunun, DB profili seçimiyle (`ProjectDbProfileResolver` — sunucu kimliği) **bilerek ayrıldığı** [Config](Config.md) belgesinde.
2. **`RBN_DEV` ve `RBN_DEBUG` bir kez `define()` edilir** (`PreBoot.php:352-353`); sonradan değişmez. `RBN_DEBUG` ortam değişkeniyle de açılabildiği için üretimde ortamda `RBN_DEBUG=1` bırakılması ekranda ayrıntı açar.
3. **`ShieldSentinel` `$kernel->get('projectKey')` okur ama hiçbir yerde `Kernel::set('projectKey', …)` yapılmaz** (tüm `Core/System` taramasında tek geçiş `ShieldSentinel.php:24`); bu yüzden `SystemDoctor::setExpectedKey()` her zaman `null` alır ve `SystemDoctor::check()` içinde de `expectedKey` okunmaz. Proje anahtarı `Bootstrap::appContext('project_key')` içindedir.
4. **`SystemDoctor` Apache'de `.htaccess` ister:** `SERVER_SOFTWARE` içinde `Apache` geçiyorsa ve `Paths::project()->public('.htaccess')` yoksa açılış durur (`SystemDoctor.php:58-67`). nginx'te bu kontrol çalışmaz (bilerek atlanır).
5. **`PermissionDoctor` Apache 2.2 sözdizimi yazar:** `Storage` klasörüne `.htaccess` içeriği `Order Deny,Allow` / `Deny from all` yazılır (`PermissionDoctor.php:205-207`). Apache 2.4'te bu `mod_access_compat` gerektirir; nginx `.htaccess`'i hiç okumaz (dizin koruması için sunucu yapılandırması gerekir). Hangi sunucuda çalışıldığı koddan belli değildir.
6. **`PermissionDoctor` yeni klasör oluşturur** (`mkdir 0755`) — yalnız `FolderMatrix::isRuntimeLayer()` olanlar ya da içinde dosya bulunan katmanlar için; "kullanılmayan klasör" `skippedFolders()`'a "atlandı" satırı olarak girer. Yazma izni yoksa açılış `PreflightException` ile durur.
7. **Aşama sırası değiştirilemez** (bağımlılık listesi `KernelFactory` docblock'ında). Yeni aşama eklenirse aynı biçimde "neye bağımlı" satırı yazılmak zorundadır.
8. **`Watchdog.php` bir sınıf dosyası gibi görünür ama koşullu bildirimdir:** `rbn_panic()` yalnız ilk çağrıda çalışır (`RBN_PANIC_ACTIVE` sabiti ikinci çağrıyı engeller), çıktı tamponlarını boşaltır, `Resources/Views/Errors/panic.view.php` varsa onu `include` eder, yoksa düz metin yığın izi basar ve `exit(1)`. Bu nedenle dosya `PreBoot::orchestrate()` içinde `require_once` ile elle yüklenir (otoload sınıfı bulamaz).
9. **`APP_VERSION` elle tanımlanmaz** (`PreBoot.php:356-362` açıklaması): giriş dosyalarında `define('APP_VERSION', …)` yazmak yasaktır; tek kaynak `projects.version`.
10. **`sendBaseSecurityHeaders()` yalnız iki başlık ekler** ve mevcut başlığı ezmez; HSTS, CSP, `X-Frame-Options` bu katmanda yoktur.
11. **`Kernel::terminate()` bugün yalnız `kernel.terminate` kancasını tetikler**; `profiling` bağlamı okunur ama işlenmez (`Kernel.php:95-104`).
12. **`PhpVersionGate` bağımsızdır:** sürüm yanlışsa PHP söz dizimi hatası yerine okunur bakım mesajı verebilmesi için `PreBoot`/`Bootstrap` onu `require_once` ile en başta yükler; PHP 7 gibi eski bir sürümde `Bootstrap.php`'nin kendisi ayrıştırılamayacağından asıl koruma giriş dosyalarındadır (`Bootstrap.php:20-26` açıklaması).

## 6. Örnek (gerçek koddan)

```php
// Core/System/Kernel/Base/KernelFactory.php:81-86
$kernel->addStage(new Autoload())
       ->addStage(new ShieldSentinel())
       ->addStage(new DatabaseGuardStage())
       ->addStage(new ComponentRegistry())
       ->addStage(new SessionSandboxStage())
       ->addStage(new Routing());
```

```php
// Oturum ilkesi dosyası şeması (SessionSandboxStage.php docblock'ı)
// <proje>/Core/Config/session-policy.php
return ['<project_key>' => ['skip_no_cookie' => ['/api/agent'], 'hosts' => ['<host>' => ['skip_no_cookie' => [...]]]]];
```

## 7. Doğrulama

* `find Core/System/Kernel -name '*.php' | wc -l` = **19**; belgede anlatılan dosya sayısı 19 (§2.1: 2, §2.2: 5, §2.3: 5, §2.4: 7).

## 8. İlgili belgeler

* [Core/System genel bakış](README.md) · [Config](Config.md) · [Discovery](Discovery.md) · [Paths](Paths.md) · [Storage](Storage.md)
* [Kavram: mimari harita](../../kavramlar/01-mimari-harita.md) · [Açık sorular](../../acik-sorular.md)
