# Core/Http/Engine — istek/yanıt parçaları, doğrulama zinciri ve kural motoru

> **Doğrulanan kod tabanı:** `d49b4413` (dal `feat/fw-license-master`) · **Tarih:** 2026-10-05 · **Yayın:** 0.9.4 = bu commit + sonrası; belge yalnız doğrulama anındaki kodu anlatır
> **Kaynak klasör:** `Core/Http/Engine/` — **17 `*.php`**.
> **Envanter:** 17 dosyanın **17'si** aşağıda anlatıldı (3 kök + `Traits/Request/` 5 + `Traits/Response/` 3 + `Traits/Validator/` 4 + `Traits/Alert/` 2 = 17).

## 1. Ne işe yarar, kim kullanır

`Engine/`, `Request`/`Response` sınıflarının **bileşenlerini** barındırır:
yüklenen dosya sarmalayıcı (`UploadedFile`), sınıf bazlı doğrulama isteği
(`FormRequest`), HTTPS karar merkezi (`RequestEnvironment`) ve beş trait
kütüphanesi (istek / yanıt / doğrulayıcı / alert).

**Kimler çağırır:** `Core/Http/Request.php` ve `Core/Http/Response.php` bu
trait'leri kullanan tek iki sınıftır; controller'lar yalnız `request()` /
`response()` yardımcılarıyla erişir. `Packages/RbnFile` doğrudan
`UploadedFile` sınıfını örnekler (`FileService.php:45-51`).

## 2. Klasör/dosya envanteri (17/17)

### 2.1 Kök (3)

| Dosya | Görev | Önemli public yöntemler |
|---|---|---|
| `Engine/FormRequest.php` | Sınıf bazlı doğrulama isteği: `authorize()` + `rules()` + `messages()` + kalkan seçenekleri tek sınıfta toplanır. | `authorize(): bool`, `abstract rules(): array`, `messages(): array`, `withShield(): array`, `validateResolved(): array`, `failedAuthorization(): void` |
| `Engine/RequestEnvironment.php` | Tek HTTPS karar merkezi; cookie `Secure` bayrağı ve `isSecure()` buradan gelir. `final`, yalnız statik. | `static isHttpsRequest(?array $server = null): bool` |
| `Engine/UploadedFile.php` | `$_FILES` girdisini sarmalar; sunucu tarafı üretilen dosyalar `isLocal=true` ile de aynı sınıftan geçer. | `__construct(array $file, bool $isLocal=false)`, `tmpName(): string`, `isValid(): bool`, `isImage(): bool`, `extension(): string`, `mime(): string`, `size(string $unit='MB'): float`, `name(): string`, `move(string $path, ?string $filename=null): bool` |

### 2.2 `Traits/Request/` (5)

| Dosya | Görev | Önemli public yöntemler |
|---|---|---|
| `Traits/Request/InputTrait.php` | Girdi toplama (`GET`+`POST`+`JSON`); `rawInput()` ile korumasız çekirdek. | `all(): array`, `rawInput(array $rules=[]): array`, `input(string,$default=null)`, `query(?string,$default=null)`, `has/filled/missing(string): bool`, `only(array): array`, `except(array): array` |
| `Traits/Request/DetectionTrait.php` | Yöntem / içerik tipi / güvenlik algılama. | `method(): string`, `isMethod(string): bool`, `isPost()/isGet(): bool`, `isAjax(): bool`, `wantsJson(): bool`, `wantsXml(): bool`, `isJson(): bool`, `isFormData(): bool`, `prefers(array $contentTypes): ?string`, `isSecure(): bool` |
| `Traits/Request/ContextTrait.php` | İstek bağlamı: IP, proje, UA, yol/URL, başlıklar. | `ip(): string`, `projectId(): int`, `userAgent(): string`, `path(): string`, `url(): string`, `fullUrl(): string`, `root(): string`, `bearerToken(): ?string`, `header(string,$default=null)`, `parseHeaders(array): array` |
| `Traits/Request/FileTrait.php` | Dosya girdileri. | `file(string): ?UploadedFile`, `hasFile(string): bool`, `allFiles(): array`, `files(string $prefix): array` |
| `Traits/Request/ValidationTrait.php` | **Kalkan zinciri**: `shield()`, `validate()`, `form()`, `filter()`, `rawAll()`. | `const FORM_INPUT_MODE_FLAG='security.form_input_mode'`, `const FORM_INPUT_MODE_VARSAYILAN='enforce'`, `const FORM_INPUT_UNVALIDATED_KODU`, `const FORM_INPUT_LOG_ETIKETI`, `shield(array $options=[]): self`, `validate(array $rules,array $messages=[]): array`, `rawAll(array $options=[]): array`, `form(array $rules,array $options=[]): array`, `filter(array $rules,array $options=[]): array`, `formInputMode(): string` (protected) |

### 2.3 `Traits/Response/` (3)

