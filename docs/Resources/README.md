# Resources — çerçeve kaynak varlıkları (görünümler, varlıklar, veri, ikonlar)

> **Doğrulanan kod tabanı:** `d49b4413` (dal `feat/fw-license-master`) · **Tarih:** 2026-10-05 · **Yayın:** 0.9.6 = bu commit + sonrası; belge yalnız doğrulama anındaki kodu anlatır
> **Kaynak klasör:** `Resources/` — 4 alt klasör: `Assets/`, `Data/`, `images/`, `Views/`.
> **Envanter:** **`Views/` 29 `*.php`** + `Views/System/sitemap.xsl` (1) →
> toplam **29 `*.php` + 1 `.xsl` = 30 dosya**; `Assets/` 52, `Data/` 10,
> `images/` 2 dosya (php değil) → klasör toplamı **94 dosya**.
> **Envanter kanıtı:** 29 php dosyanın **29'u** §2.1'de tek tek anlatıldı;
> `Assets/Data/images` §2.2–§2.4'te dosya listesiyle verildi (php olmadıkları
> için "anlatılan dosya" sayımına girmez, ayrı listelenmiştir).

## 1. Ne işe yarar, kim kullanır

`Resources/`, framework'ün **çerçeve düzeyi** (projeye özel olmayan) kaynak
ağacıdır: ortak görünümler (`Views/`), CSS/JS/font varlıkları (`Assets/`),
JSON veri sözlükleri (`Data/`) ve favicon SVG'leri (`images/`).

**Kimler çağırır:**
* `Core/Render/Resolvers/ViewResolver.php:79-90` — `Resources/Views` **son
  çare** (framework fallback) olarak aranır; proje ve bundle görünümleri
  bulunamazsa burası devreye girer.
* `Core/Render/Resolvers/SeoResolver.php:327` — `Resources/images/favicon-rbnadmin.svg`
  dosyasını okur.
* `Core/Services/Gatekeepers/Handlers/GeoIPHandler.php:268` —
  `Resources/Data/Locations/countries.json`.
* `Bundles/RbnSuite/RbnAdmin/Providers/SocialMediaProvider.php:27` —
  `Resources/Data/social_media_platforms.json`.

## 2. Klasör/dosya envanteri

### 2.1 `Views/` — 29 `*.php` (29/29 anlatıldı)

Görünümler `.rbn.php` ve `.php` uzantılarının ikisini de destekler
(`ViewResolver.php:43`). `Errors/` çerçevenin hata ekranlarıdır;
`RbnCommon/` her projede kullanılan ortak iskelet; `RbnAdmin/` ve `RbnAuth/`
bundle'ların panel/giriş görünümleridir.

#### 2.1.1 `Views/Errors/` (6 + 2 layout = 8)

| Dosya | Görev | Beklediği değişkenler |
|---|---|---|
| `Errors/development.php` | Geliştirme hata ekranı (kırmızı başlık, `$_GET/$_POST/$_SERVER` dökümü, trace). | `$data`, `$errorMessage`, `$exception`, `$trace` |
| `Errors/standard.php` | Üretim hata ekranı: koyu panel, hata kodu + ipucu. | `$code`, `$title`, `$message`, `$desc`, `$hint`, `$uri` |
| `Errors/maintenance.php` | Bakım modu ekranı. | `$maintenance_message`, `$seoMeta`, `$shieldName`, `$shieldVersion` |
| `Errors/pre_flight.php` | Uçuş öncesi tanı teşhis ekranı (`$branding`, `$yedek`, `$solution_hint`). | `$error_type`, `$error_message`, `$solution_hint`, `$branding` |
| `Errors/survival.php` | Son çare kurtarma ekranı (kopyalanabilir metin kutusu). | `$type`, `$message`, `$hint`, `$copyText` |
| `Errors/panic.view.php` | Panik ekranı: hata kimliği (`$errorId`), dosya/satır, trace. | `$errorId`, `$message`, `$file`, `$line`, `$trace` |
| `Errors/Layouts/shield_header.php` | Hata ekranlarının ortak `<head>`/marka bloğu. | `$branding`, `$view`, `$errorType`, `$asset_v` |
| `Errors/Layouts/shield_footer.php` | Hata ekranlarının ortak alt bilgi + ortak JS paketi. | `$asset_v` |

#### 2.1.2 `Views/RbnCommon/` (5 + 2 = 7)

