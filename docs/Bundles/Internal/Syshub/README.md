# Bundles/Internal/Syshub — Sistem, güvenlik, veri temizliği ve veritabanı konsolu

> **Doğrulanan kod tabanı:** `d49b4413` (dal `feat/fw-license-master`) · **Tarih:** 2026-10-05 · **Yayın:** 0.9.3 = bu commit + sonrası; belge yalnız doğrulama anındaki kodu anlatır
> **Kaynak klasör:** `Bundles/Internal/Syshub/` — 47 `*.php` + 20 `*.rbn.php` görünüm.
> **Envanter:** 47 php dosyasının 47'si bu belgelerde anlatıldı (aşağıdaki alt dal tabloları).

## 1. Ne işe yarar, kimler kullanır

Syshub, **framework'ün kendi kendini gözetleyen panosudur**: sistem sağlığı
istatistikleri, bakım modu, IP engelleme / güvenilir liste / hız sınırı, depolama
temizliği ve ham SQL terminali. Diğer modüllerin (RbnAdmin, RbnAuth, projeler)
kullandığı hiçbir işlevi **yoktur**; tek işi işletim katmanını yönetmektir.

**Kimler çağırır:** yalnız geliştirici paneli (`panel: 'developer'`, `Module`
attribute'i `SyshubController`'da). `context: 'developer'` olduğu için bu
modülün rotaları diğer rollere açılmaz (`ModuleData.php:17`).

## 2. Alt dallar ve belgeleri

| Alt dal | `*.php` | Belge | Kısa görev |
|---|---:|---|---|
| `Controllers/` (kök) | 3 | (bu belge §2.1) | `SyshubController` (dashboard), `PurgeController`, `MaintenanceController` |
| `Controllers/Security/` | 5 | [Security.md](Security.md) | Firewall, IP blok, whitelist, rate limit, güvenlik paneli |
| `Controllers/DbConsole/` | 3 | [DbConsole.md](DbConsole.md) | DB paneli, SQL terminali, tablo gezgini |
| `Handlers/` | 4 | (bu belge §2.2) | İstatistik toplayıcılar + ham SQL yürütücüsü |
| `Models/` | 3 | (bu belge §2.3) | `ModuleData` (rota + kayıt haritası), `SyshubMap`, `PurgeConfig` |
| `Providers/` | 7 | (bu belge §2.4) | Veri katmanı: shield ayarları, rate limit, whitelist, bakım, cron, DB keşfi |
| `Services/` | 2 | (bu belge §2.5) | Orkestratör servisler |
| `Views/` | 20 | (alt dallara dağılmış, aşağıda listeli) | Panel arayüzü |
| **Toplam** | **47** | | `find Bundles/Internal/Syshub -name '*.php' \| wc -l` = 47 |

### 2.1 `Controllers/` kök (3)

| Dosya | Görev | Önemli public yöntemler |
|---|---|---|
| `SyshubController.php` | Modülün kök kimliği; `#[Module(name:'syshub', data:ModuleData::class, service:'syshub', panel:'developer', context:'panel')]`. Dashboard istatistiklerini toplayıp `Dashboard/index` görünümünü render eder. | `index(): void` (:23), `fixPhpLimits(): void` (:43) |
| `PurgeController.php` | `#[SubModule(entity:'datapurge', service:'syshub', handler:'purge')]`. Depolama temizliği ekranının tüm uçları: liste, içerik göster, temizle, toplu sil, zip indir. | `index()` (:26), `module(string $type)` (:55), `show(string $type,$path=null)` (:98), `clear(string $type)` (:134), `clearAll()` (:151), `bulkDelete($target=null,string $method='bulkDestroy',$redirect=null)` (:170), `bulkDownload($ids=null,$service=null,string $method='downloadBulk')` (:196), `download($id=null,$service=null,string $method='download')` (:217) |
| `MaintenanceController.php` | `#[SubModule(entity:'maintenance', service:'syshub')]`. Bakım modu ekranı; whitelist IP listesini `master.ipWhitelist`'ten okur ve sayfalamayı elle kurar. | `index(): void` (:21), `getModalData($id=null,?string $view=null): array` (:44) |

