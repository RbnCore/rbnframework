# RbnAdmin/Views — 28 panel görünümü

> **Doğrulanan kod tabanı:** `d49b4413` (dal `feat/fw-license-master`) · **Tarih:** 2026-10-05 · **Yayın:** 0.9.3 = bu commit + sonrası; belge yalnız doğrulama anındaki kodu anlatır
> **Kaynak klasör:** `Bundles/RbnSuite/RbnAdmin/Views/` — **28 `*.php`**
> (Contact 2 · Cronlogs 2 · Hostmailhub 3 · Notification 2 · Seo 1 · Setting 8 ·
> User 5 · Webtraffic 5)
> **Envanter:** 28 dosyanın 28'i aşağıda anlatıldı.
> Üst belge: [README.md](README.md)

## 1. Ne işe yarar, kimler kullanır

Bunlar panelin **son katmanıdır**: kontrolörün `render()` çağrısıyla bulunan,
`ViewResolver`'ın çözdüğü PHP şablonlarıdır. Veri hazırlama **tamamen** kontrolörde
yapılır; görünüm yalnız bastırır.

Panel görünümlerinin **yerleşim kafesi iki yerdedir**:

| Klasör | Sahibi | Kaç dosya |
|---|---|---:|
| `Bundles/RbnSuite/RbnAdmin/Views/` | modüle özel ekranlar | 28 |
| `Resources/Views/RbnAdmin/` | **ortak** panel iskeleti (dashboard, `Layouts/`, `Components/`) | 9 |

`RbnAdminController::renderDashboard()` birincisini değil **ikincisini** render eder:
`render('RbnAdmin/dashboard', …)` → `Resources/Views/RbnAdmin/dashboard.rbn.php`
(`RbnAdminController.php:149`). Bu 9 dosya bu görevin kapsamı dışıdır
(`Resources/` ayrı görev; bkz. `docs/README.md` §2.2).

## 2. Uzantı ve isimlendirme

`ViewResolver` iki uzantıyı da kabul eder: `$extensions = ['.php', '.rbn.php']`
(`Core/Render/Resolvers/ViewResolver.php:43`). `.rbn.php` **standarttır** ve
`ViewProvider` bu uzantıya özel davranış uygular
(`Core/Render/Providers/UI/ViewProvider.php:59-60`).

**Ölçülen istisna:** `Views/Hostmailhub/index.php` düz `.php`'dir; 28 dosyanın
27'si `.rbn.php`'dir. Çalışır (çözücü ikisini de kabul eder) ama konvansiyon dışıdır.

## 3. Dosya envanteri (28 dosya)

### 3.1 `Contact/` — 2

| Dosya | Render eden | Beklediği değişkenler |
|---|---|---|
| `index.rbn.php` | `ContactController::index()` (`Controllers/ContactController.php:32`) | `stats`, `detailedStats`, `currentType`, `is_read` |
| `Partials/modal.rbn.php` | `ActionControllerTrait::modal()` | `@var array $contact`, `@var int $id` (`:1-2`) |

### 3.2 `Cronlogs/` — 2

| Dosya | Render eden | Beklediği değişkenler |
|---|---|---|
| `index.rbn.php` | `CronLogsController::index()` (`Controllers/CronLogsController.php:87`) | `activeTab`, `files`, `dbLogs`, `totalDbCount`, `totalFilesCount`, `pager` |
| `view.rbn.php` | `CronLogsController::view()` (`:125`, `:164`) | `filename`, `logId`, `logs`, `pager`, `size`, `modified` |

`view.rbn.php` **iki farklı kayıt şeklini** basar: DB kaydı (tek satır, `level`
INFO/ERROR'a çevrilmiş — `:113-123`) ve dosya (`.jsonl` satırları, `timestamp`
alanına göre sıralanmış — `:145-159`). Görünüm bu iki şekli `logs` dizisinde
aynı anahtarlarla taşıyacağı için ayrımı `logId` tipinden yapar.

### 3.3 `Hostmailhub/` — 3

| Dosya | Render eden | Beklediği değişkenler |
|---|---|---|
| `index.php` (**uzantısız**) | `HostmailhubController::index()` (`Controllers/HostmailhubController.php:66`) | `accounts`, `domains`, `projects`, `projectKey`, `selected_domain`, `default_quota` |
| `Partials/modal.rbn.php` | `ActionControllerTrait::modal()` | `domains`, `email`, `email_user`, `email_domain` (`Controllers/HostmailhubController.php:93-98`) |
| `Partials/change_password.rbn.php` | aynı modal rotası (`type` ile ayrışır) | `email`, `domain` |

### 3.4 `Notification/` — 2

| Dosya | Render eden | Beklediği değişkenler |
|---|---|---|
| `index.rbn.php` | `NotificationController::index()` (`Controllers/NotificationController.php:25`) | `stats`, `detailedStats` |
| `Partials/modal.rbn.php` | `ActionControllerTrait::modal()` | `@var array $notification`, `@var int $id` (`:1-2`) |

