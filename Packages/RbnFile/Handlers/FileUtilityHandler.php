<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnFile\Handlers;

use Rbn\Framework\Core\Base\BaseComponent;
use Rbn\Framework\Core\System\Paths\Paths;

/**
 * FileUtilityHandler - The Master Utility Worker 🛠️🛡️
 * 
 * RBN 3.5: Masterpiece Standard (BaseComponent Actor).
 * Location: Bundles\RbnSuite\RbnFile\Handlers\
 */
class FileUtilityHandler extends BaseComponent
{
    private array $config;

    public function __construct()
    {
        $this->config = [
            'upload_base_path' => Paths::project()->uploads() . '/',
            'directory_permissions' => 0755
        ];
    }

    /**
     * Klasör oluştur (Otonom) 📂
     */
    public function createDirectory(string $folder): array
    {
        try {
            $cleanFolder = $this->sanitizeFolderName($folder);
            $targetDir = $this->config['upload_base_path'] . trim($cleanFolder, '/') . '/';

            if (is_dir($targetDir)) {
                return $this->sendSuccess('Klasör mevcut', ['path' => $targetDir]);
            }

            if (mkdir($targetDir, $this->config['directory_permissions'], true)) {
                return $this->sendSuccess('Klasör oluşturuldu', ['path' => $targetDir]);
            }

            return $this->sendError('Klasör oluşturulamadı.');

        } catch (\Exception $e) {
            return $this->sendError('Klasör hatası: ' . $e->getMessage());
        }
    }

    /**
     * Boş klasör temizliği 🧹
     */
    public function cleanEmptyFolders(string $folderPath, string $relativeFolderPath): void
    {
        if (!is_dir($folderPath))
            return;

        $uploadBasePath = Paths::project()->uploads();
        if ($folderPath === rtrim($uploadBasePath, '/'))
            return;

        $files = scandir($folderPath);
        $actualFiles = array_diff($files, ['.', '..']);

        if (empty($actualFiles)) {
            if (rmdir($folderPath)) {
                $this->cleanEmptyFolders(dirname($folderPath), dirname($relativeFolderPath));
            }
        }
    }

    /**
     * 🎼 RBN 3.5: [SOVEREIGN CONTENT TO FILE] 🛰️⚓
     * Ham veriyi veya Base64 içeriği geçici bir dosyaya dönüştürür.
     */
    public function contentToFile(string $content, string $extension = 'png'): string|bool
    {
        try {
            // 🧪 Base64 kontrolü ve temizliği (data:image/png;base64, kısmını ayıkla)
            if (preg_match('#^data:image/[^;]+;base64,#', $content)) {
                $content = preg_replace('#^data:image/[^;]+;base64,#', '', $content);
            }

            // Satır sonu karakterleri ve boşlukları temizle 🧼
            $cleanContent = preg_replace('/\s+/', '', $content);
            $data = base64_decode($cleanContent, false);
            $finalContent = ($data !== false && !empty($data)) ? $data : $content;

            $tempDir = defined('TEMP_DIR') ? TEMP_DIR : sys_get_temp_dir();
            $tempPath = rtrim($tempDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'rbn_ai_' . uniqid() . '.' . $extension;

            if (file_put_contents($tempPath, $finalContent) !== false) {
                return $tempPath;
            }

            return false;
        } catch (\Exception $e) {
            return false;
        }
    }
}
