# Bundles/Internal/Webhub — Frontend kimlik, SEO, navigasyon, entegrasyon ve yasal sayfalar

> **Doğrulanan kod tabanı:** `d49b4413` (dal `feat/fw-license-master`) · **Tarih:** 2026-10-05 · **Yayın:** 0.9.6 = bu commit + sonrası; belge yalnız doğrulama anındaki kodu anlatır
> **Kaynak klasör:** `Bundles/Internal/Webhub/` — 43 `*.php` + 19 `*.rbn.php` görünüm.
> **Envanter:** 43 php dosyasının 43'ü bu belgelerde anlatıldı.

## 1. Ne işe yarar, kimler kullanır

Webhub, **ziyaretçinin gördüğü siteyi** yöneten modüldür: marka kimliği
(firma adı, slogan, iletişim), SEO ayarları ve SEO puanlama motoru, frontend
menüleri (navbar/footer), yasal sayfalar (gizlilik/çerez/KVKK), SSS ve
harici entegrasyonlar (Analytics, AdSense, head/body/footer script).

**Kimler çağrır:** geliştirici paneli (`WebhubController` üzerinde
`#[Module(panel:'developer', context:'panel')]`, `WebhubController.php:8-16`).
Modülün verisi **proje kapsamlıdır**: `z_settings` (`project.settings`) ve
`z_frontend_menus`, `z_pages`, `z_faqs` tabloları.

## 2. Alt dallar ve belgeleri

| Alt dal | `*.php` | Belge | Kısa görev |
|---|---:|---|---|
| `Controllers/` | 7 | (bu belge §2.1) | Kök + 6 alt modül kontrolörü |
| `Models/` | 3 | (bu belge §2.2) | `ModuleData`, `WebhubMap`, `SeoConfig` |
| `Services/` | 6 | (bu belge §2.3) | 6 servis, ikisi `SettingsService` türevi |
| `Providers/` | 3 | (bu belge §2.4) | Ayar kayıt köprüsü, frontend menü, sayfa/kural |
| `Handlers/` | 5 | [Seo.md](Seo.md) | SEO tarama pipeline'ı (4 tarayıcı + 1 danışman) |
| `Views/` | 19 | (aşağıda listeli) | Panel arayüzü |
| **Toplam** | **43** | | `find Bundles/Internal/Webhub -name '*.php' \| wc -l` = 43 |

### 2.1 `Controllers/` (7)

| Dosya | Görev | Önemli public yöntemler |
|---|---|---|
| `WebhubController.php` | Modül kökü; `#[Module(name:'webhub', data:ModuleData::class, service:'webhub', panel:'developer', context:'panel', icon:'bi-globe2')]`. `filterSettings()` yardımcısı tüm alt modüllerin ortak daraltıcısıdır. | `index()` (:19), `filterSettings(array $settings,array $allowedKeys): array` (:27 *protected*) |
| `IdentityController.php` | `#[SubModule(entity:'identity', entityName:'setting', bulkInputKey:'settings')]`. Firma kimliği: **değer ekranı** (`index`, grup `company` + `active()`) ve **yapı ekranı** (`manage`, tümü). Yeni alan `group_id => 1` ile yazılır. | `index(): void` (:24), `manage(): void` (:40), `create(): void` (:55), `update(): void` (:75) |
| `SeoController.php` | `#[SubModule(entity:'seo', entityName:'setting', bulkInputKey:'settings')]`. SEO alanları + **puanlama ekranları** (`score`, `report`, `scan`). Yeni alan `group_id => 2`. | `index()` (:25), `score()` (:41), `report()` (:55), `scan()` (:72), `manage()` (:89), `create()` (:104), `update()` (:123) |
| `IntegrationsController.php` | `#[SubModule(entity:'integrations', …)]`. Script/etiket yönetimi ve AdSense kurulumu. Yeni alan `group_id => 10`. | `index()` (:25), `manage()` (:44), `save()` (:62), `setupAdsense()` (:90), `adsense()` (:96) |
| `NavigationController.php` | `#[SubModule(entity:'navigation', service:'frontendMenu')]`. Frontend menü ağacı. | `index(): void` (:22), `getModalData($id): array` (:35 *protected*), `save(): void` (:52) |
| `PolicyController.php` | `#[SubModule(entity:'policy', service:'policy', provider:'policy')]`. Yasal sayfalar + slug benzersizliği. | `index(): void` (:23), `getModalData($id): array` (:33 *protected*), `save(): void` (:48) |
| `FaqController.php` | `#[SubModule(entity:'faq', service:'faq', provider:'faq')]`. SSS kayıtları; doğrulama kuralları burada. | `index()` (:25), `rules(): array` (:40 *protected*), `save(): void` (:53) |

