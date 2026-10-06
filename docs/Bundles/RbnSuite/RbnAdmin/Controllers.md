# RbnAdmin/Controllers — 11 kontrolör

> **Doğrulanan kod tabanı:** `d49b4413` (dal `feat/fw-license-master`) · **Tarih:** 2026-10-05 · **Yayın:** 0.9.4 = bu commit + sonrası; belge yalnız doğrulama anındaki kodu anlatır
> **Kaynak klasör:** `Bundles/RbnSuite/RbnAdmin/Controllers/` — **11 `*.php`**
> **Envanter:** 11 dosyanın 11'i anlatıldı.
> Üst belge: [README.md](README.md)

## 1. Ne işe yarar, kimler kullanır

On bir kontrolör, RbnAdmin paketinin **tüm HTTP giriş noktasıdır**. Hiçbiri veri
katmanına doğrudan SQL yazmaz; isteği doğrular, alt servise/repozitoryoya devreder,
sonucu `handleResult()` ile kullanıcıya döner veya `render()` ile görünüm basar.

Tümü `BaseController` → `BaseComponent` soyundan gelir; `#[Module]` (kök) veya
`#[SubModule]` (alt) özniteliğiyle kimliklerini bildirirler. `#[SubModule]`'ın
`service`/`repository`/`manager`/`model` alanları, kuruluşta (hydration) hangi
bileşenin " aktif servis" olacağını belirler
(`Core/Base/Concerns/Hydration/ComponentHydratorTrait.php:56-107`).

## 2. Dosya envanteri

| Dosya | `#[SubModule]` | Türeyen | Aktif bileşen |
|---|---|---|---|
| `RbnAdminController.php` | — (`#[Module(name:'rbnadmin', context:'backend')]`, `:17-21`) | `BaseController` | yok (kok) |
| `ModalController.php` | — (özniteliksiz, `:14`) | `BaseController` | yok |
| `WebtrafficController.php` | `entity:'webtraffic', service:'analytics'` (`:10-13`) | `RbnAdminController` | `service('analytics')` |
| `UserManagementController.php` | `entity:'user', manager:'user'` (`:14-17`) | `RbnAdminController` | `manager('user')` |
| `ContactController.php` | `entity:'contact', service:'communication'` (`:13-16`) | `RbnAdminController` | `service('communication')` |
| `NotificationController.php` | `entity:'notification', service:'communication'` (`:12-15`) | `RbnAdminController` | `service('communication')` |
| `AdminSettingsController.php` | `entity:'setting', service:'settings'` (`:12-15`) | `RbnAdminController` | `service('settings')` |
| `BotSettingsController.php` | `entity:'settingsApi', service:'settingsApi'` (`:12-15`) | `RbnAdminController` | `service('settingsApi')` |
| `CronLogsController.php` | `entity:'cron', service:'cron', modal:'Setting/Partials/modal'` (`:9-13`) | `RbnAdminController` | `service('cron')` |
| `HostmailhubController.php` | `entity:'hostmailhub', handler:'cpanelMail'` (`:16-19`) | `RbnAdminController` | `handler('cpanelMail')` |
| `SeoReportController.php` | `entity:'seo-report'` (`:13-15`) | `RbnAdminController` | türetilmiş ada göre (`seo-report`) |

**Gözlem:** `CronLogsController` özniteliğinde `service: 'cron'` yazmasına rağmen
metotların hiçbirinde `$this->service` kullanılmaz; hepsi
`$this->repository('master.cronJob')` / `$this->repository('project.cronLog')` çağırır
(`:22,52,104,186,235,250`). `service('cron')` yalnız `BotSettingsController::save()`
tarafından dolaylı kullanılır (`BotSettingsController.php:153`).

## 3. Akışlar (dosya:satır kanıtlı)

### 3.1 Ortak kalıp: form → servis → `handleResult()`

```
POST /admin/users/store
  → ControllerResolver (Route.php:143-153 ile kayıt edilen route)
  → UserManagementController (CrudControllerTrait::create)   CrudControllerTrait.php:24
      ├─ request->rawAll()                                   CrudControllerTrait.php:30
      ├─ crudInput() → hedef modelin $fillable beyaz listesi CrudControllerTrait.php:71-98
      └─ activeService->create($data) → handleResult(…, 'create')  CrudControllerTrait.php:31-34
```

Rota tablosu `ModuleData::registerRoutes()` içindedir
(`Models/ModuleData.php:52-158`); `users` grubu 11 uç tanımlar (`:87-105`).

### 3.2 Durum değiştirme (AJAX)