| Dosya | Görev | Beklediği değişkenler |
|---|---|---|
| `RbnCommon/header.rbn.php` | Ortak `<head>`: dinamik varlıklar, SEO/Apps head çıktısı, gövde sınıfı ve script'ler. | `$projectHeaderFile`, `$layoutsDir`, `$headerAssets`, `$headStateHtml`, `$appSeoHtml`, `$appSchemaHtml`, `$google_adsense_code`, `$google_analytics_code`, `$body_class`, `$no_navbar`, `$head_scripts`, `$body_scripts` |
| `RbnCommon/footer.rbn.php` | Ortak alt bar; proje alt bilgisi varsa onu kullanır. | `$projectFooterFile`, `$layoutsDir`, `$siteData`, `$footerAssets`, `$footer_scripts`, `$no_footer`, `$no_bottom_bar`, `$hasFeed`, `$rbn_name`, `$rbn_url`, `$appName` |
| `RbnCommon/navbar.rbn.php` | Çerçeve navigasyon delegasyonu (proje navigasyonu varsa onu render eder). | `$projectNavbarFile`, `$navbarsDir`, `$projectKey` |
| `RbnCommon/head_state.rbn.php` | Sayfa başlığı/descrip ikonları için paylaşılan durum parçası. | `$context` |
| `RbnCommon/userdash.rbn.php` | Kullanıcı paneli kabuğu (dekoratif arka plan). | `$userName`, `$userId`, `$userRole`, `$pageTitle`, `$pageDesc`, `$domainName`, `$userPath` |
| `RbnCommon/Components/cookie_notice.rbn.php` | Çerez rıza bildirimi. | `$consentKey`, `$policyUrl`, `$cookieDisabled` |
| `RbnCommon/Components/universal_modal.rbn.php` | Genel amaçlı modal kabuğu (tüm panel modalları bunu kullanır). | — (kabuk) |

#### 2.1.3 `Views/RbnAdmin/` (1 + 3 bileşen + 5 layout = 9)

| Dosya | Görev | Beklediği değişkenler |
|---|---|---|
| `RbnAdmin/dashboard.rbn.php` | Yönetim paneli komuta merkezi kabuğu; panel dashboard dosyasını render eder. | `$projectDashboardFile`, `$dashboardsDir`, `$projectKey`, `$stats`, `$aiReport` |
| `RbnAdmin/Components/ai_stat_cards.rbn.php` | AI kullanım özet kartları (token, istek, USD/TRY maliyet). | `$aiReport`, `$summary`, `$textCostTry`, `$imageCostTry`, `$totalCostTry`, `$containerId` |
| `RbnAdmin/Components/webtraffic_stat_cards.rbn.php` | Web trafiği özet kartları. | `$stats` |
| `RbnAdmin/Components/server_health_drawer.rbn.php` | Sunucu/sistem sağlığı çekmecesi (PHP sürümü, opcode, limitler). | `$_SERVER`, `$os`, `$phpVersion`, `$phpMemory`, `$opcacheActive`, `$postMax`, `$maxUpload`, `$maxExecution`, `$dbDriver`, `$ip`, `$software` |
| `RbnAdmin/Layouts/panel_header.rbn.php` | Panel `<head>`; **CSRF meta etiketi** burada üretilir (`FW-F08`: etiket yoktu, `rbnService.js` `_getCsrf()` null dönüyordu). | `$headerAssets`, `$headStateHtml`, `$seoHtml`, `$adminTheme` |
| `RbnAdmin/Layouts/content_header.rbn.php` | Sayfa başlığı/hero bandı + breadcrumb. | `$pageTitle`, `$pageDesc`, `$pageIcon`, `$pageBadge`, `$pageEyebrow`, `$breadcrumbs`, `$projectKey`, `$groupProjects` |
| `RbnAdmin/Layouts/navbar.rbn.php` | Üst navigasyon: proje seçici, kullanıcı paneli, bildirim. | `$adminUrl`, `$user`, `$message`, `$notification`, `$profilePanel`, `$groupProjects`, `$activeProjectName` |
| `RbnAdmin/Layouts/sidebar.rbn.php` | Panel kenar menüsü; menü öğeleri içerik varlığına göre gizlenir (`$hasBlog`, `$hasNews`, `$hasDraft`, `$hasCategory`). | `$sidebarItems`, `$contentConf`, `$Route`, `$isDeveloper`, `$isSettingsActive`, `$botActive` |
| `RbnAdmin/Layouts/panel_footer.rbn.php` | Panel kapanışı + alt varlıklar. | `$footerAssets`, `$rbnSecurity`, `$isTemplate` |

#### 2.1.4 `Views/RbnAuth/` (1 + 2 layout = 3)

