# AÇIK SORULAR

> Adı `BILINMEYENLER.md` idi; 05.10.2026'de `git mv` ile `acik-sorular.md` olarak yeniden adlandırıldı. Maddeler sahibi olan belgeye çözülüp taşındıkça bu dosyadan silinir.

> **Bu belge hangi commit'e göre yazıldı:** `d49b4413` (dal `feat/fw-license-master`)
> **Son doğrulama tarihi:** 2026-10-05
> **Yayın tabanı:** 0.9.3 = bu commit + sonrası; belge yalnız doğrulama anındaki kodu anlatır
> (`§2.10`–`§2.13` maddeleri 05.10.2026'da DOC-REHBER-AGAC-5 / `Bundles/RbnSuite`
> görevi tarafından eklendi.)
>
> Bu dosya, diğer belgelerde **kodla doğrulanamayan** ya da **çelişen** noktaları
> toplar. Buradaki bir madde **gerçek bir belirsizliktir**; "muhtemel şudur" yazılmaz.
> Bir madde çözüldüğünde ilgili belgedeki yerine taşınır ve buradan **silinir**.

---

## 1. Kod ile mevcut belge arasındaki çelişkiler

### 1.2 `MasterRbnHeartbeatsModel` tablo adı ile gerçek tablo uyuşmuyor

* Model beyanı: `protected $table = 'rbn_heartbeats'`
  (`Core/Database/Models/Master/MasterRbnHeartbeatsModel.php:21`).
* Ölçülen master tabloları arasında `rbn_heartbeats` **yok**;
  `z_sys_heartbeats` **var** (`php rbn db:tables --master`).
* `MasterDbData::REQUIRED_TABLES` da `z_sys_heartbeats` sayıyor
  (`Core/System/Config/Definitions/DbProfiles/MasterDbData.php:54-60`).
* **Çağıran araştırıldı (05.10.2026, DOC-AGAC-2):** model yalnız `SystemPhysicalMapTrait`
  içinde `master.rbnHeartbeat` anahtarıyla kayıtlı (`SystemPhysicalMapTrait.php:40`);
  `Core`, `Bundles`, `Packages`, `Resources` taramasında bu anahtarı ya da sınıfı
  **çağıran başka kod bulunamadı** (`z_sys_heartbeats` tablosuna doğrudan SQL ile
  `DatabaseGuardProvider::performHeartbeatTest()` yazıp siler — `DatabaseGuardProvider.php:185-195`;
  `ProjectCleanupJob.php` eski satırları temizler; ikisi de modeli kullanmaz).
  Ayrıntı: `docs/Core/System/Registries.md` §5.5.

* **Veri katmanı tarafı da araştırıldı (05.10.2026, DOC-AGAC-1, `Core/Database`):**
  model dosyasında hiçbir **yazma yolu yoktur** — `MasterRbnHeartbeatsModel`
  yalnız alan bildirimi (`$table`, `$primaryKey`, `$incrementing`, `$keyType`,
  `$timestamps`, `$scoped`) içerir, metot tanımlamaz
  (`Core/Database/Models/Master/MasterRbnHeartbeatsModel.php:18-56`; kardeş
  `RbnHeartbeatsModel` ile karşılaştır: `Models/Project/RbnHeartbeatsModel.php:227`
  `beat()` yazma yolunu taşır, master modeli taşımaz).
  Master tarafındaki **tek** heartbeat yazıcısı model değil, doğrudan SQL kullanan
  `DatabaseGuardProvider::performHeartbeatTest()`'tir ve `z_sys_heartbeats`
  yazar (`Core/Services/Gatekeepers/Providers/DatabaseGuardProvider.php:185-196`).
  Aynı tabloyu temizleyen `ProjectCleanupJob.php:30,40` de modeli kullanmaz.
  Ölü bir hata metni de mevcuttur: yazma yetkisi hatası "rbn_heartbeats tablosuna
  yazma yetkisi yok" der (`Core/Services/Gatekeepers/Handlers/DatabaseGuardHandler.php:239`)
  ama test yazdığı tablo `z_sys_heartbeats`'tir.

