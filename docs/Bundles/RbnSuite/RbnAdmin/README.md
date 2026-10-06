# RbnAdmin — Yönetim paneli (dashboard, trafik, kullanıcı, ayar, cron, e-posta)

> **Doğrulanan kod tabanı:** `d49b4413` (dal `feat/fw-license-master`) · **Tarih:** 2026-10-05 · **Yayın:** 0.9.5 = bu commit + sonrası; belge yalnız doğrulama anındaki kodu anlatır
> **Kaynak klasör:** `Bundles/RbnSuite/RbnAdmin/` — **49 `*.php`**
> (Controllers 11 · Views 28 · Models 3 · Providers 4 · Services 2 · Traits 1)
> **Envanter:** 49 php dosyasının **49'u** aşağıdaki tabloda anlatıldı; hiçbir dosya
> "listedeyle kapatılmadı".
> Alt dal belgeleri: [Controllers.md](Controllers.md) · [Models.md](Models.md) ·
> [Providers.md](Providers.md) (Providers + Services + Traits) · [Views.md](Views.md)

## 1. Ne işe yarar, kimler kullanır

RbnAdmin, framework'ün **her yönetim ekranının arkasındaki pakettir**: giriş sonrası
dashboard, ziyaretçi trafiği analitiği, kullanıcı/rol yönetimi, sistem ayarları,
bot & API anahtarı yönetimi, cron logları, SEO raporu, iletişim mesajları, sistem
bildirimleri ve cPanel e-posta hesapları. Tüm rotaları `Route::middleware('admin')`
grubuna bağlıdır, yani **oturum + rol denetimi olmadan hiçbiri erişilebilir değildir**
(`Core/Routes/Mappings/core.php:75-86`).

Kullanıcı yönünden: URL'den gelen istek `Dispatcher` → `Route` → ilgili kontrolör.
Kontrolörler `BaseController`'dan türer; iş mantığı **kontrolörde değil**, servis /
provider / repository katmanındadır (kontrolörler yalnız istek doğrulama + render +
yönlendirme yapar). Tek istisna: `RbnAdminService::saveColors()` bir yetki kontrolü
içerir.

## 2. Klasör envanteri (49 dosya)

### 2.1 `Controllers/` — 11 dosya

| Dosya | Görev | Önemli public yöntemler (imza) |
|---|---|---|
| `RbnAdminController.php` | Paketin kök kontrolörü; dashboard, proje geçişi, tema kaydı | `adminIndex()`, `userIndex()`, `switchProject(string $key)`, `saveTheme()`; korumalı: `renderDashboard(string $panel)` (`:58`) |
| `WebtrafficController.php` | Trafik dashboard'u + log + tarihsel rapor + GA4 ekranları | `index()`, `logs()`, `report()`, `googleAnalytics()`, `googleAnalyticsMap()` |
| `UserManagementController.php` | Kullanıcı listesi, rol, profil, aktivite kayıtları | `index()`, `getModalData($id): array`, `password($id=null)`, `updateRole()`, `delete($id)`, `profile()`, `profileUpdate()`, `profilePassword()`, `activities()`, `clearActivities()` |
| `ContactController.php` | İletişim mesajları (unread/read/trash) | `index(?string $type=null)`, `unread()`, `read()`, `trash()`, korumalı `getModalData($id): array`, `delete($id)`, `restore($id)`, `destroy($id)`, `clear()` |
| `NotificationController.php` | Sistem bildirimleri | `index()`, korumalı `getModalData($id): array`, `delete($id)`, `clear()`, `getCounts()` |
| `AdminSettingsController.php` | Ayar grupları + ayar kaydı | `index(?string $type=null)`, `update()` |
| `BotSettingsController.php` | Bot/API anahtarları, otonom görev listesi, AI kullanımı | `index()`, `apis()`, `tasks()`, `save()`, `modal($id=null, ?string $view=null)`, `create()`, `status()`, `aiUsage()`, `clearAiUsage()` |
| `CronLogsController.php` | Cron ayarları + cron logları (DB ve `.jsonl` dosya) | `cron()`, `index()`, `view()`, `deleteFile()`, `clear()`, `toggleCron()`, `saveCron()` |
| `HostmailhubController.php` | cPanel UAPI ile e-posta hesabı yönetimi | `index()`, korumalı `getModalData($id): array`, `create()`, `delete($id)`, `changePassword()`, `eternalLink()`, korumalı `getActiveDomains(): array` |
| `SeoReportController.php` | Tek ekranlık SEO raporu | `index()` |
| `ModalController.php` | AJAX modal dağıtıcısı (tüm panel için ortak uç) | `content()`; korumalı `renderErrorMessage(string $title, string $message, ?string $subText=null)` |

