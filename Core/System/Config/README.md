# Core/System/Config — TEK KAPI Kuralı

> **Bu dosya neden var:** "Global anahtarlar **TEK** dosyada toplansın; herkes her yerde ayrı okuyup tanımlamasın." (Patron isteği, 2026-10-02)
> Bu klasördeki **her ayrı okuyucu** bir daha eklenmemek üzere burada sayılıyor.

## Tek cümle kural

| Ne? | Nereye? | Okuyan |
|---|---|---|
| **Gizli değer** (parola, `app_key`, jeton, API anahtarı) | `Secrets/` klasörü + `Secrets.php` | `Secrets` (TEK okuyucu) |
| **Ortam değişkeni / bayrak / kill-switch / yol** | `Env` + `EnvKeys` | `Env` (TEK okuyucu) |
| **Kod tanımlı yapılandırma** (sabitler, ayar tabloları) | `Config.php`, `Engine/Config/ConfigResolver.php`, `Engine/Config/ConfigFileLoader.php` | `ConfigResolver` / `ConfigFileLoader` (ortak dosya tekniği) |

### Klasör yapısı (PSR-4: dizin = ad alanı)

| Yol | Ad alanı |
|---|---|
| `Config.php`, `Env.php`, `Secrets.php` | `Rbn\Framework\Core\System\Config` |
| `Definitions/` (`EnvKeys`, `ConfigMap`, `SecretsSchema`) | `…\System\Config\Definitions` |
| `Definitions/DbProfiles/` (`CommonDbData`, `MasterDbData`, `ProjectDbData` — **yalnız sabit**, hiçbir sınıftan türemez) | `…\System\Config\Definitions\DbProfiles` |
| `Engine/Config/` (`ConfigResolver`, `ConfigFileLoader`, `ConfigFileGuard`) | `…\System\Config\Engine\Config` |
| `Engine/Database/` (`DatabaseConfig`, `DbProfileResolver`, `ProjectDbProfileResolver`, `SmtpProfileResolver`) | `…\System\Config\Engine\Database` |
| `Engine/Secrets/` (`SecretsLoader`, `SecretsValidator`, `SecretsSections`, `SecretsFlatApi`) | `…\System\Config\Engine\Secrets` |
| `Secrets/` (`secrets.php` — sir dosyasi, `.gitignore`'lu) | (veri, sinif degil) |

**Kural:** framework PSR-4 (`Rbn\Framework\` → repo kökü) yükleyici kullanır, classmap yok.
Bir sınıf taşındığında **ad alanı klasörüne birebir uymak zorundadır**; aksi hâlde
`Class "…" not found` ile HTTP 500 verir. Klasörü `Definitions` yazmak da bir ayrı
`not found` sebebidir — yazım hatası düzeltilmiştir.

`$_ENV` / `$_SERVER` / `getenv` **elle** okumak yasaktır — `Env` dışında hiçbir yerde bulunmaz (birim testi `E:\tmp\_araclar\fw-regresyon\birim\fw_env_kayit.php` kalıntı taramasıyla doğrular).

## Yeni anahtar eklemek = `EnvKeys`'e **bir satır**

Kodda `RBN_*` / `APP_*` adlı yeni bir ortam değişkeni okumak istiyorsan:

1. **`EnvKeys.php`'e bir `public const <AD> = '<ENV_ADI>';` satırı ekle** ve aynı adı `EnvKeys::ALL_KEYS` listesine de yaz. Okuma `Env` içinden yapılır; kayıtsız ad `RuntimeException` ile **fail-closed** reddedilir (sessizce "tanımsız" sayılmaz).
2. Değeri **gizliyse** (parola, `app_key`, jeton) adı ayrıca `EnvKeys::SECRET_KEYS` listesine de ekle: `Env` bu listeden anlar, **değeri** hiçbir mesajda yazılmaz.
3. **Sıra sabitini `getenv(` ile tutma.** Bu klasörde tek okuyucu `Env`; kaynak sırası `$_ENV` → `$_SERVER` → `getenv`.
4. **Kanıt şartı:** kaydedilen her ad için kodda **gerçekten** `Env::string()/flag()/int()` ile okunduğunu `rg -n "Env::" rbnframework projects` ile doğrula. Okunmayan (ya da `define()` sabiti olan) ad kaydedilmez.
5. `Env` içinde **ikinci bir ayrıştırıcı yazma.** `Env::flag()` bayrak yorumunu zaten TEK merkezden (`ShieldSettingsRepository::normalizeSwitch()`) alır: `0 \| false \| off \| no \| hayir` **kapalı**, diğer her şey **açık** (fail-closed).

## `Env` API'si

```php
Env::string('APP_ENV');                    // ?string  (tanımsız/boş -> null)
Env::string('APP_ENV', 'production');     // ?string  (varsayılanlı)
Env::flag('RBN_GUARD_FAILCLOSED', true);  // bool     (kill-switch: varsayılan true = fail-closed)
Env::int('TG_SEND_DELAY_MS', 0);          // ?int     (sayı olmayan metin -> varsayılan)
```

* **Tembel (lazy):** değer ilk okunduğunda bir kez çözülür. `Env::reset()` yalnız test içindir.
* **Kayıtsız ad = hata:** `EnvKeys::ALL_KEYS` içinde olmayan ad okunursa `RuntimeException`. Yazım hatası "ortam değişkeni yok" gibi görünmez.
* **Gizli ad:** hata mesajı yalnız **adı** ve nedeni taşır; **değeri** taşımaz. `Env::isSecretKey($ad)` listeyi sorgular.

## Kayıtlı adlar (kısa tablo)

Tam liste ve tek satırlık açıklamaları için `EnvKeys.php` (sabit dosyasıdır; **içinde metot yoktur**).

| Ad | Tur | Gizli | Kullanan |
|---|---|:--:|---|
| `APP_KEY` | string | evet | `CryptoHelper::resolveKey()` (sır dosyasındaki `app_key` **önce**) |
| `ENCRYPTION_KEY` | string | evet | `CryptoHelper::resolveKey()` (2. sıra) |
| `RBN_LEGACY_SALT` | string | evet | `CryptoHelper::legacySaltApproval()` |
| `RBN_ALLOW_LEGACY_SALT` | flag | hayır | `CryptoHelper::legacySaltApproval()` (eski `1\|true\|on\|yes` listesi, bilerek korunur) |
| `COMMON_DB_USER` / `DB_USER` | string | hayır | `DbProfileResolver::user()` (ortak / proje profili) |
| `COMMON_DB_PASS` / `DB_PASS` | string | **evet** | `DbProfileResolver::password()` (ortak / proje profili, fail-closed) |
| `RBN_GUARD_FAILCLOSED` | flag | hayır | `SystemGuardHandler::resolveFailClosed()` |
| `RBN_DEBUG` / `RBN_DEV` | flag | hayır | `PreBoot::envOverrideRequested()` (kapalı listesi `normalizeSwitch` DEĞİL, bilerek korunur) |
| `APP_ENV` | string | hayır | `AiUsageManager`, `DebugHelper`, `rbn` (CLI) |
| `TG_SEND_DELAY_MS` | int | hayır | Telegram test router'ı (yalnız test) |

### Neden bu listede *olmayan*lar?

* **`MASTER_DB_USER` / `MASTER_DB_PASS`** — FW-TEK-SECRETS-DOSYASI-161 sonrası
  master profilinin kullanıcı/parola çözümü **tamamen** `Secrets::masterDb()` okur;
  ortam değişkeni yolu kodda KALDIRILDI, kanıt yoktur.
* **`RBN_CLI`, `RBN_PANIC_ACTIVE`, `RBN_SESSION_TIMEOUT`** —
  bunlar PHP **sabitidir** (`define()` / `defined()`), ortam değişkeni DEĞİLDİR;
  `rbn`, `Watchdog`, `BootSentinel`, `SessionSandboxStage`,
  `ConfigMap` tarafından okunur. Ortam değişkeni kaydı **olamaz**.
  Bu kalemler `Definitions/ConfigMap.php` tarafındaki tanımlardır (bkz. aşağıdaki ayrım).

## Sır DEĞİL, kayma riski olan kalemler

Bu klasördeki **salt-okunur tanımlar** (varlık sabitleri, sınıf sabitleri, şablon adresleri) ortam değişkeni **değildir** ve `EnvKeys`'e yazılmaz: `HOST`, `CHARSET`, `ENV_FILE`, `APP_NAME`, `APP_VERSION`, `APP_URL`, `DEFAULT_LANGUAGE`, `AssetDefinition` içindeki `RBN_*_CSS` / `RBN_*_JS` varlık sabitleri, `ApiKeysRegistry` içindeki isim eşlemeleri.

### `EnvKeys` ≠ `ConfigMap` (patron kararı)

İkisi **farklı şeydir, ikisi de kalır, birleştirilmez ve silinmez**:

| | `Definitions/EnvKeys.php` | `Definitions/ConfigMap.php` |
|---|---|---|
| Konusu | Ortam değişkeni olarak **okunan** adların sabitleri | Framework'ün **global olarak tanımladığı** ayar anahtarları + PHP `define()` sabitleri |
| Örnek | `APP_ENV`, `TG_SEND_DELAY_MS` | `app.debug`, `app.logging`, `app.env`, `RBN_CLI`, `RBN_SESSION_TIMEOUT` (`getApp*` kalıbı) |
| Okuyan | `Env` (TEK kapı) | `ConfigMap::getAppDebug()` vb. |

## `Secrets.php` neden bu kadar kısa? (ince cephe)

`Secrets.php` **yalnız genel metot imzalarını** taşır; hiçbir imza değişmemiştir, çağıranlar kırılmaz. Kural: **sabitler ayrı dosya, metotlar ayrı dosya**, hiçbir dosya 250 satırı geçmez.

| Sorumluluk | Nerede? | Ne yapar? |
|---|---|---|
| Sır şeması | `Definitions/SecretsSchema.php` | **Yalnız `public const`** — alan adları, izin tavanı, `CHANGE_ME`. Metot YOK. |
| Yol çözümü + okuma | `Engine/Secrets/SecretsLoader.php` | `secrets.php`'yi bulur, izin/biçim denetiminden geçirir, **HAM** diziyi döndürür. TEK okuma kapısı. |
| Doğrulama | `Engine/Secrets/SecretsValidator.php` | Bölüm adı biçimi + "var ama okunamıyor" ayrımı. |
| Bölüm kurgusu | `Engine/Secrets/SecretsSections.php` | `master_db`/`smtp`/`cpanel`/`api` okuma + doğrulama, düz anahtar haritaları. |
| Düz anahtar yüzeyi | `Engine/Secrets/SecretsFlatApi.php` | Eski `all()/get()/optional()/masterDbPass()/masterSmtpPass()`. `Secrets` üzerinde `use` edildiği için **imzalar korunur**. |
| Ortak dosya tekniği | `Engine/Config/ConfigFileLoader.php` | bul → `require` → dizi/anahtar doğrula → tembel önbellek. |
| Güvenlik koruması | `Engine/Config/ConfigFileGuard.php` | 0600 izin tavanı + `<?php`/`return` sızıntı kontrolü + operatör yol/ipucu. |

**Neden `require_once` var?** Bu okuyucu kernel/bootstrap aşamasında çalışır; autoloader henüz hazır olmayabilir ve birim testleri `Secrets.php`'yi **tek başına** izole bir dizine kopyalıp `require` eder. Bu yüzden komsu dosyalar `__DIR__` ile göreli çözülür.

**Güvenlik sözleşmesi:** Hata mesajları **alan adını** söyler, **değeri asla yazmaz** (`secrets.php: master_db.user tanimli degil.`). Sessiz `root`/`''` fallback **yoktur**.

> Yeni sır okuyucusu yazarken: `ConfigFileLoader`/`ConfigFileGuard` trait'lerini `use` et, doğrulamayı `ConfigFileLoader::validate()`'a bırak — algoritmayı **kopya**ma. Bölüm kurallarını `SecretsSchema::SECTION_RULES`'e koy.

## `Definitions/DbProfiles/` neden metotsuz? (kim ne yapar)

Patron kuralı: **"sabitlerin olduğu dosyalarda METOT olmaz; sabitler ayrı dosya, metotlar ayrı dosya."**

| Sorumluluk | Nerede? | Ne yapar? |
|---|---|---|
| Veritabanı **şema** kimliği | `Definitions/DbProfiles/*.php` | **Yalnız `public const`** — `KEYS_MAP`, `REQUIRED_TABLES`, tablo listeleri, ön ekler. Metot YOK. |
| **Bağlantı** değerleri | `Engine/Database/DbProfileResolver.php` | `host()` · `port()` · `databaseName()` · `user()` · `password()` · `charset()` · `keysMap()` · `credentials()` |
| **SMTP** + gönderen kimliği | `Engine/Database/SmtpProfileResolver.php` | `enabled()` · `host()` · `port()` · `secure()` · `user()` · `pass()` · `fromAddress()` · `fromName()` |

Yeni bir profil eklerken: **sabitler** profile, **çözücü metotları** `DbProfileResolver`'a.
`KEYS_MAP` literal `public const` olduğu için `Definition::get('database_*','KEYS_MAP')`
metot yazmadan çalışır.

## İlgili belgeler

* Sır dosyası: `Secrets/README.md` · `Definitions/DbProfiles/README.md`
* Sürüm kaydı: `rbnframework/.github/CHANGELOG.md` → `[Unreleased]`
* Yükseltme adımı: `rbnframework/.github/UPGRADING.md`
