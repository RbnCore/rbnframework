# RbnAdmin/Models — kayıt merkezi, panel haritası ve kimlik

> **Doğrulanan kod tabanı:** `d49b4413` (dal `feat/fw-license-master`) · **Tarih:** 2026-10-05 · **Yayın:** 0.9.5 = bu commit + sonrası; belge yalnız doğrulama anındaki kodu anlatır
> **Kaynak klasör:** `Bundles/RbnSuite/RbnAdmin/Models/` — **3 `*.php`**
> **Envanter:** 3 dosyanın 3'ü anlatıldı.
> Üst belge: [README.md](README.md)

## 1. Ne işe yarar, kimler kullanır

Bu klasörde **hiçbir veritabanı modeli yoktur** (RbnAuth ve RbnStudio'da `ModuleData`
adında bir sınıf vardır, burada öyle bir sınıf da yoktur — bu paketteki üç sınıfın
üçü de yapılandırma sınıfıdır). Üç görev üstlenirler:

1. **`ModuleData`** — paketin servis/provider kayıtlarını **ve** tüm HTTP
   rotalarını tanımlar. Paket yüklenirken `Route::module('suite','RbnAdmin')->load()`
   tarafından bulunur ve `registerRoutes()` çağrılır (`Core/Routes/Route.php:143-153`).
2. **`PanelMap`** — panel menüsünün ağacı: hangi modül, hangi başlık, hangi ikon,
   hangi kontrolör. Menü ve breadcrumb bu sabitten üretilir.
3. **`PanelIdentity`** — panelin SEO/OG/meta verisi ve varsayılan bilgilendirme
   metinleri.

## 2. Dosya envanteri

| Dosya | Sınıf | Taban | Görev | Önemli üyeler |
|---|---|---|---|---|
| `ModuleData.php` | `ModuleData` | `BaseConfig` | Kayıt merkezi + rota tanımı | `#[Bundle(name:'rbnadmin', context:'panel', map: PanelMap::MAP)]` (`:21-25`), `registerMap(): array` (`:33`), `registerRoutes(): void` (`:52`) |
| `PanelMap.php` | `PanelMap` | `BaseConfig` | Panel gezinme ağacı | `public const MAP` (`:19-167`) |
| `PanelIdentity.php` | `PanelIdentity` | `BaseConfig` | Kimlik + SEO + varsayılan metinler | `THEME_COLOR = '#c56a3c'` (`:17`), `FAVICON` (`:18`), `PANEL_IDENTITY` (`:23`), `DEFAULT_INFO` (`:47`), `VIEW_MAP` (`:71`) |

## 3. `ModuleData::registerMap()` — kayıt defteri (`:33-46`)

```php
'services'  => ['analytics' => AnalyticsService, 'socialmedia' => RbnAdminService, 'rbnAdmin' => RbnAdminService],
'providers' => ['analytics' => AnalyticsProvider, 'social'    => SocialMediaProvider],
```

* `analytics` **hem** servis **hem** provider → farklı türler, çakışma yok.
* `RbnAdminService` **iki** anahtara birden yazılıyor (`socialmedia`, `rbnAdmin`).
* `socialmedia` ayrıca global kayıt defterinde de var
  (`Core/System/Registries/RegistryMap/SystemLogicMapTrait.php:78`).

## 4. `ModuleData::registerRoutes()` — paketin 9 rota grubu

| # | Prefix | Kontrolör | Uç sayısı | Satır |
|---|---|---|---:|---|
| 1 | *(yok)* | `RbnAdminController` | 4 | `:55-60` |
| 2 | `webtraffic` | `WebtrafficController` | 5 | `:63-69` |
| 3 | `hostmailhub` | `HostmailhubController` | 6 | `:72-79` |
| 4 | `seo-report` | `SeoReportController` | 1 | `:82-84` |
| 5 | `users` | `UserManagementController` | 12 | `:87-105` |
| 6 | `settings` | `AdminSettingsController` | 2 | `:108-111` |
| 7 | `bot-settings` | `BotSettingsController` | 9 | `:114-124` |
| 8 | `cron` | `CronLogsController` | 7 | `:127-135` |
| 9 | `contact` | `ContactController` | 9 | `:138-148` |
| 10 | `notification` | `NotificationController` | 5 | `:151-157` |

**Toplam: 60 uç** (ölçüldü: `Select-String "Route::(get|post)\("` → 60 eşleşme).
Bunların tamamı `Route::middleware('admin')` → `Route::panel('admin')` grubunun
içine yüklenir (`Core/Routes/Mappings/core.php:75-86`).

Rota adlandırma tutarsızdır: yalnız **7. ve 8. gruplar** (bot-settings, cron)
`->name('admin.…')` kullanır — **16 uç** (ölçüldü: 16 `name('admin` eşleşmesi);
diğer 8 gruptaki **44 uç adsızdır**. Adı olmayan rotalar `returnPath()`'in
adlandırılmış rota keşfini kullanamaz
(`Core/Base/Web/Traits/Controller/ActionControllerTrait.php:69-…`).

