# Core/System/Storage — Dosya tabanlı depolama (önbellek, oturum, günlük, trafik…)

> **Doğrulanan kod tabanı:** `d508f5e1` (dal `feat/fw-license-master`) · **Tarih:** 2026-10-05 · **Yayın:** 0.9.6 = bu commit + sonrası; belge yalnız doğrulama anındaki kodu anlatır
> **Kaynak klasör:** `Core/System/Storage/` — 16 `*.php` (`StorageManager` 1, `Base/` 1, `Constants/` 3, `Drivers/` 1, `Providers/` 10).
> **Envanter:** 16 dosyanın 16'sı aşağıda anlatıldı.

## 1. Ne işe yarar, kim kullanır

Projenin `Storage/` klasörü (ve çalışma alanı `.cache/` klasörü) altında dosya okuma/yazmayı **tek bir sürücü** (`UniversalFileDriver`) ve **konu başına bir sağlayıcı** (`CacheProvider`, `LogProvider`, `SessionProvider`, `TrafficProvider`…) üzerinden yapar. Çağıran `service('storage')->cache()->get(...)` ya da `->logs()->error(...)` der; `StorageManager` doğru sağlayıcıyı bulur.

**Kimler çağırır:** `Kernel/Stages/SessionSandboxStage` (oturum işleyicisi), `Discovery` önbellekleri (`ProjectDataMapper` → `BootCacheProvider`), depolar/servisler (`getTtl`, `cache()->remember`), günlükleme (`logs()`), yönetim paneli (günlük/oturum/önbellek ekranları), `Routes/Engine/Providers/RouteManager` ve `CronJobsRepository` (`BootCacheProvider`).

## 2. Klasör/dosya envanteri

### 2.1 Kök ve taban

| Dosya | Görev | Önemli public yöntemler |
|---|---|---|
| `StorageManager.php` | `BaseService`'ten türeyen sağlayıcı fabrikası; `__call` ile `<ad>Provider` sınıfını bulur ve örnekler. | `driver(): UniversalFileDriver`, `__call(string $name, array $arguments)`, `hasProvider(string $name): bool`, `cache()`, `logs()`, `sessions()`, `traffic()`, `backups()`, `uploads()`, `exports()`, `view()`, `temp()`, `purgeAll(): array` |
| `Base/BaseStorageProvider.php` | Sağlayıcıların atası: `set/get/delete/clearAll/getStats/getFileContent`, güvenli yol çözümü. | `withId(string)`, `withProject(?string)`, `set($key,$value,array $options=[])`, `get($key,$default=null)`, `delete($key)`, `clearAll()`, `getStats()`, `getFileContent($filename)`; soyut `getStorageDir()` |

### 2.2 `Constants/`

| Dosya | İçerik |
|---|---|
| `CacheConstants.php` | `DEFAULT_TTL=300`, `TTL_SHORT=60`, `TTL_MEDIUM=600`, `TTL_LONG=1800`, `TTL_DAY=86400`; `SYSTEM_KEYS` (`settings_all`, `settings_shield`, `sidebar_%s`); `READABLE_PREFIXES` (11 önek: `settings_`, `comm_`, `api_`, `sidebar_`, `users_`, `sitemap_xml`, `robots_txt`, `rss_feed`, `llms_txt`, `cron_`, `content_`); `DISCOVERY_PREFIX_PROJECT='project_'`, `…_DOMAIN='domain_'`, `…_GROUP='group_'`. |
| `LogConstant.php` | `CATEGORIES` (9 kategori: `auth`, `email`, `error`, `ajax`, `application`, `cli`, `traffic`, `other`, `total`; her biri başlık/anahtar sözcük/ikon/CSS sınıfı); `getConfig(string $type): array`. |
| `TrafficConstants.php` | `DETECTABLE_BOTS` (10 giriş: `googlebot`, `bingbot`, `yandexbot`, `baiduspider`, `ahrefsbot`, `semrushbot`, `mj12bot`, genel `bot`/`spider`/`crawl`) ve `BLOCKED_BOTS` (10 giriş). |

### 2.3 `Drivers/`

| Dosya | Görev | Yöntemler |
|---|---|---|
| `UniversalFileDriver.php` | Tek dosya G/Ç: biçim (`raw`, `json`, `jsonl`, `serialized`), isteğe bağlı `crypto` yardımcısıyla şifreleme, `mkdir 0755`, `LOCK_EX`. | `write(string $path, mixed $content, string $format='raw', bool $append=false, bool $encrypt=false): bool`, `read(string $path, string $format='raw', bool $decrypt=false): mixed`, `delete(string $path): bool` |

### 2.4 `Providers/`

