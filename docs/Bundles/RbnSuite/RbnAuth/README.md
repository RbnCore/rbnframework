# RbnAuth — Kimlik doğrulama, oturum, rol ve parola kurtarma

> **Doğrulanan kod tabanı:** `d49b4413` (dal `feat/fw-license-master`) · **Tarih:** 2026-10-05 · **Yayın:** 0.9.4 = bu commit + sonrası; belge yalnız doğrulama anındaki kodu anlatır
> **Kaynak klasör:** `Bundles/RbnSuite/RbnAuth/` — **15 `*.php`**
> (Controllers 3 · Handlers 5 · Services 2 · Models 3 · Middleware 1 · Support 1)
> **Envanter:** 15 php dosyasının **15'i** aşağıdaki tabloda anlatıldı.
> Bu paket 20 eşiğinin altında olduğu için **tek belge** yeterlidir (alt dal belge
> üretilmedi — kaynak ağacı hâlâ tamamen kapsanmıştır).

## 1. Ne işe yarar, kimler kullanır

RbnAuth, framework'ün **giriş kapısıdır**: kimlik doğrulama, oturum yaşam döngüsü,
rol tabanlı yetkilendirme (ACL), "beni hatırla", parola sıfırlama, e-posta
doğrulama ve tüm bunların denetim izi (audit).

RbnAdmin ve RbnStudio bu pakete **bağımlıdır**: ikisi de `admin` middleware grubu
altında çalışır (`Core/Routes/Mappings/core.php:75-86`) ve o grubun middleware'i
`AuthMiddleware`'dir. `RbnAdminService::saveColors()` yetkisini
`handler('access')->can('admin')` ile buradan sorar (`RbnAdminService.php:28`).

**Rotaları kendi yazmaz.** RbnAuth'in tek `registerRoutes()` metodu **yoktur**;
rotaları framework'ün kendi haritasında tanımlıdır:
`Core/Routes/Mappings/auth.php:10-54`. Bu, RbnAdmin/RbnStudio'dan farklıdır
(ikisi `ModuleData::registerRoutes()` kullanır).

**Görünümleri de yoktur.** Giriş/kayıt ekranları
`Resources/Views/RbnAuth/` altındadır (3 dosya: `auth.rbn.php`,
`Layouts/auth_header.rbn.php`, `Layouts/auth_footer.rbn.php`) ve
`AuthViewController` bunları `render('auth', 'login')` **iki argümanlı** (eski)
imzayla basar (`Controllers/AuthViewController.php:47`).

## 2. Dosya envanteri (15 dosya)

### 2.1 `Controllers/` — 3

| Dosya | Görev | Önemli public yöntemler (imza) |
|---|---|---|
| `AuthController.php` | Giriş/çıkış uçları. `#[Module(name:'rbnauth', context:'auth', data: ModuleData::class)]` (`:19`) | `authenticate()`, `loginSubmit()`, `logoutSubmit()`, `logout()` |
| `AuthActionController.php` | `AuthController`'dan türer (`:23`); kayıt + kurtarma işlemleri | `verifyEmail()`, `registerSubmit()`, `forgotPasswordSubmit()`, `resetPasswordSubmit()`, korumalı `confirmPasswordMatches(array $data): bool` (`:199`) |
| `AuthViewController.php` | Giriş/kayıt/kilit/şifre ekranları — **`#[Module]` özniteliği yok**, düz `BaseController` (`:17`) | `showLogin()`, `showRegister()`, `showLockscreen()`, `showForgotPassword()`, `showVerifyCode()`, `showResetPassword()`, `showUserDashboard(?string $path=null)`, korumalı `getDashboardUrl(string $role): string` |

### 2.2 `Handlers/` — 5

