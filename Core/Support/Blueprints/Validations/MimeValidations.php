<?php
declare(strict_types=1);

namespace Rbn\Framework\Core\Support\Blueprints\Validations;
/**
 * MimeValidations - Centralized MIME Type Repository 🛡️📦⚓
 * 
 * RBN 3.5: Multi-Category MIME mapping and validation standards.
 * Provides a Single Source of Truth for File Security and Validation.
 */
class MimeValidations
{

    /**
     * Extension Categories (Standard Groups)
     */
    public const CATEGORIES = [
        'image' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'],
        'document' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'rtf'],
        'archive' => ['zip', 'rar', '7z', 'tar', 'gz'],
        'video' => ['mp4', 'avi', 'mov', 'wmv', 'mkv', 'flv'],
        'audio' => ['mp3', 'wav', 'flac', 'm4a', 'ogg'],
        'code' => ['css', 'js', 'json', 'php', 'map'],
        'asset' => ['ico', 'woff', 'woff2', 'ttf', 'eot']
    ];

    /**
     * Extension to MIME Type Mappings (Security DNA 🧬)
     * Multiple mimes allowed per extension for compatibility.
     */
    public const MAP = [
        // Images
        'jpg' => ['image/jpeg', 'image/pjpeg'],
        'jpeg' => ['image/jpeg', 'image/pjpeg'],
        'png' => ['image/png'],
        'gif' => ['image/gif'],
        'webp' => ['image/webp'],
        'bmp' => ['image/bmp', 'image/x-ms-bmp'],
        'svg' => ['image/svg+xml', 'image/svg'],

        // Documents
        'pdf' => ['application/pdf'],
        'doc' => ['application/msword'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        'xls' => ['application/vnd.ms-excel'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        'txt' => ['text/plain'],

        // Archives
        'zip' => ['application/zip', 'application/x-zip-compressed', 'multipart/x-zip', 'application/x-compressed'],
        'rar' => ['application/x-rar-compressed', 'application/octet-stream'],
        '7z' => ['application/x-7z-compressed'],

        // Videos
        'mp4' => ['video/mp4'],
        'avi' => ['video/x-msvideo'],
        'mov' => ['video/quicktime'],
        'wmv' => ['video/x-ms-wmv'],

        // Code & Assets
        'css' => ['text/css'],
        'js' => ['text/javascript', 'application/javascript'],
        'json' => ['application/json'],
        'ico' => ['image/x-icon', 'image/vnd.microsoft.icon'],
        'woff' => ['font/woff'],
        'woff2' => ['font/woff2'],
        'ttf' => ['font/ttf'],
        'eot' => ['application/vnd.ms-fontobject']
    ];

    /**
     * Get allowed extensions for a specific category.
     */
    public static function getExtensionsByCategory(string $category): array
    {
        return self::CATEGORIES[$category] ?? [];
    }

    /**
     * Get valid MIME types for a specific extension.
     */
    public static function getMimesByExtension(string $extension): array
    {
        return self::MAP[strtolower($extension)] ?? [];
    }

    /**
     * Check if a given MIME type is valid for an extension.
     */
    public static function isValidMime(string $extension, string $mime): bool
    {
        $allowed = self::getMimesByExtension($extension);

        // If we don't have a signature for this extension, we pass (soft validation)
        if (empty($allowed)) {
            return true;
        }

        return in_array($mime, $allowed);
    }
}