## 5. `PanelMap::MAP` — panel ağacı

Kök: `identity` (`'Yönetim Paneli'`, ikon `ri-dashboard-line`), `sub_modules` altında
**11 üst düğüm**:

| Anahtar | Başlık | `controller` ipucu | Alt düğümler |
|---|---|---|---|
| `webtraffic` | Webtraffic Analitik | yok (türetilir: `WebtrafficController`) | `logs`, `report`, `google-analytics` (→ `map`) |
| `navigation` | Navigasyon Yönetimi | yok | `sidebar` → **`SidebarController`** |
| `hostmailhub` | E-Posta Yönetimi | `HostmailhubController` | — |
| `seo-report` | SEO Analizi | `SeoReportController` | — |
| `users` | Kullanıcı Yönetimi | `UserManagementController` | `profile`, `activities` |
| `settings` | Sistem Ayarları | `AdminSettingsController` | — |
| `bot-settings` | Bot & API Ayarları | `BotSettingsController` | `apis`, `tasks`, `ai-usage` |
| `cron` | Zamanlanmış Görevler | `CronLogsController` | `cronlogs` |
| `contact` | İletişim Mesajları | **yok** | — |
| `notification` | Sistem Bildirimleri | **yok** | — |

`controller` ipucu yoksa `ModuleDataDriver` anahtardan türetir:
`PascalCase(slug) . 'Controller'` (`Core/System/Discovery/Engine/Drivers/ModuleDataDriver.php:185`).
Yani `contact` → `ContactController`, `notification` → `NotificationController`
**otomatik** eşleşir; yazmamak bir hataya yol açmıyor, sadece örtük.

## 6. `PanelIdentity` — kimlik ve metinler

* `THEME_COLOR = '#c56a3c'` (Terracotta) ve `FAVICON = '@fw/images/favicon-rbnadmin.svg'` (`:17-18`).
* `PANEL_IDENTITY['seo']` `robots = 'noindex, nofollow'` (`:29`) — yönetim paneli
  dizinlenmemeli, kasıtlı.
* Başlıklar `FrameworkIdentity::ADMIN_TITLE . ' | ' . FrameworkIdentity::ADMIN_NAME`
  sabitlerinden gelir (`:25,33`) — marka adları tek kaynaktan.
* `DEFAULT_INFO` yalnız `dashboard` ve `settings` için metin taşır (`:47-66`);
  diğer 9 panel modülü için karşılığı **yoktur** → görünümler kendi metinlerini yazar.
* `VIEW_MAP` yalnız iki anahtar içerir (`:71-74`) ve **hiçbir kod tarafından okunmaz**
  (ölçüldü: `VIEW_MAP` araması yalnız bu tanımı bulur). RbnAuth'ın `VIEW_MAP`'i de
  benzer biçimde tanımlıdır (`RbnAuth/Models/AuthIdentity.php:109`).

**Karşılaştırma:** `PanelIdentity` bir `#[Bundle]` özniteliği taşıyan `ModuleData`
tarafından `config['identity']` olarak okunur (`ModuleDataDriver.php:171-177`), ama
`RbnAdmin/Models/ModuleData.php` içinde `CONFIG` sabiti **yoktur** — kimlik
`#[Bundle(map: PanelMap::MAP)]` üzerinden `MAP['identity']` alanından gelir
(`ModuleDataDriver.php:162-170`).

## 7. Tuzaklar

1. **`PanelMap` içindeki `SidebarController` bu pakette değildir.**
   `PanelMap.php:63-66` `navigation.sidebar.controller = 'SidebarController'` der;
   sınıf `Bundles/Internal/Backstage/Controllers/SidebarController.php:14`'te yaşar.
   `ModuleDataDriver` bu ipucuyla `controller_map` üretir
   (`ModuleDataDriver.php:182-183`), yani panel haritası **kasıtlı olarak**
   komşu pakete işaret eder. Ama RbnAdmin'ın `registerRoutes()`'ında `navigation`
   rotası **yoktur** (`:52-158`) → menüde görünen bir düğüm için RbnAdmin tarafında
   uç yoktur.

2. **`identity` düğümü yalnız `PanelMap`'te vardır, `ModuleData` tarafından
   türetilmez.** `ModuleDataDriver` `identity` alanını `$map['identity']` üzerinden
   okur (`:162`) ve yoksa `ucfirst($bundle->name)` = `'Rbnadmin'` yazar (`:165`).
   `PanelMap::MAP['identity']['title']` = `'Yönetim Paneli'` doğru değer; yalnız
   `ModuleData` `registerMap()`'i döndürmediği için bu yol **harita dosyasına**
   bağımlıdır.

3. **Rota adlandırma tutarsızlığı** (§4). Adlandırılmamış rotalarda
   `returnPath('users/profile')` gibi **dize** yollar kullanılmak zorundadır;
   `handleResult()`'ın `returnPath()` çağrısı (`ActionControllerTrait.php:227`)
   ad çözemediğinde dizeyi olduğu gibi döndürür (`:143-147`).