### 2.2 `Handlers/` (4)

| Dosya | Görev | Önemli public yöntemler |
|---|---|---|
| `SyshubHandler.php` | Sistem panosunun istatistik motoru: depolama klasörlerini özyinelemeli tarar, güvenlik sayaçlarını toplar, modül sayısını fiziksel sayar. | `getDashboardStats(): array` (:26), `getSystemInfo(): array` (:76 *protected*), `getFolderStats(string $path): array` (:94 *protected*), `getModuleCount(): int` (:119 *protected*) |
| `DataPurgeHandler.php` | Depolama sağlayıcılarını (`cache/logs/sessions/backups/exports/uploads/view`) tek noktadan toplar; temizleme/silme/okuma yapar. | `getDashboardStats(?string $projectKey=null): array` (:24), `getProviderStats(?string $projectKey=null): array` (:54), `clear(string $type,?string $resource=null,?string $projectKey=null): bool` (:84), `bulkDelete(string $type,array $ids,?string $projectKey=null): bool` (:99), `getFileContent(string $type,string $filename,?string $projectKey=null): mixed` (:117), `getLogData(string $filename,$content,?string $projectKey=null): array` (:144) |
| `SyshubSecurityHandler.php` | Güvenlik panelinin veri derleyicisi; GeoIP zenginleştirmesini tek yerde toplar. | `getSecurityDashboardData(): array` (:27), `buildStatsTree(array $coreSecurityStats): array` (:49 *protected*), `getEnrichedRecentAttempts(): array` (:75 *protected*), `enrich(array $data): array` (:85) |
| `SyshubDbConsoleHandler.php` | Veritabanı eylem yürütücüsü. **Tek ifade kuralı, rol kapısı ve denetim kaydı buradadır.** | `executeQuery(string $sql): array` (:23), `isRawSqlRole(bool,bool,string): bool` (:68 *static*), `hasMultipleStatements(string $sql): bool` (:81 *static*), `maskForAudit(string $sql): string` (:128 *static*), `optimize(?string $table=null): bool` (:221), `cleanInstall(): bool` (:236), `changeCollation(string,string): bool` (:251), `dropTable(string): bool` (:259), `deleteRows(string,array): bool` (:267), `exportSql(string,?array): string` (:275) |

### 2.3 `Models/` (3)

| Dosya | Görev | Önemli public yöntemler |
|---|---|---|
| `ModuleData.php` | `#[Bundle(name:'syshub', context:'developer', map:SyshubMap::MAP)]`. Modülün **tek kayıt noktası**: 2 servis + 4 handler + 7 provider alias'ı ve 5 rota grubu (58 satır rota). | `registerMap(): array` (:26), `registerRoutes(): void` (:55) |
| `SyshubMap.php` | Menü haritası sabiti (`const MAP`): 1 kimlik + 5 alt modül, 3'ünde alt alt modüller. `title/description/icon` dizeleri panel menüsünü besler. | `const MAP` (:18) |
| `PurgeConfig.php` | Depolama türlerinin **arayüz metinleri** (başlık, ikon, renk, uyarı noktaları, toplu işlem etiketleri). Controller'ları temiz tutmak için ayrıldı. | `const TYPES` (:14, 6 tür), `get(string $type): ?array` (:123) |

### 2.4 `Providers/` (7)

