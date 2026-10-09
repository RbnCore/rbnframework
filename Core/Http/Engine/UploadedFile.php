<?php

namespace Rbn\Framework\Core\Http\Engine;

/**
 * UploadedFile - Intelligence for $_FILES 📎🛡️⚓
 * 
 * High-Performance file wrapper with validation and management helpers.
 */
class UploadedFile
{
    protected string $name;
    protected string $type;
    protected string $tmpName;
    protected int $size;
    protected int $error;
    protected bool $isLocal = false; // 🎼 RBN Framework: Support for server-side generated files (AI etc.)

    /**
     * DNA Capture 🧬
     */
    public function __construct(array $file, bool $isLocal = false)
    {
        $this->name    = $file['name'] ?? '';
        $this->type    = $file['type'] ?? '';
        $this->tmpName = $file['tmp_name'] ?? '';
        $this->size    = $file['size'] ?? 0;
        $this->error   = $file['error'] ?? UPLOAD_ERR_NO_FILE;
        $this->isLocal = $isLocal;
    }

    /**
     * Get temporary file path. 📍
     */
    public function tmpName(): string
    {
        return $this->tmpName;
    }

    /**
     * Dosya yükleme hatası var mı? ❌
     */
    public function isValid(): bool
    {
        if ($this->error !== UPLOAD_ERR_OK) return false;
        
        // 🎼 RBN Framework: Local files skip the is_uploaded_file check.
        return $this->isLocal || is_uploaded_file($this->tmpName);
    }

    /**
     * Resim dosyası mı? (Mime Detection) 🖼️✅
     */
    public function isImage(): bool
    {
        $mime = $this->mime();
        return str_starts_with($mime, 'image/');
    }

    /**
     * Dosya uzantısını getir 📎
     */
    public function extension(): string
    {
        return strtolower(pathinfo($this->name, PATHINFO_EXTENSION));
    }

    /**
     * Dosya tipini (MIME) getir 🧱
     */
    public function mime(): string
    {
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = @finfo_file($finfo, $this->tmpName);
            return $mime ?: $this->type;
        }
        return $this->type;
    }

    /**
     * Dosya boyutunu getir ⚖️
     * 
     * @param string $unit 'B', 'KB', 'MB'
     */
    public function size(string $unit = 'MB'): float
    {
        return match (strtoupper($unit)) {
            'MB' => round($this->size / 1024 / 1024, 2),
            'KB' => round($this->size / 1024, 2),
            default => (float) $this->size
        };
    }

    /**
     * Dosya ismini getir 🏷️
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Dosyayı sunucuya taşı 🚚✨
     */
    public function move(string $path, ?string $filename = null): bool
    {
        if (!$this->isValid()) return false;

        $filename = $filename ?? $this->name;
        $target   = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;

        // Ensure directory exists
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }

        // 🎼 RBN Framework: [ATOMIC MOVE]
        // Yerel dosyalar için copy, yüklenen dosyalar için move_uploaded_file kullan.
        if ($this->isLocal) {
            return copy($this->tmpName, $target);
        }

        return move_uploaded_file($this->tmpName, $target);
    }
}
