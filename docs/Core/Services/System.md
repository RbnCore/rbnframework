# Core/Services/System — ayar servisi, oturum/kullanıcı, CDN, modül ve iletişim (14 dosya)

> **Doğrulanan kod tabanı:** `c23b431f` · **Tarih:** 2026-10-05 · **Yayın:** 0.9.5 = bu commit + sonrası; belge yalnız doğrulama anındaki kodu anlatır
> **Kaynak klasör:** `Core/Services/System/` — **14 `*.php`** = 6 kök + `Handlers/` 2 +
> `Managers/` 2 + `Models/` 1 + `Providers/` 1 (+ `Providers/Fluent/` 2).
> **Envanter:** 14 dosyanın **14'ü** anlatıldı.
> **Doğrulama platformu:** Windows + PHP 8.3; `SettingsConfig::DEFAULT_SETTINGS` (54 ayar)
> ve `SettingsService` kayıt çözümlemesi ölçüldü.

## 1. Ne işe yarar, kim kullanır

**Proje tarafının iş servisi.** Ayar okuma/yazma, oturum, kullanıcı, CDN,
modül keşfi ve iletişim kanalları. `Core/Services/Master` master DB'ye
konuşarken bu katman **proje** DB'si ve proje dosyalarıyla çalışır.

**Kimler çağırır:** neredeyse her şey —
`Core/Render/Resolvers/SeoResolver` (`service('settings')`),
`Core/Render/Providers/UI/FrontendProvider` (`settings->read(...)`),
`BaseRender::bootHarmony()` (`session()`), `BaseContextTrait` (`session()`),
`CliManager` (`module` keşfi), `CommunicationService` (bildirim/iletişim).

## 2. Klasör/dosya envanteri (14/14)

