# RbnAdmin/Providers + Services + Traits — analiz ve sunum katmanı

> **Doğrulanan kod tabanı:** `d49b4413` (dal `feat/fw-license-master`) · **Tarih:** 2026-10-05 · **Yayın:** 0.9.6 = bu commit + sonrası; belge yalnız doğrulama anındaki kodu anlatır
> **Kaynak klasörler:** `Bundles/RbnSuite/RbnAdmin/{Providers,Services,Traits}/`
> — **7 `*.php`** (Providers 4 · Services 2 · Traits 1)
> **Envanter:** 7 dosyanın 7'si anlatıldı.
> Üst belge: [README.md](README.md)

## 1. Ne işe yarar, kimler kullanır

Bu üç klasör, RbnAdmin'ın **"veriyi topla ve sun"** katmanıdır. Hiçbiri HTTP
bilmez, hiçbiri rota tanımlamaz. Çağıranlar:

* **Kontrolörler** → `AnalyticsService` (`service('analytics')`), `RbnAdminService`
  (`service('socialmedia')` / `service('rbnAdmin')`).
* **Panel görünümleri** → doğrudan `provider('social')` üzerinden platform
  meta verisi (RbnStudio `getActiveSocialPlatforms()`, `RbnStudioController.php:276`).
* **`TrafficAnalysisTrait`** → iki ayrı sınıf tarafından `use` edilir
  (`AnalyticsProvider.php:20`, `Providers/Fluent/TrafficStatsChannel.php:16`);
  bu yüzden trait "RbnAdmin'ın trafik motorudur".

Kontrolör → servis → kanal → depolama zinciri:
`WebtrafficController` → `AnalyticsService::traffic()` → `TrafficStatsChannel`
→ `TrafficAnalysisTrait::get()` → `$this->storage->traffic()->get('date_…')`.

## 2. Dosya envanteri (7 dosya)

| Dosya | Tür | Görev | Önemli yöntemler |
|---|---|---|---|
| `Providers/AnalyticsProvider.php` | `BaseProvider` | Trafik + kullanıcı istatistiğinin **örgütlenmemiş** toplayıcısı; `$targetModel = 'Users'` (`:18`) | `getDashboardStats(): array` (`:25`), `getUserStats(): array` (`:43`) |
| `Providers/SocialMediaProvider.php` | `BaseProvider` | JSON dosyasından platform tanımlarını yükler, akıcı ("self" döndüren) arayüz sunar | `__construct()`, `getPlatform(string $key): self`, `all(bool $sortByOrder=true): array`, `total(): int`, `exists(): bool`, `title()`, `icon()`, `faIcon()`, `riIcon()`, `color()`, `order()`, `shareUrl()`, `shareClass()` |
| `Providers/Fluent/TrafficStatsChannel.php` | `BaseChannel` | Trafik kanalı; **her okuma önbellekten** | `__construct()` → `parent::__construct('analytics_traffic')` (`:20`), `stats()`, `summary()`, `hits(): int`, `trend()` |
| `Providers/Fluent/UserStatsChannel.php` | `BaseChannel` | Kullanıcı kanalı; aynı önbellek deseni | `__construct()` → `parent::__construct('analytics_users')` (`:18`), `get()`, `count(): int`, `stats()`, `total(): int`, `distribution()`, `roles()`, `all()` |
| `Services/AnalyticsService.php` | `BaseService` | Kanallara ve GA4'e tek giriş kapısı | `users(): UserStatsChannel`, `traffic(): TrafficStatsChannel`, `record(array $data=[])`, `isGoogleAnalyticsActive(): bool`, `getGoogleAnalyticsReports(?string $s=null, ?string $e=null, string $type='standard')` |
| `Services/RbnAdminService.php` | `BaseService` | Tema rengi kaydı (**yetki kontrollü**) + paylaşım düğmeleri | `saveColors(string $p, ?string $s=null, ?string $projectKey=null): bool` (`:24`), `allSocialPlatforms(bool $sortByOrder=true): array`, `socialPlatform(string $key): object`, `totalSocialPlatforms(): int`, `getShareButtons(string $url, string $title): array` (`:81`) |
| `Traits/TrafficAnalysisTrait.php` | trait | Trafik sorgu dilini ve özet hesabını tanımlar | `withProject(?string $pk)`, `query()`, `forDate(string)`, `forToday()`, `forRange(string,string)`, `forTrend(int)`, `limitQuery(int)`, `get(): array`, `count(): int`, `computeSummary(): array`, `devices(): array`, `top(string $key, int $limit=5): array`, `getActiveCount(int $minutes=5): int`, `all()`, `hits(): int` |

