# Core/Support/Definitions — Çatı tanım sabitleri (ad alanı, klasör, rota, varlık, kimlik)

> **Doğrulanan kod tabanı:** `d508f5e1` (dal `feat/fw-license-master`) · **Tarih:** 2026-10-05 · **Yayın:** 0.9.6 = bu commit + sonrası; belge yalnız doğrulama anındaki kodu anlatır
> **Kaynak klasör:** `Core/Support/Definitions/` — 10 `*.php` (`Render/` 4, `Route/` 2, `System/` 4).
> **Envanter:** 10 dosyanın 10'u aşağıda anlatıldı. Sabit sayıları `ReflectionClass` ile ölçüldü (PHP 8.3, Windows).

## 1. Ne işe yarar, kim kullanır

Çatının "değişmez tabloları"dır: ad alanı haritası, klasör matrisi, bileşen sonek tablosu, rota planı, varlık (CSS/JS/yazı tipi) paketleri, marka kimliği. İş mantığı yoktur; iki-üç küçük statik yardımcı hariç yalnız `const` bulunur. `DefinitionResolver` bu klasörü baştan sona tarar ve her sınıfı **kategori adıyla** `Definition::get('<kategori>', '<ANAHTAR>')` çağrısına açar ([Discovery §3.3](../System/Discovery.md)). Kategori, sınıfın `getDefinitionCategory()` yöntemi ya da `$definitionCategory` özelliğiyle verilir; yoksa dosya adından türetilir (`Map`, `Definitions`, `Blueprint` sözcükleri atılır).

| Sınıf | Kategori | Kimler okur |
|---|---|---|
| `NamespaceMap` | `namespace` | `NamespaceResolver`, `Autoload`, `FolderContext`, `ModuleContext`, `ModuleDiscoveryDriver` |
| `FolderMatrix` | `folder` | `FolderContext`/`Paths`, `PermissionDoctor` |
| `RouteBlueprint` | `route` | `Route::loadRoutes()`, `SessionSandboxStage`, `TrafficProvider`, `Dispatcher` |
| `ComponentTypes` | (dosya adı) | `ComponentContext`, `DiscoveryConfigTrait`, `AppProjectRegistry` |
| `FrameworkIdentity` | `identity` (ama bkz. §5.2) | `RbnSystemInfo::get()` (doğrudan sınıf sabiti) |
| Varlık sınıfları (`AssetDefinition`, `AssetBundles`, `AssetFonts`, `AssetConvention`) | dosya adından | Render katmanı (`AssetBuilder`, `SeoResolver`, `AssetController`, `RedirectManager`, `SchemaResolver`) |

## 2. Dosya envanteri

### 2.1 `System/`

| Dosya | İçerik | Yöntemler |
|---|---|---|
| `NamespaceMap.php` (`BaseConfig`) | `FRAMEWORK_PREFIX='Rbn\Framework\'`, `PROJECT_PREFIX='Rbn\Project\'`, `COMPONENT_REGISTRY`, `MAP` (26 giriş: `Framework`, `Framework.Core`, `Base`, `Database`, `Support`, `Helpers`, `Services`, `Http`, `Render`, `Routes`, `System`, `Internal`, `Suite`, `RbnSuite`, `RbnAdmin`, `RbnAuth`, `RbnStudio`, `Packages`, `App`, `Project`, `Project.App`, `Project.Core`, `Modules`, `Project.Modules`, `Backend`, `Frontend`). | — |
| `FolderMatrix.php` (`BaseConfig`) | `FRAMEWORK` (5 kök), `PROJECT` (6 kök), `RUNTIME_LAYERS` (11), `CODE_LAYERS` (16); kategori `folder`. | `getDefinitionCategory()`, `isRuntimeLayer(string)`, `isCodeLayer(string)` |
| `ComponentTypes.php` | `MAP` (22: sonek → tür; `Handler→handler`, `Validator→validation` …), `PLURAL_MAP` (22: tekil → çoğul kayıt anahtarı; `repository→repositories`, `metadata→metadata`). | `static typeMap(): array`, `static pluralize(string $type): string` (bilinmeyen tür: küçük harf + `s`) |
| `FrameworkIdentity.php` (`BaseConfig`) | 26 skaler sabit: ad, sürüm (`FRAMEWORK_VERSION='0.9.6'`), URL'ler (`https://example.tr`, `https://cdn.example.tr/`), geliştirici bilgisi, depo/sorun/güvenlik bağlantıları, `FRAMEWORK_CLI_VERSION='2.3.0'`, `SHIELD_*`, `ADMIN_*`, `AUTH_*`. | — |

### 2.2 `Route/`