### 2.2 `Models/` (3)

| Dosya | Görev | Önemli public yöntemler |
|---|---|---|
| `ModuleData.php` | `#[Bundle(name:'webhub', context:'developer', map:WebhubMap::MAP)]`. 6 servis + 4 handler + 3 provider alias'ı; 7 rota grubu (kimlik, seo, navigasyon, policy, faq, entegrasyon, dashboard). | `registerMap(): array` (:27), `registerRoutes(): void` (:56) |
| `WebhubMap.php` | Menü haritası: 1 kimlik + 6 alt modül, 3'ünde alt alt modül (`seo.score`, `seo.report`, `integrations.manage`, `integrations.adsense`). `integrations` dalında `required_role: 'developer'` var. | `const MAP` (:18) |
| `SeoConfig.php` | Puan → renk/ikon/metin eşlemesi (4 kademe). Görünüm tek yerden renk alır. | `getScoreDisplay(int $score): array` (:17) |

### 2.3 `Services/` (6)

| Dosya | Görev | Önemli public yöntemler |
|---|---|---|
| `WebhubService.php` | Ayar odaklı **akıcı** servis. `boot()` içinde `provider('webhub')`'a bağlanır (:30); `read()/withKey()` ölçüt biriktirir, `save()/action()` çağırıp ölçütü **sıfırlar** (:69,:81). | `boot(): void` (:26), `read(?string $groupKey=null): self` (:38), `withKey(string $key): self` (:50), `save(mixed $id=null,array $data=[]): self` (:60), `action(?string $name=null,array $payload=[]): self` (:77) |
| `SeoScannerService.php` | Tarama pipeline yürütücüsü: 3 handler'ı sırayla çalıştırır, `seo.advice` handler'ıyla tavsiye üretir. `getReport()` kayıtlı `seo-report`/`seo-score` ayarlarını view-ready diziye çevirir. | `scan(): array` (:18), `getReport(): array` (:55) |
| `FrontendMenuService.php` | Menü servisi; `getList()` **aktif menüyü hesaplar** (`is_current`/`active`). | `parents(?int $excludeId=null): array` (:20), `getList(?int $parentId=null,bool $onlyActive=true): array` (:28), `getTree(): array` (:48), `getNested(int $parentId=0): array` (:56) |
| `PolicyService.php` | Sayfa servisi; `create()/update()` slug türetir ve benzersizlik kontrolü yapar, çakışmada **`Exception` fırlatır**. | `create(array $data): self` (:19), `update(array $data): self` (:37), `isSlugUnique(string $slug,?int $excludeId=null): bool` (:52), `getPages(): array` (:61), `getTemplates(): array` (:67), `findRecord(int $id,string $entity='page'): ?array` (:72) |
| `IntegrationsService.php` | `SettingsService` türevi. `setupAdsense()` `SettingsConfig::DEFAULT_SETTINGS` içindeki `adsense_*`/`ads_*` anahtarlarını projeye tohumlar (yoksa ekler). | `setupAdsense(): bool` (:22) |
| `FaqService.php` | **22 satır, tek gövdesiz.** Yalnız `protected $targetModel='project.faq'` ve `protected array $cacheKeys=['faq']` beyan eder; tüm CRUD `BaseService`'ten gelir. | — |

### 2.4 `Providers/` (3)

