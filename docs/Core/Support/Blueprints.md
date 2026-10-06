# Core/Support/Blueprints — Doğrulama ve alan sabitleri

> **Doğrulanan kod tabanı:** `d508f5e1` (dal `feat/fw-license-master`) · **Tarih:** 2026-10-05 · **Yayın:** 0.9.4 = bu commit + sonrası; belge yalnız doğrulama anındaki kodu anlatır
> **Kaynak klasör:** `Core/Support/Blueprints/` — 11 `*.php` (`Constants/` 2, `Validations/` 9).
> **Envanter:** 11 dosyanın 11'i aşağıda anlatıldı. Sayılar `ReflectionClass` ile PHP 8.3'te ölçüldü (Windows).

## 1. Ne işe yarar, kim kullanır

"Plan/şablon" sabitleridir: kural **sayıları, kara/beyaz listeler, hata iletileri** koddan ayrı, tek yerde durur. Hiçbir sınıfta iş mantığı yoktur (birkaç küçük statik yardımcı hariç). `ValidationResolver` bu klasörün `Validations/` alt dizinini tarar ve her sınıfı kategori adıyla (`Email`, `File`, `Form`, `Mime`, `Password`, `Phone`, `RateLimit`, `Security`, `Threat`) `Definition`/`validation()` çağrılarına açar ([Discovery §2.3](../System/Discovery.md)). Ayrıca `SystemRegistry` `validations` anahtarıyla altı sınıfı (`email, file, form, password, phone, security`) açıkça kaydeder ([Registries](../System/Registries.md)).

**Kimler çağırır:** `Core/Http/Engine/Traits/Validator/*` (form doğrulama), `Core/Http/Engine/Traits/Request/ValidationTrait`, `Core/Services/Gatekeepers/Handlers/RateLimitHandler` (hız sınırı), `Core/Render/Controllers/AssetController`, dosya yükleme bileşenleri. Sınıf adıyla geçen dış dosya sayıları (`Core/Bundles/Packages` taraması): `RateLimitValidations` 3, `EmailValidations` 4, `FileValidations` 3, `PasswordValidations` 3, `ThreatValidations` 2, `MimeValidations` 2, `PhoneValidations` 1, `SecurityValidations` 1, `FormValidations` 1.

## 2. Dosya envanteri

### 2.1 `Constants/` (ad alanı `…\Support\Blueprints\Constants`)

| Dosya | İçerik | Yöntem |
|---|---|---|
| `EntityConstants.php` | `ENTITY_MAP` — 20 varlık adının Türkçe karşılığı (`category, menu, user, role, permission, setting, settings, general, profile, auth, page, block, contact, notification, security, developer, backup, log, sidebar, navigation`). | `static translate(string $entity): string` |
| `FieldTypeConstants.php` | `FIELD_TYPES` (13: `text, textarea, tel, whatsapp, email, url, number, select, checkbox, file, image, color, theme_swatch`), `FIELD_DISPLAY_NAMES` (20), `FIELD_LENGTH_LIMITS` (17). | `static getFieldTypes(): array` |

### 2.2 `Validations/` (ad alanı `…\Support\Blueprints\Validations`)

| Dosya | İçerik (ölçülmüş sayılar) | Yöntemler |
|---|---|---|
| `EmailValidations.php` | `TRUSTED_DOMAINS` 27, `HIGH_REPUTATION_DOMAINS` 9, `TEMP_DOMAINS` 36, `SPAM_DOMAINS` 20, `ROLE_BASED_ADDRESSES` 12, `SUSPICIOUS_PATTERNS` 11, `DISPOSABLE_PATTERNS` 2 | — |
| `FileValidations.php` | 9 iletiş sabiti (`REQUIRED, INVALID_TYPE, MAX_SIZE, MIN_DIMENSIONS, MAX_DIMENSIONS, MIME_MISMATCH, SECURITY_THREAT, MOVE_ERROR, IMAGE_INVALID`); `PRESETS` 4 (`avatar` ≤2 MB jpg/jpeg/png/webp ≥100×100 + `thumb`; `gallery` ≤10 MB +gif, `medium`; `document` ≤20 MB pdf/doc/docx/xls/xlsx/txt/zip/rar; `system` ≤50 MB sql/json/xml/zip); `VERSIONS` 3 (`thumb` 150×150, `medium` 600×450, `large` 1200×900) | `static getPreset(string $name): ?array`, `static getVersion(string $name): ?array` |
| `FormValidations.php` | 17 iletiş sabiti (`REQUIRED, MIN, MAX, NUMERIC, ALPHA, REGEX, CONFIRMED, UNIQUE, EXISTS, EMAIL, EMAIL_TEMP, EMAIL_MX, EMAIL_DOMAIN, URL, IP, DATE, PHONE_TR`); yer tutucular `{field}`, `{min}`, `{max}` | — |
| `MimeValidations.php` | `CATEGORIES` 7 (`image, document, archive, video, audio, code, asset`), `MAP` 28 (uzantı → MIME listesi) | `static getExtensionsByCategory(string)`, `static getMimesByExtension(string)`, `static isValidMime(string $extension, string $mime): bool` |
| `PasswordValidations.php` | `MIN_PASSWORD_LENGTH = 8`, `COMMON_PASSWORDS` 11, `COMMON_WORDS` 17, `SEQUENTIAL_PATTERNS` 4, `KEYBOARD_PATTERNS` 8 | — |
| `PhoneValidations.php` | `TURKISH_PHONE_PREFIXES` 32, `TURKISH_PHONE_LENGTH = 11` | — |
| `RateLimitValidations.php` | `LIMITS` 21 eylem, `FALLBACK_LIMIT = ['max'=>10,'window'=>1200]`, `ACTION_ALIASES` 5, `DIRECT_ACTIONS` 1 | `static canon(string): string`, `static getLimit(string): array`, `static hasLimit(string): bool`, `static limitOrFallback(string): array` |
| `SecurityValidations.php` | `BTK_BLOCK_IPS` 2, `PROHIBITED_DOMAINS` 10, `SPAM_WORDS` 9 | — |
| `ThreatValidations.php` | `FORBIDDEN_EXTENSIONS` 22, `SUSPICIOUS_PATTERNS` 8 | `static isForbiddenExtension(string $extension): bool`, `static containsSuspiciousPattern(string $content): bool` |