| Dosya | Görev | Önemli public yöntemler |
|---|---|---|
| `SyshubProvider.php` | `writePhpLimits(int $limit=64): bool` (:21) — proje köküne `.user.ini` yazar (`memory_limit`, `post_max_size`, `upload_max_filesize`). |
| `SyshubFirewallProvider.php` | `common.shieldSetting` tablosunun tek yazma yolu. Yazma sonrası `ShieldSettingsRepository::settingsCacheKey()` önbelleğini düşürür. | `getSettings(): array` (:20), `updatesetting($keyOrData,$value=null): bool` (:35), `performUpdate(string,string): bool` (:52 *protected*), `getHealthStatus(): array` (:85) |
| `SyshubRateLimitProvider.php` | `common.rateLimit` (CM_SYS_RATE_LIMITS) veri katmanı; GeoIP zenginleştirmesi kayıt döngüsünde yapılır. | `getRecords(int $limit=0): array` (:28), `getStats(): array` (:55), `destroy(int $id): bool` (:73), `destroyBulk(array $ids): int` (:81), `clearAll(): bool` (:89) |
| `SyshubWhitelistProvider.php` | **İki kaynağı birleştirir**: `master.ipWhitelist` kayıtları + `cm_sys_settings_shield.maintenance_ips` ayarındaki virgüllü liste. | `getList(): array` (:22), `add(string $ip,string $label): bool` (:41), `remove(string $ip): bool` (:72), `getIpsFromSettings(): array` (:94 *protected*) |
| `SyshubMaintenanceProvider.php` | Bakım modu anahtarı **ve mesajı**; `[G-03]` notu: zorlayıcı katmanla (`SystemGuardHandler`) aynı yorumlayıcıyı kullanır. | `getWhitelistedIps(): array` (:20), `isMaintenance(): bool` (:37), `getMaintenanceMessage(): string` (:51), `save(array $data): bool` (:67) |
| `SyshubCronProvider.php` | Cron görevleri ve logları; `getCrontabCommand()` komutu `service('cron')`'ten alır. | `getJobs(): array` (:23), `getCrontabCommand(): string` (:31), `getLogs(int $limit=100): array` (:39), `getStorageFolders(): array` (:47), `find(int $id): array` (:67) |
| `SyshubDbConsoleProvider.php` | Tablo envanterini `schemaDoctor` modelinden okur ve biçimlendirir (`size_formatted`, `engine`, `collation`). | `getInfo(): array` (:18), `getTables(): array` (:38), `getRows(string $table,int $limit=100): array` (:59), `getMetadata(string $table): array` (:67) |

### 2.5 `Services/` (2)

| Dosya | Görev | Önemli public yöntemler |
|---|---|---|
| `SyshubService.php` | Orkestratör. 8 kısa yöntem alt birimleri döndürür (`purge()`, `maintenance()`, `security()`, `firewall()`, `rateLimit()`, `ipBlock()`, `whitelist()`, `cron()`) + 2 yetki. `ipBlock()` bir repository döndürür, provider değil (:64). | `getDashboardStats(): array` (:80), `optimizePhpLimits(): bool` (:88) |
| `SyshubDbConsoleService.php` | DB konsolunun düz proxy'si: 4 provider + 8 handler çağrısını yönlendirir, `seedAdmin()` için `service('masterAccount')`'a gider. | `getInfo/getTables/getRows/getMetadata/executeQuery/optimize/cleanInstall/changeCollation/dropTable/deleteRows/exportSql`, `seedAdmin(): bool` (:84) |

### 2.6 `Views/` (20)