**Kayıt yolları.** `AnalyticsService`, `RbnAdminService` ve iki provider
`Models/ModuleData.php:33-46` `registerMap()` ile paket adı altında kayıt edilir
(`analytics`, `socialmedia`, `rbnAdmin`, `analytics`(provider), `social`(provider)).
Ayrıca `RbnAdminService` ve `SocialMediaProvider` global kayıt defterinde de vardır
(`Core/System/Registries/RegistryMap/SystemLogicMapTrait.php:78` ·
`SystemPhysicalMapTrait.php:79`) — yani **aynı sınıfa iki ayrı anahtardan**
(`socialmedia` paketten ve globalden) erişilebilir; ikisi de aynı sınıfı gösterir.

## 3. Akışlar

### 3.1 Trafik özeti (dashboard'ın kullandığı yol)

```
service('analytics')->traffic()->summary()      AnalyticsService.php:31-34
  → new TrafficStatsChannel()                   TrafficStatsChannel.php:18-21
  → getFromCache('summary', computeSummary)     TrafficStatsChannel.php:44-46
      → TrafficAnalysisTrait::computeSummary()  TrafficAnalysisTrait.php:137
          ├─ storage->traffic()->getMonthlyData(date('Y-m'))          :150
          ├─ listDates() → her AY için getMonthlyData() → toplamlar  :167-187
          ├─ bot temizliği: 'Bot' öneki olan yerler çıkarılır         :192-202
          ├─ active_now = getActiveCount(5)                            :217
          └─ seven_day_trend / top_pages / top_sources                 :222-234
```

### 3.2 Önbellek anahtarı nasıl üretiliyor

`BaseChannel::getFromCache()` (`Core/Base/Patterns/BaseChannel.php:252-…`) anahtarı
şunlardan kurar: kanal adı (`analytics_traffic` / `analytics_users`) + filtreler +
sıralamalar + arama terimi + hidrasyon modu → `md5(json_encode($state))`
(`:261-271`), artı **proje anahtarı** (`:277-294`: önce `?project=` sorgusu,
düşerse `project_key()`). Yani trafik istatistikleri **proje başına ayrı**
önbelleklenir.

`json_encode` başarısız olursa (INF/NAN/geçersiz UTF-8) yedek karma
`print_r` ile üretilir; ölçülen 3 bozuk filtre durumu tek anahtara düşmesin diye
eklenmiştir (`BaseChannel.php:255-258`).

### 3.3 Tema kaydı ve yetki

```
RbnAdminController::saveTheme()                   RbnAdminController.php:166
  → request->form([… regex:/^#[0-9A-Fa-f]{6}$/])  :169-173
  → RbnAdminService::saveColors()                 RbnAdminService.php:24
      ├─ handler('access')->can('admin')  → değilse throw            :27-30
      ├─ projectKey ?: project_key() ?: 'default'                    :32
      └─ service('settings')->updateSettings($settings, $projectKey) :43
  → Route->handleResult($result)                                     :185
```

`$settings` dizisine **yalnız** dolu gelen renkler eklenir (`:38-40`); ikincil renk
boşsa hiç yazılmaz, yani bir önceki değer korunur.

### 3.4 Paylaşım düğmeleri

`getShareButtons()` (`:81-114`) tüm platformları gezer, yalnız `shareUrl()` **ve**
`shareClass()` dolu olanları alır; `{url}`/`{title}` yer tutucularını
`urlencode()` edilmiş değerlerle değiştirir (`:95-99`).

## 4. Yapılandırma / varsayılanlar

