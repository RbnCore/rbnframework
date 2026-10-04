<?php

namespace Rbn\Framework\Core\Http\Engine\Traits\Request;

use Rbn\Framework\Core\Http\Engine\UploadedFile;

/**
 * FileTrait - The Data Porter 📎⚓
 */
trait FileTrait
{
    /**
     * Spesifik bir dosyayı al (UploadedFile Object) 📎✨
     */
    public function file(string $key): ?UploadedFile
    {
        if (!$this->hasFile($key)) return null;
        return new UploadedFile($this->files[$key]);
    }

    /**
     * Dosya gönderildi mi ve geçerli mi? ✅
     */
    public function hasFile(string $key): bool
    {
        if (!isset($this->files[$key])) return false;
        
        $file = $this->files[$key];
        return isset($file['error']) && $file['error'] === UPLOAD_ERR_OK;
    }

    /**
     * Tüm dosyaları al (Array of UploadedFile Objects) 📂
     */
    public function allFiles(): array
    {
        $files = [];
        foreach ($this->files as $key => $data) {
            $files[$key] = new UploadedFile($data);
        }
        return $files;
    }

    /**
     * Spesifik bir anahtarla eşleşen dosyaları al (Array of UploadedFile Objects) 📂🛰️
     */
    public function files(string $prefix): array
    {
        $all = $this->allFiles();
        return array_filter($all, fn($key) => str_contains($key, $prefix), ARRAY_FILTER_USE_KEY);
    }
}