Çelişki çözülmedi: model kayıtlı, kullanılmıyor ve tablo adı uyuşmuyor. Seçenekler
(modeli `z_sys_heartbeats`'e çevirmek / modeli ve kaydı silmek) kod kararıdır.
Belgelenen davranış: [Core/Database/Models.md](Core/Database/Models.md).
**Nasıl çözülür:** `rbn db:tables --master` çıktısıyla gerçek tablo adı doğrulanır,
ardından karar verilir. Karar patronundur.

### 1.3 Layout sarmalama: `$content` mı, bölüm mü?

`.agents/rules/core-architecture.md` §6.2, view içeriğinin layout'a `$content`
değişkeni olarak enjekte edildiğini yazıyor. **Kodda** bu doğru **değil**:

* `ViewEngine::render()` görünümü basar, **sonra** `LayoutResolver::getExtends()`
  dönen layout'u yeniden render eder (`Core/Render/ViewEngine.php:77-102`).
* Taşıma `@section()` / `@yield()` yönergeleriyle **bölüm** üzerinden yapılır
  (`ViewEngine.php:229-232`, `Core/Render/Resolvers/LayoutResolver.php:29-83`).

`$content` **yoktur**. Kural belgesi bu noktada güncel değildir.

### 1.7 `SystemLogicMapTrait` içindeki `handlers['seo']` kaydı çözümlenemiyor

* **Ne soruldu:** `Core/Services` kayıt haritasında (`SystemLogicMapTrait.php:114`)
  `'seo' => 'Core\Render\Handlers\Seo\SeoHandler'` kayıtlı. Bu sınıf gerçekten
  var mı, `handler('seo')` ne döner?
* **NEREYE bakıldı:** `Core/System/Registries/RegistryMap/SystemLogicMapTrait.php:114`
  (`logicMap()['handlers']`); `Core/Render/Handlers/` dizin ağacı
  (`Get-ChildItem Core/Render/Handlers -Directory` → yalnız `UI/`);
  `NamespaceResolver::find('seo','handler')`; çalıştırılan ölçüm:
  `$probe->handler('seo')` (PHP 8.3, gerçek `Paths::init`).
* **Sonuç (ölçüldü):** `Core/Render/Handlers/Seo/` dizini **yok**;
  `class_exists('Rbn\Framework\Core\Render\Handlers\Seo\SeoHandler')` **false**;
  `$probe->handler('seo')` → **`NULL`**. Bu kayıt **ölü** bir girdidir;
  `ComponentContext::resolve()` `mandatory=false` iken `null` döner
  (`ComponentContext.php:107-111`), `mandatory=true` olsaydı tanı üretirdi.
* **Neden hâlâ belirsiz:** **Yok — koddan çözüldü.** Render tarafında SEO işi
  `Handlers/UI/*` + `Resolvers/SeoResolver` + `Providers/SeoProvider` üçlüsünde
  yapılıyor; ayrı bir `SeoHandler` **gerekmiyor**.
* **Nasıl çözülür / karar:** kayıt **silinmesi** önerilir (salt okunur belge;
  kod değiştirilmedi). Karar patronundur.
* **Belgelenen davranış:** [Core/Render/README.md §4](Core/Render/README.md),
  [Core/Services/README.md §4](Core/Services/README.md),
  [Core/System/Registries.md §5](Core/System/Registries.md).

### 1.8 `AssetBuilder::addFont()` çift font ekler (düzeltilmiş bulgu)

* **Ne soruldu:** `addFont('Roboto')` çağrıldığında varsayılan Inter de
  ekleniyor mu?
* **NEREYE bakıldı:** `Core/Render/Builders/AssetBuilder.php:70-74` (`addFont`),
  `:142-162` (`build()` içindeki font kontrolü ve Inter ekleme),
  `:145-152` (`str_contains($path, '/fonts/')` testi).
  Ölçüm: `$rp = new ReflectionProperty(AssetBuilder::class,'styles')` ile
  `$styles` anahtarları ve `build()['styles']` çıktısı karşılaştırıldı.
* **Sonuç (ölçüldü):**

  | Çağrı | `$styles` anahtarları | `build()['styles']` |
  |---|---|---|
  | `addFont('Inter')` | `["@font/Inter"]` | **1** — `…/framework-assets/fonts/inter` (prio 5) |
  | `addFont('Roboto')` | `["@font/Roboto"]` | **2** — `…/fonts/inter` (prio 5) **+** `@font/Roboto` (prio 15) |

  Yani `addFont('Roboto')` çağrısında **Inter de eklenir**.
* **Kök neden:** `build()` fontu "derlenmiş" yoldan arar
  (`str_contains($path, '/fonts/')`, `AssetBuilder.php:148`), ama `addFont()`
  kuyruğa **derlenmemiş** `@font/<ad>` yazar (`:72`). `@font/Roboto` yolunda
  `/fonts/` **yoktur** → font yok sayılır → Inter eklenir.
* **İlk ölçümün düzeltilmesi:** İlk denemede "`addStyle()` çoğaldı, 2 eleman"
  sonucu çıkmıştı. Bu **yanlış yorumdu**: `build()['styles']` içindeki ikinci
  eleman çoğalma değil, **otomatik Inter**'dir (`AssetBuilder.php:154-162`).
  Ayrı ölçümde `$styles` **anahtarları** kontrol edildi:
  `addStyle('x.css',1)` + `addStyle('y.css',2)` + `addStyle('x.css',9)` →
  anahtarlar `["x.css","y.css"]`, yani **`addStyle()` koruması çalışıyor**;
  `x.css` önceliği ilk çağrıdaki `1`'de kalır.
  `addScript('/a.js')` iki kez → 1 eleman.
* **Neden hâlâ belirsiz:** `prepare()` akışında bu **düzelir** — temizlik
  `AssetBuilder.php:93-113` içinde `payload` üzerinde çalışır ve `@font/` ile
  başlayan **ham** yolları görür. Yani sorun yalnız **`addFont()` doğrudan**
  `AssetService`/controller üzerinden çağrıldığında görünür
  (`FrontendBaseController::addAsset()` → `AssetService::addStyle()` yolu).
  Bu yolun gerçekten kullanılıp kullanılmadığı bu çalışmada ölçülmedi.
* **Nasıl çözülür:** `build()` içindeki font kontrolü iki biçimi de kabul etmeli:
  `str_contains($path,'/fonts/') || str_starts_with($path,'@font/')`.
  Ya da `addFont()` derlenmiş yolu doğrudan kuyruğa yazmalı.
  Karar patronundur (bu görev salt okunur).
* **Belgelenen davranış:** [Core/Render/Builders.md §5.1–5.2](Core/Render/Builders.md).

### 1.9 `RawHtmlGate` `<script>` gövdesini temizlemiyor

* **Ne soruldu:** Veritabanından gelen ham snippet'ler (`google_analytics_code`,
  `head_scripts`, …) `RawHtmlGate`'den geçiriliyor. Bu gate `<script>` **içeriğini**
  de arıyor mu, yoksa yalnız `on*=` nitelikleri ve URI şemaları mı?
