---
hüküm: tamamlandı
tip: dokümantasyon
okur: FW-CSS-MOTOR-1 / FW-CSS-MOTOR-2, proje geliştiricileri, ajanlar
platform: Windows
---

# 05 — Asset Sistemi (CSS / JS / Font / İkon Yükleme)

> **Bu belge hangi commit'e göre yazıldı:** `d4af18d` (dal `feat/fw-license-master`, 2026-10-05 20:49)
> **Son doğrulama tarihi:** 2026-10-05
> **Yayın tabanı:** 0.9.5 = bu commit + sonrası; belge yalnız doğrulama anındaki kodu anlatır
> **Kapsam:** `rbnframework/` deposunun asset katmanı — `Core/Support/Definitions/Render/*`,
> `Core/Render/{Builders,Controllers,Services,Providers,Configs}/*`, `Resources/Assets/RbnCommon/*`
> **Yazım kuralı:** Her iddia `dosya:satır` ile kaynağa bağlıdır. Ölçülen sayılar **bu belgedeki
> ölçüm komutunun çıktısıdır**; başka bir yerden alınmış tahmin yoktur. Kodla doğrulanamayanlar
> §8 `BILINMEYENLER` bölümündedir.
> **Bu belge KOD DEĞİŞTİRMEZ.** Yalnız okur/uygular.

> **ÖNCEKİ YANLIŞ VARSAYIMIN DÜZELTİLMESİ**
> *"framework-assets/fonts/space-mono yerelden gelir"* → **YANLIŞ**.
> `AssetController::serve()` `fonts/<slug>` yolunu `AssetFonts::FONT_LIBRARY` içindeki **Google Fonts
> URL'sine 307 ile yönlendirir**. Yerel font **dosyası sunulmaz**.
> Canlı kanıt (§2.4, `curl` çıktısı):
> ```
> GET /framework-assets/fonts/space-mono  →  307
> Location: https://fonts.googleapis.com/css2?family=Space+Mono:ital,wght@0,400;0,700;1,400;1,700&display=swap
> ```
> Bu bir **tasarım kararıdır, hata değil**: §2.5'te anlatıldığı gibi Google Fonts / Google Tag Manager /
> Remix Icon **kalıcıdır**; `AssetDefinition` CDN adresleri **bilinçli çok yönlüdür** (isteyen kullanır).

---

## İçindekiler

