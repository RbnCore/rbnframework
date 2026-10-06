# Core/Support/Exceptions — Çatı istisna sınıfları

> **Doğrulanan kod tabanı:** `d508f5e1` (dal `feat/fw-license-master`) · **Tarih:** 2026-10-05 · **Yayın:** 0.9.5 = bu commit + sonrası; belge yalnız doğrulama anındaki kodu anlatır
> **Kaynak klasör:** `Core/Support/Exceptions/` — 8 `*.php`.
> **Envanter:** 8 dosyanın 8'i aşağıda anlatıldı.

## 1. Ne işe yarar, kim kullanır

Çatının kendi istisna türleridir. Çoğu `DiagnosticException`'dan türer: `(tür, mesaj, ipucu, kod)` dörtlüsünü taşır ve hata sayfası/`PreflightProvider` bu dörtlüyü doğrudan ekrana çevirir. Böylece "hata türü + ne yapmalı" bilgisi istisnanın içindedir.

**Kimler fırlatır / yakalar:** `Kernel::boot()` `PreflightException`'ı yakalayıp `PreflightProvider::renderFatal()` çağırır ([Kernel §3.3](../System/Kernel.md)); `BaseGuard::fail()` `PreflightException` fırlatır; `DatabaseGuardHandler` `ProjectSuspendedException` fırlatır; `ErrorAnalysisHandler` `ValidationException`'ı işler; `Core/Services/Console` cron görevleri `CronTaskException` kullanır. Sınıf adıyla geçen dış dosya sayıları (`Core/Bundles/Packages`): `PreflightException` 13, `CronTaskException` 7, `DiagnosticException` 7, `PageNotFoundException` 3, `ValidationException` 3, `ViewNotFoundException` 2, `ProjectSuspendedException` 1, `Support\Exceptions\RuntimeException` 0.

## 2. Dosya envanteri

