# Core/System/Registries — Sistem kayıt defteri (ad → sınıf haritası)

> **Doğrulanan kod tabanı:** `d508f5e1` (dal `feat/fw-license-master`) · **Tarih:** 2026-10-05 · **Yayın:** 0.9.4 = bu commit + sonrası; belge yalnız doğrulama anındaki kodu anlatır
> **Kaynak klasör:** `Core/System/Registries/` — 8 `*.php` (kök 3, `RegistryMap/` 5).
> **Envanter:** 8 dosyanın 8'i aşağıda anlatıldı.

## 1. Ne işe yarar, kim kullanır

Çatının "adı verilen şey hangi sınıf?" tablosudur. `service('module')`, `model('master.projects')`, `helper('format')` gibi çağrılar önce bu haritaya bakar (`NamespaceResolver::find()` → `SystemRegistry::locate()`); harita bulamazsa klasör taramasına düşülür ([Discovery §3.1](Discovery.md)). Ayrıca takma ad (`class_alias`) ve CLI komut tablosunu taşır.

Harita beş **özellik (trait)** dosyasına bölünmüştür; `SystemRegistry::registerMap()` onları, proje bileşen haritasını ve modül/paket haritalarını `array_merge_recursive` ile birleştirir.

**Kimler çağırır:** `NamespaceResolver` (adı çözmek), `ComponentRegistry` aşaması (takma adlar), `Routing` aşaması (`sovereignBundles()`), `ModuleDiscoveryDriver` (`sovereignBundles()`), `RbnSystemInfo`, CLI (`commands`).

## 2. Klasör/dosya envanteri

