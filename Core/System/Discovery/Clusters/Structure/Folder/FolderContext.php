<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\System\Discovery\Clusters\Structure\Folder;

use Rbn\Framework\Core\System\Discovery\Base\BaseDiscoveryContext;
use Rbn\Framework\Core\System\Paths\Paths;

/**
 * FolderContext - The Single Source of Truth for Directory Names 📂🧬🏛️⚓
 * 
 * RBN Framework: Autonomous folder context.
 * Performs physical path resolution and directory tree discovery.
 * Inherits full DNA (Shield, Cache, Normalize) from BaseDiscoveryContext.
 */
class FolderContext extends BaseDiscoveryContext
{
    /** @var self|null Standard singleton for static proxying */
    private static ?self $instance = null;

    /**
     * Get or initialize the static proxy instance 🕵️‍♂️
     */
    private static function getStaticInstance(): self
    {
        return self::$instance ??= new self();
    }
    /**
     * RBN Framework: Architectural Layer & Manifesto Constants 📂🎻🏛️⚓
     */
    public const DATA        = 'Data';
    public const MODELS      = 'Models';
    public const MODULE_DATA = 'ModuleData';

    /**
     * Recursively flattens a directory tree configuration into a list of absolute paths. 🗺️🏗️
     */
    public static function discoverDirectories(array $tree, string $prefix = ''): array
    {
        $directories = [];
        foreach ($tree as $key => $value) {
            if ($key === 'folder')
                continue;

            $currentPath = $prefix . DIRECTORY_SEPARATOR . (is_array($value) ? ($value['folder'] ?? $key) : $value);
            $directories[] = ltrim($currentPath, DIRECTORY_SEPARATOR);

            if (is_array($value)) {
                $directories = array_merge($directories, self::discoverDirectories($value, $currentPath));
            }
        }
        return $directories;
    }

    /**
     * SMART RESOLVER: Find Folder Name or Array by Key 🎯
     */
    public static function name(string $key, bool $returnFull = false)
    {
        return self::getStaticInstance()->resolveName($key, $returnFull);
    }

    /**
     * Instance-based Name Resolver 🕵️‍♂️
     */
    public function resolveName(string $key, bool $returnFull = false)
    {
        // [RBN Framework] DIRECT MATRIX ACCESS 🏛️⚔️🛡️⚓
        // We use FolderMatrix constants directly to avoid DiscoveryEngine recursion.
        // This ensures paths can be resolved even BEFORE the engine is fully booted.
        $all = [
            'FRAMEWORK' => \Rbn\Framework\Core\Support\Definitions\System\FolderMatrix::FRAMEWORK,
            'PROJECT' => \Rbn\Framework\Core\Support\Definitions\System\FolderMatrix::PROJECT,
        ];

        return $this->recursiveFind($all, $key, $returnFull);
    }

    /**
     * Recursive helper to find a key or dot-notated path.
     */
    private function recursiveFind(array $tree, string $key, bool $returnFull = false)
    {
        // 1. Path Navigation (Dot-Notation) 🐾
        if (strpos($key, '.') !== false) {
            $parts = explode('.', $key);
            $current = $tree;
            foreach ($parts as $part) {
                if (is_array($current) && array_key_exists($part, $current)) {
                    $current = $current[$part];
                } else {
                    return null;
                }
            }
            return $returnFull ? $current : (is_array($current) ? ($current['folder'] ?? array_pop($parts)) : $current);
        }

        // 2. Direct Match Check
        if (array_key_exists($key, $tree)) {
            $value = $tree[$key];
            return $returnFull ? $value : (is_array($value) ? ($value['folder'] ?? $key) : $value);
        }

        return null;
    }

    /**
     * Recursive search for a unique key or folder name in the tree. 🕵️
     */
    public static function recursiveSearch(array $tree, string $target, array $stack = []): ?array
    {
        $target = strtoupper($target);

        foreach ($tree as $key => $value) {
            if ($key === 'folder')
                continue;

            $folderName = is_array($value) ? ($value['folder'] ?? $key) : $value;

            $currentStack = $stack;
            $currentStack[] = $folderName;

            // Match against the KEY (SUITE) or the FOLDER NAME (RbnSuite)
            if (strtoupper($key) === $target || strtoupper($folderName) === $target) {
                return $currentStack;
            }

            if (is_array($value)) {
                $found = self::recursiveSearch($value, $target, $currentStack);
                if ($found)
                    return $found;
            }
        }

        return null;
    }

