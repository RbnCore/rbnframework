# Core/System/Paths — Yol kayıt defteri (çatı / proje / modül kökleri)

> **Doğrulanan kod tabanı:** `d508f5e1` (dal `feat/fw-license-master`) · **Tarih:** 2026-10-05 · **Yayın:** 0.9.3 = bu commit + sonrası; belge yalnız doğrulama anındaki kodu anlatır
> **Kaynak klasör:** `Core/System/Paths/` — 4 `*.php` (`Paths.php` + `Contexts/` altında 3).
> **Envanter:** 4 dosyanın 4'ü aşağıda anlatıldı.
> **Doğrulama platformu:** Windows (PHP 8.3 ile `FolderContext::path()` çalıştırıldı, bkz. §5).

## 1. Ne işe yarar, kim kullanır

"Bu dosya nerede?" sorusunun tek cevap kapısıdır. `Paths::init()` ile iki kök kaydedilir (proje dizini, `public` dizini); çatı kökü (`rbnframework`) ve çalışma alanı kökü dosyanın kendi konumundan bulunur. Üç **bağlam** nesnesi yol üretir:

* `Paths::framework()` → `FrameworkContext` (çatı: `Core`, `Bundles`, `Packages`, `Resources`, `vendor`),
* `Paths::project()` → `ProjectContext` (aktif proje: `App`, `Core/Config`, `Modules`, `Storage`, `public`…),
* `Paths::module($ad, $kaynak)` → `ModuleContext` (bir modülün/paketin kendi kökü).

Klasör adları koda gömülmez; `Core/Support/Definitions/System/FolderMatrix.php` içindeki ağaçtan (`FRAMEWORK`, `PROJECT`) gelir. **Kimler çağırır:** Kernel (`PreBoot::initPaths`), `Discovery` çözücüleri, Storage sağlayıcıları, Render, CLI — neredeyse her katman.

## 2. Klasör/dosya envanteri