| Dosya | Görev | Beklediği değişkenler |
|---|---|---|
| `RbnAuth/auth.rbn.php` | Giriş/kimlik ekranı kabuğu (logo, özellik listesi, sürüm). | `$siteLogo`, `$siteName`, `$siteVersion`, `$siteFeatures`, `$user`, `$token`, `$feature` |
| `RbnAuth/Layouts/auth_header.rbn.php` | Kimlik ekranı `<head>`. | `$auth_name`, `$auth_version`, `$author`, `$headerAssets`, `$seoHtml` |
| `RbnAuth/Layouts/auth_footer.rbn.php` | Kimlik ekranı alt bilgi. | `$auth_name`, `$auth_version`, `$author`, `$author_url`, `$footerAssets` |

#### 2.1.5 `Views/System/` (2 php + 1 xsl = 3)

| Dosya | Görev | Beklediği değişkenler |
|---|---|---|
| `System/adsense.rbn.php` | Google AdSense çekirdek betiği; yalnız **üretimde** ve `window.rbnEnableAdBlockModal` tanımlıysa yüklenir. | `$adSettings`, `$clientId`, `$position`, `$isLocalDev` |
| `System/redirect_loading.rbn.php` | Yönlendirme arası bekleme ekranı (geri sayım). | `$redirectUrl`, `$delay`, `$message`, `$subMessage`, `$email` |
| `System/sitemap.xsl` | **php değil**: sitemap.xml için XSLT stil sayfası (tarayıcıda okunabilir sitemap). | — |

**Kapsama:** 29 php / 29 php = **%100**; ayrıca `sitemap.xsl` listelendi.

### 2.2 `Assets/` — 52 dosya (0 php)

| Alt klasör | Dosya sayısı | İçerik |
|---|---|---|
| `Assets/RbnCommon/css/` | 5 kök + `core/` 16 + `components/` 4 + `optional/` 2 = **27** | `rbn-colors/rbn-common/rbn-master/rbn-shield/rbn-terminal.css` + çekirdek (`reset, tokens, colors, layout, buttons, cards, forms, badges, tabs, dropdowns, dashboard, elements, surfaces, responsive, utilities, rbn-auth`) + bileşen (`rbnAlert, rbnCharts, rbnModal, rbnTable`) + genişletilmiş |
| `Assets/RbnCommon/js/` | 1 kök + `core/` 2 + `components/` 4 + `Networking/` 2 + `Utils/` 4 = **13** | `rbn-master.js`, `core/rbnDom.js`, `core/rbnComponents.js`, `components/rbnAlert|rbnCharts|rbnModal|rbnTable.js`, `Networking/rbnService.js`, `Networking/DebugBridge.js`, `Utils/rbnAi|rbnBinders|rbnFile|rbnUtils.js` |
| `Assets/RbnCommon/fonts/` | **3** | `BebasNeue-Regular.ttf`, `Montserrat-Bold.ttf`, `Poppins-Bold.ttf` |
| `Assets/RbnAdmin/css/` | 4 kök + `components/` 2 + `custom/` 1 = **7** | `ra-variables/ra-global/ra-layout/rbnAdmin.css` + `components/ra-banners|ra-elements.css` + `custom/ra-settings.css` |
| `Assets/RbnAdmin/js/` | **2** | `rbnAdmin.js`, `rbnAdminApp.js` |
| **Toplam** | **27 + 13 + 3 + 7 + 2 = 52** | ölçüm: **52** (`34 .css` = 27+7 · `15 .js` = 13+2 · `3 .ttf`) |

### 2.3 `Data/` — 10 dosya (0 php, 10 `.json`)

| Dosya | Görev | Tüketici |
|---|---|---|
| `Data/countries.json` (→ `Data/Locations/countries.json`) | Ülke kodu/eşleme sözlüğü. | `Core/Services/Gatekeepers/Handlers/GeoIPHandler.php:268` |
| `Data/Locations/tr-locations.json` | TR il/ilçe verisi. | (proje tarafı) |
| `Data/Locations/Neighborhoods/antalya.json` · `burdur.json` · `isparta.json` | Semt/mahalle verisi. | (proje tarafı) |
| `Data/social_media_platforms.json` | Sosyal medya platform tanımları. | `Bundles/RbnSuite/RbnAdmin/Providers/SocialMediaProvider.php:27` (SSoT) |
| `Data/currencies.json` | Para birimi sözlüğü. | (tüketici araştırıldı, kaynak ağacında doğrudan çağıran yok) |
| `Data/locales.json` | Yerelleştirme sözlüğü. | (aynı) |
| `Data/timezones.json` | Saat dilimi sözlüğü. | (aynı) |
| `Data/category_keywords.json` | Kategori anahtar kelimeleri. | (aynı) |

