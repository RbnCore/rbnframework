<?php

namespace Rbn\Framework\Core\System\Storage\Base;

use Rbn\Framework\Core\System\Storage\Drivers\UniversalFileDriver;
use Rbn\Framework\Core\Base\BaseComponent;

/**
 * BaseStorageProvider - Depolama Birimleri İçin Soyut Temel Sınıf 🧱
 * 
 * Tüm alt sistemlerin (Cache, Log, Session vb.) ortak mantığını içerir.
 */
abstract class BaseStorageProvider extends BaseComponent
{
    protected UniversalFileDriver $driver;
    protected string $storageName;
    protected bool $encrypted = false;
    protected string $format = 'raw';

    // Fluent targeting properties
    protected ?string $targetId = null;

    public function __construct(UniversalFileDriver $driver)
    {
        $this->driver = $driver;
        parent::__construct();
    }

    /**
     * Alt sistemin yazma dizini
     */
    abstract protected function getStorageDir(): string;

    /**
     * Fluent: Belirli bir ID/Dosya hedefle
     */
    public function withId(string $id): self
    {
        $this->targetId = $id;
        return $this;
    }

    /**
     * Fluent: Belirli bir Proje hedefle 🚀
     */
    public function withProject(?string $projectKey): self
    {
        $this->projectKey = $projectKey;
        return $this;
    }

    /**
     * Veri Yaz (Standardized)
     */
    public function set(string $key, mixed $value, array $options = []): bool
    {
        $path = $this->resolvePath($key);
        return $this->driver->write(
            $path,
            $value,
            $this->format,
            $options['append'] ?? false,
            $this->encrypted
        );
    }

    /**
     * Veri Oku (Standardized)
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $path = $this->resolvePath($key);
        return $this->driver->read($path, $this->format, $this->encrypted) ?? $default;
    }


    /**
     * Veri Sil
     */
    public function delete(string $key): bool
    {
        return $this->driver->delete($this->resolvePath($key));
    }

    /**
     * Tümünü Temizle
     */
    public function clearAll(): bool
    {
        $dir = $this->getStorageDir();
        $files = glob($dir . '/*');
        foreach ($files as $file) {
            if (is_file($file))
                @unlink($file);
        }
        return true;
    }

    /**
     * Ortak İstatistik Motoru (Proje Bazlı Filtreleme Destekli) 📊
     */
    public function getStats(): array
    {
        $dir = $this->getStorageDir();
        $totalSize = 0;
        $totalFiles = 0;
        $files = [];
        $projectKey = $this->projectKey ?: (project_key() ?: 'default');

        if (is_dir($dir)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $filename = $file->getBasename();

                    // Proje bazlı depolanan dosyalarda eşleşme kontrolü 🛡️
                    if ($this->storageName === 'cache' || $this->storageName === 'sessions' || $this->storageName === 'views') {
                        if (!str_starts_with($filename, $projectKey . '_')) {
                            continue;
                        }
                    } elseif ($this->storageName === 'logs') {
                        if (!str_contains($filename, '_' . $projectKey . '.jsonl')) {
                            continue;
                        }
                    }

                    $totalSize += $file->getSize();
                    $totalFiles++;
                    $files[] = [
                        'name' => $filename,
                        'size' => $file->getSize(),
                        'modified' => $file->getMTime(),
                        'mtime' => $file->getMTime()
                    ];
                }
            }
        }

        return [
            'total_files' => $totalFiles,
            'total_size' => $totalSize,
            'files' => $files
        ];
    }

    /**
     * Güvenli Dosya Yolu Çözücü
     */
    protected function resolvePath(string $key): string
    {
        $safeKey = preg_replace('/[^a-zA-Z0-9_\-\.\/]/', '_', $key);
        return $this->getStorageDir() . '/' . ltrim($safeKey, '/');
    }

    /**
     * Get Standardized File Content for UI 🔍
     */
    public function getFileContent(string $filename): ?array
    {
        $path = $this->resolvePath($filename);
        if (!is_file($path))
            return null;

        // 1. Read Raw Content (Handle Decryption if $encrypted is true)
        $raw = $this->driver->read($path, 'raw', $this->encrypted);
        if ($raw === null || $raw === false)
            return null;

        // 2. Decode Content (Handle Serialization or JSON if needed)
        $data = $raw;
        if ($this->format === 'serialized') {
            $data = @unserialize($raw);
        } elseif ($this->format === 'json' || $this->format === 'jsonl') {
            $data = json_decode($raw, true);
        }

        return [
            'data' => $data,
            'raw' => $raw,
            'size' => filesize($path),
            'modified' => filemtime($path),
            'mtime' => filemtime($path)
        ];
    }
}