| Dosya | Görev | Önemli public yöntemler |
|---|---|---|
| `AccessHandler.php` | Giriş doğrulaması + rol denetimi + çıkış | `authenticate(string $identity, string $password): array` (`:40`), `can(string|array $requiredRole): bool` (`:188`), `terminate(): bool` (`:227`); korumalı: `equalizeVerifyTiming(string)`, `observeMasterIpBan(string): mixed`, `satirDegeri($satir, string $alan)`, `upgradePasswordHash(int,string,string)`, `forgetCookie(string)` |
| `SessionHandler.php` | Oturum açma, kilitleme, remember-me | `start($user, bool $isMaster=false, bool $remember=false): void` (`:22`), `lock(): void` (`:154`), korumalı `createRememberMe(int $userId, bool $isMaster=false)` (`:175`), `regenerateSessionId(): bool` (`:91`), `destroySessionFallback(): void` (`:117`) |
| `LifecycleHandler.php` | Kayıt / doğrulama / parola sıfırlama yazıcıları | `createIdentity(array $data): array` (`:69`), `confirmIdentity(int $userId): bool` (`:158`), `completeReset(int $userId, string $newPassword): bool` (`:203`); **statik** `deriveNameAndUsername(array $data): array` (`:44`); sabitler `NAME_MAX_LENGTH=200`, `USERNAME_MAX_LENGTH=100` (`:23,26`) |
| `CredentialHandler.php` | E-posta/parola politikası + token kasası | `validateEmail(string): array\|true` (`:23`), `validatePassword(string): array\|true` (`:60`), `calculateScore(string): int` (`:146`), `issueVaultToken(int $userId, string $type, int $hours=2): string` (`:188`); korumalı `containsSequentialRun(string): bool` (`:108`) |
| `AuditHandler.php` | Çok kanallı denetim günlüğü | `logSuccess(int $userId, string $identity, string $accountType='user')` (`:83`), `logFailure(string $identity, string $reason, string $accountType='user')` (`:172`), `logSecurityBlock(string $ip, string $reason)` (`:191`), `clearLoginFailureCounters(string $ip): bool` (`:136`); **statik** `maskIdentity(string): string` (`:34`), `sanitizeText(string, int $maxLength=200)` (`:70`) |

### 2.3 `Services/` — 2

| Dosya | Görev | Önemli public yöntemler |
|---|---|---|
| `AuthService.php` | Giriş/çıkış orkestrasyonu + oturumdaki kimlik kaynağı | `check(): bool` (`:26`), `login(string $identity, string $password, bool $remember=false): array` (`:35`), `user(): ?array` (`:151`), `logout(): bool` (`:173`) |
| `RecoveryService.php` | Kayıt + kurtarma akışının orkestratörü | `register(array $data): array` (`:42`), `initiateRecovery(string $email): array` (`:92`), `reset(string $rawToken, string $newPassword): array` (`:139`), `verify(string $rawToken, int $expectedUserId=0): array` (`:192`), `canShowResetForm(string $rawToken): bool` (`:230`); korumalı: `resetFlow`, `verifyFlow`, `claimToken(string,string): array`, `tokenErrorMessage(string): string`, `deliverLink(string,$subject,$view,$name,$token,int): bool` |

### 2.4 `Models/` — 3

| Dosya | Görev | Önemli üyeler |
|---|---|---|
| `AuthRole.php` | **Rol tablosu** (TEK kaynak) | `ROLES` (7 rol, `level` 5–100), `ADMIN_ROLES` (5 rol), `BYPASS_ROLES` (3 rol) |
| `AuthIdentity.php` | Paket kimliği + SEO + görünüm metinleri | `THEME_COLOR`, `FAVICON`, `AUTH_IDENTITY`, `DEFAULT_INFO` (6 ekran), `VIEW_MAP` |
| `ModuleData.php` | Kayıt merkezi | `CONFIG` (`:26`), `registerMap(): array` (`:53`) |

### 2.5 `Middleware/` — 1

| Dosya | Görev | Önemli public yöntemler |
|---|---|---|
| `AuthMiddleware.php` | Tüm rota güvenlik adımlarının tek orkestrasyonu | `handle(?string $requiredRole=null): void` (`:28`); **statik** `lockedPostAllowed(string $path): bool` (`:188`); korumalı: `ensureSessionPersistence()`, `clearRememberCookie()`, `verifyFingerprint(mixed)`, `enforceLockscreen()` |

### 2.6 `Support/` — 1

| Dosya | Görev | Önemli üyeler |
|---|---|---|
| `RememberTokenService.php` | "Beni hatırla" token'inin **süreli ve imzalı** biçimi (saf statik sınıf) | `VERSION='v2'`, `MAX_AGE_DAYS=30`, `MAX_AGE_SECONDS=2592000`, `LEGACY_ACCEPT_UNTIL=1793566800`; `issue(?int $now=null): array`, `isSigned(string): bool`, `isAcceptable(string, ?int $now=null): bool`, `expiresAt(string): ?int`; korumalı `isLegacyAcceptable`, `signature` |