**Araştırılan ve raporlanan (spek §3):** Bu 10 dosyanın **yalnız 2'sinin**
çerçeve kaynak ağacında doğrudan çağıranı bulundu
(`countries.json`, `social_media_platforms.json`). Kalan 8 dosya için
`Core/`, `Bundles/`, `Packages/` ağaçlarında dosya adıyla tarama yapıldı;
eşleşme çıkmadı. Bunlar ya proje katmanından `Paths::frameworkRoot()` ile
okunur ya da ileride kullanılmak üzere bırakılmıştır. **"Bilinmiyor"
denilmedi**; maddeler `../acik-sorular.md`'ye işaretleme için öneri olarak
bu belgede kayıtlıdır.

### 2.4 `images/` — 2 dosya (0 php, 2 `.svg`)

| Dosya | Görev | Tüketici |
|---|---|---|
| `images/favicon-rbnadmin.svg` | Yönetim paneli favicon'u. | `Core/Render/Resolvers/SeoResolver.php:326-327` (`file_get_contents(Paths::frameworkRoot().'/Resources/images/favicon-rbnadmin.svg')`) |
| `images/favicon-rbnauth.svg` | Kimlik ekranı favicon'u. | `SeoResolver.php:336` anahtarı `['rbnadmin','rbnauth']` ile sınırlar |

## 3. Akış

### 3.1 Görünüm çözümleme (ViewResolver önceliği)

```
ViewResolver::resolve('RbnCommon/navbar')          ViewResolver.php:34
 ├─ cacheKey = md5(view + options)                 :36
 ├─ context = options['context'] ?? aktif controller bağlamı   :46
 ├─ HEDEF LİSTESİ (soverenlik sırası):
 │    TARGET 0: Paths::module($module)->views()    :53-60
 │    TARGET 1: Paths::project()->views()          :63   ← proje her şeyi ezer
 │    TARGET 2: Paths::module($activePanel,'suite')->views()  :66-71
 │    TARGET 3: Paths::module($context)->views()   :74-76
 │    TARGET 4: Paths::framework()->views()        :79-80  ← Resources/Views
 │              + Resources/Views/RbnCommon       :81
 │    + ucfirst($activePanel) / ucfirst($context)  :84-90
 ├─ ÖNCEL ÖZEL: Resources/Views/RbnCommon/<ad>.php|rbn.php varsa DOĞRUDAN dön  :99-105
 ├─ kalıcı keşif haritası (mapper) kaydı varsa ve dosya varsa → dön  :107-111
 └─ hedef listesi taranır (.php, .rbn.php); bulunan yol mapper'a yazılır  :113-119
```

**Kritik:** Framework `Resources/Views` yalnız **son çare**dir; proje
görünümü varsa kazanır. Buna karşılık `RbnCommon` için erken çıkış
(`:99-105`) vardır — yani `RbnCommon/*` görünümleri proje tarafından
gölgelenebilir ama **proje görünümü bulunamazsa** çerçevenin kendi
`RbnCommon` kopyası her zaman bulunur.

### 3.2 Panel `<head>` → CSRF meta (FW-F08)

```
View: RbnAdmin\Layouts\panel_header.rbn.php
 ├─ <meta name="csrf-token" content="..."> üretir
 └─ js: Assets/RbnCommon/js/Networking/rbnService.js → _getCsrf() bu meta'yı okur
     → framework AJAX POST'ları token'siz kalmasın
```
Bu meta etiketi bir kez **yoktu**; JS `null` dönüyordu ve CSRF zorunlu
kılınınca panel akışları kırılıyordu (dosyanın başındaki not).

### 3.3 Varlık (asset) sunumu

```
Kod → FrontendBaseController::addAsset(...)   Core/Render/Controllers/…
 └─ AssetProvider → AssetBuilder (bağımlılık çözümleme, CDN-first sıralama)
     ├─ yollar /project-assets/… ve /framework-assets/… olarak normalize edilir  View.php:191-194
     ├─ fontlar: /framework-assets/fonts/<küçük-hyphenli-ad>   AssetBuilder.php:321
     └─ çıktı: Resources/Assets/… dosyalarının URL'leri
        (fiziksel dosyalar AssetController üzerinden sunulur)
```

## 4. Yapılandırma / ayar anahtarları