### 3.5 `Seo/` — 1

| Dosya | Render eden | Beklediği değişkenler |
|---|---|---|
| `report.rbn.php` | `SeoReportController::index()` (`Controllers/SeoReportController.php:27`) | `stats`, `details` |

### 3.6 `Setting/` — 8

| Dosya | Render eden | Beklediği değişkenler |
|---|---|---|
| `index.rbn.php` | `AdminSettingsController::index()` (`Controllers/AdminSettingsController.php:78`) | `groups`, `settings`, `activeGroup`, `currentType`, `badgeCounts` |
| `apis.rbn.php` | `BotSettingsController::apis()` (`Controllers/BotSettingsController.php:75`) | `options` (her satıra `icon` eklendi) |
| `botdash.rbn.php` | `BotSettingsController::index()` (`:34`) | `options`, `cronJobs`, `cronScheduleData`, `aiReport` |
| `cron.rbn.php` | `CronLogsController::cron()` (`Controllers/CronLogsController.php:35`) | `cronJobs`, `daysOfWeek` |
| `tasks.rbn.php` | `BotSettingsController::tasks()` (`Controllers/BotSettingsController.php:103`) | `tasks`, `cronScheduleData` |
| `ai_usage.rbn.php` | `BotSettingsController::aiUsage()` (`:246`) | `manager('aiUsage')->getFilteredReport()` çıktısı + `pager` |
| `Partials/modal.rbn.php` | `BotSettingsController::modal()` (`:182`) | `apiKeys` (`manager('api')->getFlattenedKeys()`) |
| `Partials/pricing_modal.rbn.php` | `BotSettingsController::modal()` (`type=pricing`, `:175`) | `pricingMap` (`RbnApi\Models\AiData::getPricingMap()`) |

`tasks.rbn.php` ile `Setting/cron.rbn.php` **aynı veriyi gösterir** (ikisi de
`master.cronJob` + `getScheduleSummaryData()`), yalnız `cron.rbn.php` hafta
günlerini de (`daysOfWeek`) ve `tasks.rbn.php` `hours_string`'i ayrıca üretir
(`Controllers/BotSettingsController.php:90-97`). İki ekran kasıtalı olarak
ayrılmıştır; biri kaldırılırsa diğeri etkilenmez.

### 3.7 `User/` — 5

| Dosya | Render eden | Beklediği değişkenler |
|---|---|---|
| `index.rbn.php` | `UserManagementController::index()` (`Controllers/UserManagementController.php:42`) | `users`, `pager`, `stats`, `search`, `currentStatus`, `currentRole` |
| `profile.rbn.php` | `UserManagementController::profile()` (`:139`) | `profile`, `isSelf`, `sub_module` |
| `activities.rbn.php` | `UserManagementController::activities()` (`:210`) | `activities`, `pager`, `stats`, `search`, `currentType`, `sub_module` |
| `Partials/modal_user.rbn.php` | `ActionControllerTrait::modal()` | `user`, `id`, `roles`, `appContext` (`Controllers/UserManagementController.php:65-71`) |
| `Partials/modal_role.rbn.php` | aynı, `type=role` | aynı + `User/Partials/modal_role` (`Controllers/UserManagementController.php:63`) |

### 3.8 `Webtraffic/` — 5

| Dosya | Render eden | Beklediği değişkenler |
|---|---|---|
| `index.rbn.php` | `WebtrafficController::index()` (`Controllers/WebtrafficController.php:101`) | `stats`, `recentHits` |
| `logs.rbn.php` | `WebtrafficController::logs()` (`:181`) | `selectedDate`, `paginator`, `visitorType` |
| `report.rbn.php` | `WebtrafficController::report()` (`:296`) | `startDate`, `endDate`, `totalHits`, `deviceStats`, `botStats`, `topPages`, `topSources`, `trendData`, `topLocation` |
| `google.rbn.php` | `WebtrafficController::googleAnalytics()` (`:332`) | `isActive`, `reportData`, `startDate`, `endDate` |
| `google_map.rbn.php` | `WebtrafficController::googleAnalyticsMap()` (`:399`) | `isActive`, `reportData`, `startDate`, `endDate`, `cityStats`, `maxUsers` |

`google_map.rbn.php` en ağır görünümdür (481 satır): Türkiye illerini
`DataHelper::get('tr-locations')` + `TextHelper::turkishSlug()` ile GA4 şehir
adlarına eşler (`Controllers/WebtrafficController.php:363-397`).

## 4. Veri akışı

