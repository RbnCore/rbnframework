## [Unreleased]

## 0.9.2 (yayın hazırlığı — 2026-10-05)

### Bu sürümde ne yapmalısınız (kısa liste)

Ayrıntılı anlatım aşağıdaki başlıklarda; bu liste yalnızca sırayı gösterir.

1. **Framework dosyalarını güncelleyin** (paketi değiştirin). Kırıcı değişiklik
   **vardır** — aşağıdaki PHP 8.3, kiracı izolasyonu, form() ve bağlantı adı
   maddeleri eski sitelerde davranış değiştirir; yayınlamadan önce hepsini okuyun.
2. **`project-settings.php` içindeki `has_route_map` / `dashboard_prefix`
   satırlarını silebilirsiniz.** Panel ön eki artık **tek kaynaktan**
   (`project-routemap.php`) okunuyor; bu iki anahtar ikinci kaynak olarak kaldırıldı.
   Dosyada kalırlar siteyi **bozmaz** (geriye uyum korundu), yani bu adım
   **zorunlu değildir** — yalnızca kalıntıları temizler.
3. **Proje sürümlerini master DB'den yönetin** (`projects.version`).
   Değerler `A.B.C` kuralına çevrilmeli: migration'ı elle çalıştırın, sonra
   önbelleği temizleyin ve `rbn version:check` ile doğrulayın.
4. **favicon / og görseli dosya adlarını yeni kurala uydurun**
   (`images/favicon-<project_key>.*`, `images/og-image-<project_key>.*`).
   Anahtarsız eski adlar hâlâ çalışır (geriye uyum) ama kullanımdan kaldırıldı.
5. **CSS paketlerini bilin:** `rbnExtended` otomatik yüklenmez; proje isterse
   `assets => ['rbnExtended']` ile açıkça istemek zorundadır.


### Sürümleme: `APP_VERSION` artık proje sürümüdür (tek kaynak: master DB)

**Davranış değişikliği (görünür):** `APP_VERSION` sabiti `3.5.0` gibi sabit bir
framework değeri olmaktan çıktı; artık **projenin sürümüdür** ve master DB'deki
`projects.version` kolonundan okunur. Bu, şu çıktıları değiştirir:

| Çıktı | Önce | Sonra |
|---|---|---|
| `{{APP_VERSION}}` (sablon) | `3.5.0` | projenin `projects.version` değeri |
| `module-version` (`<meta name="module-version">`) | `1.0` | aynı değer |
| `siteVersion` (giriş ekranı) / panel `app_version` | `1.0` / `1.0` | aynı değer |
| `domains/rbnbilisim/email.rbncore.tr` | `1.17.0` (giriş noktasında sabit) | aynı değer |
| RbnShield / RBN Admin Pro / RbnAuth / CLI sürümü | `v2.1` / `1.2` / `2.2` / `2.3` | `2.1.0` / `1.2.0` / `2.2.0` / `2.3.0` |

**Geriye uyum:** Sabit **her zaman** geçerli bir `A.B.C` sürümü üretir. Kayıt
yoksa veya bozuksa standart başlangıç sürümü `0.1.1` kullanılır; hiçbir site
`1.0`, `1.17.0` veya `3.5.0` gibi geçersiz bir değer basmaz. `APP_VERSION`
giriş noktasında önceden tanımlanmışsa ezilmez.

**Siz yapmanız gerekenler:**

1. **Giriş noktalarınızda `define('APP_VERSION', ...)` yazmayın.** Sürüm artık
   veritabanından gelir; sabit yazmak kural ihlalidir (canlıdaki tek örnek
   `domains/rbnbilisim/email.rbncore.tr/index.php` idi ve kaldırıldı).
2. **Sürümü elle yazmayın.** Master hub → proje kaydındaki `version` alanını
   güncellemek yerine `rbn version:next <project_key> --apply` kullanın
   (varsayılan kuru koşudur). Denetim için: `rbn version:check`
   (çıkış kodu `0` = temiz, `1` = sapma).
3. **Veri geçişi (canlıda elle, sıra önemli):**
   ```bash
   php rbn master:migrate          # 1) yedek + 0.1.1'e çevir
   php rbn cache:clear             # 2) keşif önbelleğini temizle
   php rbn version:check           # 3) doğrula (çıkış kodu 0 olmalı)
   php rbn master:migrate --rollback   # GERİ ALMA: yedekteki değerler geri yazılır
   ```
   Yedek tablo: `projects_version_backup` (geri alma sonrası **silinmez**).
4. **Şema değişmedi.** `projects.version` kolonu `varchar(20) DEFAULT '1.0'`
   olarak **aynen durur**; yalnız satır değerleri değişmiştir. Yeni proje kaydı
   yazan yol artık `Version::initial()` (`0.1.1`) yazar. Kolon `DEFAULT`'unu da
   `0.1.1` yapmak isterseniz bu **ayrı** bir şema kararıdır (patron onayı gerekir).

**Ölçüm (yerel master DB, `rbncore_master`):** 19 proje, hepsi `1.0` (geçersiz)
→ migration sonrası 19 satır `0.1.1`; yedekte 19 satır `1.0`. `applications`
tablosu boş (0 satır); bu migration ona dokunmaz.

### Favicon ve OpenGraph görseli: tek adlandırma kuralı

**Yeni kural (tek kaynak: `Core\Support\Definitions\Render\AssetConvention`):**

| Varlık | Kural (yeni dosyalar için) |
|---|---|
| Favicon | `images/favicon-<project_key>.svg` \| `.png` \| `.ico` |
| OpenGraph | `images/og-image-<project_key>.png` \| `.jpg` \| `.webp` |

`<project_key>` = `project-routemap.php` `view_mapping` anahtarıdır (örn. `ornek-proje-1`,
`ornek-alt-site`). Framework'e proje adı **yazılmaz**; kural her proje için aynıdır.

**Yapmanız gereken:** yeni bir favicon/og görseli eklerken **kural adını** kullanın.
Örnek: `domains/customers/<site>/images/favicon-<key>.svg`.

**Geriye uyum (canlıyı kırmaz):** Anahtarsız eski adlar (`images/favicon.png`,
`images/favicon.svg`, `images/og-image.png`) canlıda çalışıyor ve **"son geri dönüş"**
olarak korundu — kural adı bulunamazsa bunlara düşülür. Yani mevcut dosyalarınızı
taşımak zorunda değilsiniz; taşırsanız daha temiz olur. Taşıma **isteğe bağlıdır**.

**Beklenen davranış değişiklikleri:**

1. `og:image` artık **çalışan** bir adres verir (daha önce her sitede 404/500 idi).
2. `/favicon.ico`, `/og-image.png`, `/apple-touch-icon.png` gibi standart yollar artık
   projenin **gerçek** varlık dosyasına yönlenir (uyantı yok; doğru uzantı).
3. Projenin og görseli **yoksa** `og:image` meta etiketi **hiç üretilmez** ve JSON-LD'de
   `image` alanı düşer. Sosyal paylaşım önizlemesi görselsiz kalır (uydurma görsel
   gösterilmez) — bu, kuralın kendisinden gelir.
4. `project-routemap.php` içindeki `favicon` ve `og-image` / `og_image` anahtarları
   hâlâ **toler edilir**, ancak **öncelikli değildir** (kural önce gelir).
   **Bu anahtarlar kaldırılabilir**; kaldırıldığında motor davranışı DEĞİŞMEZ çünkü
   kural zaten önceliklidir. Kaldırma kararı patronundur (bu sürümde dosyalara dokunulmadı).

### Panel ön eki (`dashboard_prefix`) ve `has_route_map`: tek kaynak `project-routemap.php`

**Ne değişti (iki madde):**

1. **`Config.php` — `has_route_map` bayrağı ARTIK OKUNMUYOR.** `project-settings.php`
   yüklenirken, aktif `project_key` için `project-routemap.php`
   `view_mapping[<key>]` değerleri **her zaman** `project-settings` üstüne birleşir.
   Önceden bu birleştirme yalnızca `has_route_map` doğruysa çalışıyordu.
2. **`SystemGuardHandler` — bakım modu muafiyeti artık `project-settings` dosyasından
   `dashboard_prefix` OKUMAZ.** Sıra: `project-routemap.php` → BootCache →
   `project_data('dashboard_prefix')`; o da boşsa framework sabiti
   (`RouteBlueprint::DASHBOARD_PREFIX` = `dashboard`).

**Yapmanız gereken:** **HİÇBİR ŞEY.** `project-settings.php` içinde
`has_route_map` ve `dashboard_prefix` **kalsa bile** motor çalışır — bu iki anahtar
artık yalnızca YOK SAYILIR, hiçbir davranışı etkilemez. **Geriye uyum bozulmadı.**

**Temizlik (isteğe bağlı):** `project-settings.php` içinden bu iki satırı
silebilirsiniz. `dashboard_prefix` siliniyorsa, silmeden önce
`project-routemap.php` içindeki `view_mapping` bloğunda **her site için** aynı
değerin tanımlı olduğundan emin olun. Routemap'te karşılığı olmayan bir satırı
silmek, o projenin panel yolunu `dashboard`'a çevirir; o durumda önce tek kayda
taşıyın (`view_mapping[<site>]['dashboard_prefix']`), sonra silin.

**Beklenen davranış değişikliği:** `has_route_map` yazılmayan bir projede
`view_mapping` artık **uygulanır** (önceden uygulanmıyordu). Canlıda değerler
zaten `view_mapping` içinde tanımlı olduğu için yeni bir ayar değeri İCAT EDİLMEZ;
bu, o projelerde ayarların **eksik uygulanması**nın giderilmesidir. Yayına
almadan önce `ornek-proje-1` ve `ornek-proje-2` için ölçüm yapın.

**Kırıcı değişiklik yok; imza/şema/izin/metot imzası değişmedi.**

### Proje DB bilgisi: `DB_PROFILES` (local + production) — isteğe bağlı, geriye uyumlu

**Ne değişti:** Her projenin `Core/Config/project-settings.php` dosyasında proje
veritabanı bilgisi artık iki profil altında durabilir:

```php
'DB_PROFILES' => [
    'local'      => ['DB_HOST' => …, 'DB_NAME' => …, 'DB_USER' => …, 'DB_PASS' => …, 'DB_CHARSET' => …],
    'production' => ['DB_HOST' => …, 'DB_NAME' => …, 'DB_USER' => …, 'DB_PASS' => …, 'DB_CHARSET' => …],
],
```

Motor çalıştığı ortama göre profili **kendisi** seçer. Amaç: dosya canlıya
**olduğu gibi** atılabilsin, elle düzenleme gerekmesin.

**Yapmanız gereken:** **HİÇBİR ŞEY.** Mevcut düz `DB_*` anahtarlarını
kullanmaya devam eden projeler değişmeden çalışır — `DB_PROFILES` yoksa eski
davranış birebir korunur. Yalnızca `DB_PROFILES` kullanmak isteyenler kendi
ayar dosyasını göç eder.

**Ortam nasıl seçilir (sırayla):**

1. Ortam değişkeni `RBN_DB_PROFILE` = `local` veya `production`. Sunucu cron'ı
   gibi HTTP'siz ortamlarda operatörün kararı açıkça bildirmesi içindir.
   Başka bir değer (`yerel`, `1`, boş) **karar yok** sayılır ve sıradaki
   kurala geçilir — yazım hatası profili çalıştırmaz.
2. **Framework kökündeki** tam `localhost` yol segmenti (HTTP'de de CLI'de de
   aynı ölçüm).

Karar **sunucunun kimliğine** bakar, isteğe değil. `REMOTE_ADDR` / `Host` başlığı
bilerek kullanılmaz. Belirsizlik daima `production` sayılır.

> **Neden `is_local()` değil?** `is_local()` tarayıcıya dönük bir yardımcıdır;
> robots.txt muafiyeti gibi kararlarda istemci IP'sine bakar. Veritabanı
> seçimi böyle bir karara bağlanırsa aynı sunucuda istek kimliğine göre farklı
> veritabanlarına bağlanılır — yerel makinede dış ağdan gelen istek üretim
> profilini seçer, üretimde yerel profil denenir. Karar `E:/localhost` gibi
> framework kökünden okunduğu için istekten bağımsızdır.

**Sessiz düşme yoktur.** Seçilen profil yoksa, profil içinde zorunlu bir anahtar
(`DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`, `DB_CHARSET`) eksikse ya da değer
`__DOLDUR__` yer tutucusuysa site **AÇIK HATA** verir; diğer profile veya
`root`/boş parolaya düşmez.

**Önerilen göç (isteğe bağlı):** `project-settings.php` dosyanızdaki düz
`DB_*` bloklarını `DB_PROFILES['local']` altına taşıyın ve `production` bloğunu
gerçek canlı değerlerle doldurun. Canlı değerleri **canlıdaki mevcut dosyadan**
programatik okuyup yerel dosyanın `production` bloğuna yazmak en güvenli yoldur
(değerleri ekrana/loga basmayın). Göç sırasında `production` henüz dolu değilse
`__DOLDUR__` ile bırakın: canlıda yanlışlıkla seçilirse motor bağlanmayı
denemeden hata verir.

### CSS motoru iki pakete bölündü; `rbnExtended` otomatik yüklenmez

**Ne değişti:** `rbn-master.css` tek bir `@import` zinciridir ve
`AssetBundles::STACK_MAP` üzerinden **her sayfaya** gider. Yeni işlevler buraya
eklenince gzip bütçesi patlıyordu. Karar: **master'ın kapanışı değişmedi**, yeni
işlevler **isteğe bağlı paketlere** kondu.

| Paket | Dosya | Otomatik yüklenir mi | gzip |
|---|---|---|---|
| `rbnExtended` | `optional/rbn-utilities-extended.css` (312 seçici) | **HAYIR** | 8 242 B |
| `rbnExtended` | `optional/rbn-components-extended.css` (153 seçici) | **HAYIR** | (aynı paket) |
| `rbn_core_auth` | `core/rbn-auth.css` | evet (auth görünümü) | 2 682 B |

**`rbnExtended` `STACK_MAP` içine GİRMEZEDİR** — hiçbir sayfaya kendiliğinden
yüklenmez. Yalnızca proje/görünüm açıkça istediğinde yüklenir:

```php
// ViewMap tarafında
'assets' => ['rbnExtended'],

// Controller tarafında
$this->addAsset('rbnExtended');
```

`AssetBuilder` hiç değiştirilmedi — `collect()` zaten bundle adını styles
listesine çözüyordu. `core/rbn-auth.css` ile `auth_header.rbn.php` içindeki 12,5 KB'lık
view-içi `<style>` bloğu motor dosyasına taşındı (17 sınıf); 29 sabit hex yerine
mevcut `--rbn-*` token'ları kullanılıyor.

**Uyum kanıtı:** `rbn_master` paketinin istediği dosyalar birebir aynı kaldı;
`rbn-master.css` kapanışı **19 dosya / 47 884 B gzip olarak DEĞİŞMEDİ**. Görsel
değişiklik yoktur.

