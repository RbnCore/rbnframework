# Packages/PackageData — paket kayıt haritası (bütün `Packages/*` tek noktada)

> **Doğrulanan kod tabanı:** `d49b4413` (dal `feat/fw-license-master`) · **Tarih:** 2026-10-05 · **Yayın:** 0.9.6 = bu commit + sonrası; belge yalnız doğrulama anındaki kodu anlatır
> **Kaynak dosya:** `Packages/PackageData.php` — **1 `*.php`** (paket kökündeki tek dosya).
> **Envanter:** 1/1.
> Bu dosya, `Packages/` ağacındaki **79 `*.php`** dosyanın hepsini tek
> `registerMap()` dizisinde adlandırır; her paketin kendi belgesi:
> [RbnApi.md](RbnApi.md) (27) · [RbnEmail.md](RbnEmail.md) (10) ·
> [RbnFile.md](RbnFile.md) (8) · [RbnPipeline.md](RbnPipeline.md) (31) ·
> [RbnUtility.md](RbnUtility.md) (2) · + bu dosya (1) = **79**.

## 1. Ne işe yarar, kim kullanır

Tüm paket sınıflarının **bağımlılık kaydıdır**. `BaseConfig`'ten türeyen bu
sınıf `registerMap()` ile servis/manager/handler/rule/preset/builder/provider
adlarını sınıf tam adlarına eşler. Kayıt sistemi (`Core/System/Registries`)
haritayı okur; `$this->service('telegram')`, `$this->handler('fileUpload')`
gibi kısa adlar buradan çözülür.

**Kimler çağırır:** framework açılışında kayıt/keşif katmanı. Uygulama kodu
haritayı doğrudan okumaz; yalnız çözülmüş kısa adı kullanır.

## 2. Dosya envanteri (1/1)

| Dosya | Görev | Önemli public yöntemler |
|---|---|---|
| `PackageData.php` | `Rbn\Framework\Packages` ad alanında, `BaseConfig` sınıfını genişletir; tüm paket kayıtlarını tek dizide döndürür. | `registerMap(): array` (`:16-117`) |

## 3. Akış — kayıt haritasının bölümleri

```
PackageData::registerMap()                       PackageData.php:16
 ├─ 'services'  (16 kayıt)  :20-37   file, image, email, gemini, instagram,
 │                                    facebook, twitter, youtube, google,
 │                                    shopier, tmdb, api, indexNow, telegram,
 │                                    rssParser, googleTrends
 ├─ 'managers'  (4 kayıt)   :41-46   api, aiUsage, autoTask, externalFetch
 ├─ 'handlers'  (12 kayıt)  :49-64   fileUpload, fileValidator, fileUtility,
 │                                    fileExport, fileImport, fileImage,
 │                                    emailConfig, emailGuard, emailRender,
 │                                    emailTransport, emailImap, emailParser
 ├─ 'rules'     (11 kayıt)  :67-79   promptRule.json, .seo, .image, .imageSafety,
 │                                    .imageCompiler, .youtube, .innerLinking,
 │                                    .blogStructure, .writingTone, .identity,
 │                                    .contentQuality
 ├─ 'presets'   (6 kayıt)   :82-89   youtube, blog, trends, custom, news, image
 ├─ 'builders'  (3 kayıt)   :92-96   prompt, task.content, task.content_rewrite
 ├─ 'providers' (10 kayıt)  :99-110  apiGemini, apiInstagram, apiFacebook,
 │                                    apiTwitter, apiYoutube, apiShopier,
 │                                    googleMaps, googleAnalytics, apiTmdb,
 │                                    apiTelegram
 └─ 'metadata'  (1 kayıt)   :113-115 EMAIL_ → EmailConstant
```

Toplam kayıt: 16 + 4 + 12 + 11 + 6 + 3 + 10 + 1 = **63**.

**Kayıt ile dosya eşlemesi:** 63 kaydın her biri tek bir sınıfı gösterir.
`managers` (`autoTask`, `externalFetch`) ve `metadata` dışındaki tüm
sınıfların tanımı kendi paket belgesinde tek tek anlatılmıştır.

## 4. Yapılandırma / ayar anahtarları

Bu dosya **ayar okumaz**; yalnız sabit harita döndürür. Okunan ayarlar:
yok.

| Kavram | Yer | Not |
|---|---|---|
| `services.*` adları | `:21-36` | `$this->service('<ad>')` ile çağrılır |
| `handlers.*` adları | `:50-63` | `$this->handler('<ad>')` |
| `managers.*` adları | `:42-45` | `$this->manager('<ad>')` |
| `rules.*` adları | `:68-78` | `PromptBuilder` bu adlarla kural çözer |
| `builders.task.*` | `:94-95` | Nokta içeren ad, görev türü ayrımı içindir |

## 5. Tuzaklar ve kurallar

1. **`services` ile `providers` karıştırılmamalıdır.** `services.gemini`
   iş mantığı taşır (prompt, model seçimi); `providers.apiGemini` yalnız HTTP
   çağrısı yapar. İkisi arasındaki sınır [RbnApi.md §1](RbnApi.md)'de tanımlıdır.
2. **`presets` ve `rules` ayrı bölümlerdir.** `presets.blog` bir *kombinasyon*;
   `promptRule.blogStructure` tek bir *kural*. Preset, kendi kural adlarını
   `PromptBuilder` üzerinden çözer.
3. **`metadata.EMAIL_` bir sınıf değil, sabitler sınıfıdır**
   (`RbnEmail/Models/EmailConstant.php`) — `PackageData` haritasındaki tek
   metadata kaydıdır.
4. **Kayıt haritası ile gerçek dosya eşlemesi elle tutulur.** Yeni bir paket
   sınıfı eklenince `registerMap()` güncellenmezse sınıf **çalışmaz**
   (`service()` çözümlemesi başarısız olur); tersi de doğrudur — haritada
   olmayan sınıf kaydedilmez.
5. **`ApiManager` `#[Component(alias:'api', type:'manager')]` özniteliğiyle**
   ayrıca kendini kaydeder (`RbnApi/Managers/ApiManager.php:15`); bu, harita
   kaydından **farklı** bir kayıt yoludur (öznitelik keşfi).

## 6. Örnek (gerçek koddan)

Kayıt adlarının kullanımı (`Packages/RbnPipeline/Services/AutoTaskManager.php`
ve `Packages/RbnEmail/Services/EmailService.php`):

```php
$this->service('email')            → RbnEmail\Services\EmailService
$this->handler('emailRender')      → RbnEmail\Handlers\EmailRenderHandler
$this->service('cron')             → Core/Services kaydı (bu haritada değil)
$this->builder('prompt')           → RbnPipeline\Builders\PromptBuilder
```

## 7. İlgili belgeler

* [RbnApi.md](RbnApi.md) · [RbnEmail.md](RbnEmail.md) · [RbnFile.md](RbnFile.md) · [RbnPipeline.md](RbnPipeline.md) · [RbnUtility.md](RbnUtility.md) — paket belgeleri
* [RbnPipeline/Rules.md](RbnPipeline/Rules.md) — 11 kural sınıfı
* [../Core/System/Registries.md](../Core/System/Registries.md) — haritayı okuyan kayıt katmanı
* [../Core/Base/Services.md](../Core/Base/Services.md) — `service()` / `handler()` / `manager()` çözümlemesi
* [README.md](../README.md) — `Packages/` satırları