| Dosya | Görev | Önemli public yöntemler |
|---|---|---|
| `WebhubProvider.php` | Ayar kayıt köprüsü. `save()` anahtarı yoksa `label_en → label_tr → setting_name` sırasıyla slug üretir; `value` alanını `setting_value`'ya eşler; `field_options` boşsa `'[]'` yapar; sonra `settings_group_` önekli önbelleği düşürür. `actionScan()` SEO sonuçlarını dört ayara yazar. | `save(array $data): bool\|int` (:30), `purgeCache(): void` (:95 *private*), `actionScan(string $name,array $payload): mixed` (:108) |
| `FrontendMenuProvider.php` | Menü sorgu/onarım uzmanı. `getTree()` iki kipte çalışır: **düz + girinti** (`indent_level`) ve **iç içe** (`children`). `getParents()` düzenleme dalını ve tüm alt dalını listeden **çıkarır** (döngüsel hiyerarşi koruması). `destroy()` alt menü varsa silmeyi reddeder. | `getFrontendMenus(?int $parentId=null,bool $onlyActive=true,bool $onlyParents=false): array` (:17), `getTree(int $parentId=0,int $depth=0,?array $elements=null,bool $nested=false): array` (:44), `save(array $data): bool\|int` (:78), `getShowInOptions(): array` (:89), `destroy(int $id): bool` (:102), `getParents($excludeId=null): array` (:116), `findDescendants(int,array,array&): void` (:146 *private*) |
| `PolicyProvider.php` | Sayfa sağlayıcısı. `save()` `beforeSave()` kancasını tetikler; kanca `entity` alanını siler ve `show_in_footer`'ı checkbox → 0/1 yapar. `getTemplates()` dört sabit şablon döndürür. `toggleStatus()` `active` ⇄ `draft` çevirir. | `afterBoot(): void` (:21 *protected*), `save(array $data): bool\|int` (:30), `getTemplates(): array` (:41), `getPages(): array` (:53), `beforeSave(array &$data): void` (:67 *protected*), `toggleStatus(int $id,string $field='status'): bool` (:82) |

### 2.5 `Views/` (19)

| Görünüm | Sağlayan uç | Görev |
|---|---|---|
| `webhubdash.rbn.php` | `WebhubController::index` | Modül ana ekranı |
| `Identity/identity.rbn.php` | `IdentityController::index` | Firma kimliği değer kartları |
| `Identity/manage.rbn.php` | `IdentityController::manage` | Alan listesi + sıralama |
| `Identity/Partials/modal.rbn.php` | `IdentityController::modal` | Alan ekle/düzenle; `label_en` yazılınca anahtar slug'lanır (:92-95) |
| `Seo/index.rbn.php` | `SeoController::index` | Aktif SEO alanları |
| `Seo/manage.rbn.php` | `SeoController::manage` | SEO alan şeması |
| `Seo/score.rbn.php` | `SeoController::score` | Tek sayı + renk/ikon (`SeoConfig::getScoreDisplay`) |
| `Seo/report.rbn.php` | `SeoController::report` | Kriter listesi + kural tabanlı tavsiye + Gemini metni |
| `Seo/Partials/modal.rbn.php` | `SeoController::modal` | Alan ekle/düzenle |
| `Navigation/index.rbn.php` | `NavigationController::index` | Menü listesi |
| `Navigation/Partials/modal.rbn.php` | `NavigationController::modal` | Üst menü seçimi + `show_in` seçenekleri |
| `Policy/index.rbn.php` | `PolicyController::index` | Yasal sayfa listesi |
| `Policy/Partials/modal_policy.rbn.php` | `PolicyController::modal` | Başlıktan slug türetir, `policy_slug_v3` alanına yazar (:69) |
| `Faq/index.rbn.php` | `FaqController::index` | SSS listesi |
| `Faq/Partials/modal.rbn.php` | `FaqController::modal` | Soru/cevap düzenleyici |
| `Integrations/index.rbn.php` | `IntegrationsController::index` | 5 genel script ayarı |
| `Integrations/manage.rbn.php` | `IntegrationsController::manage` | Entegrasyon alan şeması |
| `Integrations/adsense.rbn.php` | `IntegrationsController::adsense` | 8 AdSense anahtarı (durum + slot) |
| `Integrations/Partials/modal.rbn.php` | `IntegrationsController::modal` | Entegrasyon alanı ekle/düzenle |