| Dosya | İçerik |
|---|---|
| `RouteBlueprint.php` (`BaseConfig`, kategori `route`) | `MIDDLEWARE` (`aliases`: `auth`, `guard` → `AuthMiddleware`, `machine-api` → `ApiGuard`; `groups`: `user`, `admin`, `superadmin`, `developer`), `FILES` (`framework`: `core`, `auth`, `web`; `project`: **boş**), `PANELS` (5: `user, admin, developer, guest, frontend`), `AUTH_ROOTS` (8: `rbn-admin, register, auth, logout, lockscreen, forgot-password, verify-code, reset-password`), `LOGIN_ALIAS` (`giris`; panel kapalıyken yönlendirilen giriş takma adı, `AUTH_ROOTS` dışındadır), `CORE_MODULES` (`dashboard`, `RbnAdmin`), `DASHBOARD_PREFIX='dashboard'`, `LOGIN_PATH='rbn-admin'`, `SYSTEM_ALLOWED_PATHS` (4), `HONEYPOT_PATHS` (35), `HONEYPOT_ROUTE_EXEMPT` (5), `HONEYPOT_KEYWORDS` (12), `LEGACY_FONTS_KEYWORDS` (3), `FALLBACK_FAVICONS` (6), `FALLBACK_MOBILE_PREFIXES` (2), `FALLBACK_MOBILE_MANIFESTS` (2), `FALLBACK_OG_IMAGES` (8), `LEGAL_ALIASES` (17: `kvkk, iptal-iade, … sozlesme`). |
| `StrategicRouteMap.php` | `STRATEGIC_ACTIONS` (7): `index` GET `/`, `modal` GET `modal/?([0-9]*)`, `update` POST `save`, `delete` POST `delete/([0-9]+)`, `destroy` POST `destroy/([0-9]+)`, `status` POST `toggle`, `bulkOrder` POST `reorder` — "sıfır kod" rota eşlemesi. |

### 2.3 `Render/`

| Dosya | İçerik | Yöntemler |
|---|---|---|
| `AssetDefinition.php` | 35 skaler sabit: CDN URL'leri (Bootstrap 5.3.2, Font Awesome 6.5.1, Bootstrap Icons 1.11.3, Remix Icon 4.2.0, flag-icons 6.6.6, jQuery 3.7.1 `['path'=>…, 'renderInHead'=>true]`, Sortable 1.15.2, AOS 2.3.4) ve çatı varlık takma yolları (`@fw/RbnAdmin/css/…`, `@fw/RbnCommon/css/…`). | — |
| `AssetBundles.php` | `STACK_MAP` (4: `universal`, `frontend`, `panel`, `auth`), `BUNDLES` (24 paket: `rbn_master_js`, `fonts_panel`, `fonts_auth`, `bootstrap`, `font_awesome`, `bootstrap_icons`, `remix_icon`, `flag_icon`, `jquery`, `admin_core_css`, `rbn_core_frontend`, `rbn_core_panel`, `rbn_core_auth`, `rbnExtended`, `rbnModal`, `rbnDashboard`, `rbnCharts`, `rbnTable`, `sortable`, `aos`, `rbn_master`, `security`, `project`, `frontend`). | — |
| `AssetFonts.php` | `FONT_LIBRARY` (33 yazı tipi; ad → Google Fonts tanımı) + 16 ad sabiti (`INTER`, `OUTFIT`, `GEIST`, …). | — |
| `AssetConvention.php` | Favicon/OG görseli adlandırma kuralı: `favicon-<project_key>.{svg,png,ico}`, `og-image-<project_key>.{png,jpg,webp}`, `apple-touch-icon-<project_key>.png`, `manifest-<project_key>.{webmanifest,json}` (`images/` altında); sanal OG adı deseni `og-image-<ad>.<uzantı>`. | `faviconCandidates(?string)`, `ogImageCandidates(?string)`, `findFavicon(?string)`, `findOgImage(?string)`, `findAppleTouchIcon(?string)`, `findManifest(?string)`, `resolveVirtualOgImage(string)`, `isVirtualOgImageName(string)`, `ogImageMimeType(string)` (hepsi `static`) |

## 3. Akış

### 3.1 Ad alanı tamamlama

`NamespaceResolver::find()` registry'den kısa ad (`Core\Services\…`) aldığında `NamespaceExpansionTrait::expandRegistryResult()` önce `Definition::get('namespace','FRAMEWORK_PREFIX')` ile, sonra `PROJECT_PREFIX` ile öneki ekleyip `class_exists` dener. `resolveCoreLayer()` `Definition::get('namespace','MAP')` içindeki her taban ad alanını sırayla dener.

### 3.2 Klasör yolu

`FolderContext::path('FRAMEWORK.CORE.SYSTEM')` ağaçta noktalı anahtarı yürür; her düğümde `['folder'=>…]` varsa o ad, yoksa anahtarın kendisi ya da değeri yola eklenir; **matriste olmayan parça ham eklenir** (ayrıntı ve ölçüm: [Paths §5](../System/Paths.md)).

