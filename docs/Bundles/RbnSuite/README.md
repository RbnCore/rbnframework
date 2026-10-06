# Bundles/RbnSuite — RBN Suite paketleri (panel + kimlik + içerik)

> **Doğrulanan kod tabanı:** `d49b4413` (dal `feat/fw-license-master`) · **Tarih:** 2026-10-05 · **Yayın:** 0.9.5 = bu commit + sonrası; belge yalnız doğrulama anındaki kodu anlatır
> **Kaynak klasör:** `Bundles/RbnSuite/` — **80 `*.php`** (RbnAdmin 49 · RbnAuth 15 ·
> RbnStudio 16)
> **Envanter:** 80 php dosyasının **80'i** alt belgelerde anlatıldı.
> Bu belge yalnız **paketler arası** ilişkileri ve ortak kalıpları anlatır; her
> dosyanın ayrıntısı alt belgelerdedir.

## 1. Ne işe yarar, kimler kullanır

RbnSuite, framework'ün **ürçteç" paket ailesidir**: kullanıcıya görünen üç yüzü
(panel, giriş, içerik yönetimi) tek klasör altında toplanır ve üçü de aynı kayıt
mekanizmasını (`#[Bundle]` / `#[Module]` + `ModuleData`) kullanır. Diğer bundle
aileleri (`Bundles/Internal/*`) ya geliştirici/sistem içi yüzeylerdir ya da
alternatif sunum katmanlarıdır.

Yükleme sırası ve erişim kapısı:

```
index.php → Kernel → Routing
  Core/Routes/Mappings/core.php:75-86
    Route::middleware('admin')               → AuthMiddleware + role:admin
      Route::panel('admin')
        Route::module('suite','RbnAdmin')->load()   → RbnAdmin/Models/ModuleData::registerRoutes()
        Route::module('suite','RbnStudio')->load()  → RbnStudio/Models/ModuleData::registerRoutes()

  Core/Routes/Mappings/auth.php:10-54
    Route::module('Suite','RbnAuth')->prefix('')    → elle yazılmış rotalar
      Route::role('user')->prefix('user') → /user dashboard
```

**RbnAuth'in rotaları `ModuleData` tarafından yüklenmez** — `registerRoutes()`
metodu yoktur; rotaları framework'ün kendi haritasındadır. Diğer ikisi
kendi `registerRoutes()` metotlarını yazar.

## 2. Paket envanteri (80 php)

| Paket | `*.php` | Belge | Alt belgeler | Kısa görev |
|---|---:|---|---|---|
| `RbnAdmin/` | 49 | [RbnAdmin/README.md](RbnAdmin/README.md) | [Controllers](RbnAdmin/Controllers.md) (11) · [Models](RbnAdmin/Models.md) (3) · [Providers+Services+Traits](RbnAdmin/Providers.md) (7) · [Views](RbnAdmin/Views.md) (28) | Yönetim paneli: dashboard, trafik, kullanıcı, ayar, cron, e-posta, SEO, mesaj, bildirim |
| `RbnAuth/` | 15 | [RbnAuth/README.md](RbnAuth/README.md) | — | Kimlik doğrulama, oturum, rol/ACL, "beni hatırla", parola kurtarma, audit |
| `RbnStudio/` | 16 | [RbnStudio/README.md](RbnStudio/README.md) | — | İçerik yönetimi: makale, haber, kategori, taslak + AI üretimi + sosyal paylaşım |
| **Toplam** | **80** | | | `find Bundles/RbnSuite -name '*.php' \| wc -l` = 80 |

Alt dal belgeleri **yalnız RbnAdmin için** üretildi: RbnAuth (15) ve RbnStudio (16)
`< 20 php` eşiğinin altında (bkz. `docs/gorevler/DOC-REHBER-AGAC-SPEK.md` §1, satır 11)
ve tam envanterleri tek belgede anlatılmıştır — hiçbir dosya atlanmamıştır.

## 3. Ortak kayıt kalıbı

Üç paket de aynı iki özniteliği kullanır, ama **farklı ağırlıkta**:

| Paket | `ModuleData` konumu | `#[Bundle]` | `#[Module]` (kontrolör) | `registerMap()` | `registerRoutes()` |
|---|---|---|---|---|---|
| RbnAdmin | `Models/ModuleData.php` | ✔ `map: PanelMap::MAP` (`:21-25`) | ✔ `RbnAdminController` (`:17-21`) | 3 servis + 2 provider | ✔ 10 grup / **60 uç** (16'sı adlandırılmış) |
| RbnStudio | `Models/ModuleData.php` | ✔ `map: StudioMap::MAP` (`:15-19`) | ✔ `RbnStudioController` (`:14-18`) | **boş** (`:25-31`) | ✔ 4 grup / **33 uç** (33'ü de adlandırılmış) |
| RbnAuth | `Models/ModuleData.php` | ✘ | ✔ `AuthController` (`:19`) | 2 servis + 5 handler + 2 constant | ✘ (rotalar `auth.php`'te: 17 uç, 13'ü adlandırılmış) |

`ModuleData::registerMap()` çıktısı paket adı altında kayıt defterine karışır
(`Core/System/Registries/SystemRegistry.php:44-46` üç `ModuleData` sınıfını da
listeler). `registerRoutes()` yalnız `Route::module(…)->load()` çağrıldığında
çalışır (`Core/Routes/Route.php:139-155`).

## 4. Paketler arası bağımlılık (kodda ölçülen)

| Kimden → Kime | Nasıl, nerede |
|---|---|
| RbnAdmin → **RbnAuth** | `use ...RbnAuth\Models\AuthRole` (`UserManagementController.php:8`); `handler('access')->can('admin')` (`RbnAdmin/Services/RbnAdminService.php:27-28`); rotalar `AuthMiddleware` grubunda (`core.php:75`) |
| **Core** → RbnAuth | `RouteBlueprint::MIDDLEWARE['aliases']['auth'\|'guard'] = AuthMiddleware` (`RouteBlueprint.php:42-43`); `FormGuardHandler` → `AuthRole::BYPASS_ROLES` (`FormGuardHandler.php:162`); `AuthHandler` → `AuthIdentity` (`Core/Render/Handlers/UI/AuthHandler.php:6,30-31`); `BreadcrumbResolver` → `AuthRole::ROLES` (`:31`) |
| **Core** → RbnAdmin | `core.php:77` → `ModalController`; `PanelHandler` → `PanelIdentity` (`Core/Render/Handlers/UI/PanelHandler.php:10`); `SystemLogicMapTrait:78` + `SystemPhysicalMapTrait:79` → `RbnAdminService` / `SocialMediaProvider`; `SystemResourceMapTrait:53` → `ADMIN_` öneki |
| RbnStudio → **RbnAdmin** | `getActiveSocialPlatforms()` → `provider('socialmedia')` (`RbnStudioController.php:276`) = `RbnAdmin\Providers\SocialMediaProvider` (`SystemPhysicalMapTrait.php:79`) |
| RbnStudio → RbnAuth | **dolaylı**: rotaları `admin` middleware'i altında (`core.php:85`) |
| Üçü → `Core\Database` | `project.user`, `project.userSecurity`, `project.userToken`, `project.userActivity`, `project.settings`, `project.cronLog`, `master.developer`, `master.ipBlock`, `master.cronJob`, `app.contentCategory`, `app.contentDraft` |

## 5. Tuzaklar (paketler arası, ölçülmüş)

1. **`RbnAdmin` ve `RbnStudio` aynı `admin` middleware grubunda yüklenir**
   (`core.php:80-86`), yani `AuthMiddleware::handle('admin')` ikisine de uygulanır.
   `AuthRole::ADMIN_ROLES` üyesi olmayan bir rol (`moderator` seviye 60) ikisine de
   giremez (`RouteBlueprint.php:48` → `role:admin` → seviye 80).

2. **Rota adlandırma üç pakette üç farklı.** RbnStudio'nın 33 ucu **tamamen**
   adlandırılmış; RbnAdmin'ın 60 ucundan yalnız **16'sı** adlandırılmış
   (`bot-settings` ve `cron` grupları); RbnAuth'ın 17 ucundan **13'ü** adlandırılmış
   (`Core/Routes/Mappings/auth.php`). `returnPath()` adlandırılmış rotaları
   çözebildiği için bu tutarsızlık davranışı değiştirir.

3. **`SocialMediaProvider` iki pakete de hizmet veriyor** ve RbnAdmin içinde
   **durumlu (stateful)** bir nesne. RbnStudio her platform için ayrı
   `getPlatform()` çağırır (güvenli, çünkü değerleri hemen kopyalar),
   RbnAdmin `all()` ile dizi döner. İki desen kasıtlıdır
   ([RbnAdmin Providers §5.9](RbnAdmin/Providers.md)).

4. **İki ölü rota hedefi ölçüldü** (her biri tek eşleşme, çağıranın kendisi):
   * `POST /admin/users/store` → `UserManagementController@store` **yok**
     ([RbnAdmin Models §7.4](RbnAdmin/Models.md))
   * `DraftsController::delete()` → `deleteDraft()` **yok** (repository'de
     `destroyDraft()` var) ([RbnStudio §5.1](RbnStudio/README.md))
   İkisi de `Dispatcher`'ın "metot yok" yoluna düşer (`Core/Routes/Engine/Dispatcher.php:173-198`).
   Bu görev **yalnız belge** üretti; kod değiştirilmedi.

5. **CSRF yüzeyi yalnız RbnStudio'da.** Dört `Route::get('delete/{id}')` kaydı
   vardır (`RbnStudio/Models/ModuleData.php:43,53,64,78`); RbnAdmin'ın tüm silme
   uçları POST'tur. RbnAuth ise GET çıkışı **bilinçli** tutuyor ama
   çapraz-site isteklerde 403 ile kapatıyor (`RbnAuth/README.md` §3.2).

6. **`RbnAuth` tek pakettir `registerRoutes()` yazmayan.** Yeni bir RbnAuth ucu
   eklemek için `Core/Routes/Mappings/auth.php`'ye dokunmak gerekir; diğer iki
   pakette bu iş `Models/ModuleData.php` içinde yapılır. Bu, üç paket arasındaki
   en pratik tuzaktır.

7. **Paneller arası hiyerarşi tek yönlüdür.** `RouteBlueprint::CORE_MODULES =
   ['dashboard','RbnAdmin']` (`RouteBlueprint.php:94`) ve
   `DASHBOARD_PREFIX = 'dashboard'` (`:99`) tanımlıdır; `RbnStudio` bu listelerde
   **yoktur** — çünkü kendi yol önekini (`studio/…`) rotalarında kendisi verir.

## 6. Örnek (gerçek koddan)

Bir paketin rotalarının framework tarafından yüklenmesi:

```php
// Core/Routes/Route.php:139-155 (özet)
$dataClass  = $baseNamespace . '\\Models\\ModuleData';
$moduleDataClass = class_exists($dataClass) ? $dataClass : $modelClass;

if (class_exists($moduleDataClass) && method_exists($moduleDataClass, 'registerRoutes')) {
    $groupAttributes['namespace'] = $baseNamespace . '\\Controllers';
    Router::getInstance()->group($groupAttributes, function () use ($moduleDataClass) {
        (new $moduleDataClass())->registerRoutes();
    });
}
```

ve panel kapısının RbnAuth'e bağımlılığı:

```php
// Core/Routes/Mappings/core.php:75-86 (özet)
Route::middleware('admin')->group(function () {
    Route::get('admin/modal/content', [\Rbn\Framework\Bundles\RbnSuite\RbnAdmin\Controllers\ModalController::class, 'content']);
    Route::panel('admin')->group(function () {
        Route::module('suite', 'RbnAdmin')->load();
        Route::module('suite', 'RbnStudio')->load();
    });
});
```

## 7. İlgili belgeler

* [RbnAdmin](RbnAdmin/README.md) · [RbnAuth](RbnAuth/README.md) · [RbnStudio](RbnStudio/README.md)
* [Core/Routes](../../Core/Routes/README.md) — `Route::module()`, `RouteBlueprint`, middleware grupları
* [kavramlar/01-mimari-harita](../../kavramlar/01-mimari-harita.md)
* [Dizin indeksi](../../README.md)
