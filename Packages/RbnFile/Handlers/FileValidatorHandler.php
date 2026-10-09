<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnFile\Handlers;

use Rbn\Framework\Core\Base\BaseComponent;
use Rbn\Framework\Core\Http\Engine\UploadedFile;
use Rbn\Framework\Core\Support\Blueprints\Validations\MimeValidations;
use Rbn\Framework\Core\Support\Blueprints\Validations\ThreatValidations;
use Rbn\Framework\Core\Support\Blueprints\Validations\FileValidations;

/**
 * FileValidatorHandler - The Security Guard (Worker) 🧪🛡️
 * 
 * RBN Framework: Standard (BaseComponent Actor).
 * Unified with FileValidations DNA.
 */
class FileValidatorHandler extends BaseComponent
{
    /**
     * Kapsamlı dosya validasyonu (DNA & Preset Support 🧪🛡️)
     */
    public function validate(UploadedFile $file, array $options = []): array
    {
        try {
            // 🎼 RBN Framework: Preset Lookup (DNA Integration)
            if (isset($options['preset'])) {
                $presetRules = FileValidations::getPreset($options['preset']);
                if ($presetRules) {
                    $options = array_merge($presetRules, $options);
                }
            }

            // 1. Temel upload kontrolü
            if (!$file->isValid()) {
                return $this->sendError(FileValidations::REQUIRED);
            }

            // 2. Boyut kontrolü
            $maxSize = $options['max_size'] ?? 10 * 1024 * 1024; // Default 10MB
            if ($file->size('B') > $maxSize) {
                $maxSizeMB = round($maxSize / 1024 / 1024, 2);
                return $this->sendError(str_replace('{max}', (string) $maxSizeMB, FileValidations::MAX_SIZE));
            }

            // 3. Uzantı kontrolü
            $extensionValidation = $this->validateExtension($file, $options['allowed_types'] ?? []);
            if (!$extensionValidation['success']) {
                return $extensionValidation;
            }

            // 4. MIME type kontrolü
            $mimeValidation = $this->validateMime($file);
            if (!$mimeValidation['success']) {
                return $mimeValidation;
            }

            // 5. Güvenlik kontrolü
            $securityValidation = $this->validateSecurity($file);
            if (!$securityValidation['success']) {
                return $securityValidation;
            }

            // 6. Resim özel kontrolleri
            if ($this->isImage($file->extension())) {
                $imageValidation = $this->validateImageDimensions($file, $options);
                if (!$imageValidation['success']) {
                    return $imageValidation;
                }
            }

            return $this->sendSuccess('Validasyon başarılı', [
                'file_info' => $this->getInfo($file)
            ]);

        } catch (\Exception $e) {
            return $this->sendError('Validasyon hatası: ' . $e->getMessage());
        }
    }

    /**
     * Dosya uzantısı kontrolü
     */
    private function validateExtension(UploadedFile $file, array $allowedTypes = []): array
    {
        $extension = $file->extension();

        if (ThreatValidations::isForbiddenExtension($extension)) {
            return $this->sendError(FileValidations::SECURITY_THREAT);
        }

        if (!empty($allowedTypes) && !in_array($extension, $allowedTypes)) {
            return $this->sendError(str_replace('{ext}', $extension, FileValidations::INVALID_TYPE));
        }

        return $this->sendSuccess('Uzantı kontrolü başarılı');
    }

    /**
     * MIME type kontrolü
     */
    private function validateMime(UploadedFile $file): array
    {
        $detectedMimeType = $file->mime();
        $extension = $file->extension();

        if (!MimeValidations::isValidMime($extension, $detectedMimeType)) {
            // 🛡️ RBN Framework: Resim dosyaları için esneklik (Farklı resim türleri arası geçişe izin ver)
            $isImageExt = $this->isImage($extension);
            $isImageMime = str_starts_with($detectedMimeType, 'image/');

            if (!($isImageExt && $isImageMime)) {
                return $this->sendError(FileValidations::MIME_MISMATCH);
            }
        }

        return $this->sendSuccess('MIME type kontrolü başarılı');
    }