### 2.7 Kayıt haritası (`ModuleData::registerMap()`, `:53-72`)

```
services  : auth → AuthService,        recovery → RecoveryService
handlers  : access → AccessHandler,    session  → SessionHandler,
            lifecycle → LifecycleHandler, credential → CredentialHandler,
            audit → AuditHandler
constants : role → AuthRole,           auth → AuthIdentity
```

Kayıt defteri boş dönmüyor: framework bu haritayı paket adı altında birleştirir
(`SystemRegistry.php:45`). `AuthMiddleware` ve `RememberTokenService` bu haritada
**yoktur** — middleware `RouteBlueprint::MIDDLEWARE['aliases']['auth'|'guard']`
üzerinden (`Core/Support/Definitions/Route/RouteBlueprint.php:42-43`), destek sınıfı
doğrudan `use` edilerek bağlanır.

## 3. Akışlar

### 3.1 Giriş (`POST /auth/login`)

```
POST /auth/login            → Core/Routes/Mappings/auth.php:21
 → AuthController::loginSubmit()                       AuthController.php:33
   → request->form(['email'=>'required|email','password'=>'required'],
                   ['rateLimitEnabled'=>true,'action'=>'login'])   :36-42
   → AuthService::login($email,$password,$remember)     AuthService.php:35
      ├─ ADIM 1: repository('project.user')->findMasterDeveloper($identity)  :40
      │    ├─ varsa parola doğrular → SessionHandler::start($master,true,$remember)  :48
      │    │    → admin_panel_disabled ise rolü 'user' yapıp '/user' döner   :54-63
      │    │    → değilse Route->url('',[], 'admin') döner                    :65-69
      │    └─ AuditHandler::logSuccess(..., 'master')                         :49
      └─ ADIM 2 (master değilse): AccessHandler::authenticate()   Handlers/AccessHandler.php:40
           ├─ Adım 1 : repository('project.userSecurity')->isIpBlocked($ip)  :45
           ├─ Adım 1b: master ban LİSTESİ **gözlenir**, engellemez           :76-83
           ├─ Adım 2 : findByIdentity() → yoksa DUMMY_HASH ile eşit maliyet   :88-95
           ├─ Adım 3 : isActive() → pasifsa aynı eşit maliyet                :99-106
           ├─ Adım 4 : crypto->verify() → hatalıysa service('ipGuard')->recordHit($ip,'login')  :109-119
           │           başarılıysa upgradePasswordHash()                     :122
           └─ Adım 5 : AuditHandler::logSuccess()                            :125
      ├─ ADIM 3: SessionHandler::start($user,false,$remember)  Handlers/SessionHandler.php:22
      │    → EN ÖNDE regenerateSessionId()  (session fixation)               :44
      │    → parola özeti eskiyse sessiz yenilenir                          :44 (AccessHandler:122)
      ├─ ADIM 4: required_login_role kontrolü → yetersizse logout() + hata   :98-111
      └─ ADIM 5: admin_panel_disabled / rol yönlendirmesi                    :113-145
 → Route->handleResult($result, …)                                          :49-53
```

**Hata sızıntısı kapatması:** `loginSubmit()`'ın `catch` bloğu istisna mesajını
**kullanıcıya göstermez**, yalnız `error_log()`'a yazar ve sabit mesaj döner
(`AuthController.php:54-70`). Gerekçe yorumda açık: canlıda kullanıcıya
`SQLSTATE[42S22]: Unknown column 'type' in 'where clause'` sızmıştı.

### 3.2 Çıkış — iki yol, kasıtlı

| Yol | CSRF | Davranış |
|---|---|---|
| `POST /auth/logout` → `logoutSubmit()` (`:89-100`) | **zorunlu** (`rawAll(['csrf'=>true,'logOnlySayac'=>false])`) | Çıkış yapılır |
| `GET /logout`, `GET /cikis` → `logout()` (`:120`) | yok | **LOG-ONLY** `logout_get_deprecated` sayacı; **çapraz-site** istekte **403 + çıkış yapılmaz** |

