# Core/Services/Gatekeepers — IP/bakım/veritabanı kapıları, WAF, GeoIP (13 dosya)

> **Doğrulanan kod tabanı:** `c23b431f` · **Tarih:** 2026-10-05 · **Yayın:** 0.9.6 = bu commit + sonrası; belge yalnız doğrulama anındaki kodu anlatır
> **Kaynak klasör:** `Core/Services/Gatekeepers/` — **13 `*.php`** = 5 kök + `Concerns/` 2 +
> `Handlers/` 5 + `Providers/` 1.
> **Envanter:** 13 dosyanın **13'u** anlatıldı.
> **Doğrulama platformu:** Windows + PHP 8.3; `IpGuardService::isAgentEndpoint()` 11 bypass
> denemesiyle, `RawHtmlGate` ve `SystemGuardHandler` yardımcıları gerçekten çalıştırıldı.

## 1. Ne işe yarar, kim kullanır

**İsteğin sunucuya girmeden önce "geçmeli mi?" sorusunu yanıtlayan** katman.
Beş kapı var ve hepsi `Kernel` aşamalarından ya da `ShieldSentinel`'dan çağrılır:

| Kapı | Servis | Aşama |
|---|---|---|
| Erken boot güvenliği | `BootSentinel` | `PreBoot` |
| IP / WAF / hız sınırı | `IpGuardService` | `ShieldSentinel` |
| Bakım modu / VIP | `SystemGuardService` | `ShieldSentinel` |
| Veritabanı bütünlüğü | `DatabaseGuardService` | `DatabaseGuardStage` |
| Güvenlik ayarları | `ShieldSettingsService` | her yerden |

**Değişmez kural:** `shield_ip_guard_mode` **bu çalışmada değiştirilmedi**.
Varsayılan `log_only`; `enforce` **yalnız tam `'enforce'` metni** ile açılır
(`IpGuardHandler.php:40-43`).

## 2. Klasör/dosya envanteri (13/13)