    /**
     * Güvenlik kontrolleri
     */
    private function validateSecurity(UploadedFile $file): array
    {
        $filePath = $file->tmpName();
        $fileContent = file_get_contents($filePath, false, null, 0, 1024);

        if (ThreatValidations::containsSuspiciousPattern($fileContent)) {
            return $this->sendError(FileValidations::SECURITY_THREAT);
        }

        $fileName = $file->name();
        if (substr_count($fileName, '.') > 1) {
            $parts = explode('.', $fileName);
            if (count($parts) > 2) {
                $secondLastExtension = strtolower($parts[count($parts) - 2]);
                if (ThreatValidations::isForbiddenExtension($secondLastExtension)) {
                    return $this->sendError('Güvenlik: Çift uzantı tespit edildi');
                }
            }
        }

        return $this->sendSuccess('Güvenlik kontrolü başarılı');
    }

    /**
     * Resim özel kontrolleri
     */
    private function validateImageDimensions(UploadedFile $file, array $options = []): array
    {
        $filePath = $file->tmpName();
        $imageInfo = getimagesize($filePath);

        if (!$imageInfo) {
            return $this->sendError(FileValidations::IMAGE_INVALID);
        }

        $width = $imageInfo[0];
        $height = $imageInfo[1];

        $minWidth = $options['min_width'] ?? 0;
        $minHeight = $options['min_height'] ?? 0;

        if ($width < $minWidth || $height < $minHeight) {
            $msg = str_replace(['{width}', '{height}'], [(string) $minWidth, (string) $minHeight], FileValidations::MIN_DIMENSIONS);
            return $this->sendError($msg);
        }

        $maxWidth = $options['max_width'] ?? 10000;
        $maxHeight = $options['max_height'] ?? 10000;

        if ($width > $maxWidth || $height > $maxHeight) {
            $msg = str_replace(['{width}', '{height}'], [(string) $maxWidth, (string) $maxHeight], FileValidations::MAX_DIMENSIONS);
            return $this->sendError($msg);
        }

        return $this->sendSuccess('Resim kontrolü başarılı');
    }

    /**
     * Dosya bilgilerini al
     */
    public function getInfo(UploadedFile $file): array
    {
        $info = [
            'name' => $file->name(),
            'size' => $file->size('B'),
            'size_formatted' => $this->formatSize((int) $file->size('B')),
            'extension' => $file->extension(),
            'mime_type' => $file->mime(),
            'type' => $this->getFileType($file->extension())
        ];

        if ($this->isImage($file->extension())) {
            $imageInfo = getimagesize($file->tmpName());
            if ($imageInfo) {
                $info['dimensions'] = ['width' => $imageInfo[0], 'height' => $imageInfo[1]];
            }
        }

        return $info;
    }

    /**
     * Dosyanın resim olup olmadığını kontrol et
     */
    public function isImage(string $extension): bool
    {
        $imageExtensions = MimeValidations::getExtensionsByCategory('image');
        return in_array(strtolower($extension), $imageExtensions);
    }

    /**
     * Dosya türünü belirle
     */
    private function getFileType(string $extension): string
    {
        foreach (MimeValidations::CATEGORIES as $type => $extensions) {
            if (in_array(strtolower($extension), $extensions)) {
                return $type;
            }
        }
        return 'unknown';
    }

    /**
     * Dosya boyutunu formatla
     */
    public function formatSize(int $bytes): string
    {
        if ($bytes == 0)
            return '0 B';
        $units = ['B', 'KB', 'MB', 'GB'];
        $factor = floor(log($bytes, 1024));
        return sprintf("%.2f %s", $bytes / pow(1024, $factor), $units[$factor]);
    }
}
