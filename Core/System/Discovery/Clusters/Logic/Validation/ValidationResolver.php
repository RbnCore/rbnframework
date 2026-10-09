<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\System\Discovery\Clusters\Logic\Validation;

use Rbn\Framework\Core\System\Discovery\Base\BaseResolver;
use Rbn\Framework\Core\System\Paths\Paths;

/**
 * ValidationResolver - Specialized DNA Motor for Validation Rules 💎⚙️⚓
 * 
 * RBN Framework: Targeted Hub for validation blueprints.
 * Scans Core/Support/Blueprints/Validations for constants and dynamic rules.
 */
class ValidationResolver extends BaseResolver
{
    /** @var array<string, string> Discovered validation categories */
    private array $categories = [];

    /** @var bool Discovery status */
    private bool $isMapped = false;

    /**
     * Master retrieval method for validation data 🧬🏛️
     */
    public function get(string $category, string $key): mixed
    {
        // 1. Point of Entry: Standard Discovery 🎯
        $class = $this->resolveCategory($category);

        if ($class) {
            $value = $this->getValueFromClass($class, $key);
            if ($value !== null) return $value;
        }

        // 2. RBN Framework [DEEP SCAN] 🛰️🎡
        // Eğer kategori 'validation' ise veya belirtilen kategoride anahtar bulunamadıysa,
        // tüm tescilli doğrulama sınıflarını tara.
        if ($category === 'validation' || $category === 'validations' || $class === null) {
            $this->ensureDiscovery();
            foreach ($this->categories as $tag => $blueprintClass) {
                // 'validation' etiketinin kendisini ve daha önce bakılmış sınıfı atla (hız için)
                if ($tag === $category || $blueprintClass === $class) continue;

                $value = $this->getValueFromClass($blueprintClass, $key);
                if ($value !== null) return $value;
            }
        }

        return null;
    }

    /**
     * Internal helper to extract values from a blueprint class 🧬
     */
    private function getValueFromClass(string $class, string $key): mixed
    {
        // 1. Check Constants (Standard DNA 🧬)
        $const = strtoupper($key);
        if (defined("$class::$const")) {
            return constant("$class::$const");
        }

        // 2. Check Static Methods (Dynamic DNA ⚙️)
        $method = 'get' . ucfirst(self::toCamelCase($key));
        if (method_exists($class, $method)) {
            return $class::$method();
        }

        return null;
    }

    /**
     * Internal Resolver logic ⚙️
     */
    public function resolveCategory(string $category): ?string
    {
        $this->ensureDiscovery();
        
        // RBN Framework: Standardize category names for discovery balance
        $tag = strtolower($category);
        return $this->categories[$tag] ?? null;
    }

    /**
     * resolve() implementation for Validations (Satisfy contract) 🧬🏹
     */
    public function resolve(string $name, ?string $context = null): ?object
    {
        $class = $this->resolveCategory($name);
        return $class ? (object)['class' => $class] : null;
    }

    /**
     * Autonomous Discovery Scan 📂🛰️
     */
    private function ensureDiscovery(): void
    {
        if ($this->isMapped) return;
        $this->isMapped = true;

        $basePath = Paths::frameworkRoot() . DIRECTORY_SEPARATOR . 'Core' . DIRECTORY_SEPARATOR . 'Support' . DIRECTORY_SEPARATOR . 'Blueprints' . DIRECTORY_SEPARATOR . 'Validations';
        $baseNamespace = 'Rbn\Framework\Core\Support\Blueprints\Validations';

        if (!is_dir($basePath)) return;

        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($basePath));
        foreach ($iterator as $file) {
            if ($file->isDir() || $file->getExtension() !== 'php') continue;

            $realPath = $file->getRealPath();
            require_once $realPath;

            $filePath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $realPath);
            $basePathDir = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $basePath);

            $relativePath = str_ireplace([$basePathDir, '.php'], ['', ''], $filePath);
            $className = $baseNamespace . str_replace(DIRECTORY_SEPARATOR, '\\', $relativePath);

            if (!class_exists($className, false)) continue;

            // RBN Framework Auto-Tagging: Standardizes 'EmailValidations' to 'email'
            $categoryKey = strtolower(str_replace(['Map', 'Definitions', 'Blueprint', 'Validations'], '', $file->getBasename('.php')));

            $this->categories[$categoryKey] = $className;
        }
    }
}