Çapraz-site tespiti iki kaynaktan olur: `Sec-Fetch-Site: cross-site` başlığı
(`:134-135`) ve `Referer` ana makinesinin `HTTP_HOST` ile karşılaştırılması
(`:138-151`). Başlıkların ikisi de yoksa (curl, PowerShell) çıkış **yapılır** —
aksi hâlde meşru bağlantılar kırılırdı (`:112-116`).

Kimlik eşleşmesi (A-14): çıkış yalnız **oturumdaki** kimliğe ait token'ı düşürür.
Master tokenı **koşulsuz** temizlenmez; yalnız oturumun kendisi master
geliştirici kimliğiyle açıldıysa (`AuthController.php:184-189`).

### 3.3 Middleware zinciri (her korumalı istekte)

```
AuthMiddleware::handle(?string $requiredRole)            AuthMiddleware.php:28
 ├─ 1 ensureSessionPersistence()   → 'rbn_remember' çerezinden oturum kurma  :33,59-105
 │     └─ önce RememberTokenService::isAcceptable($token)  (süre + imza)    :76
 ├─ 2 check() → değilse response()->redirect('/') + exit                       :36-39
 ├─ 3 verifyFingerprint()  → IP+UA sha256 değiştiyse logout + çıkış           :42,132-145
 ├─ 4 enforceLockscreen()  → kilitliyken yalnız izinli POST'lar               :45,150-181
 │     └─ lockedPostAllowed(): TAM eşleşme '/auth/login', '/auth/authenticate' :188-206
 └─ 5 can($requiredRole)   → değilse shield()->forbidden(...)                 :48-53
```

Middleware alias'ları: `auth`, `guard` → `AuthMiddleware`; gruplar: `user`,
`admin`, `superadmin`, `developer` → `['auth', 'role:<x>']`
(`Core/Support/Definitions/Route/RouteBlueprint.php:40-52`). `/user` paneli
`Route::role('user')->prefix('user')` grubunda tanımlıdır
(`Core/Routes/Mappings/auth.php:51-53`).

### 3.4 Parola sıfırlama (token zorunlu)

```
GET  /reset-password?token=T  → AuthViewController::showResetPassword()  :150
     └─ RecoveryService::canShowResetForm(T)  → repository->peek()  (tüketmez)  :230-243

POST /auth/forgot-password  → forgotPasswordSubmit()   :112
     → RecoveryService::initiateRecovery($email)      RecoveryService.php:92
       ├─ hesap yoksa: sabit maliyetli password_verify() + AYNI nötr mesaj  :104-109
       └─ hesap varsa: issueVaultToken(…, 'password_recovery', 1 saat) + e-posta

POST /auth/reset-password   → resetPasswordSubmit()    :143
     ├─ form: token(required|string), password(required|min:N),
     │         confirm_password(required|string), csrf + rateLimit('password_reset')  :145-162
     ├─ confirmPasswordMatches() → hash_equals()                         :168,199-209
     └─ RecoveryService::reset($token,$password) → claimToken() (TEK KULLANIMLIK)
          → LifecycleHandler::completeReset() → z_users_security.password_hash
                                                  + remember_token = NULL
                                                  + revokePending(...)             LifecycleHandler.php:203-237
```

E-posta doğrulama da aynı kalıpta: `verifyEmail()` (`:38`) `uid` + `token` ister,
`RecoveryService::verify()` token'ı **tüketir**, `uid` ile token sahibi örtüşmezse
aktivasyon olmaz (`RecoveryService.php:213-215`).

## 4. Yapılandırma / ayar anahtarları ve varsayılanlar