| Dosya | Görev | Önemli public yöntemler |
|---|---|---|
| `SystemRegistry.php` | Ana kayıt: beş trait + `DiscoveryConfigTrait` (statik `locate()`); birleşik haritayı proje anahtarıyla önbellekler. | `static sovereignBundles(): array`, `registerMap(): array`, `static clearCache()`, `static locate(string $name, ?string $type = null): ?string` (trait'ten) |
| `AppProjectRegistry.php` | Proje tarafı kayıt: keşfedilen modülleri (proje ayarındaki `bundles` listesiyle süzülmüş) ve `ComponentTypes::PLURAL_MAP` bileşenlerini haritaya ekler. | `bundles(): array`, `registerMap(): array` |
| `RbnSystemInfo.php` | `RbnSystemInfo::get('ADMIN_NAME')` gibi marka/kimlik sabitlerini bulur. | `static get(string $key)` |
| `RegistryMap/SystemAccessMapTrait.php` | `aliases` (4) ve `commands` (6). | `protected accessMap(): array` |
| `RegistryMap/SystemLogicMapTrait.php` | `managers` (5), `services` (27), `handlers` (24), `resolvers` (2), `jobs` (3). | `protected logicMap(): array` |
| `RegistryMap/SystemPhysicalMapTrait.php` | `models` (36), `providers` (6), `repositories` (23). | `protected physicalMap(): array` |
| `RegistryMap/SystemRenderMapTrait.php` | Render katmanı: `services` (4), `handlers` (10), `providers` (10), `presets` (1), `resolvers` (10), `clusters` (1), `aliases` (panel/auth/crawler takma adları). | `protected renderMap(): array` |
| `RegistryMap/SystemResourceMapTrait.php` | `helpers` (12), `validations` (6), `constants` (boş), `metadata` (3 önek). | `protected resourceMap(): array` |

## 3. Akış

### 3.1 `SystemRegistry::registerMap()` (`SystemRegistry.php:58-101`)

1. Proje anahtarı: `Bootstrap::getAppContext('project_key')` → `active_project_key()` → `'default'`; bu anahtarla `$mergedMapCache` varsa döner.
2. `array_merge_recursive`: `accessMap()`, `logicMap()`, `physicalMap()`, `AppProjectRegistry::registerMap()`, `resourceMap()`, `renderMap()` (`:67-74`).
3. `BaseService::get()->service('module')` varsa `registerBundles('map', sovereignBundles())` ve `registerBundles('map')` sonuçları birleştirilir (`:78-85`).
4. Aktif denetleyicinin `moduleData` özelliği (sınıf adı ya da nesne) `registerMap()` taşıyorsa o da eklenir (`:89-97`).
5. Sonuç proje anahtarına göre önbelleğe alınır.

### 3.2 Ad bulma (`locate`, `DiscoveryConfigTrait.php:30-52,97-148`)

`locateRegistry($ad,$tür)` dört "kapıyı" sırayla dener: `System` (`SystemRegistry`), `Project` (`AppProjectRegistry`), `Sovereign` (`sovereignBundles()` sınıfları), `Module` (yine `SystemRegistry`); her kapıda `registerMap()[<tür çoğulu>]` içinde `searchDeep()` ile anahtar (büyük/küçük harf duyarsız) ya da, dizin anahtarı sayısalsa sınıf dosya adı eşleşmesi aranır. Hiçbiri bulamazsa `DiscoveryEngine::namespace()->find()` (klasör taraması) çağrılır. `$isLocating` özyineleme koruması etkinse doğrudan taramaya gidilir.

### 3.3 Takma adlar (`ComponentRegistry` aşaması)

`accessMap()['aliases']`: `BaseService` → `BaseService`, `Service`; `Config` → `Config`; `Paths` → `Paths`; `RbnSystemInfo` → `RbnSysInfo`. `ComponentRegistry::registerAliases()` her biri için `class_alias()` yapar (özgün sınıf yoksa ya da takma ad zaten varsa atlar ve `LogThrottle` ile bir kez günlüğe yazar).

### 3.4 `RbnSystemInfo::get($key)` (`RbnSystemInfo.php:24-80`)

Anahtar büyük harfe çevrilir; önce `FrameworkIdentity` sabiti (`FrameworkIdentity::<ANAHTAR>`); yoksa `metadata` haritasındaki önek (`ADMIN_`, `KIT_`, `SEO_`) eşleşirse önek atılıp kalan, hedef sınıfta `BaseConfig::data()` ya da sınıf sabiti olarak aranır. Bulunamazsa `null`; her sonuç (null dahil) işlem içinde önbelleklenir.

## 4. Yapılandırma / içerik

* `sovereignBundles()` (`SystemRegistry.php:40-51`): 7 `ModuleData` sınıfı — `Packages\PackageData`, `Bundles\RbnSuite\{RbnAdmin,RbnAuth,RbnStudio}\Models\ModuleData`, `Bundles\Internal\{Backstage,Syshub,Webhub}\Models\ModuleData`. Hepsi sınıf olarak mevcuttur.
* `commands`: `db`, `make`, `migrate`, `system`, `version`, `tenant` → `Core\Services\Console\Handlers\*Handlers`.
* `AppProjectRegistry::bundles()` projenin `bundles` yönlendirme ayarını (`getRouteConfig($projectKey,'bundles')`) okur; liste doluysa **yalnız** o adlı modüller (küçük harf eşleşme) döner, boşsa keşfedilenlerin hepsi.
* Haritadaki sınıf adları `Rbn\Framework\` önekiz yazılır (`Core\Services\…`); `NamespaceExpansionTrait::expandRegistryResult()` öneki tamamlar.

## 5. Tuzaklar ve kurallar (ölçülmüş)

PHP 8.3 ile beş trait'in `accessMap/logicMap/physicalMap/resourceMap/renderMap` çıktısındaki 193 sınıf adı `class_exists` ile denendi (Windows):

| Sonuç | Sayı |
|---|---|
| Sınıfı bulunan | 188 |
| **Sınıfı bulunamayan** | **5** |

Bulunamayanlar (haritada var, kodda yok):

| Kayıt | Hedef | Durum |
|---|---|---|
| `logic.handlers['storage']` | `Core\System\Storage\Handlers\StorageHandler` | `Core/System/Storage/` altında `Handlers/` klasörü yok |
| `logic.handlers['localization']` | `Core\System\Localization\Handlers\LocalizationHandler` | `Core/System/` altında `Localization/` klasörü yok |
| `logic.handlers['seo']` | `Core\Render\Handlers\Seo\SeoHandler` | Render altında `Handlers/Seo/` yok |
| `resource.metadata['KIT_']` | `Core\Render\Config\AssetConfig` | gerçek ad alanı `Core\Render\Configs\AssetConfig` (çoğul `Configs`) |
| `resource.metadata['SEO_']` | `Core\Render\Config\SeoConfig` | `Core\Render\Config\` ad alanı yok |

Sonuçları: `handler('storage'|'localization'|'seo')` bugün `locate()`'ten sınıf döndürmez, klasör taramasına düşer ve bulamazsa `null` (zorunluysa tanılama) verir; `RbnSystemInfo::get('KIT_…')`/`('SEO_…')` her zaman `null` döner çünkü hedef sınıf yoktur (`class_exists` doğrulaması, `RbnSystemInfo.php:60`). Bu girdilerin silinmesi ya da doğru adlara çevrilmesi bir **kod kararı**dır (bu belge salt okunur).

Diğer kurallar:

1. **Çözüm sırası "kapı" sırasıdır:** `System` → `Project` → `Sovereign` → `Module`; aynı ad birden çok haritada varsa ilk bulunan kazanır. `array_merge_recursive` aynı **dize** anahtarı iki kez görürse değerleri **diziye çevirir** (`[a, b]`); `checkRegistry()` bu durumda **son** elemanı alır (`end($found)`, `DiscoveryConfigTrait.php:111`) — sessiz "son kayıt kazanır" davranışı.
2. **Harita proje başına önbelleklenir** (`$mergedMapCache`); yeni modül eklenince `SystemRegistry::clearCache()` gerekir (yoksa istek boyunca eski harita kalır).
3. **`models`/`repositories` anahtarları noktalıdır** (`master.projects`, `common.ipBlock`, `project.user`, `app.rss.source`); `searchDeep` anahtarı küçük harfe indirerek eşler, ama noktalı adı bölmez.
4. **Birden çok ad aynı sınıfı gösterebilir** (örn. `ipBlock` ve `master.ipBlock` → `MasterIpBlocksModel`; `shieldSetting` ve `common.shieldSetting` → `CmSysSettingsShieldModel`).
5. **`MasterRbnHeartbeatsModel` (`master.rbnHeartbeat`) için haritanın dışında çağıran bulunamadı** (`Core/Bundles/Packages/Resources` taraması, `rbnHeartbeat`/`rbn_heartbeat` geçişleri yalnız model dosyalarında ve bu haritada). Model `rbn_heartbeats` tablosunu bildirir; `MasterDbData::REQUIRED_TABLES` ise `z_sys_heartbeats` ister; hangi tablonun gerçek master'da bulunduğu salt okunur ölçümle doğrulanamadı (bkz. [acik-sorular](../../acik-sorular.md)).

## 6. Örnek (gerçek koddan)

```php
// Core/System/Registries/RegistryMap/SystemAccessMapTrait.php:26-31
'aliases' => [
    BaseService::class   => ['BaseService', 'Service'],
    Config::class        => ['Config'],
    Paths::class         => ['Paths'],
    RbnSystemInfo::class => ['RbnSysInfo'],
],
```

```php
// Core/System/Registries/SystemRegistry.php:40-43 (ilk iki girdi)
public static function sovereignBundles(): array
{
    return [ \Rbn\Framework\Packages\PackageData::class,
             \Rbn\Framework\Bundles\RbnSuite\RbnAdmin\Models\ModuleData::class, /* … */ ];
}
```

## 7. İlgili belgeler

* [Core/System genel bakış](README.md) · [Discovery](Discovery.md) · [Kernel](Kernel.md) (`ComponentRegistry`, `Routing`)
* [Support/Definitions](../Support/Definitions.md) (`ComponentTypes`, `FrameworkIdentity`)
* [Açık sorular](../../acik-sorular.md)