4. **`users/store` rotası var, `store()` metodu yok — bu bir ölü uçtur.** ⚠️
   `ModuleData.php:91` → `Route::post('store', 'store')`, ancak
   `UserManagementController` (ve tabanı `CrudControllerTrait`) **`store()` metodunu
   taşımaz**; CRUD oluşturma ucu `create()`'dir (`CrudControllerTrait.php:24`).
   **Ölçüldü:** `ReflectionClass(UserManagementController::class)->hasMethod('store')`
   → `false`; `hasMethod('create')` → `true`. `Dispatcher` bu durumda metot yok
   yoluna düşer ve `RuntimeException` fırlatır
   (`Core/Routes/Engine/Dispatcher.php:173-198`); üretimde mesaj
   `'Controller not found or method missing.'` olur.
   Bu uç muhtemelen **hiç kullanılmıyor** (ekranlar `update/{id}` veya servis
   tarafından oluşturma yapıyor), ama rota tablosunda "var" göründüğü için
   geliştirici yanlış yönlendirilir. Aynı grupta `update/([0-9]+)` → `update`
   **var** (`CrudControllerTrait.php:40`), yani güncelleme çalışır, ekleme çalışmaz.
   Aynı desen RbnStudio'da **yoktur** (orada tek `save` ucu hem ekler hem günceller).

5. **`users` grubu `store` ve `update/{id}` uçlarını ayırır** — bu ayrımın
   niyeti "oluştur" / "güncelle" idi, ama yalnız yarısı bağlı (tuzak 4).
   RbnStudio'da tek `save` ucu `id` yoksa ekleme, varsa güncelleme yapar
   (`RbnStudio/Controllers/PostsController.php:112-135`).

6. **`Route::get('modal/?([0-9]*)', 'modal')`** kalıpları `users` (`:90`) ve
   `contact` (`:143`) gruplarında **seçenekli** parametre kullanır — diğer tüm
   modal uçları `modal/{id?}` biçimindedir. İki yazım da çalışır ama birleştirilse
   daha tutarlı olur.

7. **`delete` uçlarının tamamı POST'tur** — ölçüldü: `users/delete` (`:94`),
   `contact/delete` (`:144`), `contact/restore` (`:145`), `contact/destroy` (`:146`),
   `notification/delete` (`:155`), `hostmailhub/delete` (`:76`) — **hiçbiri GET
   değil**. CSRF yüzeyi yoktur; bu RbnStudio'nın aksine güvenli taraftadır
   (RbnStudio'da `Route::get('delete/{id}')` da kayıtlıdır, bkz.
   [RbnStudio §5](../RbnStudio/README.md)).

8. **`PanelIdentity::VIEW_MAP` ölü — RbnAuth'ınki değil.** §6'da ölçüldü:
   `grep VIEW_MAP` → yalnız bu tanım. Karşılaştırma: RbnAuth'ın `VIEW_MAP`'i
   `Core/Render/Handlers/UI/AuthHandler.php:30` tarafından **okunur**; panelin
   karşılığı olan `PanelHandler` yalnız `communication` servisini sorgular
   (`Core/Render/Handlers/UI/PanelHandler.php:40`) ve kimlik sabitlerini
   kullanmaz (`:30`: "kaynak, aktif kontrolörden DNA mirasıyla otomatik gelir").

9. **`registerMap()` `providers` altında `analytics` var ama
   `AnalyticsProvider`'ın `$targetModel = 'Users'`** (`AnalyticsProvider.php:18`) —
   yani bu provider trafikle değil **kullanıcı tablosuyla** çalışır. Adı yanıltıcıdır.

## 8. Örnek (gerçek koddan)

Paketin rotaları nasıl yüklenir (çağıran taraf `Core/Routes/Route.php:139-155`):

```php
// Bundles/RbnSuite/RbnAdmin/Models/ModuleData.php:72-79
Route::prefix('hostmailhub')->controller('HostmailhubController')->group(function () {
    Route::get('/', 'index');
    Route::get('modal/{id?}', 'modal');
    Route::post('create', 'create');
    Route::post('delete/{email?:[a-zA-Z0-9_\.-]+}', 'delete');
    Route::post('change-password', 'changePassword');
    Route::get('eternalLink', 'eternalLink');
});
```

## 9. İlgili belgeler

* [RbnAdmin README](README.md) · [Controllers.md](Controllers.md) · [Providers.md](Providers.md)
* [Core/Routes](../../../Core/Routes/README.md) — `Route::module()`, `Route::prefix()`
* [Core/Support/Definitions](../../../Core/Support/Definitions.md) — `FrameworkIdentity`
* [RbnStudio](../RbnStudio/README.md) — aynı kalıbın (`#[Bundle]` + `registerRoutes`) ikinci örneği