| Dosya | Depo klasörü | Biçim / şifre | Özel yöntemler |
|---|---|---|---|
| `CacheProvider.php` | `<proje>/Storage/cache` | `serialized`, **şifreli**, uzantı `.cache` | `set/get/delete/forget/remember(string $key, callable $cb, ?int $ttl)`, `isAllowedKey()`, `clearRelated(string $type)`, `getTtl(string $type='default'): int`, `deleteByPrefix()`, `clearAll(?string $projectKey)` |
| `LogProvider.php` | `<proje>/Storage/logs/<kanal>/<tarih>_<proje>.jsonl` | `jsonl`, şifresiz | `channel()`, `file()`, `targetAll()`, `old(int $days)`, `toPath()`, `workspace()`, `log($level,$msg,$ctx,$channel)`, `emergency/alert/critical/error/warning/notice/info/debug()`, `logException()`, `all()`, `details()`, `clearAll()`, `getCategorizedLogs()`, `getConfigs()`, `parseLogContent()` |
| `SessionProvider.php` | `<proje>/Storage/sessions` (`sess_<id>`) | `raw`, şifresiz; `SessionHandlerInterface` | `getName()`, `open/close/read/write/destroy/gc`, `get/has/set/delete` (`$_SESSION` üstünde), `end()`, `regenerate()`, `clearAll()`, `getFileContent()` |
| `TrafficProvider.php` | `<proje>/Storage/logs/traffic/pulse_<YYYY-MM>_<proje>.json` | `json`, şifresiz | `record(array $data=[])`, `getMonthlyData()`, `saveMonthlyData()`, `getMonthlyFilePath()`, `get('date_<tarih>')`, `listDates()`, `purgeOldLogs(int $keepMonths=12)` |
| `BootCacheProvider.php` | `<çalışma alanı>/.cache/<önek><anahtar>.json` | `json`, şifresiz; **tümü statik**, `BaseStorageProvider`'dan türemez | `getCacheDir()`, `getFilePath()`, `get()`, `set()`, `delete()`, `clearProjectCache(string $projectKey)` |
| `ViewProvider.php` | `<proje>/Storage/framework/views` | `raw` | `clearAll()` (yalnız `<proje>_*.php`) |
| `BackupProvider.php` | `<proje>/Storage/backups/<proje>` | `raw` | — |
| `ExportProvider.php` | `<proje>/Storage/exports/<proje>` | `raw` | — |
| `TempProvider.php` | `<proje>/Storage/temp/<proje>` | `raw` | — |
| `UploadProvider.php` | `Paths::project()->uploads(<proje>)` | `raw` | — |

## 3. Akış

### 3.1 Sağlayıcı bulma (`StorageManager::__call`, `StorageManager.php:39-62`)

1. Bellekte varsa döner.
2. Sınıf adı `ucfirst($ad).'Provider'`; yoksa ve ad `s` ile bitiyorsa tekil (`logs` → `LogProvider`, `sessions` → `SessionProvider`, `exports` → `ExportProvider`, `backups` → `BackupProvider`, `uploads` → `UploadProvider`).
3. Sınıf varsa `new $fullClass($this->driver())`; yoksa `service($className)` ya da `service('<Ad>Manager')`.
4. `purgeAll()` yalnız `cache`, `logs`, `sessions` sağlayıcılarının `clearAll()`'ını çalıştırır (`:158-171`).

### 3.2 Önbellek (`CacheProvider`)