| Değer | Kaynak satırı | Varsayılan / etki |
|---|---|---|
| `RouteBlueprint::LOGIN_PATH` | `Core/Support/Definitions/Route/RouteBlueprint.php:75` | `'rbn-admin'` — gizli giriş yolu; `robots.txt`'te ilan edilmez (`AuthViewController.php:44-46`) |
| `dashboard_prefix` | `AuthViewController.php:36` | Boşsa `LOGIN_PATH` kullanılır (`:38`); `'user'` dışında herhangi bir değer admin girişi sayılır (`:41`) |
| `remember_me_duration` | `SessionHandler.php:214-217` | **ARTARAK KULLANILMAZ**; tek kaynak `RememberTokenService::MAX_AGE_SECONDS` (30 gün) |
| Token ömrü — e-posta doğrulama | `RecoveryService.php:28` | `EMAIL_VERIFICATION_HOURS = 24` |
| Token ömrü — parola kurtarma | `RecoveryService.php:31` | `PASSWORD_RECOVERY_HOURS = 1` (60 dk) |
| Token TTL kırpma | `CredentialHandler.php:197` | `max(1, min($hours, 24))` saat |
| Parola en kısa uzunluk | `PasswordValidations::MIN_PASSWORD_LENGTH` | `CredentialHandler.php:68,71` · `AuthActionController.php:71,148` |
| `required_login_role` | `AuthService.php:90` | Boşsa kapı yok; doluysa rol seviyesi karşılaştırılır (`:98-111`) |
| `admin_panel_disabled` | `AuthService.php:52,113` | Rol `user`'a zorlanır, yönlendirme `/user` olur |
| `user_dash_controller` | `AuthViewController.php:174` | Yoksa ortak `RbnCommon/userdash` şablonu basılır (`:231`) |
| Hız sınırı eylem adları | `AuthActionController.php:41,81,119,161` | `login`, `register`, `password_reset`; kanonik ad `RateLimitValidations::canon()` ile (`AuditHandler.php:145`) |
| `z_users.is_active` | `LifecycleHandler.php:117,170` | `2` = e-posta bekliyor, `1` = doğrulandı |
| `LEGACY_ACCEPT_UNTIL` | `RememberTokenService.php:60` | `1793566800` = **2026-11-01 21:00:00 UTC** (2026-11-02 00:00 Europe/Istanbul) — ölçüldü |

## 5. Tuzaklar ve kurallar (kodda ölçülmüş)

1. **Çıkış sırası bir daha bozulmamalı.** `SessionHandler::start()` **en başında**
   `regenerateSessionId()` çağırır (`SessionHandler.php:44`), çünkü
   `session_regenerate_id(true)` **eski oturum dosyasını siler**. `createRememberMe()`
   bundan sonra çağrılır (`:75-77`) — sıra tersine çevrilirse remember-me çerezi
   ölü oturuma bağlanır ve "1 giriş sonra hatırlanmıyorum" hatası doğar
   (`:161-167`).

2. **Master (global) IP ban listesi girişi **engellemez**.** `AccessHandler`
   `rbn_master.ip_blocks`'ı yalnız **gözlemler** (`observeMasterIpBan()`, `:147-163`)
   ve audit'a "would-block" yazar (`:76-83`). Ölçülen gerekçe yorumda: bu kayıtlar
   **tamamen** 127.0.0.1'e ait ve global; bağlansaydı tüm yerel girişler kapanırdı.
   `observeMasterIpBan()` bilinçli olarak **fail-open**'dır (`:140-145`) — engelleme
   kararı `shield_ip_guard_mode` anahtarına aittir.

3. **`DUMMY_HASH` gerçek bir hesaba ait değildir.** `AccessHandler.php:24` —
   rastgele bir bcrypt özeti; `equalizeVerifyTiming()` (`:30-33`) bununla
   `crypto->verify()` çağırıp kullanıcı yokken de aynı maliyeti üretir (zamanlama
   sızıntısı kapatması). `RecoveryService::initiateRecovery()` aynı amaç için
   **ayrı** bir sahte hash kullanır (`:106`) — iki yer, iki sabit.