| Dosya | Görev | Önemli public yöntemler |
|---|---|---|
| `Traits/Response/HeaderTrait.php` | Başlıklar, durum kodu, çerez yazımı. | `status(int): self`, `header(string,string): self`, `contentType(string $type='text/html',string $charset='utf-8'): self`, `cookie(string $name,string $value,int $minutes=60,string $path='/',?string $domain=null,?bool $secure=null,bool $httpOnly=true,?string $sameSite='Lax'): self`, `noContent(): void` |
| `Traits/Response/ContentTrait.php` | Gövde üretimi (HTML / JSON / alert JSON). | `body(?string): self`, `json(array,int $status=200): void`, `success(mixed $data=[],string $message='OK',int $status=200): void`, `error(string $message,int $status=400,mixed $data=null): void`, `alertJson(array,int $statusCode=200): void` |
| `Traits/Response/RedirectTrait.php` | Yönlendirme + **açık yönlendirme (open redirect) süzgeci**. | `redirect(string $url,int $code=302): void`, `guvenliHedef(string $url): string`, `goreliyiMutlakYap(string): string`, `guvenliRedirectHedefi(string): string` (geri uyum), `beyazListeHostlari(): array` |

### 2.4 `Traits/Validator/` (4)

| Dosya | Görev | Önemli public yöntemler |
|---|---|---|
| `Traits/Validator/CoreRulesTrait.php` | Çekirdek kurallar (`required/min/max/…`). | `validateRequired/Min/Max/Numeric/Confirmed/Regex/Alpha/Accepted/Nullable/Optional/String/Array/In/Same(string $field,$value,array $params=[]): void` |
| `Traits/Validator/DatabaseRulesTrait.php` | Veritabanı kuralları. | `validateUnique(string,$value,array $params=[]): void`, `validateExists(string,$value,array $params=[]): void` |
| `Traits/Validator/NetworkRulesTrait.php` | Ağ/biçim kuralları. | `validateEmail(string,$value): void`, `isDomainSyntaxValid(string): bool`, `validateIp/Url/Date/PhoneTr(string,$value): void` |
| `Traits/Validator/HelperTrait.php` | Hata toplama, varsayılan mesaj, alan adı güzelleştirme. | `addError(string $field,string $rule,array $params=[]): void`, `getDefaultMessage(string $rule,string $field,array $params=[]): string`, `getFieldDisplayName(string): string`, `sanitizeInput($input): string` |

### 2.5 `Traits/Alert/` (2)

| Dosya | Görev | Önemli public yöntemler |
|---|---|---|
| `Traits/Alert/NotificationTrait.php` | `alert()->success/error/info/warning()` kısa yolları. | `success/error/info/warning(string $message,?string $redirectUrl=null,?string $message2=null,array $data=[]): self` |
| `Traits/Alert/StorageTrait.php` | Mesajın session/cookie'ye yazılması ve temizlenmesi. | `viaCookie(): self`, `viaSession(): self`, `storeAlert(array): void`, `clear(): void` |

**Kapsama:** 17/17.

## 3. Akış

### 3.1 Form gönderimi (tarayıcı → DB'ye giden veri)

```
Controller: request()->form($rules, ['csrf' => true])
 └─ ValidationTrait::form()                       ValidationTrait.php:125
     ├─ applyFormGuard($options)                   :274
     │    ├─ isMethod($options['method'] ?? 'POST') değilse alert()->error + exit
     │    └─ shield($options)  (bayrak + seçenek)
     ├─ formInputMode() → Config::get('security.form_input_mode', 'enforce')   :175-192
     ├─ enforce kipi + boş kural dizisi → InvalidArgumentException   :131-138
     ├─ validate($rules)                           :140
     │    ├─ shieldActive → applyShieldConfigurations()   :288
     │    │    └─ FormService::runSecurityChecks($_POST, opts)   → ../Security/README.md
     │    ├─ POST_MAX_SIZE kontrolü (post boş + CONTENT_LENGTH>2048)  :56-61
     │    ├─ Validator::make(all(), $rules)          :64
     │    │    ├─ applyRule() → validateX() eşleşmesi  Validator.php:104-127
     │    │    └─ bilinmeyen kural: kayıt + error_log, DEVAM  :143-150
     │    ├─ fails() → handleFailedValidation(): $_SESSION['_errors']['_old_input'] + alert()->error + exit  :318-330
     │    └─ afterValidate() kancası (varsa)          :73-78
     ├─ enforce → yalnız $validated
     │   log/off → array_merge(rawInput($rules), $validated); log'da düşen alan adları kaydedilir  :142-151
     └─ nullable/optional alanlarda "" → null       :155-162
```

### 3.2 Yanıt ve yönlendirme