| Görünüm | Sağlayan uç | Görev |
|---|---|---|
| `Dashboard/index.rbn.php` | `SyshubController::index` | Sistem kartları: CPU/bellek, sürüm, güvenlik özeti, temizlenebilir alan |
| `DataPurge/index.rbn.php` | `PurgeController::index` | 7 depolama kartı + disk kullanımı + "hepsini temizle" |
| `DataPurge/Common/index.rbn.php` | `PurgeController::module` (logs dışı) | Dosya listesi + toplu seçim + `moduleConfig` uyarıları |
| `DataPurge/Common/show.rbn.php` | `PurgeController::show` | Tek dosya içeriği (önbellek: base64/serialized uyarısı) |
| `DataPurge/Logs/index.rbn.php` | `PurgeController::module` (`type=logs`) | Sekmeli log listesi (`getCategorizedLogs()`) |
| `DataPurge/Logs/view.rbn.php` | `PurgeController::show` | JSON ayrıştırılmış log içeriği |
| `DbConsole/index.rbn.php` | `DbConsoleController::index` | DB özeti (ad, tip, tablo sayısı, kayıt sayısı) |
| `DbConsole/console.rbn.php` | `SqlConsoleController::index` | SQL terminali: editör, çalıştır, CSV dışa aktar, geçmiş (satır 193-358 gömülü JS) |
| `DbConsole/tables.rbn.php` | `TableExplorerController::index` | Tablo listesi + sayfalama + arama |
| `DbConsole/browse.rbn.php` | `TableExplorerController::browse` | Tablo satırları + meta + satır silme/dışa aktarma |
| `Maintenance/index.rbn.php` | `MaintenanceController::index` | Bakım anahtarı + mesaj + izinli IP tablosu |
| `Maintenance/partials/modal_add_ip.rbn.php` | `MaintenanceController::modal` | Bakım moduna VIP IP ekleme |
| `Maintenance/partials/modal_update_message.rbn.php` | aynı | Bakım mesajı düzenleme |
| `Security/index.rbn.php` | `SecurityController::index` | Sağlık skoru + son denemeler |
| `Security/firewall.rbn.php` | `FirewallController::index` | `firewall_enabled` / `firewall_mode` anahtarları |
| `Security/rate_limits.rbn.php` | `RateLimitsController::index` | Hız sınırı kayıtları (bayrak + ülke) |
| `Security/ip_blocks.rbn.php` | `IpBlockController::index` | Manuel IP engelleri |
| `Security/ip_whitelist.rbn.php` | `IpWhitelistController::index` | Güvenilir IP listesi |
| `Security/Partials/modal_ip_block.rbn.php` | `IpBlockController::modal` | IP + sebep + süre formu |
| `Security/Partials/modal_whitelist.rbn.php` | `IpWhitelistController::modal` | IP + etiket formu |

**Kapsama:** 47/47.

## 3. Akış — bir istek syshub'a geldiğinde

```
GET /<panel>/syshub
  → Route::prefix('syshub')->controller('SyshubController')        ModuleData.php:58
  → SyshubController::index()                                     SyshubController.php:23
      → $this->service  ⇒  SyshubService (alias 'syshub')          ModuleData.php:30
          → handler('syshub')  ⇒  SyshubHandler                     SyshubService.php:82
              → repository('common.shieldSetting')                  SyshubHandler.php:28
              → getFolderStats(Paths::project()->storage('framework/views'))  :30,35
              → getFolderStats(Paths::project()->storage('logs'))            :31,36
              → getFolderStats(Paths::project()->sessions())                 :32,37
              → model('master.ipBlock')?->count()                            :49
              → model('common.rateLimit')?->count()                          :50
      → render('Dashboard/index', [...])                           SyshubController.php:27
```

Depolama temizliği zinciri:

```
POST syshub/datapurge/clearAll
  → PurgeController::clearAll()                                    PurgeController.php:151
      → SyshubMap::MAP['sub_modules']['datapurge']['sub_modules']  :155
        (7 anahtar: cache, logs, sessions, backups, exports, uploads, view)
      → her anahtar için purge()->clear($type,null,$projectKey)     :159
          → service('storage')->{$type}()->withProject($key)->clearAll()  DataPurgeHandler.php:93
```

Ham SQL zinciri:

```
POST syshub/dbconsole/console/execute
  → SqlConsoleController::executeQuery()                           SqlConsoleController.php:40
      → request->form(['query'=>'required|string'], ['action'=>'sql_console_execute'])
      → SyshubDbConsoleService::executeQuery()                      SyshubDbConsoleService.php:46
          → SyshubDbConsoleHandler::executeQuery()                 SyshubDbConsoleHandler.php:23
              ├─ session: is_logged_in / is_master_developer / user_role      :29-35
              ├─ isRawSqlRole(...)  → reddi hâlinde SQL_CONSOLE_DENIED_ROLE   :31,37
              ├─ hasMultipleStatements(...) → SQL_CONSOLE_DENIED_MULTI         :42
              ├─ auditSql('SQL_CONSOLE_EXECUTE', $sql) → logs()->channel('security')  :47,200-216
              └─ model('schemaDoctor')->executeRaw($sql)                        :50
```

## 4. Yapılandırma / ayar anahtarları