**Geri alma:** `STACK_MAP`'ten hiçbir giriş kaldırılmadığı için `rbnExtended`'i
istemeyen projelerde hiçbir şey değişmez. Yeni paketi eklediyseniz `assets`
listesinden çıkarırsanız eski görünüme dönersiniz.

### PHP >= 8.3 gereklidir (KIRICI — sunucu tarafı adım)

**Ne degisti:** Framework artık **PHP 8.3 veya üzeri** olmayan bir sunucuda
**çalışmaz** ve bunu "Parse error" ile değil, **anlaşılır bir mesajla** söyler.

İki ayrı değişiklik:

1. **Beyan:** `composer.json` → `require.php = ">=8.3"`.
2. **Çalışma zamanı kapısı:** `Core/System/Kernel/Base/PhpVersionGate.php`.
   `vendor/autoload.php` yüklenmeden **önce** çağrılır.

**Adım 1 — Sürümü yükseltin (ZORUNLU).** Hosting panelinden sitelerin
bağlandığı PHP sürümünü **8.3** yapın. Bu yapılmadan önce hiçbir adıma
geçmeyin: kapı, sürüm 8.3'ten küçükse isteği `HTTP 503` ile reddeder ve
sayfa "şu anda kullanılamıyor" görünür.

**Adım 2 — Yeterliliği doğrulayın.** Zorunlu ilan edilen eklentiler
`composer.json`'da listelidir:

```
php >= 8.3
ext-pdo  ext-pdo_mysql  ext-mbstring  ext-json  ext-curl
ext-openssl  ext-gd  ext-zip  ext-fileinfo  ext-session
```

`apcu` **zorunlu değildir** (`LogThrottle` kendi içinde kontrol eder, APCu
olmadan da çalışır). `simplexml`, `iconv`, `intl` yalnızca iki tekil RSS /
Google Trends / yedek metin çağrısında kullanılır — **önerilir, zorunlu
değildir.**

**Adım 3 — Canlı `index.php` dosyalarını güncelleyin (geçişte zorunlu).**
Kapı, `vendor/autoload.php`'den önce çalışmak için **her sitedeki
`index.php`'nin başına** eklenmelidir. Depodaki 20 `index.php` bu blokla
güncellendi; **canlıdaki `public_html/index.php` dosyaları ayrıdır** ve
geçiş paketiyle birlikte elle aynı blok eklenmelidir. Blok şudur:

```php
// 0. [FW-CANLI-ONCESI-2] PHP surum kapisi (>= 8.3) — vendor/autoload.php'dan ONCE.
$rbnPhpSurumKapisi = null;
foreach ([2, 3, 4] as $rbnSeviye) {
    $rbnKapisiAdayi = dirname(__DIR__, $rbnSeviye) . '/rbnframework/Core/System/Kernel/Base/PhpVersionGate.php';
    if (is_file($rbnKapisiAdayi)) {
        $rbnPhpSurumKapisi = $rbnKapisiAdayi;
        break;
    }
}

if ($rbnPhpSurumKapisi !== null) {
    require_once $rbnPhpSurumKapisi;
    \Rbn\Framework\Core\System\Kernel\Base\PhpVersionGate::enforce();
}
```

Bu blok eklenmezse kapı yine de çalışır ama **autoload'dan sonra** çalışır
(`Bootstrap::run()` / `PreBoot::orchestrate()` içinde). Yani 8.3'ten eski
bir sürümde "Parse error" yerine yine mesaj alırsınız, ama autoload
kütüphanesinin kendisi daha önce yüklenmeye çalışmış olur.

**Geri alma:** Sunucu PHP sürümünü geri alırsanız framework eski hâliyle
çalışmaz (8.3 sözdizimi kullanılıyor). Geri alma, sunucu sürümünü
**8.3'e çıkarmak** yönündedir; kod tarafında geri alma gerekmez.

---

### 13 modelde kiraci izolasyonu acildi (KIRICI, model bazinda olculerek)

**Ne degisti:** Asagidaki modeller `protected bool $scoped = true` beyan
ediyor. Onceden `false` idiler, yani **kiracı filtresi hic uygulanmiyordu**
ve bir modelin `query()` cagrisi tum kiracilarin satirini donuyordu.

| model | tablo | proje |
|---|---|---|
| `FaqsModel` | `z_app_faqs` | framework |
| `PagesModel` | `z_app_pages` | framework |
| `FrontendMenusModel` | `z_app_menus_frontend` | framework |
| `SettingsModel` | `z_settings` | framework |
| `SidebarCategoriesModel` | `z_bs_sidebar_categories` | framework |
| `SidebarMenusModel` | `z_bs_sidebar_menus` | framework |
| `ContentCategoryModel` | `app_content_categories` | framework |
| `ContentDraftModel` | `app_content_drafts` | framework |
| `CronLogsModel` | `z_log_crons` | framework |
| `RssSourceModel` | `app_rss_sources` | framework |
| `RssBlacklistModel` | `app_rss_blacklist` | framework |
| `AAProductModel` | `aa_products` | ornek-proje-5 |
| `IcerikModel` | `z_app_icerikler` | rbncore |

`BaseModel::$scoped` **false olarak kaldı**; diger 68 somut model kapsam
disidir ve davranislari degismedi.

**Etkilenen davranis:**

1. **Okuma.** Kapsamlı bir modelin `query()` cagrisi artik
   `WHERE <tablo>.project_key = <aktif proje>` ekler. **Kiracinin aktif
   baglami (`active_project_key()`) veritabanindaki `project_key` degeriyle
   ayni degilse o kiracinin satirlari GORUNMEZ olur.** Coklu kiracili
   veritabanlarinda (ornek-proje-5, ornek-proje-6, ornek-proje-2, ornek-proje-3, ornek-proje-4, rbncore_main)
   bu bilincli bir daralmadir.
2. **Yazma.** `create()/update()` artik `project_key` degerini **sunucu
   baglamindan** yazar; cagiranin gonderdigi deger ezilir (B-20).
3. **Baglam yoksa** (`'default'` yedeği) hicbir `project_key` yazilmaz.
4. **`cron_logs` paneli** artik yalniz aktif projenin kayitlarini gosterir.
5. **Onbellek:** `SidebarProvider` anahtari `"sidebar_{rol}"` -> 
   `"sidebar_{kiraci}_{rol}"`. Eski onbellek girdileri kullanilmayacak.

**Geri acma anahtari:** `withProjectScope()` ile kapsam genisletilemez.
Gecici olarak "her seyi gor" isteyen cagiran `withoutProjectScope()` kullanir
(bu bir kacis kapisidir ve `security` kanalina yazmaz — kullanimi bilincli
olmalidir). Geri acma gereken **model** icin `scoped = false` yazmak yeterlidir
(tek satirlik, geri uyumlu).

**Gözden geçirmeniz gereken tek durum:** Kendi kodunuz kapsamli bir modele
elle `where('project_key', X)` yaziyorsa iki kosul cakisir ve sonuc **bos**
doner. Dogru yazim:

```php
// ESKI (artik cakisir):
$model->query()->where('project_key', $X);

// YENI (kapsam istenen anahtara daralir):
$model->withProjectScope($X)->query();

// Kapsam disi modelde eski yol AYNEN calisir:
$model->query()->where('project_key', $X);
```

Kapsamli olup olmadigini sormak icin: `$model->isProjectScoped()`.

---

### Yönetici adımı (KOD SONRASI): kiraci şeması — indeks, varsayılan değer ve UNIQUE

**Bu bir kod değişikliği DEĞİL, işletim/yönetici adımıdır.** Kiriş izolasyonunu
açan kod bu sürümle geliyor; aşağıdaki şema işi **kod yayınlandıktan sonra**,
**her hedef veritabanı için ayrı ayrı** yapılır. Sıra tersine çevrilemez.

Yerelde ölçülen ve doğrulanan kapsam (7 proje veritabanı, 65 `(db,tablo)` çifti;
ölçüm **yalnızca `SELECT`** ile yapıldı):

| işlem | adet | nitelik |
|---|---|---|
| hedef tabloda `project_key` kolonu | **65/65 zaten VAR** | kolon eklemeye gerek yok |
| NULL satır / `project_key='default'` satır | **0 / 0** | dolgu (backfill) gerek yok |
| kiracı kolonuna tek kolonlu indeks | **28** | saf ekleyici; mevcut satırlara dokunmaz |
| yanlış sabit `DEFAULT` → `NULL DEFAULT NULL` | **11 kolon** | **kolonun NULL'luğu da gevşer** (aşağıya bakınız) |
| `NULL DEFAULT NULL` → `NOT NULL` | **43 kolon** | NULL satır 0 olduğu için güvenli |
| `UNIQUE (project_key, <slug>)` | **26 indeks** | **çakışan 9 grupta EKLENMEDİ** |

**Uygulama sırası (her hedef veritabanı için ayrı):**

1. **Yedek.** Hedef veritabanının tam yedeği. Yedek alınmadan aşağıdaki adımlar yasaktır.
2. **Kod önce.** Kapsamı açan kod (13 modelde `scoped = true`) veritabanı şemasından
   **önce** dağıtılmalıdır. Aksi halde yeni yazılan satırlar `project_key` yazılmadan
   girer ve MySQL **1048** verir.
3. **Denetle (yalnız okur):** `php rbn tenant:audit --database=<db>`
4. **Plan (dry-run, şemaya dokunmaz):** `php rbn tenant:plan --database=<db>`
5. **Çakışma taraması — `UNIQUE` öncesi ZORUNLU:**
   ```sql
   SELECT COUNT(*) FROM (
     SELECT project_key, slug FROM `<db>`.`<tablo>`
      WHERE project_key IS NOT NULL AND slug IS NOT NULL
      GROUP BY project_key, slug HAVING COUNT(*) > 1
   ) x;
   ```
   Sonuç **0 değilse** o tabloya `UNIQUE` **eklenmez** ve sonuç buraya yazılır.
   Yerelde ölçülen çakışma 9 grupta bulundu; bu yüzden 26 indeks eklendi, çakışan
   gruplara eklenmedi.
6. **İndeks + varsayılan değer düzeltmesi:** elle `ALTER`, **tablo başına ayrı**
   (birden çok tablo tek `ALTER`'da birleştirilmez).
7. **Doğrula:** `php rbn tenant:audit --database=<db>` tekrar. `project_key`
   **dağılımı** uygulama öncesiyle birebir aynı kalmalıdır. Değiştiyse **dur ve geri al.**
8. **Sıkma (`NOT NULL`, 43 kolon):** NULL satır sayısı 0 değilse bu adım
   **atlanır** (aşağıdaki 1138). Adım 7 doğrulanmadan yapılmaz.

**Gerçek MySQL hata kodları — yalnız bu kodlar görüldüğünde ilgili adım geçersizdir:**

| kod | ne olur | ne yapılır |
|---|---|---|
| **1138** | `NOT NULL` kolona NULL satır geldi | sıkma adımını **atla**, önce NULL satırları temizle |
| **1067** | `SET DEFAULT NULL` reddedildi (kolon hâlâ `NOT NULL`) | `MODIFY COLUMN ... NULL DEFAULT NULL` kullan; kolonun NULL'luğu değişir |
| **1091** | `DROP INDEX` — bu adda indeks yok | **adı tahmin etme**; `SHOW INDEX FROM <tablo>` ile ölç |
| **1048** | eksik `project_key` yazımı | normalde **11 kolon gevşetildiği için** artık hata yerine `NULL` yazılır |
| **1052** | JOIN'de `project_key` belirsiz | kapsam süzgeci tablo adıyla niteliklidir; elle `where` yazan sorguyu gözden geçirin |

**11 kolonun `NOT NULL` → `NULL` gevşemesi — bilinçli bir sapma:**
`STRICT_TRANS_TABLES` açıkken, `project_key` yazmadan giren bir satır artık
sessizce hata vermek yerine `NULL` yazar. Bu satır **hiçbir kiracının kapsam
süzgecine uymadığı için görünmez olur** (fail-closed): sızıntı yaratmaz, ama hatayı
da gizler. Yerelde ölçüldü ve geri alınması mümkün çıktı.

**`NOT NULL` sıkmasının yan etkisi:** kapsama alınan kolonlara `project_key`
**yazmadan** insert eden ham yollar varsa yazma artık **sesli** hata (1048) verir.
Kapsamlı 13 modelin tamamında yazma yolu sunucu kapsamından gelir ve ölçüldü.

**Yasaklar:** kullanımdan kaldırılmış **ölü şema** ve `asw_*` tabloları
**dokunulmaz**. Garanti *yapısaldır*: motor yalnız `ProjectDbData::TENANT_TABLES`
içindeki tablo listesini işler; `asw_*` listede yoktur.

**Geri alma:**
```sql
-- indeksler (26 UNIQUE + 28 tek kolonlu):
ALTER TABLE `<db>`.`<tablo>` DROP INDEX `<indeks_adi>`;
-- 43 kolon:
ALTER TABLE `<db>`.`<tablo>` MODIFY COLUMN `project_key` <kolon_tipi> NULL DEFAULT NULL;
```
`NOT NULL`'a çekilmiş bir kolonun geri alınması **her zaman** mümkündür (satır
kaybı olmaz; ölçüldü). 11 kolonu eski tanımına döndürürken **sabit `DEFAULT`
değerini de geri yazmayı unutmayın**; aksi halde o tablo yine tek bir kiracıya
bağlanır (o satırlar diğer kiracılarda görünmez olur).

**Bu adımın aracı `tenant:apply` DEĞİLDİR.** O komut yalnız **ekleyici** kolon +
indeks yapar; `NOT NULL`'a çekmez, sabit `DEFAULT` düzeltmez, `UNIQUE` eklemez.

**Canlı veritabanı adları bu dosyada taşınmaz** — `SHOW DATABASES` ile ölçün.

---

### `rbn tenant:*` komutları (kiraci izolasyonu denetimi ve migration)

**Ne eklendi:** Dört yönetim komutu. Önceden yalnız kod vardı; **yayıncı bu
araçları keşfedemiyordu.**

| komut | ne yapar | şemaya yazar mı? |
|---|---|---|
| `php rbn tenant:audit --database=<db>[,<db2>]` | kolon envanteri (kolon/tip/`NULL`/varsayılan/indeks), `project_key` **dağılımı**, NULL satır sayısı, sabit `DEFAULT` taraması, model beyan envanteri | **HAYIR — yalnız okur** |
| `php rbn tenant:plan --database=<db>` | hedef veritabanı için **dry-run** migration planı | **HAYIR — şemaya dokunmaz** |
| `php rbn tenant:apply --database=<db>` | ekleyici kolon + indeks (idempotent) | EVET |
| `php rbn tenant:revert --database=<db> --yes` | geri alma: önce indeks, sonra kolon | EVET |