4. **`AccessHandler::can()` seviye karşılaştırması yapar, isim karşılaştırması
   değil.** Dizi hâlinde roller verildiğinde önce **ad** eşitliği, sonra
   **seviye** karşılaştırılır (`:206-217`); tekil rolde yalnız seviye
   (`:220-221`). Bu yüzden `'moderator'` (60) istiyorsa `editor` (40) **geçmez** —
   doğru; ama `AuthRole::ADMIN_ROLES` üyeliği (5 rol) ile
   `can('admin')` (seviye 80 → 5 rolün **4**'ü geçer, `moderator` 60 **geçmez**)
   aynı şey değildir.

5. **`AuthController` `#[Module]` taşır, `AuthViewController` taşımaz.**
   `AuthViewController` (`AuthViewController.php:17`) düz `BaseController`'dır;
   `#[Module]` yalnız sınıfın kendisine değil, sınıf hiyerarşisine bakan
   `IdentityExtractorTrait`'e yansır (`ComponentHydratorTrait.php:35-51`) ve
   hiyerarşi taraması `parent::class` yönünde **yukarı** gider — yani alt sınıf
   üst sınıfın özniteliğini **görmez**. `AuthActionController extends
   AuthController` (`AuthActionController.php:23`) bu yüzden `#[Module]`'ı
   miraslar; `AuthViewController` hiçbir modül kimliğine sahip değildir ve
   `context` değeri `AuthViewController::showUserDashboard()` içinde **elle**
   atanır (`:230`).

6. **`AuthRole::BYPASS_ROLES` buradan okunmaz, başka yerden.** `FormGuardHandler`
   hız sınırı/honeypot muafiyeti için bu listeyi kullanır
   (`Core/Http/Security/Handlers/FormGuardHandler.php:162`); RbnAuth içinde
   referansı yoktur. Yani `developer/superadmin/admin` form korumalarından
   muaftır — `moderator` ve `editor` **değildir** (`AuthRole.php:99-103`).

7. **`AuditHandler::logFailure()` dosyaya maskeli, DB'ye düz yazar.**
   Dosya logu `maskIdentity()` kullanır (`AuditHandler.php:179-181`), DB kaydı
   `logFailedActivity()` çağrısında ham `$identity`'yi gönderir (`:185`) —
   kasıtlı: paneldeki "başarısız giriş denemeleri" listesi bu kayda dayanıyor,
   maskelenirse savunma yeteneği kaybolurdu (`:168-170`).

8. **`clearLoginFailureCounters()` yalnız giriş sayacını siler.** Önceki sürümde
   `unblockIp()` **tüm nedenli** ban kayıtlarını siliyordu; ölçülen sonuç: meşru
   kullanıcının girişi, aynı NAT/CGNAT IP'sindeki saldırganın yasağını da
   düşürüyordu. Artık yalnız kanonik `login` aksiyonunun sayacı silinir
   (`:136-158`). Ban kayıtlarına **dokunulmaz**.

9. **`VIEW_MAP` ve `DEFAULT_INFO` **iki farklı yoldan** okunur — yedekli bir
   yapı.** `ModuleData::CONFIG['configs']` ikisinin bir kopyasını taşır
   (`Models/ModuleData.php:42-45`), ama `AuthHandler` **doğrudan sabitlerden**
   okur: `$configs = AuthIdentity::VIEW_MAP` (`Core/Render/Handlers/UI/AuthHandler.php:30`),
   `$defaults = AuthIdentity::DEFAULT_INFO` (`:31`), ve `moduleData` varsa
   `configs.default_info` **kopyasını** tercih eder (`:39`) — yani veri tek
   kaynaktan gelir, kopya yalnız yedek. `VIEW_MAP` görünüm adını
   `DEFAULT_INFO` anahtarına çevirir (`:38`: `'forgot-password' → 'forgot'`).
   **Konvansiyon notu:** `VIEW_MAP` bir **görünüm → bilgi anahtarı** eşlemesidir,
   modül eşlemesi değil. RbnAdmin'de `PanelIdentity::VIEW_MAP` gerçekten ölüdür
   (panel görünümleri metinlerini kendi dosyalarında yazar ve hiçbir `AuthHandler`
   benzeri tüketici yoktur).

10. **`AuthIdentity::DEFAULT_INFO['forgot']` ve `['verify']` bayat metin.**
    `forgot.features` içinde "Geçici Erişim Kodu Desteği" ve `verify` başlığında
    "Kodu Doğrula" / "15 Dakika Boyunca Geçerli Kod" yazıyor
    (`AuthIdentity.php:70-93`), ama **6 haneli kod akışı kaldırıldı**
    (`AuthViewController::showVerifyCode()`, `:132-139`, artık
    `/forgot-password`'a yönlendiriyor) ve parola sıfırlama **60 dakika**.
    Bu metinler ekranda görünüyorsa yanlış bilgilendirmedir.

11. **`showVerifyCode()` kasıtlı olarak ölü bir uçtur.** Rota da kaldırılmıştır
    (`Core/Routes/Mappings/auth.php:37-39`: "hedef metot hiç yoktu"); metot
    geride kalmıştır ve kullanıcıyı bilgilendirerek `/forgot-password`'a
    gönderir (`AuthViewController.php:134-138`).

12. **`CredentialHandler::validatePassword()` **giriş** yolunda çalışmaz.**
    Kapsam dışı olduğu açıkça yazılıdır (`CredentialHandler.php:55-58`): mevcut
    kısa parolalı kullanıcılar bozulmasın diye yalnız yeni parola belirleyen
    akışlarda (kayıt, sıfırlama) çağrılır.

13. **`validatePassword()` girişte kısa parola denetlemez**, ama
    `AccessHandler::authenticate()` **uzunluk denetlemez** — yani 3 karakterli
    bir parola ile giriş mümkündür. Bu bilinçli bir geriye uyum kararıdır.

14. **`AuthViewController::showLogin()` yalnız admin yolunu basar.** Son kullanıcı
    portalı için `/login` isteği `response()->redirect('/')` ile ana sayfaya
    döner (`:51-52`) — yani kullanıcı giriş ekranı **framework'ün kendi
    `auth` görünümü** dışında bir yerden gelmelidir.

15. **`SessionHandler::createRememberMe()` yalnız normal kullanıcı kovasında
    satır yoksa `create()` yapar** (`:198-209`) — iki yazma yolu (update/create)
    elle ayrılmıştır.

16. **`upgradePasswordHash()` sessizce yutuyor.** `A-17` gereği başarısızlık
    girişi bozmaz (`AccessHandler.php:291-293`), ama `saveSecurityData()` tamamen
    başarısız olursa parola özeti eski kalır ve **hiçbir log yazılmaz**.

17. **`LifecycleHandler::createIdentity()` `z_users`'a `password_hash` YAZMAZ.**
    Yorumda ölçülmüş şema kanıtı var (`LifecycleHandler.php:100-105`): bu kolon
    `z_users`'ta yok; kasa `z_users_security.password_hash`'tir. Aynı gerekçe
    `completeReset()` içinde tekrarlanır (`:193-196`).

18. **`AuthService::login()` master geliştirici parolasını `crypto->verify()`
    ile doğrular ama `z_users` şemasını kullanmaz** — master tablosundaki
    `password` kolonundan okur (`AuthService.php:44-47`).

19. **`request->form()` ile `rawAll()` ayrımı güvenlik kontrolüne bağlı.**
    Parola tekrarı `rawAll()`'a **taşınmaz** çünkü ham yol güvenlik kontrolünü
    baypas ederdi (`AuthActionController.php:149-152`); buna karşılık çıkış
    `rawAll(['csrf'=>true])` kullanır, çünkü amacı yalnız kalkan zinciri
    (`AuthController.php:91-97`).

## 6. Örnek (gerçek koddan)

Statik, saf yardımcı — birim testlenebilir örnek (kırpma kuralı):

```php
// Bundles/RbnSuite/RbnAuth/Handlers/LifecycleHandler.php:60-63
return [
    'name'     => mb_substr($name, 0, self::NAME_MAX_LENGTH),      // 200
    'username' => mb_substr($finalUsername, 0, self::USERNAME_MAX_LENGTH),  // 100
];
```

Token biçimi (üç nokta ile ayrılmış, imzalı):

```php
// Bundles/RbnSuite/RbnAuth/Support/RememberTokenService.php:75
'token' => $expiresAt . '.' . $nonce . '.' . self::signature($expiresAt, $nonce),
// imza: HMAC-SHA256(uygulama anahtarı, "rbn_remember:v2:<expires>:<nonce>")
```

## 7. İlgili belgeler

* [RbnAdmin](../RbnAdmin/README.md) — panelin bu pakete bağımlı rotaları
* [RbnStudio](../RbnStudio/README.md)
* [kavramlar/01-mimari-harita](../../../kavramlar/01-mimari-harita.md) — açılış, oturum, render
* [kavramlar/03-veritabani-ve-kiracilik](../../../kavramlar/03-veritabani-ve-kiracilik.md) — `z_users`, `z_users_security`, kapsam
* [Core/Http](../../../Core/Http/README.md) — `form()`, `rawAll()`, `Validator`, `FormGuardHandler`
* [Core/Routes](../../../Core/Routes/README.md) — `RouteBlueprint::MIDDLEWARE`, rota grupları
* [Core/Base/Services](../../../Core/Base/Services.md) — `BaseService`, `BaseProvider`, `BaseChannel`
* [acik-sorular](../../../acik-sorular.md)