| Anahtar | Yer | Varsayılan | Not |
|---|---|---|---|
| `maintenance_mode` | `cm_sys_settings_shield` | `'0'` | `SyshubMaintenanceProvider.php:43`; zorlayıcı katmanla **aynı** yorumlayıcı (`ShieldSettingsRepository::normalizeSwitch`) |
| `maintenance_ips` | aynı tablo | `''` | Virgüllü IP listesi; whitelist ve bakım izinlerinin ortak kaynağı |
| bakım mesajı | aynı tablo | `'Sistem şu anda bakımda. Lütfen daha sonra tekrar deneyin.'` | `SyshubMaintenanceProvider.php:58` |
| `firewall_enabled` | aynı tablo | `'0'` | `SyshubFirewallProvider.php:90` |
| `firewall_mode` | aynı tablo | `'standard'` | `SyshubFirewallProvider.php:91` |
| `CLEANUP_ALLOWED_TABLES` | `Definition::get('database_project', …)` | — | `cleanInstall()`'ın sadece bu tabloları truncate etmesini sağlar (`SyshubDbConsoleHandler.php:239`) |
| `writePhpLimits(64)` | `.user.ini` (proje kökü) | 64 MB | `SyshubProvider.php:23-26`; üç anahtarı birlikte yazar |
| Depolama türü anahtarları (7) | `SyshubMap.php:52-60` | — | `PurgeController::clearAll()` bu anahtarları `service('storage')` metot adı olarak kullanır |

## 5. Tuzaklar ve kurallar (kodda görülen, ölçülmüş)

1. **`BulkControllerTrait` imza tuzağı.** `PurgeController::bulkDelete($target=null, string $method='bulkDestroy', $redirect=null)`
   imzası **bilerek** yorum bozucudur: yönlendirici `{type}`'ı 1. argüman, `{path}`'i 2. argüman geçirir
   (`ModuleData.php:78,81`), controller bunu `$path = ($method !== 'bulkDestroy') ? $method : null`
   ile ayrıştırır (`PurgeController.php:176`). `$method` bir yöntem adı değil, dosya yoludur.
2. **`logs` ve `view` ayrıcalıklı tip.** `DataPurgeHandler::getProviderStats()` yedi tip döndürür ama
   `logs` için `getCategorizedLogs()/getConfigs()` ve `view` için farklı anahtar kullanılır
   (`DataPurgeHandler.php:60-66`). `PurgeController::module()` yalnız `logs`'ta özel dallanır (:69).
3. **`PurgeConfig::TYPES` ile `SyshubMap` uyuşmuyor.** `PurgeConfig` `framework` türünü tanımlar
   (`PurgeConfig.php:51`) ama `SyshubMap` alt modülleri arasında `framework` **yoktur**; `view` var
   (`SyshubMap.php:59`). Ters yönde de fark var: `view` `PurgeConfig`'te yok. Bu yüzden `view` kartı
   `bg_color` alamaz ve `PurgeController::index` `#6366f1` varsayılanına düşer (`PurgeController.php:39`).
4. **Ham SQL üç kapıdan geçer ve her durumda denetlenir.** Rol (`:31`), tek ifade (`:42`) ve denetim
   (`:47`) sırayla; reddedilen durumlar da `auditSql` ile kayda girer (`:38,43`). Denetim yazımı
   `try/catch` içindedir — **denetim kaydı yazılamazsa sorgu yine de çalışır** (`:213-215`).
5. **`isMaintenance()` yorum uyumu (G-03).** Panel eskiden `=== 'on'` ile okuyordu; zorlayıcı katman
   `normalizeSwitch()` kullanıyordu. `maintenance_mode='1'` yazan operatör siteyi AÇIK görüyor,
   site ENGELLİ görünüyordu. İkisi de tek kaynağa bağlandı; **anahtar yoksa KAPALI** sayılır
   (`SyshubMaintenanceProvider.php:29-35,42-45`).
6. **Önbellek düşürme yazma sonrası zorunlu.** `getSetting()` `settings_shield` önbelleğini kullanır;
   yazma sonrası düşürülmezse panel eski değeri okumaya devam eder
   (`SyshubMaintenanceProvider.php:88-97`, `SyshubFirewallProvider.php:71-77`).
   Her iki yerde de `try/catch` ile yutulur — **güvenlik açığı değil, gecikmedir**.