| Dosya | Görev | Önemli public yöntemler |
|---|---|---|
| `Paths.php` | Statik kayıt defteri: kökleri tutar, bağlamları tembel kurar, modül bağlamlarını önbellekler. | `init(?string $project, ?string $public = null)`, `workspace(): string`, `frameworkRoot(): string`, `projectRoot(): string`, `publicRoot(): string`, `isInitialized(): bool`, `project(): ProjectContext`, `framework(): FrameworkContext`, `module(string $name, string $source = 'auto'): ModuleContext` |
| `Contexts/FrameworkContext.php` | Çatı yolları (`FolderContext`'ten türer; `FolderMatrix::FRAMEWORK` ağacını kullanır). | `namespaces(): NamespaceContext`, `root(?$path)`, `vendor(?$path)`, `core()`, `bundles()`, `packages()`, `resources()`, `assets()`, `views()`, `layouts()`, `system()`, `suite(string $moduleName, ?$path)`, `internal()`, `shield()` |
| `Contexts/ProjectContext.php` | Aktif projenin yolları (`FolderMatrix::PROJECT` ağacı). | `namespaces()`, `root()`, `public()`, `baseUrl(): string`, `core()`, `app()`, `module()`, `resources()`, `storage()`, `configs()`, `tasks()`, `system()`, `routes()`, `views()`, `assets()`, `layouts()`, `components()`, `dashboards()`, `sessions()`, `logs()`, `uploads()`, `exports()`, `backups()`, `temp()` |
| `Contexts/ModuleContext.php` | Tek bir modül/paket: kök, kaynak, ad alanı, alt dizinler. | `root()`, `namespaces(): NamespaceContext`, `views()`, `controllers()`, `models()`, `handlers()`, `providers()`, `assets()`, `getName()`, `getSource()` |

(Hepsi `?string $path` alır ve sonuna ayraçla ekler.)

## 3. Akış

### 3.1 Başlatma

`PreBoot::orchestrate()` proje verisini topladıktan sonra `initPaths()` çağırır (`Kernel/Base/PreBoot.php:515-531`): `projects/<custom_path ?? project_key>` yolunu kurar ve `Paths::init($projectPath, $publicPath)` yapar. `init()` (`Paths.php:37-57`):

1. kökleri `rtrim` ile saklar, `ProjectContext` ve `FrameworkContext` kurar;
2. `Core/Support/Bridges/Helpers/…` altındaki global yardımcı dosyasını `require_once` eder (`:50`);
3. `initialized = true`;
4. `RBN_DEV` doğruysa ve `?rbn_diag` varsa kökleri basıp `die` eder (`:55-57`) — yalnız geliştirme ortamında.

### 3.2 Kök bulma (`Paths::workspace()`, `Paths.php:63-85`)

`__DIR__`'den yukarı çıkarak adı `rbnframework` olan dizini arar; bulursa çatı kökü o, çalışma alanı kökü onun üstüdür. Bulunamazsa `dirname(__DIR__, 3)` çatı kökü varsayılır. Bu yüzden çatı klasörünün adı **`rbnframework`** olmalıdır (yeniden adlandırılırsa 3 seviye yukarı varsayımına düşer).

### 3.3 `publicRoot()` (`Paths.php:109-122`)

`project_data('public_path')` doluysa `<workspace>/domains/<public_path>` yolunu dener; dizin varsa onu döndürür, **yoksa `init`'te verilen `public` yolunu** döndürür.

### 3.4 Modül bağlamı (`Paths::module`, `Paths.php:160-169`)

Anahtar `"<ad>_<kaynak>"` ile bellek önbelleği; yoksa `ModuleDiscoveryDriver::resolveModuleContext()` (ayrıntı: [Discovery §3.5](Discovery.md)). `ModuleContext::namespaces()` taban ad alanını `NamespaceMap::MAP[<kaynak>]`'tan, yoksa `Project|Modules` için `Rbn\Project\Modules\`, diğerleri için `Rbn\Framework\Bundles\<Kaynak>\`'tan üretir ve önek zaten modül adıyla (ya da `Rbn<Ad>` ile) bitiyorsa tekrar eklemez (`ModuleContext.php:43-73`).

### 3.5 `ProjectContext::baseUrl()` (`ProjectContext.php:71-118`)

Protokol `$_SERVER['HTTPS'] === 'on'` ile; proje verisindeki `domain` tam URL ise olduğu gibi; değilse geçerli ana makine `.test`/`.local` ile bitiyorsa aynı eki ekler ve portu korur. `active_project_key()` proje verisinden farklıysa (yönetim panelinde proje değiştirilmişse) `BootCacheProvider`'dan o projenin verisini okur. Domain yoksa `HTTP_HOST` kullanılır.

## 4. Yapılandırma

Yol ağacı kaynağı `FolderMatrix` (`Core/Support/Definitions/System/FolderMatrix.php`): çatı için `BUNDLES` (`Internal`, `RbnSuite/{RbnAdmin,RbnAuth,RbnStudio}`), `CORE` (`Base, Database, Http, Render, Routes, Services, Support, System`), `PACKAGES`, `RESOURCES` (`Assets`, `Views`), `vendor`; proje için `App`, `Core` (`Config`, `Tasks`), `Modules` (`Backend`, `Frontend/Controllers`), `Resources` (`Components`, `Dashboards`, `Data`, `Layouts`), `Storage` (`cache`, `framework/views`, `logs/traffic`, `sessions`), `public` (`css`, `js`, `images`).

`ProjectContext::sessions()/logs()/uploads()/exports()/backups()/temp()` doğrudan `Storage/<ad>` verir; **yalnız `temp()`** dizin yoksa ve alt yol verilmemişse `mkdir 0775` ile oluşturur (`ProjectContext.php:296-303`).

## 5. Tuzaklar ve kurallar (ölçülmüş)

PHP 8.3 ile `FolderContext::path()` çalıştırıldığında (Windows):

```
PROJECT.CORE.SYSTEM.ROUTES        => Core\SYSTEM\ROUTES
PROJECT.CORE.SYSTEM               => Core\SYSTEM
PROJECT.CORE.CONFIG               => Core\Config
PROJECT.RESOURCES.ASSETS          => Resources\ASSETS
FRAMEWORK.BUNDLES.SUITE.RBNSHIELD => Bundles\RbnSuite\RBNSHIELD
FRAMEWORK.BUNDLES.SUITE.RBNADMIN  => Bundles\RbnSuite\RBNADMIN
FRAMEWORK.CORE.SYSTEM             => Core\System
```

1. **Matriste olmayan anahtar olduğu gibi (BÜYÜK harfle) yola yazılır.** `FolderContext::resolvePath()` her parça için matriste yoksa `$part`'ı ham ekler (`FolderContext.php:176-185`). `FolderMatrix::PROJECT['CORE']` altında `SYSTEM` yoktur, `PROJECT['RESOURCES']` altında `ASSETS` yoktur, `FRAMEWORK['BUNDLES']['SUITE']` altında `RBNADMIN`/`RBNSHIELD` yoktur (yalnız `ADMIN/AUTH/STUDIO`). Sonuç: `ProjectContext::system()`, `routes()`, `assets()` ve `FrameworkContext::suite('RbnAdmin')`, `shield()` **Windows'ta (büyük/küçük harf duyarsız) çalışabilir ama Linux üretiminde yanlış-harfli yol üretir.**
2. **Gerçek diskte de bu klasörler yok:** `projects/<grup>/<site>/` altında (yerel yerleşim) `Core/` yalnız `Config`, `Prompts`, `Tasks` içerir (`Core/System/Routes` yok); `Resources/` altında `Assets` yok (`Components, Dashboards, Data, Layouts`); `Bundles/RbnSuite/` altında `RbnShield` yok (`RbnAdmin, RbnAuth, RbnStudio`). Bu yüzden `ProjectContext::routes()` bugün yalnız `Route::loadRoutes()` içinde (`Core/Routes/Route.php:269`) ve `RouteBlueprint::FILES['project']` **boş olduğu için hiç çağrılmıyor**; `FrameworkContext::shield()` çağıranı bulunamadı. Biri etkinleştirildiğinde önce `FolderMatrix`'e anahtar eklenmelidir.
3. **`ProjectContext::views()` ile `resources()` aynı yolu verir** (`PROJECT.RESOURCES`, `ProjectContext.php:210-212`); görünümler `Resources/` köküne doğrudan bakar, `Views` alt klasörü aranmaz.
4. **`Paths::project()` `init` öncesi çağrılırsa `LogicException`** (`Paths.php:137-139`); `Paths::framework()` ise tembelce kendini kurar (`:146-154`). `Paths::isInitialized()` ile önce sorulmalıdır.
5. **`public_path` biçimi:** `publicRoot()` değeri `domains/` ile birleştirir (`Paths.php:115`). Master kayıtlarında 18'i öneksiz, 1'i `domains/` önekli yazılmış (eski ölçüm); önekli kayıt `domains/domains/…` olur, `is_dir()` yanlış çıkar ve **sessizce `init`'e verilen `public` yoluna düşülür** (`:116-121`). Giriş noktası zaten doğru `public` dizinini verdiği için kullanıcı etkisi yoktur; öneksiz biçim doğru biçimdir. Kaydın düzeltilmesi master veri işidir (bu belge salt okunur). *(Eski `acik-sorular.md` §1.5 — davranış kodla çözüldü, veri düzeltmesi hâlâ açık: bkz. [acik-sorular](../../acik-sorular.md).)*
6. **`workspace()` varsayımı:** çatı klasörü adı `rbnframework` değilse 3 seviye yukarı çıkılır (`Paths.php:79-82`).

## 6. Örnek (gerçek koddan)

```php
// Core/System/Kernel/Base/PreBoot.php:528-530
$projectPath = $rootDir . DIRECTORY_SEPARATOR . 'projects' . DIRECTORY_SEPARATOR . ltrim($customPath, './\\');
Paths::init($projectPath, $publicPath);
```

```php
// Core/System/Kernel/Stages/Autoload.php:28-32
$workspace = Paths::workspace();
$project = Paths::project();
$frameworkRoot = Paths::frameworkRoot();
$frameworkPackages = Paths::framework()->packages();
```

## 7. İlgili belgeler

* [Core/System genel bakış](README.md) · [Discovery](Discovery.md) (`FolderContext`, `NamespaceContext`) · [Kernel](Kernel.md) · [Storage](Storage.md)
* [Support/Definitions](../Support/Definitions.md) (`FolderMatrix`, `NamespaceMap`)
* [Kavram: mimari harita](../../kavramlar/01-mimari-harita.md) · [Açık sorular](../../acik-sorular.md)
