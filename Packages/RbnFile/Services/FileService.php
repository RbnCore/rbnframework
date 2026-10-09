<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnFile\Services;

use Rbn\Framework\Core\Base\Services\BaseService;
/**
 * FileService - The Master File Orchestrator (Chef) 🛸🛰️👨‍🍳
 * 
 * RBN Framework: Unified Gateway for all file operations.
 * This service orchestrates specialized handlers (Workers).
 * 
 * @property \Rbn\Framework\Packages\RbnFile\Handlers\FileUploadHandler $fileUpload
 * @property \Rbn\Framework\Packages\RbnFile\Handlers\FileValidatorHandler $fileValidator
 * @property \Rbn\Framework\Packages\RbnFile\Handlers\FileUtilityHandler $fileUtility
 * @property \Rbn\Framework\Packages\RbnFile\Handlers\FileExportHandler $fileExport
 * @property \Rbn\Framework\Packages\RbnFile\Handlers\FileImportHandler $fileImport
 * @property \Rbn\Framework\Packages\RbnFile\Handlers\FileImageHandler $fileImage
 */
class FileService extends BaseService
{
    /**
     * Boot the File Service DNA ⚓
     */
    public function boot(): void
    {
        $this->fileUpload = $this->handler('fileUpload');
        $this->fileValidator = $this->handler('fileValidator');
        $this->fileUtility = $this->handler('fileUtility');
        $this->fileExport = $this->handler('fileExport');
        $this->fileImport = $this->handler('fileImport');
        $this->fileImage = $this->handler('fileImage');
    }