`CategoriesController` (RbnStudio) ve `CronLogsController::toggleCron()` (`:229-239`)
aynı normalizasyonu kullanır: `'1' | true | 'true' | 'on' → 1`, aksi hâlde `0`.
`handleResult($result, null, false, 'status')` çağrısında 3. parametre `false`
olduğu için `returnPath(false)` → yönlendirme yapılmaz; tarayıcıda kalan sayfa
korunur (`ActionControllerTrait.php:227`).

### 3.3 Modal görünümü üç farklı yolla üretiliyor

| Yöntem | Kullanıcı | Veri kaynağı |
|---|---|---|
| `modal()` **kendi** metodunu yazar | `BotSettingsController` (`:170-186`), `CategoriesController` (RbnStudio `:130-150`), `DraftsController` (RbnStudio `:148-164`) | doğrudan |
| `getModalData()` kancası | `UserManagementController` (`:56-72`), `ContactController` (`:58-72`), `NotificationController` (`:34-48`), `HostmailhubController` (`:79-99`) | `ActionControllerTrait::modal()` çağırır (`:260-262`) |
| `$modalView` alanı ezilir | `UserManagementController` dinamik olarak (`type==='role'` ise `User/Partials/modal_role`, `:63`); `BotSettingsController` sabit (`'Setting/Partials/modal'`, `:19`) | görünüm yolu |

### 3.4 Dosya sistemiyle çalışan tek kontrolör: `CronLogsController`

```
GET /admin/cron/cronlogs?tab=files
  → CronLogsController::index()                            :44
      ├─ repository('project.cronLog')->getLogs(300, $pk)  :55   (DB sekmesi)
      └─ glob(Paths::project()->root('Storage/logs/cron') . '/*.jsonl')  :60-79
          └─ yalnız dosya adı '_<proje anahtarı>.jsonl' ile BİTENLER alınır  :70
```

Dosya adı süzgeci `str_contains($filename, '_' . $pk . '.jsonl')` ile yapılır
(`:70`) — bu **sona eki** değil, **içinde geçme** testidir; `x_abc.jsonl.bak`
benzeri bir ad teorik olarak eşleşir, ama glob yalnız `*.jsonl` döndürdüğü için
pratikte kapalıdır.

## 4. Ayar anahtarları ve varsayılanlar

| Değer | Yer | Not |
|---|---|---|
| `POST admin/bot-settings/save` → `{settings:{…}, cron:{…}}` | `BotSettingsController::save()` (`:116-119`) | İkisi de `nullable|array`; boşsa hiçbir yazma yapılmaz |
| `POST admin/settings/save` → `{settings (required|array), type (required), project (nullable)}` | `AdminSettingsController::update()` (`:89-93`) | `project` boşsa `'default'` (`:95`) |
| `POST admin/users/password/{id}` → `{new_password (required|min:8), repeat_password (required|same:new_password)}` | `UserManagementController::password()` (`:80-83`) | Profil şifresi için **aynı kurallar** (`:175-178`) |
| `POST admin/users/profile/update` → `{name (required), email (required|email)}` | `UserManagementController::profileUpdate()` (`:159-162`) | `firstname`/`lastname` **yok** — şemada da yok (`:148-151`) |
| `POST admin/cron/toggle-cron` → `{id, status\|value}` | `CronLogsController::toggleCron()` (`:231-232`) | `status` yoksa `value` okunur |
| `admin_theme` aralığı | `RbnAdminController::saveTheme()` (`:170-172`) | `regex:/^#[0-9A-Fa-f]{6}$/` — `#fff` kısa biçim **reddedilir** |
| `admin/cronlogs/view` sayfa boyutu | `index()` 15 (`:85`), `view()` dosya dalı 50 (`:162`) | DB kaydı dalı 1 (`:128`) |
| `admin/webtraffic` sayfa boyutu | `WebtrafficController::report()` yok; `logs()` `paginate($allHits)` → varsayılan 10 (`ViewTrait.php:110`) | |

## 5. Tuzaklar

1. **`BotSettingsController::status()` `parent::status()` çağırır, sonra yanlış
   nesneye yazar.** `CrudControllerTrait::status()` çalışır, ama
   `clearProjectCache()` **doğrudan sınıf adıyla** çağrılır
   (`BotSettingsController.php:222-228`) — bu yol `service('settingsApi')` değil,
   `BootCacheProvider`'ın statik metodudur. Aynı statik çağrı `save()` (`:163`) ve
   `create()` (`:213`) içinde de üç kez tekrarlanmıştır.

