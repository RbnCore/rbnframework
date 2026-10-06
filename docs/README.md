# RBN Framework — Belgeler Dizini (kaynak ağacı haritası)

> **Bu belge hangi commit'e göre yazıldı:** `d49b4413` (dal `feat/fw-license-master`)
> **Son doğrulama tarihi:** 2026-10-05
> **Yayın tabanı:** 0.9.4 = bu commit + sonrası; belge yalnız doğrulama anındaki kodu anlatır
> **Kapsam:** `rbnframework/` deposunun tamamı (okuma yönü: framework'ü hiç bilmeyen biri)

Bu dizi, framework'ün **kendi kodunu okuyarak** yazılmış rehberidir. Her iddia
`dosya:satır` ya da `sınıf::metot` ile kaynağa bağlıdır. Kodda
doğrulanamayan hiçbir şey burada **yazılmamıştır**; doğrulanamayanlar
`acik-sorular.md` dosyasındadır.

Dizin **kaynak ağacını aynalar**: kaynak yolu = belge yolu
(`Core/Http/…` → `Core/Http/README.md`), böylece okuyucu kodda nerede ise
belgede de orada.

---

## 1. Kavram rehberleri (`kavramlar/`)

| Belge | Konu | Kim okumalı? |
|---|---|---|
| [kavramlar/01-mimari-harita.md](kavramlar/01-mimari-harita.md) | Açılış zinciri, Kernel aşamaları, yönlendirme, render, `project_key` / site / modül kavramları, `Paths`, ortam algısı | Framework'e ilk kez dokunan **herkes** |
| [kavramlar/02-yapilandirma.md](kavramlar/02-yapilandirma.md) | Hangi ayar hangi dosyada, kim okuyor, öncelik sırası, ortam değişkenleri, "bir ayar nereden okunur?" karar tablosu | Ayar ekleyen/değiştiren |
| [kavramlar/03-veritabani-ve-kiracilik.md](kavramlar/03-veritabani-ve-kiracilik.md) | Bağlantı aileleri, `BaseModel` kapsam/yazma koruması, migration, master tabloları, lisans kapısı | Veritabanı ve model yazan |
| [kavramlar/04-surumleme-ve-yayin.md](kavramlar/04-surumleme-ve-yayin.md) | Sürüm kuralı, tek kaynak, `rbn version:*`, CHANGELOG/UPGRADING biçimi, yükseltme | Sürüm/yayın yapan |
| [kavramlar/05-asset-sistemi.md](kavramlar/05-asset-sistemi.md) | ASSET/CSS/JS/FONT/İKON sistemi, konvansiyon, önbellek | Asset/view yazan |
| [acik-sorular.md](acik-sorular.md) | Kodda doğrulanamayan ya da çelişen noktalar (her maddede: ne soruldu · nereye bakıldı · neden belirsiz · nasıl çözülür) | Herkes (okuma sonrası mutlaka) |

---

## 2. Kaynak ağacı haritası (klasör → belge)

Tek tablo: kaynak klasörü, dosya sayısı ve o klasörü anlatan belge.

### 2.1 `Core/`

| Kaynak klasör | `*.php` | Belge | Durum |
|---|---|---|---|
| `Core/Base/` | 65 | [Core/Base/README.md](Core/Base/README.md) · [Data.md](Core/Base/Data.md) · [Services.md](Core/Base/Services.md) · [Web.md](Core/Base/Web.md) | ✅ |
| `Core/Database/` | 74 | [Core/Database/README.md](Core/Database/README.md) · [Engine.md](Core/Database/Engine.md) · [Models.md](Core/Database/Models.md) · [Repositories.md](Core/Database/Repositories.md) · [Migrations.md](Core/Database/Migrations.md) | ✅ |
| `Core/Http/` | 36 | [Core/Http/README.md](Core/Http/README.md) (5 kök) · [Engine.md](Core/Http/Engine.md) (17) · [Security.md](Core/Http/Security.md) (14) | ✅ |
| `Core/Render/` | 51 | [Core/Render/README.md](Core/Render/README.md) (2 kök) · [Builders.md](Core/Render/Builders.md) (7) · [Configs.md](Core/Render/Configs.md) (5) · [Controllers.md](Core/Render/Controllers.md) (5) · [Handlers.md](Core/Render/Handlers.md) (6) · [Providers.md](Core/Render/Providers.md) (11) · [Resolvers.md](Core/Render/Resolvers.md) (10) | ✅ |
| `Core/Routes/` | 13 | [Core/Routes/README.md](Core/Routes/README.md) | ✅ |
| `Core/Services/` | 81 | [Core/Services/README.md](Core/Services/README.md) (+ kayıt haritası) · [Console/README.md](Core/Services/Console/README.md) (30) · [Exception.md](Core/Services/Exception.md) (13) · [Gatekeepers.md](Core/Services/Gatekeepers.md) (13) · [Hosting.md](Core/Services/Hosting.md) (6) · [Master.md](Core/Services/Master.md) (7) · [System.md](Core/Services/System.md) (11) | ✅ |
| `Core/Support/` | — | `Core/Support/README.md` (+ alt dallar) | ⏳ ayrı görev |
| `Core/System/` | — | `Core/System/README.md` (+ `Config.md`, `Discovery.md`, `Paths.md`, …) | ⏳ ayrı görev |

### 2.2 `Bundles/`, `Packages/`, `Resources/`

| Kaynak klasör | `*.php` | Belge | Durum |
|---|---|---|---|
| `Bundles/Internal/Syshub/` | 47 | [Bundles/Internal/Syshub/README.md](Bundles/Internal/Syshub/README.md) · [Security.md](Bundles/Internal/Syshub/Security.md) (5 controller) · [DbConsole.md](Bundles/Internal/Syshub/DbConsole.md) (3 controller) | ✅ |
| `Bundles/Internal/Webhub/` | 43 | [Bundles/Internal/Webhub/README.md](Bundles/Internal/Webhub/README.md) · [Seo.md](Bundles/Internal/Webhub/Seo.md) (5 handler) | ✅ |
| `Bundles/Internal/Backstage/` | 24 | [Bundles/Internal/Backstage/README.md](Bundles/Internal/Backstage/README.md) | ✅ |
| `Bundles/RbnSuite/` | 80 | [Bundles/RbnSuite/README.md](Bundles/RbnSuite/README.md) — paketler arası ilişkiler, ortak kayıt kalıbı | ✅ |
| `Bundles/RbnSuite/RbnAdmin/` | 49 | [Bundles/RbnSuite/RbnAdmin/README.md](Bundles/RbnSuite/RbnAdmin/README.md) · [Controllers.md](Bundles/RbnSuite/RbnAdmin/Controllers.md) (11) · [Models.md](Bundles/RbnSuite/RbnAdmin/Models.md) (3) · [Providers.md](Bundles/RbnSuite/RbnAdmin/Providers.md) (7: Providers+Services+Traits) · [Views.md](Bundles/RbnSuite/RbnAdmin/Views.md) (28) | ✅ |
| `Bundles/RbnSuite/RbnAuth/` | 15 | [Bundles/RbnSuite/RbnAuth/README.md](Bundles/RbnSuite/RbnAuth/README.md) | ✅ |
| `Bundles/RbnSuite/RbnStudio/` | 16 | [Bundles/RbnSuite/RbnStudio/README.md](Bundles/RbnSuite/RbnStudio/README.md) | ✅ |
| `Packages/RbnApi/` | 27 | [Packages/RbnApi.md](Packages/RbnApi.md) | ✅ |
| `Packages/RbnEmail/` | 10 | [Packages/RbnEmail.md](Packages/RbnEmail.md) | ✅ |
| `Packages/RbnFile/` | 8 | [Packages/RbnFile.md](Packages/RbnFile.md) | ✅ |
| `Packages/RbnPipeline/` | 31 | [Packages/RbnPipeline.md](Packages/RbnPipeline.md) + [RbnPipeline/Rules.md](Packages/RbnPipeline/Rules.md) (11) | ✅ |
| `Packages/RbnUtility/` | 2 | [Packages/RbnUtility.md](Packages/RbnUtility.md) | ✅ |
| `Packages/PackageData.php` | 1 | [Packages/PackageData.md](Packages/PackageData.md) | ✅ |
| `Resources/` (Assets, Data, images, Views) | 29 php + 53 diğer | [Resources/README.md](Resources/README.md) | ✅ |

> `Bundles/RbnSuite/*` satırları **05.10.2026'da DOC-REHBER-AGAC-5** görevinde
> dolduruldu: RbnAdmin 49 + RbnAuth 15 + RbnStudio 16 = **80 php**, tamamı
> alt belgelere yazıldı. Bu görevde ölçülen iki ölü rota hedefi bulundu;
> bkz. `acik-sorular.md` §2.
>
> ⏳ satırı, o kaynak ağacı için **önceden ayrılmış** belge yolunu gösterir;
> belge o görevde üretilecektir. Kapsam dışı bırakılan kaynak ağaçları
> (`projects/`, `domains/`) bu dizinin konusu değildir.

---

## 3. Okuma sırası

1. **Yeni başlayan (framework'ü bilmiyor):**
   `kavramlar/01-mimari-harita.md` → `kavramlar/02-yapilandirma.md` → `acik-sorular.md`
2. **Ayar/ayar dosyası ile uğraşan:** `kavramlar/02-yapilandirma.md` → `acik-sorular.md`
3. **Model/Repository/migration yazan:** `kavramlar/03-veritabani-ve-kiracilik.md` → `Core/Database/README.md` → `Core/Base/Data.md`
4. **Controller/view yazan:** `Core/Base/Web.md` → `Core/Routes/README.md` → `Core/Http/README.md` → `Core/Http/Engine.md`
5. **Panel ekranı yazan:** `Bundles/RbnSuite/RbnAdmin/README.md` → [Controllers.md](Bundles/RbnSuite/RbnAdmin/Controllers.md) → [Views.md](Bundles/RbnSuite/RbnAdmin/Views.md) → `Bundles/RbnSuite/RbnAdmin/Models.md` (rota kaydı)
6. **Giriş/oturum/rol değiştiren:** `Bundles/RbnSuite/RbnAuth/README.md` → `Core/Routes/README.md` (middleware grupları)
7. **İçerik (makale/haber/kategori) yazan:** `Bundles/RbnSuite/RbnStudio/README.md` → `Core/Database/README.md`
8. **Dosya/görsel yükleyen:** `Packages/RbnFile.md` → `Core/Http/Engine.md` (`UploadedFile`)
9. **Dış servis (sosyal medya, arama motoru, ödeme) bağlayan:** `Packages/RbnApi.md` → `Packages/PackageData.md`
10. **Sürüm/yayın yapan:** `kavramlar/04-surumleme-ve-yayin.md` (kök `README.md` + `.github/UPGRADING.md` ile birlikte)
11. **Şablon/görünüm yazan:** `Core/Render/README.md` → `Core/Render/Resolvers.md` → `Core/Render/Handlers.md` (`TemplateExpressionGuard`, `RawHtmlGate`) → `Core/Render/Builders.md` + [kavramlar/05-asset-sistemi.md](kavramlar/05-asset-sistemi.md)
12. **Güvenlik/erişim kuralı yazan:** `Core/Services/Gatekeepers.md` → `Core/Services/Master.md` (lisans) → `Core/Services/Exception.md`
13. **CLI/cron/migration yazan:** `Core/Services/Console/README.md` → [kavramlar/04-surumleme-ve-yayin.md](kavramlar/04-surumleme-ve-yayin.md)
14. **Ajan (kod okuyup değiştiren):** `kavramlar/` altındaki beş belgeyi de oku;
    ardından `.agents/rules/core-architecture.md`, `database-and-cli.md`,
    `versioning.md` kurallarını oku.

> Kök `README.md` **kurulum** içindir (Gereksinimler, Kurulum, sır yönetimi,
> klasör haritası, CLI listesi, güvenlik, sürüm). Mimari soruları **o belgede
> değil**, bu dizide aranır.

---

## 4. Nasıl katkı / güncelleme kuralı

**Kural (bağlayıcı):** Framework kodunda yapılan **her değişiklik**, o
değişikliğin ilgili olduğu belgeyi **aynı ya da takip eden yayın
güncellemesinde** güncellemek zorundadır. Belge güncellenmeden commit
edilmiş bir davranış değişikliği **eksik sayılır**.

Belge–konu eşlemesi:

| Kod değişikliği | Güncellenmesi gereken belge |
|---|---|
| Giriş noktası, `PreBoot`, `KernelFactory`, bir `Stage`, `Route::run()`, `RedirectManager`, `Paths`, `is_local()` / `ProjectDbProfileResolver` | `kavramlar/01-mimari-harita.md` |
| Yeni ayar anahtarı, `Config` önceliği, `project-routemap.php` / `project-settings.php` şeması, `EnvKeys` | `kavramlar/02-yapilandirma.md` |
| Bağlantı ailesi, `BaseModel` kapsam, `$fillable`, migration, master tablosu, lisans kapısı | `kavramlar/03-veritabani-ve-kiracilik.md` + `Core/Database/` altındaki belge |
| `Version`, `FrameworkIdentity`, `APP_VERSION`, CLI sürüm komutları, CHANGELOG/UPGRADING biçimi | `kavramlar/04-surumleme-ve-yayin.md` |
| `BaseComponent`/`BaseService`/`BaseModel` tabanları, `Concerns/*`, `Patterns/*` | `Core/Base/README.md` + ilgili alt dal |
| `Bundles/Internal/Syshub/**` (sistem panosu, bakım modu, veri temizliği, DB konsolu, güvenlik) | `Bundles/Internal/Syshub/README.md` + [Security.md](Bundles/Internal/Syshub/Security.md) / [DbConsole.md](Bundles/Internal/Syshub/DbConsole.md) |
| `Bundles/Internal/Webhub/**` (kimlik, SEO + tarama pipeline'ı, menü, yasal sayfa, SSS, entegrasyon) | `Bundles/Internal/Webhub/README.md` + [Seo.md](Bundles/Internal/Webhub/Seo.md) |
| `Bundles/Internal/Backstage/**` (panel sidebar'ı, e-posta ayarları, ayar mimarisi) | `Bundles/Internal/Backstage/README.md` |
| `Request`/`Response`/`Validator` | `Core/Http/README.md` |
| `Core/Http/Engine/*` (trait'ler, `UploadedFile`, `FormRequest`) | `Core/Http/Engine.md` |
| `Core/Http/Security/*` (form kalkanı, `ApiGuard`) | `Core/Http/Security.md` |
| `Packages/RbnApi/*` (dış servis kapısı) | `Packages/RbnApi.md` + `Packages/PackageData.md` |
| `Packages/RbnEmail/*` (SMTP, şablon, IMAP) | `Packages/RbnEmail.md` |
| `Packages/RbnFile/*` (yükleme, GD, dışa/içe aktarma) | `Packages/RbnFile.md` |
| `Packages/RbnPipeline/*` (AI içerik hattı) | `Packages/RbnPipeline.md` (+ `Rules.md`) |
| `Packages/RbnUtility/*` (RSS, Trends) | `Packages/RbnUtility.md` |
| `Resources/Views/*`, `Resources/Assets/*`, `Resources/Data/*` | `Resources/README.md` |
| `Route`, `Collector`, `Matcher`, `Dispatcher`, `RedirectManager`, `Mappings/*` | `Core/Routes/README.md` |
| Asset/CSS/JS/FONT/İKON | `kavramlar/05-asset-sistemi.md` + `Core/Render/Builders.md` + `Core/Render/Configs.md` |
| Şablon yönergesi (`@if/@yield/@import/@csrf`), layout, view yolu güvenliği | `Core/Render/README.md` + `Core/Render/Resolvers.md` + `Core/Render/Handlers.md` (`TemplateExpressionGuard`) |
| SEO/meta/schema/`robots.txt`/sitemap/`llms.txt` | `Core/Render/Resolvers.md` + `Core/Render/Providers.md` + `Core/Render/Controllers.md` |
| Varlık vekili (`/framework-assets`, `/project-assets`), font/medya yönlendirme | `Core/Render/Controllers.md` + `Core/Render/Configs.md` |
| Hata sayfası, RbnShield, panic ekranı | `Core/Services/Exception.md` + `Core/Services/Gatekeepers.md` (`BootSentinel`) |
| IP koruması, WAF, hız sınırı, bakım modu, VIP | `Core/Services/Gatekeepers.md` |
| Lisans doğrulama, proje/uygulama sürümü | `Core/Services/Master.md` |
| CLI komutu, cron, migration, temizlik işi | `Core/Services/Console/README.md` |
| Ayar servisi, oturum, kullanıcı, CDN, modül keşfi | `Core/Services/System.md` |
| cPanel (e-posta/alan adı/SSO) | `Core/Services/Hosting.md` |

Ek kurallar:

* Her belgenin başındaki **"Doğrulanan kod tabanı"** satırı, belgeyi yazan kişi
  tarafından **güncellenir**; "son doğrulama tarihi" de aynı anda yazılır.
* **"Yayın tabanı"** satırı sürümü anlatır: `0.9.4 = bu commit + sonrası`, yani belge
  yalnız **doğrulama anındaki** kodu anlatır. Belgeyi yazan kişi o commit'ten sonra
  değişen bir şeyi anlatıyorsa commit'i **kendisi** günceller — başkası güncellemez.
* Bir iddia artık kodda doğrulanamıyorsa **silinmez**,
  `acik-sorular.md`'ye taşınır.
* Belgelerde proje/müşteri/kişi adı yazılmaz (bkz.
  `.agents/rules/core-architecture.md` §9). Örnekler jeneriktir:
  `<proje>`, `site.example`, `ornek-proje-1`.
* Yeni metot/fonksiyon adları İngilizce, açıklama metinleri Türkçedir
  (aynı kural §10).