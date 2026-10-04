<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnFile\Handlers;

use Rbn\Framework\Core\Base\BaseComponent;
use Rbn\Framework\Core\Http\Engine\UploadedFile;
use Rbn\Framework\Core\System\Paths\Paths;

/**
 * FileUploadHandler - The Storage Guard (Worker) 🚚🏢
 * 
 * RBN 3.5: Masterpiece Standard (BaseComponent Actor).
 * Location: Bundles\RbnSuite\RbnFile\Handlers\
 */
class FileUploadHandler extends BaseComponent
{
    private array $config;

    public function __construct()
    {
        // 🎯 [TARGET 1]: Secure Storage
        $securePath = Paths::project()->uploads() . DIRECTORY_SEPARATOR;

        // 🎯 [TARGET 2]: Public Storage (Dynamically resolved via Paths::publicRoot())
        $publicPath = Paths::publicRoot();

        $this->config = [
            'secure_root' => $securePath,
            'public_root' => $publicPath . DIRECTORY_SEPARATOR
        ];
    }

    /**
     * Dosya yükleme işlemine başla (Worker logic) 🚀
     */
    public function execute(UploadedFile $file, string $folder, string $disk = 'secure', ?array $options = []): array
    {
        try {
            // 🎼 RBN 3.5: [SOVEREIGN DISK ORCHESTRATION WITH CDN OVERRIDES] 🏛️🛰️⚓
            if ($disk === 'cdn' || !empty($options['cdn_path'])) {
                $cdnPath = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $options['cdn_path']), DIRECTORY_SEPARATOR);
                $cdnUrl = rtrim($options['cdn_url'] ?? '', '/');
                $projectKey = $options['project_key'] ?? '';
                
                $subFolder = trim($folder, '/\\');
                if ($projectKey) {
                    $targetDir = $cdnPath . DIRECTORY_SEPARATOR . 'projects' . DIRECTORY_SEPARATOR . $projectKey . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $subFolder) . DIRECTORY_SEPARATOR;
                    $relativePath = $cdnUrl . '/projects/' . $projectKey . '/' . trim($folder, '/') . '/';
                } else {
                    $targetDir = $cdnPath . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $subFolder) . DIRECTORY_SEPARATOR;
                    $relativePath = $cdnUrl . '/' . trim($folder, '/') . '/';
                }
            } elseif ($disk === 'public') {
                $targetDir = $this->config['public_root'] . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, trim($folder, '/\\')) . DIRECTORY_SEPARATOR;
                $relativePath = '/' . trim($folder, '/') . '/';
            } else {
                $targetDir = $this->config['secure_root'] . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, trim($folder, '/\\')) . DIRECTORY_SEPARATOR;
                $relativePath = '/uploads/' . trim($folder, '/') . '/';
            }
            
            $targetDir = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $targetDir);
            
            // 1. Dosya adını belirle (UtilityHandler kullanılabilir ama burada atomic isim üretilecek)
            $originalName = $file->name();
            $extension = $file->extension();
            $fileName = $options['filename'] ?? $this->generateUniqueName(null, $extension);

            // 2. Dosyayı taşı (RBN 3.5 DNA)
            if ($file->move($targetDir, $fileName)) {
                $fullRelativePath = $relativePath . $fileName;

                return $this->sendSuccess('Dosya başarıyla yüklendi', [
                    'path' => $fullRelativePath,
                    'filename' => $fileName,
                    'original_name' => $originalName,
                    'size' => $file->size('B')
                ]);
            }

            return $this->sendError('Dosya hedef klasöre taşınamadı.');

        } catch (\Exception $e) {
            return $this->sendError('Upload execution hatası: ' . $e->getMessage());
        }
    }

    /**
     * Dosyayı sil 🗑️
     */
    public function delete(string $imagePath, ?array $options = []): array
    {
        try {
            // 🎼 RBN 3.5: [DYNAMIC PATH RESOLUTION] 🏹🛰️⚓
            $imagePath = ltrim($imagePath, '/');
            
            // CDN / Özel dizin silme kontrolü
            if (!empty($options['cdn_path']) || str_starts_with($imagePath, 'http')) {
                $cdnPath = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $options['cdn_path'] ?? ''), DIRECTORY_SEPARATOR);
                $cdnUrl = rtrim($options['cdn_url'] ?? '', '/');
                
                if ($cdnUrl && str_starts_with($imagePath, $cdnUrl)) {
                    // Url kısmını temizleyip cdn_path ile birleştir
                    $cleanPath = ltrim(substr($imagePath, strlen($cdnUrl)), '/');
                    $fullPath = $cdnPath . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $cleanPath);
                } else {
                    // Eğer doğrudan temiz yol gelmişse
                    $fullPath = $cdnPath . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $imagePath);
                }
            } elseif (str_starts_with($imagePath, 'uploads/')) {
                $cleanPath = preg_replace('#^uploads/#', '', $imagePath);
                $fullPath = $this->config['secure_root'] . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $cleanPath);
            } else {
                // Public disk (images, pdfs, excels vb. hepsi buradan çözülür)
                $fullPath = $this->config['public_root'] . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $imagePath);
            }

            if (!file_exists($fullPath)) {
                return $this->sendSuccess('Dosya zaten mevcut değil');
            }

            if (unlink($fullPath)) {
                return $this->sendSuccess('Dosya başarıyla silindi');
            }

            return $this->sendError('Dosya silinemedi');

        } catch (\Exception $e) {
            return $this->sendError('Dosya silme hatası: ' . $e->getMessage());
        }
    }
}
