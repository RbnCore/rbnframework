# Core/Http — istek/yanıt motoru, doğrulama ve form güvenliği

> **Doğrulanan kod tabanı:** `d49b4413` (dal `feat/fw-license-master`) · **Tarih:** 2026-10-05 · **Yayın:** 0.9.4 = bu commit + sonrası; belge yalnız doğrulama anındaki kodu anlatır
> **Kaynak klasör:** `Core/Http/` — 36 `*.php` = **5 kök + 17 `Engine/` + 14 `Security/`**.
> Spek §1 "≥20 php alan için alt dal belgesi" kuralı gereği bu klasör **üç belgeye**
> bölünmüştür: kök dosyalar burada, `Engine/` → [Engine.md](Engine.md),
> `Security/` → [Security.md](Security.md).
> **Envanter:** 36 dosyanın **36'sı** anlatıldı (5 + 17 + 14).

## 1. Ne işe yarar, kim kullanır

İsteği okuyan (`Request`), yanıtı yazan (`Response`), kullanıcıya mesaj
iletiren (`AlertService`), dışarıya istek atan (`RemoteRequest`) ve kuralları
çalıştıran (`Validator`) beş sınıfı bir araya getirir. Hepsi trait'lere
bölünmüştür; trait'ler `Engine/` altındadır.

**Kimler çağırır:** tüm controller'lar (`request()`, `response()`, `alert()`
yardımcıları), `ValidationTrait` → `FormService`, `Core/Routes/Mappings/web.php`
(`ApiGuard`), `Packages/RbnEmail` ve ajan entegrasyonları (`RemoteRequest`),
`Packages/RbnFile` (`UploadedFile`).

## 2. Klasör/dosya envanteri — kök (5/5)

| Dosya | Görev | Önemli public yöntemler |
|---|---|---|
| `Request.php` | İstek anlık görüntüsü; `$_POST/$_GET/$_FILES/$_SERVER` yakalar, 5 trait kullanır. | `__construct()`, `getJsonData(): array`, `static capture(): self` |
| `Response.php` | Yanıt motoru; 3 trait (Header/Content/Redirect) kullanır. | `static getInstance(): self`, `send(): void` |
| `AlertService.php` | Tek mesaj kanalı (kilitli). Session/cookie saklar, AJAX'ta JSON, normalde yönlendirme. | `static get(): self`, `send(string $type,string $message,?string $redirectUrl=null,?string $message2=null,array $data=[]): void` |
| `RemoteRequest.php` | cURL tabanlı dış HTTP istemcisi. | `request(string $method,string $url,array $params=[],array $headers=[],bool $isJson=true,array $options=[]): array`, `get(...)`, `post(...)` |
| `Validator.php` | Kural motoru; kural adı → `validateX()` metodu eşlemesi. | `__construct(array $data,array $rules,array $messages=[])`, `static make(...)`, `fails(): bool`, `errors(): array`, `validated(): array`, `validate(): array`, `unknownRules(): array` |

Alt dal belgeleri:

| Alt klasör | `*.php` | Belge | Kapsama |
|---|---|---|---|
| `Engine/` | 17 | [Engine.md](Engine.md) | 17/17 |
| `Security/` | 14 | [Security.md](Security.md) | 14/14 |

## 3. Akış (kök sınıfların bağladığı zincir)

```
$request = request()                      Base/Web yardımcısı → Request::capture()
 └─ Request::__construct()  $_POST/$_GET/$_FILES/$_SERVER anlık görüntüsü
     ├─ InputTrait        (girdi toplama)              → Engine.md §2.2
     ├─ DetectionTrait    (method/içerik tipi)          → Engine.md §2.2
     ├─ ContextTrait      (IP/proje/UA/başlık)          → Engine.md §2.2
     ├─ FileTrait         (dosya girdileri)             → Engine.md §2.2
     └─ ValidationTrait   (shield + validate)           → Engine.md §3.1

$response = response()                    Response::getInstance()
 ├─ HeaderTrait      status/header/cookie  → Engine.md §2.3
 ├─ ContentTrait     body/json/success     → Engine.md §2.3
 └─ RedirectTrait    redirect + güvenliHedef süzgeci  → Engine.md §3.2

alert()->error('...', '/panel')           AlertService::get()->send()
 ├─ NotificationTrait kısa yolları       → Engine.md §2.5
 ├─ StorageTrait     session/cookie       → Engine.md §2.5
 └─ send() → response()->guvenliHedef($redirectUrl)  (AlertService.php:70-72)
```

