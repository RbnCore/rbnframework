# Core/Http/Security — form kalkanı, dış API koruması ve makine anahtar deposu

> **Doğrulanan kod tabanı:** `d49b4413` (dal `feat/fw-license-master`) · **Tarih:** 2026-10-05 · **Yayın:** 0.9.4 = bu commit + sonrası; belge yalnız doğrulama anındaki kodu anlatır
> **Kaynak klasör:** `Core/Http/Security/` — **14 `*.php`** (5 kök + 8 `Handlers/` + 1 `bin/`).
> **Envanter:** 14 dosyanın **14'ü** aşağıda anlatıldı.

## 1. Ne işe yarar, kim kullanır

`Security/`, tarayıcıdan gelen formu gönderim anında denetleyen **form kalkanı**
(`FormService` → `Handlers/*`) ile, dışarıdan gelen makine trafiğini denetleyen
**API korumasını** (`ApiGuard` + `MachineApiRegistry` + `MachineApiKeyStore`)
bir arada tutar. `FormService` ince bir ağ geçididir; asıl iş
`FormGuardHandler` ile `Handlers/` altındaki denetleyicilerdedir.

**Kimler çağırır:**
`Core/Http/Engine/Traits/Request/ValidationTrait.php` (`form()`/`rawAll()` →
`FormService::runSecurityChecks()`),
`Core/Routes/Mappings/web.php` (`ApiGuard` middleware),
`Core/Render` görselleri (`UrlSafetyHandler`),
operatör (CLI: `Security/bin/keys.php`).

## 2. Klasör/dosya envanteri (14/14)

### 2.1 Kök (5)