### 2.2 `Models/` — 3 dosya

| Dosya | Görev | Önemli üyeler |
|---|---|---|
| `ModuleData.php` | Paketin kayıt merkezi: servis/provider kayıtları **ve** tüm rotalar | `registerMap(): array` (`:33`), `registerRoutes(): void` (`:52`) |
| `PanelMap.php` | Panel gezinme ağacı (menü hiyerarşisi, başlık, ikon, `controller` ipucu) | `MAP` sabiti (`:19-167`) |
| `PanelIdentity.php` | SEO/OG/meta + varsayılan bilgilendirme metinleri | `THEME_COLOR`, `FAVICON`, `PANEL_IDENTITY`, `DEFAULT_INFO`, `VIEW_MAP` |

### 2.3 `Providers/` — 4 dosya

| Dosya | Görev | Önemli yöntemler |
|---|---|---|
| `AnalyticsProvider.php` | Trafik + kullanıcı istatistiklerinin ham toplayıcısı | `getDashboardStats(): array`, `getUserStats(): array` |
| `SocialMediaProvider.php` | `Resources/Data/social_media_platforms.json` dosyasını okuyup akıcı platform erişimi verir | `getPlatform(string $key): self`, `all(bool $sortByOrder=true): array`, `total(): int`, `exists()`, `title()`, `icon()`, `faIcon()`, `riIcon()`, `color()`, `order()`, `shareUrl()`, `shareClass()` |
| `Fluent/TrafficStatsChannel.php` | Trafik için önbellekli akıcı kanal | `stats()`, `summary()`, `hits()`, `trend()` |
| `Fluent/UserStatsChannel.php` | Kullanıcı için önbellekli akıcı kanal | `get()`, `count()`, `stats()`, `total()`, `distribution()`, `roles()`, `all()` |

### 2.4 `Services/` — 2 dosya

| Dosya | Görev | Önemli yöntemler |
|---|---|---|
| `AnalyticsService.php` | Trafik/kullanıcı kanalına + GA4'e tek giriş kapısı | `users(): UserStatsChannel`, `traffic(): TrafficStatsChannel`, `record(array $data=[])`, `isGoogleAnalyticsActive(): bool`, `getGoogleAnalyticsReports(?string $s, ?string $e, string $type='standard')` |
| `RbnAdminService.php` | Tema rengi kaydı (yetki kontrollü) + sosyal medya paylaşım düğmeleri | `saveColors(string $p, ?string $s=null, ?string $projectKey=null): bool`, `allSocialPlatforms(bool $sortByOrder=true)`, `socialPlatform(string $key)`, `totalSocialPlatforms(): int`, `getShareButtons(string $url, string $title): array` |

### 2.5 `Traits/` — 1 dosya

| Dosya | Görev | Önemli yöntemler |
|---|---|---|
| `TrafficAnalysisTrait.php` | Trafik sorgu/analiz motoru; `AnalyticsProvider` ve `TrafficStatsChannel` **ortak** kullanır | `withProject()`, `query()`, `forDate()`, `forToday()`, `forRange()`, `forTrend()`, `limitQuery()`, `get()`, `count()`, `computeSummary()`, `devices()`, `top()`, `getActiveCount(int $minutes=5)`, `all()`, `hits()` |

### 2.6 `Views/` — 28 dosya (`.rbn.php` uzantısı zorunlu değil, `ViewResolver` ikisini de kabul eder)