| Dosya | Görev | Önemli public yöntemler |
|---|---|---|
| `BootSentinel.php` (154) | **Sıfır bağımlılıklı** erken-boot bekçisi: `set_exception_handler`, `set_error_handler`, `register_shutdown_function` kurar ve 3 katmanlı kurtarma yürütür. | `static register(?Throwable $e=null): void`, `dispatch(Throwable $e): void`, `static render(Throwable $e): void` |
| `IpGuardService.php` (566) | **Ana IP kapısı.** Kara/beyaz liste, bot, GeoIP, WAF taraması, ajan ucu muafiyeti, kademeli ceza. | `boot()`, `check()`, `isLimitReached(string $identifier,string $action): bool`, `remainingTime(string,string): int`, `recordHit(string,string,array $metadata=[]): bool`, `countryCode(string $ip): string`, `flag(string $countryCode): string`, `isBlockedBot(string $userAgent): bool`, `isAgentEndpoint(?string $uri=null): bool`, `detectAttack(?string $body=null): ?string`, `progressiveBlockMinutes(string $ip): int`, `blockIpAddress(string $ip,string $reason,int $durationMinutes=1440,?int $projectId=0): void` · `const BODY_SCAN_MAX_BYTES=65536`, `const KADEMELI_CEZA_DAKIKA=[15,60,1440,1440]` |
| `SystemGuardService.php` (42) | Bakım modu orkestratörü (42 satır). | `boot()`, `check(): void` |
| `DatabaseGuardService.php` (77) | Veritabanı yolu/ayar doğrulama orkestratörü. | `boot()`, `resolvePath(string $category,?string $projectKey=null): string`, `resolveConfig(string $category,?string $projectKey=null): DatabaseConfig`, `check(): void` |
| `ShieldSettingsService.php` (51) | Güvenlik anahtarı okuma/yazma + önbellek temizleme. | `getSetting(string $key,$default=null)`, `getGroup(string $groupKey): array`, `set(string $key,$value,string $groupKey='general',string $valueType='boolean',?string $projectKey=null): bool` |
| `Handlers/IpGuardHandler.php` (464) | **Karar motoru.** Beyaz liste → bot → ajan muafiyeti → WAF → kara liste sırası; log_only/enforce. | `check(): array` · korumalı: `wouldBlock()`, `decision()`, `logWouldBlock()`, `logWarning()`, `logError()` |
| `Handlers/SystemGuardHandler.php` (248) | Bakım modu + VIP (geliştirici token, yerel IP, global VIP listesi). | `isAccessDenied(): bool`, `getMaintenanceMessage(): string` · korumalı: `isStaticAssetPath(string)`, `isCliRequest()`, `safeConfig()`, `resolveFailClosed()`, `logGuardWarning()` · `private const FAILCLOSED_OFF_VALUES` |
| `Handlers/DatabaseGuardHandler.php` (245) | Yol bütünlüğü, tablo varlığı, proje durumu, **lisans kapısı**, yazma yetkisi. | `resolve(string $category,?string $projectKey=null): string` · korumalı: `validateIntegrity()`, `verifyTables()`, `checkProjectStatus()`, `isUnrestricted()`, `checkWritePermissions()` |
| `Handlers/RateLimitHandler.php` (215) | Hız sınırı (nitelikli sayaç). | `__construct()`, `isBlocked(string $identifier,string $action): bool`, `canonicalAction(string $action): string`, `hasLimit(string $action): bool`, `recordAttempt(string $identifier,string $action,array $metadata=[]): bool`, `getRemainingTime(string $identifier,string $action): int`, `getLimits(): array` · korumalı: `logWarning()` |
| `Handlers/GeoIPHandler.php` (389) | IP → ülke kodu (harici `ipwho.is`), bayrak/ülke adı. | `getCountryCode(string $ip): string`, `static ulkeKoduAyikla(?string $govde): string`, `getFlag(string $countryCode): string`, `getCountryName(string $countryCode): string` · `public const BILINMEYEN_ULKE='XX'` · `private const GEOIP_UC_NOKTA/GEOIP_SUNUCU/GEOIP_TIMEOUT_SN/GEOIP_MAX_GOVDE_BAYT` |
| `Providers/DatabaseGuardProvider.php` (197) | Veritabanı sağlayıcısı: kimlik bilgisi, bağlantı testi, tablo listesi, **heartbeat yazma testi**. | `loadCredentials(string $category,string $path): array`, `testConnection(string $category): \PDO`, `fetchTableList(\PDO): array`, `fetchProjectData(string $projectKey): ?array`, `performHeartbeatTest(\PDO): void` · `private const PROJECT_DATA_COLUMNS` |
| `Concerns/LogsGuardErrors.php` (56) | Koruma katmanı hata günlükleme. | `logGuardError(…)` |
| `Concerns/LogsLicenceWarnings.php` (44) | Lisans anormallik günlükleme (B-04 tekrar koruması). | `licenceWarn(string $message): void`, `licenceWarnOnce(string $message): void` |

## 3. Akış — `BootSentinel` (154 satır, **hiç miras yok**)