7. **`SyshubCronProvider::find()` ölü kod.** `getJobs()` kayıtlı alias `master.cronJob` kullanırken
   (`SyshubCronProvider.php:25`) `find()` kayıtsız `model('cronJobs')` çağırır (:69). Bu ada karşılık
   gelen `CronJobsModel` **yoktur** (dosya adı `MasterCronJobsModel.php`; kayıt alias'ı
   `SystemPhysicalMapTrait.php:29`). `?->toArray()` sayesinde hata fırlatmaz, **daima `[]` döner**.
8. **Statistiklerde sert kodlanmış değerler.** `active_threats => 0`, `health_score => 98`
   (`SyshubHandler.php:53-54`), `getHealthStatus()['score'] => 98` (`SyshubFirewallProvider.php:92`),
   `traffic` bloğu tamamen sıfır (`SyshubHandler.php:64-68`). Panel bunları gerçek ölçüm gibi gösterir.
9. **`Paths::frameworkRoot()` ile modül sayımı.** `getModuleCount()` `Bundles/Internal` ve
   `Bundles/RbnSuite` altındaki **dizin sayısını** sayar (`SyshubHandler.php:122-137`); paket içi
   `*.php` sayısıyla ilgisi yoktur.
10. **`SqlConsoleController::getTableData()` tablo adını interpolasyonla gömüyor** —
    `` "SELECT * FROM `{$form['table']}` LIMIT 100" `` (`SqlConsoleController.php:78`).
    Değer `required|string` ile gelir; **backtick kaçışı yapılmadığı** için backtick içeren bir tablo adı
    sorguyu bozabilir. `executeQuery()` bu yüzden tek ifade kuralını uygular ama tablo adını doğrulamaz.
11. **Rol kapısı yalnız SQL'de var.** `executeQuery()` `user_role ∈ {developer, superadmin}` ya da
    `is_master_developer` arar (`SyshubDbConsoleHandler.php:68-75`). `dropTable`, `deleteRows`,
    `optimize`, `cleanInstall` gibi **daha yıkıcı** işlemlerin hiçbirinde bu kapı yoktur; güvenlik
    yalnız URI'nin `context: 'developer'` korumasına dayanır.

## 6. Örnek (gerçek koddan)

```php
// Bundles/Internal/Syshub/Handlers/SyshubDbConsoleHandler.php:23-47 (özetlenmiş)
public function executeQuery(string $sql): array
{
    if (empty(trim($sql))) {
        return ['success' => false, 'message' => 'Sorgu boş olamaz.'];
    }

    $session  = $this->session();
    $role     = (string) $session->get('user_role', '');
    $allowed  = self::isRawSqlRole(
        (bool) $session->get('is_logged_in', false),
        (bool) $session->get('is_master_developer', false),
        $role
    );
    if (!$allowed) {
        $this->auditSql('SQL_CONSOLE_DENIED_ROLE', $sql);
        return ['success' => false, 'message' => 'Bu işlem için yetkiniz yok.'];
    }
    if (self::hasMultipleStatements($sql)) {
        $this->auditSql('SQL_CONSOLE_DENIED_MULTI', $sql);
        return ['success' => false, 'message' => 'Aynı anda yalnızca tek bir SQL ifadesi çalıştırılabilir.'];
    }
    $this->auditSql('SQL_CONSOLE_EXECUTE', $sql);
    // …
}
```

## 7. İlgili belgeler

* [Security.md](Security.md) · [DbConsole.md](DbConsole.md)
* [Core/Base/README.md](../../../Core/Base/README.md) — `BaseComponent`, `BaseController`, keşif zinciri
* [Core/Base/Services.md](../../../Core/Base/Services.md) — `BaseService` / `BaseProvider` sözleşmeleri
* [Core/System/Config.md](../../../Core/System/Config.md) — `Definition::get('database_project', …)`
* [Core/Database/README.md](../../../Core/Database/README.md) — `schemaDoctor`, master/common katmanları
* [kavramlar/03-veritabani-ve-kiracilik.md](../../../kavramlar/03-veritabani-ve-kiracilik.md)
* [acik-sorular.md](../../../acik-sorular.md)