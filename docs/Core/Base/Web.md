# Core/Base/Web — controller, render ve sayfalama tabanları

> **Doğrulanan kod tabanı:** `1d89c431` (dal `feat/fw-license-master`) · **Tarih:** 2026-10-05 · **Yayın:** 0.9.6 = bu commit + sonrası; belge yalnız doğrulama anındaki kodu anlatır
> **Kaynak klasör:** `Core/Base/Web/` — 8 `*.php`.
> **Envanter:** 8 dosyanın 8'i aşağıda anlatıldı.

## 1. Ne işe yarar, kim kullanır

Web katmanının tabanları: `BaseController` (servis/model enjeksiyonu + view
bağlamı + SEO), `BaseRender` (view/layout/asset harmonisi), `Paginator`
(sayfalama), ve dört controller trait'i (view, CRUD, toplu işlem, indirme).

**Kimler çağırır:** `Bundles/*/Controllers/*`, `Core/Render/Controllers/*`
ve proje modüllerinin controller'ları; hepsi `BaseController`'dan türer.

## 2. Dosya envanteri

| Dosya | Görev | Önemli public yöntemler |
|---|---|---|
| `BaseController.php` | Controller kökü; `SERVICE`/`MODEL` sabitlerinden otomatik enjeksiyon, view bağlamı, `noIndex()`. | `__construct()`, `bootControllerSymphony(): void`, `reportComponentResolutionFailure(string $tur,string $ad,\Throwable $e): void`, `noIndex(string $directives='noindex, nofollow, noarchive, nosnippet, noimageindex'): self` |
| `BaseRender.php` | Render harmonisi: view engine, resolver'lar, parça çözümleme. | `__construct(?BaseService $rbn=null)`, `bootHarmony(): array`, `prepare(string $type,?string $view,array $data): array`, `viewEngine(): ViewEngine`, `viewResolver(): ViewResolver`, `layoutResolver(): LayoutResolver`, `assetResolver(): AssetResolver`, `assetService(): AssetService`, `viewProvider(): ViewProvider`, `seoResolver(): SeoResolver`, `breadcrumbResolver(): BreadcrumbResolver`, `resolveFragment(string $viewPath,array $data): ?string`, `handleMissing(string $path): void` |
| `Traits/Paginator.php` | Sayfalama nesnesi; URL kalıpları ve bağlantı üretimi. | `__construct(?array $items,$controller=null)`, `static make(?array $items=null,?int $total=null,int $perPage=15,?string $url=null): self`, `perPage(int): self`, `baseUrl(string): self`, `to(string $key): self`, `items(): array`, `total(): int`, `currentPage(): int`, `hasPages(): bool`, `lastPage(): int`, `onFirstPage(): bool`, `hasMorePages(): bool`, `previousPageUrl()/nextPageUrl(): string`, `links(): string` |
| `Traits/Controller/ViewTrait.php` | Controller view bağlamı ve render. | `getActiveView(): ?View`, `set(string\|array $key,$value=null): self`, `import(string $path): self`, `get(string $key)`, `render(string $arg1,$arg2=[],$arg3=[]): View`, `paginate($items,int $perPage=10): Paginator` |
| `Traits/Controller/ActionControllerTrait.php` | CRUD sonuç yönetimi, mesajlar, modal, API yanıtı, dönüş yolu. | `getDetectedEntityName(): string`, `getCrudMessage(string $key,string $default): string`, `returnPath(mixed $subPath=null)`, `handleResult($result,?string $message=null,mixed $path=null,mixed $context=null,array $data=[])`, `alert(string $type,string $message,string\|array $redirectOrData=[]): void`, `modal($id=null,?string $view=null): void`, `apiSuccess(mixed $data=[],string $message='…',int $code=200,array $meta=[]): void`, `apiError(string $message='…',int $code=400,mixed $errors=null,array $meta=[]): void` |
| `Traits/Controller/Engine/CrudControllerTrait.php` | Oluştur/güncelle/sil/durum aksiyonları + beyaz liste bağlama. | `create(): void`, `update(): void`, `delete($id): void`, `status(): void`, `crudInput(array $data): array`, `crudTargetModel(): ?object`, `logCrudInputDrops(object $model,array $fields): void` |
| `Traits/Controller/Engine/BulkControllerTrait.php` | Toplu sil/durum/sıra/değer işlemleri. | `bulkDelete($target=null,string $method='bulkDestroy',$redirect=null): void`, `bulkStatus($target=null,?bool $status=null,string $method='bulkToggleStatus'): void`, `bulkOrder($target=null,string $method='bulkUpdateOrder'): void`, `bulkValueUpdate(): void`, `handleBulkAction($target,string $method,array $extraParams=[],array $messages=[]): void` |
| `Traits/Controller/Engine/DownloadControllerTrait.php` | Tekil/çoklu dosya indirme. | `download($id=null,$service=null,string $method='download'): void`, `bulkDownload($ids=null,$service=null,string $method='downloadBulk'): void`, `respondDownload(string $filename,string $message): void`, `respondMultiDownload(array $downloads,string $message): void` |

