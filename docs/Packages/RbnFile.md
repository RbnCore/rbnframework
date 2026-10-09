# Packages/RbnFile — dosya yükleme, doğrulama, görsel işleme, dışa/içe aktarma

> **Doğrulanan kod tabanı:** `d49b4413` (dal `feat/fw-license-master`) · **Tarih:** 2026-10-05 · **Yayın:** 0.9.6 = bu commit + sonrası; belge yalnız doğrulama anındaki kodu anlatır
> **Kaynak klasör:** `Packages/RbnFile/` — **8 `*.php`** (6 `Handlers/` + 2 `Services/`).
> **Envanter:** 8 dosyanın **8'i** aşağıda anlatıldı.

## 1. Ne işe yarar, kim kullanır

Form yüklemelerinin **tek giriş noktasıdır**: `FileService` (şef) altı
handler'ı (işçi) sırayla çağırır — doğrula, klasörü kur, yükle, WebP'ye
çevir, temizle. `ImageService` ise GD tabanlı görsel işlemlerini (boyut,
filtre, filigran, indirip filigranlama) sunar.

**Kimler çağırır:** controller'lar (`$this->service('file')->image(...)`),
`Packages/RbnPipeline` (`AutoTaskManager::generateAndSavePostImage()`),
form view'ları. Kayıt: `PackageData.php:21-22, 50-55`.

## 2. Klasör/dosya envanteri (8/8)

### 2.1 `Services/` (2)

| Dosya | Görev | Önemli public yöntemler |
|---|---|---|
| `Services/FileService.php` | Ana dosya orkestratörü; `boot()` altı handler'ı bağlar (`:26-34`). | `boot()`, `image(string $inputName, string $folder, string $disk = 'secure', ?array $options = [])` (`:39`), `file(string $inputName, string $folder, ?array $options = [])`, `multiple(...)`, `delete(string $path, ?array $options = [])`, `export()`, `import()` |
| `Services/ImageService.php` | Görsel işlem servisi; `boot()` ile `fileImage` handler'ına bağlanır. | `boot()`, `resize(...)`, `transform(string $imagePath, array $transforms)`, `watermark(...)`, `downloadAndWatermark(string $imageUrl, string $folder, string $watermarkText, ?array $options = [])`, `base64Image(string $base64, string $folder, string $disk = 'public', ?array $options = [])` |

### 2.2 `Handlers/` (6)

| Dosya | Görev | Önemli public yöntemler |
|---|---|---|
| `Handlers/FileValidatorHandler.php` | Güvenlik nöbetçisi: upload geçerliliği, boyut, uzantı, MIME, tehdit taraması, görsel ölçüleri. | `validate(UploadedFile $file, array $options = []): array` (`:24`), `getInfo(UploadedFile $file)`, `isImage(string $extension)` (`:208`), `formatSize(int $bytes)` |
| `Handlers/FileUploadHandler.php` | Depolama nöbetçisi: `secure` / `public` / `cdn` disk hedeflerine yazar. | `__construct()`, `execute(UploadedFile $file, string $folder, string $disk = 'secure', ?array $options = []): array` (`:38`), `delete(string $imagePath, ?array $options = [])` |
| `Handlers/FileUtilityHandler.php` | Dizin oluşturma, boş dizin temizliği, içerik→dosya. | `__construct()`, `createDirectory(string $folder)`, `cleanEmptyFolders(string $folderPath, string $relativeFolderPath)`, `contentToFile(string $content, string $extension = 'png')` |
| `Handlers/FileImageHandler.php` | GD ile boyutlandırma, filtre, filigran, WebP dönüşümü. | `resize(string $imagePath, int $width, int $height, bool $maintainAspect = true, ?string $suffix = null)` (`:21`), `transform(...)` (`:77`), `watermark(string $imagePath, string $text, ?array $options = []): bool` (`:162`), `convertToWebp(string $imagePath, int $quality = 85, bool $deleteOriginal = true): array` (`:370`); private: `createImage()` `:434`, `saveImage()` `:445` |
| `Handlers/FileExportHandler.php` | JSON/CSV dışa aktarma. | `toJson(string $filename, array $data, int $options = JSON_PRETTY_PRINT\|JSON_UNESCAPED_UNICODE)`, `toCsv(string $filename, array $data, string $delimiter = ',')` |
| `Handlers/FileImportHandler.php` | Uzak dosya/URL içe aktarma. | `fromUrl(string $url)` |

**Kapsama:** 8/8.

## 3. Akış

### 3.1 Resim yükleme (form yolu)

```
$file->service('file')->image('kapak', 'blog', 'public', ['preset' => 'blog'])
 └─ FileService::image()                                  FileService.php:39
     ├─ is_direct=true ise sunucu tarafı dosya UploadedFile'a sarılır   :44-51
     │      (AI üretimi gibi istekten gelmeyen dosyalar)
     ├─ yoksa $this->request->file($inputName)  → FileTrait   :53
     ├─ 1) FileValidations::getPreset($options['preset']) → $options birleşimi  :60-65
     ├─ fileValidator->validate($file, $options)           :67
     │    ├─ isValid()                                     FileValidatorHandler.php:36
     │    ├─ boyut: max_size yoksa 10 MB                    :41
     │    ├─ uzantı (allowed_types)                        :48
     │    ├─ MIME (MimeValidations)                        :54
     │    ├─ tehdit taraması (ThreatValidations)            :60
     │    └─ görsel ise ölçü kontrolü                       :66
     ├─ 2) fileUtility->createDirectory($folder)            :72
     ├─ 3) fileUpload->execute($file, $folder, $disk, $opts) :75
     │    ├─ disk 'cdn' veya options['cdn_path'] → CDN yolu  FileUploadHandler.php:42
     │    ├─ disk 'public' → Paths::publicRoot()            :55, 27
     │    └─ varsayılan 'secure' → Paths::project()->uploads()  :24
     └─ 3.5) görsel ise ve convert_webp (varsayılan true)    :80-81
          ├─ webp_quality yoksa 85                          :84
          ├─ cdn_path verilmişse fiziksel yol bu kökten hesaplanır  :87-89
          └─ FileImageHandler::convertToWebp()              FileImageHandler.php:370
```

