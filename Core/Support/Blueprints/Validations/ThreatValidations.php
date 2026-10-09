<?php
declare(strict_types=1);

namespace Rbn\Framework\Core\Support\Blueprints\Validations;
/**
 * ThreatValidations - The Forbidden Library 🛡️🐉🚫
 * 
 * RBN Framework: Master list of dangerous extensions and attack patterns.
 * Provides a Single Source of Truth for Security blocking rules.
 */
class ThreatValidations
{

    /**
     * Illegal Extensions (Hard Block 🚫)
     * These should NEVER be allowed in any context.
     */
    public const FORBIDDEN_EXTENSIONS = [
        // Scripts & Web
        'php',
        'php3',
        'php4',
        'php5',
        'phtml',
        'phps',
        'pl',
        'py',
        'cgi',
        'asp',
        'aspx',
        'jsp',
        'js',
        // System & Binaries
        'sh',
        'exe',
        'bat',
        'cmd',
        'scr',
        'vbs',
        'jar',
        'msi',
        'com'
    ];

    /**
     * Dangerous Patterns (Regex Support 🧬)
     */
    public const SUSPICIOUS_PATTERNS = [
        '<?php',
        '<?=',
        '<script',
        'javascript:',
        'eval(',
        'exec(',
        'system(',
        'shell_exec('
    ];

    /**
     * Check if a given extension is in the forbidden list.
     */
    public static function isForbiddenExtension(string $extension): bool
    {
        return in_array(strtolower($extension), self::FORBIDDEN_EXTENSIONS);
    }

    /**
     * Check if content contains any suspicious security patterns.
     */
    public static function containsSuspiciousPattern(string $content): bool
    {
        foreach (self::SUSPICIOUS_PATTERNS as $pattern) {
            if (stripos($content, $pattern) !== false) {
                return true;
            }
        }
        return false;
    }
}