| Dosya | Üst sınıf | Görev | Yapıcı / yöntemler |
|---|---|---|---|
| `DiagnosticException.php` | `\Exception` | Tür+mesaj+ipucu+kod taşıyan taban. | `__construct(string $type, string $message, string $hint = '', int $code = 500)`, `getType()`, `getHint()`, `getRenderData(): array` (`type, message, hint, code`) |
| `PreflightException.php` | `DiagnosticException` | Açılış ön kontrolü başarısız (kernel kesilir). | `__construct(string $message, string $hint = '', string $type = 'RbnShield Pre-Flight', int $code = 500)` |
| `ProjectSuspendedException.php` | `DiagnosticException` | Proje askıda/pasif; varsayılan kod 403. | `__construct(string $message, string $hint = '', string $type = 'RbnShield Project Status', int $code = 403)` |
| `RuntimeException.php` | `DiagnosticException` | Genel çalışma anı hatası, tür `RUNTIME_ERROR`, kod 500. | `__construct(string $message, string $hint = 'Sistem operasyonu sırasında beklenmedik bir hata oluştu.', int $code = 500)` |
| `ValidationException.php` | `DiagnosticException` | Form doğrulama hatası (HTTP 422), hata dizisi ve dönüş adresi. | `__construct(string $message, array $errors = [], ?string $redirectUrl = null)`, `getErrors(): array`, `getRedirectUrl(): string` |
| `ViewNotFoundException.php` | `DiagnosticException` | Görünüm dosyası yok (404). | `__construct(string $path, int $code = 404, ?\Exception $previous = null)`, `getPath(): string` |
| `PageNotFoundException.php` | `\Exception` | Rota/sayfa yok; mesaj `Page Not Found: /<uri>`, kod 404. | `__construct(string $uri, int $code = 404, ?\Throwable $previous = null)`, `getUri(): string` |
| `CronTaskException.php` | `\RuntimeException` (PHP'nin) | Cron görev sonucu: `skipped` mi `failed` mi. | statik fabrikalar: `skipped($msg)`, `alreadyPublished($detail)`, `notScheduledTime($detail)`, `candidateNotFound($msg)`, `configuration($msg)`, `missingParameter($paramKey,$projectKey)`, `modelNotFound($modelAlias,$projectKey)`, `invalidTaskClass($className)`, `invalidTaskInheritance($className)`, `taskFileNotFound($taskClass,$taskKey)`, `undefinedTaskKey($taskKey)`; `getCronStatus(): string`, `isSkipped(): bool` |

## 3. Akış

1. Bir bekçi `BaseGuard::fail($msg,$hint,$type,$code)` çağırır → `PreflightException`.
2. `Kernel::boot()` yakalar → `PreflightProvider::renderFatal($e->getType(), $e->getMessage(), $e->getHint(), $e->getCode())` → sayfa basılır, `exit`.
3. `ValidationException` doğrulama katmanından fırlatılır; `ErrorAnalysisHandler` `errors` ve `redirect_url` alanlarını yanıta koyar (`Core/Services/Exception/Handlers/ErrorAnalysisHandler.php:112`).
4. Cron: görev "atla" gerektiğinde `CronTaskException::skipped()` (durum `skipped`), gerçek hatada diğer fabrikalar (durum `failed`) fırlatılır; çağıran `isSkipped()` ile ikisini ayırır.

## 4. Yapılandırma

Yok; varsayılan HTTP kodları: `PreflightException` 500, `ProjectSuspendedException` 403, `ValidationException` 422, `ViewNotFoundException`/`PageNotFoundException` 404, `RuntimeException` 500.

## 5. Tuzaklar ve kurallar

1. **`PreflightException` son çaredir:** yakalanınca `exit` edilir; bu istisnayı üretim kodunda "olağan hata" gibi fırlatmayın (açılışı durdurur).
2. **İsim çakışması:** `Rbn\Framework\Core\Support\Exceptions\RuntimeException` ile PHP'nin `\RuntimeException`'ı farklı sınıflardır. Sınıf adıyla başka dosyadan **hiç** kullanılmıyor (tarama: 0 dış dosya). `CronTaskException` ise çatının değil PHP'nin `\RuntimeException`'ından türer, `DiagnosticException` ailesinde değildir.
3. **`ValidationException::$redirectUrl` varsayılan olarak `$_SERVER['HTTP_REFERER']`'dir** (yoksa `/`), yani istemci kontrollüdür. Dönüş adresini kullanan tek dış yer `ErrorAnalysisHandler` (yanıt verisine `redirect_url` koyar); adres yönlendirmeye çevrilirken (yönlendirme katmanı) dış alan adı denetimi o katmanda yapılmalıdır — bu istisna sınıfı doğrulama yapmaz.
4. **`ViewNotFoundException` mesajı Türkçe/İngilizce karışıktır** (`[RBN]:: Görünüm Dosyası Bulunamadı: <yol>`, ipucu İngilizce); ekrana yol basıldığı için üretimde ayrıntı gizleme `Bootstrap::configureErrorVisibility` ve hata sayfasına bağlıdır.
5. **`CronTaskException::taskFileNotFound` mesajı "Görev pasif konuma alındı" der**, ama sınıf yalnız mesaj taşır; görevi pasife almak çağıranın işidir.
6. **`DiagnosticException::getRenderData()`** yalnız dört alan döndürür; `ValidationException`'ın `errors` dizisi bu çıktıda yoktur, ayrıca `getErrors()` ile alınır.

## 6. Örnek (gerçek koddan)

```php
// Core/System/Kernel/Base/BaseGuard.php:41-44
protected static function fail(string $msg, string $hint, string $type = 'RbnShield', int $code = 500): void
{
    throw new PreflightException($msg, $hint, $type, $code);
}
```

## 7. İlgili belgeler

* [Core/Support genel bakış](README.md) · [Contracts](Contracts.md) · [System/Kernel](../System/Kernel.md)
* [Açık sorular](../../acik-sorular.md)