* `set($key,$value,['ttl'=>…])` yalnız **izinli anahtar**larda yazar (`isAllowedKey`: `SYSTEM_KEYS` kalıpları ya da `READABLE_PREFIXES`'ten biriyle başlayan anahtarlar); izinsiz anahtarda `false` döner ve **hiçbir şey yazılmaz** (`:31-47`).
* Dosya adı: `<proje>_<anahtar>.cache`; anahtar `READABLE_PREFIXES`'ten biriyle başlıyorsa okunabilir ad, aksi halde `md5(<proje>_<anahtar>)` (`formatKey`, `:186-197`). Kayıt `{data, expires_at, created_at}` serileştirilip şifrelenir.
* `get()` süresi dolmuşsa dosyayı siler ve varsayılanı döner; `remember()` önbellekte `null` olmayan değer varsa onu, yoksa callback sonucunu yazar; callback `Exception` fırlatırsa `null` döner.
* `getTtl('short'|'medium'|'long'|'day'|'week'|'month'|'year'|varsayılan)` = 60 / 600 / 1800 / 86400 / 604800 / 2592000 / 31536000 / 300 sn (`:169-180`).
* `clearRelated($tür)`: `SYSTEM_KEYS` adı/değeri eşleşenleri siler, `READABLE_PREFIXES` kategorisine uyanları `deleteByPrefix` ile siler; `discovery`/`all` ise `BaseService::get()->discovery->clear()` çağırır.

### 3.3 Önyükleme önbelleği (`BootCacheProvider`, `ProjectDataMapper`)

`set($anahtar,$veri,$publicPath,$önek)` `<workspace>/.cache/<önek><anahtar>.json` dosyasına `JSON_PRETTY_PRINT` ile yazar; dizin yoksa `mkdir(…, 0777, true)` (`:56-61`). `get()` dosya yoksa/boşsa/geçersiz JSON ise `null`. **Süre (TTL) kavramı yoktur**; silme yalnız `delete()` / `clearProjectCache()` iledir (proje önbelleği silinince `domain_<alan>` ve `group_<grup>` kayıtları da silinir). Yazan: `ProjectDataMapper::build()` (anahtarlar `project_<proje>`, `domain_<alan>`, `group_<grup>`), `RouteManager`, `CronJobsRepository`.

### 3.4 Günlük (`LogProvider::log`, `:122-152`)

Kanal adı `[A-Za-z0-9_-]` dışı karakterlerden arındırılır (boşsa `app`). Yol: `<depo>/<kanal>/<YYYY-MM-DD>_<proje>.jsonl`. Satır: `timestamp (ISO 8601)`, `level` (büyük harf), `message`, `ip` (`REMOTE_ADDR`), `uri`, `method`, `context`. Yazım `UniversalFileDriver::write($path,$entry,'json',append=true)`.

### 3.5 Oturum (`SessionProvider`)

Çerez adı `RBN_<PROJE>_<çalışma alanı SHA-256'sının ilk 8 hanesi>_SESS` (`getName`, `:33-38`). Dosya `sess_<id>` (`resolvePath` yalnız `[a-zA-Z0-9_\-./]` karakterlerine izin verir, diğerleri `_` olur). `destroy()` idempotenttir: olmayan dosya başarı sayılır (aksi halde `session_regenerate_id(true)` ilk girişte reddediliyordu, `SessionProvider.php` docblock'u). `gc()` **en az 86400 sn** kullanır: `max($max_lifetime, 86400)` (`:107`). `SessionSandboxStage` ayrıca %10 olasılıkla `gc(86400)` çağırır ([Kernel §3.4](Kernel.md)).

### 3.6 Trafik (`TrafficProvider::record`, `:103-238`)

Atlanan istekler: ilk yol bölümü panel öneki (`Definition::get('route','DASHBOARD_PREFIX')` = `RouteBlueprint::DASHBOARD_PREFIX` = `dashboard`; sabit okunamazsa kod yedeği `rbn`), `api` ya da `AUTH_ROOTS` içindeyse; `robotsResolver->isPathRestricted()` doğruysa. Aksi halde aylık JSON dosyası okunur, gün kaydı güncellenir, geri yazılır. IP başına gün kaydı (`ip_log`): saat, son saat, URL, konum, bot mu, bot adı, **User-Agent, Referer**, sayfa görüntüleme sayısı, gezilen sayfalar. Konum: bot ise `Bot (<ad>)`; `is_local()`/`127.0.0.1`/`::1` ise `Localhost`; değilse `geoIP` işleyicisi, yoksa `XX`.

## 4. Yapılandırma ve varsayılanlar

| Ayar | Değer | Kaynak |
|---|---|---|
| Önbellek varsayılan TTL | 300 sn | `CacheConstants.php:8` |
| Trafik saklama süresi | 12 ay (`purgeOldLogs($keepMonths = 12)`) | `TrafficProvider.php:335` |
| Oturum çöp toplama alt sınırı | 86400 sn | `SessionProvider.php:107` |
| Dizin izni (sürücü) | `0755` | `UniversalFileDriver.php:36` |
| Dizin izni (önyükleme önbelleği) | `0777` | `BootCacheProvider.php:58` |
| Önyükleme önbelleği klasörü | `<workspace>/.cache` | `BootCacheProvider.php:22` |
| Önbellek şifreleme | açık (`$encrypted = true`), anahtar `CryptoHelper` | `CacheProvider.php:19` |

## 5. Tuzaklar ve kurallar

1. **`CacheConstants` TTL'leri yalnız `CacheProvider::getTtl()` içinde kullanılır** (kod taraması, `Core/Bundles/Packages`): çağıranlar `BaseChannel`, `ContactRepository`, `NotificationRepository`, `CrawlerController` (`getTtl('short'|'long'|'medium')`). Keşif/önyükleme önbelleği (`BootCacheProvider`) bu TTL'leri **kullanmaz**; onun süresi yoktur. *(Eski `acik-sorular.md` §2.7 — kodla çözüldü.)*
2. **Önbellek izin listesi (allow-list) vardır:** `READABLE_PREFIXES`/`SYSTEM_KEYS` dışında bir anahtarla `cache()->set()` **sessizce `false`** döner ve `remember()` her seferinde callback'i çalıştırır (`CacheProvider.php:43-45,97-98`). Yeni önbellek ailesi eklerken önce `CacheConstants::READABLE_PREFIXES`'e önek yazılmalıdır.
3. **`BootCacheProvider` düz metin JSON yazar ve proje verisinin tamamını taşır** (`assemble()` sonucu: `domain`, `custom_path`, `options`, `cron_tasks`, **`api_keys`** — yalnız `bot_activity` açıkken). Dosyalar `<workspace>/.cache/` içindedir; bu klasör `public` dışındadır ama `0777` ile oluşturulur. Yerel Windows'ta izin modeli farklıdır; Linux üretiminde gerçek izin, umask'a bağlıdır (`mkdir` modu umask ile kısılır). Üretimde izin değeri ölçülmedi.
4. **`SessionProvider` oturumu şifrelemez ve `raw` yazar** (`$encrypted=false`); koruma `Storage/` klasörüne yazılan `.htaccess`'e (`PermissionDoctor::secureFolder`, Apache) bağlıdır — nginx'te bu dosya yok sayılır ([Kernel §5](Kernel.md)).
5. **Trafik sayacı "ziyaretçi sayar", "istek" saymaz:** aynı IP aynı gün tekrar gelirse `page_views` artar ama `total_hits`/`human_hits`/`bot_hits`/cihaz/konum sayaçları **artmaz**; fonksiyon bu noktada erken `return` eder (`TrafficProvider.php:187-199`). Yani "total_hits" gün başına **tekil IP** sayısıdır.
6. **Trafik dosyası okunur-değiştirilir-yazılır:** her istek aylık JSON'u baştan okuyup geri yazar; yazma `LOCK_EX` kullanır ama okuma-yazma arası kilit yoktur → eşzamanlı isteklerde güncelleme kaybı olabilir ve dosya ay içinde IP sayısıyla büyür.
7. **`LogProvider::details($dosya)`** yolu `<depo>/<$dosya>` olarak birleştirir ve yalnız `basename($dosya)` içinde `_<proje>.jsonl` geçmesini şart koşar; `..` segmentleri süzülmez (`LogProvider.php:259-267`). Çağıranın (panel) dosya adını `all()` çıktısından almasına güvenilir.
8. **`UniversalFileDriver` `serialized` biçimi `unserialize()` ile okur** (`allowed_classes` kısıtı yok, `:135`); dosyaların yalnız uygulama tarafından yazıldığı varsayımı vardır. Önbellek şifreli olduğundan geçersiz içerik `null` döner (`decrypt` başarısızsa `null`).
9. **Hatalar sessizdir:** `write` her `Throwable`'da `false`, `read` `null` döner; üst katman yazmanın başarısını mutlaka denetlemelidir.
10. **`SessionProvider::get/set` `$_SESSION` üzerindedir**, sağlayıcının dosya anahtar-değer deposu değildir; `ensureStarted()` başlık gönderilmişse oturum başlatmaz.
11. **Konfigürasyon yolu tutarsızlığı:** `TrafficProvider::getStorageDir()` `custom_path` verisi varsa `<workspace>/projects/<custom_path>/Storage/logs/traffic` kullanır, aksi halde `Paths::project()->logs('traffic')`; `LogProvider` benzer biçimde `resolveProjectPath()` kullanır. İkisi de aktif olmayan bir projenin dizinine yazabilir (proje anahtarı verildiğinde).
12. **`LogThrottle` Storage'da değildir:** gerçek konum `Core/Support/Bridges/Helpers/Library/LogThrottle.php`'dir (kısıt kapısı `secrets.php` `app.log_throttle`).

## 6. Örnek (gerçek koddan)

```php
// Core/System/Kernel/Stages/SessionSandboxStage.php:37-44
$handler = $services->service('storage') ? $services->service('storage')->sessions() : null;
if (is_object($handler) && method_exists($handler, 'getName')) {
    session_name($handler->getName());
    session_set_save_handler($handler, true);
}
```

```php
// Core/Database/Repositories/Common/ContactRepository.php:61
$ttl = $this->storage->cache()->getTtl('short');
```

## 7. İlgili belgeler

* [Core/System genel bakış](README.md) · [Kernel](Kernel.md) · [Discovery](Discovery.md) (`ProjectDataMapper`) · [Paths](Paths.md)
* [Support/Bridges](../Support/Bridges.md) (`CryptoHelper`, `LogThrottle`)
* [Açık sorular](../../acik-sorular.md)