## 3. Akış — hız sınırı eylem adı (en kritik kullanım)

1. Çağıran (örn. giriş formu) `action` olarak `login` gönderir.
2. `RateLimitValidations::canon('login')` küçük harfe çevirip `ACTION_ALIASES`'ten `frontend_login` kovasına çevirir (`RateLimitValidations.php:163-167`).
3. `getLimit($action)` `LIMITS[canon]` yoksa **istisna fırlatır** (fail-closed); `limitOrFallback()` yoksa `FALLBACK_LIMIT` verir.
4. `RateLimitHandler` `max` istek / `window` saniye uygular.

`LIMITS` (eylem → max/pencere sn): `frontend_login 5/900`, `admin_login 3/1800`, `user_register 5/3600`, `password_reset 3/3600`, `form_submission 10/1200`, `contact_form 3/3600`, `add_comment 10/300`, `search_query 30/600`, `api_request 100/3600`, `ajax_request 50/600`, `file_upload 20/3600`, `export_data 5/3600`, `import_data 3/3600`, `backup_create 2/3600`, `settings_update 10/600`, `cache_clear 5/3600`, `bulk_update 3/1800`, `email_send 10/3600`, `suspicious_activity 1/3600`, `action 60/3600`, `sql_console_execute 10/3600`.

`ACTION_ALIASES`: `login→frontend_login`, `register→user_register`, `recovery→password_reset`, `actions→action`, `default_form→form_submission`. `DIRECT_ACTIONS`: `sql_console_execute`.

## 4. Yapılandırma

Bu klasörde çalışma anı ayarı yoktur; her değer sabittir. Değiştirmek kod değişikliğidir (sürümlenir). Ayrıca dosya yükleme koruması iki yerde birlikte okunur: `ThreatValidations::FORBIDDEN_EXTENSIONS` (kesin yasak) ve `MimeValidations::MAP` (uzantı–MIME eşleşmesi).

## 5. Tuzaklar ve kurallar

1. **Yeni hız-sınırı eylemi eklerken** `LIMITS`'e gerçek karşılık yazılmalıdır; `getLimit()` bilinmeyen eylemde istisna atar (A0-3 düzeltmesi: eski sessiz varsayılan "fail-open"dı ve giriş/kayıt/şifre sıfırlama limitsiz kalmıştı).
2. **`recovery` kalıcı takma addır** (`password_reset` ile aynı kovayı paylaşır, çift sayım yok).
3. **`Constants/` klasörü `ValidationResolver` tarafından taranmaz** (yalnız `Validations/`); `FieldTypeConstants` yalnız `Core/Http/Engine/Traits/Validator/HelperTrait.php:51` içinde `FIELD_DISPLAY_NAMES` için dolaylı kullanılır; `EntityConstants` için dış çağıran sınıf adıyla bulunamadı.
4. **`SystemRegistry` yalnız altı doğrulama sınıfını kaydeder** (`email, file, form, password, phone, security`); `Mime`, `RateLimit`, `Threat` kategori adıyla yine bulunur (`ValidationResolver` klasörü taradığı için), ama registry anahtarı yoktur.
5. **Kategori adı dosya adından türer** (`EmailValidations` → `email`): `str_replace(['Map','Definitions','Blueprint','Validations'], '', …)` (`ValidationResolver.php:124`); dosya yeniden adlandırılırsa kategori adı değişir.
6. **Kara liste verileri (`TEMP_DOMAINS`, `SPAM_DOMAINS`, `BTK_BLOCK_IPS` …) kurum bilgisi içermez**; bu belge listelerin içeriğini kopyalamaz (sürümlenen tek kaynak koddur).

## 6. Örnek (gerçek koddan)

```php
// Core/Support/Blueprints/Validations/RateLimitValidations.php (ACTION_ALIASES)
'login'    => 'frontend_login', // AuthController::loginSubmit
'recovery' => 'password_reset', // eski şifre sıfırlama istek adı
```

```php
// Core/Http/Engine/Traits/Validator/HelperTrait.php:51
$fieldNames = $fConst ? $fConst::FIELD_DISPLAY_NAMES : [];
```

## 7. İlgili belgeler

* [Core/Support genel bakış](README.md) · [Definitions](Definitions.md) · [System/Discovery](../System/Discovery.md) · [System/Registries](../System/Registries.md)
* [Açık sorular](../../acik-sorular.md)