| Değer | Kaynak satırı | Varsayılan / etki |
|---|---|---|
| Platform veri dosyası | `SocialMediaProvider.php:27` | `Resources/Data/social_media_platforms.json`; yoksa boş dizi |
| `all()` sıralaması | `SocialMediaProvider.php:53-55` | `order` alanına göre `uasort`; alan yoksa `999` |
| `getPlatform()` bilinmeyen anahtar | `SocialMediaProvider.php:42` | `$activeData = []` → `exists()` false, tüm erişici alanlar **sabit yedek** döner (`icon`→`bi-link-45deg`, `color`→`#6c757d`, `ri_icon`→`ri-link`, `fa_icon`→`fas fa-link`) |
| `TrafficStatsChannel` önbellek öneki | `TrafficStatsChannel.php:20` | `'analytics_traffic'` |
| `UserStatsChannel` önbellek öneki | `UserStatsChannel.php:18` | `'analytics_users'` |
| Kanal önbellek ömrü | `BaseChannel::$cacheTtl = null` → "short" (`Core/Base/Patterns/BaseChannel.php:27`) | `cache(?int $ttl)` ile değiştirilebilir; **bu iki kanal `cache()` çağırmaz** → hep varsayılan |
| Bot tespiti | `TrafficAnalysisTrait.php:193,206` · `WebtrafficController.php:125,129,249` | `location` alanı `'Bot'` önekiyle başlar |
| `computeSummary()` cihaz dağılımı | `TrafficAnalysisTrait.php:165,223` | `['PC'=>0,'Mobile'=>0,'Tablet'=>0]` |
| `top()` sınırı | `TrafficAnalysisTrait.php:267` | `5` |
| GA4 tarih penceresi (panel) | `WebtrafficController.php:321-324` | Son 30 gün |
| `saveColors()` yetki eşiği | `RbnAdminService.php:28` | `can('admin')` → `AuthRole::ADMIN_ROLES` içinde `admin` seviyesi |

## 5. Tuzaklar

1. **`TrafficAnalysisTrait` iki sınıfta `use` edilir → metotlar iki yerde de
   vardır.** `AnalyticsProvider` (`:20`) ve `TrafficStatsChannel` (`:16`) aynı trait'i
   kullanır. PHP bir trait'i aynı sınıfta iki kez `use` etseydi **fatal** verirdi;
   burada iki **farklı** sınıf olduğu için derlenir. Sonuç: `count()`,
   `get()`, `top()`, `hits()` gibi metotların **iki uygulaması** vardır ve ikisi de
   aynı gövdeyi taşır. Kod değiştirirken **her ikisi** değiştirilmelidir.