**Kapsama:** 43/43.

## 3. Akış — SEO taraması (modülün en ağır yolu)

```
POST webhub/seo/scan
  → SeoController::scan()                              SeoController.php:72
      → $this->service('seoScanner')                   :75
          → SeoScannerService::scan()                  :18
              ├─ base_url = https? + $_SERVER['HTTP_HOST']       :20-22
              ├─ executePipeline(['seo.settings','seo.core','seo.dom'], $params)  :30-34
              │    → her handler execute() → [score, report]; skorlar TOPLANIR  :105-111
              │      · SettingsScannerHandler  : execute()   (25 puan)
              │      · CoreScannerHandler      : execute()   (30 puan)
              │      · DomScannerHandler       : execute()   (45 puan)
              │    → score = min(100, Σ)                            :114
              └─ handler('seo.advice')->execute([score, report])   :37-41
                   → [advice (kural tabanlı), gemini (AI)]         AdviceHandler.php:18
      → provider('webhub')->actionScan('save_seo_scan', $result)  :78
          → dört ayara yazılır: seo-score, seo-report, seo-advice,
            seo-gemini-advice                                      WebhubProvider.php:111-115
          → purgeCache() (settings_group_*)                       :117
      → handleResult(true, …, 'seo/report')                       :82
```

Ayar kayıt akışı (Kimlik/SEO/Entegrasyon ortak):

```
POST webhub/seo/save
  → SeoController::update()                          SeoController.php:123
      → request->form([id, label_tr, setting_key])    :125-130
      → $this->service->action()->withId($id)->save($data)   :131
          ├─ criteria['setting_key'] = …              WebhubService.php:56
          ├─ provider->save(array_merge($data,$criteria))      :68
          │    ├─ anahtar yoksa: label_en → label_tr → setting_name slug'ı  :36-39
          │    ├─ 'value' → 'setting_value' eşlemesi  :45-49
          │    ├─ field_options boşsa '[]'            :52-56
          │    ├─ var mı? update(id) : create()       :65-83
          │    └─ purgeCache()                        :86
          └─ criteria = []  (akıcı döngü sıfırlama)  :69
```

## 4. Yapılandırma / ayar anahtarları

| Öğe | Yer | Değer | Not |
|---|---|---|---|
| `WebhubService::$cacheKeys` | `WebhubService.php:21` | `['webhub_settings']` | Bildiriliyor; yazma sonrası düşürülen önek `settings_group_`'dir (`WebhubProvider.php:98`) — **farklı anahtarlar** |
| `FaqService::$targetModel` / `$cacheKeys` | `FaqService.php:20-21` | `project.faq` / `['faq']` | Kayıtlı alias (`SystemPhysicalMapTrait.php:54`) |
| `FrontendMenuService::$targetModel` | `FrontendMenuService.php:15` | `FrontendMenus` | Registry'de `frontendMenu` alias'ı var; bu ad **beyan** olarak kullanılır |
| `PolicyService::$targetModel` | `PolicyService.php:14` | `Pages` | `PolicyProvider::afterBoot()` de aynı değeri yeniden atar (:23) |
| `IntegrationsService::$targetModel` | `IntegrationsService.php:16` | `settings` | `SettingsService` türevi; anahtar önekli |
| SEO tarama puan tavanı | `SeoScannerService.php:114` | `min(100, Σ)` | Üç handler toplamı 25+30+45 = **100** tam puan |
| `seo-score` / `seo-report` / `seo-advice` / `seo-gemini-advice` | `z_settings` | — | `actionScan()` yazar, `getReport()` okur (`WebhubProvider.php:112-115`, `SeoScannerService.php:60-68`) |
| `show_in` seçenekleri | `FrontendMenuProvider::getShowInOptions()` | `0` üst+alt · `1` yalnız alt · `2` alt sütun başlığı · `3` gizli | Sabit dizi, kodda değil (`FrontendMenuProvider.php:89-97`) |
| Sayfa şablonları | `PolicyProvider::getTemplates()` | `default`, `full-width`, `sidebar`, `landing` | Sabit dizi (:41-48) |
| `adsense` tohumlama | `IntegrationsService::setupAdsense()` | grup id varsayılanı `5` | Grup bulunamazsa `5` kullanılır (:29-33) |
| Alan grupları (sabit id) | `IdentityController:66` `1` · `SeoController:113` `2` · `EmailController:51` `3` · `IntegrationsService:29` `5` · `IntegrationsController:82` `10` | — | **Grup id'leri koda gömülü**; grup silinip yeniden oluşturulursa yanlış gruba yazar |