| Dosya | Görev | Önemli public yöntemler |
|---|---|---|
| `SettingsService.php` (208) | **Ayar orkestratörü.** Fluent kriter havuzu + tek dosya önbelleği (`settings_all`). `protected $targetModel = 'project.settings'`. | `boot()`, `withGroup(string $key): self`, `withProject(string $projectKey): self`, `withRole(string $role): self`, `withKey(string $key): self`, `decorate(bool $status=true,bool $select=true): self`, `groups(): self`, `all(): array`, `read(?string $groupKey=null): array`, `updateSettings(array $settings,string $projectKey): bool`, `bulkValueUpdate(array $data): self` · magic: `projectSettingsRepository`, `SettingsHandler` |
| `SettingsApiService.php` (58) | API anahtarı ayarları + bot aktivitesi açık/kapalı. `protected $targetModel = 'project.settingsApi'`. | `getOptions(): array`, `saveOption(array $data): bool`, `getBotActivityStatus(): bool`, `setBotActivityStatus(bool $status): bool` |
| `Handlers/SettingsHandler.php` (74) | Sunum katmanı süslemesi (select seçenekleri, rozetler). | `decorateSelectOptions(array &$settings): void`, `decorateStatusBadges(array &$settings): void` |
| `Models/SettingsConfig.php` (802) | **54 varsayılan ayarın** tek kaynağı (TR/EN etiket + alan tipi + rol). | `const NAME='RbnSettings'`, `const VERSION='1.0'`, `const DEFAULT_SETTINGS` (54 kayıt) |
| `Managers/SessionManager.php` (127) | Oturum yaşam döngüsü (SSoT): oku/yaz/sil/flash/regenerate/end. | `get(string $key,mixed $default=null): mixed`, `has(string $key): bool`, `set(string $key,mixed $value): bool`, `delete(string $key): bool`, `flash(string $key,mixed $value): bool`, `getFlash(string $key,mixed $default=null): mixed`, `all(): array`, `regenerate(bool $deleteOldSession=true): bool`, `end(): bool` · korumalı: `ensureStarted()` |
| `Managers/UserManager.php` (146) | Kullanıcı + etkinlik günlüğü yönetimi. | `store(array $data): bool`, `update(mixed $idOrData,?array $data=null): bool`, `updatePassword(int $id,string $password,string $role): bool`, `updateProfile(int $id,array $data): bool`, `updateEmail(int $id,string $email): bool`, `setRole(int $id,string $role): bool`, `destroy(int $id): bool`, `getActivities(array $filters=[]): array`, `clearActivities(): bool`, `allUsers(array $filters=[]): array`, `getUser(int $id): array`, `getProfileData(int $id): ?array`, `getUserStats(): array`, `getActivityStats(): array` |
| `ModuleService.php` (62) | Modül/paket kaydı ve metadata çözümü (Discovery sürücülerine delegasyon). | `registerBundles(string $type='map',?array $bundles=null): mixed`, `resolve(string $module,string $moduleSource='auto'): array`, `findSubModule(array $moduleMetadata,string $targetSub): array` · korumalı: `getDiscovery()`, `getDataDriver()` |
| `CdnService.php` (63) | CDN taban URL'si + alan yeniden yazma. | `getBaseUrl(string $context='projects'): string`, `resolve(string $path,string $context='projects'): string`, `resolveFields(object\|array &$data,array $fields,string $context='projects'): void`, `resolveCollection(iterable $collection,array $fields,string $context='projects'): iterable` |
| `Handlers/CdnHandler.php` (68) | Alan/koleksiyon düzeyinde CDN URL'si yeniden yazma. | `processFields(object\|array &$data,array $fields,string $baseUrl): void`, `processCollection(iterable $collection,array $fields,string $baseUrl): iterable` · korumalı: `updateValue(object\|array &$data,string $field,mixed $newValue): void` |
| `CommunicationService.php` (87) | İletişim kanalları (iletişim + bildirim) toplu giriş noktası. | `boot()`, `contacts(): ContactChannel`, `notifications(): NotificationChannel`, `getContactStats(): array`, `getNotificationStats(): array`, `refresh(): void` |
| `BaseProjectService.php` (112) | Ortak proje verisi (sayfa/SSS verisi, sosyal linkler, paylaşım linkleri). | `getProjectPayload(array $pageFilters=[],array $faqFilters=[]): array`, `getSocialLinks(): array`, `getShareLinks(string $url,string $title=''): array` |
| `Providers/LegalProvider.php` (69) | Yasal sayfa render'ı (KVKK/çerez vb.). | `render(?string $view,array $data,string $type): string` · korumalı: `getActiveRouteMapping(): array` |
| `Providers/Fluent/ContactChannel.php` (120) | **Fluent** iletişim kanalı (okundu/çöp/istatistik). | `find(int $id): array`, `markAsRead(int $id): bool`, `moveTrash(int $id): bool`, `restore(int $id): bool`, `delete(int $id): bool`, `emptyTrash(): int`, `unread(): self`, `read(): self`, `trash(): self`, `get(): mixed`, `count(): int`, `stats(): array` |
| `Providers/Fluent/NotificationChannel.php` (104) | **Fluent** bildirim kanalı. | `find(int $id): array`, `markAsRead(int $id): bool`, `delete(int $id): bool`, `clearAllRead(): bool`, `unread(): self`, `read(): self`, `get(): mixed`, `count(): int`, `stats(): array` |

*(13 satır = 11 dosya + `Providers/Fluent/` 2 dosya; `Console/` altındaki `Core/Services/System`
içi dosya yoktur.)*

## 3. Akış — `SettingsService::read()` (tek dosya önbelleği) (SettingsService.php:123-185)

```
SettingsService::read($groupKey = null)
 ├─ RECURSION GUARD: $this->isBusy ise [] döner                     :126-129, :182-184
 ├─ $projectKey = activeProjectKey()
 │    'default' veya 'master' ise [] döner                            :133-136
 └─ cache()->remember('settings_all', function() { … })                :138-173
      1. Statik $groupMapCache boşsa: fetch(['target'=>'groups','is_active'=>1])
         → id → group_key haritası (hem int hem string anahtarla)     :139-149
      2. fetch(['project_key'=>$key, 'is_active'=>1]) → tüm aktif ayarlar  :152-155
      3. group_id → group_key eşlemesi, sonuç [group][key] = value      :157-170
 → $groupKey ? ($all[$groupKey] ?? []) : $all
```