2. **`AnalyticsProvider::getDashboardStats()` ölü ve ayrıca yanlış.**
   (a) Hiçbir yerden çağrılmıyor (`grep getDashboardStats` → yalnız tanım ve
   `Internal/Syshub`'ın **kendi** benzer adlı metodu).
   (b) İçindeki `'today_hits' => $traffic->hits()` çağrısı
   `TrafficAnalysisTrait::hits()`'e düşer (`TrafficAnalysisTrait.php:316-319`),
   o da `count()`'a (`:123-132`) yani **tüm tarihlerin toplamına**. Kanal sınıfındaki
   `TrafficStatsChannel::hits()` ise **bugünün** toplamını döndürür (`:52-58`).
   **Aynı metot adı, aynı paket, iki farklı anlam.** Panel bugün kanalını
   (`WebtrafficController` ve `RbnAdminController` → `->traffic()->summary()`) kullandığı
   için belki hâlâ doğru çalışıyor; bu satırlara güvenilmemeli.

3. **`computeSummary()` özyineleme mührü zorunlu.** `static $isCalculating`
   (`:139-143`) aynı anda ikinci bir çağrı gelirse `[]` döner, yoksa sonsuz döngü.
   `finally` (`:236-238`) mührü **her** yolda kaldırır; bu blok silinirse kanal
   kalıcı olarak kilitlenir.

4. **`TrafficAnalysisTrait::get()` üç farklı dönüş şekli döndürür.**
   `forRange` → düz satır dizisi; `forDate` → o günün dizisi; `forTrend` →
   **`['YYYY-MM-DD' => int]` sayım haritası** (`:100-108`, erkenden `return` ile).
   Tüketici (`WebtrafficController::report()`, `:204`) yalnız ilk iki şekli
   bekleyecek şekilde yazılmıştır; `forTrend()` sonucunu `array_filter(fn($hit) =>
   isset($hit['time']))` içinden geçirirse **her şeyi düşürür**.

5. **`TrafficAnalysisTrait` durum tutucu — nesne yeniden kullanılamaz.**
   `query()` (`:34-43`) tüm durumu sıfırlar ama `$projectKey` **sıfırlanmaz**
   (`:19`); `withProject()` (`:24-29`) ayrıca `$this->storage->traffic()->projectKey`
   alanını da mutasyona uğratır — yani **paylaşılan depolama durumunu** değiştirir.
   Aynı istekte iki farklı proje için trafik sorgusu yapılırsa ilk `withProject()`
   etkisi kalıcıdır.

6. **`AnalyticsProvider::getUserStats()` modeli null'a karşı savunmasız.**
   `$UsersModel = $this->UsersModel` (`:45`) — bu alan `#[SubModule]`/sihirli
   özellik keşfiyle dolar; `BaseProvider` bunu garanti etmez. Model yoksa metot
   erken çıkar (`:47-53`), ama **dolu ama yanlış** modelde (`count()`/`select()`
   çağrılamayan) bir nesne sessizce hata verir.

7. **`getStatusDistribution()` bilinmeyen durumları yutar.** `$stats[$row['status']]`
   (`:74`) ile yazılır; `$stats` yalnız `active/inactive/banned/pending` ile
   başlatılmıştır (`:72`). Veritabanında beşinci bir durum varsa **dinamik alan**
   eklenir — şema ile uyumsuzluk sessizdir.

8. **`RbnAdminService::saveColors()` istisna atıyor, çağıran yakalamıyor.**
   `RbnAdminController::saveTheme()` (`:166-186`) `try/catch` **içermez** →
   yetkisiz istek 500 + ham hata sayfası döner. Ölçüm: `RbnAdminController` içinde
   `try`/`catch` yok.

9. **`SocialMediaProvider` durumlu (stateful) nesne.** `getPlatform()` (`:40-43`)
   `$this->activeData`'yı **üzerine yazar** ve `$this` döndürür. Aynı provider
   örneği üzerinde iki platform üst üste sorulursa ikinci çağrı birincinin
   bağlamını **siler** (`exists()` false olur). Bu yüzden RbnAdminService'deki
   `allSocialPlatforms()` (`RbnAdminService.php:53-56`) `$this->provider('social')
   ->all(...)` ile **dizi** dönerken, RbnStudio her platform için ayrı
   `getPlatform()` çağırır (`RbnStudioController.php:286,303`) — iki farklı
   kullanım deseni kasıtlıdır.

10. **`registerMap()` aynı anahtarı iki türe yazar.** `'analytics'` hem
    `services` hem `providers` altında (`:37,42`). Farklı türler ayrı defterlerde
    tutulduğu için çakışma değildir, ama `analytics` adı **iki anlam** taşır.

11. **Önbellek temizleme yolları atlanabiliyor.** Trafik özeti önbelleğinin
    (aynı projeye ait `api_cpanel_mail` önbelleği gibi) bir "temizle" metodu
    RbnAdmin içinde **yoktur**; `getFromCache` anahtarı saat/gün bazlı değil,
    durum karması bazlıdır. Trafik özeti "anlık" görünmesi gereken yerde
    (yeni hit) bayat kalabilir — kanıt: `AnalyticsService::record()` (`:40-44`)
    yazdıktan sonra **hiçbir yerde** önbellek düşürülmüyor.

12. **`AnalyticsService::getGoogleAnalyticsReports()` `$type` parametresi
    yalnız iki değer kabul ediyor.** `'map'` dışındaki her değer standart rapora
    düşer (`:56-59`); sessizdir.

13. **`RbnAdminService::getShareButtons()` çıktısı sırasız.** `$platforms` dizisi
    JSON dosyasındaki sırayla gelir (`:87` `->all()` — varsayılan **sıralı**),
    ama `socialPlatform($key)` → `provider('social')->getPlatform($key)` her
    turda **aynı provider örneğinin** durumunu değiştirir (tuzak 9).

## 6. Örnek (gerçek koddan)

Özet istatistiği önbellekten okumak (tüm panel ekranlarının kullandığı yol):

```php
// Bundles/RbnSuite/RbnAdmin/Providers/Fluent/TrafficStatsChannel.php:42-47
public function summary(): array
{
    return $this->getFromCache('summary', function () {
        return $this->computeSummary();
    });
}
```

ve platform erişiminin akıcı kullanımı:

```php
// Bundles/RbnSuite/RbnStudio/Controllers/RbnStudioController.php:286-293
$meta = $socialProvider ? $socialProvider->getPlatform('instagram') : null;
$platforms['instagram'] = [
    'name'  => $meta && $meta->exists() ? $meta->title() : 'Instagram',
    'icon'  => $meta && $meta->exists() ? $meta->riIcon() : 'ri-instagram-line',
    'color' => $meta && $meta->exists() ? $meta->color() : '#E4405F',
    'badge' => 'Business Media',
    'account_id' => $igAccountId
];
```

## 7. İlgili belgeler

* [RbnAdmin README](README.md) · [Controllers.md](Controllers.md)
* [Core/Base/Data](../../../Core/Base/Data.md) — `BaseProvider`, `BaseService`, `BaseChannel`
* [Core/System/Storage](../../../Core/System/Storage.md) — `storage->traffic()` sayaç dosyaları
* [Packages/RbnApi](../../../Packages/RbnApi.md) — `service('api')->google()`