## 5. Tuzaklar ve kurallar (kodda görülen, ölçülmüş)

1. **`group_id` sabitleri kırılgandır.** `create()` çağrıları `group_id => 1/2/10` yazar
   (`IdentityController.php:66`, `SeoController.php:113`, `IntegrationsController.php:82`).
   Grup id'leri veritabanında yeniden üretilirse yeni alan yanlış gruba düşer.
2. **Kontrollü `Exception` fırlatma.** `PolicyService::create()/update()` slug çakışmasında
   `throw new \Exception('Bu slug zaten kullanılıyor.')` (:27,:43) — `PolicyController::save()`
   bunu **yakalamaz**, `handleResult` yalnız boolean bekler. Yakalanmayan hata 500'e gider.
3. **`request->rawAll()` iki denetleyicide.** `NavigationController::save()` ve
   `PolicyController::save()` kural dizisi tanımlamak yerine ham girdi kullanır ve bunu
   **bilinçli** yapar (yorum: `FW-ALTYAPI-2 / B`, `NavigationController.php:54-56`). Beyaz liste
   `CrudControllerTrait::crudInput()` yolunda çalışır, bu yol trait'i kullanmıyor.
   Her iki controller da `unset($data['project'])` ile proje anahtarını düşürür
   (:60 / :55) — yazma kapsamı modelin beyanına bırakılır.
4. **`IntegrationsController::save()` `WebhubProvider::save()`'ı hiç çağırmaz.** Servis
   `IntegrationsService extends SettingsService` → `CrudServiceTrait`; `create()/update()`
   ikisi de `save()`'a düşer (`CrudServiceTrait.php:128,140`) ve `save()` **önce**
   `component('model')` dener (`:399-405`) — `SettingsModel` çözüldüğü için
   `provider->save()` dalına (`:420-426`) hiç girilmez. Sonuç: `WebhubProvider`'ın üç
   koruması — slug türetme (`:36-39`), `field_options` JSON koruması (`:52-56`) ve
   `settings_group_` önbellek düşürme (`:95-101`) — **bu yolda çalışmaz**. Aynı desen
   `IdentityController::create/update` ve `SeoController::create/update` için de geçerlidir
   (`withId()->save()` → `WebhubService::save()` → `provider->save()`, orada çalışır);
   fark, `WebhubService`'in `$this->provider`'ı `boot()`'ta **bağladığı** için (`:30`)
   provider dalına düşmesidir.
5. **`getTree()` iki kip, iki çağrı.** `getNested()` iç içe (`$nested=true`),
   `getTree()` düz+girinti (`indent_level`). `getParents()` yalnız **düz** kipi okur (:118) —
   iç içe ağaçta `indent_level` olmadığı için seçenekler girintisiz görünürdü.
6. **Menü silme guard'ı sessiz başarısızlık.** `destroy()` alt menü varsa `false` döner
   (`FrontendMenuProvider.php:105-107`); `delete/{id}` rotası bu false'u kullanıcıya
   "silinemedi" diye ayırt etmeden gösterir. `Backstage`'deki karşılığı da aynı desende
   (`SidebarHandler::deleteCategory`).