```
Controller: redirect('/panel')   → Response::redirect()          RedirectTrait.php:43
 ├─ guvenliHedef($url)                                     :45 / :85
 │    1) boş → '/'                                        :90-92
 │    2) kontrol karakteri/CRLF → '/'                     :95-97
 │    3) '\' → '/'                                        :105
 │    4) başı '/' olmayan GÖRELİ yol → '/' eklenir (şema taşıyan dokunulmaz)  :118 / :182-197
 │    5) '//host' veya mutlak → beyaz listede mi?          :123-158
 │    6) değilse → '/'  (fail-closed)                     :161
 ├─ headers_sent() değilse http_response_code + header('Location')
 └─ headers gittiyse <script>/<noscript>/<a> fallback (escape'li)  :52-58
```

`AlertService::send()` de aynı süzgeci kullanır: `$redirectUrl`
`response()->guvenliHedef()`'ten geçirilir (`Core/Http/AlertService.php:70-72`) —
AJAX yolunda `redirect()` hiç çağrılmadığı için bu süzgeç **zorunludur**.

### 3.3 Yüklenen dosya → `UploadedFile`

```
FormService::image('kapak', 'blog')            Packages/RbnFile/Services/FileService.php:39
 ├─ is_direct=true ise sunucu tarafı dosya UploadedFile'a sarılır   :44-51
 ├─ request()->file('kapak')  → FileTrait::file()   Traits/Request/FileTrait.php
 └─ FileValidatorHandler::validate()  → sonra FileUploadHandler::execute()
```

## 4. Yapılandırma / ayar anahtarları

| Anahtar | Yer | Varsayılan / not |
|---|---|---|
| `security.form_input_mode` | `Traits/Request/ValidationTrait.php:178` | `enforce`; kabul: `off\|log\|enforce`. Okunamaz/bozuk → `enforce` (fail-closed) |
| Cookie bayrakları | `Traits/Response/HeaderTrait.php:56` | `httpOnly=true`, `sameSite='Lax'`, `secure` null ise `RequestEnvironment::isHttpsRequest()` |

## 5. Tuzaklar ve kurallar

1. **`form([])` + `enforce` kipi istisna atar.** Kuralsız yazma yolu kapalıdır;
   ham gövde isteniyorsa `rawAll()` kullanılmalıdır
   (`ValidationTrait.php:131-138`). `off` yalnız acil kapama anahtarıdır.
2. **Çift çalıştırma tuzağı.** `applyFormGuard()` yalnız method kontrolü ve
   shield bayrağını kurar; denetimler `validate()` içinde çalışır. `rawAll()`
   doğrulama yapmadığı için denetimleri **kendisi** çağırır
   (`ValidationTrait.php:264-272`).
3. **Bilinmeyen kural sessizce geçmez.** Kayıt + `error_log` + devam
   (`Core/Http/Validator.php:143-150`).
4. **`rawInput()` korumasız çekirdektir** (`protected`); dışarıdan tek giriş
   noktası `rawAll()`'dır (`InputTrait.php:23-38`).
5. **CSRF'de geliştirici muafiyeti yok** (`../Security/README.md` §5.6).
6. **`X-Forwarded-Proto` yalnız "https" yönünde etkilidir**, ters yönde
   asla çıkarım yapılmaz (`RequestEnvironment.php:40-45`).
7. **`\`, `//host` ve mutlak host beyaz listeye girmez.** Beyaz liste iki
   aşamalıdır: güvenilir kök (`project_data('domain')`, `group_projects()`)
   ve yalnız köke **doğrulandıktan sonra** eklenen türevler (`baseUrl()`,
   `HTTP_HOST`); kök boşsa liste boştur ve **hiçbir mutlak hedef geçmez**
   (`RedirectTrait.php:237-303`).
8. **Alt alan eşleşmesi nokta ile başlar.** `notmysite.example` ve
   `site.example.test.evil.example` reddedilir (`RedirectTrait.php:411-433`).
9. **Port kuralı serttir:** hedefte açık port varsa beyaz listede de açık
   yazılmalıdır (`RedirectTrait.php:139-158`).

## 6. Örnek (gerçek koddan)

Kural + kalkanlı form (`ValidationTrait.php:125` sözleşmesi):

```php
$data = request()->form([
    'email'    => 'required|email',
    'mesaj'    => 'required|string|min:10|max:5000',
    'kvkk'     => 'accepted',
    'website'  => 'nullable',      // honeypot
], ['csrf' => true]);
```

Sorgu parametreleri için `filter()` (her alan `optional` olur ve verilmiş
varsayılana düşer, `ValidationTrait.php:227-254`):

```php
$liste = request()->filter(['s' => '', 'sayfa' => 1], ['method' => 'GET']);
```

## 7. İlgili belgeler

* [README.md](README.md) — `Core/Http` kök dosyaları (`Request`, `Response`, `AlertService`, `RemoteRequest`, `Validator`)
* [Security.md](Security.md) — `shield()` zincirinin yürütüldüğü handler'lar
* [../Base/README.md](../Base/README.md) — `BaseComponent::__get()` ile `$this->request/response`
* [../System/Config.md](../System/Config.md) — `Config::get()` ve ayar önceliği
* [../../Packages/RbnFile.md](../../Packages/RbnFile.md) — `UploadedFile`'ın tek dış tüketicisi