| Dosya | Görev |
|---|---|
| `Contact/index.rbn.php` | Mesaj listesi + istatistik kartları |
| `Contact/Partials/modal.rbn.php` | Tek mesajın okunduğu modal (`@var array $contact`, `@var int $id`) |
| `Cronlogs/index.rbn.php` | Cron log listesi (DB sekmesi + dosya sekmesi) |
| `Cronlogs/view.rbn.php` | Tek log kaydı/dosyası ayrıntısı |
| `Hostmailhub/index.php` | **Uzantısız `.php`** — diğer 27 görünümden tek istisna (§5.7) |
| `Hostmailhub/Partials/modal.rbn.php` | Yeni e-posta hesabı modalı |
| `Hostmailhub/Partials/change_password.rbn.php` | Hesap parolası değiştirme modalı |
| `Notification/index.rbn.php` | Bildirim listesi + sayaç kartları |
| `Notification/Partials/modal.rbn.php` | Tek bildirim modalı (`@var array $notification`) |
| `Seo/report.rbn.php` | SEO raporu (`stats` + `details`) |
| `Setting/index.rbn.php` | Ayar grupları + ayar formu (`ra-stat-*` kart standardı) |
| `Setting/apis.rbn.php` | API anahtarı listesi (sosyal medya ikonlarıyla) |
| `Setting/botdash.rbn.php` | Bot dashboard (AI telemetri kartları dahil) |
| `Setting/cron.rbn.php` | Cron iş ayarı (gün/saat seçimi) |
| `Setting/tasks.rbn.php` | Otonom görev listesi |
| `Setting/ai_usage.rbn.php` | AI kullanım ve maliyet tablosu |
| `Setting/Partials/modal.rbn.php` | API anahtarı ekleme modalı |
| `Setting/Partials/pricing_modal.rbn.php` | Model fiyat listesi modalı |
| `User/index.rbn.php` | Kullanıcı listesi + filtreler |
| `User/profile.rbn.php` | Kendi profilini düzenleme |
| `User/activities.rbn.php` | Aktivite kayıtları (denetim izi) |
| `User/Partials/modal_user.rbn.php` | Kullanıcı ekleme/düzenleme modalı |
| `User/Partials/modal_role.rbn.php` | Rol değiştirme modalı |
| `Webtraffic/index.rbn.php` | Trafik özet ekranı |
| `Webtraffic/logs.rbn.php` | Günlük hit tablosu + bot/organik filtresi |
| `Webtraffic/report.rbn.php` | Tarih aralığı raporu (cihaz/bot/sayfa/kaynak dağılımı) |
| `Webtraffic/google.rbn.php` | GA4 panosu |
| `Webtraffic/google_map.rbn.php` | GA4 Türkiye şehir ısı haritası |

> **Dashboard görünümü bu klasörde değildir.** `RbnAdminController::renderDashboard()`
> `RbnAdmin/dashboard` render eder (`RbnAdminController.php:149`); bu dosya
> `Resources/Views/RbnAdmin/dashboard.rbn.php` yolunda, panelin ortak
> `Layouts/` ve `Components/` dosyalarıyla birlikte oradadır (9 dosya).

## 3. Akış: bir istek panelden ekrana

### 3.1 Dashboard açılışı

```
GET /admin/  →  Core/Routes/Mappings/core.php:75-86  (middleware 'admin' → RbnAdmin modülü)
  → Route::module('suite','RbnAdmin')->load()  → Route.php:130-159
  → (new ModuleData)->registerRoutes()  → ModuleData.php:55-60  (Route::controller('RbnAdminController'))
  → Dispatcher → RbnAdminController::adminIndex()          RbnAdminController.php:27
  → renderDashboard('admin')                                RbnAdminController.php:58
      ├─ provider('dash.stats') → get<Proje>Stats() | getAdminStats()   :62-72
      ├─ service('analytics')->traffic()->summary()                    :75-80
      ├─ helper('Geo')->countryFlags()  → ülke adı/flag çözümü        :84-106
      ├─ group_projects() → aktif proje adı/alan adı                  :113-138
      ├─ service('shieldSettings')->getSetting('bot_activity',0)       :141
      │    └─ 1 ise manager('aiUsage')->getFilteredReport(['project'=>…])  :144
      └─ render('RbnAdmin/dashboard', …)                              :149
```

`dashboard` görünümü `Resources/Views/RbnAdmin/dashboard.rbn.php`; panel kasıtlı
olarak **boş** bırakılmıştır (`'module' => null`, `RbnAdminController.php:151`),
yani proje modülü zorunlu değildir.

### 3.2 Trafik raporu

```
GET /admin/webtraffic/report?start_date=&end_date=
  → WebtrafficController::report()                     WebtrafficController.php:191
  → service('analytics')->traffic()->query()->forRange($s,$e)->get()
      → TrafficAnalysisTrait::get()                   TrafficAnalysisTrait.php:79-118
        (her gün için Storage->traffic()->get('date_YYYY-MM-DD') ; satırlara 'date' eklenir)
  → PHP içinde cihaz/bot/sayfa/kaynak/trend sayımı    :207-262
  → render('Webtraffic/report', …)                     :296
```