7. **Ölçüt sıfırlama akıcı döngüyü kısa ömürlü kılar.** `WebhubService::save()` ve `action()`
   çalıştıktan sonra `$this->criteria = []` yapar (:69,:81). Aynı servis örneği üzerinde
   ikinci bir `save()` **önceki** `withKey/withGroup` filtresini kaybetmiş olur.
8. **`FaqController::index()` dönüş tipi tutarsız.** Diğerleri `void`/`render` çağırırken
   bu bir `return $this->render(...)` yapar (:32-34) ve `$this->paginate($faqs)->to('faqs')`
   zincirlemesi kullanır — `to()` çağrısının dönüş değeri atanır ama `faqs` olarak da geçirilir.
9. **SEO taraması kendi sunucusunu HTTP ile yoklar.** `CoreScannerHandler` ve `DomScannerHandler`
   `base_url`'e `remote->get()` atar (`CoreScannerHandler.php:46`, `DomScannerHandler.php:23`).
   `$_SERVER['HTTP_HOST']` doğrudan kullanıldığı için tarama **panelin erişildiği** host'u
   tarar; `Host` başlığı değiştirilmiş bir istek yanlış siteyi ölçebilir.
   SSL doğrulaması **kapalıdır** (`CURLOPT_SSL_VERIFYPEER => false`, :50-51 / :23).
10. **`scan()` puanı zaman içinde kaydedilen bir sayıdır.** `getReport()` veritabanındaki
    `seo-report` JSON'unu sayar; tarama yapılmamışsa `seo-score` `0` gelir ve
    `SeoConfig::getScoreDisplay(0)` "Taranmadı" döner (`SeoConfig.php:19-25`) —
    yani `score=0` "kritik" değil "ölçülmedi" anlamına gelir.
11. **AdSense tohumlama `project_key`'i elle yazar.** `setupAdsense()` `save()` çağrısında
    `'project_key' => $projectKey` alanını açıkça koyar (`IntegrationsService.php:51`);
    diğer yollarda bu alan bilinçli olarak yazılmıyor (kapsam modelde).
12. **`FaqService` boş bir sınıftır.** 22 satır, yalnız iki `protected` beyan; herhangi bir
    metot yoktur (`FaqService.php:17-22`). Tüm davranış `BaseService` + `project.faq`
    repository'sinden gelir.

## 6. Örnek (gerçek koddan)

```php
// Bundles/Internal/Webhub/Providers/FrontendMenuProvider.php:116-140 (özetlenmiş)
public function getParents($excludeId = null): array
{
    $tree    = $this->getTree();
    $options = [];

    // Düzenleme yapılıyorsa: kendisi + tüm alt dalı listeden çıkar (döngüsel koruması)
    $forbiddenIds = [];
    if ($excludeId) {
        $forbiddenIds[] = (int) $excludeId;
        $this->findDescendants((int) $excludeId, $tree, $forbiddenIds);
    }

    foreach ($tree as $item) {
        if (in_array((int) $item['id'], $forbiddenIds)) {
            continue;
        }
        $prefix = $item['indent_level'] > 0 ? str_repeat(' ', $item['indent_level'] * 2) . '↳ ' : '';
        $options[] = ['id' => $item['id'], 'title' => $prefix . $item['title']];
    }

    return $options;
}
```

## 7. İlgili belgeler

* [Seo.md](Seo.md) — tarama pipeline'ının dört handler'ı ve puanlama kuralları
* [Core/Base/README.md](../../../Core/Base/README.md) — `BaseService` akıcı sözleşmesi
* [Core/Base/Data.md](../../../Core/Base/Data.md) — `BaseModel` kapsam (`scoped`) davranışı
* [kavramlar/02-yapilandirma.md](../../../kavramlar/02-yapilandirma.md) — `z_settings` şeması
* [kavramlar/05-asset-sistemi.md](../../../kavramlar/05-asset-sistemi.md)
* [acik-sorular.md](../../../acik-sorular.md)