**Kapsama:** 8/8.

## 3. Akış

### 3.1 Controller kuruluşu ve istek

```
new PageController()
 └─ BaseController::__construct()                  BaseController.php:29
     ├─ parent::__construct() → BaseComponent → bootConcernsContext()  :32
     │    └─ bootBaseContext(): controller olduğu için setActiveController($this)
     │         ve extractIdentity()/hydrateComponents() çalışır
     └─ bootControllerSymphony()                   :35
          ├─ SERVICE sabiti/props → $this->service = $this->service($ad)  :45-54
          │    (çözülemezse null bırakılır + log — B-37)
          ├─ MODEL sabiti/props → $this->model = $this->model($ad)       :55-63
          └─ (kimlik keşfi artık BaseContextTrait'te)                     :65-66

Dispatcher::callController() → $controller->index()
 └─ ViewTrait::render('page.index', $data)
     ├─ BaseRender::prepare()/resolveFragment()      BaseRender.php:132, 209
     ├─ ViewEngine::render() + LayoutResolver       (bkz. ../Render)
     └─ $response null ise Dispatcher getActiveView() ile basar
        (../Routes/README.md §3.2)
```

### 3.2 CRUD + toplu işlem

```
$controller->update()
 ├─ CrudControllerTrait::update()                  CrudControllerTrait.php:40
 │    ├─ crudInput() → getFillableFields() beyaz listesiyle ham veriyi bağlar  :71-105
 │    │    (liste boşsa davranış DEĞİŞMEZ; düşen alanlar loglanır  :136-152)
 │    ├─ $this->activeService->update($data)
 │    └─ ActionControllerTrait::handleResult()  → alert + yönlendirme  ActionControllerTrait.php:156
```

## 4. Yapılandırma

| Öğe | Yer | Not |
|---|---|---|
| `noIndex()` varsayılanı | `BaseController.php:99` | `noindex, nofollow, noarchive, nosnippet, noimageindex` |
| `Paginator` varsayılan sayfa boyutu | `Paginator.php:15` | `perPage = 15` |
| `ViewTrait::paginate()` varsayılanı | `ViewTrait.php:110` | `perPage = 10` |
| `getCrudMessage()` anahtarları | `ActionControllerTrait.php:55-66` | `{entity}.created`, `.updated`, `.deleted` vb. |

## 5. Tuzaklar ve kurallar

1. **Servis/model çözülemezse `null` bırakılır ama log yazılır** (B-37).
   Davranış korunur, görünürlük eklenmiştir (`BaseController.php:44-93`).
2. **Controller, aktif controller olarak kaydedilir.** DNA (module/panel/
   sub_module) önce controller'dan gelir (`../README.md` §3).
3. **Beyaz listesi olmayan modelde CRUD verisi süzülmez.**
   `crudInput()` listeyi bağlar, liste boşsa elde değerler değişmez
   (`CrudControllerTrait.php:71-105`).
4. **`render()` dönüşü `null` ise Dispatcher `getActiveView()` ile basar**
   (`../Routes/README.md` §3.2).
5. **Paginator iki yapım biçimi vardır:** dizi alırsa `preSliced` çalışır,
   toplam + perPage alırsa `COUNT` uygulanır (`Paginator.php:15-20, 50`).
6. **View bağlamı otomatik dolar:** `appName`, `projectKey`, `projectGroup`,
   `appVersion` controller kuruluşunda view'a verilir
   (`../Concerns/BaseContextTrait.php:102-107`).

## 6. Örnek (gerçek koddan)

```php
// View + sayfalama (ViewTrait.php:72, 110)
return $this->render('admin.pages.index', ['rows' => $rows, 'total' => $total]);

// Toplu işlem (BulkControllerTrait.php:19)
$this->bulkDelete($ids, 'bulkDestroy', 'admin.pages');

// API yanıtı (ActionControllerTrait.php:308, 331)
$this->apiSuccess(['id' => $id], 'Kayıt oluşturuldu');
$this->apiError('Doğrulama hatası', 422, $errors);
```

## 7. İlgili belgeler

* [../README.md](README.md), [Services.md](Services.md)
* [../Routes/README.md](../Routes/README.md) — controller'ın çağrılma/dispatch akışı
* [../Http/README.md](../Http/README.md) — `alert()`/`response()` motorları
* `../Render/README.md` (bu görevde üretilmemiş, ayrı iş kalemi) — view engine, layout, SEO