```
BootSentinel::register()                        BootSentinel.php:26
 ├─ $e verilmişse → dispatch($e) ve return       :31-34
 ├─ set_exception_handler([$instance,'dispatch'])                :37
 ├─ set_error_handler(...)  → ErrorException'a çevirir           :39-43
 └─ register_shutdown_function(...) → E_ERROR/E_PARSE/E_CORE_ERROR/E_COMPILE_ERROR :45-50

BootSentinel::dispatch($e)                       :56-106
 0. LogHandler::failsafeLog() — panik seviyesinde kayıt           :60-73
    · level = ShieldMetadata::ERROR_MAP[...] ?? 'development'      :63
    · reason = level==='user' ? 'warning' : 'panic'                :65
 1. ExceptionHandler::handle($e) + exit                           :79-87
 2. SurvivalProvider::render(...) + exit                           :90-102
 3. self::render($e)  ← sıfır bağımlılıklı son savunma            :105

BootSentinel::render($e)                         :111-153
 ├─ define('RBN_PANIC_ACTIVE', true)                              :114-116
 ├─ Watchdog::$inPanic = true                                     :118-120
 ├─ while (ob_get_level() > 0) ob_end_clean()                     :123-125
 ├─ header 500 + Content-Type (headers_sent değilse)              :128-131
 ├─ Resources/Views/Errors/panic.view.php include + exit(1)        :140-145
 └─ die("<h1>RBN Critical Boot Failure</h1>…")                     :152
```

**Neden `extends` yok (`:19` yorumu):** "Zero-Dependency: core servisler
başarısız olsa bile çalışsın diye." Bu, bu klasördeki en önemli tasarım
kararıdır.

**`RBN_PANIC_ACTIVE` tüketicileri (ölçülen iki yer):**
`BaseRender::bootHarmony()` (`Core/Base/Web/BaseRender.php:57-64`) ve
`ServicesContextTrait::initServicesContext()` (`Core/Base/Concerns/Contexts/ServicesContextTrait.php:37-42`)
— ikisi de bu sabit tanımlıysa boş/basalı döner. Yani panic modunda view
motoru ve servis keşfi **devre dışıdır**.

## 4. Akış — `IpGuardHandler::check()` karar sırası (464 satır)

Dönüş tipi: `array` (karar kayıtları). Her kayıt:
`['rule','decision','ip','project_id','mode','detail']`, `decision ∈
{allow, would-block, blocked, skipped, error, fallback}` (`:63-65`).

```
check()                                          Handlers/IpGuardHandler.php:70
 0.  Ayar okuma + mod                              :78-123
     · mode = normalizeIpGuardMode(getSetting(KEY_IP_GUARD_MODE, MODE_LOG_ONLY))
     · killSwitchAcik = normalizeSwitch(getSetting(KEY_IP_GUARD, true), true)
     · okunamazsa: mode=log_only, killSwitch=AÇIK, 'settings_unreadable' kaydı
       (B-10: fail-OPEN DEĞİL — katman log_only ile ÇALIŞMAYA DEVAM EDER)  :106-123
 0.b kill-switch kapalıysa → 'skipped' + return                      :127-130
 0.c service('ipGuard') çözülemezse → 'skipped' + return             :133-137
 0.d REMOTE_ADDR boşsa → 'skipped' + return                          :139-142
 0.4 BEYAZ LİSTE (master.ipWhitelist) → 'allow' KAYDI (akış devam)     :159-167
 0.5 BOT kuralı (isBlockedBot, !beyaz liste)
       log_only → 'would-block' + return                             :180-183
       enforce  → 403 + 'Access Denied: Crawler blocked…' + exit     :184-190
 0.6 AJAN UCU: MachineApiRegistry::match() beyanı, yoksa isAgentEndpoint()  :221-232
       muafsa → 'skipped' kaydı (WAF/ban yine de DEĞERLENDİRİLİR)     :233-239
 1.  WAF: ipGuardService->detectAttack() && !ajan                       :249-252
       log_only → 'would-block' + return (ban YAZILMAZ)               :255-259
       enforce  → progressiveBlockMinutes() + blockIpAddress()
                  + 403 + 'Access Denied: Dangerous request payload…'  :270-295
 2.  KARA LİSTE: beyaz listede → 'allow' + return                     :296-300
       master.ipBlock: blocked_until > now() AND (project_id=0 OR =$projeId)
       ajan ucuysa → 'skipped' (ban satırı var ama durulmadı)          :318-327
       log_only → 'would-block' + return                              :338-341
       enforce  → shield()->diagnostic() + return                      :343-366
       aksi halde → 'allow' (aktif ban yok)                            :368
 catch (DiagnosticException) → yeniden fırlat                         :371-373
 catch (\Throwable) → 'error' kaydı + logError() (sessiz yutma YOK)      :374-392
```