    /**
     * Build a physical path based on a config key and optional sub-path. 🏛️🏗️
     */
    public static function path(string $key, ?string $sub = null): string
    {
        return self::getStaticInstance()->resolvePath($key, $sub);
    }

    /**
     * Instance-based Path Resolver (Zero-Tolerance) 🛡️⚓⚖️
     */
    public function resolvePath(string $key, ?string $sub = null): string
    {
        // [RBN Framework] BOOTSTRAP BYPASS 🏛️⚔️🛡️⚓
        // We MUST bypass cacheDiscovery() for basic framework/project paths.
        // This prevents the "Paths not initialized" LogicException during boot/survival.

        // 1. Resolve Hierarchy via Dot-Notation 🐾
        $parts = explode('.', $key);
        $rootKey = array_shift($parts); // FRAMEWORK or PROJECT

        // [RBN Framework] DIRECT MATRIX ACCESS 🏛️⚔️🛡️⚓ (No Engine, No Recursion)
        $tree = match (strtoupper($rootKey)) {
            'FRAMEWORK' => \Rbn\Framework\Core\Support\Definitions\System\FolderMatrix::FRAMEWORK,
            'PROJECT' => \Rbn\Framework\Core\Support\Definitions\System\FolderMatrix::PROJECT,
            default => null
        };

        if ($tree === null) {
            // 🛡️ Trigger Diagnostic if root is invalid
            $this->triggerDiagnostic($rootKey, 'Folder Root (Matrix)');
            return $key . ($sub ? DIRECTORY_SEPARATOR . $this->normalize($sub) : '');
        }

        $pathParts = [];
        $current = $tree;

        foreach ($parts as $part) {
            if (is_array($current) && isset($current[$part])) {
                $value = $current[$part];
                $pathParts[] = is_array($value) ? ($value['folder'] ?? $part) : $value;
                $current = $value;
            } else {
                $pathParts[] = $part;
            }
        }

        $physicalPath = implode(DIRECTORY_SEPARATOR, $pathParts);
        $finalPath = $physicalPath . ($sub ? DIRECTORY_SEPARATOR . $this->normalize($sub) : '');

        return $finalPath;
    }

    /**
     * Check if a specialized sub-folder exists for a given namespace. 🕵️
     * 
     * Dynamically detects if 'Services' or 'Models' subdirectory exists.
     */
    public static function hasSpecializedFolder(string $namespace, string $subFolder): bool
    {
        return self::getStaticInstance()->checkSpecializedFolder($namespace, $subFolder);
    }

    public function checkSpecializedFolder(string $namespace, string $subFolder): bool
    {
        $parts = explode('.', $namespace);
        $prefix = array_shift($parts);
        $resolvedRoot = \Rbn\Framework\Core\System\Discovery\Engine\DiscoveryEngine::instance()->namespace()->resolveLayer($prefix);

        $resolved = rtrim($resolvedRoot, '\\');
        if (!empty($parts)) {
            $resolved .= '\\' . implode('\\', $parts);
        }
        $resolved = trim($resolved, '\\');

        // [RBN Framework] Autonomous Definition Access 🏛️⚔️🛡️⚓
        $fwPrefix = \Rbn\Framework\Core\System\Discovery\Clusters\Logic\Definition\Definition::get('namespace', 'FRAMEWORK_PREFIX');
        $prPrefix = \Rbn\Framework\Core\System\Discovery\Clusters\Logic\Definition\Definition::get('namespace', 'PROJECT_PREFIX');

        $isFramework = str_starts_with($resolved . '\\', (string) $fwPrefix);
        $isProject = str_starts_with($resolved . '\\', (string) $prPrefix);

        if (!$isFramework && !$isProject) {
            return false;
        }

        $relativePath = $isFramework
            ? str_replace($fwPrefix, '', $resolved . '\\')
            : str_replace($prPrefix, '', $resolved . '\\');

        $relativePath = trim($relativePath, '\\');

        // [RBN Framework] Use Centralized Path Hub 🛰️
        $rootPath = $isFramework
            ? Paths::frameworkRoot()
            : Paths::project()->root();

        $folder = str_replace('\\', DIRECTORY_SEPARATOR, $relativePath);
        $fullPath = rtrim($rootPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR . $subFolder;

        return is_dir($fullPath);
    }
}