| Dosya | Görev | Önemli public yöntemler |
|---|---|---|
| `Security/FormService.php` | İnce ağ geçidi; `token()`/`honeypotName()` üretir, ağır işi `FormGuardHandler`'a yollar. | `token(): array`, `honeypotName(): string`, `runSecurityChecks(array $data,array $options=[]): array` |
| `Security/ApiGuard.php` | Dış API middleware'i: proje anahtarı → yapılandırma → IP → kimlik → kapsam → gövde → hız sınırı → denetim. | `handle(): void` |
| `Security/UrlSafetyService.php` | URL güvenlik servisi (görsel URL'leri için üst katman). | `validate(string $url, ?string $title=''): array` |
| `Security/MachineApiRegistry.php` | Makine API yollarının **kapsam beyan kaydı** (tohum + runtime). `final`. | `declarations(): array`, `declare(string $path,string $scope='default',bool $enforce=self::ENFORCE_DEFAULT,bool $ipExempt=false): void`, `match(?string $uri=null): ?array`, `isDeclared(?string $uri=null): bool`, `normalize(?string $uri): ?string` |
| `Security/MachineApiKeyStore.php` | Anahtar tanım normalizasyonu, `hash_equals` karşılaştırma, kimlik/scope yürütmesi. `final`. | `normalizeDefinitions(mixed): array`, `normalizeScopes(mixed): array`, `normalizeRate(mixed): ?array`, `normalizeTime(mixed): ?int`, `resolve(array $tanimlar,string $given): ?array`, `isRevoked(array,?int $now=null): bool`, `isExpired(array,?int $now=null): bool`, `generate(int $bytes=32): array`, `generateId(string $scope='default',array $mevcut=[]): string`, `rememberIdentity(array): void`, `currentIdentity(): ?array`, `forgetIdentity(): void`, `assertScope(?string $action): bool` |

### 2.2 `Handlers/` (8)

| Dosya | Görev | Önemli public yöntemler |
|---|---|---|
| `Handlers/CsrfHandler.php` | CSRF token üretimi/doğrulama (1 saat geçerli). | `generate(): array`, `validate(string $token): bool`, `verify(array $data): array` |
| `Handlers/BotHandler.php` | Honeypot alan adı + kullanıcı aracısı denetimi. | `getDynamicName(): string`, `validateHoneypot(array $data,string $field='website'): bool`, `validateUserAgent(): bool`, `static isSuspiciousUserAgent(string $ua): bool` |
| `Handlers/FormGuardHandler.php` | Kademeli ceza + sayaç kayıt defteri (F-10…F-14). | `const SAYAC_SINIRSIZ_FORM`, `const SAYAC_ORIJIN_YOK`, `const SAYAC_GET_CIKIS`, `artirLogOnlySayac(string): void`, `static logOnlySayacOku(?string $anahtar=null): int\|array`, `audit(array $data,array $options=[]): array` |
| `Handlers/OriginHandler.php` | `Origin`/`Referer` doğrulama. | `validate(array $allowedOrigins=[],bool $strict=false): bool` |
| `Handlers/InjectionHandler.php` | XSS/enjeksiyon deseni taraması. | `detectXss(array $data): bool` |
| `Handlers/SanitizationHandler.php` | Girdi temizleme. | `trimData(array): array`, `stripTags(array,array $fields=[]): array`, `clean(?string): string` |
| `Handlers/FileSecurityHandler.php` | Yüklenen dosya güvenliği (Http katmanı; ayrıntılı doğrulama `Packages/RbnFile`'da). | `validateRequestFiles(array $files): array` |
| `Handlers/UrlSafetyHandler.php` | URL analizi: ana domain, IP, yasak desen, görsel ölçüsü. | `analyze(string $url, ?string $title=''): array` |

### 2.3 `bin/` (1)

| Dosya | Görev | Önemli public yöntemler |
|---|---|---|
| `Security/bin/keys.php` | `external-api` anahtar yönetimi CLI'si: `list/create/revoke/rotate`. Anahtar tanımlarını `external-api.php` dosyasına **yeniden yazar**. | — (CLI betiği; `MachineApiKeyStore` statiklerini kullanır) |

**Kapsama:** 14/14.

## 3. Akış

### 3.1 Form gönderimi denetimi

```
ValidationTrait::form()/rawAll()          Engine/Traits/Request/ValidationTrait.php:140, 264
 └─ FormService::runSecurityChecks($_POST, $options)     FormService.php
     └─ FormGuardHandler::audit($data, $options)          FormGuardHandler.php
         ├─ CsrfHandler::verify()        → token 1 saat, hash_equals   CsrfHandler.php:22, 61
         ├─ BotHandler::validateHoneypot() + validateUserAgent()      BotHandler.php
         ├─ OriginHandler::validate()    → Origin/Referer               OriginHandler.php
         ├─ InjectionHandler::detectXss()                              InjectionHandler.php
         ├─ SanitizationHandler::trimData()/stripTags()                SanitizationHandler.php
         └─ kademeli ceza: KADEMELI_CEZA_DAKIKA dizisine göre sayaç artar
            (yazılamazsa LOG-ONLY sayaç artar → fail-open)
```

### 3.2 Dış API isteği

```
Mappings/web.php: ApiGuard middleware
 └─ ApiGuard::handle()                       ApiGuard.php:40
     ├─ project_key() boş → 400 NO_PROJECT   :42-45
     ├─ resolveProjectConfig('external-api') → yoksa 403 NOT_CONFIGURED / DISABLED  :49-56
     ├─ ipKontrol(): allowed_origins boşsa veya '*' ise DENETİM YAPILMAZ  :90-99
     ├─ kimlikDogrula(): başlık X-RBN-API-KEY, yoksa input('token')  :110-187
     │    ├─ allow_query_token=false ise sorgudan gelen anahtar reddedilir  :124-129
     │    ├─ keys[] → MachineApiKeyStore::resolve() (hash_equals)  :139-165
     │    ├─ iptal/süre → 401  :149-156
     │    ├─ düz api_key dalı → hash_equals  :169-173
     │    └─ MachineApiKeyStore::rememberIdentity() + kapsamKontrol()  :160-162
     ├─ MachineApiRegistry::match() → kapsam beyanı (runtime `declare()` ile büyür)
     ├─ govdeKontrol() (LOG-ONLY)   :228-252
     ├─ hizSiniri() (dosya sayacı, LOG-ONLY, fail-open)  :262-333
     └─ denetim('allow') → logs kanalı 'machine-api'  :390-408
```

## 4. Yapılandırma / ayar anahtarları

| Anahtar | Yer | Varsayılan / not |
|---|---|---|
| `external-api.php` proje ayarı | `ApiGuard.php:49` | `enabled`, `allowed_origins`, `allow_query_token` (**varsayılan `true`**), `api_key`, `keys[]` |
| `keys[]` alanları | `bin/keys.php:165-174` | `id`, `hash` (`sha256:<hex>`), `scopes`, `rate{max,per}`, `body_max`, `expires_at`, `revoked_at`, `label` |
| `MachineApiRegistry::SEED` | `MachineApiRegistry.php:~90-103` | `path`→`scope`; `ENFORCE_DEFAULT` log-only |
| `FormGuardHandler` kademeli ceza | `FormGuardHandler.php:27` | `KADEMELI_CEZA_DAKIKA = [15, 60, 1440, 1440]` |
| `FormGuardHandler::SAYAC_*` | `:53,56,74` | `form_without_rate_limit`, `origin_referer_missing`, `logout_get_deprecated` |
| `security.form_input_mode` | `../Engine/README.md` §4 | Kalkanın açılıp kapanması buradan karar verilir |

## 5. Tuzaklar ve kurallar

1. **`?token=` kapatılmaz** (patron kararı): yalnız LOG-ONLY uyarı ve
   `allow_query_token` ayarı (`ApiGuard.php:26-28, 124-136`).
2. **Gövde ve hız sınırı LOG-ONLY'dur** (`enforce=false`); sayaç dosyası
   yazılamazsa **fail-open** (`ApiGuard.php:262-333`).
3. **Çözülemeyen middleware fail-closed'dır** — bu katmanda değil, rota
   katmanındadır: `Core/Routes/Engine/Dispatcher.php:107-109`.
4. **CSRF'de geliştirici muafiyeti yok.** Tüm roller için doğrulama aynı ve
   zorunludur (`CsrfHandler.php:68-76`).
5. **Token 1 saat geçerli; süre dolunca `validate()` oturumu temizler**
   (`CsrfHandler.php:55-59`).
6. **IP denetimi `allowed_origins` boşsa veya `'*'` ise hiç yapılmaz**
   (`ApiGuard.php:90-99`) — bu, "denetim yapıldı" anlamına gelmez.
7. **Çift çalıştırma tuzağı yok:** `FormGuardHandler::audit()` tek giriş
   noktasıdır; `ValidationTrait` `rawAll()` yolunda denetimleri kendisi çağırır
   (`ValidationTrait.php:264-272`).
8. **Webp/URL güvenliği iki katmanlıdır:** `UrlSafetyService` →
   `UrlSafetyHandler::analyze()`; ikisi de allow-list mantığıyla çalışır,
   kara liste değil.

## 6. Örnek (gerçek koddan)

Form kalkanı kullanımı (`ValidationTrait.php:125` sözleşmesi):

```php
$data = request()->form([
    'email'   => 'required|email',
    'website' => 'nullable',      // honeypot
], ['csrf' => true]);
```

`FormService` doğrudan da çağrılabilir (View katmanından):

```php
$token = $this->service('http.form')->token();
```

## 7. İlgili belgeler

* [README.md](README.md) — `Core/Http` kök dosyaları
* [Engine.md](Engine.md) — `form()`/`rawAll()` çağıran taraf ve `RedirectTrait`
* [../Routes/README.md](../Routes/README.md) — `ApiGuard`'ın middleware olarak bağlandığı yer
* Render katmanı (`Core/Render/`) — görsel URL'lerinin tüketildiği yer (⏳ ayrı görev, belgesi henüz yazılmadı)
* [../../kavramlar/02-yapilandirma.md](../../kavramlar/02-yapilandirma.md) — `security.*` anahtarları
* [../../acik-sorular.md](../../acik-sorular.md) §3 (`security.form_input_mode = log` ölçüm turu)