**`--database` zorunludur ve hedef veritabanı başına ayrı koşu gerekir.** Migration
motoru tek seferde birden çok şemayı tarayamaz. `tenant:audit` birden çok veritabanı
kabul eder ve sırayla okur, ayrıca `--local` kısayolunu (o anki bağlantının
veritabanı) destekler; `plan/apply/revert` **tek** hedef alır. Yanlış `--database`
ile `tenant:apply` **o hedefe** uygular — bu yüzden hedef adı komut satırından gelir
ve framework'e **yazılmaz**.

**`tenant:audit` gerçekten yalnız okur:** kaynağında tek bir yazma SQL'i yoktur
(`INSERT/UPDATE/DELETE/ALTER/CREATE/DROP/TRUNCATE/REPLACE` geçmez). Yine de
`tenant:revert` **etkilidir** ve `--yes` olmadan hiçbir şey yapmaz.

**Sıkma (`NOT NULL`), sabit `DEFAULT` düzeltmesi ve `UNIQUE` bu komutların işi
DEĞİLDİR** — elle `ALTER` ile, yukarıdaki yönetici adımına göre yapılır.

---

### Kiraci kapsami istisnasi modele tasindi (yerel API degisikligi)

**Ne degisti:** `QueryModelTrait` icindeki `cm_sys_ip_blocks` tablo adi
sabitini kaldirildi; istisna artik modelin kendi dosyasinda
(`protected array $projectScopeIncludes = []`). `SettingsApiRepository`deki
elle `whereIn('project_key', [X,'shared'])` cift süzgeci de kaldirildi.

**Etkilenen davranis:** IP bloklari tablosunun `GLOBAL` kurallariyla birlikte
okunmasi **aynen korunur** (davranma degismedi). `z_settings_api` icin
`shared` satirlarinin gorunurlugu **bugunku haliyle korunur** (proje ozel
satirlar); `shared` satirini gorunur kilmak icin modele
`projectScopeIncludes = ['shared']` yazmak gerekir — DIKKAT: bu durumda
`saveApiKey()` paylasilan satiri guncelleyip proje ozel kaydi uretmez.

---

### form() artik yalniz DOGRULANMIS alanlari dondurur (KIRICI)

**Ne degisti:** `request->form($kurallar)` bugun
`array_merge($this->all(), $dogrulanmis)` ile **tum** `GET + POST + JSON`
govdesini donduruyordu; yani kural dizisinde olmayan alanlar da servise ve
modele gecebiliyordu. Artik donen dizi **yalniz kural dizisinde tanimli VE
istekte gonderilmis** alanlari icerir.

**Etkilenen davranis:**
- Kural dizisinde olmayan bir alani okuyan kontrolorlerde o alan artik
  **yoktur**. Kuralsiz yazma yolu icin yeni acik metot kullanin:
  `request->rawAll()` (bkz. asagida).
- `nullable` / `optional` kurali olup **gonderilmemis** alanlar dizide
  **bulunmaz**. `null` YAZILMAZ; boylece isaretlenmemis checkbox'in kolonu
  `NULL`'a dusmez, oldugu gibi kalir (onceki davranis).
- `filter()` da kendiliginden daraldi: donen dizide yalniz `filter()`
  anahtarlari ve verilen varsayilanlar vardir.

**Geri acma anahtari:** `security.form_input_mode`
(`off` = eski davranis, `log` = eski davranis + olcum, `enforce` = yeni
davranis). **Varsayilan `enforce`.** Bu bayrak yalniz ACIL KAPAMA icindir;
bozuk/okunamayan bir deger `enforce`'a duser (fail-closed).

**Yeni metot — `rawAll()`:** `request->form([])` yerine
`request->rawAll($secenekler)` kullanilir. Iki farki vardir ve ikisi de
bilinçlidir:

1. `rawAll()` **alan dogrulamasi yapmaz**; kural dizisi kavrami yoktur.
2. `rawAll()` `form()` ile **ayni** kalkan zincirini calistirir: HTTP method
   kontrolu, CSRF, Origin/Referer, bot (honeypot + User-Agent), hiz siniri ve
   dosya guvenligi. Yani korumayi atlama yolu DEGILDIR.

`rawAll()` kullanilan yerler bu surumde: `CrudControllerTrait::create/update()`
(T5 yazma yolu), `NavigationController::save()`, `PolicyController::save()` ve
`AuthController::logoutSubmit()`.

**Gözden geçirmeniz gereken tek durum:** Kendi kodunuz bir `form()` cagrisindan
dizi ogesi okuyorsa ve o alan kural dizisinde **yoksa**, iki seceneginiz var:
- alan gercekten formdan geliyorsa -> kural dizisine ekleyin (beyaz liste),
- alan sunucu tarafindan uretiliyorsa -> hicbir sey yapmaniza gerek yok.

Guvenlik alanlarini (parola tekrar dogrulamasi gibi) `rawAll()`'a **tasiyinin**:
bu, guvenlik kontrolunu baypas etmek demektir. `confirm_password` alani bu
surumde kural dizisine eklendi; ornek olarak `AuthActionController`'a
bakabilirsiniz.

**Ölçüm — ve bu bayrağın varsayılanı zaten `enforce`:** `log` kipinde düşecek
alanlar (yalnız ALAN ADLARI, değerler değil) `FORM_INPUT_UNVALIDATED_FIELD`
koduyla günlüğe düşülür.

**Bu bayrak 0.9.0'da varsayılan `enforce` olarak gelir**; yani ölçüm turu
yapılmadan zorlama açılmıştır. Bu dalda **canlı ölçüm turu YAPILMADI**
(`form()` çağrılarının model eşleşmesi statik olarak çözülemediği için). Ölçüm
yapmak isterseniz kurulumdan sonra `security.form_input_mode = log` ile **bir
günlük** tur atın: günlük kaydı **0** ise `enforce`te kalabilirsiniz, kayıt varsa
önce ilgili kuralları tamamlayın — yoksa o alanlar doğrulamada sessizce düşer.

**"Zorlamayı kapatabilirsiniz" ifadesi yalnız `enforce` bilinçli olarak `off`
veya `log` yapıldığında geçerlidir.** Varsayılan zaten `enforce`'dur; ölçüm
yapılmadan kapatma adımı yoktur.

### Bilinmeyen veritabani baglanti adi artik HATA verir (KIRICI)

**Ne degisti:** Veritabani baglantisi uretilirken tanimadigimiz bir ad
verilirse (or. `database_mastrer` yazim hatasi) framework artik o adi sessizce
`database_project` veritabanina baglamak yerine **istisna firlatir** ve
baglanti kurulmaz.

**Neden:** Sessizce baglanmak, cok projeli (cok kiracili) bir kurulumda proje
A'nin verisinin proje B'nin veritabanina yazilmasi demekti. Simdi bu durum
"yanlis yere yazdim" degil, "baglanti adi yanlis, duzelt" olarak durur.

**Gozden gecirmeniz gereken tek durum:** Kendi kodunuzde veritabani baglanti
adi **degisken** ile veriliyorsa ve o degisken tanimli olmayan bir deger
turetebiliyorsa istegi o noktada bir hata alirsiniz. Sabit ad kullanan her
yer (`database_project`, `database_master`, `database_common`) **aynen
calisir**. Eski `default` adi de calismaya devam eder ve `database_project`
ile ayni kumesi gosterir.

**Geriye uyum:** Baglanti adlarinin **listesi degismedi**. `connection()` metodu
ve `connectionScoped()` kapsam metodu **oldugu gibi** duruyor; hicbir projenin
baglanti kodu degismesi gerekmiyor. `DatabaseGuardProvider` gibi yalniz *okuma*
yapan cagirmalar artik aktif baglantiyi degistirmeden calisiyor (davranislari
ayni, yan etkileri yok).

### Durum (aktif/pasif) yazimi: "degistir" ile "deger yaz" ayrildi

**Ne degisti:** Panelde "durumu degistir" istegi, veri katmani hazir degilse
artik **istenediginiz degeri yazar**. Once her zaman mevcut degerin tersine
cevriliyordu; yani ekranda gonderilen deger bazen yok sayiliyordu.

**KIRILMA YOK (dönüşler korunur) — ama TEK bir davranış değişikliği vardır.**
Metot adları **aynen duruyor** (`toggleStatus()`), imzaları değişmedi, hiçbir
projenin çağrısı değişmesi gerekmiyor; dönüş değerleri korunur.

**Tek davranış değişikliği:** **sağlayıcı yolunda ekranda gönderilen *değer*
artık uygulanır.** Önceden iki yol tutarsızdı — model yolunda istenen değer
zaten düşüyordu, sağlayıcı yolunda düşmüyordu. Ek olarak anlamı açık iki yeni ad
eklendi:
`$service->setStatus($id, $deger, $alan)` ve
`$provider->setStatusById($id, $alan, $deger)`.

**Yeni kod icin:** Yeni yazilan kodlar `setStatus()` / `setStatusById()` adlarini
kullansin; `toggleStatus()` kullanimi saatte bir kez gunluge duser (sessiz
değil) ve yakinda kullanımdan kaldirilacak.

**Gozden gecirmeniz gereken tek durum — bu, yukarıdaki tek davranış değişikliğinin
sonucudur:** Bir panel ekrani "degeri degistir"
 dugnesi olarak calisiyordu ve gonderdigi degeri **yok sayilmasini** istiyordu
(deger ne olursa olsun tersine cevir). Boyle bir ekran varsa `toggleStatus()`
adini koruyun ya da `setStatus($id, null)` cagirin — `null` "degeri oku ve
tersine cevir" anlamina gelir, eski davranisin tam olarak kendisidir.

### Tablosuz modelde tablo adi isteyen kod artik acik hata alir

**Ne degisti:** `SchemaDoctorModel` gibi tablosu olmayan bir modelde
`getTable()` cagrisi, sifir kayit donen kod yerine **tablosu olmadigini soyleyen
bir hata** firlatir ve dogru yolu (`getTableOrNull()`) gosterir.

**KIRILMA YOK:** `getTable()` imzasi degismedi (`string` olarak duruyor) ve
tum modellerde aynen calisir. Yalniz **zaten hata** veren bir cagrinin hata
mesaji netlesti. Tablo adi gereken islemler icin metotlarin tablo adini
parametre alan varyantlari zaten mevcut ve degismedi
(`getTablePrimaryKey($tablo)`, `getTableSchema($tablo)`, `getRows($tablo)`).

### Kayit (register) adimini tanimlamayan saglayicilar artik gorunur

**Ne degisti:** Kurulum sirasinda bazi saglayicilarin kendi kayit adimini
tanimlamadigi fark ediliyordu ve bu **sessizce** atlaniyordu. Artik bu durum
gunluge saatte bir kez yazilir (saglayicinin sinif adiyla).

**KIRILMA YOK:** Hicbir saglayici calismaya devam etmez; hicbir istek dusmez.
Yalniz gorunurluk eklendi. Saglayici listesi degismedi, hicbir kayit adimi
calistirilmadi.

### Bilinen sinirlar ve bilincli kararlar

Bu surumle birlikte `KNOWN-LIMITATIONS.md` eklendi: **bilerek "daha siki"
yapilmamis** guvenlik ve yapilandirma tercihlerinin tam listesi. Kurulum
oncesi okumaniz onerilir; icerik kullaniciga yoneliktir ve proje/ajan adi
icermez.

### Cevrimdisi tutmada, gercek sayfa kaybi giderildi (R-02)

**Ne degisti:** Tarayici tarama tuzagi (`HONEYPOT_PATHS`) listesinde `/admin`,
`/test`, `/demo`, `/site`, `/ip` gibi uygulama onekleri vardi. Bu adresler bir
projede GERCEK sayfaya karsilik geliyorsa istek kalici olarak ana sayfaya
yonlendiriliyordu (o site icin "sayfa yok" gibi gorunuyordu). Artik yalniz bu
onekler icin "bu yol gercek bir rotaya eslesiyor mu" kontrolu yapilir; eslesme
varsa yonlendirme yapilmaz.

**Sizin icin yapmaniz gereken HICBIR SEY yok.** Tuzak listesi, eski yazi tipi
ve `.php`/`.html`/`.htm` uzanti taramalari, alt dizge eslesmesi ve tum diger
yonlendirme kurallari **AYNEN korundu**. Gercek rota bulunamazsa (rota
koleksiyonu bos, eski istemci, bir hata) **eski davranis** devreye girer:
yani bu degisiklik hicbir projede tuzagi zayiflatmaz.

**Gozden gecirmeniz gereken tek durum:** Bir projede `/test`, `/demo`, `/site`,
`/ip` veya `/admin` adresine **ozel bir yonlendirme/robots kurali** yazdiysaniz
ve o adres gercek bir rota **degilse**, davranis degismedi. Adres gercek bir
rota ise artik o sayfa acilir; boyle bir kuralin **kasitli oldugunu**
dogrulamaniz yeterli.

### Yetkisiz form isteğinde yanit govdesi degisti (DUSUK-4)

**Ne degisti:** `FormRequest::failedAuthorization()` artik duz metin
(`403 Forbidden - Unauthorized Request`) yerine framework'un **standart 403
sayfasini** basar (`shield()->forbidden()`).

**KIRILMA YOK:** Durum kodu yine **403**'tur. Tarayiciya giden govde degistigi
icin, yetkisiz form POST'una **govde metnine gore** tepki veren bir testiniz
varsa guncelleyin. `shield()` yardimcisi olmayan cok erken boot / birim testi
ortamlarinda eski yedek yol korunur; istek hicbir kosulda 200 veya bos donmez.

### Guvenlik degil, yalniz gorunurluk (S-09) ve yalniz belge (S-10)

- **S-09:** Baslatma asamasinda cozulemeyen/kaydedilemeyen kayitlar artik
  saatte bir gunluge yaziliyor. **Istek dusurulmez, istisna firlatilmaz,
  yanit degismez.** Gunluk satirlari proje adi, anahtar, kullanici veya istek
  verisi icermez. Gunlugu susturmak icin `RBN_LOG_THROTTLE=0` (yalniz test/
  olcum icin; varsayilan kapali degildir, yani etkin durumdadir).
- **S-10:** Alti baslatma asamasinin sirasi ve bagimliliklari yorum olarak
  yazildi. **Davranis degismedi.**

### Kayıt ve parola sıfırlamada parola tekrarı artık SUNUCUDA zorunlu (KIRICI)

**Ne değişti — iki ayrı bozukluk üst üste biniyordu:**

1. **KAYIT: alan adı tutarsızdı ve sunucu doğrulaması yoktu.**
   `registerSubmit()` kural dizisinde `confirm_password` **hiç yoktu** ve
   kontrolör bu alanı hiç okumuyordu; parola tekrarı yalnızca istemcide
   `required` idi. Üstüne kayıt görünümündeki alan `password_confirm` adıyla
   yazılmıştı, yani **hiçbir sunucu kuralıyla eşleşmiyordu** (sıfırlama
   görünümü zaten `confirm_password` adını kullanıyor).