**Tuzak — beyaz liste WAF'tan muaf DEĞİLDİR (`:150-158` yorumu):**
Beyaz liste `ip_blacklist` ve `bot_user_agent` kurallarından muaftır ama
**WAF'tan muaf değildir** — "WAF bir IP-itibar kuralı değil, istek YÜZEYİ
taramasıdır." Bu, "ofis/CI'dan gelen SQLi denemesi de yakalanmalı" gerekçesiyle
bilinçli bir karardır.

**Tuzak — `wouldBlock()` ban YAZMAZ (`:255-258`):** "yazmak, 48 saatlik gözlemi
kendi verisiyle bozardı (yeni kayıt = geçmiş hata)." Yani `log_only` modunda
`ip_blocks` tablosuna **hiçbir kayıt girmez**.

**Tuzak — `$this->shield` normal akışta NULL'dır (`:349-351` yorumu):**
`BaseDiscoveryContext::triggerDiagnostic()` içinde tembel atanır; eski kod
`$this->shield->diagnostic()` yazdığı için `enforce` modunda *"Call to a member
function diagnostic() on null"* → catch → **sessiz geçiş**, yani engelleme
yine tutmuyordu. Düzeltme: `$this->shield ?? shield()` (global fabrika).

## 5. Akış — `IpGuardService::detectAttack()` (566 satır, desen kütüğü)

### 5.1 Girdi yüzeyleri (`:246-281`)

| Yüzey | Desen seti | Gerekçe |
|---|---|---|
| **Yol** | `attackPatternsForPath()` — **yalnız traversal** | Yol bir kimliktir, kullanıcı verisi değil; SQLi/XSS yüzeyi sorgu ve gövdedir |
| **Sorgu dizesi** | `attackPatternsForQuery()` | SQLi + XSS + traversal |
| **`$_POST`** | `attackPatternsForQuery()` (json_encode) | aynı |
| **Gövde** (`php://input`) | `attackPatternsForBody()` — **daraltılmış** | Gövde meşru HTML/JS/Türkçe metin taşır |

### 5.2 Gövde taraması neden daraltılmış (`:190-219` yorumu)

* **SQLi:** yalnız *yapısal* kalıplar (`union select`, `information_schema`,
  `drop table`, `insert into`, `delete from`, `sleep(`/`benchmark(`, `or 1=1`,
  `having 1=1`).
* **Gövdede KULLANILMAZ:** `select … from`, `update … set`,
  `order by <sayı>`, `group concat` — meşru Türkçe metinde de geçer
  ("select the best option from the list").
* **Path traversal:** aynen uygulanır.
* **XSS gövdede UYGULANMAZ** — `InjectionHandler::detectXss()` zaten form
  katmanında çalışıyor; IP katmanı ikinci kez `alert(`/`javascript:` ararsa
  meşru içerikleri susturur.
* **Byte sınırı:** `BODY_SCAN_MAX_BYTES = 65536` aşılırsa gövde **taranmaz**
  (`:274-276`) — sınırı aşmakla taramadan kaçınmak mümkün değil.
* **`php://input` akışı tüketilmez** (`:213-217`) — `file_get_contents()` ile
  defalarca okunabilir, `Request::getJsonData()` bozulmaz.

### 5.3 Sorgu desenleri (`:301-349`)

