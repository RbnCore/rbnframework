# Core/System/Config — TEK KAPI Kuralı

> **Bu dosya neden var:** "Global anahtarlar **TEK** dosyada toplansın; herkes her yerde ayrı okuyup tanımlamasın." (Patron isteği, 2026-10-02)
> Bu klasördeki **her ayrı okuyucu** bir daha eklenmemek üzere burada sayılıyor.

## Tek cümle kural

| Ne? | Nereye? | Okuyan |
|---|---|---|
| **Gizli değer** (parola, `app_key`, jeton, API anahtarı) | `Secrets/` klasörü + `Secrets.php` | `Secrets` (TEK okuyucu) |
| **Ortam bayrağı / kill-switch / çalışma ayarı** | `secrets.php` → `app` bölümü | `Secrets::app()` (TEK okuyucu) |
| **Kod tanımlı yapılandırma** (sabitler, ayar tabloları) | `Config.php`, `Engine/Config/ConfigResolver.php`, `Engine/Config/ConfigFileLoader.php` | `ConfigResolver` / `ConfigFileLoader` (ortak dosya tekniği) |

### Klasör yapısı (PSR-4: dizin = ad alanı)

| Yol | Ad alanı |
|---|---|
| `Config.php`, `Secrets.php` | `Rbn\Framework\Core\System\Config` |
| `Definitions/` (`ConfigMap`, `SecretsSchema`) | `…\System\Config\Definitions` |
| `Definitions/DbProfiles/` (`CommonDbData`, `MasterDbData`, `ProjectDbData` — **yalnız sabit**, hiçbir sınıftan türemez) | `…\System\Config\Definitions\DbProfiles` |
| `Engine/Config/` (`ConfigResolver`, `ConfigFileLoader`, `ConfigFileGuard`) | `…\System\Config\Engine\Config` |
| `Engine/Database/` (`DatabaseConfig`, `DbProfileResolver`, `ProjectDbProfileResolver`, `SmtpProfileResolver`) | `…\System\Config\Engine\Database` |
| `Engine/Secrets/` (`SecretsLoader`, `SecretsValidator`, `SecretsSections`, `SecretsFlatApi`) | `…\System\Config\Engine\Secrets` |
| `Secrets/` (`secrets.php` — sir dosyasi, `.gitignore`'lu) | (veri, sinif degil) |

**Kural:** framework PSR-4 (`Rbn\Framework\` → repo kökü) yükleyici kullanır, classmap yok.
Bir sınıf taşındığında **ad alanı klasörüne birebir uymak zorundadır**; aksi hâlde
`Class "…" not found` ile HTTP 500 verir. Klasörü `Definitions` yazmak da bir ayrı
`not found` sebebidir — yazım hatası düzeltilmiştir.

## Ortam değişkeni YOKTUR (ADR · FW-096-D8 · 09.10.2026)

**Karar:** Framework işletim sistemi ortam değişkeni **okumaz**. `getenv()`, `$_ENV`,
`$_SERVER['APP_*'|'RBN_*'|…]`, dotenv dosyası ve web sunucusu ortam yönergeleri bu framework'ün
mekanizması **değildir**. Ortam bayrağı dahil tüm anahtarların TEK kaynağı
`Secrets/secrets.php` (bölümlü şema `Definitions/SecretsSchema.php`), TEK okuyucusu `Secrets`.

**Bağlam:** FW-ENV-KAYIT-160 ile `Env.php` + `EnvKeys.php` (16 ad) paralel bir katman olarak
büyümüştü; canlıda hiçbiri tanımlı değildi (cPanel PHP ortam ayarı kapalı, dotenv dosyası yok),
yani bu yol fiilen hiç çalışmıyordu. Patron: "Bizde env yok; bütün anahtarları TEK dosyadan
merkezi yazıyoruz."

**Sonuç:** `Env.php` ve `EnvKeys.php` silindi. Sır niteliğindekiler zaten sır bölümlerindeydi
(`app_key`, `master_db`), OS-env yedek yolları kaldırıldı. Sır olmayan çalışma anahtarları yeni,
**tamamen opsiyonel** `app` bölümüne taşındı:

| Eski ortam değişkeni | Yeni yer | Tip · güvenli varsayılan | Okuyan |
|---|---|---|---|
| `APP_ENV`, `RBN_ENV` (takma ad) | `app.environment` | `'production'` \| `'development'` · **production** (+ bir kez log) | `PreBoot::isProductionDeclared()` |
| `RBN_DEBUG`, `RBN_DEV` | `app.debug`, `app.dev` | `bool` · `false` | `PreBoot::detectEnvironment()` |
| `RBN_GUARD_FAILCLOSED` | `app.guard_failclosed` | `bool` · `true` | `SystemGuardHandler::resolveFailClosed()` |
| `RBN_LOG_THROTTLE` | `app.log_throttle` | `bool` · `true` | `LogThrottle::enabled()` |
| `RBN_DB_PROFILE` | `app.db_profile` | `''` \| `'local'` \| `'production'` · `''` (otomatik) | `ProjectDbProfileResolver::activeProfile()` |
| `TG_SEND_DELAY_MS` | `app.tg_send_delay_ms` | `int` · `0` (yalnız test) | sroweb Telegram test router'ı |
| `RBN_ALLOW_LEGACY_SALT` | `app.allow_legacy_salt` | `bool` · `false` | `CryptoHelper::legacySaltApproval()` |
| `RBN_LEGACY_SALT` | üst düzey `legacy_salt` (sır) | metin · yok | `Secrets::legacySalt()` |
| `APP_KEY`, `ENCRYPTION_KEY` | üst düzey `app_key` (zaten vardı) | — | `CryptoHelper` → `Secrets::optional('app_key')` |
| `COMMON_DB_USER/PASS` | `master_db` (zaten vardı) | — | `DbProfileResolver` → `Secrets::masterDb()` |
| `DB_USER/PASS` | proje `project-settings.php`; yedek `db_user`/`db_pass` | — | `DbProfileResolver` → `Secrets::optional()` |

**Kurallar:**

* Değer tipi PHP tipidir (`true`/`false`, tam sayı, metin). Tipi tutmayan ya da izinli listede
  olmayan değer **güvenli varsayılana** düşer ve loglanır — metin yorumlayan ikinci bir
  "kapalı listesi" yazılmaz (`'banal'` hata ayıklamayı açmaz, `'staging'` üretimi gevşetmez).
* `app.environment` yoksa **production** kabul edilir ve süreç başına bir kez
  `RBN Uyari: secrets.php: app.environment tanimli degil; production kabul edildi.` loglanır.
* PreBoot en erken aşamadır; `Secrets.php` komşularını kendisi `require_once` ettiği için
  autoloader'sız okunur. Paralel ikinci ayar dosyası **yoktur**. Dosya var ama okunamazsa
  PreBoot üretim tarafına düşer ve nedeni loglar.
* **Tek bootstrap istisnası yoktur:** framework ağacında ortam değişkeni okuyan satır kalmadı.
  (Test/koşucu araçları — `E:\AgentSpace\.agentspaceraclarw-regresyon` — framework değildir.)
* Yeni çalışma anahtarı = `SecretsSchema::APP_DEFAULTS`'a bir satır (+ gerekiyorsa
  `APP_ALLOWED`) + `secrets.example.php`'ye örnek. Başka dosya açılmaz.

### `ConfigMap` ayrımı

`Definitions/ConfigMap.php` framework'ün PHP `define()` sabitlerinden türeyen getter'larıdır
(`getAppDebug()` → `RBN_DEBUG` sabiti, `getAppIsCli()` → `RBN_CLI` sabiti). Bu sabitler ortam
değişkeni **değildir**; `RBN_DEBUG`/`RBN_DEV` sabitlerini `PreBoot::detectEnvironment()`
yukarıdaki `app` ayarlarından türeterek tanımlar.

## `Secrets.php` neden bu kadar kısa? (ince cephe)

`Secrets.php` **yalnız genel metot imzalarını** taşır; hiçbir imza değişmemiştir, çağıranlar kırılmaz. Kural: **sabitler ayrı dosya, metotlar ayrı dosya**, hiçbir dosya 250 satırı geçmez.

| Sorumluluk | Nerede? | Ne yapar? |
|---|---|---|
| Sır şeması | `Definitions/SecretsSchema.php` | **Yalnız `public const`** — alan adları, izin tavanı, `CHANGE_ME`. Metot YOK. |
| Yol çözümü + okuma | `Engine/Secrets/SecretsLoader.php` | `secrets.php`'yi bulur, izin/biçim denetiminden geçirir, **HAM** diziyi döndürür. TEK okuma kapısı. |
| Doğrulama | `Engine/Secrets/SecretsValidator.php` | Bölüm adı biçimi + "var ama okunamıyor" ayrımı. |
| Bölüm kurgusu | `Engine/Secrets/SecretsSections.php` | `master_db`/`smtp`/`cpanel`/`api`/`app` okuma + doğrulama, düz anahtar haritaları. |
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