* **NEREYE bakıldı:** `Core/Render/Handlers/RawHtmlGate.php:42` (`EVENT_ATTR_PATTERN`),
  `:50` (`URI_ATTR_PATTERN`), `:53` (`DANGEROUS_SCHEMES` = 7 şema), `:61` (`sanitize`),
  `:100` (`hasDangerousMarkup`). Çağıran: `Core/Render/Providers/UI/FrontendProvider.php:124-136`
  (`safeSnippet()`). Ölçüm: 5 HTML örneği.
* **Sonuç (ölçüldü):**
  | Girdi | `hasDangerousMarkup` | `sanitize` |
  |---|---|---|
  | `<a href="javascript:alert(1)">x</a>` | `true` | `href="about:blank#rbn-blocked"` |
  | `<img src=x onerror=alert(1)>` | `true` | `<img src=x>` |
  | `<a href="data:image/svg+xml;base64,AAA">x</a>` | `true` | `href="about:blank#rbn-blocked"` |
  | `<a href="/safe">x</a>` | `false` | değişmez |
  | `<svg><script>alert(1)</script></svg>` | **`false`** | **değişmez** |

  Yani gate **`<script>` etiketini hiç ele almıyor**.
* **Neden bu tasarım (bilinçli):** `FrontendProvider.php:111-123` yorumu:
  "Bu değerler analytics / AdSense / Tag Manager snippet'i olarak **ham HTML
  olmak** üzere tasarlanmıştır; kaçış kapısı kaldırılamaz; `RawHtmlGate` ise
  **ikinci katman**da inline olay handler'larını ve tehlikeli URI şemalarını
  nötrleştirir. Meşru snippet'ler **AYNEN** korunur." Yazma yetkisi
  `SettingsConfig::REQUIRED_ROLE` (admin/developer) ile sınırlıdır.
* **Neden hâlâ belirsiz:** Gate'in `on*=` ve şema dışında **kontrolsüz bıraktığı**
  yüzey sayımlı değil: `<script>alert(1)</script>` gibi ham script gövdesi
  sızmaz, ama hangi vektörlerin **kasıtlı** bırakıldığı kodda belgelenmemiş.
  Özellikle `<script src="https://kotu.example/x.js">` biçimi **hiçbir kurala
  takılmaz** — host beyaz listesi yok.
* **Nasıl çözülür:** `google_analytics_code`/`head_scripts` alanlarının yalnız
  izinli domainlere giden `<script src>` biçiminde olması kuralı
  veritabanı yazım katmanında uygulanmalı, ya da gate'e `<script src>` için
  host beyaz listesi eklenmeli. Karar patronundur.
* **Belgelenen davranış:** [Core/Render/Handlers.md §4](Core/Render/Handlers.md).

### 1.10 `ViewResolver` mantıksal view adını fiziksel yola bağlayan kural yok

* **Ne soruldu:** `ViewResolver::resolve('errors/404')` gerçek bir dosyaya
  çözülüyor mu? Mantıksal ad ile `Resources/Views/` altındaki fiziksel dosya
  adları arasında bir eşleme (alias) kuralı var mı?
* **NEREYE bakıldı:** `Core/Render/Resolvers/ViewResolver.php:34-125`
  (7 hedef dizin, `['.php','.rbn.php']` uzantı sırası, `RbnCommon` ön kontrolü,
  keşif haritası); `Core/Routes/Mappings/core.php` (çağıran rotalar);
  `Providers/UI/FrontendProvider.php:81-87` (`Layouts/header`, `Layouts/footer`,
  `handleMissing()`). Ölçüm: 5 mantıksal ad ile gerçek `resolve()` çağrısı
  (`context=frontend`, `Paths::init('E:/localhost/projects/<proje>', …)`).
* **Sonuç (ölçüldü):** beş adın **beşi de** `NULL` döndü:
  `errors/404`, `layouts/master`, `RbnCommon/`, `partials/x`, `auth/login`.
  Framework `Resources/Views/` altındaki gerçek adlar
  `Errors/development.php`, `RbnAuth/auth.rbn.php`, `RbnCommon/header.rbn.php`
  gibi **büyük harfli klasör + farklı dosya adı** taşıyor (29 dosya ölçüldü).
  Resolver `toCaseSafePath()` uyguladığı için **klasör eşleşiyor**, ama
  `errors/404` için `Resources/Views/Errors/404.php` **yok** (dosya adı
  `development.php` / `pre_flight.php` / `survival.php`).