**Trafik bir SQL sorgusu değil, dosya tabanlı bir sayaçtır**: `TrafficAnalysisTrait`
her şeyi `$this->storage->traffic()->get('date_' . $date)` ile okur
(`TrafficAnalysisTrait.php:91,99,104`). Ağır istatistikler (`computeSummary()`)
aylık özet dosyalarından (`getMonthlyData()`) toplanır (`:150,174`).

### 3.3 Modal akışı (her alt modül için ortak)

`GET /admin/modal/content?type=<modül>&id=<id>` →
`ModalController::content()` (`ModalController.php:19`, rota `core.php:77`) →
`discover()->controller($type)` ile kontrolör bulunur → varsa `modal($id,$view)`
çağrılır. Bulunamazsa uyarı bloğu basılır, **hata fırlatılmaz** (`:72-80`).
Kontrolörün kendi `getModalData($id)` varsa veri oradan gelir; yoksa servis/provider
`find($id)` ile okunur (`Core/Base/Web/Traits/Controller/ActionControllerTrait.php:251-299`).

## 4. Yapılandırma / ayar anahtarları

| Anahtar | Nerede okunur | Varsayılan / etki |
|---|---|---|
| `theme_color_primary`, `theme_color_secondary` | `RbnAdminController::saveTheme()` doğrulaması: `regex:/^#[0-9A-Fa-f]{6}$/` (`RbnAdminController.php:170-171`) | `secondary` `nullable`; `settings->updateSettings()` ile yazılır |
| `bot_activity` | `RbnAdminController.php:141`, `BotSettingsController` tarafından `settingsApi` üzerinden yazılır | `0`; `1` ise dashboard'a AI kullanım kartları düşer |
| Ayar grupları (`group_key`) | `AdminSettingsController::index()` — `appearance` grubu **bilerek gizlenir** (`AdminSettingsController.php:36`) | Boşsa `type` ilk aktif grup anahtarı olur (`:42-44`) |
| `admin_panel_disabled` | Okuyan yer: `AuthService` (RbnAuth) | RbnAdmin'de okunmaz; yalnız yönlendirme sonucunu etkiler |
| Cron log dizini | `Paths::project()->root('Storage/logs/cron')` (`CronLogsController.php:60,140,199,213`) | Proje kökü altında `Storage/logs/cron` |
| cPanel mail hesap listesi önbelleği | `HostmailhubController.php:57-60` — `cache()->withProject($key)->remember('api_cpanel_mail', …, 600)` | **600 sn**; create/delete/changePassword sonrası elle düşürülür |
| Sosyal medya platform tanımları | `SocialMediaProvider::__construct()` → `Paths::frameworkRoot().'/Resources/Data/social_media_platforms.json'` (`SocialMediaProvider.php:27`) | Dosya yoksa `platforms = []` (sessiz boş) |
| GA4 etkinliği | `AnalyticsService::isGoogleAnalyticsActive()` → `service('api')->google('analytics_active')` (`AnalyticsService.php:51`) | `false` ise GA4 ekranları boş rapor basar |
| GA4 harita illeri | `WebtrafficController.php:366-377` — `helper('Data')->get('tr-locations')` + `turkishSlug()`/`toEnglishAlphabet()` | Veri yoksa şehir eşlemesi boş kalır |

## 5. Tuzaklar ve kurallar (kodda ölçülmüş)

1. **`ModuleData::registerMap()` anahtar çakışması — düzeltilmiş ama izlenmeli.**
   `analytics` **hem servis** (`AnalyticsService`) **hem provider** (`AnalyticsProvider`)
   adıdır (`ModuleData.php:37,42`). Farklı türler olduğu için çakışma *yoktur*; ama
   `RbnAdminService` iki ayrı anahtara birden yazılır (`socialmedia`, `rbnAdmin`,
   `ModuleData.php:38-39`) ve `socialmedia` **ayrıca** global kayıt defterinde vardır
   (`Core/System/Registries/RegistryMap/SystemLogicMapTrait.php:78`). Yani aynı sınıf
   üç yerden erişilebilir.

