# Core/Base/Services — servis, sağlayıcı ve yönetici tabanları

> **Doğrulanan kod tabanı:** `1d89c431` (dal `feat/fw-license-master`) · **Tarih:** 2026-10-05 · **Yayın:** 0.9.5 = bu commit + sonrası; belge yalnız doğrulama anındaki kodu anlatır
> **Kaynak klasör:** `Core/Base/Services/` — 18 `*.php`.
> **Envanter:** 18 dosyanın 18'i aşağıda anlatıldı.

## 1. Ne işe yarar, kim kullanır

İş mantığının tabanları: `BaseService` (tekil orkestratör; bulunmayan metotları
bağlı provider'a devreder), `BaseProvider` (veri erişim sağlayıcısı),
`BaseManager` (kanonik yönlendirme gibi istek-öncesi yöneticiler),
`BasePrompt` (içerik üretim şablonları). Alt trait'ler CRUD/toplu/okuma
akışlarını servis ve provider katmanında yeniden kullanılabilir hale getirir.

**Kimler çağırır:** `Core/Services/*`, `Bundles/*/Services/*`,
`Packages/*/Services/*`; controller'lar `service('<alias>')` ile.

## 2. Dosya envanteri

### 2.1 Kök (4)

| Dosya | Görev | Önemli public yöntemler |
|---|---|---|
| `BaseService.php` | Servis kökü; singleton (`get()`), aktif controller kaydı, `boot()`, provider'a devir (`__call`). | `boot(): void`, `setActiveController(object): void`, `activeController(): ?object`, `__construct(?BaseService $rbn=null)`, `static get(): self`, `static forgetInstance(): void`, `__call(string $method,array $args)` |
| `BaseProvider.php` | Sağlayıcı kökü (`BaseProviderInterface`). | `__construct(?BaseService $rbn=null)` |
| `BaseManager.php` | Yönetici kökü; `afterBoot()` kancası. | `afterBoot(): void` (protected) |
| `BasePrompt.php` | İçerik üretim şablonları (şema, metin, görsel ayarları, ayar birleştirme). | `getSchema(array $context): array`, `getContentText(string $title, array $context): string`, `getContentTextSettings(array $context): array`, `getImageSettings(array $context): array`, `mergeSettings(array $base,array $custom): array` |

### 2.2 `Traits/Provider/` (1) + `Traits/Provider/Engine/` (3)

| Dosya | Görev | Önemli yöntemler |
|---|---|---|
| `Traits/Provider/ActionProviderTrait.php` | Sağlayıcı trait'lerini birleştirir. | — (trait bileşimi) |
| `Engine/CrudProviderTrait.php` | Sağlayıcı CRUD yüzeyi (id'li, eski imzalar dahil). | `save(array $data): bool\|int`, `create(array): int\|bool`, `update(int $id,array $data): bool`, `updateById(int $id,array $data): bool`, `destroy(int $id): bool`, `setStatusById(int $id,string $field,?bool $status=null): bool`, `toggleStatus(int $id,string $field='is_active'): bool`, `increment/decrement(int $id,string $column,int $amount=1)` |
| `Engine/BulkProviderTrait.php` | Sağlayıcı toplu yazma. | `destroyBulk(array $ids): int`, `toggleStatusBulk(array $ids,bool $status,string $field='is_active'): int`, `updateBulk(array $ids,array $data): int` |
| `Engine/ReadProviderTrait.php` | Sağlayıcı okuma + eager load. | `with($relations): self`, `find($id): ?array`, `all(): array` |

### 2.3 `Traits/Service/` (1) + `Blogcontent/` (4) + `Engine/` (5)

| Dosya | Görev | Önemli yöntemler |
|---|---|---|
| `Traits/Service/ActionServiceTrait.php` | Servis ortak durumu (`targetId`, `lastResult`, `criteria`) ve sonuç/önbellek temizleme. | `withId(int $id): self`, `success(): bool`, `clearCache($customKeys=null,?string $projectKey=null): self` |
| `Blogcontent/ContentServiceTrait.php` | İçerik CRUD + listeleme + önbellek. | `getPosts(array $options=[]): array`, `savePost(array $data)`, `destroyPost(?int $id=null): self`, `flushContentCache(): bool`, `deletePost(?int $id=null): bool`, `getPostBySlug(string,?string=null): ?array`, `getPostBySimilarSlug(string,?string=null): ?array`, `incrementViews(int $id): bool` |
| `Blogcontent/ContentDataServiceTrait.php` | Görünüm verisi üretimi (ana sayfa, liste, kategori, detay). | `getMappedPosts(?string $projectKey=null,array $options=[]): array`, `getCategory(int\|string $idOrSlug,?string=null): ?array`, `getListingData(?string=null,?string $search=null): array`, `getHomeData(?string=null,array $options=[]): array`, `getCategoryListingData(string $categorySlug,?string=null): array`, `getDetailData(string $categorySlug,string $postSlug,?string=null,?string $currentUrl=null,?string $prefix=null): array` |
| `Blogcontent/ContentMetaTrait.php` | Yazı içeriğine SEO/özet meta ekleme. | `enrichPost($post, array $contentFields=['content','author_comment'],?string $customUrl=null)` |
| `Blogcontent/ContentLegacyRedirectServiceTrait.php` | Eski içerik/kategori/etiket slug yönlendirmesi (WordPress kalıntıları). | `resolveLegacyPostRedirect(string $slug,?string $projectKey=null,string $prefix='/blog'): string`, `resolveLegacyCategoryRedirect(string $categorySlug,?string $projectKey=null,string $prefix='/blog'): string`, `resolveLegacyTagRedirect(string $tagSlug,string $prefix='/blog'): string`, `sanitizeLegacySlug(string): string` |
| `Engine/CrudServiceTrait.php` | Servis CRUD + durum değiştirme; zincir (fluent) API. | `create(array $data): self`, `update(array $data): self`, `updateById(int $id,array $data=[]): self`, `destroy(?int $id=null): self`, `setStatus(?int $id=null,$status=null,string $field='is_active'): self`, `toggleStatus(?int $id=null,$status=null,string $field='is_active'): self`, `save(mixed $id=null,array $data=[])`, `truncate(?string $modelName=null): self` |
| `Engine/BulkServiceTrait.php` | Toplu servis işlemleri. | `bulkDestroy(array $ids): self`, `bulkToggleStatus(array $ids,bool $status,string $field='is_active'): self`, `bulkUpdateOrder(array $order,string $field='order_num'): self`, `bulkValueUpdate(array $data): self` |
| `Engine/ReadServiceTrait.php` | Okuma servis yardımcıları (aktif/pasif, hiyerarşi). | `active(string $field='is_active',$value=1): self`, `passive(string $field='is_active',$value=0): self`, `find(int $id): ?object`, `all(): array`, `count(): int`, `parents(?int $excludeId=null): array`, `children(int $parentId): array`, `atCategory(int $categoryId): array` |
| `Engine/FileServiceTrait.php` | Geçici dosya/zip üretimi, güvenli yol çözümleme, depolama istatistiği. | `prepareTempFile(string $filename,string $content): string`, `prepareTempZip(string $filename,array $files): string`, `executeBulkDelete(string $directory,array $filenames): bool`, `calculateStorageStats(string $directory,string $pattern='*',bool $recursive=false): array`, `resolveSafePath(string $baseDir,string $identifier): string` |
| `Engine/FileTransferServiceTrait.php` | Dosya indirme/yükleme/dışa-içe aktarma (CSV). | `deliverFile(string $path,?string $displayName=null): void`, `deliverZip(array $files,?string $zipName=null): void`, `deliverRaw(string $content,string $filename): void`, `receiveUpload(string $inputName,string $targetPath,array $allowedExtensions=[]): string`, `exportData(array $items,array $headerMap,string $filename='export.csv',string $format='csv'): void`, `exportCsv(array $items,array $headerMap,string $filename): void`, `importCsv(string $filePath,callable $callback,string $delimiter=';'): void`, `contentDispositionHeader(string $filename): string` |

**Kapsama:** 18/18.

## 3. Akış

### 3.1 Servis → provider devri

```
$service->create($data)                 → CrudServiceTrait (varsa)
 ├─ yoksa __call()                      BaseService.php:105
 │    ├─ kısa ad: 'UserService' → 'User' → provider anahtarı 'UserProvider'  :108-110
 │    ├─ component('provider', 'UserProvider', false)  :114 (zorunlu DEĞİL)
 │    └─ varsa call_user_func_array  :117-119
 │         yoksa Exception "Sovereign Service Error"  :121
 └─ provider->create() → CrudProviderTrait
```

### 3.2 Servis kuruluşu

```
new PageService()
 ├─ BaseService::__construct()          BaseService.php:52
 │    ├─ self::$instance = $this (ilk örnek kazanır)  :55-57
 │    ├─ parent::__construct($rbn) → BaseComponent → bootConcernsContext()  :60
 │    └─ $this->boot()                  :63
```

## 4. Yapılandırma

| Öğe | Yer | Not |
|---|---|---|
| `toggleStatus()` 2. parametre uyumu | `CrudServiceTrait.php:46-74` | Eski imza (`$status` değer) ve yeni imza (`$field` adı) birlikte desteklenir |
| `bulkUpdateOrder` varsayılan alanı | `BulkServiceTrait.php:63` | `order_num` |
| `ContentCacheTrait` TTL | `../Data.md` §4 | 3600 sn |
| `importCsv` ayraç | `FileTransferServiceTrait.php:211` | `';'` (Excel TR varsayılanı) |

## 5. Tuzaklar ve kurallar

1. **Singleton tek statik slottur.** `self::$instance` `static::class` DEĞİLDİR;
   bu yüzden test izolasyonu için `forgetInstance()` vardır ve yalnız test/
   teardown çağırır (`BaseService.php:31, 89-103`).
2. **Provider zorunlu değildir.** `component('provider', $key, false)` — bulunamazsa
   teşhis mesajı üretilir, istisna fırlatılmaz (`BaseService.php:112-114`).
3. **Devir sırası kesindir.** `__call` yalnız metot **serviste yoksa** devreder.
4. **`CrudServiceTrait::update(array $data)` korunur.** Yeni giriş noktası
   `updateById()`'dir; `save()` yolu bilinçli olarak değiştirilmemiştir
   (`CrudServiceTrait.php:167-179`).
5. **Eski/kayıt ID tuzağı:** `targetId=0` + `id=42` → `update(42)` eskiden
   yanlış çalışıyordu; artık varlık `null` ayrımı kullanılır
   (`CrudServiceTrait.php:140-155`).
6. **Geçici dosyalar `E:\tmp\_gecici\`-benzer geçici alana yazılır**; görev
   kuralı gereği çalışma dizinine yazılmaz. `prepareTempFile()`/
   `prepareTempZip()` bu yüzden `sys_get_temp_dir()` tabanlıdır.
7. **`resolveSafePath()` yol geçişini kapatır** — taban dizin dışına çıkış
   reddedilir (`FileServiceTrait.php:135`).

## 6. Örnek (gerçek koddan)

```php
// Servis CRUD (akışkan) — CrudServiceTrait.php:126, 221
$service = $this->service('page');
$ok = $service->updateById($id, $data)->success();

// Toplu sıra güncelleme — BulkServiceTrait.php:63
$this->service('sidebarMenu')->bulkUpdateOrder([12 => 1, 13 => 2]);

// Eski WordPress yolu yönlendirmesi — ContentLegacyRedirectServiceTrait.php:19
$hedef = $this->resolveLegacyPostRedirect('yazi-slug', $projectKey, '/blog');
```

## 7. İlgili belgeler

* [../README.md](README.md) — `BaseComponent` tabanı ve özellik çözümleme
* [Web.md](Web.md) — controller'ın bu servisleri nasıl tükettiği
* [../Routes/README.md](../Routes/README.md) — `ContentLegacyRedirectServiceTrait`'in `RedirectManager` içindeki kullanımı
* [../../kavramlar/01-mimari-harita.md](../../kavramlar/01-mimari-harita.md)