2. **SIFIRLAMA: boş tekrar kabul ediliyordu.** `resetPasswordSubmit()` kuralı
   `nullable|string` idi ve koşul `$confirm !== '' && !hash_equals(...)`
   şeklindeydi; yani tekrar alanı **hiç gönderilmeden** parola değiştirilebiliyordu.

**Artık:** kayıt kurallarında `'confirm_password' => 'required|string'` vardır ve
eşleşme **kompulsuz** `hash_equals()` ile kontrol edilir. Sıfırlamada kural
`required|string`'dur; boş veya eksik tekrar doğrulamada elenir. İki akış da tek
bir yardımcı metoda (`confirmPasswordMatches()`) yazılır ve **parola/tekrar boşsa
"eşleşmiş" sayılmaz.** Eşleşme kontrolü `rawAll()`'a **taşınmadı** ve
taşınmayacaktır — kural dizisinde (beyaz listede) kalır.

**Gözden geçirmeniz gereken tek durum — KIRICI:** kayıt formunun **özel bir
şablonu** varsa (framework'ün `Resources/Views/RbnAuth/auth.rbn.php` görünümü
yerine kendi şablonunuzu kullanıyorsanız) ve o şablonda alan `password_confirm`
adıyla yazılmışsa, alan adını **`confirm_password`** yapmanız gerekir. Aksi
halde istek "tekrar zorunlu" doğrulama hatasıyla reddedilir, yani **kayıt
başarısız olur** (bu bir güvenlik açığı değil, hatalı eşleşmenin sonucudur).
Framework'ün kendi görünümü zaten düzeltildi; `auth/register` ve
`auth/reset-password` uçlarını yalnızca o görünüm çağırır.

Parola sıfırlama görünümü zaten doğru alan adını gönderiyordu; **orada alan adı
değişikliği yoktur** (yalnızca boş tekrar artık kabul edilmiyor).

---

### Çözülemeyen proje anahtarı artık sonsuza kadar asılmıyor (KIRICI DEĞİL)

**Ölçülen belirti:** master'da **bulunmayan** bir `project_key` ile boot eden
süreç, hiçbir çıktı üretmeden (stdout/stderr/istisna yok) **sınırsız** süre asılı
kalıyordu. Tetikleyici bir çağrı hatası değil, **aranan anahtarın
bulunamamasıdır**: geçerli bir anahtarla boot ve başka bir geçerli anahtarı
çözmek ölçüldü, ikisi de ~0.1 sn'de yeşildir.

**Kök neden:** proje yapılandırmasını çözen katmanda, henüz bulunmamış bir proje
anahtarı istendiğinde karşılıklı özyineleme oluşuyordu (proje yolu → özel yol →
proje verisi → yapılandırma → özel yol → …). Önbellek doldurulmadığı için döngü
kesilmiyordu; her düzey ayrıca bir veritabanı sorgusu yaptığı için bellek yavaş
büyüyor ve süreç "sessizce asılı" görünüyordu.

**Düzeltme:** aynı (anahtar, yapılandırma) çifti için yeniden giriş "yapılandırma
yok" ile dönüp zinciri kesiyor; kayıt **her yolda** temizlendiği için ardışık
(yuvalanmayan) çağrılar etkilenmiyor.

**Yalnız "çözülemeyen anahtar" yolu değişti:** yanlış anahtar artık **sınırlı
sürede ve açık hatayla** başarısız oluyor — komut satırında ön kontrol hatası,
HTTP'de standart hata sayfası (genel metin + hata kimliği; ayrıntı
**sızdırılmaz**). Geçerli anahtar yolu, özel yol doğrulaması ve yapılandırma
dosyası yükleme mantığı birebir korundu.

**KIRICI DEĞİLDİR, geri almanız gerekmez:** imza, dönüş tipi ve hiçbir geçerli
yol değişmedi.

---

### Oturum temizleme (`gc()`) oturum dizinini belleğe almıyor (KIRICI DEĞİL)

**Ne değişti:** oturum çöp toplama her çağrıda oturum dizininin **tamamını**
`glob()` ile belleğe alıyor, sonra her dosya için ayrıca `filemtime()` çağırıyordu.
Artık `FilesystemIterator` ile **tek geçişte** geziliyor; dizin listesi bellekte
tutulmuyor. Bu bir kaynak hijyeni/verimlilik düzeltmesidir.

**Davranış birebir aynı:** yalnızca **gerçek** dosyalar işlenir, `.`/`..`
atlanır, güvenlik tabanı `max($max_lifetime, 86400)` aynen korunur, dizin yoksa
veya erişilemiyorsa istisna **yayılmaz** (0 döner). İmza değişmedi, dönüş tipi
değişmedi, hiçbir çağrıdan kaldırılmadı.

---

### Security

### Makine API korumasi (`ApiGuard`)

**YAPMANIZ GEREKEN HICBIR SEY YOK.** Geri uyum kilitleri:

- `external-api` ayar dosyanizdaki `api_key` (duz metin) **calismaya devam ediyor**. `keys`
  bolumu eklemeden once tum eski istemciler bit-bit ayni davraniyor.
- `?token=` ile gonderim **reddedilmiyor** (varsayilan `allow_query_token = true`); yalniz
  denetim kaydi dusuyor.
- Hata yanitlarindaki `message` alani **degismedi**; yeni `code` alani **eklendi**, yani
  eski istemciler aynen okumaya devam ediyor.
- IP katmanindaki ajan/agent muafiyetleri **aynen duruyor** (`/api/agent/*`,
  `/api/telegram/webhook`). Crew/worker uclarinin muafiyeti **kapali** — gozlem verisi
  toplanmadan acilmadi.

**DURUM DEGISTIREN YER:** `/api/agent/*` uclarinin 401 yanitinda anahtari geri yansitan
alan **kaldirildi**. Yanitta `given_key` alanini okuyan bir istemciniz varsa o alan artik
gelmez (Go ajanı okumuyordu; Go testleriyle dogrulandi).

**YENI:** Anahtar yonetimi icin tek dosyalik CLI eklendi:

```
php rbnframework/Core/Http/Security/bin/keys.php list --proje=<proje-anahtari>
php rbnframework/Core/Http/Security/bin/keys.php create --scope=blog,faq --rate=60/60 --ttl=2592000
php rbnframework/Core/Http/Security/bin/keys.php rotate <key_id> --proje=<proje-anahtari>
php rbnframework/Core/Http/Security/bin/keys.php revoke <key_id> --proje=<proje-anahtari>
```

Anahtar degeri **yalniz `create`/`rotate` ciktisinda bir kez** gosterilir; dosyada yalniz
`hash` saklanir. `revoke`/`rotate` yazma oncesi `external-api.php.bak` alir.

# Yükseltme Notları

> **Bu dosya neden var:** Framework tek bir dosya güncellemesiyle **tüm siteleri** etkiler. Yanlış sırayla yapılan bir yükseltme tüm siteleri aynı anda düşürebilir. Bu dosya, sürüm başına **zorunlu adımları ve geri alma yolunu** tanımlar.

> **dil: Türkçe (iç)** — depo açılırken İngilizce kararı verilecek.