* **Neden hâlâ belirsiz:** `FrontendProvider` `Layouts/header`/`Layouts/footer`
  arıyor; framework altında `Layouts/` **yok** (RbnCommon/ ve
  `RbnAdmin/Layouts/ altında). Yani **header/footer sarmalama bu yolla
  sağlanmıyor** — `handleMissing()` yalnız **asıl** view için tetikleniyor,
  header/footer **sessizce** atlanıyor (`FrontendProvider.php:97-105`
  `if ($headerPath && file_exists($headerPath))`). Bu, kasıtlı bir "opsiyonel
  katman" mı, yoksa atlanmış bir bağlantı mı — **koddan ayırt edilemiyor**.
* **Nasıl çözülür:** `Bundles/RbnSuite/RbnAdmin` ve `Bundles/Internal/Webhub`
  kaynaklarında `Layouts/header` adına karşılık gelen bir view adı aranır;
  bulunamazsa karar iki seçenekten biri:
  (a) `ViewResolver`'a bir **ad eşleme tablosu** (alias) eklenir,
  (b) `FrontendProvider` bu adları gerçek dosya yollarıyla değiştirir.
  Karar patronundur.
* **Belgelenen davranış:** [Core/Render/Resolvers.md §3](Core/Render/Resolvers.md),
  [Core/Render/Controllers.md §5](Core/Render/Controllers.md).

### 1.11 `UI/AjaxProvider` kayıtsız ve çözümlenemiyor (ölü kod); `UI/ViewProvider` ise kayıt dışı çözülüyor

* **Ne soruldu:** `Core/Render/Providers/UI/AjaxProvider.php` gerçekten
  kullanılıyor mu? `UI/ViewProvider.php` neden kayıt haritasında yok?
* **NEREYE bakıldı:** `Core/System/Registries/RegistryMap/SystemRenderMapTrait.php:47-61`
  (`providers` — 10 kayıt; `AjaxProvider` **yok**), `:100`
  (`'ajax' => 'partials'` alias), `Core/Render/View.php:53-57`
  (`$data['ajax'] === true` ise `$this->type = 'ajax'`),
  `Core/Base/Concerns/Traits/Controller/ActionControllerTrait.php:291`
  (`'ajax' => true` tetikleyicisi),
  `Core/System/Discovery/Clusters/Logic/Component/ComponentContext.php:84-99`
  (3 aşamalı çözümleme). Ölçüm: `provider('ajax')` ve `provider('view')`.
* **Sonuç (ölçüldü):**

  | Sınıf | Durum |
  |---|---|
  | `UI/ViewProvider` | **Çalışıyor**, ama `renderMap['providers']` içinde **yok**. `NamespaceResolver::find('view','provider')` → `'Rbn\Framework\Core\Render\Providers\UI\ViewProvider'` (tam yol). `FrontendBaseController::viewExists()` ve `BaseRender::viewProvider()` bunu kullanıyor. |
  | `UI/AjaxProvider` | **Ölü kod.** `View.php:53` `type='ajax'` atar → `RenderService::render('ajax', …)` → `provider('ajax')` → alias `ajax => partials` → **`PartialProvider`**. Sınıfa **hiçbir yoldan** ulaşılamıyor. `class_exists` true (dosya mevcut) ama kayıt yok. |

* **Neden hâlâ belirsiz:** İki sınıf da aynı arayüzü sunuyor
  (`render(?string $view, array $data=[]): string`) — `AjaxProvider::render()`
  `PartialProvider::render()` ile imza-uyumlu, bu yüzden **yanlışlık sessizce
  geçiyor**. `View.php:53` yorumu "Shift to AjaxProvider" der; bu yorum
  **kodun davranışıyla çelişiyor**. `ViewProvider`'ın kayıt dışı çözülmesi
  de "tek doğruluk kaynağı" ilkesinin ihlali: aynı sınıf iki farklı haritadan
  gelebiliyor.
* **Nasıl çözülür:** ya `AjaxProvider` silinir (alias `ajax => partials` kalır),
  ya da `SystemRenderMapTrait.php:100`'de `'ajax' => 'ajax'` yapılıp
  `providers['ajax']` kaydı eklenir. `ViewProvider` için `providers['view']`
  satırı `SystemRenderMapTrait.php:47-61` içine eklenmelidir.
  Karar patronundur.
* **Belgelenen davranış:** [Core/Render/Providers.md §4](Core/Render/Providers.md).

### 1.12 `RobotsConfig::cms_version` ile `SeoConfig::VERSION` çakışıyor

* **Ne soruldu:** `robots.txt` çıktısındaki `cms_version` ile `SeoConfig::VERSION`
  aynı değeri (`1.3`) taşıyor. Bu iki sürüm aynı kavram mı?
* **NEREYE bakıldı:** `Core/Render/Configs/RobotsConfig.php:132`
  (`DEFAULT_CONFIG['cms_version'] = '1.3'`), `:15` (`VERSION = '1.0'`),
  `Core/Render/Configs/SeoConfig.php:19` (`VERSION = '1.3'`). Ölçüm:
  `RobotsConfig::DEFAULT_CONFIG` ve `SeoConfig::VERSION` reflection ile okundu.
* **Sonuç (ölçüldü):**

  | Sabit | Değer |
  |---|---|
  | `SeoConfig::VERSION` | `1.3` |
  | `RobotsConfig::DEFAULT_CONFIG['cms_version']` | `1.3` |
  | `RobotsConfig::VERSION` | `1.0` |

  Tam `DEFAULT_CONFIG` (12 anahtar): `crawl_delay:1`, `bing_crawl_delay:1`,
  `yandex_crawl_delay:2`, `ecommerce_enabled:false`,
  `google_news_enabled:false`, `social_media_crawl:true`,
  `china_market:false`, `yandex_clean_param:true`,
  `custom_allowed_paths:[]`, `custom_disallowed_paths:[]`,
  `custom_banned_bots:[]`, `cms_version:"1.3"`.
* **Neden hâlâ belirsiz:** İki sürümün **farklı sistemlere** ait olduğu
  koddan anlaşılıyor (`cms_version` = SEO/SEO-hizmet sürümü,
  `SeoConfig::VERSION` = SEO motor sürümü) ama aralarında **çapraz bağı yok**:
  `SeoConfig::VERSION` değişirse `cms_version` **elle güncellenmek zorunda**.
  Aynı dosyada `RobotsConfig::VERSION = '1.0'` ile
  `DEFAULT_CONFIG['cms_version'] = '1.3'` yan yana duruyor — hangisinin
  `robots.txt`'ye yansıdığı ayrıca ölçülmedi (`RobotsResolver::resolvePayload()`
  çalıştırılmadı; çıktı beklenmedik bir hata döndü).
* **Nasıl çözülür:** ya `cms_version` `SeoConfig::VERSION`'a bağlanır, ya da
  ayrı sürümleme politikasının gerekçesi yazılır. Karar patronundur.
* **İlgili not (ayrı tutarsızlık):** kayıt haritasında `AssetConfig` ve
  `SeoConfig` için `Core\Render\Config\` (**tekil**) yazılmış
  (`SystemResourceMapTrait`, [System/Registries.md §5](Core/System/Registries.md));
  gerçek dizin adı **`Configs` (çoğul)**.
* **Belgelenen davranış:** [Core/Render/Configs.md §4, §7](Core/Render/Configs.md).

### 1.13 `SettingsConfig::DEFAULT_SETTINGS` içinde `contact-adress` yazımı

* **Ne soruldu:** `contact-adress` anahtarı hangi yazımla saklanıyor ve
  tüketiciler hangisini okuyor?
* **NEREYE bakıldı:** `Core/Services/System/Models/SettingsConfig.php:24`
  (`DEFAULT_SETTINGS`; ölçüldü: **54** kayıt, 54 `setting_key` listesi),
  `Core/Render/Providers/UI/FrontendProvider.php:37` (`settings->read('contact')`),
  `Core/Render/Resolvers/SeoResolver.php:30` (aynı).
* **Sonuç (ölçüldü):** anahtar **`contact-adress`** (İngilizce `address`
  yerine Türkçe yazım) olarak **sabitlerde** geçiyor ve tüketiciler grup
  **dizisini** (`$contact`) okuyup içindeki anahtarlara erişiyor.
  `DEFAULT_SETTINGS` **sıralı (0..53) dizi**, `group_key` ile indekslenmiş
  harita **değil**; haritayı `SettingsService::read()` üretir.
* **Neden hâlâ belirsiz:** **Yok — koddan çözüldü** (yazım sabittir ve
  tutarlıdır). Madde yalnız **düzeltilirse neyin güncellenmesi gerektiğini**
  kayda geçirmek için tutulur: `SettingsConfig::DEFAULT_SETTINGS`
  (tek kaynak) ve `SettingsService::read('contact')` dönüş anahtarı.
  Tüketiciler dizi aldığı için otomatik uyarlar → risk düşüktür.
* **Belgelenen davranış:** [Core/Services/System.md §6, §10](Core/Services/System.md).

### 1.14 `CPanelProvider` cURL SSL doğrulamasını kapatıyor

* **Ne soruldu:** cPanel UAPI çağrısında `CURLOPT_SSL_VERIFYPEER/HOST`
  neden `false`? Bu kasıtlı mı?
* **NEREYE bakıldı:** `Core/Services/Hosting/Providers/CPanelProvider.php:90-101`
  (URL + curl seçenekleri), `:43-57` (tembel kimlik bilgisi, fail-closed),
  `:26-35` (`afterBoot()` sır okumaz).
  **Bu çalışmada `secrets.php` açılmadı ve cPanel API çağrısı yapılmadı**
  (gece görevi kuralı), dolayısıyla **canlı davranış ölçülmedi**.
* **Sonuç (koddan):** curl seçenekleri: `CURLOPT_HTTPAUTH => CURLAUTH_BASIC`,
  `CURLOPT_USERPWD => "user:token"`, `CURLOPT_SSL_VERIFYPEER => false`,
  `CURLOPT_SSL_VERIFYHOST => false`; zaman aşımları `connect_timeout: 5`,
  `timeout: 15`. Yani cPanel Basic kimlik bilgileri **TLS el sıkışması
  doğrulanmadan** gönderiliyor. Kodda **gerekçe yorumu yoktur**.
* **Neden hâlâ belirsiz:** SSL kapatmanın gerekçesi kodda **yazılı değil**.
  Olası nedenler: cPanel sertifikasının özel CA ile imzalanması (bu durumda
  doğru çözüm `CURLOPT_CAINFO` vermektir) ya da geliştirme kolaylığı.
  Hangisi olduğu koddan ayırt edilemiyor.
* **Nasıl çözülür:** üretimde `openssl s_client -connect <cpanel-host>:2083`
  ile sertifika zinciri incelenir; zincir güvenilirse `CURLOPT_CAINFO`
  verilerek `SSL_VERIFYPEER` açılır. Güvenlik kararıdır; patronundur.
* **Belgelenen davranış:** [Core/Services/Hosting.md §6.3](Core/Services/Hosting.md).

---
### 1.5 `projects.public_path` biçimi tutarsız

`Paths::publicRoot()` `public_path`'i `workspace + '/domains/' + public_path` olarak
birleştirir (`Core/System/Paths/Paths.php:112-121`). Ölçümde 19 kaydın **18'i**
`domains/` öneki olmadan, **1'i** `domains/` önekiyle yazılmış.

**Koddan çözülen kısım (DOC-AGAC-2):** önekli kayıt için birleştirilmiş yol
`domains/domains/...` olur, `is_dir()` başarısız olur ve `publicRoot()` **sessizce
`Paths::init()`'e verilen `public` yoluna düşer** (`Paths.php:116-121`); giriş
noktası doğru dizini verdiği için kullanıcıya görünen hata yoktur. Ayrıntı:
`docs/Core/System/Paths.md` §5.5.

**Hâlâ açık:** önekli kaydın **veri hatası mı, kasıtlı mı** olduğu koddan
anlaşılamıyor; master `projects` tablosunda ön kontrol/normalize **yoktur**
(`ProjectDataMapper.php:85-87` doğrudan `SELECT *`). **Nasıl çözülür:** master
`projects` tablosunda önekli kaydın sahibine sorulur; kayıt düzeltilirse
(`public_path` öneksiz) bu madde kapanır. Veri değişikliği olduğu için bu belge
salt okunur kaldı.

### 1.6 `PurgeConfig::TYPES` ile `SyshubMap` depolama türleri uyuşmuyor

* `PurgeConfig::TYPES` altı tür tanımlar ve **`framework`** anahtarını içerir
  (`Bundles/Internal/Syshub/Models/PurgeConfig.php:51`).
* `SyshubMap::MAP['sub_modules']['datapurge']['sub_modules']` yedi anahtar içerir ve
  **`framework` yok**, **`view` var** (`Bundles/Internal/Syshub/Models/SyshubMap.php:52-60`).
  `view` anahtarı `PurgeConfig`'te hiç geçmiyor.
* **Araştırıldı (05.10.2026, DOC-AGAC-4):** iki liste bağımsız yazılmış.
  `DataPurgeHandler::getProviderStats()` yedi türü `service('storage')` üzerinden
  gerçekten sorgular (`DataPurgeHandler.php:60-66`), dolayısıyla **çalışma** tarafı
  doğru; uyuşmazlık yalnız **arayüz metni** katmanında. `PurgeController::index()`
  eksik anahtarda `bg_color` yerine `#6366f1` yazıyor (`PurgeController.php:39`),
  yani `view` kartı panelde varsayılan renkte görünür. `PurgeConfig::TYPES`'taki
  `framework` girdisi ise **hiçbir yerden okunmuyor** (ölü sabit).
* **Neden hâlâ belirsiz:** `framework` girdisi kasıtlı olarak silinmemiş bir geriye
  dönük kalıntı mı, yoksa `storage/framework` yolu (`SyshubHandler.php:30`) başka
  bir alt modül olarak mı planlanıyor — kodda bu yönde bir işaret yok.
* **Nasıl çözülür:** `PurgeConfig::TYPES` içindeki `framework` girdisi
  `view` ile değiştirilir veya tamamen silinir; kararı modülün sahibi verir.

---

## 2. Kodda doğrulanamayan davranışlar

### 2.3 `version:next` hata yolları

`--apply` **olmadan** çalıştırıldığında hiçbir şey yazılmadığı ve çıkış kodunun `1`
olduğu **yalnız belgeden** (`kavramlar/04-surumleme-ve-yayin.md` §4.2,
`.github/UPGRADING.md:51-53`) ve koddan (`VersionHandlers.php:124, 130, 140`)
biliniyor. Canlı bir **kuru koşu ölçümü yapılmadı**.

### 2.4 `CITATION.cff` sürüm alanı

`version:check` bu dosyadan framework sürümünü okuyup **eşitliği** denetliyor
(`Core/Services/Console/Handlers/VersionHandlers.php:64-67, 217`).
Dosyadaki **alan adı** bu çalışmada okunmadı.

### 2.5 `applications` tablosu denetim yolu

`version:check` `masterSurumleri()` üzerinden hem `projects` hem `applications`
sürümlerini denetler (`VersionHandlers.php:74-78, 165`), ancak yerel master'da
`applications` **boş** olduğu için bu dal ölçümde hiç görünmedi.

### 2.8 Üretim web sunucusu Apache mi, nginx mi?

**Soru:** `SystemDoctor` ve `PermissionDoctor` Apache'ye özgü davranır
(`SERVER_SOFTWARE` içinde `Apache` varsa `.htaccess` zorunlu; `Storage/` altına
`Order Deny,Allow / Deny from all` yazılır). Üretimde hangisi çalışıyor?
**Nereye bakıldı:** `Core/System/Kernel/Guards/SystemDoctor.php:58-67`,
`PermissionDoctor.php:199-211` ve `docs/Core/System/Kernel.md` §5.4-5.5;
yerel çalışma Windows'ta olduğu için sunucu yazılımı ölçülemedi.
**Neden hâlâ belirsiz:** `SERVER_SOFTWARE` yalnız canlı bir istekte okunabilir; kodda
sunucu yazılımını sabitleyen bir ayar yok.
**Nasıl çözülür:** üretimde `php -r` yerine bir sağlık sayfasından (`$_SERVER['SERVER_SOFTWARE']`)
ya da hosting panelinden sunucu türü okunur; nginx ise `Storage/` dizin korumasının
`.htaccess` yerine sunucu yapılandırmasıyla sağlandığı doğrulanır.

### 2.9 `<workspace>/.cache` dizininin üretimdeki gerçek izni ve erişilebilirliği

**Soru:** `BootCacheProvider` proje verisini (alan adı, `custom_path`, ayarlar,
`bot_activity` açıksa API anahtarları) düz metin JSON olarak `<workspace>/.cache/`
içine yazar ve dizini `mkdir(…, 0777, true)` ile oluşturur
(`Core/System/Storage/Providers/BootCacheProvider.php:56-61`; ayrıntı:
`docs/Core/System/Storage.md` §5.3). Dizin üretimde hangi izinle oluşuyor ve web
kökünün dışında mı?
**Nereye bakıldı:** yalnız kod (`Paths::workspace()` = `rbnframework`'ün üst dizini);
Windows'ta POSIX izni ölçülemez.
**Neden hâlâ belirsiz:** gerçek izin umask'a ve hosting yapılandırmasına bağlıdır,
`public` kökünün workspace'in neresinde olduğu canlı düzenle belirlenir.
**Nasıl çözülür:** üretimde `stat -c '%a %U' <workspace>/.cache` ve alan adından
`/.cache/` yolunun 404/403 verdiği (`curl -I`) doğrulanır.

### 2.10 `blogAutopilot` servisi ve `Ssblogs.post` görünümü framework içinde tanımsız

**Soru (05.10.2026, DOC-REHBER-AGAC-5 — `Bundles/RbnSuite/RbnStudio`):**
`DraftsController::generate()` şu iki şeyi kullanıyor:
`service('blogAutopilot')->generateArticle($id, 'Ssblogs.post', 'app.contentDraft',
'app.contentCategory', $projectKey, ['has_author_comment'=>true])`
(`Bundles/RbnSuite/RbnStudio/Controllers/DraftsController.php:128-135`).
Bu servis ve bu görünüm adı **hangi projede** tanımlı?

**Nereye bakıldı:** `grep blogAutopilot E:\localhost` → **1** eşleşme
(çağıranın kendisi); `grep Ssblogs E:\localhost` → **1** eşleşme (aynı satır).
Kayıt defteri taraması da boş: `Core/System/Registries/RegistryMap/*` içinde
`blogAutopilot` geçmiyor; `Packages/PackageData.php` içinde de yok.
Karşılaştırma: `DraftsController::rewrite()` yolu framework'e ait bir builder'ı
kullanıyor ve **kayıtlı**: `task.content_rewrite →
RbnPipeline\Builders\Tasks\ContentRewriteTaskBuilder` (`Packages/PackageData.php:95`,
sınıf `Packages/RbnPipeline/Builders/Tasks/ContentRewriteTaskBuilder.php:18`).

**Neden hâlâ belirsiz:** `E:\localhost\projects\` altındaki projeler bu
çalışmada kapsam dışı (bu dizinin konusu değil) ve orada tanımlı olabilirler;
ayrıca servis adı bir **proje tanımından** (`project-routemap.php`) geliyor olabilir.
Koddan ayırt etmek mümkün değil — iki olasılık da koda uygun.

**Nasıl çözülür:** ya (a) tanımı olması gereken projelerde
`grep -rn "blogAutopilot" projects/` ve `grep -rn "Ssblogs" projects/` çalıştırılır,
bulunan dosya `RbnSuite/RbnStudio/README.md` §5.2'ye not edilir; ya da (b) karar:
**RbnStudio, framework paketi olduğu için bu bağımlılığı `RbnStudioController`'ın
`getContentConfig()` anahtarlarından okumalıdır** (`content.blog.autopilot_service`
gibi) — çünkü şu haliyle paket, tanımsız bir servise bağlıdır ve tanım olmayan
projede `GET /admin/studio/drafts/generate/{id}` **500** verir.

### 2.11 `TrafficAnalysisTrait`'in ölü ve yanlış etiketli yolu hangi ekranda kullanılıyor?

**Soru (05.10.2026, DOC-REHBER-AGAC-5 — `Bundles/RbnSuite/RbnAdmin`):**
`AnalyticsProvider::getDashboardStats()` (`Providers/AnalyticsProvider.php:25`)
hiçbir yerden çağrılmıyor ve içindeki `'today_hits' => $traffic->hits()`
(`:32`) `TrafficAnalysisTrait::hits()` → `count()` → **tüm tarihlerin toplamı**
(`Traits/TrafficAnalysisTrait.php:316-319, 123-132`) yoluna düşüyor; yani
`today_hits` etiketi yanlış. Aynı isimli `TrafficStatsChannel::hits()`
(`Providers/Fluent/TrafficStatsChannel.php:52-58`) ise **bugünü** döndürüyor.
Bu ölü yol **silinecek** mi, yoksa bir ekran hâlâ `AnalyticsProvider`'ı mı
çağırıyor?

**Nereye bakıldı:** `grep getDashboardStats E:\localhost` → 8 eşleşme; 4'ü
`Internal/Syshub`'ın **kendi** benzer adlı metodu
(`Bundles/Internal/Syshub/Handlers/SyshubHandler.php:26`,
`Services/SyshubService.php:80`, `Controllers/SyshubController.php:25`,
`Controllers/PurgeController.php:30`), 1'i `SyshubSecurityHandler`'ın çağrısı,
1'i tanımın kendisi. **RbnAdmin'ın `AnalyticsProvider::getDashboardStats()`'ına
hiçbir çağrı yok.** Panelin kullandığı yol ölçüldü:
`RbnAdminController.php:75-80` ve `WebtrafficController.php:21-22,116,202`
üçü de `->traffic()->summary()` çağırıyor, yani `TrafficStatsChannel` yolu.

**Neden hâlâ belirsiz:** hiçbir çağıran kalmadığı için "ölü" olduğu kesin;
ancak **bu görev yalnız belge üretti**, kod değiştirilmedi. Silinip silinmeyeceği
bir kod kararıdır ve bu belgenin kapsamı dışındadır.

**Nasıl çözülür:** `AnalyticsProvider::getDashboardStats()` silinir veya
`today_hits` satırı `TrafficStatsChannel::hits()` ile aynı anlama getirilir;
`Bundles/RbnSuite/RbnAdmin/Providers.md` §5.2 bölümü buna göre güncellenir.

### 2.12 `AuthIdentity::DEFAULT_INFO` bayat metinleri ekranda görünüyor mu?

**Soru (05.10.2026, DOC-REHBER-AGAC-5 — `Bundles/RbnSuite/RbnAuth`):**
`DEFAULT_INFO['forgot']` içinde "Geçici Erişim Kodu Desteği",
`DEFAULT_INFO['verify']` başlığında "Kodu Doğrula" / "15 Dakika Boyunca Geçerli
Kod" yazıyor (`Models/AuthIdentity.php:68-93`). Oysa 6 haneli kod akışı
**kaldırılmış** (`AuthViewController::showVerifyCode()` artık
`/forgot-password`'a yönlendiriyor, `:132-139`) ve parola sıfırlama token'ı
**60 dakika** (`RecoveryService.php:31`). Bu metinler kullanıcıya gösteriliyor mu?

**Nereye bakıldı:** `Core/Render/Handlers/UI/AuthHandler.php:38-50` —
`$info['features']` (`:50`) ve `$info['icon']` (`:49`) görünüme aktarılıyor;
`forgot-password` görünümü `Resources/Views/RbnAuth/auth.rbn.php` içinde
`siteFeatures`'i basıyor. **Görünüm dosyasının içeriği bu çalışmada
ayrıntılı okunmadı** (görünüm envanteri `Resources` görevine ait, DOC-REHBER-AGAC
kapsamı dışı).

**Neden hâlâ belirsiz:** `DEFAULT_INFO` "Settings tablosu boşsa kullanılır"
diye etiketli (`AuthIdentity.php:45-46`) — yani proje ayarlarından metin
gelirse bayat metinler **görünmez**; gelmezse görünür. Hangi yolun işlediği
canlı bir giriş sayfası isteği gerektirir.

**Nasıl çözülür:** `/forgot-password` sayfası canlıda açılır ve
`siteFeatures` listesinin "Geçici Erişim Kodu" satırını içerip içermediğine
bakılır. İçeriyorsa `DEFAULT_INFO['forgot']` ve `['verify']` güncellenir
(`.github/CHANGELOG.md`'ye not düşülerek).

### 2.13 RbnStudio'daki `Route::get('delete/{id}')` uçları gerçekten erişilebilir mi?

**Soru (05.10.2026, DOC-REHBER-AGAC-5 — `Bundles/RbnSuite/RbnStudio`):**
Dört grupta da `Route::get('delete/{id:[0-9]+}')` **ve** aynı adlı
`Route::post(...)` kayıtlı (`Models/ModuleData.php:43-44, 52-53, 63-64, 78-79`),
ikisi de `admin.studio.<x>.delete` adına bağlı. Aynı adın iki kez kaydedilmesi
yönlendirme/önbellekte hangi rotanın geçerli olduğunu belirsiz kılıyor mu?
GET sürümü bir `<img src>` ile silme yapabiliyor mu?

**Nereye bakıldı:** rota tanımları (`ModuleData.php:43-44, 52-53, 63-64, 78-79`);
karşılaştırma: RbnAdmin'ın tüm silme uçları POST'tur
(`RbnAdmin/Models/ModuleData.php:94, 144-146, 155, 76`). `Dispatch` katmanında
CSRF `middleware('admin')` grubunun **dışında** bir kontrolü yok —
`RouteBlueprint::MIDDLEWARE['groups']['admin'] = ['auth', 'role:admin']`
(`Core/Support/Definitions/Route/RouteBlueprint.php:48`), yani yalnız oturum +
rol denetlenir, istek yöntemi denetlenmez. CSRF doğrulaması `request->form()`
içinden gelir (`Controllers/RbnAuth` ve `CrudControllerTrait` yolları); bu
kontrolörler `request->input()` ile **ham** okuduğu için (`PostsController::save()`
`:112-124`, `NewsController::save()` `:114-134`, `CategoriesController::save()`
`:74-82` `form()` kullanıyor) davranış **kontrolör başına değişir**.

**Neden hâlâ belirsiz:** aynı adın iki kez kaydı Router'ın ad çözümlemesinde
hangi tanımın kazandığı koddan **kesin** okunamadı; ayrıca
`DraftsController::delete()` zaten 500 verdiği için (bkz.
`Bundles/RbnSuite/RbnStudio/README.md` §5.1) o uçtaki GET riski ölçülemez.
`posts`/`news`/`categories` gruplarının GET sürümü gerçekten silme yapıyorsa bu
bir CSRF açığıdır.

**Nasıl çözülür:** (a) Router'ın ad-defteri çakışmasında hangi kaydın
kazandığı, `Route::load()` → `Router::addRoute()` sonrası ad listesinin
okunmasıyla (`Core/Routes/Route.php:152`) belirlenir; (b) üç grubun GET
`delete` ucu elle bir `<img src>` ile çağrılıp kaydın silinip silinmediği
gözlemlenir (üretimde, geçici bir kayıtla). Bulgu netleşince GET satırları
silinir ve `.github/UPGRADING.md`'ye kırıcı değişiklik notu düşülür.

---

## 3. Ölçülmesi gerekenler (bu çalışmada yapılmadı)

| Konu | Nasıl ölçülür | Neden yapılmadı |
|---|---|---|
| `MasterRbnHeartbeatsModel` hangi tabloyu kullanıyor | `php rbn db:tables --master` (çağıran araştırıldı, bulunamadı; bkz. §1.2) | Çağıran kod yok; yalnız tablo adı ölçümü kaldı |
| `view_mapping` boş dizi unutulduğunda gerçek hangi ayar miras kalıyor | İki siteli bir proje klasöründe `robots` farkı ölçümü | Canlı siteye müdahale gerektirir |
| `security.form_input_mode = log` ile bir günlük tur | `.github/UPGRADING.md:534-541` tarif ediyor | Canlı ölçüm turu; görev kapsamı dışı |
| `tenant:audit` ile 7 hedef veritabanının güncel kolon envanteri | `.github/UPGRADING.md:379` | Yalnız `SELECT` olsa da çok veritabanı; gece görevi kapsamı dışı |

---

## 4. Bu belgenin kapsamı dışında bırakılan konular

Bunlar **bu dizinin** konusu değil; başka yerde tam olarak anlatılıyor:

* Kurulum / sır dosyası adımları → kök `README.md:30-95`
* Güvenlik bildirimi ve desteklenen sürümler → `.github/SECURITY.md`
* Bilinçli olarak sıkılaştırılmamış tercihler → `.github/KNOWN-LIMITATIONS.md`
* Sürüm başına zorunlu yükseltme adımları → `.github/UPGRADING.md`
* Kod standartları (`new` yasağı, Repository/Provider ayrımı, §9/§10)
  → `.agents/rules/core-architecture.md`
* Veritabanı keşif standardı → `.agents/rules/database-and-cli.md`
* Gece vardiyası çalışma kuralları → `E:\AgentSpace\docs\gorevler\FW-GECE-ORTAK-KURALLAR.md`
