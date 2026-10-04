<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnFile\Services;

use Rbn\Framework\Core\Base\Services\BaseService;
use Rbn\Framework\Core\Base\Attributes\Component;

/**
 * ImageService - Görsel İşleme ve Manipülasyon Servisi 🎨⚓
 * RBN 3.5 Masterpiece Standard.
 */
#[Component(alias: 'image', type: 'service')]
class ImageService extends BaseService
{
    /** @var \Rbn\Framework\Packages\RbnFile\Handlers\FileImageHandler */
    protected $fileImage;

    /**
     * Servis ilk çalıştırma hazırlığı 🏺
     */
    public function boot(): void
    {
        $this->fileImage = $this->handler('fileImage');
    }

    /**
     * ✅ Görseli yeniden boyutlandırır (Oran koruma ve sonek desteğiyle)
     */
    public function resize(string $imagePath, int $width, int $height, bool $maintainAspect = true, ?string $suffix = null): array
    {
        return $this->fileImage->resize($imagePath, $width, $height, $maintainAspect, $suffix);
    }

    /**
     * ✅ Görsele filtre ve dönüşümler uygular (crop, fit, stretch vb.)
     */
    public function transform(string $imagePath, array $transforms): array
    {
        return $this->fileImage->transform($imagePath, $transforms);
    }

    /**
     * ✅ Görsele filigran (watermark) ekler
     */
    public function watermark(string $imagePath, string $text, ?array $options = []): bool
    {
        return $this->fileImage->watermark($imagePath, $text, $options);
    }

    /**
     * ✅ Belirtilen görsel URL'sini indirir, watermark ekler ve yerleşik resim yükleme sistemine ($this->image) delege eder.
     */
    public function downloadAndWatermark(string $imageUrl, string $folder, string $watermarkText, ?array $options = []): array
    {
        try {
            $fileService = $this->service('file');
            
            // 1. Resmi geçici dosyaya indir (FileService import)
            $tempPath = $fileService->import()->fromUrl($imageUrl);
            if (!$tempPath) {
                return ['success' => false, 'message' => 'Görsel indirilemedi.'];
            }

            // 2. Resmi filigranla
            if (!$this->watermark($tempPath, $watermarkText, $options)) {
                @unlink($tempPath);
                return ['success' => false, 'message' => 'Filigran eklenemedi.'];
            }

            // 3. Mevcut resim yükleme akışını tetikle
            $options = $options ?? [];
            $options['is_direct'] = true;
            $options['filename'] = basename($tempPath);
            
            $disk = $options['disk'] ?? 'public';
            $uploadResult = $fileService->image($tempPath, $folder, $disk, $options);

            // 4. Temizlik
            @unlink($tempPath);

            return $uploadResult;

        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'downloadAndWatermark Hatası: ' . $e->getMessage()];
        }
    }

    /**
     * ✅ Base64 içeriğinden otonom resim mühürleme ve kaydetme 🎨
     */
    public function base64Image(string $base64, string $folder, string $disk = 'public', ?array $options = []): array
    {
        try {
            $fileService = $this->service('file');
            $fileUtility = $this->handler('fileUtility');

            // 1. Ham veriyi geçici dosyaya dönüştür 🛠️
            $tempPath = $fileUtility->contentToFile($base64, $options['extension'] ?? 'png');
            if (!$tempPath) {
                return ['success' => false, 'message' => 'Base64 veri işlenemedi.'];
            }

            // 2. Mevcut yükleme akışını tetikle 🚀
            $options = $options ?? [];
            $options['is_direct'] = true;
            $uploadResult = $fileService->image($tempPath, $folder, $disk, $options);

            // 3. Temizlik 🧹
            @unlink($tempPath);

            return $uploadResult;

        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'base64Image Hatası: ' . $e->getMessage()];
        }
    }
}