Sürümlendirme: [SemVer](https://semver.org/lang/tr/). Değişiklik kaydı: [CHANGELOG.md](CHANGELOG.md). Güvenlik bildirimi: [SECURITY.md](SECURITY.md).

## Bu sürüm

- **Son sürüm:** `0.9.2` (2026-10-05) — canlı geçiş düzeltmeleri sonrası: tek kaynak, kural ve araç düzenlemesi. Kırıcı değişiklik **var** (aşağıdaki 0.9.2 bölümüne bakın).
- Bu dosyaya yazılan her sürüm, o sürümün canlıya çıktığı andan itibaren geçerlidir.

---

## 0.9.1 (canlı geçiş düzeltmeleri — 2026-10-05)

Bu sürümde **kırıcı değişiklik yoktur**, yalnız **canlı geçiş sırasında ortaya çıkan 4 gerçek
hatayı düzeltir**. Yükseltme sonrası geri almaya gerek yoktur; yalnız aşağıdaki **(a)** maddesi
zaten dağıtılmış `domains/*/index.php` dosyaları için zorunlu bir **dosya yeniden kopyalama**
adımıdır. Kapsam dışı: `asw_*` tabloları, ortak (`cm_*`) şema, master şeması
(`developers`, `ip_blocks`), sır rotasyonu, lisans/IP katmanı ayarları — hiçbiri değişmedi.

### 0.9.1 — (a) ZORUNLU: `domains/*/index.php` dosyaları yeniden kopyalanmalı

**Neden:** 0.9.0 ile eklenen PHP sürüm kapısı (`PhpVersionGate`) blokunda yol bir değişkene
**yazılıyor**, karşılaştırma **başka bir değişkenle** yapılıyordu
(`is_file($rbnKapisiAdayi)` — değişken yazım hatası). `is_file(null)` her zaman `false`
döndüğü için kapı dosyası hiç bulunamıyor ve sürüm kapısı **sessizce devre dışı** kalıyordu.
Geliştirme ortamında `display_errors` açık olduğu için her istek sayfaya ayrıca iki hata
(`Warning: Undefined variable …`, `Deprecated: is_file(): Passing null …`) basıyordu ve
render hatasının **önünde** görünüyordu.

**Etkilenen dosya sayısı:** 20 adet `domains/<site>/index.php`.

**Ne yapmalısınız:** Bu dosyalar framework **paketinin parçası değildir** (her site kendi
giriş noktasını taşır). Yükseltmede framework'ü kopyalayan tek adım bu dosyaları **yeniden
getirmez**; onları ayrıca kopyalamanız gerekir. Kopyalama sonrası kapının çalıştığını şöyle
doğrulayın:

- Sunucuda PHP sürümü 8.3+ değilse kapı **anlaşılır bir hata** ile kapatır (kasıtlı davranış).
- Doğru yüklendiğinde sayfa gövdesinin **başında** `Warning`/`Deprecated` satırı **yoktur** —
  gövdenin ilk satırı `<!DOCTYPE` veya `<html` olmalıdır.
- Tarayıcıda `?v=` sürüm damgası yerine panel alt bilgisinde framework sürümü `0.9.1` görünür.

**Geri alma:** Bu dosyalar değiştirilmediği için eski hâliyle bırakmak da güvenlidir; yalnız o
durumda sürüm kapısı devre dışı kalır (bu, 0.9.0 öncesi durumdur).

### 0.9.1 — (b) Dış görsel vekili artık `proxy_allowed_hosts` ile beyaz liste alıyor

**Neden:** Dış CDN hedefleri `media/<base64>` dalında güvenlik gereği beyaz listede aranıyordu;
liste kaynağı yalnız framework sabitiydi ve motor genel kalsın diye **boş** bırakıldı. Sonuç:
dış CDN kullanan her proje `Location` üretilmeden 404 alıyordu. Artık beyaz listenin **ikinci
kaynağı aktif projenin kendi ayarıdır.**

**Ne yapmalısınız:** Dış CDN kullanan projelerde, kullandığınız host'ları projenin ayar dosyasına
ekleyin (`Core/Config/project-settings.php` veya `project-routemap.php`):

```php
'project-settings' => [
    // Dis CDN host'lari icin beyaz liste. Yalniz ALAN ADI; sema/yol/port YAZILMAZ.
    // Kural: tam host eslesir, ya da host bu son eki degilse ('.' son ek) son ek eslesir.
    'proxy_allowed_hosts' => ['cdn.example.com', 'images.example.net'],
],
```

**Motor genel kalır (Anayasa §9):** framework'e proje/müşteri adı ya da host listesi
**yazılmaz**; liste yalnızca projenin dosyasında durur. Değer kuralları: eleman **yalnız string**
olmalı; şema/port/yol/boşluk/kontrol karakteri içeren elemanlar **sessizce yok sayılır** (beyaz
liste genişlemez, hata fırlatılmaz). Ayar dosyası okunamazsa liste **boş** kalır — bu fail-closed
davranıştır ve kasıtlıdır.

**Güvenlik değişmedi:** şema beyaz listesi (yalnız `http`/`https`), kontrol karakteri/CRLF ve ters
bölü normalizasyonu aynen durur; reddedilen hedef için `Location` üretilmez.

### 0.9.1 — (c) Dikkat: paketlemede büyük/küçük harf duyarsız dosya filtresi

**Uyarı (davranış değişikliği DEĞİLDİR — yalnız paketleme/arşivleme tarafı):** Git, macOS ve
Windows dosya sistemlerinde dosya adını **büyük/küçük harf duyarsız** karşılaştırır, Linux
(POSIX) **duyarlıdır**. Framework'te iki farklı dosya vardır:

| Dosya | Ne |
|---|---|
| `Core/System/Config/Secrets.php` | Framework **sınıf** dosyası (kod). **Her zaman gereklidir.** |
| `Core/System/Config/Secrets/secrets.php` | Sunucudaki **sır** dosyası (gerçek değerler). **Asla commit edilmez.** |

İkisi yalnız **harf büyüklüğüyle** ayrılır. Paketleme/dağıtım betiğiniz "sır dosyasını ele" gibi
**genel bir filtre** kullanıyorsa, büyük/küçük harf duyarsız karşılaştırma yüzünden
`Secrets.php` **sınıf dosyasını da** eleyebilir. Belirtisi: sınıf bulunamadığı için
`Class "…Secrets" not found` veya sır okuma katmanının açılmamasıdır.

**Ne yapmalısınız:** Sır dosyası filtresi **yalnız tam yolu hedefleyen** ve
**harf büyüklüğüne duyarlı** olmalıdır:

```
KAPSAM = Core/System/Config/Secrets/secrets.php   (yalnız bu, tam yol, harf duyarlı)
```

`Core/System/Config/Secrets/secrets.example.php` **hariç tutulmaz** — şablondur, gerçek değer
içermez ve depoda tutulması gerekir. Kural, `.gitignore` içinde de aynı şekilde dar yazılmalıdır
(`secrets.php` kalıbı, kök dizinlerdeki başka dosyaları yutmasın).

**Doğrulama:** paketten sonra sınıf dosyası **VAR**, sır dosyası **YOK** olmalıdır:

```bash
test -f Core/System/Config/Secrets.php                    && echo "OK sinif dosyasi var"
test ! -f Core/System/Config/Secrets/secrets.php           && echo "OK sir dosyasi yok"
test -f Core/System/Config/Secrets/secrets.example.php     && echo "OK sablon var"
```

## 0.9.0 (güvenlik onarım sürümü)

Bu sürümde **zorunlu** adımlar aşağıdadır. Hiçbiri isteğe bağlı değildir.

### 0.9.0 — Adım 15 (KIRICI): CRUD girişi model beyaz listesine bağlı

**Özet:** `CrudControllerTrait::create()`/`update()` artık ham istek gövdesini servis/modele aktarmıyor; veri hedef modelin `$fillable` listesine göre budanır. Altı modülün 35 yazma modeli beyaz listeye bağlandı (ornek-proje-5, ornek-proje-3, ornek-proje-4, ornek-proje-6, ornek-proje-2, ornek-proje-1).

**Kırıcı olan taraf:** `$fillable` **tanımlayan** bir modele, listede olmayan bir alanla yazmayı deneyen özel kod artık o alanı **yazamaz** — süzgeç alanı sessizce düşürür. Panel ekranlarının gerçekten gönderdiği alanların tamamı listeye alındığı için meşru kayıtlar etkilenmiyor; bir modülün meşru yazımı kırılırsa **listeye o alanı ekleyin** (sunucu kararı olmayan, panelin gerçekten gönderdiği alan).

**Ne yapmalısınız:**

- **Modelinize `$fillable` tanımlayacaksanız listeyi gerçek şemadan ölçün:** `SHOW COLUMNS FROM <tablo>`. Birincil anahtarı ve zaman damgalarını (`created_at`/`updated_at` motor tarafından yazılır) listelemeye gerek yok; zaman damgaları **yine de** yazılır.
- **`query()->insert()/update()` ile yazıyorsanız süzgeç çalışmaz.** Bu yollar `CrudModelTrait`'ten geçmez. Beyaz listeyi açıkça uygulayın: `$data = $model->filterFillable($data);`
- **Kendi CRUD kontrolörünüz `request->form([])` ile ham veri alıyorsa**, veriyi hedef modelin listesine göre süzün: `$this->crudInput($data)` (aynı kilit: liste tanımsızsa veri dokunulmadan geçer).
- **RbnAdmin profil ekranı artık `name` + `email` gönderiyor** (gerçek şema: `z_users`'ta `firstname`/`lastname`/`phone_number` kolonları **yok**). `email` değiştirmek isteyen kendi kodunuz `UserManager::updateEmail($id, $email)` yetkili yolunu kullanmalı; genel `update()` yolu `email`'i düşürür.
- **`UserSecurityRepository::saveSecurityData()` artık `create` yolunda `true` döner.** Bu çağrının dönüş değerine göre akış kuran kod "başarısız" sanmayı bırakır; `false` dönen tek durum artık geçersiz `userId` (<= 0) veya satırın yazılamamasıdır.
- **Geri dönüş (geçici):** `Config::set('security.mass_assignment', false)` (ya da `'off'` / `'log_only'`) tek satırdır ve tüm süzme — hem `$fillable` hem `$guarded` — kapanır. Güvenlik açığı geri gelir; kalıcı çözüm değildir.
- **Ölçüm anahtarı:** `security.protected_field_log` varsayılan AÇIK; `Storage/logs/security/*.jsonl` içinde `MASS_ASSIGNMENT_BLOCK` (model süzgeci) ve `CRUD_INPUT_FIELD_DROPPED` (controller giriş süzgeci) satırları hangi alanın düşürüldüğünü gösterir. **Değer içermez** — yalnız model sınıfı ve alan adları.
- **Şema/migration yoktur.** `$fillable`/`$guarded` tanımlamayan modellerin davranışı değişmedi.

### 0.9.0 — Adım 1: Sır dosyası **zorunludur** (master sırları)

**Özet:** Master veritabanı ve master SMTP parolaları kaynak koddan çıkarıldı; bunlar artık `Core/System/Config/Secrets/master-secrets.php` dosyasından okunur. Bu dosya depoya girmez; **sunucuda elle oluşturulur.** Okuyucu bu dosyayı bulamazsa sessizce devam etmez — hata verir. Bu, "güvenli kapalı" davranışıdır ve kasıtlıdır.

**Yayın sırası (bağlayıcı):**

```
① Sunucuda Core/System/Config/Secrets/master-secrets.php dosyasını OLUŞTUR
   (master-secrets.example.php şablonundan kopyala, gerçek değerleri yaz, izin: 0600)
② Anahtarların gerçekten yazıldığını ve "CHANGE_ME" yer tutucusu kalmadığını DOĞRULA
③ SONRA kod dosyalarını yükle
```

Ters sıra (önce kod, sonra sır dosyası) = **tüm siteler açılmaz.** Geçiş kolaylığı için yayın paketinin kodu, sır dosyasından bağımsız olarak önce yüklenebilen parçalar hâlinde hazırlanır; yine de sır dosyasının hazır olması her hâlükârda ön koşuldur.

**Kim etkilenir:** Framework'ü kullanan **her proje ve her alan adı** (master veritabanına bağlanan tüm siteler). Kimse tek tek güncellenmez; tek dosya tüm ağacı etkiler.

**Ne yapılır:**

1. Sunucuda sır dosyasını oluşturun: `Core/System/Config/Secrets/master-secrets.php`.
   - İçerik şablonundan (`master-secrets.example.php`) kopyalanır.
   - Dosya izinleri **0600** olmalı; web sunucusu kullanıcısı okuyabilmeli, başka kimse erişememeli.
2. Zorunlu anahtarları gerçek değerleriyle doldurun: **master veritabanı parolası** ve **master SMTP parolası.**
3. **Doğrulama:** hiçbir değer boş bırakılmamalı ve şablondaki `CHANGE_ME` yer tutucusu kalmamalı. (Yer tutucu kalırsa doğrulama biçimi "anahtar var mı" değil, "değer hâlâ yer tutucu mu" olmalıdır.)
4. Ana doğrulama: master veritabanına bağlanabilen bir komut çalıştırın (ör. proje listesini okuyan CLI komutu). Çıktı beklenen sayıda proje vermeli.
5. Kod dosyalarını yükleyin. Yüklemeden sonra her alan adı için ana sayfa duman testi yapın.
6. Gerçek sır dosyasını **asla** depoya eklemeyin, yayın paketine koymayın, yedek arşivine sızdırmayın.

**Geri alma:**

- Sır dosyası **silinmez** (sonsuza kadar gerekir).
- Kod tarafı geri alınır: ilgili yamanın tek commit'ini geri alın (`git revert`); eski kodda sır dosyasına bakılmaz, siteler eski hâliyle açılır.
- Sır dosyasının kendisi bozuksa: dosyayı şablondan yeniden oluşturup değerleri geri yazın. Bu, kod değişikliği değildir; ayrı bir işlemdir.

### 0.9.0 - Adım 3b (yapıldı, **henüz yayınlanmadı**): `orderBy()` geçersiz yönde artık istisna fırlatır

**Özet:** Sorgu kurucusunun `orderBy()` yön parametresi doğrulanmıyordu ve kullanıcı girdisi doğrudan SQL'e giriyordu. Artık **yalnız `ASC` ve `DESC`** kabul edilir; başka her değer `InvalidArgumentException` fırlatır. `ASC`/`asc`/`Desc` gibi yazımlar büyük harfe normalleştirilir, yani meşru çağrılar **aynı SQL anlamını** korur.

**Kim etkilenir:** `orderBy()` çağıran her proje. Düz `orderBy('sütun')`, `orderBy('sütun','DESC')`, `orderBy('tablo.sütun','asc')` çağrıları **hiç değişmez**.

**Ne yapmalısınız:** Sıralama yönünü bir form/URL parametresinden alıyorsanız, gelen değeri `ASC`/`DESC` dışına çıkmayacak şekilde kendi tarafınızda doğrulayın. Sessizce `ASC`'ye düşürmek yerine istisna tercih edildi: yanlış sıralama, hatadan daha kötüdür.

```php
// ÖNCE (hata sessizce geçerdi):
$query->orderBy('created_at', $request->query('dir'));

// SONRA:
$yon = strtoupper((string) $request->query('dir', 'ASC'));
$query->orderBy('created_at', in_array($yon, ['ASC', 'DESC'], true) ? $yon : 'ASC');
```

**Geri alma yolu:** `orderBy()`'ı `orderByRaw()` ile değiştirmek **güvenli değildir** (`orderByRaw()` kasıtlı olarak ham SQL yazır ve hiçbir koruma taşımaz).

---

### 0.9.0 - Adım 3c (yapıldı, **henüz yayınlanmadı**): Parola belirleme/değiştirme akışları en az 8 karakter ister

**Özet (kırıcı değişiklik, ama yalnız yeni parolalar için):** Parola politikasında taban bir minimum uzunluk yoktu; tek uzunluk kuralı form katmanında dağınık `min:6` idi. Artık kayıt, şifre sıfırlama ve (varsa) parola değiştirme akışları **en az 8 karakter** ister ve boş/yalnızca boşluklu parola reddedilir. Kural tek yerde tanımlıdır: `PasswordValidations::MIN_PASSWORD_LENGTH` (ileride ayarlanabilir tek nokta).

**Kim etkilenir:** Yeni kullanıcı kaydı yapanlar, şifre sıfırlama isteyenler, parolasını değiştirenler — yani **yalnız parola belirleyenler**.

**Mevcut kullanıcılar etkilenmez:** Giriş (login) akışında parola uzunluğu **denetlenmez**; mevcut kısa parolalar geçerli kalır ve kimse girişte reddedilmez. Kullanıcı kendi parolasını değiştirdiğinde yeni kural uygulanır.

**Ne yapmalısınız:**

- Kendi formlarınızda parola alanı kuralı yazıyorsanız sabiti kullanın, sabit yazmayın: `'password' => 'required|min:' . PasswordValidations::MIN_PASSWORD_LENGTH`.
- Kullanıcı yönlendirmesi yapan yardım metinleri en az 8 karakter gereksinimini yansıtmalı.
- Kurum içi/entegrasyon testi hesapları 8 karakterden kısa parola kullanıyorsa bu artık **beklenen şekilde** reddedilir; test verisini güncelleyin.

**Geri alma:** İki kod değişikliğinin commit'ini geri alın (`fix(auth): A-12 ...`). Sıfırlama/izin ihtiyacınız varsa alternatif olarak `PasswordValidations::MIN_PASSWORD_LENGTH` değerini düşürmek yeterlidir (davranış `CredentialHandler` ve form doğrulamasına aynı sabitten akar).

---

### 0.9.0 - Adım 3d (yapıldı, **henüz yayınlanmadı**): "Beni hatırla" çerezi artık süreli

**Özet (kırıcı değişiklik):** "Beni hatırla" token'ı artık kendi içinde imzalı bir bitiş zamanı taşır ve **30 gün** sonra sunucu tarafında reddedilir. Önceden süre yalnızca tarayıcıdaki çerezin ömrüydü; sunucu tarafında bir süre denetimi yoktu.

**Kim etkilenir:** "Beni hatırla" seçeneğiyle giriş yapmış **her kullanıcı** — bir süre sonra tarayıcıyı açtığında yeniden giriş yapması istenir. Bu bir hata değil, amaçlanan davranıştır.

**Eski çerezler ne olur?** Bu sürümden önce üretilmiş (süresiz) tokenlar geçiş süresince kabul edilir: **en geç 2026-11-02** tarihinden sonra istenmez ve kullanıcı bir kez normal giriş yapar. Yani geriye dönük kırılma **en fazla 30 gün** sonra başlar.

**Veritabanı:** **Değişiklik yok.** Kolonlar aynen duruyor (`remember_token`, `dev_token_hash`); migration çalıştırmanız gerekmiyor.

**Bilmeniz gerekenler:**

- Süre tek yerde tanımlıdır: `RememberTokenService::MAX_AGE_SECONDS` (30 gün). Siteye özel `security.remember_me_duration` ayarı **artık dikkate alınmaz** (çerez ömrü de token süresi de bu sabitten gelir; iki ayrı kaynak "çerez 30 gün, sunucu 90 gün" gibi sessiz bir tutarsızlık üretirdi). Farklı bir süre isteyen kurumlar sabiti değiştirmelidir.
- Tokenın imzası **uygulama anahtarınızdan** (`APP_KEY` / `ENCRYPTION_KEY` / `secrets.php` → `app_key`) üretilir. Anahtar tanımlı değilse çalışma zamanında hata verilir (sessizce zayıf bir anahtara düşülmez); bu durumda giriş yine başarılı olur, yalnızca "beni hatırla" devre dışı kalır ve hata günlüğe yazılır.
- **Düzeltme:** "Beni hatırla" seçeneği normal kullanıcı hesaplarında **hiç çalışmıyordu** (oturum yalnızca master geliştirici hesaplarında yeniden kurulabiliyordu). Artık çalışıyor. Kullanıcılarınızın "beni hatırla" dedikten sonra da giriş yapamama şikâyeti bu yüzden kaybolmuş olabilir.
- **Çıkış davranışı daraldı:** Çıkış yalnızca çıkış yapan kullanıcının kendi oturumunu kapatır. Önceden, master geliştirici olmayan bir kullanıcı çıktığında numarası çakışan master geliştirici hesabının tokenı da siliniyordu.
- **Giriş banı:** Global (master) IP ban listesi giriş akışında **izlenir ama uygulanmaz** — kayıt bulunduğunda yalnızca denetim kaydına not düşülür, giriş durdurulmaz. Ölçümde global listede **505 aktif kayıt** bulundu ve tamamı yerel IP adresine aitti; liste girişe bağlansaydı tüm yerel sitelerin girişi kapanırdı. Ban kayıtlarının temizliği ayrı bir karardır ve bu sürümde yapılmamıştır.

**Geri alma:** İlgili commit'leri geri alın (`fix(auth): A-10 ...`, `A-14 ...`, `A-19 ...`). Geri alma durumunda eski davranış (süresiz çerez) geri gelir; ayrıca geri alma öncesi üretilmiş yeni biçimli tokenlar geçersiz olur ve kullanıcılar bir kez giriş yapar.

---

### 0.9.0 - Adım 3 (yapıldı, **henüz yayınlanmadı**): Ortam değişkenleri TEK kapıdan (`Env` + `EnvKeys`)

**Özet:** "Global anahtarlar TEK dosyada toplansın; herkes her yerde ayrı okuyup tanımlamasın." (Patron isteği, 2026-10-02) `getenv(` / `$_ENV` / `$_SERVER` ile okuma yapan 11 nokta tek kapıya (`Core/System/Config/Env.php`) taşındı; tüm adların listesi tek kayıt dosyasına (`EnvKeys.php`) girdi.

**Zorunlu adım YOK.** Bu bir saf yeniden düzenlemedir: hiçbir ortam değişkeninin adı, varsayılanı, öncelik sırası veya fail-closed kararı değişmedi. Yayın sırası önemsiz.

**Sizin için ne değişiyor?**

1. **Hiçbir şey yapmanız gerekmiyor.** Mevcut `vhost` ortam değişkenleriniz, `.env` tanımlarınız ve `secrets.php` dosyanız **aynen** çalışır.
2. **Yeni bir ortam değişkeni okuyan kod yazarsanız:** önce `Core/System/Config/Definitions/EnvKeys.php` → `KAYIT` tablosuna bir satır ekleyin, sonra `Env::string('AD')` / `Env::flag('AD', true)` / `Env::int('AD', 0)` ile okuyun. **Kayıtsız bir adı okumak `RuntimeException` fırlatır** (fail-closed) — bu bir hata değil, yazım hatası yakalamasıdır. Kuralın yazılı hâli: `Core/System/Config/README.md`.
3. **Projenizin kendi `getenv`/`$_ENV` okuması varsa** (ör. `projects/...` altında), aynı kural geçerlidir: önce `EnvKeys`'e kayıt, sonra `Env`. Ağaç genelinde `getenv(` geçişi yalnız `Env.php` içinde kalmalıdır.
4. **Kendi `Env` benzerinizi yazmayın.** Framework `Env`'i veriyor; ikinci bir okuyucu kalıntı taramasını kırar.

**Bilinen, kasıtlı davranış farkları (güvenli yönde):**

| Yer | Önce | Sonra |
|---|---|---|
| `DebugHelper` | yalnız `$_ENV['APP_ENV']` | `Env` → `$_ENV`/`$_SERVER`/getenv. `APP_ENV=production` gerçek ortamda tanımlıysa hata ayıklama çıktısı **artık da** bastırılır (sızıntı yönünde). |
| `PreBoot` / `CryptoHelper` / `BaseDbData` | ilk kaynakta boş değer varsa bir sonrakine geçmezdi | `Env` boş (yalnız boşluk) değerleri geçer, ilk **dolu** kaynağı kullanır. |
| `ProjectStatusService` / `WorkerTokenService` | `getenv` → `$_SERVER` → `$_SERVER['REDIRECT_…']` | `Env` (sıra `$_ENV`→`$_SERVER`→getenv) + `REDIRECT_…` **kayıtlı ad** olarak. Gerçekçi kurulumlarda aynı sonuç. |

**Geri alma:** Tek commit'in `git revert`'i yeterlidir; hiçbir veri/ayar dosyası taşınmaz.

### 0.9.0 - Adım 2 (yapıldı, **henüz yayınlanmadı**): `*DbData` sınıflarının taşınması

**Özet:** Dört veritabanı kimlik sınıfı `Core/Services/Gatekeepers/Models/` → **`Core/System/Config/Definitions/DbProfiles/`** altına taşındı. Namespace `Rbn\Framework\Core\Services\Gatekeepers\Models` → **`Rbn\Framework\Core\System\Config\Definitions\DbProfiles`**. **Sınıf adları değişmedi** (`BaseDbData`, `CommonDbData`, `MasterDbData`, `ProjectDbData`).

**Ek kırıcı değişiklik — `PASS`/`USER` sabitleri tamamen kaldırıldı (A0-8):** 0.9.0'da `PASS` yalnız `protected` yapılmıştı; bu bir koruma değildi, değer hâlâ depodaydı. Artık `BaseDbData`/`CommonDbData`/`ProjectDbData` içindeki `PASS` sabitleri ve `MasterDbData`/`CommonDbData` içindeki `USER` sabitleri **hiç yok**. Dışarıdan `MasterDbData::PASS` **ya da** `MasterDbData::USER` yazan kod artık **`Error`** alır (sessiz `''` / `'root'` değil). Tek erişim yolu `pass()` ve `user()` metotlarıdır (ortam değişkeni → sır dosyası → fail-closed). Projelerin **kendi** `*DbData` sınıfları `pass()`/`user()` override ediyorsa etkilenmez; sabit kullananlar `pass()`/`user()` çağırmalıdır.

**Kim etkilenir:** Framework'ü kullanan her proje. Taşıma framework genelindedir, tek projeye özel değildir.

**Ne yapılır — zorunlu sıra:**

1. **Tüm dosyaları yükle** (taşınan sınıflar dahil). Kısmi yükleme yasaktır.
2. **Eski 4 dosyayı sunucudan SİL** (`Core/Services/Gatekeepers/Models/{Base,Common,Master,Project}DbData.php`). Paket tüm dosyalarla birlikte yüklendiği için eski sınıflar kalırsa **iki kopya** olur; autoloader hangisini yükleyeceği kurallı değildir.
3. **Keşif haritalarını sil ve yeniden üret.** Haritalar `Storage/framework/` altında üretilir; sürüm takibinde **değildir** (üretilmiş çıktıdır). Eski harita kırık/eksik kalırsa site 500 verir.
4. **Doğrula:** her projede ana sayfa 200, master bağlantısı kuran komut (`php rbn project:list`) beklenen çıktıyı veriyor, çoklu alan adılı projelerde alan adı geçişleri çalışıyor.

**ÖNEMLİ — harita önbelleği tuzağı (Hasan 75 ve bu görevde yeniden ölçüldü):** Harita üretimi **yalnız web bağlamında** çalışır; CLI-only ve web dışı katmanlar haritaya yazılmaz. Yerelde 36 harita dosyası silinip 18 site ile yeniden üretildiğinde toplam kayıt **2833 → 1263** düştü (**133 kayıp tek bir projede**, yeni kayıt 0). Sayfa 200 verdiği için **gerileme görünmez**. Bu görevde ölçüldü: `DbData` referansı haritalarda **zaten 0** (ÖNCE ve SONRA), yani taşıma haritayı etkilemiyor. Bu yüzden haritalar **yedekten geri yüklendi** ve 36/36 SHA eşleşti. Canlıda da aynı ölçüm yapılmalı: kayıt sayısı düşerse **geri yükle**, haritayı yeni haliyle bırakma.

**Geri alma:** Kod commit'i geri alınır (`git revert`), eski 4 dosya **geri yüklenir**, keşif haritaları yedekten geri yüklenir. **Geri alma zorluğu: orta** — haritalar üretilmiş dosya olduğu için, kod geri alınsa bile haritaları geri almak gerekir.

### 0.9.0 - Adim 1b: cPanel sir dosyasi (yalniz cPanel kullanan akislar icin)

**Ozet:** cPanel kimlik bilgisi (sunucu, kullanici, API tokeni/parola) kaynak koddan cikarildi; artik `Core/System/Config/Secrets/cpanel-secrets.php` dosyasindan okunur. Bu dosya depoya girmez; **sunucuda elle olusturulur.**

**Yayin sirasi (zorunlu, cPanel kullanilan akislar icin):**

```
(1) Sunucuda Core/System/Config/Secrets/cpanel-secrets.php dosyasini OLUSTUR
    (cpanel-secrets.example.php sablonundan kopyala, gercek degerleri yaz, izin: 0600)
(2) Anahtarlarin gercekten yazildigini ve hicbirinde 'CHANGE_ME' KALMADIGINI DOGRULA
    (bos deger de reddedilir)
(3) SONRA kod dosyalarini yukle
```

**Tersi (once kod, sonra sir dosyasi) = cPanel kullanan akislar acmaz.** Etkilenen akislar: e-posta yonetimi, alan adi yonetimi, dosya yoneticisi, phpMyAdmin, webmail ve cPanel tek-tik oturum acma. Bu akislar sir dosyasi yoksa **acik hata** verir (sessizce yapilandirilmis sayilmaz).

**Kim etkilenir:** cPanel **kullanmayan** siteler ve komutlar **etkilenmez** - sir dosyasi tembel (gecikmeli) yuklenir, yalniz bir cPanel islevi cagrilinda okunur. Yalniz cPanel ekranlarini acan projeler etkilenir.

**Geri alma:**

- Sir dosyasi **silinmez**.
- Kod tarafi `git revert` ile geri alinir; eski kodda bu dosyaya bakilmaz.
- Dosyanin kendisi bozuksa sablondan yeniden olusturup degerleri geri yazin (ayri islemdir).

### 0.9.0 — Adım 3: Keşif haritaları sürüm takibinde değildir

**Özet:** Keşif haritaları her projede `Storage/framework/` altında **üretilir** ve depoya girmez. Framework güncellemesinden sonra eski haritalar geçerli olmayabilir.

**Kim etkilenir:** Harita üretimi çalışan her proje (framework geneli).

**Ne yapılır:** Framework dosyaları güncellendikten sonra ilgili projelerde haritalar silinir ve yeniden üretilir. Üretimden önce her harita sözdizimi denetiminden ve açılış testinden geçer.

**Geri alma:** Harita üretimi geri alınamaz; kod geri alınırsa haritalar yeniden üretilir (aynı işlem).

### 0.9.0 — Adım 4: IP güvenlik katmanı **log-only** olarak açılıyor

**Özet:** IP güvenlik katmanı (kara liste / beyaz liste / WAF tarama) daha önce hiç çalışmıyordu: okuduğu ayar sağlayıcısı ve kullandığı model adları hiçbir kayıt defterinde tanımlı değildi, bu yüzden katman her istekte sessizce atlanıyordu. Bu adımda okuma zinciri düzeltildi ve katman **ilk sürümde engelleme yapmadan** çalışır.

**Kim etkilenir:** Tüm projeler (framework geneli).

**Ne yapılır — zorunlu, bu sırayla:**

1. **Pilot site önce:** `projects/<proje>` (`site.example`) — tek alan adı, düşük trafik.
2. **Log-only modu varsayılandır.** IP katmanı engellemez; engelleyeceği kararı
   `Storage/logs/ipguard/<tarih>_<proje>.jsonl` dosyasına `would-block` olarak yazar
   (hangi IP, hangi kural, hangi proje). Kullanıcı hiçbir şekilde engellenmez.
3. **48 saat log topla.** `would-block` kayıtlarından gerçek yanlış-pozitif oranı çıkarılır.
   Tek bir meşru ziyaretçi veya Google/bing botu bile `would-block` ile karşılaştıysa
   `enforce` **açılmaz**; önce o kayıt nedenini temizle.
4. **Keşif haritalarını yeniden üret** (Adım 3). Yeni kayıtlar haritada görünmüyorsa
   IP katmanı yine sessizce atlanır — harita üretimi bu adımın parçasıdır.
5. **`enforce` ayrı bir yayındır.** Ayar tek yerde: `shield_ip_guard_mode = log_only | enforce`.
   `enforce` bu pakette **kapalı** bırakılır.

**Sık yapılan hata — ayarı değiştirdim ama etkisi yok:**
Güvenlik ayarları `settings_shield` önbellek anahtarıyla **diskte** saklanır
(`ShieldSettingsRepository::getSetting`). `cm_sys_settings_shield` tablosunda bir ayarı
değiştirdikten sonra önbellek temizlenmezse yeni değer **okunmaz**.
Ayar değişikliğinden sonra ilgili projenin `Storage/cache/` klasöründeki
`<proje>_settings_shield.cache` dosyası silinmelidir.

**İstemci IP'si kaynağı:** Framework `X-Forwarded-For` / `CF-Connecting-IP`
başlıklarına bilinçli olarak **GÜVENMEZ** (fail-closed); proxy/Cloudflare arkasında
IP tabanlı kontroller (`allowed_origins`, IP ban) `REMOTE_ADDR`'e bakar; güvenilir
proxy modeli ayrı bir karardır. Bu adımda da değişen bir şey yoktur.

**Ne değişmedi:** Bakım modu, hız sınırı ve oturum davranışı aynen korunur.
Bu adım yalnız IP katmanını okunur hale getirir; hiçbir isteği engellemez.

**Geri alma:** Tek commit; `git revert` + haritaların yeniden üretimi. **Geri alma zorluğu: düşük.**
(log-only hiçbir şeyi engellemediği için geri alınsa bile kullanıcı etkilenmez.)

---

### 0.9.0 — Adım 4: Hata ayıklama modu artık Host başlığından açılmaz

**Özet:** `RBN_DEBUG` / `RBN_DEV` sabitleri artık Host başlığındaki **alt dizgeye** bakarak belirlenmiyor. Üretim varsayılandır; geliştirme modu yalnız (a) sunucuda `RBN_DEBUG=1` (veya `RBN_DEV=1`) ortam değişkeninin açıkça tanımlı olması ya da (b) Host'un tam eşleşmesi/`.test` tam son-ek eşleşmesi **ve** istemcinin loopback ya da özel ağ IP'sinden gelmesi hâlinde açılır. `X-Forwarded-For` bilinçli olarak yok sayılır.

**Kim etkilenir:** Hata ayıklama ekranı gören geliştiriciler. Uzak bir makineden (uzak masaüstü, VPN, Docker/VM, SSH port yönlendirmesi) `*.test` adresine giderken hata ayıklama modu **kapalı** görünecektir.

**Ne yapılır:** Uzak geliştirme akışı gerekiyorsa web sunucusu ortamında `RBN_DEBUG=1` tanımlanır (Apache: `SetEnv RBN_DEBUG 1`; nginx+fpm: `fastcgi_param RBN_DEBUG 1;`). Bu değişken üretimde **tanımlanmamalıdır**.

**Geri alma:** Kod geri alınırsa `PreBoot::detectEnvironment()` eski hâline döner. Geri alma önerilmez (alt dizge açığı geri gelir).

---

### 0.9.0 — Adım 5: E-posta doğrulama / parola sıfırlama token kasası (migrasyon **zorunlu**)

**Özet:** Parola sıfırlama ve e-posta doğrulama bağlantıları artık tek kullanımlık ve süreli çalışır. Ham token veritabanında saklanmaz; `z_user_tokens` tablosunda yalnızca özeti, kullanım zamanı ve son kullanma zamanı tutulur. Dogrulama 24 saat, parola sifirlama 60 dakika gecerlidir.

**Yayın sırası — SQL ÖNCE:**

1. **1. adım (koddan önce):** `111-z_user_tokens.sql` dosyasını **her proje veritabanına** ayrı ayrı uygula. Tablo yoksa token üretimi çalışmaz ve e-posta doğrulama / parola sıfırlama akışı hata verir.
   - **Dosya depoda:** `rbnframework/Core/Database/Migrations/111-z_user_tokens.sql` (FW-KARAR-UYGULA-A / kira-2-6eb7f5 ile eklendi; `CREATE TABLE IF NOT EXISTS` olduğu için tekrar çalıştırmak güvenlidir ve mevcut tabloyu değiştirmez). phpMyAdmin'de içe aktarılabilir; `mysql -u <kullanıcı> -p <db> < 111-z_user_tokens.sql` ile de uygulanabilir.
   - Kapsam: yalnız proje/master şemaları (`z_` ön ekli kullanıcı tablolarını içeren veritabanları). `asw_*` tablolarına ve master `developers` tablosuna dokunulmaz.
   - Doğrulama: `SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'z_user_tokens';` → 1 satır.
2. **2. adım (SQL'den sonra):** kod dosyalarını dağıt.
3. **3. adım:** duman testi — parola sıfırlama isteği (500 değil), yeni parolanın kaydedilmesi, e-posta doğrulama bağlantısı.

**Geçiş etkileri (bilerek kabul edilenler):**

- **Mevcut doğrulanmış hesaplar etkilenmez.** `z_users.is_active = 1` olan hesaplar yeniden doğrulamaya zorlanmaz; giriş akışı aynen çalışır.
- **Eski (token'sız) doğrulama bağlantıları geçersizdir.** Artık token olmadan hiçbir hesap etkinleştirilmez; kullanıcı açık hata alır ve "yeni bağlantı iste" sayfasına düşer. E-posta gönderimi yaygın olduğu için bu, canlıya geçişten sonra kısa süreli bir destek yükü demektir.
- **Altı haneli kodla doğrulama ekranı kaldırıldı** (`/verify-code` rotası ve ilgili form). Parola sıfırlama tek bağlantı üzerinden yürür.
- `z_users_security.verification_token` / `reset_token` kolonları **SİLİNMİZ** (geri dönüş için kalır, kod artık okumaz/yazmaz). `z_users_security.type` kolonu **EKLENMEZ** — yokluğu ölçülmüştür ve kod artık o kolona `where()` atmıyor.

**Geri alma:** Kod geri alınabilir; tablo geri alınaması gerekmez (`DROP TABLE IF EXISTS z_user_tokens;` veri kaybına yol açmaz — tabloda yalnız geçici token satırları vardır). Ancak tabloyu düşürürseniz **dağıtılmış kod token üretemez**; önce kodu geri alın.

---

### 0.9.0 — Adım 6: Şifreleme tuzu fail-closed + gömülü kurulum parolası kaldırıldı (yayın öncesi **zorunlu** kontrol)

**Özet:** `Core/Support/Bridges/Helpers/Library/CryptoHelper.php` içindeki gömülü şifreleme tuzu kaldırıldı. Tuz artık sırasıyla `APP_KEY`, `ENCRYPTION_KEY` ve sır dosyasındaki `app_key` anahtarından okunur.

> **Tarihçe notu (artık geçerli bir adım değil):** Bu başlıkta geçen "gömülü kurulum parolası kaldırıldı, kurulumda rastgele üretilir" yönergesi **geçersizdir**. Kurulum aracı (Installer) bütünüyle kaldırıldı (bkz. **Adım 10**); artık ne gömülü parola vardır ne de kurulumda parola üreten bir adım.

**Yayın uyarısı (R1 fail-closed):** `APP_KEY` / `ENCRYPTION_KEY` / sır dosyası `app_key` tanımlı olmayan projelerde şifreleme artık `RuntimeException` verir. Bu kasıtlıdır (gömülü zayıf tuzla devam etmektense durmak), ama **yayından önce her projede** doğrulanmalıdır; aksi halde o projelerde şifreleme kullanan her akış kırılır. Hata mesajı hangi değişkenin nerede tanımlanabileceğini söyler.

**Geçici kill-switch (kalıcı değildir):** `RBN_ALLOW_LEGACY_SALT` **ve** `RBN_LEGACY_SALT` ortam değişkenleri birlikte tanımlanırsa eski gömülü tuz geçici olarak geri gelir. Yalnız onay (`RBN_ALLOW_LEGACY_SALT`) tek başına **etkisizdir** — yarım kabul bilinçli olarak reddedilir. Bu, yalnızca geçiş süresi için vardır; kalıcı yapı değildir ve yayından sonra kaldırılmalıdır (ayrıntı: güvenlik incelemesi raporu).

**Geri alma:** Kod geri alınabilir; geri alma, gömülü tuzun geri gelmesi demektir (güvenlik gerilemesi) — bu yüzden geçici çözüm kill-switch'tir, kod geri alma değildir. Şifreli verinin anahtarı değiştiği için **geri alınan kod, eski anahtarla yazılmış veriyi çözemez**; sır rotasyonu gerekiyorsa veri kaybı olabilir.

### 0.9.0 — Adım 8: Sunucuda **TEK dosya**: `secrets.php` (eski 3 adımın yerine)

**Özet:** Artık **bir** sır dosyası vardır: `Core/System/Config/Secrets/secrets.php`.
Önceki iki dosya (`master-secrets.php`, `cpanel-secrets.php`) ve onların "geriye uyum"
kipi **TAMAMEN KALDIRILDI**. `MASTER_DB_USER` / `MASTER_DB_PASS` ortam değişkeni ile
geçersiz kılma yolu da kaldırıldı — üç yol değil, **TEK yol** vardır.

**Yayın sırası (bu adımda önce KOD gelir):**

```
① ÖNCE kodu yükle
② SONRA sunucuda Core/System/Config/Secrets/secrets.php oluştur
     (secrets.example.php'ten kopyala, CHANGE_ME'leri doldur, chmod 600)
```

**`secrets.php` içeriği (bölümlü ve düz):**

```php
return [
    'master_db' => ['host' => '…', 'port' => '…', 'name' => '…', 'user' => '…', 'pass' => '…'],
    'smtp'      => ['enabled' => '…', 'host' => '…', 'port' => '…', 'secure' => '…',
                    'user' => '…', 'pass' => '…', 'from_address' => '…', 'from_name' => '…'],
    'cpanel'    => ['host' => '…', 'user' => '…', 'token' => '…'],
    'app_key'   => '…',
    'api'       => ['iyzico' => ['api_key' => '…', 'secret_key' => '…']],
];
```

**Kod değişikliği (dikkat):** `MasterDbData` içindeki `DB_NAME`, `SMTP_ENABLED`,
`SMTP_HOST`, `SMTP_PORT`, `SMTP_USER`, `EMAIL_FROM_ADDRESS`, `EMAIL_FROM_NAME`
sabitleri **KALDIRILDI**; hepsi artık metotla okunur:
`MasterDbData::host()/port()/dbName()/user()/pass()`,
`MasterDbData::smtpEnabled()/smtpHost()/smtpPort()/smtpSecure()/smtpUser()/smtpPass()/emailFromAddress()/emailFromName()`.
`MasterDbData::DB_NAME` kullanan kod `MasterDbData::dbName()` çağırmalıdır.
`Secrets::cpanel()` dizisinin anahtarları `cpanel_host/cpanel_user/cpanel_token`
olarak **KORUNDU** (kabul testi bunu sabitler).

**Kurallar:** `CHANGE_ME` hiçbir alanda kabul edilmez (fail-closed). `master_db.pass`
ve `smtp.pass` **boş olabilir** (yerelde gerçekten boş); `master_db.host/port/name/user`,
`smtp` ve `cpanel` alanları **boş olamaz**. Alan eksikse hata **adını söyler**
(`secrets.php: master_db.user tanimli degil`) ve **değeri yazmaz**.

**Geri alma:** Kod tek commit ile geri alınabilir. `secrets.php` silinmez.

---

### 0.9.0 — Adım 7: ~~Sırlar tek çıkıştan okunur (`secrets.php` bölümlü tek dosya)~~ — **GEÇERSİZ, Adım 8'e bakınız**

> Bu adım "isteğe bağlı geçiş" olarak yazılmıştı ve eski iki dosyayı okumaya devam
> ediyordu. **Adım 8 ile tamamen kaldırıldı:** artık `secrets.php` **zorunludur** ve
> eski iki dosya **okunmaz**. Aşağıdaki metin tarihsel kayıttır, uygulamayın.

**Özet:** ~~Sırlar artık tek bir okuyucudan (`Core\System\Config\Secrets`) ve isteğe bağlı olarak **tek bir bölümlü dosyadan** (`Core/System/Config/Secrets/secrets.php`) okunur.~~

**Yayın sırası (bu adımda önce KOD gelir):**

```
① ÖNCE kodu yükle
② SONRA (isteğe bağlı) sunucuda Core/System/Config/Secrets/secrets.php oluştur
     (secrets.example.php'ten kopyala, CHANGE_ME'leri doldur, chmod 600)
```

Tersi de güvenlidir (yeni dosya olmadan eski dosyalar okunur), fakat **önce kod** önerilir: `secrets.php` yokken sistem eski kipte davranır, dosya hazır olduğunda tek dosyaya geçer.

**Öncelik kuralı (birebir):**

| Durum | Okunan |
|---|---|
| `secrets.php` **VAR** | yalnız `secrets.php` (bölümlü kip) |
| `secrets.php` **YOK** | `master-secrets.php` + `cpanel-secrets.php` (eski kip) |

**`secrets.php` içeriği:**

```php
return [
    'master_db' => ['pass' => '…', 'user' => '…'],       // MasterDbData::pass()/::user()
    'smtp'      => ['pass' => '…'],                       // MasterDbData::smtpPass()
    'cpanel'    => ['host' => '…', 'user' => '…', 'token' => '…'],
    'api'       => ['iyzico' => ['api_key' => '…', 'secret_key' => '…']],
    'app_key'   => '…',                                    // CryptoHelper
];
```

**Kurallar:** `CHANGE_ME` hiçbir bölümde kabul edilmez (fail-closed). `master_db.pass` ve `smtp.pass` **boş olabilir** (yerelde gerçekten boş); `cpanel` ve `api` değerleri **boş olamaz**.

**Dikkat — geçiş tuzağı:** `secrets.php`'ye geçerken **`cpanel` bölümünü de doldurun.** Yeni dosya tek başına yetmez; `cpanel-secrets.php`'i silerseniz ve `cpanel` bölümünü yazmamışsanız cPanel akışları (rbncore paneli: e-posta, alan adı, dosya yöneticisi, phpMyAdmin, webmail, cPanel SSO) fail-closed hata verir. Doldurursanız eski `cpanel-secrets.php`'i silebilirsiniz.

**API değişikliği (geriye uyumlu):** yeni çıkışlar `Secrets::masterDb()`, `::smtp()`, `::cpanel()`, `::api($ad)`, `::section($ad)`, `::appKey()`. Eski metot adları **korundu**: `all()`, `get()`, `masterDbPass()`, `masterSmtpPass()`, `optional()`, `cpanel()`, `cpanelHost()`, `cpanelUser()`, `cpanelToken()`. Çağıran kod kırılmaz. Tek istisna: **`Secrets::cpanel()` dizisinin anahtarları `cpanel_host/cpanel_user/cpanel_token` olarak KORUNDU** (kabul testi ve mevcut proje kodu bunu sabitler); kanonik bölüm biçimi `Secrets::section('cpanel')` üzerinden okunur.

**Yeni dış servis eklemek artık tek dosya değişikliğidir:** `api` bölümüne yeni bir anahtar + satır ekleyin (`Secrets::api('iyzico')`). İkinci bir sır dosyası açmaya gerek yoktur.

**Geri alma:** Kod tek commit ile geri alınabilir; `secrets.php` silinmez (varlığı zararsızdır — yeni koda geri dönüldüğünde `secrets.php` yine öncelikli okunur, bu yüzden **önce `secrets.php`'yi kaldırıp sonra kodu geri alın** ya da `secrets.php`'yi yerinde bırakıp eski koda dönün). Sır değerleri hiçbir adımda değişmez; rotasyon ayrı operasyonel işlemdir (bkz. Genel kurallar 5).

### 0.9.0 — Adım 7b: Sır dosyası izinleri artık **fail-closed** (B-4) — **yayın öncesi kontrol**

**Özet:** Bu adımda dosya **yoksa** hiçbir şey değişmez (aynı davranış). Değişiklik yalnız dosya **var ama okunamıyorsa / izin fazla genişse / bozuksa** ortaya çıkar: `Secrets::optional()` bu durumları artık `null` ile yutmaz, açık hata verir.

**Neden:** Önceden 0644 izinli veya bozuk bir sır dosyasında `optional()` sessizce "anahtar yok" deyordu; çağıran (`BaseDbData::pass()`, `Secrets::appKey()`) **kendi genel "çözülemedi" hatasını** atıyordu. Yani hatayı gören operator sır dosyasını yeniden yazdırıyordu, sorun (geniş izin) duruyordu ve ikinci denemede de aynı şey oluyordu — **yanlış teşhis + belirsiz süre**. Artık hata mesajı **gerçek nedeni** söyler (okunamadı / izin fazla geniş / biçim hatalı / dizi dönmüyor).

**Yayın öncesi kontrol (her sır dosyası için, tek komut):**

```
chmod 600 Core/System/Config/Secrets/secrets.php
```

Grup erişimi gerekiyorsa `640` **da reddedilir** (`guardFileMode()` yalnız tam `0600` kabul eder: `izinTavani() = 0600`, grup/başka biti varsa hata).

**Platform farkı (önemli):** izin kontrolü **Linux/macOS'ta** çalışır; **Windows'ta bilinçli olarak ATLANIR** (`Secrets::guardFileMode()` → `PHP_OS_FAMILY === 'Windows'` → erken dönüş), çünkü NTFS izinleri `fileperms()` ile temsil edilmez (yaygın olarak `0666` döner) ve kontrol her yüklemeyi hata ile düşürürdü. Yani **Windows'ta 0644 bir sır dosyası sessizce kabul edilir**; koruma orada `.gitignore` + yayın sırasındaki operator `chmod 600` sorumluluğuna dayanır. Linux sunucusunda kontrol kendiliğinden devrededir.

**Yayın sırası:** bu kontrol **kod yüklenmeden önce** yapılırsa yayın kesintisiz geçer. Yapılmazsa, yalnız **hatalı izin/bozuk biçimli** kurulumlar etkilenir; hata mesajı hangi dosyada ne yapılacağını söyler (sır değeri **yazılmaz**).

**Yanlış negatif riski yoktur:** `optional()` yalnız "sır/bölüm **tanımlı değil**" durumunda `null` döner — tanımlı değerler her zaman döner. Değişen tek şey hata yutma katmanıdır.

---

### 0.9.0 — Adım 9: Merkezi lisans tabloları (`applications` + `licences`) — elle migration

**Özet:** Lisans artık tek merkezde: proje de uygulama da aynı sistem. Master veritabanına **iki yeni tablo** eklenir — `applications` (ürün: `app_key`, `platform`, `channel`, `status`, `current_version`, `download_url`, `sha256`, `min_version`) ve `licences` (TEK lisans tablosu: `licence_key`, `subject_type` = `project|application`, `subject_id`, `tier` = `FREE|LIFETIME|PRO`, `status` = `active|suspended|revoked|expired`, `expires_at`, `max_activations`, `device_hash`, `notes`). **Yayın öncesi komut (yalnız elle, otomatik çalışmaz):** `php rbn master:migrate` — komut `applications` + `licences` tablolarını kurar ve `projects.license_key` değerlerini `licences` tablosuna **kopyalar** (`RBN-LIFETIME-` → `LIFETIME`, `RBN-FREE-`/`FREE-OSS` → `FREE`, diğer → `PRO`; `projects.status = 'suspended'` → `suspended`, diğer her durum → `active`; boş anahtarlı projeye satır **yazılmaz**, adedi komut çıktısında bildirilir). `projects` tablosundan **hiçbir sütun kaldırılmaz** — `version` ve `license_key` yerinde kalır, mevcut kod okumaya devam eder; lisans tablosunda **sürüm sütunu yoktur** (sürüm ürüne aittir, `applications.current_version`). Geri alma: `php rbn master:migrate --rollback` yalnız bu iki tabloyu kaldırır, `projects`'e dokunmaz; durum: `php rbn master:migrate:status`.

**Lisans kapısı bu tabloya bağlandı (yayın öncesi zorunlu sıra):** Lisans kararı artık `projects` tablosundaki metin anahtardan değil, **`licences` tablosundaki kayıttan** verilir. Bu nedenle `master:migrate` ve lisans kayıtlarının (tohum/ekleme) **KODDAN ÖNCE** çalışması gerekir. Kayıt yoksa, okunamıyorsa ya da geçersizse proje **"kısıtlı"** sayılır — yani `status != active` olan projeler kapanır. Bu, lisansın zorunlu olduğu kurulumlarda beklenen davranıştır; kaldırma/erişim adımı değildir.

---

### 0.9.0 — Adım 11 (KIRICI): WHERE'siz `update()` / `increment()` / `decrement()` artık istisna fırlatır

**Özet:** Sorgu katmanı (`QueryBuilder`) artık WHERE koşulu olmadan toplu yazma yapmaz. `update()`, `updateAffected()`, `increment()` ve `decrement()` çağrılarında hiçbir `where()` / `whereIn()` / `whereRaw()` koşulu yoksa **istisna fırlatılır** ve hiçbir satır değiştirilmez. Bu, `delete()` ile zaten var olan güvenlik sözleşmesinin `update()` ve sayaç yollarına da uygulanmasıdır (eskiden `update()` WHERE'siz çalışıp tüm tabloyu güncelliyor ve sabit `true` dönüyordu).

**Ne yapmalısınız:**

- Toplu güncelleme **gerçekten** yapmak istiyorsanız açık bir koşul yazın. Artık "koşulsuz toplu güncelleme" diye bir kısayol yoktur:

  ```php
  // YANLIŞ — artık istisna fırlatır, hiçbir satır yazılmaz
  $db->table('t')->update(['status' => 'pasif']);

  // DOĞRU — hedef satırları açıkça belirtin
  $db->table('t')->whereIn('id', $kimlikler)->update(['status' => 'pasif']);
  ```

- Sayaçlar için de aynı kural geçerlidir: `where()` eklemeden `increment()` / `decrement()` çağırmak istisna fırlatır.
- **Dönüş sözleşmesi değişmedi:** `update()` her zaman `bool true` döner (hiçbir satır eşleşmese de). Etkilenen satır sayısına ihtiyacınız varsa `updateAffected()` kullanın — bu davranış önceden de böyleydi.
- **Yan etki düzeltmesi:** `increment()` / `decrement()` daha önce `SQLSTATE[HY093]` veriyor ve sayacı hiç güncellemiyordu. Artık adlandırılmış parametrelerle çalışır; **daha önce sessizce çalışmayan** model katmanı sayaçları (view count vb.) artık gerçekten artıyor. Yeni yazılan testlerde sayaç değerlerine göre doğrulama yapın.

**Geri alma:** Kod değişikliği geri alınırsa, WHERE'siz toplu yazma yolu tekrar açılır. Geri alınacaksa geçici olarak açık koşul (`->whereIn('id', $tumId)`) ekleyin — koşulsuz çağrıyı geri açmayın.

---

### 0.9.0 — Adım 10 (KIRICI): Installer kaldırıldı, web kurulum yolu ve konsol kurulum komutları kalktı

**Özet:** `rbnframework/Installer/` klasörünün tamamı, `InstallerStage` ve `isInstallerBypass()` kaldırıldı. **Kırıcı değişiklik:** `rbn install`, `rbn system:check`, `rbn install:project` ve `rbn shield:sync` komutları **artık yoktur**; `/rbn/install` web kurulum adresi **artık yoktur** (`RouteBlueprint::SYSTEM_ALLOWED_PATHS` kaydı silindi) ve o adrese yapılan istekler normal yönlendirme kurallarına tabidir. `Installer\Services\InstallationService` ile beş `Installer\Handlers\*` servis kaydı, `FrameworkContext::installer()` erişimcisi, `FolderMatrix` `INSTALLER` girdisi ve Autoload PSR-4 `Installer\` girdisi de kaldırıldı.

**Ne yapmalısınız:**

- Yeni bir proje kurmanız gerekiyorsa **elle kurun**: master veritabanına `projects` satırı, proje klasörü ve `Core/config/project-settings.php` dosyası oluşturulur. Konsoldan otomatik kurulum yolu yoktur.
- `system:check` yerine **`system:doctor` / `doctor`** komutunu kullanın (sistem sağlığı, Master DB ve tüm projeleri teşhis eder).
- `shield:sync` için bir komut kalmadı; gerekiyorsa Shield yapılandırması ilgili servisler üzerinden elle yönetilir.
- Yeni hiçbir bypass/kısa yol **yazılmadı**. Çekirdek tanılama katmanları (asset, bakım, izin, sistem, shield, veritabanı) artık istek adresi içeriğine bakmaz ve istisna durumunda kapatılan (fail-closed) yönde çalışır.

**Geri dönüş:** Değişiklik yalnız framework kodundadır; veritabanı şeması **değişmedi**, migration yoktur. Geri almak için bu değişiklik kümesini geri alın.

---

### 0.9.0 - Adım 12 (KIRICI): CSRF doğrulaması TÜM roller için zorunlu (developer muafiyeti kalktı)

**Özet:** `developer`, `admin`, `superadmin` ve master-developer oturumları artık CSRF tokenı **taşımadan** POST/PUT/PATCH/DELETE isteği gönderemez. `CsrfHandler::verify()` içindeki developer bypass bloğu kaldırıldı; `FormGuardHandler::audit()` bu rollere verdiği bot/orijin/user-agent/hız sınırı muafiyetlerini korurken CSRF adımını artık atlamaz. Token üretimi ve geçerlilik süresi (1 saat) değişmedi.

**Kırıcı olan taraf:** `developer` oturumuyla yapılan POST isteklerinin token taşıması gerekir. Özel AJAX çağrılarınıza `X-CSRF-TOKEN` ekleyin ya da gövdeye `csrf_token` alanı koyun.

**Ne yapmalısınız:**

- **Düzenli HTML formu:** `<form>` içine `@csrf` ekleyin (framework panellerinde bu zaten var).
- **Özel AJAX / `fetch` / XHR:** `rbnService.post()` token'ı otomatik ekler — ancak **sayfada `<meta name="csrf-token">` olmalıdır**. Framework'ün kendi panel başlığı (`RbnAdmin/Layouts/panel_header.rbn.php`) bu etiketi basar; kendi panel/şablonunuz varsa `<?= $this->csrfMeta(); ?>` (veya `<meta name="csrf-token" content="<?= $this->csrfToken(); ?>">`) ekleyin. Etiket yoksa `RbnService` `null` bulur ve POST token'sız gider → istek reddedilir.
- **Token'ı sunucudan almak:** `csrfToken()` (değer) / `csrfField()` (gizli input) / `csrfMeta()` (meta etiketi) yardımcıları kullanılabilir. Yanlış veya süresi dolmuş token da reddedilir (fail-closed); sessiz kabul yoktur.
- **CSRF hatası görürseniz:** isteğin `csrf_token` alanını/başlığını kontrol edin, tarayıcıda 403 + "Token missing"/"Invalid or expired CSRF token" mesajı göreceksiniz.

**Geri dönüş:** Değişiklik yalnızca framework kodundadır; şema/migration yoktur. Geri alınacaksa bu değişiklik kümesini geri alın — ancak F-08 açığı geri gelir.

---

### 0.9.0 - Adım 13 (KIRICI): Scoped modellerde kullanıcı `project_key`'i yok sayılır

**Özet:** `protected bool $scoped = true;` tanımlayan modellerde `create()` **ve** `update()` artık `project_key` değerini istekten değil, her zaman sunucu tarafındaki aktif uygulama bağlamından yazar. İstekte gelen `project_key` (dolu olsa bile) ezilir. Kapsam dışı (`scoped = false`) modellerin davranışı **değişmedi**.

**Kırıcı olan taraf:** kapsamlı bir modele bilerek başka bir proje anahtarıyla kayıt yazan kod artık aktif anahtara düşer. Framework içinde bunu yapan tek yer, güvenlik ayarlarını `GLOBAL` kapsama yazan `ShieldSettingsRepository::saveSetting()` idi; o artık açık ve loglanan `writeAsProject()` kaçış kapısından geçiyor.

**Ne yapmalısınız:**

- **Kendi kapsamlı modelinize başka projeye yazdırıyorsanız:** `$model->writeAsProject('GLOBAL')->save([...])` ya da `->update($id, [...])` kullanın. Bu kapı her çağrıda `security` kanalına `MASS_ASSIGNMENT_PROJECT_OVERRIDE` kaydı düşürür.
- **Yazmayan (okuma) kapsam atlatması değişmedi:** `withoutProjectScope()` yalnızca sorgu içindir.
- **Şema/migration yoktur.** `QueryModelTrait::insert()` yolu değişmedi: kapsam enjekteyonu hâlâ yapılmaz, yalnız log-only ölçüm üretilir.

**Geri dönüş:** `CrudModelTrait` içindeki kapsam enjeksiyonu tek bloktur; geri alınacaksa o blok geri alınır — ancak kiracı izolasyonu açığı geri gelir.

### 0.9.0 - Adım 13b (motor eklendi, zorlama Adım 14'te açıldı): `$fillable`/`$guarded` ve korumalı alan log-only izleme

**Özet:** Model katmanına iki yeni anahtar eklendi — `security.mass_assignment` (`$fillable`/`$guarded` süzgecini açar) ve `security.protected_field_log` (korumalı alan sayacını açar). Bu adımda motor eklenmiş, **zorlama açılmamıştı**; zorlama ve beş güvenlik modeline `$guarded` tanımı **Adım 14**'te açıldı (ikisi de bu sürümde varsayılan AÇIKTIR).

**Kırıcı olan taraf:** bu adımda yoktu — anahtarlar açılmadığı sürece hiçbir davranış değişmedi. Bir model `$fillable`/`$guarded` tanımlamazsa `security.mass_assignment` açık olsa bile süzme uygulanmaz.

**Ne yapmalısınız:** Zorlamayı açmadan önce `security.protected_field_log` ile ölçüm yapın: `Storage/logs/security/*.jsonl` içinde `MASS_ASSIGNMENT_BLOCK` satırları, alan adı + model + işlem türünü içerir (**değer içermez**). Ölçüm bittikten sonra beş güvenlik modeline tanım verilecektir; o adım ayrı bir sürüm notudur.

---

### 0.9.0 - Adım 14 (KIRICI): Beş güvenlik modelinde korumalı alanlara toplu atama yazamaz

**Özet:** `security.mass_assignment` artık **varsayılan AÇIK**tır ve beş model `$guarded` tanımı kazandı. `create()`/`update()`/`save()` bu alanları süzerek **düşürür**:

| Model | Korumalı alan |
|---|---|
| `UsersModel` | `role`, `email`, `username` |
| `UserSecurityModel` | `user_id` |
| `MasterDevelopersModel` | `role`, `password` |
| `MasterProjectsModel` | `license_key`, `status` |
| `MasterLicenceModel` | `status` |

**Kırıcı olan taraf:** bu beş modelin korumalı alanlarına `update()`/`create()` ile **doğrudan** yazan özel kod artık o alanı **yazamaz** — süzgeç sessizce düşürür. Framework içindeki her meşru yazıcı bu sürümle birlikte yetkili yola taşındı:

- rol ataması → `UserManager::setRole($id, $role)` (rol beyaz listesiyle; `UserManagementController::updateRole()` bunu çağırır)
- parola → `UserRepository::updatePassword($id, $plain, $role)` (özet sunucuda üretilir)
- güvenlik kasası → `UserSecurityRepository::saveSecurityData($userId, $data)` (`user_id` **daima** metot imzasından gelir)
- lisans durumu → `MasterLicenceRepository::create()` / `updateStatus()`
- proje kimliği → `MasterProjectsRepository::createProjectIdentity()` / `updateProjectIdentity()`

**Ne yapmalısınız:**

- **Kendi kodunuz bu alanlara yazıyorsa yetkili yollara geçirin.** Ham kullanıcı girdisiyle `role`/`email`/`username`/`password`/`user_id`/`license_key`/`status` yazmak artık mümkün değildir; bu amaçla `UserManager::setRole()` gibi **karar alan** metotlar kullanın.
- **Meşru bir yazma için model düzeyinde kapı açmanız gerekiyorsa:** `$model->authorizeFields(['status'])->update($id, [...])`. Bu kapı **klon** döndürür (kaynak model değişmez), her çağrıda `security` kanalına `MASS_ASSIGNMENT_AUTHORIZED_WRITE` kaydı düşürür ve **istekten gelen alan adını kabul etmez** — izin listesini siz yazarsınız. `writeAsProject()` gibi sahte bir genel ad kullanmayın.
- **`email` değiştirmek istiyorsanız:** `UsersModel`'da `email` de korumalıdır; `UserManager` üzerinden açık bir yazıcı kullanın. RbnAdmin profil ekranı (`UserManagementController::profileUpdate()`) bugün ayrı bir nedenle kırıktır (`z_users` tablosunda `firstname`/`lastname` kolonları yoktur); o ekranı düzeltirken `email` alanını da yetkili yola taşıyın.
- **Geri dönüş (geçici):** `Config::set('security.mass_assignment', false)` (ya da `'off'` / `'log_only'`) tek satırdır ve tüm süzme kapanır. Güvenlik açığı geri gelir; kalıcı çözüm değildir.
- **Ölçüm anahtarı açık kaldı:** `security.protected_field_log` varsayılan AÇIK; `Storage/logs/security/*.jsonl` içindeki `MASS_ASSIGNMENT_BLOCK` satırları hangi modelin hangi korumalı alana ne sıklıkla veri gönderdiğini gösterir (**değer içermez**).
- **Şema/migration yoktur.** `status` ve `is_active` bilinçli olarak korumalı sayılmaz (meşru çağıranları kırmamak için); `insert()` ham yolu yalnız ölçülür, süzülmez.

**Geri dönüş:** `MassAssignmentTrait::massAssignmentMode()` içindeki tek varsayılan değer (`'on'` → `'off'`) ile tüm zorlama kapanır. Beş modeldeki `$guarded` tanımlarını kaldırmak zorunlu değildir — bayrak kapalıyken motor alan süzmez.

---

## Genel kurallar

1. **Dağıtım dosya bazlıdır:** framework klasörünün tamamı değil, değişen dosyalar canlıya gider. Bir framework dosyası = o dosyayı kullanan tüm siteler.
2. **Kısmi dağıtım yok:** bir dalga tek commit grubudur, yarısı yüklenmez.
3. **Yayın öncesi:** yerel tam regresyon (duman testi) yeşil olmadan canlıya çıkılmaz.
4. **Duman testi kapsamı:** ana sayfa (her alan adı), giriş + CSRF'li panel, çoklu alan adı geçişi, iletişim formu gönderimi, parola sıfırlama (500 değil), hızlı hatalı girişte kilit, oturum çerezi bayrakları, panel sayfası, ajan uçları, statik varlık önbellek başlığı.
5. **Sır rotasyonu geri alınamaz.** Parola değişiklikleri ayrı, operasyonel bir işlemdir; kod güncellemesiyle aynı pakette yapılmaz ve bu dosyada "geri al" adımı yoktur.
