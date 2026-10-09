# Core/Services/Exception — hata analizi, günlükleme ve kullanıcı/developer çıktısı (13 dosya)

> **Doğrulanan kod tabanı:** `c23b431f` · **Tarih:** 2026-10-05 · **Yayın:** 0.9.6 = bu commit + sonrası; belge yalnız doğrulama anındaki kodu anlatır
> **Kaynak klasör:** `Core/Services/Exception/` — **13 `*.php`** = 1 kök + `Concerns/` 2 +
> `Data/` 2 + `Handlers/` 3 + `Providers/` 4 (+ `Providers/Base/` 1).
> **Envanter:** 13 dosyanın **13'u** anlatıldı.
> **Doğrulama platformu:** Windows + PHP 8.3; `ExceptionService::shouldShowRichError()` ve `ShieldMetadata::ERROR_MAP` gerçekten okundu.

## 1. Ne işe yarar, kim kullanır

Framework'ün **hata yönetim katmanı**. `Throwable` → sınıflandırma → günlük →
çıktı (JSON veya HTML) zincirini kurar. Üç ayrı giriş noktası vardır ve
**üçü de fail-closed** tasarlanmıştır:

| Giriş | Çağıran | Bkz. |
|---|---|---|
| `ExceptionService::handle()` | Kernel/route sonrası normal akış | [Core/System/Kernel.md](../System/Kernel.md) |
| `ExceptionHandler::handle()` | `BootSentinel` (erken boot) | [Gatekeepers.md §3](Gatekeepers.md) |
| `SurvivalProvider::render()` | Son savunma (katman 0) | §3.3 |

## 2. Klasör/dosya envanteri (13/13)