**Ölçülen mimari karar (`$cacheKeys`, `:24`):** `['settings_all', 'settings_shield']`
— RBN 3.5 "single table cache architecture": her grup için ayrı önbellek dosyası
spawn etmek yerine **tek** ana dosya.

**Tuzak — `$isBusy` geri çağırma kilidi (`:126-129`):** `read()` içinde bir
başka ayar okuması tetiklenirse **boş dizi** döner (sonsuz döngü yerine).
Yani "boş" sonucu iki farklı anlama gelebilir: gerçekten boş **ya da** kilit.

**Tuzak — `all()` kriter havuzunu temizler (`:112`):** `$this->criteria = []`
çağrılır; yani `withGroup()->all()` sonrası kriter **kalmaz**. Zincirleme
`withGroup('a')->withKey('x')->all()` kalıcı değildir.

**Tuzak — `read()` proje bağlamı yoksa boş döner (`:134-136`)** —
CLI'da `CliManager::switchProject()` yapılmadan `settings->read()` çağrılırsa
**sessizce** boş dizi döner.

## 4. Akış — `SessionManager` (Managers/SessionManager.php)

Her metot `ensureStarted()` ile başlar (`:21-26`):
`session_status() === PHP_SESSION_NONE && !headers_sent()` ise `@session_start()`.

| Metot | Uygulama | Satır |
|---|---|---|
| `get()` | `$_SESSION[$key] ?? $default` | `:31-35` |
| `set()` | `$_SESSION[$key] = $value` → `true` | `:49-54` |
| `flash()` | `$_SESSION['_rbn_flash'][$key]` | `:69-74` |
| `getFlash()` | oku **ve sil** | `:79-88` |
| `regenerate()` | `@session_regenerate_id($deleteOldSession)` — **session fixation koruması** | `:102-106` |
| `end()` | `$_SESSION=[]` + `session_unset` + `session_destroy` + **cookie düşürme** | `:111-126` |

**Tuzak — flash ön ekli (`:72`, `:82`):** Flash verisi `$_SESSION['_rbn_flash']`
altında tutulur, yani `all()` çıktısında **`_rbn_flash` anahtarı görünür**.

**Tuzak — `end()` cookie'yi de düşürür (`:120-123`):** `setcookie(name, '', time()-3600, '/')`.
Bu, `SessionProvider` (Storage katmanı) ile **çift** oturum yönetimi riski
taşır; hangi katmanın yetkili olduğu kodda **tek kaynakta belirlenmemiş**
görünüyor. *(Kod okundu, ölçülmedi.)*

## 5. Akış — `UserManager` ve korumalı alanlar (Managers/UserManager.php)

Yorumlardan çıkan **yetkili yazma yolları** (FW-BASE-2 T4 / FW-BASE-3 BULGU-2):

| Alan | Durum | Yetkili yol | Satır |
|---|---|---|---|
| `role` | `UsersModel::$guarded` içinde | `setRole()` | `:70-77` |
| `email` | `UsersModel::$guarded` içinde | `updateEmail()` | `:59-64` |
| profil (`name`) | TEK kolon (`firstname`/`lastname` **yok**) | `updateProfile()` | `:47-56` |
| parola | — | `updatePassword()` (rol parametresiyle birlikte) | `:41-44` |

`update()` **polymorphic**: `update($id, $data)` **veya** `update(['id'=>…, …])`
(`:29-36`). Yani `id` dizinin içinden çıkarılır ve **dizinin tamamı** veri
olarak gider.

**Tuzak:** `updatePassword($id, $password, $role)` — **rol daima** güncellenir.
Yani parola değiştirme işlemi rolü de yazar; yalnız parola değiştirmek isteyen
çağıran mevcut rolü geçirmek **zorundadır**.

## 6. `SettingsConfig::DEFAULT_SETTINGS` — ölçülen 54 ayar

Ölçülen kayıt yapısı (13 alan):
`group_key, setting_key, label_tr, label_en, field_type, field_options,
help_text_tr, help_text_en, setting_value, required_role, is_active, order_num`

**Ölçülen 54 `setting_key` (sırayla):**