```
PATTERN_TRAVERSAL = (\.\.[\/\\\\]){2,}|etc\/passwd|boot\.ini|win\.ini|proc\/self\/environ
PATTERN_SQLI_QUERY = union select | select [`\w.]+ … from [`\w] | insert into | delete from
                   | drop table | truncate table | update [`\w.]+ set [`\w.]+ =
                   | information_schema | order by \d+ (;|--|#|/*|$) | group concat
                   | benchmark\( | sleep\( | or \d+ = \d+ | having \d+ = \d+
PATTERN_XSS_QUERY = <script[^>]*> | <iframe[^>]*> | javascript\s*: | onload= | onerror=
                   | onmouseover= | document.cookie|write | eval\s*\(['"]
                   | window.location = javascript | alert\( (hedef/argüman koşulu)
```

**G-07 daraltma kanıtı (`:223-245` yorumu):** önceki hâlde `REQUEST_URI` **yol +
sorgu dizesi birlikte** tam desene geçiyordu; ölçülen 6 yanlış pozitif:
`/kaynaklar/select the best option from the list`, `/blog/order by 1 rows`,
`/destek/alert(acilir)`, `?s=update your settings`, `?q=order by 5 rows`,
`?arama=alert(acilir)`. Düzeltme: **yol ayrı tarama altından çıkarıldı**,
sorgu desenleri daraltıldı; saldırı korpusu 9/9 yine yakalanıyor.

### 5.4 Kademeli ceza (`:504-552`) — ölçülen sabit

`KADEMELI_CEZA_DAKIKA = [15, 60, 1440, 1440]` (ölçüldü):

| Aktif ban sayısı | Ceza |
|---|---|
| 0 | 15 dakika |
| 1 | 60 dakika (1 saat) |
| 2+ | 1440 dakika (24 saat) |

**Hiçbir kademede 7 gün yoktur** (`10080` bilinçli kullanılmaz, `:531`).
DB okunamazsa **en yumuşak** kademe (15 dk) seçilir (`:547-549`).
Bu dizi **`FormGuardHandler::KADEMELI_CEZA_DAKIKA` ile bilinçli olarak AYNI**
ve kopyalandı (`:506-519` yorumu) — `FormGuardHandler`'ın sabiti `private`
olduğu ve kabul testi kapsamını ölçtüğü için tek noktaya taşınamıyor.

### 5.5 Ajan ucu muafiyeti — 11 bypass denemesi (ölçüldü)

`isAgentEndpoint()` (`:134-176`) normalizasyonu: sorgu dizesi/parça atılır →
`rawurldecode` → `..`, `\`, `//` varsa **muaf değil** → **BÜYÜK HARF = fail-closed**
(küçük harfe indirme yapılır ama yol değiştiyse reddedilir) → sondaki `/` kırpılır.

| Yol | Sonuç |
|---|---|
| `/api/agent/x` | `true` (muaf) |
| `/api/agent` | `true` |
| `/api/telegram/webhook` | `true` |
| `/api/telegram/webhook?x=1` | `true` (sorgu dizesi atılıyor) |
| `/API/AGENT/x` | **`false`** (büyük harf → muafiyet BYPASS olamaz) |
| `/api/agentx` | `false` |
| `/foo/api/agent/` | `false` |
| `/api/agent/../admin` | `false` (`..` → traversal) |
| `/api/agent//mesaj` | `false` (`//`) |
| `/api/agent%2f..%2fadmin` | `false` (decode sonrası `..`) |

**Müafiyet beyandan beslenir** (`:219-232`): önce `MachineApiRegistry::match()`
(`machine-api` beyan tablosu), sonra **eski emniyet** olarak sabit liste.
Sabit liste silinmemiştir: "beyan yoksa eski davranış" kuralı. Beyanlı ama
muafiyeti **verilmemiş** uçlarda (crew/worker) koruma **uygulanmaya devam eder**
(`:242-252`).

## 6. Akış — `SystemGuardHandler::isAccessDenied()` (248 satır)

```
isAccessDenied()                                  Handlers/SystemGuardHandler.php:30
 1. YOL ayrıştırma: '/' . trim(parse_url(REQUEST_URI, PHP_URL_PATH), '/')   :41
    isStaticAssetPath() → true ise return false (erişime açık)            :42-44
 2. Kritik yol muafiyeti:
    · dashboard öneki = project_data('dashboard_prefix') ?: RouteBlueprint::DASHBOARD_PREFIX  :53-54
    · RouteBlueprint::SYSTEM_ALLOWED_PATHS                               :57-59
    · str_starts_with($uriPath, $path) → return false                     :60-64
 3. BOŞ REMOTE_ADDR: CLI ise return false; WEB'de VIP DEĞİL (fail-OPEN değil)  :70-72
 4. maintenance_mode ayarı: repository('common.shieldSetting')             :76-77
    normalizeSwitch($value, false) → false ise return false               :80-82
 5. Geliştirici token bypass: X-RBN-DEV-TOKEN → crypto hashToken()
    → MasterDevelopersModel('dev_token_hash') exists → return false      :85-97
 6. Yerel IP beyaz listesi (virgülle ayrılmış, boş kayıtlar filtreli)     :103-113
 7. Global master VIP listesi: MasterIpWhitelistModel('ip_address')       :116-119
 → true (engelli)
```

**Ölçülen `isStaticAssetPath()` (5 yol):**

| Yol | Sonuç |
|---|---|
| `/framework-assets/css/a.css` | `true` |
| `/project-assets/js/b.js` | `true` |
| `/?x=/framework-assets/a.css` | **`false`** ← ham `REQUEST_URI` yerine **yol** ayrıştırılıyor |
| `/index.php` | `false` |

S-1 düzeltmesi (`:36-40` yorumu): önceki hâlde ham `REQUEST_URI` üzerinde
`str_contains` ile `/?x=/framework-assets/` gibi istekler **bakım modu + tüm VIP
kontrolunu atlıyordu**.

**`resolveFailClosed()`:** `Secrets::app()['guard_failclosed']` (`secrets.php`
`app` bölümü, PHP `bool`; FW-096-D8) → alan yoksa ya da dosya okunamazsa **`true`** (engelle). Tek kapı; ikinci "kapalı
listesi" yazılmaz (`ShieldSettingsRepository::KAPALI_DEGERLER` yalnız geriye
dönük uyum sabiti olarak korunur, `:195-196`).

