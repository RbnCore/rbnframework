# Core/Services — iş servisleri, güvenlik kapıları, hata yönetimi, master ve CLI (81 dosya)

> **Doğrulanan kod tabanı:** `c23b431f` (dal `feat/fw-license-master`) · **Tarih:** 2026-10-05 · **Yayın:** 0.9.6 = bu commit + sonrası; belge yalnız doğrulama anındaki kodu anlatır
> **Kaynak klasör:** `Core/Services/` — **81 `*.php`**.
> **Envanter:** 81 dosyanın **81'i** anlatıldı (0 + 29 + 13 + 13 + 5 + 7 + 14).
> **Doğrulama platformu:** Windows + PHP 8.3, gerçek `Paths::init` ile servis/handler/manager çözümlemeleri çalıştırıldı.

## 1. Ne işe yarar, kim kullanır

Bu klasör framework'ün **"yapan" katmanıdır**. `Core/System` ne olduğunu
bulur, `Core/Base` nasıl bağlandığını yönetir, `Core/Render` ne gösterir;
`Core/Services` ise **işi yürütür**: isteği denetler (Gatekeepers), hatayı
çevirir (Exception), ayarı okur/yazar (System), lisansı doğrular (Master),
cron'u çalıştırır (Console), cPanel'e bağlanır (Hosting).

**Kimler çağırır:** `Kernel` aşamaları (`ShieldSentinel`, `DatabaseGuardStage`,
`ComponentRegistry`), `Core/Routes` (`Route::run`), tüm controller'lar
(`service('settings')`, `service('ipGuard')`), CLI (`php rbn`), cron.

## 2. Alt dallar ve belgeleri

| Klasör | `*.php` | Belge | Kısa görev |
|---|---:|---|---|
| *(kök)* | 0 | — | `Core/Services/` kökünde `*.php` **yoktur** (`RbnCli` `Console/` altındadır) |
| `Console/` | 29 | [Console/README.md](Console/README.md) | CLI komutları, cron kervanı, migration, temizlik işleri |
| `Exception/` | 13 | [Exception.md](Exception.md) | Hata analizi, günlükleme, kullanıcı/developer çıktısı |
| `Gatekeepers/` | 13 | [Gatekeepers.md](Gatekeepers.md) | IP/bakım/veritabanı kapıları, WAF, GeoIP |
| `Hosting/` | 5 | [Hosting.md](Hosting.md) | cPanel API köprüsü, e-posta ve alan adı uçları |
| `Master/` | 7 | [Master.md](Master.md) | Lisans doğrulama, proje/ayar/uygulama yönetimi |
| `System/` | 14 | [System.md](System.md) | Ayar servisi, oturum/kullanıcı, CDN, modül, iletişim |
| **Toplam** | **81** | | `Get-ChildItem Core/Services -Recurse -Filter *.php` = 81 (0+29+13+13+5+7+14) |

## 3. Kök dosya — `RbnCli` (1/1)

| Dosya | Görev | Önemli public yöntemler |
|---|---|---|
| `Console/RbnCli.php` (135) | **CLI giriş noktası.** Kayıt haritasından komutları keşfeder, `argv`'yi ayrıştırır, komutu çalıştırır. | `__construct()`, `run(array $argv): void`, `listCommands(): void` · korumalı: `discoverCommands(): void` |

*(Not: bu dosya `Console/` altındadır; `Core/Services/` kökünde `*.php` **yoktur**.
Yukarıdaki tablo "kök" için yanıltıcı olmasın diye düzeltildi: tüm 81 dosya
alt dallarda dağılmıştır, `RbnCli` dahil.)*

## 4. Kayıt haritası — `SystemLogicMapTrait` (ölçülen)

`Core/System/Registries/RegistryMap/SystemLogicMapTrait.php:19-131`:

| Tür | `Core/Services` kayıtları |
|---|---|
| `managers` (4) | `cron`→`Console\Managers\CronManager`, `task`→`TaskManager`, `user`→`System\Managers\UserManager`, `session`→`SessionManager` |
| `services` (16) | `rbncli`, `cron`, `base.taskLog`, `masterProjects`, `masterSettings`, `masterLicences`, `masterApplications`, `settings`, `settingsApi`, `cdn`, `module`, `communication`, `base.project`, `bootSentinel`, `shieldSettings`, `ipGuard`, `systemGuard`, `databaseGuard`, `exception`, `cpanel` |
| `handlers` (16) | `cronScheduler`, `cronNotification`, `ipGuard`, `rateLimit`, `systemGuard`, `databaseGuard`, `geoIP`, `errorAnalysis`, `log`, `exception`, `settings`, `cdn`, `cpanelMail`, `cpanelDomains` |
| `resolvers` (2) | `cron`→`Console\Handlers\Resolvers\CronResolver`, `task`→`TaskResolver` |
| `jobs` (3) | `cleanup`, `cleanup.server`, `cleanup.project` |