1. [Yükleme sistemi uçtan uca](#1-yükleme-sistemi-uçtan-uca)
2. [Sabit tablosu (`AssetDefinition`)](#2-sabit-tablosu-assetdefinition)
3. [`AssetBundles` — paketler ve bağlam yığınları](#3-assetbundles--paketler-ve-bağlam-yığınları)
4. [`AssetBuilder` — sıralama, `css_engine` kararı, URL üretimi, sürümleme](#4-assetbuilder--sıralama-css_engine-kararı-url-üretimi-sürümleme)
5. [`AssetController` — sunum uçları, font yönlendirmesi, medya vekili](#5-assetcontroller--sunum-uçları-font-yönlendirmesi-medya-vekili)
6. [Font sistemi (`AssetFonts`)](#6-font-sistemi-assetfonts)
7. [CSS katmanları](#7-css-katmanları)
8. [JS katmanları](#8-js-katmanları)
9. [İkon sistemi](#9-ikon-sistemi)
10. [Favicon / OG görseli konvansiyonu (`AssetConvention`)](#10-favicon--og-görseli-konvansiyonu-assetconvention)
11. ["Bir kaynağı nasıl ister / değiştiririm" tarifleri](#11-bir-kaynağı-nasıl-ister--değiştiririm-tarifleri)
12. [Performans notları (yalnız ölçülen)](#12-performans-notları-yalnız-ölçülen)
13. [BILINMEYENLER](#13-bilinmeyenler)

---

## 1. Yükleme sistemi uçtan uca

Altı katman, tek yönde çalışır:

```
(1) project-routemap.php      →  css_engine: 'rbn' | belirtilmemiş (= bootstrap)
(2) AssetDefinition.php       →  tek dosya yol/CDN sabitleri (saf kayıt defteri, mantık YOK)
(3) AssetBundles.php          →  BUNDLES (hangi asset hangi paket) + STACK_MAP (hangi paket hangi bağlamda)
(4) AssetBuilder.php          →  injectCoreStack() → collect() → compile() → build()   [sıralama + URL]
(5) AssetController.php       →  /framework-assets/… , /project-assets/…                 [fiziksel sunum]
(6) Controller addAsset()     →  yalnızca SAYFAYA ÖZEL ek varlık
```

Yol haritası:

| Katman | Dosya | Satır sayısı |
|---|---|---|
| (2) | `Core/Support/Definitions/Render/AssetDefinition.php` | 64 |
| (3) | `Core/Support/Definitions/Render/AssetBundles.php` | 82 |
| (6) | `Core/Render/Controllers/FrontendBaseController.php` | 222 |
| (4) | `Core/Render/Builders/AssetBuilder.php` | 317 |
| (5) | `Core/Render/Controllers/AssetController.php` | 402 |
| keşif | `Core/System/Discovery/Clusters/Resources/AssetResolver.php` | 123 |
| boyama | `Core/Render/Providers/AssetProvider.php` | 112 |
| orkestrasyon | `Core/Render/Services/AssetService.php` | 96 |
| rota | `Core/Routes/Mappings/core.php:46-48` | 71 (dosya) |

Rotalar (`Core/Routes/Mappings/core.php:45-48`):

```php
// Framework Assets Proxy
Route::prefix('framework-assets')->get('{path:.+}', 'AssetController@serve');
// Project Assets Proxy (Dynamic)
Route::prefix('project-assets')->get('{path:.+}', 'AssetController@serveProject');
```

`serveProject()` (`AssetController.php:315-318`) yalnızca `serve($path, true)` çağırır; tek fark
`$isProject = true` — sanal SVG ve sanal OG görseli dalları sadece bu bayrakla açılır
(`AssetController.php:107`, `:124`).

**Token sözleşmesi** (`AssetConfig::PROXY_SETUP`, `Core/Render/Configs/AssetConfig.php:24-34`):

| Önek | Proxy yolu | Kaynak |
|---|---|---|
| `@project/` | `/project-assets/` | projenin `public/` kökü |
| `@fw/` | `/framework-assets/` | framework `Resources/Assets/` |
| `@font/` | `/framework-assets/fonts/<slug>` | **yok — 307 yönlendirme** (§6) |

Token ayrıştırma `AssetBuilder::resolveToken()` (`:350-369`), sırayla `project` → `framework` → `font`
denenir; ilk eşleşen önek kazanır.

---

## 2. Sabit tablosu (`AssetDefinition`)

`Core/Support/Definitions/Render/AssetDefinition.php` — **36 `public const`**. Sınıf dokümanı
(`:7-12`) bunu açıkça söyler: *"This file is a PURE REGISTRY … Hiçbir mantık yok."* Doğrulandı:
dosyada tek bir `if`/`foreach`/metot yok.

### 2.1 Dış CDN sabitleri (tür: **CDN**)

| Sabit | Satır | URL / Yol | Hangi bundle |
|---|---|---|---|
| `BOOTSTRAP_CSS` | 18 | `https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css` | `bootstrap` |
| `BOOTSTRAP_JS` | 19 | `https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js` | `bootstrap` |
| `FONT_AWESOME` | 21 | `https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css` | `font_awesome` |
| `BOOTSTRAP_ICONS` | 22 | `https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css` | `bootstrap_icons` |
| `REMIX_ICON` | 23 | `https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css` | `remix_icon` |
| `FLAG_ICON` | 24 | `https://cdnjs.cloudflare.com/ajax/libs/flag-icon-css/6.6.6/css/flag-icons.min.css` | `flag_icon` |
| `JQUERY` | 26 | `['path' => 'https://code.jquery.com/jquery-3.7.1.min.js', 'renderInHead' => true]` | `jquery` |
| `SORTABLE_JS` | 27 | `https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js` | `sortable` |
| `AOS_CSS` | 28 | `https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css` | `aos` |
| `AOS_JS` | 29 | `https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js` | `aos` |

> `JQUERY` **tek CDN sabiti değil**, dizi biçimindedir (`path` + `renderInHead`), çünkü `<head>`'de
> çalışması gerekir. `AssetBuilder::compile()` diziyi `is_array($item)` dalında açıp `renderInHead`
> ve `attributes` alanlarını korur (`:311-312`).

### 2.2 Framework ortak varlıkları (tür: **yerel**, token `@fw/`)

| Sabit | Satır | Yol | Hangi bundle |
|---|---|---|---|
| `RBN_ADMIN_CSS` | 34 | `@fw/RbnAdmin/css/rbnAdmin.css` | `rbn_core_panel` |
| `RBN_ADMIN_JS` | 35 | `@fw/RbnAdmin/js/rbnAdmin.js` | `rbn_core_panel` |
| `RBN_ADMIN_VARIABLES_CSS` | 36 | `@fw/RbnAdmin/css/variables.css` | `admin_core_css` |
| `RBN_ADMIN_GLOBAL_CSS` | 37 | `@fw/RbnAdmin/css/global.css` | `admin_core_css` |
| `RBN_ADMIN_APP` | 38 | `@fw/RbnAdmin/js/rbnAdminApp.js` | `security` |
| `RBN_MASTER_JS` | 44 | `@fw/RbnCommon/js/rbn-master.js` | `rbn_master_js` **ve** `rbn_master` |
| `RBN_COMMON_CSS` | 45 | `@fw/RbnCommon/css/rbn-common.css` | `rbn_core_frontend` |
| `RBN_MASTER_CSS` | 46 | `@fw/RbnCommon/css/rbn-master.css` | `rbn_core_panel`, `rbn_core_auth`, `rbn_master` |
| `RBN_SHIELD_CSS` | 47 | `@fw/RbnCommon/css/rbn-shield.css` | **HİÇBİR PAKETTE YOK** (§13-B1) |
| `RBN_DASHBOARD_CSS` | 48 | `@fw/RbnCommon/css/core/dashboard.css` | `rbn_core_panel`, `rbnDashboard` |
| `RBN_AUTH_CSS` | 49 | `@fw/RbnCommon/css/core/rbn-auth.css` | `rbn_core_auth` |
| `RBN_EXTENDED_UTILITIES_CSS` | 55 | `@fw/RbnCommon/css/optional/rbn-utilities-extended.css` | `rbnExtended` |
| `RBN_EXTENDED_COMPONENTS_CSS` | 56 | `@fw/RbnCommon/css/optional/rbn-components-extended.css` | `rbnExtended` |
| `RBN_SERVICE` | 61 | `@fw/RbnCommon/js/Networking/rbnService.js` | **HİÇBİR PAKETTE YOK** (§13-B1) |
| `RBN_DEBUG_BRIDGE` | 62 | `@fw/RbnCommon/js/Networking/DebugBridge.js` | **HİÇBİR PAKETTE YOK** (§13-B1) |
| `RBN_ALERT_JS` | 63 | `@fw/RbnCommon/js/components/rbnAlert.js` | **HİÇBİR PAKETTE YOK** — `rbn-master.js` dinamik yüklüyor (§8.1) |
| `RBN_ALERT_CSS` | 64 | `@fw/RbnCommon/css/components/rbnAlert.css` | `rbn_core_frontend` |
| `RBN_MODAL_JS` | 65 | `@fw/RbnCommon/js/components/rbnModal.js` | **HİÇBİR PAKETTE YOK** — dinamik yükleniyor |
| `RBN_MODAL_CSS` | 66 | `@fw/RbnCommon/css/components/rbnModal.css` | `rbnModal` |
| `RBN_UTILS` | 67 | `@fw/RbnCommon/js/Utils/rbnUtils.js` | **HİÇBİR PAKETTE YOK** — dinamik yükleniyor |
| `RBN_BINDERS` | 68 | `@fw/RbnCommon/js/Utils/rbnBinders.js` | **HİÇBİR PAKETTE YOK** — dinamik yükleniyor |
| `RBN_AI` | 69 | `@fw/RbnCommon/js/Utils/rbnAi.js` | **HİÇBİR PAKETTE YOK** |
| `RBN_CHARTS_JS` | 70 | `@fw/RbnCommon/js/components/rbnCharts.js` | `rbnCharts` |
| `RBN_CHARTS_CSS` | 71 | `@fw/RbnCommon/css/components/rbnCharts.css` | `rbnCharts` |
| `RBN_TABLE_JS` | 72 | `@fw/RbnCommon/js/components/rbnTable.js` | `rbnTable` |
| `RBN_TABLE_CSS` | 73 | `@fw/RbnCommon/css/components/rbnTable.css` | `rbnTable` |

**Önemli gözlem (davranış, hata değil):** `rbn-master.js` çekirdek JS modüllerini **kendisi dinamik
olarak** yükler (§8.1). Bu yüzden `RBN_ALERT_JS`, `RBN_MODAL_JS`, `RBN_UTILS`, `RBN_BINDERS`
sabitleri vardır ama **hiçbir pakete bağlı değildir** — tanımları `BUNDLES` yerine `rbn-master.js`
içindeki `coreModules` dizisini besler. Yeni bir çekirdek JS modülü eklemek için iki yer güncellenir:
sabit + `rbn-master.js` dizisi.

### 2.3 Token'ı doğrudan yazan paket girdileri

`AssetBundles` içinde sabit kullanılmayan iki yer:

| Değer | Satır | Ne yapar |
|---|---|---|
| `'@project/css/master.css'` | `AssetBundles.php:48` (`rbn_core_frontend`) | frontend'in **proje** giriş CSS'i; her frontend sayfasında otomatik |
| `'@project/master.css'` | `AssetBundles.php:90` (`project`) | ⚠️ `project` paketi `rbn_core_frontend` ile **çakışır ve kullanılmaz**; ayrıca `domains/**/css/master.css` (21 dosya) yapısıyla uyuşmaz (`@project/master.css` proje kökü bekler). §13-B2 |
| `'@font/<Ad>'` | `AssetBundles.php:36-37` (`fonts_panel`, `fonts_auth`) | §6 |

### 2.4 Yönlendirme (tür: **yönlendirme**) — `fonts/<slug>`

`AssetController::serve()` (`AssetController.php:53-77`):

1. Yol `fonts/` ile başlıyorsa slug çıkarılır (`fonts/` soyulur).
2. `AssetFonts::FONT_LIBRARY` içindeki **her** anahtar için `strtolower(str_replace(' ', '-', $name))`
   slug'ı hesaplanır ve istenen slug ile karşılaştırılır.
3. Eşleşirse: hedef `FONT_LIBRARY`'den gelir (istekten **gelmez** → açık yönlendirme riski yok).
   Yine de iki kontrol uygulanır (`:68-72`): kontrol karakteri/CRLF yok, şema `http`/`https`.
   Başarısızsa `abort(404, '… guvensiz font hedefi reddedildi')`.
4. `header("Location: $url", true, 307); exit;`

**Canlı ölçüm (yerel canlı, 2026-10-05):**

```
$ curl -s -k -D - https://rbncore.tr.test/framework-assets/fonts/space-mono
HTTP/1.1 307 Temporary Redirect
Location: https://fonts.googleapis.com/css2?family=Space+Mono:ital,wght@0,400;0,700;1,400;1,700&display=swap

$ curl -s -k -I https://rbncore.tr.test/framework-assets/fonts/space-mono     # HEAD
HTTP/1.1 307 Temporary Redirect
Location: https://fonts.googleapis.com/css2?family=Space+Mono:...

$ curl … /framework-assets/fonts/space-mono=v1.0                               # sürüm son eki
HTTP/1.1 307 Temporary Redirect        → aynı hedef  (sürüm son eki fonts/ yolunu BOZMAZ)
```

**Yerel font dosyası `fonts/` rotasından sunulmaz.** Doğrulandı:
`GET /framework-assets/fonts/BebasNeue-Regular.ttf` → **HTTP 500** (aşağıda "404 gerçekte 500" notu).
Kütüphanede slug'ı olmayan bir istek de aynı şekilde 500'e düşer.

> ⚠️ **Ölçülen davranış — `abort(404)` HTTP 500 döner.**
> `AssetController` "bulunamadı" için `$this->abort(404, …)` çağırıyor (`AssetController.php:135`),
> ama `abort()` → `shield()->abort()` → `throw new \Exception($message, $code)`
> (`Core/Services/Exception/Concerns/ErrorHandlingTrait.php:65-68`,
> `Core/Services/Exception/Concerns/Shield.php:86-89`) ve istisna işleyicisi 500 üretiyor.
> Ölçüm: `/framework-assets/rbncommon/yok.css` → **500**; normal bir sayfa 404'ü `/yok-boyle-bir-sayfa-xyz`
> → **404**. Yani **asset 404'leri şu an 500 olarak çıkıyor.** §13-B3.

### 2.5 "Bu CDN'ler neden duruyor?" — Patron kararı (2026-10-05)

`AssetDefinition`'daki CDN sabitleri **bilinçli çok yönlü** tasarımdır; framework geneldir ve tek
bir CSS/JS ailesi dayatmaz (Anayasa §9). Ölçülen gerçek kullanım:

| Aile | Gerekçe (ÖLÇÜLDÜ) |
|---|---|
| **Google Fonts** | `AssetFonts::FONT_LIBRARY`'ın **33/33** girdisi `fonts.googleapis.com` (§6). Yerel font yolu yok. |
| **Remix Icon** | `class="… ri-…"` içeren view satırı: **2 527** (framework + projeler). `bi-` 726, `fa*/fas-` **0**. `ri-` fiili standarttır. |
| **Google Tag Manager** | `<site-b>.test` ana sayfasında canlı: `https://www.googletagmanager.com/gtag/js?id=G-J49KXF3T2L`. Proje ayarı/Controller üzerinden yükleniyor (`addAsset` değil, view/`$google_analytics_code` kanalı — §13-B4). |
| Bootstrap / Bootstrap Icons / Font Awesome | Yalnız `css_engine` belirtilmemiş projelerde yüklenir (§4.2). Canlıda `<site-c>` ve `<site-b>.test` hâlâ Bootstrap alıyor. |
| `flag_icon` | Tek proje isteği: `<grup-x>/…/<projeC>FrontendController.php:23` + framework `PanelMap.php` (`'assets' => ['rbnCharts','flag_icon']`). |
| jQuery / Sortable | Yalnız `panel` bağlamında otomatik (`STACK_MAP['panel']`). Frontend'de **yok**. |

**Sonuç:** Google Fonts, Google Tag Manager ve Remix Icon **KALIR**. Kaldırmak `2 527` view satırını
kırar; motor `bi-` (Bootstrap Icons) karşılığı yoktur (`bi-` kullanımı 726 satır, `rbn` motorunda
bu ikonlar **tarayıcıda boş** — §13-B5).

---

## 3. `AssetBundles` — paketler ve bağlam yığınları

`Core/Support/Definitions/Render/AssetBundles.php`.

### 3.1 `STACK_MAP` (`:21-26`) — hangi bağlamda hangi paketler otomatik

| Bağlam | Otomatik paketler (satır) |
|---|---|
| `universal` | `bootstrap`, `font_awesome`, `bootstrap_icons`, `remix_icon`, `rbn_master_js` (`:22`) |
| `frontend` | `rbn_core_frontend` (`:23`) |
| `panel` | `fonts_panel`, `jquery`, `sortable`, `rbn_core_panel`, `rbnTable` (`:24`) |
| `auth` | `fonts_auth`, `rbn_core_auth` (`:25`) |

> `STACK_MAP` bir **bağlam → paket adı** eşlemesidir. `universal` **her** bağlamda önce çalışır
> (`AssetBuilder::injectCoreStack()`, `:257-263`), sonra bağlama özgü yığın (`:270-274`).
> Yani bir `frontend` sayfası `universal` + `frontend` paketlerini alır.

### 3.2 `BUNDLES` (`:32-92`) — 24 paket

| Paket | Satır | `styles` | `scripts` |
|---|---|---|---|
| `rbn_master_js` | 34 | — | `RBN_MASTER_JS` |
| `fonts_panel` | 36 | `@font/Plus Jakarta Sans`, `@font/DM Serif Display`, `@font/JetBrains Mono` | — |
| `fonts_auth` | 37 | `@font/Plus Jakarta Sans` | — |
| `bootstrap` | 38 | `BOOTSTRAP_CSS` | `BOOTSTRAP_JS` |
| `font_awesome` | 39 | `FONT_AWESOME` | — |
| `bootstrap_icons` | 40 | `BOOTSTRAP_ICONS` | — |
| `remix_icon` | 41 | `REMIX_ICON` | — |
| `flag_icon` | 42 | `FLAG_ICON` | — |
| `jquery` | 43 | — | `JQUERY` |
| `admin_core_css` | 46 | `RBN_ADMIN_VARIABLES_CSS`, `RBN_ADMIN_GLOBAL_CSS` | — |
| `rbn_core_frontend` | 47-49 | `RBN_ALERT_CSS`, `RBN_COMMON_CSS`, `@project/css/master.css` | — |
| `rbn_core_panel` | 51-56 | `RBN_MASTER_CSS`, `RBN_DASHBOARD_CSS`, `RBN_ADMIN_CSS` | `RBN_ADMIN_JS` |
| `rbn_core_auth` | 57-65 | `RBN_MASTER_CSS`, `RBN_AUTH_CSS` | — |
| `rbnExtended` | 72-74 | `RBN_EXTENDED_UTILITIES_CSS`, `RBN_EXTENDED_COMPONENTS_CSS` | — |
| `rbnModal` | 77 | `RBN_MODAL_CSS` | `RBN_MODAL_JS` |
| `rbnDashboard` | 78 | `RBN_DASHBOARD_CSS` | — |
| `rbnCharts` | 79 | `RBN_CHARTS_CSS` | `RBN_CHARTS_JS` |
| `rbnTable` | 80 | `RBN_TABLE_CSS` | `RBN_TABLE_JS` |
| `sortable` | 81 | — | `SORTABLE_JS` |
| `aos` | 82 | `AOS_CSS` | `AOS_JS` |
| `rbn_master` | 83-86 | `RBN_MASTER_CSS` | `RBN_MASTER_JS` |
| `security` | 87 | — | `RBN_ADMIN_APP` |
| `project` | 90 | `@project/master.css` | — |
| `frontend` | 91 | — | — |

**Ölçülen gerçek kullanım** (tüm repo, `Storage/framework` derleme önbelleği hariç):

| Paket | Kullanan yer |
|---|---|
| `rbnTable` | 20 (framework `PanelMap`, `<proje-b>` `<projeB>Map`/`<projeB2>Map` `'assets' => ['rbnTable']`) + `STACK_MAP['panel']` |
| `rbnCharts` | `<proje-a>/…/<projeA>FrontendController.php:28`, `RbnAdmin/Models/PanelMap.php` |
| `aos` | `<proje-b>` `<projeB>FrontendController.php:90`, `<projeB2>FrontendController.php:51`, `<proje-d>` `<projeD>FrontendController.php` |
| `flag_icon` | `<grup-x>/…/<projeC>FrontendController.php:23`, `RbnAdmin/Models/PanelMap.php` |
| `rbnDashboard` | `rbncore/…/EmailFrontendController.php` |
| `rbnModal` | **hiçbir yerden istenmiyor** — motor `rbn-master.js` yüklüyor (`:8.1`) |
| `admin_core_css` | **hiçbir yerden istenmiyor** (§13-B6) |
| `rbnExtended` | **hiçbir yerden istenmiyor** (§7.5) |
| `security` | 22 eşleşme ama hepsi `Security`/`Syshub` **controller sınıf adı**; paket adı olarak istenmiyor (§13-B7) |

### 3.3 Paket başvurusu iki yoldan yapılır

1. **Controller:** `$this->addAsset('rbnCharts')` →
   `FrontendBaseController::addAsset()` (`:92-132`), `:119` pakette tanımlı olduğu için başlık
   dönüştürülmez; `AssetService::prepare($items, 'frontend')` çağrılır (`:128`).
2. **Paket haritası (`PanelMap` / `*Map.php`):** `'assets' => ['rbnCharts','flag_icon']` →
   `Core/Render/Providers/UI/PanelProvider.php:48-55`: `prepare($subModuleData['assets'], 'panel')`,
   sonra `headerAssets`/`footerAssets` yeniden basılır.

> `addAsset()`'ın ön-eğri kuralı (`:118-122`): paket adı değilse **ve** `@`/`http`/`/` ile başlamıyorsa
> başına `@project/` konur. Bu yüzden `addAsset('js/frontend.js')` ve `addAsset('/js/frontend.js')` **farklı**
> yollara gider: ilki `@project/js/frontend.js`, ikinci site-kökünden çözülür. Ölçüm: `<proje-a>` ana sayfasında
> `.../project-assets/js/frontend.js=v1789207191` (tek `<script>`) — projelerde `/` ile başlayan
> biçim baskın.

### 3.4 Mükerrerlik koruması

`AssetBuilder::injectCoreStack()` (`:276-282`): `auth` / `panel` / `rbn` motorunda
`common.css` ve `rbnAlert.css` `rbn-master.css` içinde zaten var; filtre bunları atar:

```php
if (($isAuthContext || $isPanelContext || $isRbnEngine) && isset($payload['styles'])) {
    $payload['styles'] = array_values(array_filter($payload['styles'], function ($style) {
        $path = is_array($style) ? ($style['path'] ?? '') : $style;
        return !str_contains($path, 'common.css') && !str_contains($path, 'rbnAlert.css');
    }));
}
```

**Canlı doğrulama (aynı anda iki paket görünmez):**

| Sayfa | `rbn-master.css` | `rbnalert.css` | `rbn-common.css` |
|---|---|---|---|
| `rbncore.tr.test` (`css_engine=rbn`) | ✅ `=v1791201628` | ❌ yok | ❌ yok |
| `<site-a>.test` (`css_engine=rbn`) | ✅ `=v1791201628` | ❌ yok | ❌ yok |
| `<site-b>.test` (bootstrap) | ❌ yok | ✅ `=v1788997133` | ✅ `=v1789055904` |
| `<site-c>.test` (bootstrap) | ❌ yok | ✅ `=v1788997133` | ✅ `=v1789055904` |

Filtre çalışıyor; ayrıca 4 sayfanın hiçbirinde aynı CSS iki kez basılmıyor (sayım §12).

---

## 4. `AssetBuilder` — sıralama, `css_engine` kararı, URL üretimi, sürümleme

`Core/Render/Builders/AssetBuilder.php`.

### 4.1 `css_engine` kararı (`injectCoreStack()`, `:245-283`)

```php
$projectKey = (string) ($this->resolveProjectData('project_key') ?? '');   // :250
$cssEngine  = $this->getRouteConfig($projectKey, 'css_engine');           // :251
$isRbnEngine = (strtolower((string) $cssEngine) === 'rbn');                // :252

$isAuthContext = ($appContext === 'auth');                                 // :254
$isPanelContext = ($appContext === 'panel');                               // :255
```

1. **Bootstrap baypası (`:257-263`)** — `auth`/`panel`/`rbn` motorunda `bootstrap`,
   `bootstrap_icons`, `font_awesome` paketleri `continue` ile atlanır (0 KB).
   `remix_icon` ve `rbn_master_js` **her zaman** yüklenir (listeden düşmez).
2. **`rbn_master` enjeksiyonu (`:266-268`)** — koşul:
   `if ($isAuthContext || ($isRbnEngine && $appContext === 'frontend'))`.
   ⚠️ **`panel` bağlamı bu koşula girmiyor**; panel `rbn_master`'i zaten
   `STACK_MAP['panel'] → rbn_core_panel` içinden alıyor (§4.1 tablosu).
3. **Bağlam yığını (`:270-274`)**.

`getRouteConfig()` (`Core/Base/Concerns/Data/ResolvesProjectConfigTrait.php:71-111`) okuma yolunu
**doğrudan dosyadan** verir: `Paths::project()->configs('project-routemap.php')` → `require` →
`['view_mapping'][$projectKey][$key]`. Yani `css_engine` yalnız
`projects/<proje>/Core/Config/project-routemap.php` → `view_mapping.<alt-proje>.css_engine`
altında aranır.

**Mevcut durum — ÖLÇÜLDÜ (tüm `project-routemap.php` dosyaları taranmış):**

| Değer | Alt proje sayısı | Dosya:satır |
|---|---|---|
| `'css_engine' => 'rbn'` | **13** | `rbncore` (`:11,:25,:37,:49`), `<grup-y>` (`:36,:92`), `<grup-z>` (`:13,:46`), `<proje-d>` (`:17,:43,:58`), `<proje-a>` (`:11,:21`) |
| belirtilmemiş (⇒ bootstrap) | **8** | `<grup-w>` (`<proje-b>`,`<proje-b2>`), `<grup-x>` (`<proje-c>`,`<proje-g>`,`<proje-h>`), `<grup-y>` (`<proje-e>`,`<proje-f>`) |
| **Toplam `view_mapping` girişi** | **21** | |

> Önceki analiz (`Patron-Rapor/Analiz/rbncommon-rapor/css-dahil-etme-sistemi.md:54`) "6 alt proje `rbn`,
> 5 varsayılan" diyordu. **Bu sayım bayattır**; 2026-10-05'te ölçülen gerçek sayı 13 / 8.
> (O analiz 2026-10-02 tarihliydi; sonraki gece işçileri `css_engine` eklemiş.)

**Canlı doğrulama:**

| Site | `css_engine` | Bootstrap CSS | `rbn-master.css` |
|---|---|---|---|
| `rbncore.tr.test` | `rbn` | ❌ | ✅ |
| `<site-a2>.test` | `rbn` | ❌ | ✅ |
| `<site-c>.test` | *(yok)* | ✅ `bootstrap@5.3.2` | ❌ |
| `<site-b>.test` | *(yok)* | ✅ `bootstrap@5.3.2` | ❌ |

### 4.2 Sıralama (`build()`, `:142-202`)

`uasort` comparator (`:164-192`) üç kural uygular, sırayla:

1. `@project-assets/` ile başlayanlar **daima en son** (`:166-174`) — framework ve CDN'leri ezebilmek için.
2. Yolu `bootstrap` / `rbn-master.css` / `rbn-core.css` **içeren** her şey **daima en başta** (`:177-185`).
3. `priority` küçükten büyüğe (`:187-189`). Varsayılan `10` (`:39`, `:313`);
   `addFont()` `15` verir (`:70`) ama pratikte fontlar `=v` üretiminden gelir;
   `build()`'in varsayılan-Inter graceful fallback'i `priority = 5` (`:159`).

**Varsayılan Inter fallback'i (`:144-162`):** frontend'de **hiç** `/fonts/` içeren yol yoksa
`@font/Inter` `priority 5` ile eklenir. Ayrıca `prepare()` (`:92-113`) özel bir font istenmişse ve
kullanıcı **açıkça** `@font/Inter` dememişse, önceki çalışmadan kalmış varsayılan Inter'i siler.

> **Ölçülen gözlem:** `<proje-a>` `<projeA>FrontendController.php:28` `@font/Inter`'i **açıkça** istediği için
> Inter + Outfit + JetBrains Mono + Space Mono = 4 font basıldı. `<proje-c>` yalnız `@font/Syne`
> istedi → 1 font. `rbncore` `@font/Space Mono` + `@font/JetBrains Mono` → 2 font, Inter düştü.
> `<proje-b>` **hiç** font istemedi → fallback devreye girmeliydi ama sayfada **font link'i yok**
> (§13-B8).

### 4.3 Derleme ve sürümleme (`compile()`, `:289-316`)

```php
$version = AssetConfig::VERSION;                          // '1.0'  (:301)
if ($detected && file_exists($detected->path)) {
    $version = (string) filemtime($detected->path);      // fiziksel dosya varsa (:303)
}
```

- **Fiziksel dosya varsa sürüm = `filemtime`** → dosya değişince URL değişir, tarayıcı önbelleği kırılır.
- Fiziksel dosya bulunamazsa (`$detected === null`) ve yol `http` içermiyorsa ve `font` değilse
  `compile()` `null` döner → asset **sessizce düşer** (`:297-299`).

**Doğrulama:** `filemtime` canlıda görünür —
`rbn-master.css=v1791201628`, `rbn-common.css=v1789055904`, proje `master.css=v1788046898`
(farklı dosyalar, farklı zaman damgaları; hepsi 2026-10 çevresi).

### 4.4 URL üretimi (`generateWebUrl()`, `:318-348`)

| Girdi | Çıktı | Satır |
|---|---|---|
| `@font/<Ad>` | `url('/framework-assets/fonts/<ad>-<kısa-i>)` | `:320-322` |
| `http…` | **olduğu gibi** (CDN, sürümleme yok) | `:324-326` |
| `@fw/RbnCommon/css/rbn-master.css` | `url('/framework-assets/rbncommon/rbn-master.css=v<filemtime>')` | `:335-347` |
| `@project/css/master.css` | `url('/project-assets/css/master.css=v<filemtime>')` | `:343-347` |

**Framework "düzleştirme" kuralı (`:335-342`)** — önemli ve çok sık hata yapılan yer:
`@fw/<küme>/<derin/yol>/<dosya>` → **`/framework-assets/<küme>/<dosya>`**. Alt klasörler **kaybolur**,
yalnız küme (ilk segment, küçük harf) ve dosya adı (küçük harf) kalır.
Tek istisna: küme `images` ise yol **düzleştirilmez**, tam yol küçük harfe iner (`:337-339`).

**Canlı kanıt (düzleştirme gerçekten oluyor):**

```
GET /framework-assets/rbncommon/rbn-master.css=v1791201628       → 200
GET /framework-assets/rbncommon/BebasNeue-Regular.ttf             → 200   (küme + dosya adı)
GET /framework-assets/rbncommon/bebasneue-regular.ttf             → 200   (büyük/küçük harf ayrımı YOK)
GET /framework-assets/images/favicon-rbnauth.svg                 → 200   (images istisnası)
```

Bu, **fiziksel keşfin neden "yapılandırılmış" olduğunu** açıklar: `AssetResolver` (§5.5) düz
yolu bulamazsa küme klasöründe **özyinelemeli arama** yapar (`AssetResolver.php:68-89`).

### 4.5 Sürümleme biçimi: `=v…` **path sonu**, query değil

`generateWebUrl()` (`:347`): `… . "/{$proxy}/{$finalPath}=v{$version}"`.
`AssetController::serve()` (`:48-51`) gelen yolda `=` varsa `explode('=', …)[0]` ile **keser**.

> ⚠️ Elle `<link … ?v=123>` yazan kod bu ayrıştırmayı tetiklemez → **sürümleme çalışmaz**
> ve `?v=` dosya adının bir parçası olur. Doğrulandı:
> `GET /framework-assets/fonts/space-mono=v1.0` **307** döndü (yani `=v1.0` doğru kesildi);
> `?v=` biçiminde yazılan bir elle link için bu belge canlı test yapmamıştır — kod
> (`str_contains($path, '=')`) `?v=`'yi görmez, dolayısıyla **kesinlikle sürümlemez** (§13-B9).

---

## 5. `AssetController` — sunum uçları, font yönlendirmesi, medya vekili

`Core/Render/Controllers/AssetController.php`, 402 satır.

### 5.1 Akış sırası (`serve()`, `:46-158`)

| # | Koşul | Satır | Sonuç |
|---|---|---|---|
| 0 | yolda `=` varsa | 48-51 | sürüm son eki kesilir |
| 1 | `fonts/` ile başlıyor | 55-77 | **307** → `FONT_LIBRARY` URL'si, `exit` |
| 2 | `media/` ile başlıyor | 80-104 | **307** → base64 çözülmüş medya, `exit` |
| 3 | `$isProject` ve `.svg` | 107-110 | `serveVirtualResource()` — SEO resolver'dan SVG |
| 4 | `$isProject` ve `og-image-*` | 124-127 | `serveVirtualOgImage()` (§10) |
| 5 | fiziksel keşif | 130-157 | `AssetResolver` → MIME + `Cache-Control` + gövde, `exit` |

### 5.2 Fiziksel teslim başlıkları (`:150-157`)

```php
header('Content-Type: ' . $contentType);
header('Cache-Control: public, max-age=31536000');   // 1 yıl — :152
header('Content-Length: ' . strlen($content));
header('X-RBN-Source: ' . $detected->source);
```

Gzip **framework değil, web sunucusu** tarafından ekleniyor (`AssetController` `Content-Encoding`
yazmıyor). Canlı kanıt:

```
$ curl -H 'Accept-Encoding: gzip' … /framework-assets/rbncommon/rbn-master.css=v1791201628
HTTP/1.1 200 OK
Content-Type: text/css;charset=UTF-8
Content-Length: 1420          ← sıkıştırılmamış (banner + 19 @import satırı)
Cache-Control: public, max-age=31536000
X-RBN-Source: framework
   (Accept-Encoding: gzip ile) Content-Encoding: gzip
```

> `immutable` **yok** ve `AssetController.php:35-38` bunu bilinçli açıklıyor: bir kez önbelleğe alınan
> 307 hedefi `immutable` ile kalıcı olarak değiştirilemiyordu (cache poisoning). `media/` dalı
> `Cache-Control: private, max-age=300` (`:99-100`).

### 5.3 `decorate()` — içerik imzası

`AssetProvider::decorate()` (`:108-134`): `.css`/`.js` içeriğinin başına 🔱/🏛️/🛰️/⚓/🛡️ banner'ı
eklenir (framework adı + sürüm + kit adı + dosya adı + etiket). Bu yüzden
`rbn-master.css` ham 1 097 bayt iken canlı `Content-Length` **1 420**'dir (banner ≈ 323 bayt).
Ölçülen bu fark, §12'deki boyut tablosunun "sunulan" sütununu açıklar.

### 5.4 `media/<base64>` — dış varlık vekili + `proxy_allowed_hosts`

`AssetController.php:80-104` ve `guvenliProxyHedefi()` (`:180-248`).

Yol biçimi: `/framework-assets/media/<kategori>/<base64url>/<ad>` **veya**
`/framework-assets/media/<base64url>/<ad>`. Kod 2. segmenti base64 kabul eder (`:81-83`).
Base64 varyantı standart değil: `strtr($encodedUrl, '-_', '+/')` — yani **base64url**.

Doğrulama sırası (`guvenliProxyHedefi()`):

1. Kontrol karakteri / CRLF → red (`:189-191`).
2. Ters bölü → `/` normalizasyonu (**[F-01]**, `:196`). Tarayıcı `Location: /\evil.example/x`
   değerini `//evil.example/x` sayar; normalizasyon olmazsa "göreli yol" sanılıp geçerdi.
3. Tek bölü/çizgi ile başlayan **göreli** yol → olduğu gibi geçer (yerel `/storage/...` medya) (`:200-202`).
4. Mutlak hedef → şema beyaz listesi: **yalnız `http`/`https`**; `javascript:`, `data:`, `vbscript:` kapanır (`:205-209`).
5. Ana beyaz liste: `RedirectTrait::guvenliHedef($url) !== '/'` → aktif proje alan adı + grup alan adları
   + doğrulanmış istek host'u (`:217-219`).
6. **Ek yapılandırılabilir liste** — İKİ KAYNAK BİRLİKTE (`:233-244`):
   * `AssetConfig::PROXY_ALLOWED_HOSTS` (`AssetConfig.php:70-74`) — framework varsayılanı **BOŞ**
     (üç satır yorum örnek olarak duruyor).
   * `projectAllowedProxyHosts()` (`:272-310`) → `Config::get('project-settings.proxy_allowed_hosts')`.
     `project-settings.php`'de `has_route_map` açıksa `Config::get`, aktif `project_key` için
     `project-routemap.php → view_mapping` değerlerini project-settings üstüne **yazar**
     (`:220-226` yorumu) — yani anahtar `project-routemap.php`'de de verilebilir.
   * Eşleşme: **tam host** ya da **son ek** (`cdn.example.com` → `*.example.com`), `.'.$sonEk`
     ile kontrol edilir ki `notcdn.example.com` yanlış eşleşmesin (`:241`).
   * Geçersiz girdi (dizi değil, şema/port/yol/boşluk/kontrol karakteri, `..`, baş/son tire)
     **sessizce yok sayılır**; hata fırlatmaz, liste genişlemez (`:286-305`).
7. Fail-closed → `null` → **`Location` başlığı üretilmez**, istek normal asset akışına düşer → 404/500.

**Canlı kanıt:**

```
# Beyaz listede olmayan dış hedef
GET /project-assets/media/<b64(https://evil.example/x.png)>   →  HTTP 500   (Location YOK)

# Beyaz listedeki host (<proje-b>: 'i.ytimg.com', 'yt3.ggpht.com')
GET /project-assets/media/<b64(https://i.ytimg.com/vi/x/default.jpg)>
   →  HTTP 307
      Cache-Control: private, max-age=300
      Location: https://i.ytimg.com/vi/x/default.jpg
```

`proxy_allowed_hosts` gerçek tanımları (ölçüldü, `project-routemap.php`):

| Proje | Satır | Host'lar |
|---|---|---|
| `<proje-b>` | `projects/<grup>/<proje>/Core/Config/project-routemap.php:15` | `i.ytimg.com`, `yt3.ggpht.com` |
| `<proje-c>` | `projects/<grup>/<proje>/Core/Config/project-routemap.php:16` | `image.tmdb.org` |
| `<proje-e>` | `projects/<grup>/<proje>/Core/Config/project-routemap.php:16` | `img.youtube.com` |
| `<proje-f>` | `projects/<grup>/<proje>/Core/Config/project-routemap.php:68` | `is1-ssl.mzstatic.com`, `www.8x8.com` |

> **Mühendislik notu:** `AssetConfig::PROXY_ALLOWED_HOSTS` **boş bırakılmış olması doğru davranıştır**
> (Anayasa §9: framework'e proje/müşteri adı yazılmaz). Dış CDN kullanacaksanız listeyi **projenizin**
> `project-routemap.php → view_mapping.<alt-proje>.proxy_allowed_hosts` girdisine ekleyin —
> framework'e değil.

### 5.5 Fiziksel keşif (`AssetResolver`)

`Core/System/Discovery/Clusters/Resources/AssetResolver.php`, `resolve($name, $context)`.

| Adım | Satır | Davranış |
|---|---|---|
| 1 | 24-25 | `path_symmetric($name)`, baştaki `/` ve `\` atılır |
| 2 | 29-40 | **Project first**: `Paths::project()->public()` altında tam yol |
| 3 | 43-66 | **Framework**: `Paths::framework()->assets()` altında tam yol, sonra `Paths::framework()->resources()` altında tam yol |
| 4 | 68-89 | **Düzleştirilmiş yol kurtarma**: ilk segment = küme adayı; küme klasörü `glob` ile büyük/küçük harf duyarsız bulunur, sonra `deepSearch()` (`:113-125`) **özyinelemeli** olarak dosya adını arar |
| 5 | 91-104 | **Akıllı desen**: `^rbn([A-Z][a-zA-Z0-9]+)\.(css|js)$` → `RbnCommon/<css|js>/components/<ad>` |

`resolve()` üç `source` değeri döndürür: `'project'`, `'framework'`, (yoksa) `null`.
`X-RBN-Source` başlığı bunu yazar (`:154`) — canlı: proje CSS → `X-RBN-Source: project`,
framework CSS → `X-RBN-Source: framework`. **Ölçüldü.**

---

## 6. Font sistemi (`AssetFonts`)

`Core/Support/Definitions/Render/AssetFonts.php`, 74 satır.

### 6.1 `FONT_LIBRARY` — 33 slug, hepsi Google Fonts

Ölçüldü (`AssetFonts.php:19-60`): **33** anahtar. Slug kuralı
`AssetController.php:60`: `strtolower(str_replace(' ', '-', $name))` — **tire**, boşluk→tire, küçük harf.

| Kategori (yorum satırı) | Slug'lar (ad → slug) |
|---|---|
| Modern Sans-Serif (`:20`) | `Inter→inter`, `Outfit→outfit`, `Plus Jakarta Sans→plus-jakarta-sans`, `Poppins→poppins`, `Manrope→manrope`, `Geist→geist`, `Roboto→roboto`, `Montserrat→montserrat`, `IBM Plex Sans→ibm-plex-sans` |
| Expressive / Editorial Display Serif (`:31`) | `Fraunces→fraunces`, `Newsreader→newsreader`, `Instrument Serif→instrument-serif`, `DM Serif Display→dm-serif-display`, `Cormorant Garamond→cormorant-garamond`, `EB Garamond→eb-garamond`, `Bodoni Moda→bodoni-moda`, `Cardo→cardo`, `Source Serif 4→source-serif-4`, `Spectral→spectral`, `Crimson Pro→crimson-pro`, `Playfair Display→playfair-display`, `Lora→lora` |
| Brutalist / Poster / Dynamic (`:46`) | `Bricolage Grotesque→bricolage-grotesque`, `Space Grotesk→space-grotesk`, `Syne→syne`, `Anton→anton`, `Big Shoulders Display→big-shoulders-display`, `Tomorrow→tomorrow` |
| Monospace (`:54`) | `JetBrains Mono→jetbrains-mono`, `Space Mono→space-mono`, `Geist Mono→geist-mono`, `IBM Plex Mono→ibm-plex-mono`, `Fira Code→fira-code` |

**Hedef URL biçimi (33/33):** `https://fonts.googleapis.com/css2?family=<Ad:+,@,&ital…>&display=swap`.
Kütüphane URL'si tek tek elle yazılmıştır; `<slug> = ad → kısa-i` dönüşümü yalnız **istem yolunda**
(`AssetController.php:60`) ve **URL üretiminde** (`AssetBuilder.php:321`) uygulanır.

### 6.2 Doğrudan erişim takma adları (`:65-80`)

16 sabit (`INTER`, `OUTFIT`, `GEIST`, `JETBRAINS_MONO`, `SPACE_MONO`, `GEIST_MONO`,
`PLUS_JAKARTA_SANS`, `POPPINS`, `SYNE`, `MANROPE`, `FRAUNCES`, `NEWSREADER`, `INSTRUMENT_SERIF`,
`DM_SERIF_DISPLAY`, `BRICOLAGE_GROTESQUE`, `SPACE_GROTESK`) — yalnız **sekmey tamamlama** için
(`:63` yorumu). Çalışma zamanında kullanılmıyor; `@font/<ad>` metinini kullanmak daha doğru.

### 6.3 Font `<link>`'i neden `<head>`'de **iki** yerde basılmaz?

Font linkleri **SEO** katmanında basılır, asset katmanında **filtrelenir**:

* `AssetProvider::renderStyle()` (`:78-81`) → `/framework-assets/fonts/` içeren yol için `''` döner.
* `SeoProvider::renderLinks()` (`Core/Render/Providers/SeoProvider.php:204-219`) → `assetBuilder->build()`
  sonucunu gezer, `/framework-assets/fonts/` içerenleri `<link rel="stylesheet">` olarak basar.

`renderLinks()` ayrıca **preconnect** ekler (`SeoProvider.php:201-202`):
`https://fonts.googleapis.com` ve `https://fonts.gstatic.com` (`crossorigin`).
Her canlı sayfada bu iki satır doğrulandı.

**Sıralama:** header şablonu (`Resources/Views/RbnCommon/header.rbn.php:14-25`):

```html
<head>
    {!! $appSeoHtml   !!}   ← SEO: preconnect + FONT linkleri + favicon
    {!! $headerAssets !!}   ← Asset: CSS + head script'leri (fontlar ZATEN basıldığı için atlanır)
    {!! $appSchemaHtml !!} {!! $headStateHtml !!} {!! $google_analytics_code !!}
    {!! $google_adsense_code !!} {!! $head_scripts !!}
</head>
```

### 6.4 Panele özel font paketleri (kim ne zaman yükler)

| Paket | Fontlar | Ne zaman |
|---|---|---|
| `fonts_panel` (`AssetBundles.php:36`) | Plus Jakarta Sans, DM Serif Display, JetBrains Mono | `panel` bağlamında **otomatik** |
| `fonts_auth` (`:37`) | Plus Jakarta Sans | `auth` bağlamında **otomatik** |
| `frontend` | — | **otomatik font yok**; `@font/<Ad>` ile `addAsset()` gerekir, hiç istenmezse fallback `Inter` |

**Canlı kanıt — auth sayfası (`https://rbncore.tr.test/rbn-admin`, HTTP 200):**

```
<link href="…/framework-assets/fonts/inter"              ← fallback Inter (priority 5)
<link href="…/framework-assets/fonts/plus-jakarta-sans"   ← fonts_auth paketi
<link href="…/framework-assets/images/favicon-rbnauth.svg=v1787673791
<link href="…/framework-assets/rbncommon/rbn-master.css=v1791201628
<link href="…cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css
<link href="…/framework-assets/rbncommon/rbn-auth.css=v1791205000
<script src="…/framework-assets/rbncommon/rbn-master.js=v1788037859"
```

**jQuery `auth` sayfasında YOK** — doğru (`STACK_MAP['auth']` yalnız `fonts_auth` + `rbn_core_auth`).
Auth sayfasında Bootstrap/BAI/FA da **yok** — `injectCoreStack()` `:259-261` baypası çalışıyor.
`rbn-auth.css` dosyası, eskiden `auth_header.rbn.php` içindeki 12 501 baytlık view-içi `<style>`
bloğunun taşındığı yerdir (`AssetBundles.php:58-60` yorumu).

### 6.5 "Fontu yerelden sunabilir miyim?" — **HAYIR, destek yok**

Kodda `fonts/` rotasının **yalnız** `FONT_LIBRARY` 307 yönlendirmesi vardır (`AssetController.php:55-77`).
Yerel `.ttf`/`.woff2` dosyası için **`fonts/` rotasında hiçbir dal yok**. Ölçüldü:
`GET /framework-assets/fonts/BebasNeue-Regular.ttf` → **500**.

Framework'te **3 yerel TTF** vardır — `Resources/Assets/RbnCommon/fonts/`:
`BebasNeue-Regular.ttf` (61 400), `Montserrat-Bold.ttf` (200 460), `Poppins-Bold.ttf` (155 996).
Bunlar `fonts/` rotasından **değil**, düz framework keşfinden servis edilir:
`GET /framework-assets/rbncommon/BebasNeue-Regular.ttf` → **200** (düzleştirme kuralı, §4.4).

**Yeni yerel font isteyen bir proje için yol haritası (kod değişikliği gerekir):**

1. `Assets/` altına (veya projenin `public/fonts/`) `.woff2` koyun.
2. Proje `master.css` içinde `@font-face { src: url('/project-assets/fonts/<ad>.woff2') }` yazın
   — `frontend-and-ui.md` §2.1 yalnız **Google Fonts `@import`**'unu yasaklar, `@font-face` yazmaz.
3. Ya da framework'e bir dal eklersiniz: `AssetController.php:55` bloğu, slug eşleşmediğinde
   `fonts/<slug>.<ext>` dosyasını `Assets/` altında aramayı dener. Bu bir **motor** değişikliğidir;
   §11.1'de tarif edilen üç adımlı yol tercih edilir.

---

## 7. CSS katmanları

### 7.1 `rbn-master.css` `@import` zinciri (19 dosya, sıra önemli)

`Resources/Assets/RbnCommon/css/rbn-master.css` (29 satır, ham 1 097 bayt) — **yalnız `@import`**.

| # | Satır | Dosya | Katman |
|---|---|---|---|
| 1 | 7 | `rbn-colors.css` | A — Master renk kütüphanesi (24 rainbow + nötr) |
| 2 | 10 | `core/tokens.css` | A — token tanımları |
| 3 | 11 | `core/reset.css` | A — reset |
| 4 | 12 | `core/layout.css` | **A + B** (12-kolon ızgara, `.d-flex`, `.py-*`, `.mb-*`) |
| 5 | 13 | `core/utilities.css` | **B** (tek yönlü `pt-*`/`pb-*`, `gap-*`, `rounded-*`) |
| 6 | 14 | `core/buttons.css` | A (`.rbn-btn*`) |
| 7 | 15 | `core/forms.css` | A (`.rbn-form-*`) |
| 8 | 16 | `core/cards.css` | A (`.rbn-card*`, `.object-*`) |
| 9 | 17 | `core/badges.css` | A (`.rbn-badge*`) |
| 10 | 18 | `core/tabs.css` | A |
| 11 | 19 | `core/dropdowns.css` | A |
| 12 | 20 | `core/elements.css` | A |
| 13 | 21 | `core/surfaces.css` | A |
| 14 | 22 | `core/colors.css` | **B** (`text-*`, `bg-*`) |
| 15 | 23 | `core/responsive.css` | A/B duyarlılık |
| 16 | 26 | `rbn-common.css` | A/B ortak araçlar |
| 17 | 27 | `components/rbnAlert.css` | A |
| 18 | 28 | `components/rbnModal.css` | A |
| 19 | 29 | `components/rbnTable.css` | A |

**Kapanışa girmeyenler (ve neden):** `core/dashboard.css` (yalnız `panel`),
`core/rbn-auth.css` (yalnız `auth`), `optional/*` (opsiyonel paket), `rbn-shield.css`
(hiçbir pakette yok — §13-B1), `rbn-terminal.css` (hiçbir paket/sabit yok — §13-B10).

### 7.2 ÖLÇÜLEN boyut tablosu

Ölçüm yöntemi: `gzip.compress(baytlar, 9)` (Python `gzip` — sunucudaki gzip seviyesiyle birebir
aynı değildir; **göreli** karşılaştırma içindir). Kapanış = 19 dosyanın toplamı.

| Dosya | Ham (bayt) | gzip -9 (bayt) |
|---|---|---|
| `rbn-colors.css` | 6 331 | 2 160 |
| `core/tokens.css` | 3 274 | 960 |
| `core/reset.css` | 866 | 407 |
| `core/layout.css` | 16 978 | 3 078 |
| `core/utilities.css` | 12 075 | 2 725 |
| `core/buttons.css` | 15 510 | 2 972 |
| `core/forms.css` | 12 754 | 2 938 |
| `core/cards.css` | 10 950 | 2 819 |
| `core/badges.css` | 5 182 | 1 626 |
| `core/tabs.css` | 4 178 | 1 263 |
| `core/dropdowns.css` | 3 900 | 1 207 |
| `core/elements.css` | 20 481 | 4 707 |
| `core/surfaces.css` | 8 050 | 2 076 |
| `core/colors.css` | 12 844 | 2 339 |
| `core/responsive.css` | 8 834 | 2 276 |
| `rbn-common.css` | 15 287 | 3 412 |
| `components/rbnAlert.css` | 19 327 | 4 029 |
| `components/rbnModal.css` | 12 660 | 2 745 |
| `components/rbnTable.css` | 18 482 | 4 203 |
| **KAPANIŞ TOPLAM (19 dosya)** | **207 963** | **47 942** |
| *`rbn-master.css` (kapanış dosyasının kendisi)* | *1 097* | *422* |
| `optional/rbn-utilities-extended.css` | 21 642 | 3 932 |
| `optional/rbn-components-extended.css` | 20 746 | 4 446 |
| `core/dashboard.css` (panel) | 16 231 | 3 244 |
| `core/rbn-auth.css` (auth) | 12 853 | 2 692 |
| `rbn-shield.css` (bağsız) | 13 155 | 3 070 |
| `rbn-terminal.css` (bağsız) | 5 189 | 1 388 |

**Sunulan boyut farkı:** canlıda `rbn-master.css` `Content-Length: 1420` (banner ≈ +323 bayt) ve
`Accept-Encoding: gzip` ile `Content-Encoding: gzip` gönderiliyor. Yani **tarayıcı 19 dosyayı
ayrı ayrı değil, `@import` zinciriyle 19 ayrı istek olarak** çekiyor (§13-B11).

### 7.3 Üç katmanlı adlandırma (design-system §4.2 ile uyum)

`design-system.md` §4.2'deki tablo **kod taramasıyla doğrulandı**:

| Katman | Önek | Nerede tanımlanır | Doğrulama |
|---|---|---|---|
| A. Çekirdek bileşen | `rbn-` (her zaman) | `core/*.css`, `components/*.css` | ✅ kapanışta **471** `rbn-` sınıfı |
| B. Yardımcı (utility) | **ön eksiz** (Bootstrap uyumlu) | **yalnız** `core/layout.css`, `core/utilities.css`, `core/colors.css` | ✅ kapanışta **722** ön eksiz sınıf |
| C. Alan (domain) | `rbn-{alan}-` | ilgili bundle dosyası | ✅ `rbn-auth-*`, `rbn-dash-*`, `rbn-admin-*` örnekleri mevcut |

**"Ön eksiz utility yalnız B katmanında tanımlanır" kuralı:** tarama sonucu **tutarlı** —
`core/layout.css`, `core/utilities.css`, `core/colors.css` dışında ön eksiz sınıf tanımı yok
(`.object-cover` gibi `cards.css` içindeki 5 istisna hariç — §13-B12).

**Token düzeni (`core/tokens.css`, 66 satır):** 8 grup hâlinde `--rbn-*`:

| Grup | Satır | Örnekler |
|---|---|---|
| Canvas & Surface | 7-10 | `--rbn-bg-canvas`, `--rbn-bg-surface`, `--rbn-bg-surface-subtle`, `--rbn-bg-surface-hover` |
| Typography & Ink | 13-20 | `--rbn-font-sans`, `--rbn-font-display`, `--rbn-font-mono`, `--rbn-text-display/primary/secondary/muted` |
| Brand & Functional | 23-31 | `--rbn-primary`, `--rbn-primary-hover`, `--rbn-secondary`, `--rbn-accent`, `--rbn-success`, `--rbn-warning`, `--rbn-danger` |
| Borders & Hairlines | 34-36 | `--rbn-border`, `--rbn-border-subtle`, `--rbn-border-strong` |
| **8-Point Spatial Grid** | 39-49 | `--rbn-space-0..16` |
| Geometry & Radius | 52-56 | `--rbn-radius-none/sm/md/lg/pill` |
| 3-Level Elevation | 59-63 | `--rbn-shadow-sm/card/hover/modal`, `--rbn-bg-overlay` |
| On-primary | 64 | `--rbn-text-on-primary` |

**Kritik tasarım kararı — token **çift katmanlı** (`:7-63`):**
`--rbn-*` token'ları **her zaman** bir **proje token'ına geri düşer**:

```css
--rbn-bg-canvas:  var(--bg-canvas,  var(--rbn-slate-50));
--rbn-primary:    var(--primary-color, var(--rbn-blue-600));
--rbn-radius-md:  var(--radius-md, 10px);
```

Yani **proje `variables.css` yalnız `--rbn-*` DEĞİL, ön ek olmadan** (`--bg-canvas`,
`--primary-color`, `--font-display`, `--radius-md`, `--border-color`, `--shadow-card`…) tanım
yapar ve motor onları otomatik toplar. Bu, `design-system.md` §1.3'ün
"`variables.css`: sadece renk/zemin/font/radius token'ları" ile birebir örtüşür.
Örnek: `domains/<marka>/rbncore.tr/css/variables.css` `--primary-color`, `--bg-canvas`,
`--font-mono`, `--text-display` vb. tanımlıyor; `core/tokens.css` bunları `--rbn-*` altına topluyor.

**8-Point ızgarası — iki FARKLI ölçek var (kritik!):**

| Ölçek | Değerler | Kaynak |
|---|---|---|
| `core/tokens.css` `--rbn-space-*` | 0, 4, 8, 12, 16, 20, 24, 32, 40, 48, 64 (`-0..-16`) | `tokens.css:39-49` |
| `.p-*`/`.py-*` **eşlemesi** | `.p-1`=4 · `.p-2`=8 · `.p-3`=16 · `.p-4`=24 · `.p-5`=32 · `.p-6`=48 | `design-system.md` §4.2 + `utilities.css` |
| `docs/gorevler/css-bootstrap-rbn-esleme.json` `sayiOlcegi` | "1=4px 2=8px 3=12px 4=16px 5=20px 6=24px" (`--rbn-space-{0,1,2,3,4,5,6,8,10,12,16}`) | JSON `:14` |

⚠️ **Bu üçü çelişiyor.** `design-system.md` §4.2 `.p-3 = 16px` derken `utilities.css:17` de
`.pt-3 { padding-top: var(--rbn-space-4) }` → 16px ✓ **aynı**; ama eşleme JSON'u `.p-3 → 12px`
diyor. `--rbn-space-3 = 12px`. Yani **`design-system.md` ile eşleme JSON'u birbirini tutmuyor.**
Kod doğrusu: `utilities.css:17` (`--rbn-space-4` = 16px). JSON §13-B13'e yazıldı.

### 7.4 Proje CSS'i (`project-assets/css/*`)

- **Kaynak:** `domains/<proje_yolu>/css/`. Ölçüldü: **21** `master.css`, hepsi `css/` altında.
- **Giriş noktası:** `rbn_core_frontend` paketi `@project/css/master.css`'i **her frontend sayfasında**
  otomatik yükler (`AssetBundles.php:48`).
- **Yükleme sırası:** `build()` comparator kural 1 → `/project-assets/` **daima en son**
  (`AssetBuilder.php:166-174`). Yani proje CSS'i framework'ü ve CDN'leri **ezebilir**.
- **İçerik deseni:** `master.css` bir `@import` girişidir (kopyala-yapıştır örnekler):

  ```css
  /* LUMIÈRE Master CSS Import */
  @import url('variables.css');
  @import url('global.css');
  @import url('common.css');
  @import url('components.css');
  @import url('header-footer.css');
  @import url('hero.css');   …   @import url('responsive.css');
  ```
  ```css
  /* 1. Variables - Renk paleti ve sabitler */
  @import url('variables.css');
  /* 2. Layout & Components */
  @import url('layout.css');
  @import url('m-components.css');
  /* 3. Pages & Tools */
  @import url('m-pages.css');  @import url('m-blog.css');  @import url('m-tools.css');
  ```
- **Kural (frontend-and-ui.md §3):** CSS `domains/<public_path>/css/` altında, tek dosyaya
  doldurma yasağı, `variables.css` yalnız token, `modules/<modül>.css` modül bazlı,
  `master.css` `@import` birleştirici. 21 projenin hepsi bu yapıda.

**Ölçülen uyarı — proje CSS'i büyüyor:** `<proje-c>` `master.css` **13 `@import`** satırı içeriyor.
`design-system.md` §4.2 "proje CSS'i mümkün olduğunca az (hedef: sıfıra yakın)" diyor;
`bootstrap-eksiksizlik.md`/eşleme tablosu bu yönde ilerleme kaydediyor. §13-B14'e not.

### 7.5 `optional/` paketleri (isteğe bağlı)

| Sabit | Satır | Dosya | Ham | gzip |
|---|---|---|---|---|
| `RBN_EXTENDED_UTILITIES_CSS` | `AssetDefinition.php:55` | `optional/rbn-utilities-extended.css` | 21 642 | 3 932 |
| `RBN_EXTENDED_COMPONENTS_CSS` | `AssetDefinition.php:56` | `optional/rbn-components-extended.css` | 20 746 | 4 446 |

**Paket:** `rbnExtended` (`AssetBundles.php:72-74`).

**Kim/nasıl yükler (yorum `:69-71`):**
```php
// Iste: `assets => ['rbnExtended']` (Map) veya `$this->addAsset('rbnExtended')` (Controller).
```

**"HER SAYFAYA OTOMATİK YÜKLENMEZ" doğrulaması (üç bağımsız kanıt):**

1. `rbn-master.css` içinde `optional`/`extended` **geçmişi yok** (Select-String → boş).
2. `rbnExtended` adı **hiçbir proje/framework PHP dosyasında istenmiyor** — yalnız
   `AssetBundles.php`'in kendi 3 satırında geçiyor (`:70`, `:71`, `:72`).
3. Canlı hiçbir sayfanın `<link>` listesinde `rbn-utilities-extended` / `rbn-components-extended`
   **yok** (4 sayfa tarandı: rbncore, <proje-a>, <proje-c>, <proje-b>).
4. Dosyalar yine de **sunulabilir** durumda:
   `GET /framework-assets/rbncommon/rbn-utilities-extended.css=v1` → **200** (düzleştirme).

**Ölçülen çakışma:** opsiyonel paketlerin **387** sınıfı var; bunların **17** tanesi zaten kapanışta
tanımlı (`rbn-form-input` dâhil). Bu 17 sınıf iki yerden geliyorsa son sıralama bazen farklı değer
üretebilir (özellikle `!important` taşıyan B katmanı yardımcıları — §88 kural). §13-B15.

### 7.6 "Bir sınıf motorda var mı?" — nasıl denetlenir

**Yöntem 1 (canlı, tek sınıf):** `curl` ile kapanış dosyasını indirip ara:

```powershell
curl.exe -s -k https://<site>/framework-assets/rbncommon/rbn-master.css=v1 -o E:\tmp\_gecici\m.css
Select-String -Path E:\tmp\_gecici\m.css -Pattern '\.pt-3\b'
```
> Dikkat: `rbn-master.css` **yalnız `@import` satırlarıdır**. Sınıflar içindeki dosyalarda.
> Düzleştirme kuralı sayesinde her biri **doğrudan** adreslenebilir:
> `https://<site>/framework-assets/rbncommon/core/utilities.css` → 200.

**Yöntem 2 (kapsam denetimi — toplu):** eşleme tablosu
`E:\AgentSpace\docs\gorevler\css-bootstrap-rbn-esleme.json` (135 212 bayt,
`$schema: rbn-css-esleme/v1`, `surum: 1.1.0`, `uretim.tarih: 2026-10-05`,
üretici `FW-CSS-MOTOR-2`). Yapısı:

```json
"olcum": {
  "motorKapanisDosyaSayisi": 23,
  "motorKapanisSinif": 1563,
  "motorKapanisRbnOnekli": 575,
  "motorKapanisOneksiz": 988,
  "eslemeGirdisi": 863,
  "hedefTanimli": 863,
  "hedefEksik": 0,
  "eksikBootstrapSiniflari": [],
  "paketler": { "rbn_master": "…", "rbnExtended": "…", "rbn_core_auth": "…" }
}
```
Her giriş `{"<bootstrap-adı>": {"rbn": "<rbn karşılığı>", "durum": "TANIMLI|EKSIK", "motor": "<motor>"}}`
biçimindedir (ör. `:34-38` `"accordion" → rbn-accordion`, `:64-68` `"active" → active`).

⚠️ **JSON'un ölçüm sayıları bu belgedeki bağımsız ölçümle UYUŞMUYOR** (§13-B16):

| Ölçüm | JSON (1.1.0) | Bu belgenin bağımsız ölçümü |
|---|---|---|
| Kapanış dosya sayısı | 23 | **19** |
| Kapanış sınıf | 1 563 | **1 193** |
| `rbn-` önekli | 575 | **471** |
| Ön eksiz | 988 | **722** |
| `hedefEksik` | **0** | — |
| `eksikBootstrapSiniflari` | **`[]` (boş)** | — |

> JSON'daki `hedefEksik: 0` iddiası **kapsam daraltılmadan** anlamsız: bootstrap'ın ~1 300 sınıfının
> tamamı listelenmemiş, yalnız 863 girdi taranmış ve **hepsi** karşılık bulmuş.
> Gerçek eksik sayısı ancak `css_engine=rbn` **VE** panel/auth kapsamına daraltılınca anlamlıdır.
> **Bu belgede kapsam daraltılmış ölçüm yapılmadı** — bu bir sonraki görevin işidir; §13-B16'da
> açıkça bırakıldı. `ölcu-genis.mjs` betiği repoda **bulunamadı** (§13-B17).

**Bu belgenin bağımsız sınıf ölçümü (yöntem ve ham sonuç):**
`\.(-\?[A-Za-z_][A-Za-z0-9_-]*)` deseniyle, kapanıştaki 19 dosyanın **birleşimi**:

```
kapanis sinif sayisi = 1193   (471 rbn- onekli · 722 oneksiz)
opsiyonel paket sinif = 387    (bunlarin 17'si kapanista zaten var)
```

**Bu belgede doğrulanan 25 sınıf (tümü kapanışta):**

| Sınıf | Kapanışta? | Nerede |
|---|---|---|
| `rbn-btn`, `rbn-btn-primary` | ✅ | `core/buttons.css` |
| `rbn-card`, `rbn-card-header` | ✅ | `core/cards.css` |
| `rbn-container`, `rbn-grid-3` | ✅ | `core/layout.css` |
| `rbn-form-input` | ✅ | `core/forms.css` |
| `rbn-badge` | ✅ | `core/badges.css` |
| `rbn-modal` | ✅ | `components/rbnModal.css` |
| `rbn-empty-state`, `rbn-drawer-right`, `rbn-accordion` | ✅ | `core/elements.css` |
| `pt-3` | ✅ | **`core/utilities.css:17`** `.pt-3 { padding-top: var(--rbn-space-4) !important; }` |
| `pb-3`, `mb-0`, `py-2`, `gap-4`, `d-flex`, `rounded`, `rounded-3`, `border-bottom`, `fs-sm`, `fw-bold`, `text-dark` | ✅ | B katmanı |
| `text-primary` | ✅ (⚠️ iki yerde) | `core/colors.css:10` **ve** `core/layout.css:118` — **çakışma**, §13-B18 |
| `object-fit-cover` | ❌ | **YANLIŞ İDDİA** — §13-B19 |
| `<projeA>-tag` | ❌ | motorda yok; projelerin kendi CSS'inde (`domains/<grup>/<site>/css/{components,elements}.css`) |

> **`design-system.md` §4.2'deki `object-fit-cover` örneği motorda YOK.** `core/cards.css:363-367`'de
> `.object-cover` / `.object-contain` / `.object-fill` / `.object-none` / `.object-scale-down` var;
> `.object-fit-cover` **yok** (Select-String tüm `Resources/Assets/**/*.css` → yalnız bu 5 + `cards.css:323`
> bir özellik kullanımı). §13-B19.
> `<projeA>-tag` de motorda yok — `design-system.md` §4.1 standart şablonunda kullanılıyor ama tanımı
> projeye ait. §13-B20.

---

## 8. JS katmanları

### 8.1 `rbn-master.js` — dinamik modül yükleyici (2 740 bayt, 83 satır)

`Resources/Assets/RbnCommon/js/rbn-master.js`. Yükleyen paket: `rbn_master_js` (`universal` →
**her bağlamda otomatik**) ve `rbn_master` (`AssetBundles.php:83-86`).

İki bölüm:

**1. `rbnReady` kuyruk motoru (`:11-54`)**
```js
window.rbnReady = rbnReady;        // :47
window.rbnProcessQueue = …;        // :48
window._rbnQueue = window._rbnQueue || [];   // :14
```
Fonksiyonu `document.readyState === 'loading'` ise kuyruğa alır, değilse **hemen** çalıştırır
(`:20-24`). `DOMContentLoaded`'da kuyruk boşaltılır (`:27-45`).
Kullanım: `rbnReady(function () { … })`.

**2. Dinamik çekirdek modül yükleyici (`:56-81`)** — CSS'in `@import` mantığının JS karşılığı:

```js
const basePath = currentScript ? currentScript.src.substring(0, currentScript.src.lastIndexOf('/'))
                              : '/framework-assets/rbncommon/js';    // :60-62
const coreModules = [
    'core/rbnDom.js', 'core/rbnComponents.js',
    'Utils/rbnUtils.js', 'Utils/rbnBinders.js', 'Utils/rbnFile.js',
    'components/rbnAlert.js', 'components/rbnModal.js', 'components/rbnTable.js',
    'Networking/rbnService.js'
];                                                                    // :64-74
coreModules.forEach(module => { /* <script src=basePath/module>, async=false */ }); // :76-81
```

`script.async = false` (`:79`) sıralı çalıştırma garantisi verir — **`rbnDom.js` → `rbnComponents.js`
→ bileşenler** sırası önemlidir.

**Kapanış dışında kalan JS'ler (dinamik yüklenmez):**
`Utils/rbnAi.js` (15 207), `Networking/DebugBridge.js` (11 196), `components/rbnCharts.js` (22 683).
`rbnCharts` `rbnCharts` paketiyle **isteğe bağlı**; `rbnAi`/`DebugBridge` **hiçbir paketten yüklenmiyor**
(§13-B1).

**Boyut (ham bayt):** `rbn-master.js` 2 740 · `rbnDom.js` 16 965 · `rbnComponents.js` 14 369 ·
`rbnUtils.js` 3 201 · `rbnBinders.js` 23 711 · `rbnFile.js` 7 599 · `rbnAlert.js` 20 453 ·
`rbnModal.js` 20 389 · `rbnTable.js` 28 272 · `rbnService.js` 14 152 · `rbnCharts.js` 22 683 ·
`rbnAi.js` 15 207 · `DebugBridge.js` 11 196 · `rbnAdmin.js` (RbnAdmin) · `rbnAdminApp.js` (RbnAdmin).
**Yalnız `rbn-master.js` kapsamının toplamı (9 dosya): 151 852 bayt** — yani ilk `<script>` 2 740 bayt,
kalan 8 dosya çalışma anında indirilir. §13-B11.

### 8.2 `data-rbn-*` ve `data-bs-*` öznitelikleri

**Kritik düzeltme:** "`data-bs-*` = Bootstrap JS" **yanlıştır.** Framework'ün kendi JS'i
`data-bs-*` özniteliklerini **okur** — `rbnComponents.js` ve `rbnModal.js` içinde
`data-bs-toggle` / `data-bs-target` / `data-bs-parent` / `data-bs-dismiss` /
`data-bs-backdrop-static` **kendi seçicileriyle** sorgulanır:

| Öznitelik | Okuyan yer |
|---|---|
| `[data-bs-toggle="dropdown"]` | `core/rbnComponents.js:88` |
| `[data-bs-toggle="collapse"]` | `core/rbnComponents.js:145` |
| `data-bs-target` | `core/rbnComponents.js:149,187,245` |
| `data-bs-parent` | `core/rbnComponents.js:156` |
| `[data-bs-toggle="offcanvas"]` | `core/rbnComponents.js:184` |
| `[data-bs-dismiss="offcanvas"]` | `core/rbnComponents.js:214` |
| `[data-bs-toggle="tab"], [data-bs-toggle="pill"]` | `core/rbnComponents.js:241` |
| `[data-bs-dismiss="modal"]` | `components/rbnModal.js:35` |
| `data-bs-backdrop-static` | `components/rbnModal.js:44` |
| `data-bs-title` (tooltip) | `core/rbnDom.js:173` (üretir) |

Ayrıca `rbnComponents.js:38` `data-bs-toggle="dropdown"` özniteliğini **kendisi üretir**
(native `<select>` → custom dropdown dönüşümünde, `:35-40`).

**Yani:** `css_engine='rbn'` projelerinde **Bootstrap JS yüklü değil** ama **Bootstrap'ın `data-bs-*`
sözleşmesi motor tarafından geriye uyumlu olarak uygulanıyor.** Ölçüldü: framework JS'inde
`data-bs-` eşleşmesi **21 satır**, hepsi yukarıdaki okuma noktaları.

**Desen (her yerde aynı):** `data-bs-X` **önce**, `data-rbn-X` **fallback**:

```js
// rbnComponents.js:88
const toggle = e.target.closest('[data-bs-toggle="dropdown"], [data-rbn-toggle="dropdown"], .dropdown-toggle');
// rbnComponents.js:149
const targetSelector = btn.getAttribute('data-bs-target') || btn.getAttribute('data-rbn-target') || btn.getAttribute('href');
// rbnModal.js:26
const trigger = e.target.closest('[data-rbn-modal="true"], [data-rbn-toggle="modal"], .notification-modal-btn');
// rbnModal.js:35
const closeBtn = e.target.closest('[data-bs-dismiss="modal"], [data-rbn-dismiss="modal"], .btn-close, .modal-close-btn');
```

> ⚠️ `rbnModal.js:24` **yorum satırı** `[data-rbn-modal="true"], [data-bs-toggle="modal"], [data-rbn-toggle="modal"]`
> diyor ama `:26`'daki gerçek seçici **`[data-bs-toggle="modal"]` içermiyor**. Yorum kod ile uyuşmuyor.
> §13-B21.

**`data-rbn-*` envanteri (framework JS'i, 43 benzersiz):**
`data-rbn-toggle` (11), `data-rbn-otp` (6), `data-rbn-table-reset` (5), `data-rbn-table-search` (5),
`data-rbn-ai` (4), `data-rbn-chart` (4), `data-rbn-preview` (3), `data-rbn-sort-select` (3),
`data-rbn-table-filter` (3), `data-rbn-target-table` (3), `data-rbn-filter-col` (3),
`data-rbn-target` (3), `data-rbn-form` (3), `data-rbn-password-toggle` (3), `data-rbn-dismiss` (3),
`data-rbn-file-label` (2), `data-rbn-filter-attr` (2), `data-rbn-table-export` (2),
`data-rbn-table-sort` (2), `data-rbn-cascade` (2), `data-rbn-confirm` (2), `data-rbn-mask` (2),
`data-rbn-copy` (2), `data-rbn-password-generate` (2), `data-rbn-geo` (2), `data-rbn-modal` (2),
`data-rbn-redirect`, `data-rbn-sortable`, `data-rbn-theme-icon`, `data-rbn-status-toggle`,
`data-rbn-enhanced`, `data-rbn-select`, `data-rbn-drawer`, `data-rbn-parent`, `data-rbn-ajax`,
`data-rbn-bulk-action`, `data-rbn-tooltip`, `data-rbn-scroll-top`, `data-rbn-password-length`,
`data-rbn-type`, `data-rbn-action`, `data-rbn-theme-toggle`, `data-rbn-title`.

### 8.3 Dış kütüphaneler: kim ne zaman yükler

| Kütüphane | Yükleme yolu | Otomatik mi? | Ölçülen canlı |
|---|---|---|---|
| **jQuery** 3.7.1 | `jquery` paketi → `STACK_MAP['panel']` | **yalnız `panel`**, otomatik | `rbn-admin` (auth) sayfasında **yok** ✓; frontend'de **yok** ✓ |
| **SortableJS** 1.15.2 | `sortable` paketi → `STACK_MAP['panel']` | **yalnız `panel`**, otomatik | — |
| **Bootstrap JS** 5.3.2 | `bootstrap` paketi → `STACK_MAP['universal']` | evet, **ama `rbn`/panel/auth'ta baypas** | `<proje-b>` ve `<proje-c>` sayfalarında **var**; `rbncore`/`<proje-a>`/`rbn-admin` sayfasında **yok** ✓ |
| **AOS** 2.3.4 | `aos` paketi | **hayır** — `addAsset('aos')` gerekir | `<proje-b>` (<projeB>/<projeB2> `addAsset('aos', …)`) ve `rbncore` (`MainFrontendController.php:35`) sayfalarında **css+js** var; `<proje-a>`/`<proje-c>` sayfasında **yok** ✓ |
| **Chart.js** (`rbnCharts` paketi) | `rbnCharts` paketi | **hayır** | `<proje-a>` `<alt-site-a>` sayfasında isteniyor (`<projeA>FrontendController.php:28`); ana `<site-a>` sayfasında **yok** ✓ |
| **Google Tag Manager** | ⚠️ **paket/AssetDefinition YOK** | — | `<proje-b>` ana sayfasında `<script src="https://www.googletagmanager.com/gtag/js?id=G-J49KXF3T2L">` **var**; bu asset sisteminden **gelmiyor** (§13-B4) |

### 8.4 Bootstrap JS olmadığında ne olur (davranış)

`rbn-master.js` → `rbnModal.js` başlıklı yorumu (`:1-8`) bunu açıkça söyler:
*"100% Native Pure JavaScript (Zero Bootstrap JS & Zero jQuery Dependency)"*.
Sonuç: `data-bs-toggle="modal|dropdown|tab|collapse|offcanvas"`, `data-bs-dismiss`,
`data-bs-target`, `data-bs-parent` **çalışır**; **ama** Bootstrap'ın JS API'si
(`new bootstrap.Modal()`, `bootstrap.Tooltip()`, `bootstrap.Dropdown` nesneleri) **yoktur**.
Tooltip/modal **kendi** motoruyla çalışır (`rbnDom.js` tooltip, `rbnModal.js` modal).

---

## 9. İkon sistemi

### 9.1 Üç ayrı ikon ailesi, üç ayrı kaynak

| Aile | Kaynak | Yükleme | Ölçülen kullanım (view satırı) |
|---|---|---|---|
| **Remix Icon** (`ri-*`) | CDN `remixicon@4.2.0` | `remix_icon` paketi → `STACK_MAP['universal']`, **her sayfada otomatik** | **2 527** ✅ fiili standart |
| **Bootstrap Icons** (`bi-*`) | CDN `bootstrap-icons@1.11.3` | `bootstrap_icons` paketi → `universal`, ama `rbn`/panel/auth'ta **baypas** | **726** — bu projelerde **boş** (§13-B5) |
| **Font Awesome** (`fas`/`fa*`) | CDN `font-awesome@6.5.1` | `font_awesome` paketi → `universal`, `rbn`/panel/auth'ta **baypas** | **0** (view `class` özniteliğinde) |
| **flag-icon-css** | CDN `flag-icon-css@6.6.6` | `flag_icon` paketi — **hiçbir `STACK_MAP` girdisinde YOK** | 2 isteyici (`<projeC>FrontendController.php:23`, `PanelMap.php`) |

> **Frame notu:** `IconLibrary`'nin icon listeleri `bi-`/`fas` **klasör adları** olarak kullanılır
> (`IconLibrary.php:34-42`: `bootstrap_icons.php`, `font_awesome.php`). Bu, `rbn` motorunda
> Bootstrap Icons CDN'i **yüklenmediği** halde panel ikon listelerinin hâlâ `bi-*` sınıfları
> döndürebileceği anlamına gelir — panel bağlamı `bootstrap_icons`'u baypas ediyor.
> Ölçülen kanıt: `rbn-admin` login sayfasında `bi-*` veya `fa*` CDN linki **yok**
> (auth bağlamı baypası). §13-B5.

### 9.2 `IconLibrary` / `SmartIconCategories`

`Core/Support/Bridges/Helpers/Library/IconLibrary.php` (105 satır) — kayıt:
`Core/System/Registries/RegistryMap/SystemResourceMapTrait.php:31` → `'icons' => IconLibrary`.

| Metot | Satır | Ne yapar |
|---|---|---|
| `all()` | 32-43 | `Icons/Internal/bootstrap_icons.php` + `font_awesome.php` (`solid`+`regular`+`brands`) → `IconCollection` |
| `category(string $categoryName)` | 51-78 | 7 kategoriye yönlendirir: `admin`, `developer`, `popular`, `sidebar`, `menu`, `form`, `status` |
| `search(string $keyword)` | 83-86 | `all()` içinde arama → ilk eşleşen `IconCollection` |
| `match(string $keyword, string $default = 'bi-circle')` | 99-102 | `SmartIconCategories::matchKeyword()` → anahtar kelime → ikon sınıfı |

Sabitler `BOOTSTRAP='bootstrap'`, `FONTAWESOME='fontawesome'`, `ALL='all'` (`:19-21`) —
**dokümanlama düzeyinde**; metotlarda kullanılmıyor.

`SmartIconCategories` (`Icons/SmartIconCategories.php`) her kategoriyi
`'sınıf' => ['label' => 'Türkçe etiket', 'emoji' => '…']` biçiminde döndürür
(ör. `'bi bi-speedometer2' => ['label' => 'Dashboard', …]`, `:23`).
Panel ikonları `ri-*` olarak da tanımlı (`PanelMap.php`: `'icon' => 'ri-dashboard-line'`,
`'icon' => 'ri-line-chart-line'`, `'icon' => 'ri-terminal-box-line'`).

### 9.3 "`icon()`-benzeri" yardımcı var mı?" — **framework seviyesinde YOK**

`icon()` adlı bir global yardımcı **framework'te yok**. Tarama:
`Get-ChildItem -Recurse -Include *.php -Path rbnframework` + `function icon` → tek eşleşme
`Bundles/RbnSuite/RbnAdmin/Providers/SocialMediaProvider.php:82` (`public function icon(): string` —
bir **sınıf metodu**, view helper değil, sosyal medya sağlayıcısının ikon alanı).

**Kullanılan desen (view'larda):** düz sınıf adı —
`<i class="ri-close-line"></i>`, `<i class="ri-arrow-down-s-line opacity-50"></i>`
(`rbnComponents.js:40,54`).

**Yardımcı sınıflar:** `IconCollection` (`Icons/IconCollection.php`), `SmartIconCategories`,
`ColorHelper`, `FormatHelper`, `TextHelper`, `MetaSeoHelper` — hepsi `Core/Support/Bridges/Helpers/`
altında. Global fonksiyon dosyaları: `Helpers/Global/{http,project,support,system}_helpers.php`
+ `Helpers/rbn_helpers.php`.

### 9.4 SVG ikon varlıkları

Framework'te **statik SVG ikon dosyası yok** (`Get-ChildItem -Recurse -Include *.svg -Path
rbnframework\Resources\Assets` → boş). SVG'ler **çalışma anında üretilir**:

* **Sanal logo/favicon:** `AssetController::serveVirtualResource()` (`:409-453`) —
  `resolver('seo')->getVirtualResourceRaw($name)` → yoksa `SeoConfig::LOGO_SVG` fallback'i
  (`:416-419`). `data:image/svg+xml;base64,…` önekini temizler (`:422-433`).
* **Sanal OG görseli:** `serveVirtualOgImage()` (`:376-407`) — §10.

---

## 10. Favicon / OG görseli konvansiyonu (`AssetConvention`)

`Core/Support/Definitions/Render/AssetConvention.php`, 228 satır.
Dosya dokümanı (`:22-32`) "dört kapı"nın aynı kararı vermek zorunda kaldığını ve bu sınıfın
**tek doğruluk kaynağı** olduğunu anlatıyor: `SeoResolver`, `AssetController`,
`RedirectManager::selfHealingAssets()`, `SchemaResolver`.

### 10.1 Ad kalıbı

| Varlık | Kural | Uzantılar (öncelik sırasıyla) | Konum |
|---|---|---|---|
| favicon | `favicon-<project_key>.<ext>` | `svg`, `png`, `ico` (`:43`) | `images/` |
| og image | `og-image-<project_key>.<ext>` | `png`, `jpg`, `webp` (`:46`) | `images/` |

Anahtar `normalizeKey()` (`:219-228`) ile normalize edilir: küçük harf, trim, `[a-z0-9_-]{1,64}`
deseni. Uymayan anahtar `null` → **bozuk ad (`og-image-.png`) üretilmez**.

### 10.2 Aday listeleri (geriye uyum, "son geri dönüş")

`faviconCandidates()` (`:62-83`), öncelik sırasıyla:

1. `images/favicon-<key>.{svg,png,ico}`   ← **KURAL**
2. `images/<key>.{svg,png,ico}`          ← eski (anahtarlı, kural dışı ad)
3. `images/favicon.{svg,png,ico}`        ← eski (anahtarsız)

`ogImageCandidates()` (`:90-107`):

1. `images/og-image-<key>.{png,jpg,webp}` ← **KURAL**
2. `images/og-image.{png,jpg,webp}`       ← eski

`findFavicon()` / `findOgImage()` (`:115-130`) listede **gerçekten var olan ilk** dosyayı döndürür
(`firstExisting()`, `:235-250`). **`findOgImage()` dosya yoksa `null` döner** → `og:image` meta
etiketi **hiç üretilmez** (uydurma görsel yasak, `:120-126`).

### 10.3 Sanal OG görseli rotası

`/project-assets/og-image-<key>.<png|jpg|webp>` → `AssetController::serveVirtualOgImage()` (`:376-407`).

* Tanıma: `isVirtualOgImageName()` (`:183-198`) — `VIRTUAL_OG_PATTERN`
  `/^og-image-([A-Za-z0-9_-]{1,64})\.(png|jpg|webp)$/` (`:55`); ayrıca `/`, `\`, `..` ve kontrol
  karakteri reddi.
* Çözümleme: `resolveVirtualOgImage()` (`:143-178`) —
  1. `Paths::isInitialized()` değilse `null` (sessiz fallback, `:256-265`).
  2. `public/images/<ad>` fiziksel dosya kontrolü.
  3. **`realpath()` kök sızırma kontrolü** (`:167-177`): çözülen yol `public/images/` altında mı?
     `rtrim(realpath(public/images), '/') . '/'` öneki ile başlıyor mu — sembolik bağlantı ve `..`
     kaçışı bu adımda kapanır (**[R-14]**).
* MIME: `ogImageMimeType()` (`:203-213`) — `png→image/png`, `jpg/jpeg→image/jpeg`, `webp→image/webp`,
  bilinmeyen → `null` → 404.
* Başlıklar: `Cache-Control: public, max-age=31536000` (`:398`), `X-RBN-Source: ProjectConvention` (`:400`),
  `X-RBN-Resource: <guvenliBaslikDegeri()>` (`:403`).
* `guvenliBaslikDegeri()` (`:351-364`) — kontrol karakteri ayıklanır, `basename()` ile sınırlandırılır,
  255 bayta kırpılır, boşsa `'bilinmiyor'`.

**Bu dal 2026-10-05'te eklenmiş** (`:112-123` yorumu): öncesinde yalnız `.svg` açıktı, raster OG
görselleri fiziksel keşfe düşüyor ve `og:image` **her sitede ölü URL** üretiyordu.

**Canlı kanıt:**

| Site | `<link rel="icon">` | `og:image` |
|---|---|---|
| `rbncore.tr.test` | `/project-assets/favicon-rbncore.svg` | `…` |
| `<site-a2>.test` | `/project-assets/favicon-<proje-a>.svg` | `…` |
| `<site-b>.test` | `/project-assets/favicon-<proje-b>.svg` | `/project-assets/og-image-<proje-b>.png` |
| `<site-c>.test` | `/project-assets/images/favicon-<proje-c>.png=v1782671388` | `…` |
| `rbn-admin` (auth) | `/framework-assets/images/favicon-rbnauth.svg=v1787673791` | `…` |

> **Fark gözlemlendi:** Yeni siteler `AssetConvention` kuralına uyuyor
> (`favicon-<key>.svg`, dosya kökünde), `<proje-c>` ise **proje `images/` klasöründe** ve
> **sürüm son ekli** (`=v1782671388`) — yani `SeoResolver` meta etiketi üretirken fiziksel yolu
> doğrudan basıyor, sanal `AssetController` rotasını kullanmıyor. İki yol birlikte çalışıyor;
> `AssetConvention`'ın tek doğruluk kaynağı olma hedefi **tam kapanmamış** (§13-B22).
> `<proje-b>` `og:image` ise sanal rotayı kullanıyor ✓ — yani yeni kural uygulanıyor.

**Favicon `<link>` basımı:** `SeoProvider::renderLinks()` (`:221-232`) — `type` uzantıdan türetilir
(`.png→image/png`, `.svg`/`data:image/svg`→`image/svg+xml`, aksi halde `image/x-icon`).

---

## 11. "Bir kaynağı nasıl ister / değiştiririm" tarifleri

### 11.1 Yeni bir CDN kütüphanesi ekleme (3 adım — paket üzerinden)

**Adım 1 — sabit** (`Core/Support/Definitions/Render/AssetDefinition.php`, CDN bloğuna):

```php
public const CHARTJS_JS = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js';
```

> **Kural:** sürümü **URL'ye yaz**, açıklama metni Türkçe, sabit adı İngilizce
> (`.agents/rules/core-architecture.md` §10). Proje/müşteri adı **yazma** (§9).

**Adım 2 — paket** (`AssetBundles.php`, `BUNDLES`):

```php
'chartJs' => ['scripts' => [AD::CHARTJS_JS]],
```

**Adım 3 — iste** (2 yol):

```php
// (a) Controller — yalnız o sayfaya özel
$this->addAsset('chartJs');

// (b) Panel/Map alt modülü — kalıcı olarak o modülde
// PanelMap.php:  'assets' => ['chartJs'],
```

**Asla yapma:** view/layout'da `<script src="…">` elle yazma, `<style>`/`style=` kullanma
(`frontend-and-ui.md` §1, `design-system.md` §5).

**Bir paket `STACK_MAP`'e girerse** her sayfaya otomatik yüklenir — bu **bilinçli** bir karar olmalıdır
(§12'deki dış istek sayısına bak).

### 11.2 Yerel CSS/JS dosyası ekleme (proje tarafı — kod değişikliği gerekmez)

```php
// projects/<proje>/Modules/Frontend/<Modül>/Controllers/<X>FrontendController.php
protected function onAfterBoot(): void
{
    parent::onAfterBoot();

    // Yalnız SAYFAYA ÖZEL varlık — çekirdek asset'ler (rbn-master.css,
    // rbn-common.css, rbnAlert.css, rbn-master.js) zaten otomatik geliyor.
    $this->addAsset('@font/Syne');                 // font: @font/<KütüphaneAdı>
    $this->addAsset('@project/css/modules/blog.css'); // proje CSS'i → EN SON
    $this->addAsset('@project/js/landing.js');        // proje JS'i  → footer
}
```

* Dosyayı `domains/<proje_yolu>/css/` veya `js/` altına koy (frontend-and-ui.md §3).
* `.js` → **footer**'a basılır (`renderInHead=false`, `AssetService::renderFooter()`, `:71-85`).
* CDN olmayan tek yol `renderInHead: true`: sabiti dizi olarak tanımla
  (`['path' => '…', 'renderInHead' => true]`), tıpkı `JQUERY` (`AssetDefinition.php:26`).
* **Çekirdek asset'leri `addAsset()` ile tekrar ekleme** (`frontend-and-ui.md` §2) — mükerrer `<link>`.

### 11.3 `css_engine` seçimi

```php
// projects/<proje>/Core/Config/project-routemap.php
'view_mapping' => [
    '<alt-proje-key>' => [
        'module'     => '<Modül>',
        'domain'     => '<site.example>',
        'css_engine' => 'rbn',     // ← 'rbn' yaz veya YAZMA
    ],
],
```

| Değer | Sonuç |
|---|---|
| `'rbn'` | Bootstrap + Bootstrap Icons + Font Awesome **0 KB** baypas; `rbn-master.css` en üstte; `common.css`/`rbnAlert.css` mükerrerlik filtresi devrede |
| yazılmamış veya `'bootstrap'` | Bootstrap 5.3.2 + BAI + FA CDN'den; `rbn-master.css` **yüklenmez**; `rbn-common.css` + `rbnAlert.css` ayrı ayrı yüklenir |

**Geçiş yaparken:** (1) `design-system.md` §1.2 gereği view'ları `rbn-*` sınıflarına dönüştür;
(2) `frontend-and-ui.md` §4.1 uyarısı: `pb-*`/`pt-*` **artık tanımlı** (`core/utilities.css`) —
eski kural geçersiz, geliştiriciyi alıkoymasın; (3) `bi-*` ikonları `ri-*`/`rbn-*` ile değiştir
(§13-B5); (4) `docs/gorevler/css-bootstrap-rbn-esleme.json`'u güncelle.
**Dönüşü olmayan nokta:** projeye özel CSS'te tanımlı Bootstrap'a özgü sınıflar kaybolabilir.

### 11.4 İsteğe bağlı CSS paketi isteme

```php
$this->addAsset('rbnExtended');           // frontend Controller
// veya PanelMap/*Map.php:  'assets' => ['rbnExtended'],
```

* Eklenen: `optional/rbn-utilities-extended.css` (21 642 B) + `optional/rbn-components-extended.css` (20 746 B).
* Ham +42 388 B, gzip -9 +8 378 B (yalnız CSS; §7.2).
* Yüklenme sırası: `@project-assets` en son kuralı **bu dosyalar için geçerli değil**
  (`/framework-assets/…`), yani önce framework çekirdeği, sonra opsiyonel paket gelir.
* ⚠️ 387 sınıfın 17'si kapanışta zaten var (§7.5) — paketi eklerken **özgüllük** (specificity)
  ve `!important` (B katmanı kuralı, `design-system.md` §88) çatışmasına dikkat.

### 11.5 Font ekleme

1. `AssetFonts::FONT_LIBRARY` içine `'Yeni Font' => 'https://fonts.googleapis.com/css2?family=…&display=swap'`
   **veya** mevcut listeden seç (§6.1).
2. `$this->addAsset('@font/Yeni Font')`.
3. CSS'te `@import` ile font **yasak** (`frontend-and-ui.md` §2.1) — yalnız `@font-face` (§6.5).
4. Panel/auth'ta `fonts_panel`/`fonts_auth` paketlerini `AssetBundles.php:36-37`'den düzenle.

### 11.6 Bir sınıf motorda **yok** gibi görünüyorsa — teşhis sırası

1. `css_engine` doğru mu? (`rbn` değilse `.rbn-*` sınıflar **yüklenmiyor** → §4.1)
2. Sınıf B katmanında mı? → `core/layout.css`, `core/utilities.css`, `core/colors.css` (§7.3)
3. Opsiyonel pakette mi? → `optional/*.css` (§7.5)
4. Proje CSS'inde tanımlı mı? → `domains/<proje>/css/*.css` (bu **kural ihlali** — `design-system.md` §4.2)
5. Adlandırma: `.pt-3` **var**, `.pt-md-3` **yok** (kırılma son eki kaynakta önce:
   `rounded-3`, `fs-sm`, `rbn-btn-outline-danger` — JSON `:13`)
6. `!important` çatışması: B katmanı `!important` yazıyor (`utilities.css:17`), A katmanında **yasak**
   (`design-system.md` §88) → yeni A sınıfı B sınıfına **kaybeder**.
7. Doğrulama: §7.6 Yöntem 1 (canlı `curl`) veya eşleme JSON'u.

---

## 12. Performans notları (yalnız ölçülen)

### 12.1 CSS kapanışı boyutu (ham + gzip -9)

Bkz. §7.2 tam tablo. Özet:

| Ölçüm | Değer |
|---|---|
| `rbn-master.css` kapanışı (19 dosya) | **207 963 B ham · 47 942 B gzip -9** |
| `rbn-master.css` dosyasının kendisi | 1 097 B ham · 422 B gzip |
| Canlı `Content-Length` (banner dâhil) | **1 420 B** |
| `rbnExtended` (2 dosya, opsiyonel) | 42 388 B ham · 8 378 B gzip |
| `core/dashboard.css` (yalnız panel) | 16 231 B ham · 3 244 B gzip |
| `core/rbn-auth.css` (yalnız auth) | 12 853 B ham · 2 692 B gzip |
| `rbn-shield.css` (bağsız) | 13 155 B ham · 3 070 B gzip |

### 12.2 Bir sayfa için dış istek sayısı (canlı `<link>`+`<script>` taraması)

| Site | `css_engine` | **Dış host isteği** | Yerel varlık isteği | Toplam `<link>`+`<script>` |
|---|---|---|---|---|
| `<site-a2>.test` | `rbn` | **2** (`fonts.gstatic.com` preconnect, `remixicon.css`) | 9 | 13 |
| `rbncore.tr.test` | `rbn` | **4** (`fonts.gstatic.com`, `remixicon.css`, `aos.css`, `aos.js`) | 6 | 12 |
| `<site-c>.test` | bootstrap | **7** (`fonts.gstatic.com`, bootstrap, BAI, FA, remixicon, flag-icon, bootstrap.js) | 8 | 17 |
| `<site-b>.test` | bootstrap | **9** (+ **GTM `googletagmanager.com`**) | 6 | 17 |

`preconnect` `fonts.googleapis.com` yalnız `<link rel="preconnect">` — istek sayımına
`fonts.gstatic.com` dahil, `fonts.googleapis.com` hariç tutuldu (istek açan değil, hazırlık).

> **Ölçülen fark:** `css_engine='rbn'` projeleri 2-4 dış istekle, bootstrap projeleri 7-9 dış istekle
> çalışıyor. Fark: Bootstrap CSS (~230 KB ham) + BAI (~100 KB) + FA (~100 KB) + bootstrap.js (~80 KB).
> `css_engine='rbn'` bunların **tamamını 0'a** indiriyor.

### 12.3 HTTP önbellekleme

| Varlık | `Cache-Control` | `immutable` | Kaynak |
|---|---|---|---|
| Fiziksel CSS/JS/görsel | `public, max-age=31536000` (1 yıl) | ❌ | `AssetController.php:152` |
| Sanal SVG (favicon/logo) | `public, max-age=31536000` | ❌ | `AssetController.php:440` |
| Sanal OG görseli | `public, max-age=31536000` | ❌ | `AssetController.php:398` |
| `media/<base64>` 307 | `private, max-age=300` | ❌ (bilinçli kaldırıldı) | `AssetController.php:99-100`, `:35-38` |
| Font 307 | *(başlık yazılmıyor)* | — | `AssetController.php:73` |

Sürümleme `=v<filemtime>` ile 1 yıllık önbelleği güvenli kılar; `immutable` bilinçli **yok**
(bir kez önbelleğe alınan 307 hedefi kalıcı değişemiyordu — cache poisoning).

### 12.4 Sıkıştırma

`AssetController` `Content-Encoding` **yazmaz** — gzip web sunucusunda. Canlı kanıt:
`Accept-Encoding: gzip` → `Content-Encoding: gzip`; `Content-Length: 1420` (sıkıştırılmamış
beyan, transfer sıkıştırılmış).

---

## 13. BILINMEYENLER

Kodla **doğrulanamayan** ya da **çelişen** noktalar. Her satır: gözlem + kanıt + öneri.

| # | Konu | Gözlem | Kanıt |
|---|---|---|---|
| **B1** | Pakete bağlı olmayan sabitler | `RBN_SHIELD_CSS`, `RBN_SERVICE`, `RBN_DEBUG_BRIDGE`, `RBN_AI`, `RBN_UTILS`, `RBN_BINDERS`, `RBN_ALERT_JS`, `RBN_MODAL_JS` **hiçbir `BUNDLES` paketine girmiyor.** JS olanlar `rbn-master.js` `coreModules` dizisiyle dinamik yükleniyor (kabul edilebilir); **CSS olan `RBN_SHIELD_CSS` ve JS olan `RBN_SERVICE`/`DebugBridge`/`rbnAi` hiçbir yoldan yüklenmiyor.** | `AssetDefinition.php:47,61,62,69,67,68,63,65` ↔ `AssetBundles.php:32-92` (çapraz arama). `rbn-service.js` yalnız dinamik listede (`rbn-master.js:73`). |
| **B2** | `project` paketi | `'project' => ['styles' => ['@project/master.css']]` (`AssetBundles.php:90`) kullanılmıyor **ve** yolu yanlış: 21 projenin `master.css`'i `css/` altında, bu paket proje **kökünü** bekliyor. `rbn_core_frontend` zaten `@project/css/master.css` yüklüyor (`:48`). | `Get-ChildItem -Recurse -Filter master.css -Path domains` → 21 dosya, hepsi `*/css/master.css`; kök seviyede **0**. `'project'` adı hiçbir yerde istenmiyor. |
| **B3** | Asset 404'leri **500** dönüyor | `AssetController` "bulunamadı" için `abort(404, …)` çağırıyor; `Shield::abort()` `throw new Exception` → istisna işleyicisi 500 üretiyor. Tarayıcı/SEO tarafında yanlış sinyal; CDN negatif önbellekleme de bozulur. | `AssetController.php:135` · `ErrorHandlingTrait.php:65-68` · `Shield.php:86-89`. Canlı: `/framework-assets/rbncommon/yok.css` → **500**; `/yok-boyle-bir-sayfa-xyz` → **404**. **Öneri:** `Shield::abort()` `http_response_code($code)` + `exit` (veya bu dalda doğrudan `404`) — *ayrı görev, bu belgede kod değiştirilmedi.* |
| **B4** | Google Tag Manager asset sisteminden **gelmiyor** | `<proje-b>` ana sayfasında `<script src="googletagmanager.com/gtag/js?id=G-J49KXF3T2L">` var; `AssetDefinition`/`AssetBundles`/`addAsset` içinde **GTM yok** (`Select-String googletagmanager` → `projects/**` 0 eşleşme). Yani header'daki `$google_analytics_code` kanalından geliyor — **bu belgenin kapsamı dışında bir yol.** | `<proje-b>-home.html` `<script>` listesi (canlı). `Get-ChildItem -Recurse -Include *.php -Path projects \| Select-String googletagmanager` → 0. |
| **B5** | `bi-*` ikonları `rbn` motorunda **boş** | 726 view satırı `bi-*` kullanıyor, ama `injectCoreStack()` (`:259`) `bootstrap_icons`'u `rbn`/panel/auth'ta **baypas** ediyor → ikon dosyası indirilmiyor, ikonlar görünmez. `IconLibrary`/`SmartIconCategories` ise **panel** ikon listelerini `bi-*` sınıflarıyla döndürüyor (`IconLibrary.php:34`, `SmartIconCategories.php:23+`) — panel bağlamı da BAI'sız. | Ölçüm: view satırları (`ri-` 2 527, `bi-` 726, `fas-` 0). `rbn-admin` sayfasının `<link>` listesinde BAI CDN linki **yok**. |
| **B6** | `admin_core_css` paketi ölü | `RBN_ADMIN_VARIABLES_CSS` + `RBN_ADMIN_GLOBAL_CSS` paketi hiçbir yerden istenmiyor ve `STACK_MAP`'te yok. | `Select-String "'admin_core_css'"` → yalnız `AssetBundles.php:46`. |
| **B7** | `security` paketi | `'security' => ['scripts' => [AD::RBN_ADMIN_APP]]` (`:87`). 22 eşleşmenin **hepsi** `Security`/`Syshub` **controller sınıf adı**; paket adı olarak istenmiyor → `rbnAdminApp.js` hiç yüklenmiyor. | `Select-String "'security'"` → 22 dosya, hiçbiri `addAsset('security')` değil. |
| **B8** | Varsayılan `Inter` fallback'i `<proje-b>`'da **düşmüyor** | `build()` (`:154-162`) frontend'de font yoksa `@font/Inter` eklemeli. `<proje-b>` hiç `addAsset` font çağırmıyor, sayfada **hiç font link'i yok**. `<proje-c>` (`@font/Syne`) → 1 font, `rbncore` (2 font) → Inter düştü, `<proje-a>` (açıkça `@font/Inter`) → 4 font. `<proje-b>` → **0 font** (Inter de yok). Muhtemel `prepare()`'teki `:104-112` temizleme ile `build()`'in `:154` kontrolünün etkileşimi ya da `hasAnyFont` sayımının `/fonts/` kalıbı. | Canlı font link sayımı (§6.4 tablosu). Kod: `AssetBuilder.php:93-113` ve `:144-162`. **Kök neden bu belgede kesinleştirilemedi** — `rbncore`'da `Space Mono` varken Inter düşmesi `:104-112` ile açıklanıyor; `<proje-b>`'da `:104-112` de tetiklenmemeli (özel font yok). İnceleme gerekiyor. |
| **B9** | `?v=` elle sürümleme çalışmaz | Üretici `=v<filemtime>` (path sonu) üretir ve `serve()` yalnız `=` ile ayırır. Elle yazılan `?v=` **hiçbir şeyi ayırmaz** → sürümleme çalışmaz. | `AssetBuilder.php:347` · `AssetController.php:48-51`. `=v1.0` ile 307 doğrulandı; `?v=` ile canlı test **yapılmadı** (kod yolu açık). `Resources/Views/Errors/Layouts/shield_header.php` bu konuda elle link yazıyor (önceki analiz D-07/D-08) — bu belgede yeniden ölçülmedi. |
| **B10** | `rbn-terminal.css` bağsız | 5 189 B ham dosya hiçbir sabitte, pakette veya `@import`'ta geçmiyor. | `Select-String 'rbn-terminal'` tüm repo → 0 (dosya adı hariç). |
| **B11** | `@import` ve dinamik JS: **19 + 9 = 28 ek istek** | `rbn-master.css` **19 `@import`** → tarayıcı **19 ayrı istek** açar (HTTP/2 altında da 19 kaynak). `rbn-master.js` 9 modülü `document.createElement('script')` ile **çalışma anında** ekler → **9 ek istek + JS yürütme gecikmesi**. Ölçülen toplam: CSS 207 963 B / JS 151 852 B ham. | `rbn-master.css:7-29` (19 satır) · `rbn-master.js:64-81` (9 modül). Canlı: `rbncore` sayfasında 6 yerel varlık isteği (baskı sonrası gerçek sayım §12.2). **Karar gerekiyor:** kapanışı tek dosyaya birleştirmek (`FW-CSS-MOTOR-2` kararı: *"bundle bölme kararı (geriye uyumlu, en küçük değişiklik)"* — sıra korundu, paketler ayrıldı). |
| **B12** | B katmanı kuralına istisna | `design-system.md` §4.2: ön eksiz utility **yalnız** `layout.css`/`utilities.css`/`colors.css` içinde tanımlanır. Ama `core/cards.css:363-367` ön eksiz `.object-cover/.object-contain/.object-fill/.object-none/.object-scale-down` tanımlıyor. | `core/cards.css:363-367` (Select-String ile doğrulandı). |
| **B13** | **Üç farklı boşluk ölçeği** çelişiyor | `design-system.md` §4.2: `.p-3` = **16px**. `docs/gorevler/css-bootstrap-rbn-esleme.json` `sayiOlcegi`: "1=4px 2=8px **3=12px** 4=16px". Kod (`utilities.css:17`): `.pt-3 { padding-top: var(--rbn-space-4) }` → `--rbn-space-4 = 16px`. Yani JSON ile kural belgesi **çelişiyor**. | `design-system.md:87` · JSON `:14` · `utilities.css:17` · `tokens.css:42` (`--rbn-space-4: 16px`). **Kod doğrusu (16px).** Öneri: JSON'daki `sayiOlcegi` düzeltilsin veya `--rbn-space` dizini ile `.p-*` dizisi bilinçli ayrılsın. |
| **B14** | Proje CSS'i şişiyor | `<proje-c>` `master.css` **13 `@import`**; `design-system.md` §4.2 "hedef: sıfıra yakın". | `domains/<grup>/<site>/css/master.css` (13 satır `@import`). |
| **B15** | `rbnExtended` ile kapanış arasında 17 sınıf çakışması | Opsiyonel paketlerin 387 sınıfından **17**si zaten kapanışta. B katmanı `!important` yazdığı için (B14/B88 kuralı) yükleme sırasına göre sonuç değişebilir. | Ölçüm: `|opsiyonel ∩ kapanış| = 17` (örn. `rbn-form-input`). |
| **B16** | Eşleme JSON'u ölçüm sayıları **tutarsız** ve üretim betiği yok | JSON `surum 1.1.0`: kapanış 23 dosya / 1 563 sınıf / 575 `rbn-` / 988 ön eksiz. Bu belgenin **bağımsız** ölçümü: **19 dosya / 1 193 sınıf / 471 `rbn-` / 722 ön eksiz**. Ayrıca `hedefEksik: 0` ve `eksikBootstrapSiniflari: []` iddiaları **kapsam daraltılmadan** anlamsız (863 girdi taranmış, bootstrap'ın ~1 300 sınıfının hepsi değil). | JSON `:19-31` · bu belgenin §7.6 ölçümü (19 dosya, `\.[-\w]+` regex). **Bu belgede kapsam-daraltılmış yeniden ölçüm YAPILMADI** — `css_engine=rbn` + panel/auth kapsamına daraltılmış gerçek eksik sayısı açık bir sonraki görevdir. |
| **B17** | Ölçüm betiği repoda yok | JSON `uretici: "FW-CSS-MOTOR-2 / zeki-6eb7f5 (olcu-genis.mjs + el ile)"`. `olcu-genis.mjs` araması: `E:\localhost`, `E:\AgentSpace`, `E:\tmp\_araclar` → **bulunamadı**. Yeniden üretilebilirlik yok. | `Get-ChildItem -Recurse -Include *.mjs,*.ps1,*.py` (üç kök) — eşleşme yok. |
| **B18** | `.text-primary` **çift tanımlı** | `core/colors.css:10`: `.text-primary, .text-blue { color: var(--rbn-blue-600, #2563eb) !important; }` · `core/layout.css:118`: `.text-primary { color: var(--primary-color, var(--rbn-primary)) !important; }`. İkisi de `!important`, ikisi de `layout.css`/`colors.css` **B katmanı**. `rbn-master.css` sırası: `layout.css` (12) → `colors.css` (22) ⇒ **`colors.css` kazanır**, yani `--rbn-blue-600` (sabit mavi). Proje `--primary-color` ezilmez. | `core/colors.css:10` + `core/layout.css:118` · sıra `rbn-master.css:12` vs `:22`. |
| **B19** | `design-system.md` §4.2 `object-fit-cover` **yanlış** | §4.2 B katmanı örneği olarak `object-fit-cover` veriyor. Motorda **yok**. Var olan: `.object-cover/.object-contain/.object-fill/.object-none/.object-scale-down` (`core/cards.css:363-367`). | `Select-String 'object-fit'` tüm `Resources/Assets/**/*.css` → yalnız `cards.css:323` (özellik) ve `:363-367` (sınıflar). |
| **B20** | `<projeA>-tag` motorda yok | `design-system.md` §4.1 "Standart Şablon"unda `<projeA>-tag <projeA>-tag-blue` kullanılıyor. Framework motorunda **tanımı yok**; tanımları proje CSS'inde (`domains/<grup>/<site>/css/{components,elements}.css`). Yani bu "standart şablon" **bir projeye özgü** sınıfı standart gösteriyor. | `Select-String '\.<projeA>-tag'` `rbnframework/**` → 0; `domains/**` → `<site-a2>` dosyaları. |
| **B21** | `rbnModal.js` yorumu kodla uyuşmuyor | `:24` yorumu `[data-rbn-modal="true"], [data-bs-toggle="modal"], [data-rbn-toggle="modal"]` diyor; `:26`'daki gerçek seçici **`[data-bs-toggle="modal"]` içermiyor**. | `components/rbnModal.js:24` vs `:26`. |
| **B22** | `AssetConvention` tek doğruluk kaynağı **tam kapanmamış** | `<proje-c>` favicon'ı `SeoResolver` üzerinden **fiziksel yol + `=v<filemtime>`** ile basıyor (`/project-assets/images/favicon-<proje-c>.png=v1782671388`), sanal `AssetController` rotasını kullanmıyor. Yeni siteler (`rbncore`, `sro`, `<proje-b>`) sanal/uyumlu yolu kullanıyor. | Canlı `<link rel="icon">` taraması (§10.3). |
| **B23** | `panel` bağlamı `css_engine`'den **bağımsız** | `injectCoreStack()` `:266` koşulu `panel`'i içermiyor; panel `rbn_master`'i `STACK_MAP['panel'] → rbn_core_panel` ile alıyor. **Davranış doğru** ama kod okuyan kişi `:266`'ya bakıp "panel'de rbn_master yüklenmiyor" sanabilir. Belge düzeltmesi değil, okuma tuzağı notu. | `AssetBuilder.php:266` vs `AssetBundles.php:24,51-56`. Canlı `rbn-admin` sayfasında `rbn-master.css` **var** ✓. |
| **B24** | `addAsset('js/x.js')` ile `addAsset('/js/x.js')` **farklı** yollar | `addAsset()` `:118-122` yalnız `@`/`http`/`/` ile **başlamayan** yola `@project/` ekler. `js/frontend.js` → `@project/js/frontend.js`; `/js/frontend.js` → site kökü (düzleştirme yok, `.test` altına gider). İkisi de 200 dönebilir ama **farklı fiziksel dosyalardır**. | `FrontendBaseController.php:118-122`. Canlı `<proje-a>`: `…/project-assets/js/frontend.js=v1789207191` (`addAsset('/js/frontend.js')` çağrısından, `<projeA>FrontendController.php:28`). |

---

## Bölüm başına bağımsız doğrulama özeti

Her bölüm için 5 rastgele iddia **bu belgenin yazımı sırasında koddan/canlıdan bağımsız olarak
yeniden sorgulandı**. Sonuçlar:

| Bölüm | 5 iddia | Sonuç |
|---|---|---|
| §1 Yükleme sistemi | `core.php:46-48` iki rota · `PROXY_SETUP` iki kaynak · `serveProject` tek bayrak farkı · token sırası project→framework→font · `AssetDefinition` saf kayıt | 5/5 ✅ |
| §2 Sabit tablosu | 36 `public const` · 10 CDN · `JQUERY` dizi · `REMIX_ICON` her bağlamda · `RBN_SHIELD_CSS` paketsiz | 5/5 ✅ (paket dışı olanlar §13-B1'e yazıldı) |
| §3 `AssetBundles` | 4 `STACK_MAP` bağlamı · 24 paket · `rbn_core_frontend` 3 stil · `rbnExtended` 2 stil · `PanelProvider:48-55` `assets` yolu | 5/5 ✅ |
| §4 `AssetBuilder` | `:250-252` `css_engine` okuma · `:259` baypas · `:266` enjeksiyon koşulu · `:303` `filemtime` · `:347` `=v` biçimi · `:166-174` `@project` en son | 6/6 ✅ (`css_engine` sayımı eski analizle çelişti → 13/8 düzeltildi) |
| §5 `AssetController` | `=v` kesme · `fonts/` 307 · `media/` 307 + beyaz liste · sanal SVG/OG dalları · `Cache-Control` 1 yıl · `immutable` yok | 6/6 ✅ (bir **yeni bulgu**: 404→500, §13-B3) |
| §6 Font | 33 slug · slug kuralı · 16 takma ad · link basımı `SeoProvider`'da · `AssetProvider` filtresi · `fonts_panel`/`fonts_auth` | 6/6 ✅ (**yeni bulgu**: fallback Inter düşmüyor, §13-B8) |
| §7 CSS | 19 `@import` · 19 dosya ölçümü · `pt-3` = `utilities.css:17` · token çift katmanı · `.text-primary` çift tanım · `object-fit-cover` yok | 6/6 (4 ✅, 2 **yeni çelişki**: §13-B18, §13-B19) |
| §8 JS | 9 dinamik modül · `async=false` · `data-bs-*` **okunuyor** · 21 `data-bs` eşleşmesi · `jquery` yalnız panel · `rbnExtended` kullanılmıyor | 6/6 ✅ (`data-bs-*` "yok" varsayımı **çürütüldü**) |
| §9 İkon | `ri-` 2 527 / `bi-` 726 / `fas-` 0 · `flag_icon` STACK_MAP dışı · `IconLibrary::match` · `icon()` global yok · `PanelMap` `ri-*` | 5/5 ✅ (`bi-` boşluğu yeni bulgu → §13-B5) |
| §10 `AssetConvention` | 3 uzantı / 3 uzantı · aday sırası · `realpath` kök kontrolü · `findOgImage` null · sanal OG MIME | 5/5 ✅ (`<proje-c>` sapması yeni → §13-B22) |
| §11 Tarifler | `addAsset` ön-eğri · `rbnExtended` 3 yol · `?v=` çalışmaz · `@font-face` serbest · `findFavicon` öncelik | 5/5 ✅ |
| §12 Performans | 19 dosya ham/gzip · canlı `Content-Length 1420` · dış istek 2/4/7/9 · `max-age=31536000` · gzip sunucuda | 5/5 ✅ |
| §13 Bilinmeyenler | 24 kalem, her biri kanıt bağlantılı · B16/B17 ölçüm tekrarlanamazlığı açıkça belirtildi | — |

---

## PLATFORM BEYANI

**İş doğrulandığı platform: Windows** (PowerShell, `curl.exe`, Python 3 `gzip`, Git 2.x).

**Platforma bağlı olan ve dokunduğum kod yolu:**

1. **Dosya yolu ayrımı** — `AssetBuilder::generateWebUrl()` `:332` `str_replace('\\', '/', $path)` ve
   `AssetResolver::resolve()` `:25` `ltrim($path, '/\\')` **her iki platformda** ters bölüyü
   düzleştirir. `AssetConvention::resolveVirtualOgImage()` `:161` ise ters bölüyü `DIRECTORY_SEPARATOR`'a
   çevirir — **macOS/Linux'da `DIRECTORY_SEPARATOR` = `/`, Windows'ta `\`**. `realpath()` kök kontrolü
   (`:167-177`) iki platformda da `str_replace('\\', '/')` ile normalize edildiği için
   **platformdan bağımsız**. Canlı doğrulama Windows'ta yapıldı; macOS/Linux'da aynı sonucu verir.
2. **Yol küçük harfe inmesi ve büyük/küçük harf duyarsızlığı** — URL üretimi küçük harfe indirir
   (`AssetBuilder.php:336,340`) ama `AssetResolver::deepSearch()` (`:119`) karşılaştırmayı
   `strtolower()` ile yapar. Windows'ta dosya sistemi zaten büyük/küçük harf duyarsız; **macOS/Linux'da
   da aynı sonuç verir** çünkü eşleştirme uygulama seviyesinde `strtolower` ile yapılıyor.
   Doğrulama (`/framework-assets/rbncommon/bebasneue-regular.ttf` → 200) **Windows'ta yapıldı**;
   Linux'ta `deepSearch` yolu devreye girer ve sonuç aynıdır.
3. **Kabuk/ikili adı** — ölçüm komutlarında `curl.exe` (Windows), `python` ve `Get-ChildItem`
   (PowerShell) kullanıldı. Bunlar **yalnız ölçüm araçlarıdır**, belgedeki hiçbir iddia
   platforma bağlı değildir. macOS/Linux'ta `curl` (`.exe` soneksiz) ve `python3` gerekir;
   belgedeki URL'ler ve sonuçlar aynıdır.
4. **Sıkıştırma** — `gzip.compress(data, 9)` **Python'ın** zlib seviyesidir. Canlıdaki web sunucusu
   (nginx/Apache) farklı bir seviye kullanabilir. Bu yüzden §12'deki gzip rakamları
   **"gzip -9 referansı"** olarak etiketlendi, sunucu ölçümü iddiası olarak yazılmadı.
   Sunucunun gerçek `Content-Length`'i canlı ölçüldü (`1420`), ama bu **sıkıştırılmamış** beyan.
5. **HTTP istekleri** — `curl.exe -k` ile yerel `*.test` alan adlarına yapıldı; **TLS doğrulaması
   kapatıldı** (`-k`, yerel sertifika). macOS/Linux'ta da aynı bayrak geçerli. Canlı üretim
   alan adları **kullanılmadı**.
6. **Süreç/izin gerektiren hiçbir kodu dokunmadım.** Kod değişikliği **yapmadım**; yalnız okudum,
   ölçtüm ve bu belgeyi yazdım. Bu nedenle "diğer platformda ne olur" sorusu §5/§10/§13'teki
   davranışsal iddialar için yukarıdaki 1-2. maddelerle sınırlıdır.

**Koşulamadığım platformlar:** **macOS ve Linux** — bu ortamda yalnız Windows bulundu
(`win32`). Kod okuma, statik doğrulama ve yerel HTTP ölçümü yapabildim; macOS/Linux'a özgü bir
çalıştırma ortamı yoktu. §5.5 `AssetResolver` `deepSearch()` özyinelemeli tarayıcısı ve
`AssetConvention` `realpath()` kök kontrolü **koddan** platformdan bağımsız olduğu için bu iki
yerde macOS/Linux'ta **aynı** sonucu verecektir; ancak "koşulamadı + neden" notu düşülmüştür.