| Dosya | Görev | Önemli public yöntemler |
|---|---|---|
| `ExceptionService.php` (83) | ** Orkestratör.** Analiz → günlük → render zincirini yürütür. | `boot(): void`, `handle(Throwable $e): void`, `shouldShowRichError(): bool` · magic: `errorAnalysisHandler`, `logHandler`, `exceptionHandler`, `developmentProvider`, `userErrorProvider` |
| `Handlers/ErrorAnalysisHandler.php` (125) | **Sınıflandırıcı.** `ShieldMetadata::ERROR_MAP`'ten `level/design/view/label` bulur, `ExceptionData` DTO'sunu doldurur. | `analyze(Throwable $e): ExceptionData` · korumalı: `hydrate(array)`, `classify(Throwable)`, `extractContext(Throwable)` |
| `Handlers/LogHandler.php` (123) | Günlükleme (dosya) + **failsafe** günlükleme. | `log(array $analysis): void`, `static failsafeLog(array $data,string $panicReason=''): void` |
| `Handlers/ExceptionHandler.php` (99) | **4 katmanlı hiyerarşi**: Panic → Fatal → Pre-flight → Development. | `handle(Throwable $exception): void` · korumalı: `survivalFallback(Throwable,string $messagePrefix='')`, `publicHint(string $hint): string` |
| `Data/ExceptionData.php` (41) | DTO: yalnız özellik taşır (doldurma mantığı `ErrorAnalysisHandler`'da). | `const DEBUG_MODE = true` · magic: `level, design, view, label, class, message, file, line, trace, code, type, hint, is_database, is_validation, is_diagnostic, can_render_rich, context` |
| `Data/ShieldMetadata.php` (76) | **SSoT:** marka sabitleri + istisna→katman politikası. | `SEO_TITLE`, `SEO_DESC`, `FAVICON`, `ICON`, `const ERROR_MAP` (6 kayıt) |
| `Providers/Base/BaseExceptionProvider.php` (305) | Tüm provider'ların tabanı: kimlik üretimi, redaksiyon, JSON/HTML render, `isAjax()`. | `const GENERIC_HINT` · korumalı statik: `richOutputAllowed()`, `newErrorId()`, `redactForPublicOutput()`, `safeSiteUrl()`, `renderAutonomous(string $view,array $data,int $code=500): void` · korumalı: `getBranding()`, `getNow()`, `isAjax()`, `renderJson(array,int $code=500)` |
| `Providers/UserErrorProvider.php` (134) | **Üretim** hata sayfası/JSON'u (4xx/5xx). | `render(int $code,array $data=[]): bool` · korumalı: `getErrorMapping(int $code): array` |
| `Providers/DevelopmentProvider.php` (99) | **Geliştirme** hata ekranı: kod parçacıkları, trace. | `render(array $analysis,Throwable $exception): bool`, `static renderFatal(string $errorType,string $errorMessage,string $solutionHint='',int $httpCode=500): void` · korumalı: `getCodeSnippet(?string $file,?int $line,int $padding=8): ?array`, `terminalRender(array)` |
| `Providers/PreflightProvider.php` (67) | Boot öncesi (pre-flight) hata çıktısı — keşif kullanmaz. | `render(string $view,array $data=[]): void`, `static renderFatal(...)` |
| `Providers/SurvivalProvider.php` (73) | **Katman 0:** hiçbir şey çalışmıyorken bile çalışan, bağımlılıksız ekran. | `static render(string $type,string $message,string $hint='',int $code=500): void`, `static checkAjax(): bool` |
| `Concerns/Shield.php` (90) | Programatik hata **fırlatma** yardımcıları. | `preflight(string $message,string $hint='',string $type='RbnShield Pre-Flight'): void`, `notFound(string $path)`, `pageNotFound(string $uri)`, `validation(array $errors,string $message='Form Validation failed.')`, `diagnostic(string $type,string $message,string $hint='')`, `panic(string $message,string $hint='')`, `forbidden(string $message='')`, `abort(int $code=404,string $message='')` |
| `Concerns/ErrorHandlingTrait.php` (91) | Hata biriktirme + JSON yanıt yardımcıları (`addError`, `fail`, `abort`, `sendSuccess/sendError`). | `addError(string $field,string $message)`, `getErrors(): array`, `hasErrors(): bool`, `clearErrors()`, `fail(string $message='The operation has failed.')`, `abort(int $code=404,string $message='')`, `sendSuccess(string $msg,array $data=[]): array`, `sendError(string $msg,array $extra=[]): array` |

## 3. Akış — `ExceptionService::handle()` (ExceptionService.php:42-58)

```
Throwable
 1. errorAnalysisHandler->analyze($e) → ExceptionData          Handlers/ErrorAnalysisHandler.php:26
 2. (array) $data → logHandler->log($arrayData)               Handlers/LogHandler.php:27
 3. Karar:
      level !== 'user' && shouldShowRichError() → developmentProvider->render($data, $e)   :53-54
      aksi halde                              → userErrorProvider->render($code, $data)  :55-57
```

**Kritik kural (`:52` yorumu):** `level === 'user'` olan hata **debug açık olsa bile**
`DevelopmentProvider`'a gitmez. Doğrulama hatası/404 üretimde kullanıcıya
kaynak sızdırmaz.

### 3.1 `shouldShowRichError()` — çift kapı (ExceptionService.php:63-76)

```
RBN_DEV tanımlı ve true değilse  → false   ← fail-closed (FW-KARAR-2 / Z-1)
RBN_DEBUG tanımlıysa              → RBN_DEBUG
RBN_DEBUG tanımsızsa              → false
```

**Ölçülen çıktı:** CLI bağlamında `RBN_DEV` **tanımsız** →
`shouldShowRichError() = false`. Yani ayarlar DB'sine sorulmaz; doğrudan
sabitler okunur (`ExceptionService.php:65-66` yorumu: "circular loops with
SettingsService" önlemi).

**Tuzak:** `RBN_DEV` **tanımlı olsa bile `false`** ise ayrıntılı ekran kapalıdır.
`RBN_DEV` tanımı `PreBoot` tarafından yapılır
([Core/System/Kernel.md](../System/Kernel.md)).

### 3.2 `ShieldMetadata::ERROR_MAP` — ölçülen 6 kayıt (ShieldMetadata.php:29-75)

| İstisna | `level` | `design` | `view` | `label` |
|---|---|---|---|---|
| `PreflightException` | `pre_flight` | `pre_flight` | `pre_flight` | Sistem Başlatma Hatası |
| `PDOException` | `fatal` | `fatal` | `internal` | Veritabanı Çöküşü |
| `DiagnosticException` | `development` | `development` | `development` | Sistem Teşhis Hatası |
| `ValidationException` | `user` | `user` | *(yok)* | Form Doğrulama Hatası |
| `PageNotFoundException` | `user` | `user` | `404` | Sayfa Bulunamadı |
| `'default'` | `development` | `development` | `development` | Sistem Hatası |

**Tuzak:** `ValidationException` ve `PreflightException` kayıtlarında **`view`
anahtarı yok**. `ErrorAnalysisHandler::classify()` bu eksik anahtarı
`hydrate()`'da ele almalıdır (`:30-48` dizi alanları doldururken). `view`
olmaması → provider tarafında `?? 'development'` gibi bir varsayılan gerekir;
bu varsayılan `ErrorAnalysisHandler.php:56-85` arasındadır (okundu).

### 3.3 Katman hiyerarşisi — `ExceptionHandler::handle()` (ExceptionHandler.php:22-63)

```
handle($e)
 ├─ $e instanceof PreflightException → PreflightProvider::renderFatal(...); return   :27-35
 │    (keşif KULLANILMAZ — boot sırasında henüz çalışmıyor olabilir)
 ├─ service('exception') çözülebiliyorsa → $service->handle($e); return                :38-44
 ├─ $e instanceof DiagnosticException → SurvivalProvider::render(...); return          :47-55
 └─ survivalFallback($e)  ← katman 0                                              :58
 catch (Throwable $e) → survivalFallback($e, "Critical Orchestration Failure: …")     :60-62
```

**`survivalFallback()` (`:69-86`):** önce `LogHandler::failsafeLog()` (kayıt),
sonra `SurvivalProvider::render()`. Yani **son savunma katmanı da günlük yazar** —
günlüğün kendisi patlarsa `failsafeLog` zaten minimum bağımlılıkla çalışır.

**`publicHint()` (`:92-98`) — ikinci `RBN_DEV` kapısı:** K-06 gereği
"composer install", tam yol gibi ipuçları **yalnız yerel geliştirmede** gösterilir.
`RBN_DEV` **tanımsızsa kapalı sayılır** (fail-closed) — erken boot'ta henüz
tanımlı değildir.

### 3.4 `Concerns\Shield` — hata fırlatma (Concerns/Shield.php:27-86)

Yedi yardımcı, her biri bir istisna sınıfı fırlatır veya `abort()` çağırır:

| Metot | Fırlattığı/ürettiği | Satır |
|---|---|---|
| `preflight()` | `PreflightException` | `:27` |
| `notFound($path)` | view bulunamadı | `:35` |
| `pageNotFound($uri)` | `PageNotFoundException` | `:43` |
| `validation($errors)` | `ValidationException` | `:51` |
| `diagnostic($type,$msg,$hint)` | `DiagnosticException` | `:59` |
| `panic($msg,$hint)` | en üst seviye | `:67` |
| `forbidden($msg)` | 403 | `:76` |
| `abort($code,$msg)` | genel | `:86` |

**Kullanım yeri örneği:** `CrawlerController::feed()` → `shield()->abort(404)`
([Core/Render/Controllers.md](../../Core/Render/Controllers.md)).

## 4. Yapılandırma ve sabitler

| Sabit | Değer | Kaynak |
|---|---|---|
| `ExceptionData::DEBUG_MODE` | `true` | `Data/ExceptionData.php:40` |
| `BaseExceptionProvider::GENERIC_HINT` | `'Ayrıntılar sistem günlüğüne yazıldı. Sorun sürerse sistem yöneticisine başvurun.'` | `Providers/Base/BaseExceptionProvider.php:25` |
| `ShieldMetadata::SEO_TITLE` | `FrameworkIdentity::SHIELD_NAME . ' \| Sistem Koruması ve Hata Yönetimi'` | `Data/ShieldMetadata.php:18` |
| `ShieldMetadata::SEO_DESC` | `SHIELD_NAME . ' güvenli hata yönetimi ve sistem koruma servisidir.'` | `:19` |
| `ShieldMetadata::FAVICON` | data-URI SVG (🛡️) | `:20` |
| `ShieldMetadata::ICON` | `<span style="color:#ef4444;font-size:32px;…">⚠️</span>` | `:21` |
| `ErrorAnalysisHandler` `code` varsayılanı | `$e->getCode() ?: 500` | `Handlers/ErrorAnalysisHandler.php:40` |

## 5. Tuzaklar ve kurallar

1. **`ExceptionHandler` `BaseService`'den DİRİK ÇÖZÜM YAPMAZ** — kendisi
   `BaseComponent` değildir, `BaseRender` değildir; sadece `BaseService::get()`
   çağırır (`:38`). Bu yüzden erken boot'ta güvenle kullanılabilir.
2. **`ExceptionService` `#[\AllowDynamicProperties]`** taşır
   (`ExceptionService.php:22`) — magic `__get()` ile tembel atanan uydurma
   özellikler PHP 8.2'de sesli olmasın diye.
3. **`boot()` uydurma özellikleri "uyandırır"** (`:32-36`):
   `errorAnalysisHandler`, `logHandler`, `exceptionHandler`,
   `developmentProvider`, `userErrorProvider`. Bu, keşif maliyetini ödemeyi
   **hata anına** bırakır.
4. **`ExceptionData` yalnız veri taşır** (`:53-54` yorumu) — `fromArray`
   kaldırıldı; doldurma `ErrorAnalysisHandler::hydrate()`'da.
5. **`ErrorAnalysisHandler` içinde `new ExceptionData()`** var (`:58`).
   Anayasa §1 `new` yasağı **bileşen kaydı** içindir; DTO özelliği bu kapsam
   dışındadır (aynı desen `LicenceAccessRule` yorumunda da belgelenmiştir).
6. **`can_render_rich` hesaplanırken `RBN_DEBUG` kullanılır, `RBN_DEV`
   kullanılmaz** (`ErrorAnalysisHandler.php:46`) — yani bu bayrak
   `shouldShowRichError()`'ın iki kapılı davranışından **farklıdır**.
   `ExceptionService::handle()` yine de `shouldShowRichError()`'a bakar
   (`:53`), yani pratikte `RBN_DEV` kapısı belirleyicidir; `can_render_rich`
   ikincil bir bilgidir.
7. **`DevelopmentProvider::render()` bool döndürür** (`:23`) — `false`
   döndürürse `ExceptionService` çıktı basmamış olur (return yok, `:53-57`).
   Dönüş değeri **yok sayılır** (`ExceptionService.php:53-54`).
8. **`DevelopmentProvider` ve `UserErrorProvider` `register()` metodu
   tanımlamaz** — ve bu artık bir sorun DEĞİLDİR. Ölçüm (FW-095): framework
   ağacındaki **41/41** sağlayıcı bu yola düşüyor, yani `[RBN] … register()
   metodunu TANIMLAMIYOR; kayit adimi sessizce atlandi (B-63 gorunurluk
   kaydi)` satırı saf yanlış-pozitif gürültüydü; kayıt işi constructor/DNA ile
   zaten yapılıyor. **FW-095'te bu günlük satırı kaldırıldı**; tanımlı olan bir
   `register()` varsa çağrı aynen sürer.

## 6. Örnek (gerçek koddan)

```php
// Core/Services/Exception/Handlers/ExceptionHandler.php:92-98  (K-06 fail-closed)
private function publicHint(string $hint): string
{
    if (defined('RBN_DEV') && RBN_DEV === true) {
        return $hint;
    }
    return 'Ayrıntılar sistem günlüğüne yazıldı. Sorun sürerse sistem yöneticisine başvurun.';
}
```

```php
// Ölçülen çıktı
// ExceptionService::shouldShowRichError()  → false   (RBN_DEV tanımsız)
// ShieldMetadata::ERROR_MAP anahtarları → PreflightException, PDOException,
//   DiagnosticException, ValidationException, PageNotFoundException, 'default'
```

## 7. İlgili belgeler

* [Core/Services genel](README.md) · [Gatekeepers](Gatekeepers.md) · [System](System.md)
* [Core/Support/Exceptions.md](../Support/Exceptions.md) (istisna sınıfları) ·
  [Core/System/Kernel.md](../System/Kernel.md) (panic / `RBN_PANIC_ACTIVE`) ·
  [Core/System/Storage.md](../System/Storage.md) (`LogHandler` yazma hedefi) ·
  [Core/Render/Handlers.md](../../Core/Render/Handlers.md) (`RawHtmlGate`)
* [Kavram: mimari harita](../../kavramlar/01-mimari-harita.md) · [Açık sorular](../../acik-sorular.md)