**Ölçülen `isCliRequest()`:** `PHP_SAPI === 'cli'` (`:184-187`).

## 7. Akış — `DatabaseGuardHandler` (lisans kapısı dahil)

```
DatabaseGuardService::resolvePath($category, $projectKey)     DatabaseGuardService.php:37
 └─ DatabaseGuardHandler::resolve()                            Handlers/DatabaseGuardHandler.php:39
    ├─ validateIntegrity($category, $path)                     :58-110
    ├─ verifyTables($category, $pdo)                           :112-141
    ├─ checkProjectStatus($category, $path)                   :143-195
    │    ├─ service('masterLicences')->forSubject('project', $id)  :206-207
    │    ├─ LicenceAccessRule::isAnomalous($satir) → licenceWarnOnce()  :218-220
    │    ├─ return LicenceAccessRule::isUnrestricted($satir)      :222
    │    └─ Bootstrap::setAppContext('project_id'/'project_data')  :185-186
    └─ checkWritePermissions($category, $pdo)                  :232-243
         provider('databaseGuard')->performHeartbeatTest($pdo)
         PDOException → PreflightException("… rbn_heartbeats tablosuna yazma yetkisi yok")
```

**Ölçülen iki karar** (`LicenceAccessRule`, [Master.md §4](Master.md)):

| Kayıt | `isUnrestricted` | `isRecordUsable` |
|---|---|---|
| `null` | `false` | `false` |
| `{status:'active', tier:'FREE'}` | (kural gereği `true` alanı da kontrol edilir) | **`true`** |
| `{status:'revoked', tier:'PRO'}` | `false` | **`true`** |
| `{status:'bilinmeyen', tier:'X'}` | `false` | **`false`** |

