<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Support\Bridges\Traits;

/**
 * NormalizationTrait - Shared Case Normalization Logic 🐪🏔️
 * 
 * RBN 3.5: Centralized string normalization for all configuration and discovery components.
 * Integrated into the Core DNA (BaseContextTrait).
 */
trait NormalizationTrait
{
    /**
     * Normalization: toCamelCase 🐪
     */
    public static function toCamelCase(string $input): string
    {
        $input = str_replace(['-', ' '], '_', $input);
        return lcfirst(str_replace('_', '', ucwords($input, '_')));
    }

    /**
     * Normalization: toPascalCase 🏔️
     */
    public static function toPascalCase(string $input): string
    {
        $input = str_replace(['-', ' '], '_', $input);
        return str_replace('_', '', ucwords($input, '_'));
    }

    /**
     * Path Normalization: toPascalPath (Linux-Safe Discovery) 📂🛰️⚓
     * RBN 3.5: Splits path by slashes and PascalCases every segment.
     */
    public static function toPascalPath(string $path): string
    {
        $path = str_replace('\\', '/', $path);
        $segments = explode('/', $path);
        $normalized = array_map([self::class, 'toPascalCase'], $segments);
        return implode('/', $normalized);
    }

    /**
     * Case-Safe Path: toCaseSafePath (Optimal View Discovery) 👁️🛰️⚓
     * RBN 3.5: PascalCases directories but keeps the final filename lowercase.
     */
    public static function toCaseSafePath(string $path): string
    {
        $path = str_replace('\\', '/', $path);
        $segments = explode('/', $path);
        
        $filename = array_pop($segments);
        $normalized = array_map([self::class, 'toPascalCase'], $segments);
        $normalized[] = strtolower($filename); // View files are usually lowercase
        
        return implode('/', $normalized);
    }

    /**
     * Normalize input strings (standardizing paths/namespaces) 🧼🚿
     */
    public function normalize(string $input): string
    {
        return str_replace(['/', '\\'], DIRECTORY_SEPARATOR, trim($input, '/\\ '));
    }

    /**
     * Sanitize Folder Name/Path (Security DNA) 📂🚿
     */
    public function sanitizeFolderName(string $folder): string
    {
        return trim(preg_replace(['/[^a-zA-Z0-9\-_\/]/', '/\/+/'], ['', '/'], $folder), '/');
    }

    /**
     * Sanitize File Name (Security DNA) 🏷️🚿
     */
    public function sanitizeFileName(string $filename, ?string $extension = null, bool $withTimestamp = false): string
    {
        $filename = preg_replace('/[^a-zA-Z0-9\-_]/', '_', $filename);
        $filename = trim($filename, '_');

        if ($withTimestamp) {
            $filename .= '_' . date('Ymd_His');
        }

        return $extension ? $filename . '.' . ltrim($extension, '.') : $filename;
    }

    /**
     * Generate Unique Identity Name 🆔✨
     */
    public function generateUniqueName(?string $prefix = null, ?string $extension = null): string
    {
        $name = ($prefix ? trim($prefix, '_') . '_' : '') . uniqid() . '_' . time();
        return $extension ? $name . '.' . ltrim($extension, '.') : $name;
    }
}
