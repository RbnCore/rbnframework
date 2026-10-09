<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Support\Blueprints\Validations;

/**
 * FileValidations - Unified DNA for File Operations 📂🛡️💎
 * 
 * RBN Framework: Consolidates both error messages and technical rule presets.
 * Centralized Source of Truth for all RbnFile components.
 */
class FileValidations
{
    /** --- Error Messages (Messages DNA) --- */
    public const REQUIRED = "Lütfen bir dosya seçiniz.";
    public const INVALID_TYPE = "Seçilen dosya türü ({ext}) izin verilenler arasında değil.";
    public const MAX_SIZE = "Dosya boyutu çok büyük. Maksimum {max} MB olabilir.";
    public const MIN_DIMENSIONS = "Resim boyutları çok küçük. Minimum {width}x{height} px olmalıdır.";
    public const MAX_DIMENSIONS = "Resim boyutları çok büyük. Maksimum {width}x{height} px olmalıdır.";
    public const MIME_MISMATCH = "Dosya içeriği ile uzantısı uyumsuz (Örn: .png görünümlü .jpg dosyası). Lütfen uzantıyı düzeltin.";
    public const SECURITY_THREAT = "Dosya içerisinde şüpheli/zararlı kod blokları veya güvenlik tehdidi tespit edildi.";
    public const MOVE_ERROR = "Dosya hedef klasöre taşınamadı.";
    public const IMAGE_INVALID = "Geçersiz veya bozuk resim dosyası.";

    /** --- Technical Presets (Logic DNA) --- */
    public const PRESETS = [
        'avatar' => [
            'max_size' => 2 * 1024 * 1024,
            'allowed_types' => ['jpg', 'jpeg', 'png', 'webp'],
            'min_width' => 100,
            'min_height' => 100,
            'versions' => ['thumb' => [150, 150]]
        ],
        'gallery' => [
            'max_size' => 10 * 1024 * 1024,
            'allowed_types' => ['jpg', 'jpeg', 'png', 'webp', 'gif'],
            'versions' => ['medium' => [600, 450]]
        ],
        'document' => [
            'max_size' => 20 * 1024 * 1024, // 20MB
            'allowed_types' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'zip', 'rar']
        ],
        'system' => [
            'max_size' => 50 * 1024 * 1024, // 50MB
            'allowed_types' => ['sql', 'json', 'xml', 'zip']
        ]
    ];

    /** --- Derivative Versions (Variation DNA) --- */
    public const VERSIONS = [
        'thumb' => [150, 150],
        'medium' => [600, 450],
        'large' => [1200, 900]
    ];

    /**
     * Get rule set for a specific preset 🔍
     */
    public static function getPreset(string $name): ?array
    {
        return self::PRESETS[$name] ?? null;
    }

    /**
     * Get dimensions for a standard version 🖼️
     */
    public static function getVersion(string $name): ?array
    {
        return self::VERSIONS[$name] ?? null;
    }
}