`isRecordUsable` yalnız **tanınabilirlik** kontrol eder (fail-closed);
`isUnrestricted` tier+status+süre kuralıdır.

**Tuzak — ölü hata metni (`DatabaseGuardHandler.php:239`):** hata mesajı
`rbn_heartbeats` tablosunu söyler ama test yazdığı tablo `z_sys_heartbeats`'tir
([acik-sorular.md §1.2](../../acik-sorular.md), DOC-AGAC-1 bulgusu).

## 8. Akış — `GeoIPHandler` (389 satır) — ölçülen sabitler ve çıktılar

| Sabit | Değer | Kapsam |
|---|---|---|
| `GEOIP_UC_NOKTA` | `'https://ipwho.is/{ip}?fields=success,message,country_code'` | `private` |
| `GEOIP_SUNUCU` | `'ipwho.is'` | `private` |
| `GEOIP_TIMEOUT_SN` | `2` | `private` |
| `GEOIP_MAX_GOVDE_BAYT` | `4096` | `private` |
| `BILINMEYEN_ULKE` | `'XX'` | **`public`** |

Ölçülen çıktı:

```
getFlag('TR')      → '🇹🇷'
getFlag('ZZ')      → '🏳️'      ← tanımsız kod bayrağa düşer, hata vermez
getCountryName('TR') → 'Türkiye'
getCountryCode('127.0.0.1') → 'TR'   ← yerel IP, ağ yok; ülkeye düşüyor
```

**Tuzak:** `getCountryCode()` **ağ çağrısı yapar** (2 sn zaman aşımı) ve
sonuç **önbelleklenir** (`loadFlags()`, `:265`). `IpGuardService::countryCode()`
her istekte çağrılabildiği için önbellek zorunludur. Çevrimdışı ortamda
`getCountryCode('127.0.0.1')` dahi `TR` döndü — yani **yerel IP için ağ
başarısız olsa bile** bir değer üretilir (`BILINMEYEN_ULKE` = `XX` yalnızca
gövde ayrıştırılamazsa).

**Platform notu:** `GEOIP_UC_NOKTA` `https://ipwho.is` — üretimde **dış bağımlılık**.
Bu çalışmada **ağ çağrısı yapılmadı** (yerel IP yolu kullanıldı), dolayısıyla
timeout davranışı ve gerçek HTTP yanıtı ölçülmedi.

## 9. Yapılandırma — anahtarlar ve varsayılanlar (ölçülen)

`ShieldSettingsRepository` sabitleri (tümü ölçüldü):

| Sabit | Değer |
|---|---|
| `KEY_IP_GUARD_MODE` | `shield_ip_guard_mode` |
| `KEY_IP_GUARD` | `shield_ip_guard` |
| `KEY_RATE_LIMIT` | `shield_rate_limit` |
| `KEY_GEOIP_STATUS` | `shield_geoip_status` |
| `KEY_MAINTENANCE_MODE` | `maintenance_mode` |
| `KEY_MAINTENANCE_IPS` | `maintenance_ips` |
| `KEY_MAINTENANCE_MESSAGE` | `maintenance_message` |
| `MODE_LOG_ONLY` / `MODE_ENFORCE` | `log_only` / `enforce` |
| `KAPALI_DEGERLER` | `["0","false","off","no","hayir"]` |

| Diğer | Değer | Kaynak |
|---|---|---|
| `IpGuardService::BODY_SCAN_MAX_BYTES` | `65536` | `:185` (ölçüldü) |
| `IpGuardService::KADEMELI_CEZA_DAKAKA` | `[15, 60, 1440, 1440]` | `:521` (ölçüldü) |
| `blockIpAddress()` varsayılan süre | `1440` dk | `:557` |
| `RBN_GUARD_FAILCLOSED` | varsayılan `true` (engelle) | `SystemGuardHandler.php:221` (ölçüldü) |
| Cron pasifleştirme (log temizliği değil) | `rand(1,50)===1` | `TaskManager.php:92` |

