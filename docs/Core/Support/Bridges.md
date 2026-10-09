# Core/Support/Bridges — Yardımcı kütüphaneler, global işlevler, vekil ve ortak özellikler

> **Doğrulanan kod tabanı:** `d508f5e1` (dal `feat/fw-license-master`) · **Tarih:** 2026-10-05 · **Yayın:** 0.9.6 = bu commit + sonrası; belge yalnız doğrulama anındaki kodu anlatır
> **Kaynak klasör:** `Core/Support/Bridges/` — 32 `*.php`: `Helpers/rbn_helpers.php` 1, `Helpers/Global/` 4, `Helpers/Library/` 15, `Helpers/Library/Icons/` 2 + `Icons/Internal/` 7, `Proxies/` 1, `Traits/` 2.
> **Envanter:** 32 dosyanın 32'si aşağıda anlatıldı.

## 1. Ne işe yarar, kim kullanır

"Köprü" katmanıdır: framework'ün her yerinden çağrılan küçük, durumsuz yardımcılar. Üç biçimi var:

1. **Global işlevler** (`Helpers/Global/*.php`): `url()`, `project_key()`, `request()`… Şablonlarda ve her katmanda çağrılır. `Paths::init()` içinde `rbn_helpers.php` yüklenir (`Core/System/Paths/Paths.php:50`), o da dört dosyayı `require_once` eder.
2. **Yardımcı sınıflar** (`Helpers/Library/*`): `CryptoHelper`, `FormatHelper`, `TextHelper`… `service('…')` ile değil, çoğunlukla `helper('<ad>')` (kayıt adları [Registries](../System/Registries.md) `resource.helpers` bölümünde) ya da doğrudan sınıf adıyla çağrılır.
3. **Vekil ve özellikler** (`Proxies/`, `Traits/`): `AssetProxy` (`$asset->framework->css(...)`), `NormalizationTrait` (ad dönüşümleri), `ViewHelperTrait` (CSRF alanları, medya URL'si).

**Kimler çağırır:** şablonlar (`.rbn.php`), denetleyici/servis/model taban sınıfları, `Paths`, `PreBoot` (`ProjectVersionResolver`, `LogThrottle`, `Version` erken `require_once` ile), Storage (`CryptoHelper` önbellek şifrelemesi).

## 2. Dosya envanteri

### 2.1 Giriş ve global işlevler

| Dosya | İçerik |
|---|---|
| `Helpers/rbn_helpers.php` | Dört global işlev dosyasını sırayla `require_once` eder: `system_helpers`, `http_helpers`, `support_helpers`, `project_helpers`. |
| `Helpers/Global/system_helpers.php` | `shield(): Shield`, `now(string $format='Y-m-d H:i:s', ?int $timestamp=null): string`, `is_local(): bool`. |
| `Helpers/Global/http_helpers.php` | `old(string $key, $default=null)`, `response(): Response`, `request(): Request`, `alert(): AlertService`. |
| `Helpers/Global/support_helpers.php` | `collect($items=[])`, `path_symmetric(string)`, `path_candidates(string)`, `next_version`, `version_is_valid`, `version_compare`\*, `version_parse`, `version_initial`. (\*bkz. §5.2) |
| `Helpers/Global/project_helpers.php` | `project_data(?string $key=null, mixed $default=null)`, `project_id(): int`, `project_key(): string`, `active_project_key(): string`, `project_group(): string`, `group_projects(): array`, `app_name(): string`, `app_version(): string`, `site_domain(): string`, `url(string $path=''): string`. |

### 2.2 `Helpers/Library/` — yardımcı sınıflar (ad alanı `…\Bridges\Helpers\Library`)

| Dosya | Görev | Önemli yöntemler |
|---|---|---|
| `CryptoHelper.php` | AES-256-CBC şifreleme + bcrypt + jeton. | `static encrypt(string $data, ?string $key=null): string`, `decrypt(string, ?string): ?string`, `hash(string): string`, `verify(string,string): bool`, `needsRehash(string): bool`, `blindIndex(string): string`, `generateToken(int $length=32): string`, `hashToken(string): string` |
| `FormatHelper.php` | Biçimlendirme: para, tarih, telefon, dosya boyutu, UA ayrıştırma. | `currencyTL`, `formatCurrency`, `formatDateTurkish`, `getRelativeTime`, `formatPhone`, `formatWhatsapp`, `formatFileSize`, `formatPercentage`, `estimatedReadTime`, `seoCleanText`, `weekdaysMap`, `dateRange`, `periodLabel`, `parseUserAgent` |
| `TextHelper.php` | Metin: kısaltma, slug, harf dönüşümü, baş harfler. | `truncate`, `cutText`, `turkishSlug`, `toEnglishAlphabet`, `getInitials`, `nameTitleCase`; sabitler `TURKISH_CHARS`, `ENGLISH_CHARS` |
| `MetaSeoHelper.php` | SEO üst verisi (`BaseComponent`'ten). | `seoSlug(string $title, int $maxWords=6)`, `autonomousSeo(string $title, int $limit=70): array`, `generateMetaDescription(string $text, int $length=160)` |
| `DataHelper.php` | `Resources/Data/<ad>.json` okuyup arar. | `get(string $fileName, ?string $searchValue=null, string $searchKey='id', ?string $returnKey=null)` |
| `GeoHelper.php` | `Resources/Data/Locations/tr-locations.json`'dan ülke/il/ilçe. | `countries()`, `countryFlags()`, `trCities()`, `trDistricts(string $city)` |
| `DataSweepHelper.php` | JSON/metin temizleme, alan adı çıkarma, base64url. | `cleanJson`, `decodeJson`, `sweepText`, `extractDomain`, `encodeJson`, `sweepTemplateUrls`, `base64UrlEncode`, `base64UrlDecode` |
| `PathHelper.php` | Yol simetrisi (`toSymmetric`) ve fiziksel aday yollar. | `toSymmetric(string)`, `getPhysicalCandidates(string $realPath)`, `cleanProxy(string)`, `isFramework(string)` |
| `ColorHelper.php` | Renk dönüşümü ve tema stili. | `hexToHsl`, `hslToHex`, `generateThemeStyles(string $primaryHex, ?string $secondaryHex=null)` |
| `DebugHelper.php` | Hata ayıklama çıktısı. | `prePrint($data, bool $die=false, string $title='')`, `dd`, `dump` |
| `RenderFieldHelper.php` | Ayar/alan formu HTML üretimi. | `renderSetting`, `renderField`, `parseOptions`, `buildInput` |
| `IconLibrary.php` | İkon kütüphanesi girişi (`BOOTSTRAP`, `FONTAWESOME`, `ALL`). | `all(): IconCollection`, `category(string)`, `search(string)`, `match(string $keyword, string $default='bi-circle'): string` |
| `Version.php` | Sürüm `A.B.C` kuralı (statik). | `isValid`, `parse`, `next`, `compare`, `initial`; sabitler `PATTERN`, `INITIAL='0.1.1'` |
| `ProjectVersionResolver.php` | Proje sürümünün tek çözücüsü. | `static resolve(mixed $raw): string`, `static resolveFromCache(mixed $projectData=null): string` |
| `LogThrottle.php` | Tanılama günlüğü için saatlik kapı. | `static once(string $key, int $ttlSeconds=3600): bool`, `setClockOverride(?int)`, `reset(?string $key=null)` |

### 2.3 `Helpers/Library/Icons/`

| Dosya | İçerik |
|---|---|
| `IconCollection.php` | İkon listesi üzerinde akıcı API: `limit`, `random`, `except`, `search`, `toArray`, `toSelect`, `toOptionsHtml`, `toJson`. |
| `SmartIconCategories.php` | Bağlama göre ikon kümeleri: `getAdminIcons`, `getDeveloperIcons`, `getPopularIcons`, `getSidebarCategoryIcons`, `getSidebarMenuIcons`, `getFormActionIcons`, `getStatusIcons`, `getRecommendedIcons(string $useCase)`, `getContextualIcons(string $context, string $action='')`, `matchKeyword(string)` (tümü `static`). |
| `Internal/bootstrap_icons.php` | Veri: 463 üst düzey girdi (`return [...]`). |
| `Internal/font_awesome.php` | Veri: 3 üst düzey kategori. |
| `Internal/devicons.php` | Veri: 3 üst düzey kategori. |
| `Internal/payment_icons.php` | Veri: 4. |
| `Internal/social_brands.php` | Veri: 21. |
| `Internal/tabler_icons.php` | Veri: 1. |
| `Internal/unicode_emojis.php` | Veri: 6. |

### 2.4 `Proxies/` ve `Traits/`

| Dosya | Görev | Yöntemler |
|---|---|---|
| `Proxies/AssetProxy.php` (`BaseProxy`) | Kapsamlı varlık URL'si üretir: `$asset->framework->css('x.css')`. | `__construct(string $type, string $scope='project')`, `__get(string $name)` (kapsam değiştirir), `__call(string $name, array $args): string` (`<tür>('<dosya>')` → sürümlü URL) |
| `Traits/NormalizationTrait.php` | Ad/yol dönüşümleri. | `static toCamelCase`, `toPascalCase`, `toPascalPath`, `toCaseSafePath`; `normalize`, `sanitizeFolderName`, `sanitizeFileName(string $filename, ?string $extension=null, bool $withTimestamp=false)`, `generateUniqueName(?string $prefix=null, ?string $extension=null)` |
| `Traits/ViewHelperTrait.php` | Şablon yardımcıları. | `csrfToken()`, `csrfField()`, `csrfMeta()`, `methodField(string $method)`, `static mediaUrl(?string $url, ?string $category=null, string $size='w500', string $fallback='/images/default.png')`, `static normalizeHtmlUrls(string $html, ?string $targetBaseUrl=null, array $proxyConfig=[])` |

## 3. Akış

### 3.1 Şifreleme anahtarı (`CryptoHelper::resolveKey`, `:129-171`)

Öncelik: (1) çağıranın verdiği `$key`; (2) sır dosyası `Secrets::optional('app_key')` (ortam değişkeni yolu yok, FW-096-D8); (3) yalnız `secrets.php` `app.allow_legacy_salt === true` **ve** üst düzey `legacy_salt` doluysa eski türetme; hiçbiri yoksa `RuntimeException` (gömülü/sabit tuz **yok**). Anahtar = `substr(hash('sha256', <ham>), 0, 32)`.
`encrypt()`: rastgele IV (`openssl_random_pseudo_bytes`), `openssl_encrypt(…, 'aes-256-cbc', $key, 0, $iv)`, çıktı `base64_encode($iv . $şifreli)`. `decrypt()`: IV uzunluğundan kısa girdi, boş girdi ya da hata → `null`.

### 3.2 `LogThrottle::once($anahtar, $ttl=3600)` (`LogThrottle.php:98-152`)

Aynı anahtar için TTL içinde yalnız ilk çağrıya `true`. Depo sırası: APCu `apcu_add`; yoksa `Storage/cache/log-throttle/<sha256>.throttle` dosyası (`flock`); ikisi de yoksa **`false`** (günlük yazılmaz, istek bozulmaz). İstisna fırlatmaz. `RBN_LOG_THROTTLE=0|false|off|no|hayir` kapıyı açar (her çağrı `true`).

### 3.3 Sürüm kuralı (`Version`)

Desen `^(0|[1-9][0-9]*)\.[0-9]\.[0-9]$`: **A** sınırsız, **B ve C yalnız tek hane (0–9)**. `next()`: C<9 → C+1; C=9, B<9 → B+1, C=0; ikisi de 9 → A+1, B=C=0 (`0.9.9 → 1.0.0`, `9.9.9 → 10.0.0`). `ProjectVersionResolver::resolve()` geçersiz/boş değerde `0.1.1` verir ve geçersiz dolu değerde `LogThrottle` ile **saatte bir** `error_log` yazar.

### 3.4 Global `project_*` işlevleri (`project_helpers.php`)

Hepsi `Bootstrap::getAppContext('project_data')` üstündendir. `project_data('group_projects')` özeldir: `project_group` (yoksa `project_key`) adıyla `BootCacheProvider::get($grup, null, 'group_')` okur (yoksa `[]`). `active_project_key()` önce `Bootstrap::getAppContext('project_key')`, yoksa proje verisi, yoksa `'default'` verir; `?` sonrası atılır. `url($yol)` taban adresi `Paths::project()->baseUrl()`'dan alır, `http(s)://` ya da taban ile başlayan yolu aynen döndürür.

### 3.5 `AssetProxy::__call` (`AssetProxy.php:54-73`)

`$asset->css('a.css')`: ilk argüman dosyadır (boşsa `''`); kapsam anahtarı `AssetConfig::PROXY_SETUP[$kapsam]['token']` (`@project/`, `@fw/`) dosyanın önüne eklenir; `cluster('asset')->resolveUrl($yol, $tür, AssetConfig::VERSION)` sürümlü URL'yi verir.

## 4. Yapılandırma

| Ayar | Değer | Kaynak |
|---|---|---|
| Şifreleme algoritması | `aes-256-cbc` | `CryptoHelper.php` (`$method`) |
| Şifre özeti | `PASSWORD_BCRYPT` | `CryptoHelper::hash` |
| `LogThrottle` TTL | 3600 sn | `LogThrottle.php:64` |
| `LogThrottle` dizini | `<proje>/Storage/cache/log-throttle` | `LogThrottle.php:61` |
| Başlangıç sürümü | `0.1.1` | `Version::INITIAL` |
| Veri klasörü | `<framework>/Resources/Data` (`DataHelper`), `Resources/Data/Locations/tr-locations.json` (`GeoHelper`) | ilgili sınıflar |

## 5. Tuzaklar ve kurallar

1. **`CryptoHelper` doğrulamasız CBC kullanır:** şifreli metne MAC/HMAC eklenmez; `decrypt()` yalnız `openssl_decrypt` sonucuna bakar. Anahtar, `sha256` onaltılık özetinin ilk 32 karakteridir (32 baytlık ASCII dize). Önbellek dosyaları (`CacheProvider`) bu yolla "şifreli" tutulur; **gizlilik** sağlar, **bütünlük** sağlamaz. Değişiklik (kimlik doğrulamalı mod, anahtar türetme) mevcut şifreli verinin okunamamasına yol açar; bu yüzden bir **kod kararı** gerektirir ve bu belge salt okunurdur.
2. **Global `version_compare()` hiç tanımlanmaz:** `support_helpers.php:62-69` `if (!function_exists('version_compare'))` kapısı içindedir, ama PHP'nin yerleşik `version_compare` işlevi vardır (`ReflectionFunction::isInternal() = true`, PHP 8.3 ile doğrulandı); blok ölü koddur. Çatı karşılaştırması için `Version::compare()` kullanılmalıdır (yerleşik `version_compare` `A.B.C` tek-hane kuralını bilmez).
3. **`LogThrottle` `Core/System/Storage/` içinde değildir** ve bilerek orada durmaz (kalıntı taraması `Storage` klasörlerini hariç tutar). Başka belgelerdeki "Storage/LogThrottle.php" ifadesi eskidir.
4. **`LogThrottle` fail-closed:** depolama kullanılamıyorsa `false` döner ve kayıt **hiç yazılmaz**; tanılama satırlarının yazılmaması bir hata değil tasarımdır.
5. **`Version` B ve C için tek hane ister:** `0.10.0` geçersizdir; `next()` 9'dan sonra üst basamağa taşar.
6. **`DataHelper::get($fileName)` dosya adını `ltrim($fileName,'/\\')` ile temizler ama `..` segmentlerini süzmez** (`DataHelper.php` yol birleşimi `…/Resources/Data/<ad>.json`); yalnız `.json` uzantılı dosya okunur, ad çağıranın güvenilir sabitinden gelmelidir.
7. **`GeoHelper`/`DataHelper` dosyayı her çağrıda okur (`DataHelper`) ya da nesne başına önbellekler (`GeoHelper::$locationData`)**; sık çağrılan yerde tek örnek kullanın.
8. **`ViewHelperTrait::csrfField()` jeton değerini HTML özniteliğine kaçışsız yazar** (`csrfToken()` → `service('form')->token()['token']`); jeton hex/rastgele üretildiği sürece güvenlidir, ama başka bir kaynaktan gelirse kaçışlanmalıdır.
9. **`active_project_key()` ile `project_key()` farklıdır:** `project_key()` yalnız çözülmüş proje verisinden (`''` olabilir); `active_project_key()` yönetim panelinde seçili projeyi (`Bootstrap` bağlamı) ya da `'default'`'u verir.
10. **`is_local()` bir muafiyet kapısıdır** (robots, IndexNow, cron e-postası, trafik): HTTP'de `PreBoot::isTrustedLocalEnvironment(host, REMOTE_ADDR)`, CLI'da yalnız sunucu yolları (`DOCUMENT_ROOT`, dosya konumu, `getcwd()`) içinde tam `localhost` segmenti. Ölçüm tablosu: [Config §5](../System/Config.md).
11. **`Icons/Internal/*.php` veri dosyalarıdır** (`return [...]`, sınıf içermez). `IconLibrary::all()` yalnız `bootstrap_icons.php` ve `font_awesome.php` dosyalarını `require` eder (`IconLibrary.php:34-35`); `devicons`, `payment_icons`, `social_brands`, `tabler_icons`, `unicode_emojis` dosyalarını `Core/Bundles/Packages/Resources` içinde **yükleyen kod yoktur** (ad taraması, kendi dosyaları hariç).

## 6. Örnek (gerçek koddan)

```php
// Core/Support/Bridges/Helpers/Library/ProjectVersionResolver.php:41-55 (özet)
$metin = is_string($raw) ? trim($raw) : '';
if ($metin !== '' && Version::isValid($metin)) { return $metin; }
return Version::initial();   // '0.1.1'
```

```php
// Core/System/Kernel/Stages/ComponentRegistry.php:117
if (!LogThrottle::once($tag . ':' . static::class)) { return; }
```

## 7. İlgili belgeler

* [Core/Support genel bakış](README.md) · [Definitions](Definitions.md) · [System/Config](../System/Config.md) · [System/Storage](../System/Storage.md) · [System/Registries](../System/Registries.md)
* [Kavram: sürümleme ve yayın](../../kavramlar/04-surumleme-ve-yayin.md) · [Açık sorular](../../acik-sorular.md)
