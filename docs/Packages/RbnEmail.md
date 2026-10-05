# Packages/RbnEmail — SMTP gönderimi, şablon sarmalama ve IMAP okuma

> **Doğrulanan kod tabanı:** `d49b4413` (dal `feat/fw-license-master`) · **Tarih:** 2026-10-05 · **Yayın:** 0.9.3 = bu commit + sonrası; belge yalnız doğrulama anındaki kodu anlatır
> **Kaynak klasör:** `Packages/RbnEmail/` — **10 `*.php`**
> (6 `Handlers/` + 1 `Models/` + 1 `Services/` + 1 `Tasks/` + 1 `Views/`).
> **Envanter:** 10 dosyanın **10'u** aşağıda anlatıldı.

## 1. Ne işe yarar, kim kullanır

E-posta gönderiminin **orkestra** (servis + işçi handler'lar) ve okuma
tarafının (IMAP + MIME parser) paketidir. Gönderimde her iş adımı bir
"worker" handler'dır; servis bunları sırayla çağırır.

**Kimler çağırır:** `PackageData.php:23, 58-63` → `$this->service('email')`.
Kayıt/hatırlatma/form akışları `EmailService::send()` çağırır; toplu gönderim
`queueEmail()` ile cron kuyruğuna bırakılır.

## 2. Klasör/dosya envanteri (10/10)

| Dosya | Görev | Önemli public yöntemler |
|---|---|---|
| `Services/EmailService.php` | Orkestra şefi: gönderim, doğrudan gönderim, kuyruğa atma, açık mı. | `send(string $to, string $subject, string $view, array $data = [], ?string $toName = null, bool $useMaster = false)`, `direct(string $to, string $subject, string $body, ?string $toName = null, bool $useMaster = false)`, `queueEmail(...)`, `isEnabled()` (`:104-107`) |
| `Handlers/EmailConfigHandler.php` | Ayar çözümleyici: SMTP kimlik bilgileri + uygulama kimliği (logo, renk, imza). | `resolve(bool $useMaster = false)` (`:25`); `private isTrue($value): bool` (`:94`) |
| `Handlers/EmailGuardHandler.php` | Giden kimlik/itibar denetimi: biçim, kara liste, şüpheli desen, **çevrimdışı** alan adı sözdizimi. | `check(string $email)` (`:24`), `isDomainSyntaxValid(string $domain)` (`:75`) |
| `Handlers/EmailRenderHandler.php` | Tasarımcı: şablon çözümleme + premium HTML sarmalama. | `__construct()`, `view(string $path, array $data = [], string $subject = '')` (`:28`), `render(string $content, array $data = [], string $subject = '')` (`:37`), `parse(string $content, array $data = [])` (`:65`) |
| `Handlers/EmailTransportHandler.php` | PHPMailer sarmalayıcı; port→şifreleme eşlemesi. | `send(array $data)` (`:24`) |
| `Handlers/ImapClientHandler.php` | PHP `imap` uzantısı **olmadan** saf SSL/TLS soketle IMAP4rev1 istemcisi. | `connect(string $host, int $port, string $user, string $password, string $ssl = 'ssl')`, `selectFolder(string $folder = 'INBOX')`, `fetchRecentHeaders(int $limit = 20)`, `fetchMessageBody(string\|int $uid)`, `disconnect()`, `__destruct()` |
| `Handlers/MailParserHandler.php` | MIME multipart / HTML gövde / ek ayrıştırıcı. | `parse(string $rawMessage)` |
| `Models/EmailConstant.php` | Paket sabitleri ve görsel varsayılanlar. | `const NAME='RbnEmail'` `:14`, `const VERSION='1.5'` `:15`, `const SMTP_AUTH=true` `:19`, `const SMTP_SECURE='tls'` `:20`, `const TIMEOUT=30` `:21`, `const CHARSET='UTF-8'` `:22`, `const DEBUG=false` `:25`, `const LOG_ENABLED=true` `:26`, `const LOG_FILE='email'` `:27`, `const DEBUG_LOG_FILE='email_debug'` `:28`, `const DEFAULT_THEME_COLOR='#3b82f6'` `:31`, `const DEFAULT_BG_COLOR='#f3f4f6'` `:32`, `const DEFAULT_TEXT_COLOR='#1f2937'` `:33`, `const DEFAULT_LOGO_SVG` `:38`, `const DEFAULTS` `:43` |
| `Tasks/EmailQueueTask.php` | Arka plan gönderim işçisi (cron/queue motorundan gelir). | `run(array $params = [])` |
| `Views/layout.php` | E-posta iskeleti (tablo tabanlı HTML kabuk). `{{ $degisken }}` yer tutucuları. | — (şablon) |

**Kapsama:** 10/10.

## 3. Akış

### 3.1 Şablonlu gönderim

```
EmailService::send($to, $subject, $view, $data, $toName, $useMaster)  EmailService.php:20
 ├─ [GUARD] emailGuard->check($to)                        :24-25
 │    ├─ filter_var(FILTER_VALIDATE_EMAIL)               EmailGuardHandler.php:29
 │    ├─ EmailValidations::TEMP_DOMAINS                   :38
 │    ├─ EmailValidations::SPAM_DOMAINS                   :42
 │    ├─ SUSPICIOUS_PATTERNS + DISPOSABLE_PATTERNS       :47-52
 │    └─ isDomainSyntaxValid() (ÇEVRİMDIŞI, DNS çağrısı YOK)  :62 / :75-99
 ├─ emailRender->view($view, $data, $subject)             :28
 │    ├─ loadFile(): Paths::project()->views("$path.rbn.php") → "$path.php"  :79-88
 │    ├─ emailConfig->resolve($useMaster) → görsel DNA    EmailRenderHandler.php:41
 │    ├─ parse() ile {{ $anahtar }} değiştirme            :65-74
 │    └─ Views/layout.php iskeletine gömülür               :52-59
 └─ emailTransport->send([...])                           :31-37
      ├─ new PHPMailer(true) → isSMTP()                   EmailTransportHandler.php:35, 38
      ├─ port 465 → SMTPS, 587 → STARTTLS, diğer → SMTPS   :43
      └─ hata ayrıntısı 'email_debug' kanalına             :61
```

### 3.2 Kuyruğa atma

```
EmailService::queueEmail(...)                              EmailService.php:74
 ├─ [GUARD] emailGuard->check($to)                         :78
 └─ service('cron')->dispatch(EmailQueueTask::class, [...]) :82-88
     └─ EmailQueueTask::run($params) → send() → EmailService::send()
```

### 3.3 Gelen kutusu okuma (IMAP)

```
ImapClientHandler::connect(host, port, user, pass, 'ssl')   ImapClientHandler.php
 ├─ selectFolder('INBOX')
 ├─ fetchRecentHeaders(limit = 20)
 └─ fetchMessageBody(uid) → MailParserHandler::parse($raw)
     └─ gövde + ekler ayrıştırılır
```

## 4. Yapılandırma / ayar anahtarları

| Anahtar | Yer | Varsayılan / not |
|---|---|---|
| Proje ayarı `email` / `email_critical` | `EmailConfigHandler.php:42-43` | `service('settings')->read('email')` + `read('email_critical')`; ikisi `array_merge` edilir (`useMaster=false` iken) |
| `smtp_host`, `smtp_port`, `smtp_username`, `smtp_password` | `EmailConfigHandler.php:65-68` | Proje ayarı **önceliklidir**; yoksa `SmtpProfileResolver` (`secrets.php` `smtp` bölümü) |
| `email_from_address`, `email_from_name` | `:69-70` | `useMaster=true` ise ayar **hiç okunmaz**, doğrudan çözümleyiciye gider |
| `email_enabled` | `:64` | `isTrue()` yalnız `on/1/true/'true'` kabul eder (`:94-97`) |
| Şifreleme | `EmailTransportHandler.php:43` | 465 → `SMTPS`, 587 → `STARTTLS`, diğer → `SMTPS` |
| Zaman aşımı / karakter seti | `EmailConstant.php:21-22` | 30 sn, `UTF-8` |
| Log kanalları | `EmailConstant.php:27-28` | `email` ve `email_debug` |

## 5. Tuzaklar ve kurallar

1. **DNS sorgusu bilinçli olarak kaldırılmıştır (A-07).**
   `EmailGuardHandler` artık `checkdnsrr($domain,'MX')` **çağırmaz**: PHP bu
   çağrıda zaman aşımı kabul etmez, gönderimden hemen önce kullanıcıyı
   bekleten asılı bir çözücü isteği bırakır. Karar **yalnız sözdizimine**
   dayanır; gerçek teslimat doğrulaması SMTP katmanındadır
   (`EmailGuardHandler.php:54-64`).
2. **`useMaster=true` önbelleği atlar** (`EmailConfigHandler.php:27-29`) ve
   proje ayarı okumaz; sistem/master maillerinde gereksiz önbellek
   tetiklenmez (`:40-44`).
3. **SMTP değerleri `secrets.php` `smtp` bölümünden gelir**; gömülü
   `SMTP_*`/`EMAIL_FROM_*` sabitleri kaldırılmıştır (`:61-62`).
4. **Logo uzantısı `.svg` ise `.png`'ye çevrilir** (`:50-52`) — e-posta
   istemcilerinin çoğu SVG'yi göstermez; ardından şema taşımayan göreli yol
   `https://{domain}/` ile mutlaklaştırılır (`:54-56`).
5. **Şablon dosyası bulunamazsa işlem durmaz:** `loadFile()` metin olarak
   `"Template not found: ..."` döner (`EmailRenderHandler.php:87`); bu
   kullanıcıya giden bir e-postadır, bu yüzden hataya çevrilmemiştir.
6. **`Views/layout.php` yoksa** minimal HTML'e düşülür
   (`EmailRenderHandler.php:52-54`).
7. **`parse()` yalnız skaler değerleri değiştirir** (`:67-71`); dizi/object
   değerler `{{ $x }}` içinde görünmez.

## 6. Örnek (gerçek koddan)

```php
// Şablonlu gönderim
$this->service('email')->send($to, 'Hoş geldiniz', 'welcome', ['ad' => $ad]);

// Doğrudan HTML
$this->service('email')->direct($to, 'Bildirim', '<p>...</p>');

// Kuyruk
$this->service('email')->queueEmail($to, 'Rapor', 'weekly-report', $data);
```

## 7. İlgili belgeler

* [PackageData.md](PackageData.md) — `services.email`, `handlers.email*`, `metadata.EMAIL_` kayıtları
* [RbnApi.md](RbnApi.md) — `RemoteRequest` tabanlı dış çağrılar (bu paket SMTP'yi doğrudan kullanır)
* [../Core/Http/README.md](../Core/Http/README.md) — `RemoteRequest`
* [../Core/System/Config.md](../Core/System/Config.md) — `service('settings')` ve `SmtpProfileResolver`
* [../Core/Support/Blueprints.md](../Core/Support/Blueprints.md) — `EmailValidations` (TEMP/SPAM/DISPOSABLE listeleri)
* [../kavramlar/02-yapilandirma.md](../kavramlar/02-yapilandirma.md) — `smtp` ayarları