    /**
     * ✅ Resim yükleme ve orkestrasyon 📸
     */
    public function image(string $inputName, string $folder, string $disk = 'secure', ?array $options = []): array
    {
        try {
            // 🎼 RBN Framework: [DIRECT FILE SUPPORT] 🛰️⚓
            // Eğer dosya doğrudan bir yol (path) olarak gelirse (AI üretimi vb.), request'i pas geç.
            if ($options['is_direct'] ?? false) {
                $file = new \Rbn\Framework\Core\Http\Engine\UploadedFile([
                    'name' => $options['filename'] ?? basename($inputName),
                    'type' => mime_content_type($inputName),
                    'tmp_name' => $inputName,
                    'error' => 0,
                    'size' => filesize($inputName)
                ], true);
            } else {
                $file = $this->request->file($inputName);
            }

            if (!$file)
                return $this->sendError(\Rbn\Framework\Core\Support\Blueprints\Validations\FileValidations::REQUIRED);

            // 1. Validasyon & Preset DNA Lookup 🧬
            if (isset($options['preset'])) {
                $preset = \Rbn\Framework\Core\Support\Blueprints\Validations\FileValidations::getPreset($options['preset']);
                if ($preset) {
                    $options = array_merge($preset, $options);
                }
            }

            $vResult = $this->fileValidator->validate($file, $options);
            if (!$vResult['success'])
                return $vResult;

            // 2. Klasör Hazırlığı
            $this->fileUtility->createDirectory($folder);

            // 3. Ana Yükleme (Worker: Upload) 🚚
            $uploadResult = $this->fileUpload->execute($file, $folder, $disk, $options);
            if (!$uploadResult['success'])
                return $uploadResult;

            // 3.5 Auto-WebP Conversion for Performance & Disk Optimization 📸
            $isImage = str_starts_with((string)$file->mime(), 'image/');
            if ($isImage && ($options['convert_webp'] ?? true)) {
                $uploadedPath = $uploadResult['data']['path'] ?? $uploadResult['path'] ?? null;
                if ($uploadedPath) {
                    $quality = $options['webp_quality'] ?? 85;
                    
                    // If cdn_path option is used, compute the physical path of the uploaded file
                    if (!empty($options['cdn_path'])) {
                        $cdnPath = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $options['cdn_path']), DIRECTORY_SEPARATOR);
                        $filename = basename($uploadedPath);
                        $physicalPath = $cdnPath . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, trim($folder, '/\\')) . DIRECTORY_SEPARATOR . $filename;
                        $webpResult = $this->fileImage->convertToWebp($physicalPath, $quality, true);
                    } else {
                        $webpResult = $this->fileImage->convertToWebp($uploadedPath, $quality, true);
                    }

                    if ($webpResult['success']) {
                        $webpFilename = basename($webpResult['data']['path'] ?? $webpResult['path'] ?? '');
                        
                        // For custom cdn/shared disks, calculate relative path properly using the original relative path prefix
                        $originalDir = dirname($uploadedPath);
                        $newPath = ($originalDir === '.' || $originalDir === '/' || $originalDir === '\\') ? '/' . $webpFilename : rtrim(str_replace('\\', '/', $originalDir), '/') . '/' . $webpFilename;
                        
                        if (isset($uploadResult['data'])) {
                            $uploadResult['data']['path'] = $newPath;
                            $uploadResult['data']['filename'] = $webpFilename;
                        } else {
                            $uploadResult['path'] = $newPath;
                            $uploadResult['filename'] = $webpFilename;
                        }
                    }
                }
            }

            // 4. Otonom Varyasyon Üretimi (Derivatives) 🖼️🛰️
            $versions = $options['versions'] ?? [];
            $versionResults = [];

            if (!empty($versions)) {
                $mainPath = $uploadResult['data']['path'] ?? $uploadResult['path'] ?? null;
                if ($mainPath) {
                    foreach ($versions as $vName => $dims) {
                        $rResult = $this->fileImage->resize($mainPath, $dims[0], $dims[1], true, (string) $vName);
                        if ($rResult['success']) {
                            $versionResults[$vName] = $rResult['data']['path'] ?? $rResult['path'] ?? '';
                        }
                    }
                }
            }

            // Sonuçları konsolide et
            if (!empty($versionResults)) {
                if (isset($uploadResult['data'])) {
                    $uploadResult['data']['versions'] = $versionResults;
                } else {
                    $uploadResult['versions'] = $versionResults;
                }
            }

            // 5. Opsiyonel Transform (crop/fit/stretch/resize) ✂️🖼️
            if (!empty($options['transform'])) {
                $uploadedPath = $uploadResult['path'] ?? null;
                if ($uploadedPath) {
                    $this->fileImage->transform($uploadedPath, $options['transform']);
                    // transform() overwrite modunda aynı path'a yazıyor, path değişmez
                }
            }

            return $uploadResult;

        } catch (\Exception $e) {
            return $this->sendError('FileService Image Error: ' . $e->getMessage());
        }
    }

    /**
     * ✅ Genel dosya yükleme 📂
     */
    public function file(string $inputName, string $folder, ?array $options = []): array
    {
        try {
            $file = $this->request->file($inputName);
            if (!$file)
                return $this->sendError('Dosya seçilmedi.');

            // 1. Validasyon
            $vResult = $this->fileValidator->validate($file, $options);
            if (!$vResult['success'])
                return $vResult;

            // 2. Klasör Hazırlığı
            $this->fileUtility->createDirectory($folder);

            // 3. Yükleme
            return $this->fileUpload->execute($file, $folder, 'secure', $options);

        } catch (\Exception $e) {
            return $this->sendError('FileService File Error: ' . $e->getMessage());
        }
    }

    /**
     * ✅ Çoklu dosya yükleme 📂📂
     */
    public function multiple(string $inputName, string $folder, ?array $options = []): array
    {
        try {
            $files = $this->request->files($inputName);
            if (empty($files))
                return $this->sendError('Hiç dosya seçilmedi.');

            $this->fileUtility->createDirectory($folder);

            $results = [];
            $successCount = 0;

            foreach ($files as $file) {
                $vResult = $this->fileValidator->validate($file, $options);
                if ($vResult['success']) {
                    $uResult = $this->fileUpload->execute($file, $folder, 'secure', $options);
                    if ($uResult['success']) {
                        $results[] = $uResult;
                        $successCount++;
                    }
                }
            }

            return [
                'success' => $successCount > 0,
                'message' => "{$successCount} dosya başarıyla işlendi.",
                'data' => $results
            ];

        } catch (\Exception $e) {
            return $this->sendError('FileService Multiple Error: ' . $e->getMessage());
        }
    }

    /**
     * ✅ Dosya/Resim silme ve klasör temizliği 🗑️
     */
    public function delete(string $path, ?array $options = []): array
    {
        $result = $this->fileUpload->delete($path, $options);
        if ($result['success']) {
            $this->fileUtility->cleanEmptyFolders(dirname($path), dirname($path));
        }
        return $result;
    }


    /**
     * ✅ Export servislerine erişim 📤
     */
    public function export(): \Rbn\Framework\Packages\RbnFile\Handlers\FileExportHandler
    {
        return $this->fileExport;
    }

    /**
     * ✅ Import servislerine erişim 📥
     */
    public function import(): \Rbn\Framework\Packages\RbnFile\Handlers\FileImportHandler
    {
        return $this->fileImport;
    }


}