2. **`AdminSettingsController` nesne/dizi karışımı okuyor — ve bu **güvenli**,
   ama okuyucu için yanıltıcıdır.** Aynı grup kümesi üç farklı stille okunur:
   `$group->group_key` (`:35`), `$group['group_key']` (`:52`) ve
   `is_object($group) ? $group['id'] : $group['id']` (`:62`, `:71`).
   **Ölçüldü:** `SettingsRepository::fetchGroups()` → `->get()->all()` model
   **nesneleri** döndürür (`Core/Database/Engine/Traits/Query/ExecutionTrait.php:38-51`),
   ama `BaseModel implements \ArrayAccess` + `HybridAccessTrait::offsetGet()`
   (`Core/Base/Data/BaseModel.php:47,50` · `Core/Base/Concerns/Data/HybridAccessTrait.php:34-37`)
   sayesinde **dizi erişimi de çalışır**. Yani `:52` ölü bir hata değildir —
   **kırılganlıktır**: `fetchGroups()` bir gün düz dizi döndürmeye başlarsa
   `:52` sessizce `''` döner ve `$activeGroup` hiç bulunamaz (ayarlar ekranı
   grupsuz açılır). Üç stilin tek stile (`:35`'teki gibi) indirgenmesi güvenli olur.
   Aynı "nesne mi dizi mi" belirsizliği `badgeCounts` hesabında da var (`:60-76`).

3. **`HostmailhubController` alan adı beyaz listesi.** `create`/`delete`/
   `changePassword` üçünde de istenen alan adı `getActiveDomains()` içinde
   değilse işlem **önce reddedilir** (`:114-117`, `:147-150`, `:181-184`).
   `getActiveDomains()` boş grup dönerse tek elemanlı liste üretir (`:215-219`).

4. **`HostmailhubController::delete()` rota imzası tuzaklı.** Rota
   `POST hostmailhub/delete/{email?:[a-zA-Z0-9_\.-]+}`'dir
   (`ModuleData.php:76`); metot ise `$this->request->input('email') ?? $id`
   okur (`:141`). `@` karakteri alan adına ait olduğu için URL'de **gönderilemez**;
   pratikte `email` gövde alanından gelmelidir.

5. **`UserManagementController::delete()` kendini silmeyi engeller.**
   Oturumdaki `user_id` ile hedef karşılaştırılır (`:114-117`); eşitse işlem
   yapılmaz ve hata mesajı gösterilir. Bu denetim **yalnız bu yolda** vardır;
   `CrudControllerTrait::delete()` benzeri koruma taşımaz.

6. **`UserManagementController::updateRole()` korumalı alanı yetkili yoldan yazar.**
   Genel `update()` yolu `role` alanını yazamaz (`UsersModel::$guarded`); rol
   değişimi yalnız `UserManager::setRole()` üzerinden olur ve her çağrı
   `security` kanalına `MASS_ASSIGNMENT_AUTHORIZED_WRITE` olarak düşer (`:95-99`).

7. **`ModalController` çözülemeyen `type` için istisna atmaz**, uyarı bloğu basar
   (`:72-80`). Buna karşılık `content()` **AJAX değilse 403** döner (`:21-24`) —
   korumanın yalnız yarısı burada.

8. **`SeoReportController` "thin controller" standardı.** 27 satır, tek ekran,
   doğrudan `service('seoScanner')->getReport()`. Öznitelikte `service` yok,
   bu yüzden aktif servis türetilmiş ada (`seo-report`) göre çözülür (`:13-15`).

9. **`WebtrafficController::googleAnalytics*()` tarih varsayılanı 30 gün**
   (`:321-324`, `:351-354`); `report()` ise **7 gün** (`:194-195`). İki ekran
   farklı pencere kullanır ve bu bir hata değil, bilinçli seçimdir.

## 6. Örnek (gerçek koddan)

Kendi parolasını değiştirmek isteyen kullanıcının kendi profil şifresi — hedef kimlik
**istemekten değil oturumdan** gelir:

```php
// Bundles/RbnSuite/RbnAdmin/Controllers/UserManagementController.php:173-186
$data = $this->request->form([
    'new_password'    => 'required|min:8',
    'repeat_password' => 'required|same:new_password'
]);

$targetId = (int) $this->session()->get('user_id');
$user     = $this->manager->getUser($targetId);
$role     = $user['role'] ?? 'user';
$result   = $this->manager->updatePassword($targetId, $data['new_password'], $role);

$this->handleResult($result, 'Şifreniz başarıyla güncellendi.', 'users/profile');
```

## 7. İlgili belgeler

* [RbnAdmin README](README.md) — paket geneli, ayar anahtarları, 15 tuzak
* [RbnAuth](../RbnAuth/README.md) — `AuthRole::ROLES`, `handler('access')->can()`
* [Core/Base/Web](../../../Core/Base/Web.md) — `BaseController` + `CrudControllerTrait`
* [Core/Routes](../../../Core/Routes/README.md) — rota kaydı ve middleware grupları