| Grup | Anahtarlar |
|---|---|
| `appearance` | `theme_color_primary` (#0ea5e9), `theme_color_secondary` (#0284c7) |
| `company` | `company-name`, `company-slogan`, `company-map-location`, `company-favicon`, `company-logo` |
| `contact` | `contact-email`, `contact-adress`, `contact-phone`, `whatsapp` |
| e-posta | `email_enabled`, `email_contact_address`, `email_from_address`, `email_from_name`, `email_admin_address`, `contact_email_subject`, `contact_auto_reply`, `contact_auto_reply_subject`, `smtp_host`, `smtp_port`, `smtp_username`, `smtp_password` |
| entegrasyon | `google_analytics_code`, `google_adsense_code`, `head_scripts`, `body_scripts`, `footer_scripts` |
| genel | `default_language`, `date_format`, `timezone`, `site_currency`, `remember_me_duration`, `session_timeout` |
| `seo` | `meta-title`, `meta-description`, `meta-keywords`, `og-title`, `og-description`, `og-image`, `og-type`, `og-locale`, `seo-report`, `seo-advice`, `seo-gemini-advice` |
| sosyal | `facebook`, `twitter`, `instagram` |
| reklam | `adsense_status`, `adsense_client_id`, `ads_slot_feed`, `ads_slot_content_top`, `ads_slot_content_bottom`, `ads_slot_sidebar` |

**Tuzak (ölçüldü):** `DEFAULT_SETTINGS` **sıralı (0..53) dizi**dir, `group_key`
ile indekslenmiş bir harita **değildir**. `SeoResolver` `settings->read('seo')`
çağırır ve `['seo']['meta-title']` bekler; bu harita **repository'den** üretilir,
sabit yalnız **tohum verisidir**.

**Tuzak (güvenlik):** 54 ayarın `required_role` alanı `'admin'` (bazıları
`'developer'`). Yazma yetkisi bu alandan gelir — `SettingsConfig` tek
kaynak olduğu için **bir ayarın rolünü değiştirmek tüm projeleri etkiler**.

**Tuzak (yazım):** `contact-adress` **İngilizce yazılmış** (`adress`).
`SeoResolver` ve `FrontendProvider` bu anahtarı **bu yazımla** okur
(`FrontendProvider.php:37` `$contact`), yani düzeltilirse o da güncellenmelidir.
*(Kod değiştirilmedi.)*

## 7. Akış — `CommunicationService` + Fluent kanallar (CommunicationService.php)

```
CommunicationService::boot()                  :30
 ├─ contacts() → ContactChannel              :49-55   (new ContactChannel, :18-29)
 ├─ notifications() → NotificationChannel    :57-65   (new NotificationChannel, :18-29)
 ├─ getContactStats() / getNotificationStats()  :67-81
 └─ refresh()                                 :83-85
```

Her iki kanal `BaseChannel`'ı (`Core/Base/Patterns/BaseChannel.php`) genişletir
ve **aynı beşli kalıbı** paylaşır: `find()`, `markAsRead()`, `delete()`,
`unread()`, `read()`, `get()`, `count()`, `stats()`.
Fark: `ContactChannel` çöp kutusu yönetimi ekler (`moveTrash`, `restore`,
`emptyTrash`, `trash`); `NotificationChannel` `clearAllRead()` sunar
(`NotificationChannel.php:45`).

**Ölçülen çözümleme:** `service('communication')` →
`Rbn\Framework\Core\Services\System\CommunicationService`.

## 8. Akış — `CdnService` + `CdnHandler` (63 + 68 satır)

```
CdnService::getBaseUrl($context = 'projects')     CdnService.php:21-39
CdnService::resolve($path, $context)             :40-50
CdnService::resolveFields(&$data, $fields, $ctx) :51-58  → CdnHandler::processFields()
CdnService::resolveCollection($collection, …)    :59-61  → CdnHandler::processCollection()
CdnHandler::processFields(&$data, $fields, $baseUrl)  Handlers/CdnHandler.php:19-47
CdnHandler::updateValue(&$data, $field, $newValue)     :48-59   ← dizi/nesne ikili
```

`updateValue()` hem `object` hem `array` kabul eder (`object|array &$data`) —
yani model nesnesi ve ham dizi aynı koddan geçer.

## 9. Akış — `ModuleService` ve `new` kullanımı (ModuleService.php)

```
ModuleService::registerBundles('map', ?array)   :41-44 → ModuleDiscoveryDriver::registerBundles()
ModuleService::resolve($module, $source='auto') :49-52 → ModuleDataDriver::getModuleInfo()
ModuleService::findSubModule($meta, $targetSub) :57-61 → ModuleDataDriver::deepSearch()
getDiscovery() → $this->discoveryDriver ??= new ModuleDiscoveryDriver()   :28-31
getDataDriver() → $this->dataDriver      ??= new ModuleDataDriver($this->rbn)  :33-36
```

**Anayasa §1 (`new` yasağı) istisnası (ölçüldü):** Bu dosya **`new` kullanıyor**
(`:30`, `:35`). Anayasa §1 bileşen **kaydı** için `new` yasağı koyar; sürücü
nesneleri kayıt sistemi dışındaki, elle tutulan bağımlılıklardır (`??=` tembel
atanır, tek örnek yaşar). `ModuleService` bu yüzden kayıt altında **`services`
kaydına sahip** ama sürücüleri Discovery ile değil elle tutar.

## 10. Tuzaklar ve kurallar (kodda görülen + ölçülen)

1. **`SettingsService::$cacheKeys` iki anahtar** (`settings_all`,
   `settings_shield`) — `:24`. Yeni bir grup eklenirse ayrı önbellek dosyası
   **değil**, `settings_all` içine yazılır.
2. **`SettingsService::all()` kriter havuzunu sıfırlar** (`:112`) — kriter
   kalıcı değildir.
3. **`SettingsService::read()` geri çağırma kilidi** (`:126-129`) — iç içe
   okumada `[]`.
4. **`SessionManager::getFlash()` oku ve siler** (`:82-87`) — ikinci çağrı
   `default` döner.
5. **`UserManager::update()` polymorphic** (`:29-36`) — `['id'=>…]` biçiminde
   `id` dizide kalır ve veri olarak da gider; repository'nin `id` alanını
   `$guarded`'a koyup temizlemesi beklenir.
6. **`ModuleService` elle `new` yapar** (§9) — kayıt sistemi dışı.
7. **`ContactChannel::emptyTrash()` `int` döner** (silinen kayıt sayısı),
   `NotificationChannel::clearAllRead()` `bool` döner — iki kanalın dönüş
   tipleri farklıdır.
8. **`LegalProvider::render(?string $view, array $data, string $type)`** —
   `$type` zorunlu; `getActiveRouteMapping()` korumalı.

## 11. Örnek (gerçek koddan)

```php
// Core/Services/System/Managers/SessionManager.php:102-106  (session fixation koruması)
public function regenerate(bool $deleteOldSession = true): bool
{
    $this->ensureStarted();
    return @session_regenerate_id($deleteOldSession);
}
```

```php
// Ölçülen çıktı — SettingsConfig::DEFAULT_SETTINGS
//   tip: array (sıralı), adet: 54
//   ilk kayıt:
//   {"group_key":"appearance","setting_key":"theme_color_primary",
//    "label_tr":"Birincil Tema Rengi","label_en":"Primary Theme Color",
//    "field_type":"text","field_options":null,
//    "setting_value":"#0ea5e9","required_role":"admin","is_active":1,
//    "order_num":10}
```

## 12. İlgili belgeler

* [Core/Services genel](README.md) · [Master](Master.md) · [Gatekeepers](Gatekeepers.md) ·
  [Console](Console/README.md)
* [Core/System/Config.md](../System/Config.md) (`Config` önceliği) ·
  [Core/System/Discovery.md](../System/Discovery.md) (modül sürücüleri) ·
  [Core/System/Storage.md](../System/Storage.md) (cache/oturum sağlayıcıları) ·
  [Core/Database/Repositories.md](../Database/Repositories.md) (`project.settings`, `project.user`)
* [Kavram: yapılandırma](../../kavramlar/02-yapilandirma.md) ·
  [Kavram: mimari harita](../../kavramlar/01-mimari-harita.md) ·
  [Açık sorular §1.13](../../acik-sorular.md) (`contact-adress` yazımı)