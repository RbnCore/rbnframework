<?php

namespace Rbn\Framework\Core\Base\Services\Traits\Service\Engine;

use Rbn\Framework\Core\System\Paths\Paths;
use Exception;
use ZipArchive;

/**
 * FileServiceTrait - Service-side file management & delivery engine 📂
 * 
 * Provides unified logic for:
 * 1. File Statistics (Size, Count)
 * 2. Standardized Downloads (Streaming)
 * 3. Bulk ZIP Packaging
 * 4. Safe File Deletion
 */
trait FileServiceTrait
{
    /**
     * Execute Bulk Deletion 🗑️
     * 
     * @param string $directory Absolute base path
     * @param array $filenames List of relative filenames to delete
     * @return bool
     */
    protected function executeBulkDelete(string $directory, array $filenames): bool
    {
        $success = true;
        foreach ($filenames as $file) {
            $path = $this->resolveSafePath($directory, (string) $file);
            if (file_exists($path) && is_writable($path)) {
                if (!@unlink($path)) {
                    $success = false;
                }
            }
        }
        return $success;
    }

    /**
     * Prepare Temporary File from content (Preparation Logic) 💾
     */
    public function prepareTempFile(string $filename, string $content): string
    {
        $path = Paths::project()->storage('framework/temp');
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }

        file_put_contents($path . DIRECTORY_SEPARATOR . $filename, $content);
        return $filename;
    }

    /**
     * Prepare Temporary ZIP from raw content array (Preparation Logic) 📦
     * 
     * @param string $filename Target ZIP name
     * @param array $files [filename => content, ...]
     * @return string
     */
    public function prepareTempZip(string $filename, array $files): string
    {
        $path = Paths::project()->storage('framework/temp');
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }

        $zipPath = $path . DIRECTORY_SEPARATOR . $filename;
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
            throw new Exception("ZIP dosyası oluşturulamadı.");
        }

        foreach ($files as $name => $content) {
            $zip->addFromString($name, $content);
        }
        $zip->close();

        return $filename;
    }

    /**
     * Calculate Storage Statistics 📊
     * 
     * @param string $directory Absolute path to scan
     * @param string $pattern Glob pattern (e.g. *.cache)
     * @param bool $recursive Deep scan
     * @return array [total_files, size_raw, files]
     */
    protected function calculateStorageStats(string $directory, string $pattern = '*', bool $recursive = false): array
    {
        if (!is_dir($directory)) {
            return ['total_files' => 0, 'size_raw' => 0, 'files' => []];
        }

        $files = $recursive ? $this->scanDirRecursive($directory, $pattern) : glob($directory . DIRECTORY_SEPARATOR . $pattern);
        $totalSize = 0;
        $fileList = [];

        if ($files) {
            foreach ($files as $file) {
                if (is_file($file) && basename($file) !== '.gitkeep') {
                    $size = filesize($file);
                    $totalSize += $size;
                    $mtime = filemtime($file);

                    $fileList[] = [
                        'name' => basename($file),
                        'relative' => str_replace($directory . DIRECTORY_SEPARATOR, '', $file), // Keep subdirectory info if recursive
                        'size' => $size,
                        'mtime' => $mtime,
                        'modified_at' => date('Y-m-d H:i:s', $mtime)
                    ];
                }
            }

            // Sort by newest first
            usort($fileList, function ($a, $b) {
                return $b['mtime'] <=> $a['mtime'];
            });
        }

        return [
            'total_files' => count($fileList),
            'size_raw' => $totalSize,
            'files' => $fileList
        ];
    }

    /**
     * Safe Path Resolver 🛡️
     * Prevents directory traversal attacks.
     */
    protected function resolveSafePath(string $baseDir, string $identifier): string
    {
        $baseDir = rtrim($baseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        // Strip out any directory traversal attempts
        $safePath = str_replace(['../', '..\\'], '', $identifier);
        return $baseDir . $safePath;
    }

    /**
     * Recursive Directory Scanner Helper
     */
    private function scanDirRecursive(string $directory, string $pattern = '*'): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        // Convert pattern to regex for recursive matching (simple implementation)
        $regex = '/^' . str_replace(['*', '.'], ['.*', '\.'], $pattern) . '$/i';

        foreach ($iterator as $file) {
            if ($file->isFile() && preg_match($regex, $file->getFilename())) {
                $files[] = $file->getPathname();
            }
        }
        return $files;
    }
}