### 3.3 Favicon/OG görseli (`AssetConvention`)

Dört kapı (meta etiketleri `SeoResolver`, sanal varlık sunumu `AssetController`, `/favicon.ico` yönlendirmesi `RedirectManager`, JSON-LD `image` `SchemaResolver`) aynı aday sırasını bu sınıftan alır: önce proje anahtarlı ad, sonra anahtarsız eski adlar ("son geri dönüş").

## 4. Yapılandırma

Bu klasördeki tüm değerler sabittir. `RouteBlueprint::FILES['project']` boş olduğundan proje rota dosyası **otomatik yüklenmez**; yüklenecek dosya adları buraya eklenir ve `Paths::project()->routes()` altında aranır ([Discovery §5.10](../System/Discovery.md)).

## 5. Tuzaklar ve kurallar

1. **`NamespaceMap::COMPONENT_REGISTRY` var olmayan bir sınıfı gösterir:** `\Rbn\Framework\Core\System\Registries\ComponentRegistry::class` (`NamespaceMap.php:31`); `class_exists` `false` döner — gerçek sınıf `Core\System\Kernel\Stages\ComponentRegistry`'dir. Sabitin `Core/Bundles/Packages` altında başka okuyucusu yoktur (tek eşleşme tanımın kendisidir), bu yüzden bugün zarar vermez; ama okunursa yanlış sınıfa gider.
2. **`identity` kategorisi `Definition::get` içinde yakalanır:** `FrameworkIdentity` `$definitionCategory = 'identity'` taşır, ama `Definition::get('identity', …)` çözücüye gitmeden `AssetConfig::PROXY_SETUP`'tan yalnız `PROXY_PATHS`/`PROXY_TOKENS` döndürür, diğer her anahtar için `null` verir (`Definition.php:54-66`). Marka sabitlerine `Definition::get` ile değil `RbnSystemInfo::get()` ya da doğrudan `FrameworkIdentity::<SABİT>` ile ulaşılır. Genel kural: kategori adı açık verilmediyse dosya adından türer (`DefinitionResolver.php:117`); dosya adı değişirse `Definition::get` çağrıları `LogicException` verir.
3. **`DefinitionResolver` `Core/Support/Definitions` altındaki tüm `*.php` dosyalarını `require_once` eder**; bu klasöre yan etkili (kod çalıştıran) dosya konmamalıdır.
4. **`NamespaceMap::MAP` anahtarları aynı hedefe birden çok ad verir** (`Suite` ve `RbnSuite`; `Modules` ve `Project.Modules`; `App` ve `Project.App`); `resolveCoreLayer()` ilk bulunanı kullanır.
5. **`ComponentTypes::MAP` sonekten türe** (22 giriş), `PLURAL_MAP` türden kayıt anahtarına (22 giriş) gider; iki tablonun anahtar kümeleri aynı değildir (`PLURAL_MAP`'te `alias`, `metadata`, `constant`, `command`, `validation` vardır, `MAP`'te `Guard`, `Library`, `Validator` gibi sonekler vardır). Yeni bileşen türü eklenirken iki tabloya da bakılmalıdır.
6. **`RouteBlueprint::HONEYPOT_PATHS` (35 yol) kurum bilgisi taşımaz**; liste `TrafficProvider::record()` ve tuzak yol koruması tarafından okunur — içerik burada kopyalanmadı.
7. **Marka bilgisi yalnız `FrameworkIdentity` içindedir** (Anayasa §9): `example.tr`, `info@example.tr`, `example`; framework kodu başka yerde marka/proje adı yazmaz.
8. **CDN adresleri sürüme sabitlenmiştir** (`bootstrap@5.3.2`, `font-awesome/6.5.1`, …); güncelleme `AssetDefinition` değişikliğidir. İntegrity (SRI) özniteliği bu dosyada yoktur.

## 6. Örnek (gerçek koddan)

```php
// Core/System/Kernel/Stages/Autoload.php:41
$projectPrefix = Definition::get('namespace', 'PROJECT_PREFIX') ?: 'Rbn\Project\\';
```

```php
// Core/Support/Definitions/System/ComponentTypes.php:83-87
public static function pluralize(string $type): string
{
    $lowType = strtolower($type);
    return self::PLURAL_MAP[$lowType] ?? $lowType . 's';
}
```

## 7. İlgili belgeler

* [Core/Support genel bakış](README.md) · [Blueprints](Blueprints.md) · [System/Discovery](../System/Discovery.md) · [System/Paths](../System/Paths.md) · [System/Registries](../System/Registries.md)
* [Kavram: asset sistemi](../../kavramlar/05-asset-sistemi.md) · [Açık sorular](../../acik-sorular.md)