### 3.2 Görsel işlem zinciri

```
ImageService::resize/downloadAndWatermark(...)
 └─ FileImageHandler
     ├─ resize()      → GD ile yeni boyut, isteğe bağlı oran koruma  :21
     ├─ transform()   → filtre/efekt listesi                          :77
     ├─ watermark()   → metin filigranı (logo PNG'si de kullanılabilir, :236) :162
     └─ convertToWebp() → imagewebp() ve isteğe bağlı orijinal silme  :370, :409
```

## 4. Yapılandırma / ayar anahtarları

| Seçenek / anahtar | Yer | Varsayılan / not |
|---|---|---|
| `$disk` | `FileUploadHandler.php:38` | `'secure'` (proje `uploads/`). Diğer değerler: `'public'`, `'cdn'` |
| `$options['max_size']` | `FileValidatorHandler.php:41` | `10 * 1024 * 1024` (10 MB) |
| `$options['allowed_types']` | `:48` | Boşsa uzantı kısıtı uygulanmaz; pratikte preset ile gelir |
| `$options['preset']` | `FileService.php:60`, `FileValidatorHandler.php:28-31` | `FileValidations::getPreset()` → kurallar `$options` ile birleştirilir (elle verilen anahtar önceliklidir) |
| `$options['convert_webp']` | `FileService.php:81` | `true` — görseller varsayılan olarak WebP'ye çevrilir |
| `$options['webp_quality']` | `FileService.php:84` | `85` |
| `$options['cdn_path']` | `FileService.php:87`, `FileUploadHandler.php:42` | Verilirse yazma hedefi CDN köküne kayar |
| `convertToWebp` kalite | `FileImageHandler.php:370` | `85`; `deleteOriginal` varsayılan `true` |

## 5. Tuzaklar ve kurallar

1. **Preset, elle verilen seçeneği EZMEZ.** `array_merge($presetRules, $options)`
   sırası seçenekleri sona koyar (`FileValidatorHandler.php:31`) —
   çağıranın verdiği değer kazanır.
2. **Varsayılan disk `secure`'tir**, yani `public/` altına yazılmaz; görünür
   olması gereken dosyalar için `'public'` **açıkça** geçilmelidir
   (`FileUploadHandler.php:24, 55`).
3. **WebP dönüşümü varsayılan olarak AÇIKTIR** (`convert_webp` yoksa `true`).
   `deleteOriginal=true` olduğu için orijinal dosya **silinir**; boyut/Disk
   hesabı buna göre yapılmalıdır (`FileService.php:81`,
   `FileImageHandler.php:370`).
4. **Sunucu tarafı dosya yolu `is_direct` ile açılır** ve `mime_content_type()`
   ile tiplenir (`FileService.php:44-51`); bu yol gelen kullanıcı girdisi
   değildir, `request` atlanır.
5. **`clearEmptyFolders()` yalnızca göreli yol alır**
   (`$folderPath`, `$relativeFolderPath`) — silme işlemi yanlış köke
   yazılmaması için iki argüman ister (`FileUtilityHandler`).
6. **Görsel işlemler GD'ye bağlıdır** (`imagecreatefromjpeg/png/webp/gif`,
   `imagewebp` — `FileImageHandler.php:437-450`); GD olmayan sunucuda bu
   handler sessizce değil, çağıran hata olarak görür.
7. **İki katmanlı dosya güvenliği vardır:** `Core/Http/Security/Handlers/FileSecurityHandler.php`
   (Http katmanı, istek düzeyi) ve bu paketin `FileValidatorHandler`'ı
   (dosya düzeyi). Preset/MIME/tehdit denetimleri ikincisindedir.

## 6. Örnek (gerçek koddan)

```php
// Form yüklemesi (görsel)
$sonuc = $this->service('file')->image('kapak', 'blog', 'public', [
    'preset'  => 'blog',
    'max_size' => 5 * 1024 * 1024,
]);

// Görsel işlem
$this->service('image')->resize($yol, 1200, 630);
$this->service('image')->watermark($yol, '© site.example');

// Dışa/içe aktarma
$this->service('file')->export()->toCsv('rapor', $satirlar);
$this->service('file')->import()->fromUrl('https://site.example/gorsel.png');
```

## 7. İlgili belgeler

* [PackageData.md](PackageData.md) — `services.file/image`, `handlers.file*` kayıtları
* [../Core/Http/Engine.md](../Core/Http/Engine.md) — `UploadedFile` sınıfı
* [../Core/Http/Security.md](../Core/Http/Security.md) — `FileSecurityHandler` (Http katmanı denetimi)
* [../Core/Support/Blueprints.md](../Core/Support/Blueprints.md) — `FileValidations` preset'leri, `MimeValidations`, `ThreatValidations`
* [RbnPipeline.md](RbnPipeline.md) — görsel üretimi sonrası kaydetme
* [../Resources/README.md](../Resources/README.md) — `Assets/` ve `Data/`