| Anahtar / değer | Yer | Not |
|---|---|---|
| Görünüm uzantıları | `Core/Render/Resolvers/ViewResolver.php:43` | `['.php', '.rbn.php']` — ikisi de her yerde geçerli |
| `project-assets` / `framework-assets` önekleri | `Core/Render/Configs/AssetConfig.php:27,31`; `View.php:191-194` | `Resources/Assets` içeriği `framework-assets` önekiyle sunulur |
| `adsense.rbn.php` yükleme koşulu | `Resources/Views/System/adsense.rbn.php` başlığı | Yalnız üretimde **ve** `window.rbnEnableAdBlockModal` tanımlıysa |
| Panel CSRF meta | `Resources/Views/RbnAdmin/Layouts/panel_header.rbn.php` | `rbnService.js::_getCsrf()` bunu okur |
| `Resources/Data` katman tanımı | `Core/Support/Definitions/System/FolderMatrix.php:168` | Projede `Resources/Data` bir **kod katmanı**dır (içine yazılabilir) |

## 5. Tuzaklar ve kurallar

1. **Çerçeve görünümü her zaman son çaredir.** Proje/bundle görünümü varsa
   kazanır (`ViewResolver.php:62-90`). Çerçeve görünümünü değiştirerek bir
   sorunu "çözmek", projelerin kendi görünümleri varken **etkisiz**dir.
2. **`RbnCommon` erken çıkış kuralı istisnadır** (`:99-105`): framework'ün
   kendi `RbnCommon` kopyası, diğer tüm hedeflerden **önce** denenir.
   Yeni bir ortak görünüm eklendiğinde burası güncellenmezse proje
   görünümü gelene kadar erken çıkış devrede kalır.
3. **`ViewResolver` çift önbellek kullanır**: statik `self::$resolveCache`
   (bellek içi) ve `mapper` (kalıcı keşif haritası, `:94, :102, :118`).
   Görünüm taşınırsa kalıcı harita eski yolu gösterir; kodda
   `file_exists()` denetimi bu yüzden atlanmaz (`:108`).
4. **CSRF meta etiketi panel `<head>`'inde üretilir.** `panel_header.rbn.php`
   silinir/taşınırsa tüm panel AJAX POST'ları token'sız kalır ve CSRF
   zorunluluğu açıldığında kırılır (`FW-F08` notu).
5. **`adsense.rbn.php` yerelde yüklenmemelidir**; `$isLocalDev` koruması
   vardır, kaldırılırsa yerel ortamda reklam betiği çalışır.
6. **Yeni ortak CSS/JS `rbn-master` zincirine eklenmelidir**, tek tek
   `<script>` etiketiyle değil — `header.rbn.php` varlıkları
   `headerAssets` üzerinden toplar.
7. **`Assets/` dosyaları php olmadığı için §2.2 "anlatılan dosya" sayımına
   girmez**; bu belgede yine de tamamı listelenmiştir (spk §2 "dosya
   atlanmaz" kuralı).

## 6. Örnek (gerçek koddan)

Görünüm çağrısı ve panel CSRF akışı (gerçek sözleşmeler):

```php
// Görünüm çözümleme sırası: proje > bundle > framework > RbnCommon
// Yeni ortak görünüm eklerken: Resources/Views/RbnCommon/<ad>.rbn.php

// Panelde CSRF:
// <meta name="csrf-token"> panel_header.rbn.php üretir
// rbnService.js::_getCsrf() bu değeri okur ve POST başlıklarına koyar
```

## 7. İlgili belgeler

* Core/Render (`../Core/Render/`) — `ViewResolver`, `AssetBuilder` (⏳ ayrı görev; belgesi henüz yazılmadı)
* [../Core/System/Paths.md](../Core/System/Paths.md) — `Paths::framework()->views()`, `publicRoot()`
* [../Core/Support/Definitions.md](../Core/Support/Definitions.md) — `FolderMatrix::CODE_LAYERS`
* Core/Services (`../Core/Services/`) — `GeoIPHandler` (⏳ ayrı görev; belgesi henüz yazılmadı)
* [../Packages/RbnFile.md](../Packages/RbnFile.md) — `Paths::project()->uploads()` / `publicRoot()`
* [../kavramlar/05-asset-sistemi.md](../kavramlar/05-asset-sistemi.md) — varlık sistemi kavram rehberi
* [../acik-sorular.md](../acik-sorular.md) — `Data/*.json` tüketicileri (araştırıldı, 8 dosyada çağıran bulunamadı)