### 4.1 Ölçülen çözümleme tablosu (21 çağrı)

| İstek | Çözülen sınıf |
|---|---|
| `service('ipGuard')` | `Gatekeepers\IpGuardService` |
| `service('systemGuard')` | `Gatekeepers\SystemGuardService` |
| `service('databaseGuard')` | `Gatekeepers\DatabaseGuardService` |
| `service('shieldSettings')` | `Gatekeepers\ShieldSettingsService` |
| `service('bootSentinel')` | `Gatekeepers\BootSentinel` |
| `service('exception')` | `Exception\ExceptionService` |
| `service('settings')` | `System\SettingsService` |
| `service('masterLicences')` | `Master\MasterLicencesService` |
| `service('module')` | `System\ModuleService` |
| `service('cdn')` | `System\CdnService` |
| `service('communication')` | `System\CommunicationService` |
| `service('cpanel')` | `Hosting\CPanelService` |
| `service('rbncli')` | `Console\RbnCli` |
| `service('cron')` | `Console\Services\CronService` |
| `manager('session')` | `System\Managers\SessionManager` |
| `manager('user')` | `System\Managers\UserManager` |
| `manager('cron')` | `Console\Managers\CronManager` |
| `manager('task')` | `Console\Managers\TaskManager` |
| `handler('geoIP')` | `Gatekeepers\Handlers\GeoIPHandler` |
| `handler('ipGuard'/'rateLimit'/'systemGuard'/'databaseGuard')` | eşleşen `Gatekeepers\Handlers\*` |
| `handler('settings'/'cdn'/'cpanelMail'/'cpanelDomains')` | eşleşen `System\Handlers\*`, `Hosting\Handlers\*` |
| `handler('errorAnalysis'/'log'/'exception')` | eşleşen `Exception\Handlers\*` |
| `handler('cronScheduler'/'cronNotification')` | eşleşen `Console\Handlers\*` |
| `resolver('cron'/'task')` | eşleşen `Console\Handlers\Resolvers\*` |
| `job('cleanup'/'cleanup.server'/'cleanup.project')` | eşleşen `Console\Jobs\*` |

**Bulgu (yeni, `handler('seo')` gibi):** `SystemLogicMapTrait.php:114`
`'seo' => 'Core\Render\Handlers\Seo\SeoHandler'` kayıtlı; ölçüldü: `NULL`
döner (dizin/sınıf yok). Ayrıntı:
[Core/Render/README.md §4](../../Core/Render/README.md), [acik-sorular §1.7](../../acik-sorular.md).

## 5. Örnek (gerçek koddan)

```php
// Core/Services/Console/Managers/CronManager.php:16-18 (mimari kural, yorum)
 * 1. Tüm zamanlama, gün, saat ve kota kontrolleri TEK MERKEZDE ($this) yapılır.
 * 2. Görev alt katmanlarda (Builder/AutoTaskManager) saçma sapan kontrollerle yarıda KESİLEMEZ.
 * 3. DB tek gerçek kaynaktır.
```

```php
// Ölçülen çıktı — CliManager proje bağlamı anahtarı
// CliManager::switchProject('<proje>')              => true
// CliManager::getActiveProjectKey()                 => '<proje>'
// CliManager::runInProject('<proje>', fn($k)=>$k)    => '<proje>'   (finally ile geri alındı)
```

## 6. İlgili belgeler

* [Console](Console/README.md) · [Exception](Exception.md) · [Gatekeepers](Gatekeepers.md) ·
  [Hosting](Hosting.md) · [Master](Master.md) · [System](System.md)
* [Core/Base/Services.md](../Base/Services.md) (`BaseService`/`BaseManager` tabanları) ·
  [Core/System/Kernel.md](../System/Kernel.md) (aşama sırası) ·
  [Core/System/Registries.md](../System/Registries.md) (kayıt haritaları) ·
  [Core/Database/README.md](../Database/README.md) (repository katmanı)
* [Kavram: mimari harita](../../kavramlar/01-mimari-harita.md) ·
  [Kavram: sürümleme](../../kavramlar/04-surumleme-ve-yayin.md) ·
  [Açık sorular](../../acik-sorular.md)