2. **`analytics` servisinin `AnalyticsProvider` kullanımı ölü.** `AnalyticsProvider`
   `getDashboardStats()` metodu (`AnalyticsProvider.php:25`) **hiçbir yerden çağrılmıyor**
   (`grep getDashboardStats` → yalnız tanım + `Internal/Syshub`'ın *kendi* metodu).
   Dashboard'da kullanılan yol `TrafficStatsChannel::computeSummary()`'dir. Ayrıca
   `AnalyticsProvider::getDashboardStats()` içindeki `$traffic->hits()` çağrısı
   `TrafficAnalysisTrait::hits()`'e düşer ve bu **tüm zamanların toplamını** döndürür
   (`TrafficAnalysisTrait.php:316-319` → `count()`), yani metot adı `today_hits`
   olmasına rağmen "bugün" değildir. Kullanılmadığı için etkisiz, yanıltıcıdır.

3. **`TrafficAnalysisTrait::count()` tüm tarihleri tarar.** `$provider->listDates()`
   döngüsüyle aylık dosyaların **tümünü** okur (`TrafficAnalysisTrait.php:123-132`).
   `total_hits` yıllar geçtikçe doğrusal yavaşlar. `computeSummary()` bunu ay
   özetleriyle telafi eder (`:167-187`), ama `count()` kendisi telafi etmez.

4. **`TrafficStatsChannel` aynı trait'i iki kez kullanır → özyineleme mührü zorunlu.**
   `AnalyticsProvider` **ve** `TrafficStatsChannel` aynı `TrafficAnalysisTrait`'i
   `use` eder (`AnalyticsProvider.php:20`, `TrafficStatsChannel.php:16`), bu yüzden
   aynı sınıftaki metotlar **iki kez** tanımlanmış olur ve `use` ile çakışmaz —
   trait tek yalnız derlenir. `computeSummary()`'nın `static $isCalculating`
   mührü (`TrafficAnalysisTrait.php:139-143`) bu yinelenmeyi kırmak içindir;
   `finally` bloğunda kaldırılır (`:236-238`). Mühre dokunulmazsa yol kilitlenir.

5. **`WebtrafficController` bayrak çözümlemesini 4 kez tekrarlar.**
   `index()` (`:32-51`), `logs()` (`:143-165`) ve `RbnAdminController` (`:87-105`) aynı
   "ISO kodu → ülke adı + `fi fi-xx` sınıfı" mantığını kopyalar. Yeni bir ülke
   kaynağı eklenirse **üçü** de güncellenmelidir.

6. **Bot ayrımı string önekine dayanır.** Bot/organik sınıflandırması
   `str_starts_with($location, 'Bot')` ile yapılır (`WebtrafficController.php:125,129,249`;
   `TrafficAnalysisTrait.php:193,206`). `report()` ayrıca `'🤖'` önekini de eler
   (`WebtrafficController.php:270`) — bu iki eleme **tutarsızdır** (`computeSummary`
   `🤖`'ü eleyen yoktur, `TrafficAnalysisTrait.php:193`). Yeni bir bot etiketi
   biçimi bu üç noktada ayrı ayrı tanımlanmalıdır.

7. **`Hostmailhub/index.php` uzantısı diğerlerinden farklı.** 28 görünümün 27'si
   `.rbn.php`; bu biri düz `.php`. `ViewResolver` ikisini de kabul eder
   (`Core/Render/Resolvers/ViewResolver.php:43`), yani çalışır — ama konvansiyon
   dışıdır ve `ViewProvider`'ın `.rbn.php`ye özel davranışından
   (`Core/Render/Providers/UI/ViewProvider.php:59-60`) muaftır.

8. **Cron log dosyası adı path traversal'a karşı elle süzülür, ama süzgeç dar.**
   `view()`/`deleteFile()` `..`, `/`, `\` reddi yapar
   (`CronLogsController.php:136,195`) — doğru. Ancak `clear()` **tüm** `*.jsonl`
   dosyalarını siler ve proje/oturum sınırı kontrolü yoktur (`:211-224`); bu bir yetki
   kararıdır, `admin` middleware'ine bağlıdır.

9. **`Route::post('users/store')` rotası var, hedef metot yok.** `ModuleData.php:91`
   → `Route::post('store', 'store')`; ancak `UserManagementController` ve tabanı
   `CrudControllerTrait` **`store()` taşımaz** (CRUD oluşturma ucu `create()`'dir,
   `Core/Base/Web/Traits/Controller/Engine/CrudControllerTrait.php:24`).
   Ölçüldü: `ReflectionClass(UserManagementController::class)->hasMethod('store')` →
   **false**. Bu uç çağrılırsa `Dispatcher` `RuntimeException` fırlatır
   (`Core/Routes/Engine/Dispatcher.php:173-198`). Detay: [Models §7.4](Models.md).

10. **`PanelMap` içindeki `SidebarController` bu pakette değildir.**
    `PanelMap.php:63-66` `navigation.sidebar.controller = 'SidebarController'` der;
    bu sınıf `Bundles/Internal/Backstage/Controllers/SidebarController.php:14`'te
    yaşar. Panel ağacının başka bir pakete işaret etmesi kasıtalıdır
    (`ModuleDataDriver` controller eşlemesini buradan üretir,
    `Core/System/Discovery/Engine/Drivers/ModuleDataDriver.php:180-194`), ama
    RbnAdmin'ın kendi `registerRoutes()`'ında `navigation` rotası **yoktur**
    (`ModuleData.php:52-158`): yalnız harita tanımı vardır.

11. **`RbnAdminController::saveTheme()` yetkiyi servis içinde denetler.**
    `RbnAdminService::saveColors()` `handler('access')->can('admin')` çağırıp
    yetkisizse `throw new \Exception(...)` atar (`RbnAdminService.php:27-30`).
    Kontrolör bunu **yakalamaz** → yetkisiz istek 500'e düşer, kullanıcı dostu
    hata mesajı üretilmez.

12. **`handleResult()` 4. parametresi fiil olarak "eylem" ayarı.**
    `Context` diye adlandırılmış ama içerik `create|store|update|status|delete|destroy|
    bulkOrder|bulkValueUpdate` olmalı; başka bir metot adı geçirilirse mesaj
    sözlüğünde bulunamaz ve `update` varsayılanına düşer
    (`ActionControllerTrait.php:156-194`). Kullanıcı hatalı `context` verirse
    mesaj yanlış ama isabetli görünür.

13. **Durum değiştirme değer normalizasyonu üç yerde kopyalanmış.**
    `'1' | true | 'true' | 'on' → 1` kuralı `CategoriesController` (RbnStudio),
    `CronLogsController::toggleCron()` (`:233`) ve `CrudControllerTrait::status()`
    (`Core/Base/Web/Traits/Controller/Engine/CrudControllerTrait.php:178`) içinde
    ayrı ayrı yazılıdır.

14. **`switchProject()` `Referer`'a kör güvenir.** Yönlendirme hedefi
    `strtok($_SERVER['HTTP_REFERER'], '?')`'dir (`RbnAdminController.php:40-41`);
    `Referer` yoksa panele düşer. Açık yönlendirme riski `Referer`'ı ayarlayan
    istemciye bağlıdır (tarayıcı `Referer`'ı yazılır, ham istemci yazmaz).

15. **`remember()` önbelleği 600 sn; yazma yolları elle düşürüyor.**
    `create`/`delete`/`changePassword` üçünde de aynı 12 satırlık "projeyi bul →
    `api_cpanel_mail` önbelleğini sil" bloğu tekrar edilir
    (`HostmailhubController.php:123-131,156-163,189-197`). **Dördüncü** bir yazma
    yolu eklenirse bu üç yer de güncellenmelidir, aksi halde 10 dakika eski liste görünür.

## 6. Örnek (gerçek koddan)

Tema kaydı — doğrulama, yetki ve yazma zinciri:

```php
// Bundles/RbnSuite/RbnAdmin/Controllers/RbnAdminController.php:166-186
$data = $this->request->form([
    'theme_color_primary'   => 'required|regex:/^#[0-9A-Fa-f]{6}$/',
    'theme_color_secondary' => 'nullable|regex:/^#[0-9A-Fa-f]{6}$/',
    'project'               => 'nullable|string'
]);
$result = $this->service->saveColors(
    $data['theme_color_primary'],
    $data['theme_color_secondary'] ?? null,
    $data['project'] ?? null
);
return $this->Route->handleResult($result);
```

Akıcı trafik sorgusu:

```php
// Bundles/RbnSuite/RbnAdmin/Controllers/WebtrafficController.php:203-204
$hits = $traffic->query()->forRange($startDate, $endDate)->get();
$allHits = array_values(array_filter($hits, fn($hit) => isset($hit['time'])));
```

## 7. İlgili belgeler

* [RbnAuth](../RbnAuth/README.md) — giriş, rol tablosu, oturum, CSRF (panelin kapısı)
* [RbnStudio](../RbnStudio/README.md) — içerik yönetimi (makale/haber/kategori/taslak)
* [Core/Base/Web](../../../Core/Base/Web.md) — `BaseController`, `handleResult()`, `modal()`
* [Core/Http](../../../Core/Http/README.md) — `Request::form()`, `Validator`, `rawAll()`
* [Core/Routes](../../../Core/Routes/README.md) — `Route::module()`, middleware grupları
* [Core/Database/Repositories](../../../Core/Database/Repositories.md) — `project.*` depoları
* [acik-sorular](../../../acik-sorular.md)