`Validator::make(all(), $rules)` kural adını `validate<Rule>` metoduna çevirir
(`Validator.php:104-127`); eşleşme yoksa kural kayıt + `error_log` ile
**geçilmez, devam eder** (`Validator.php:143-150`).

## 4. Yapılandırma / ayar anahtarları

| Anahtar | Yer | Varsayılan / not |
|---|---|---|
| `security.form_input_mode` | `Engine/Traits/Request/ValidationTrait.php:178` | `enforce`; kabul: `off\|log\|enforce`. Okunamaz/bozuk → `enforce` (fail-closed) |
| `external-api.php` proje ayarı | `Security/ApiGuard.php:49` | `enabled`, `allowed_origins`, `allow_query_token`, `api_key`, `keys[]` |
| Cookie bayrakları | `Engine/Traits/Response/HeaderTrait.php:56` | `httpOnly=true`, `sameSite='Lax'`, `secure` null ise `RequestEnvironment::isHttpsRequest()` |

Ayrıntılı tablo: [Engine.md §4](Engine.md) ve [Security.md §4](Security.md).

## 5. Tuzaklar ve kurallar (kök katman)

1. **`AlertService::send()` yönlendirme süzgeci zorunludur.** AJAX yolunda
   `redirect()` hiç çağrılmadığı için `$redirectUrl` elle
   `guvenliHedef()`'ten geçirilir (`AlertService.php:70-72`); bu satır
   silinirse AJAX mesajları açık yönlendirme açığıdır.
2. **`Validator` bilinmeyen kuralı sessizce geçmez** (kayıt + `error_log` +
   devam) — bu, tavan katmanında KVKK onay kutusu doğrulamasının
   uygulanmadığı canlı bir hatayı yakalamıştır.
3. **Tek giriş noktası ilkesi:** dışarıdan korumasız ham gövde okuma yolu
   `rawAll()`'dır; `rawInput()` `protected`'tır
   (`Engine/Traits/Request/InputTrait.php:23-38`).
4. **`Response` ve `AlertService` tekil (singleton)**; `BaseComponent::__get()`
   bunları `$this->response` / `$this->request` adıyla açar
   ([../Base/README.md](../Base/README.md)).

## 6. Örnek (gerçek koddan)

```php
// doğrulamalı + kalkanlı form (ValidationTrait.php:125 sözleşmesi)
$data = request()->form(['email' => 'required|email'], ['csrf' => true]);

// JSON yanıt
return response()->success($data, 'Kaydedildi');

// güvenli yönlendirme
return response()->redirect('/panel/blog');
```

## 7. İlgili belgeler

* [Engine.md](Engine.md) — istek/yanıt trait'leri, doğrulama zinciri, kural motoru, redirect süzgeci
* [Security.md](Security.md) — form kalkanı handler'ları, `ApiGuard`, makine anahtar deposu
* [../Routes/README.md](../Routes/README.md) — `ApiGuard` middleware bağlantısı
* [../Base/README.md](../Base/README.md) — `BaseComponent::__get()` ile `$this->request/response/logs`
* [../System/Config.md](../System/Config.md) — `Config::get()` ve ayar önceliği
* [../../Packages/RbnFile.md](../../Packages/RbnFile.md) — `UploadedFile` tüketicisi
* [../../Packages/RbnEmail.md](../../Packages/RbnEmail.md) — `RemoteRequest` tüketicisi
* [../../kavramlar/02-yapilandirma.md](../../kavramlar/02-yapilandirma.md) — `security.*` anahtarları