```
Kontrolör                              →  View::render()          →  dosya
------------------------------------------------------------------------------------
AdminSettingsController::index()   :78 → render('Setting/index')     → Setting/index.rbn.php
CronLogsController::cron()        :35 → render('Setting/cron')       → Setting/cron.rbn.php
BotSettingsController::aiUsage() :246 → render('Setting/ai_usage')   → Setting/ai_usage.rbn.php
HostmailhubController::index()    :66 → render('Hostmailhub/index')  → Hostmailhub/index.php
SeoReportController::index()      :27 → render('Seo/report')         → Seo/report.rbn.php
RbnAdminController::adminIndex() :149 → render('RbnAdmin/dashboard') → Resources/Views/RbnAdmin/
                                                                     dashboard.rbn.php
```

`View::render($view, $data, $type)` üçüncü argümanı **context**'tir
(`panel`/`auth`/`frontend`); hangi RenderProvider'ın çalışacağını belirler
(`Core/Base/Web/Traits/Controller/ViewTrait.php:83`).

## 5. Tuzaklar

1. **Beklenen değişken adı denetlenmez.** `render()` çıktısındaki eksik bir
   anahtar PHP uyarısı (`Undefined array key`) ve sessiz boş render verir;
   istisna **fırlatılmaz**. `report.rbn.php` bunu kendi içinde koruyor:
   `$totalHits > 0 ? round(…) : 0` (`:1-2`). Yeni bir görünüme veri eklerken
   kontrolörün değişkeni gönderdiğinden emin olmak gerekir.

2. **`ajax => true` bayrağı bazı görünümlerde eksik.** `ActionControllerTrait::modal()`
   her modal render'da `'ajax' => true` enjekte eder (`:291`), ama RbnAdmin'ın
   **kendi** `modal()` yazan iki görünümü bu bayrağı elle koymak zorundadır:
   `BotSettingsController::modal()` (`:177,184`), `HostmailhubController` modalı.
   Bayrak, görünümün partial olarak mı tam sayfa mı basılacağını belirler.

3. **`Hostmailhub/index.php` uzantısı.** §2.

4. **`Setting/tasks.rbn.php` ve `Setting/cron.rbn.php` aynı veriyi iki kez
   sorgulatır.** `tasks()` görevi, proje için iş bulunamazsa **tüm** işleri de
   çeker (`Controllers/BotSettingsController.php:89-98`); `cron()` yalnız proyeye
   ait işleri çeker. İki ekran arasındaki fark bu yedek sorgudur.

5. **`ra-stat-*` sınıf ön eki bir konvansiyondur, kod değil.** `Setting/index`,
  `User/index`, `User/activities`, `Setting/botdash` görünümlerinde
  `<!-- … Terracotta ra-stat-* StandardÄ± … -->` yorumları vardır. Panelin görsel
  kimliği `PanelIdentity::THEME_COLOR = '#c56a3c'` ile bu sınıflara bağlıdır; tema
  değişikliği (`saveTheme()`) bu sınıflara da yansımalıdır.

6. **`report.rbn.php` yüzde hesabını kendisi yapar.** `$organicPercent` /
  `$botPercent` kontrolörde değil görünümde hesaplanır (`:1-2`); `WebtrafficController::report()`
  yalnız ham `botStats` sayılarını verir. Aynı hesap `TrafficAnalysisTrait::computeSummary()`
  içinde **yeniden** yapılır (`TrafficAnalysisTrait.php:225-234`) — iki kaynak,
  aynı mantık.

7. **Sekme sayfaları iki farklı isim kullanır.** `CronLogsController::index()`
   görünüme `files` **ve** `dbLogs` gönderir ve **yalnız aktif sekmeninkini**
   doldurur (`Controllers/CronLogsController.php:89-90`); diğer anahtar boş dizi olur.

## 6. Örnek (gerçek koddan)

Panel iskeleti bu klasörde değil, `Resources` altında — dashboard render çağrısı:

```php
// Bundles/RbnSuite/RbnAdmin/Controllers/RbnAdminController.php:147-158
return $this->render('RbnAdmin/dashboard', array_merge([
    'panel'            => $panel,
    'module'           => null,          // proje modülü zorunlu değil
    'groupProjects'    => $groupProjects,
    'projectKey'       => $projectKey,
    'activeProjectName'=> $activeProjectName,
    'projectDomain'    => $projectDomain,
    'botActive'        => $botActive,
    'aiReport'         => $aiReport
], $data));
```

## 7. İlgili belgeler

* [RbnAdmin README](README.md) · [Controllers.md](Controllers.md) · [Models.md](Models.md) · [Providers.md](Providers.md)
* [kavramlar/05-asset-sistemi](../../../kavramlar/05-asset-sistemi.md) — görünüm/asset çözümleme konvansiyonları
* [Core/Render](../../../Core/Render/README.md) — `ViewResolver`, `ViewProvider` (⏳ ayrı görev)
* [Resources/README](../../../Resources/README.md) — panel iskeleti (`Resources` görevi)