## 10. Tuzaklar ve kurallar (kodda görülen + ölçülen)

1. **`shield_ip_guard_mode` yalnız tam `'enforce'` metni** ile zorlamaya
   sayılır; `1`/`true`/`on` **`log_only`'ya düşer**
   (`IpGuardHandler.php:40-43`, güvenlik incelemesi 104 A0-1a).
2. **Ayar okunamazlığı fail-OPEN DEĞİLDİR (B-10, ölçüldü):** okuma kendi
   `try/catch`'inde; hata olursa mod `log_only` + kill-switch **açık** varsayılır,
   `settings_unreadable` kararı yazılır ve katman **çalışmaya devam eder**
   (`IpGuardHandler.php:104-123`). Önceki hâlde katman hiçbir koruma üretmeden
   dönüyordu.
3. **`IpGuardHandler::check()` `DiagnosticException`'ı yeniden fırlatır**
   (`:104-105, :371-373`) — yani bu istisna türü katmanın `catch`'ine takılmaz.
4. **`RateLimitHandler` kendi `logWarning()`'ını tanımlar** (`:68`) — yani
   `LogsGuardErrors` trait'ini kullanmaz. İki ayrı günlük yolu var.
5. **`DatabaseGuardService::boot()` boştur** (`:29-32`) — "keşif ilk çağrıya
   ertelenir" (bootstrap'ta döngü riski).
6. **`DatabaseGuardService::check()` iki kategori doğrular:**
   `database_master` sonra `database_project` (`:69-76`).
7. **`IpGuardService::boot()` üç uyduyu uyandırır** (`:33-39`):
   `ipGuardHandler`, `geoIPHandler`, `rateLimitHandler`.
8. **`SystemGuardService::check()` tek mantıksal karar verir** (`:28-41`):
   `isAccessDenied()` true ise `shield->diagnostic('Sistem Bakımdadır 🛠️', …)`.
   Yani **HTTP 503 yerine RbnShield tanı sayfası** basılır.
9. **`isStaticAssetPath()` yalnız `AssetConfig` küme yollarını tanır**
   (`SystemGuardHandler.php:10` `AssetConfig` kullanımı) — `AssetConfig::
   PROXY_SETUP` iki küme (`framework-assets`, `project-assets`).
10. **`GeoIPHandler` `MachineApiRegistry` kullanmaz** — ajan muafiyeti
    `IpGuardHandler` katmanındadır. GeoIP **yalnız ülke/ban kaydı** içindir.

## 11. Örnek (gerçek koddan)

```php
// Core/Services/Gatekeepers/IpGuardService.php:173-175  (fail-closed muafiyet)
return $yol === '/api/agent'
    || str_starts_with($yol, '/api/agent/')
    || $yol === '/api/telegram/webhook';
```

```php
// Ölçülen çıktı — aynı ifadenin 6 varyantı
// /api/agent/x → true    /API/AGENT/x → false   (büyük harf)
// /api/agentx  → false   /api/agent/../admin → false  (traversal)
// /api/agent//mesaj → false (çift slash)   /api/telegram/webhook?x=1 → true
```

## 12. İlgili belgeler

* [Core/Services genel](README.md) · [Console](Console/README.md) · [Exception](Exception.md) ·
  [Master](Master.md)
* [Core/Http/Security.md](../Http/Security.md) (`MachineApiRegistry`, `InjectionHandler`,
  `FormGuardHandler`) · [Core/System/Kernel.md](../System/Kernel.md) (`ShieldSentinel`,
  `DatabaseGuardStage`) · [Core/Database/README.md](../Database/README.md) ·
  [Core/Support/Exceptions.md](../Support/Exceptions.md)
* [Kavram: veritabanı ve kiracılık](../../kavramlar/03-veritabani-ve-kiracilik.md) ·
  [Açık sorular §1.2](../../acik-sorular.md)