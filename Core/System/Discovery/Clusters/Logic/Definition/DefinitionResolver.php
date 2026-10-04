<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\System\Discovery\Clusters\Logic\Definition;

use Rbn\Framework\Core\System\Discovery\Base\BaseResolver;
use Rbn\Framework\Core\System\Paths\Paths;

/**
 * DefinitionResolver - The Master DNA Motor & API 🛰️⚙️⚓
 * 
 * RBN 3.5: Hybrid Hub. 
 * Managed by DiscoveryEngine (untouched).
 * Provides the instance-based API for $this->discover->definitions()->get().
 */
class DefinitionResolver extends BaseResolver
{
    /** @var array<string, string> Discovered blueprint categories */
    private array $categories = [];

    /** @var bool Discovery status */
    private bool $isMapped = false;

    /**
     * Master retrieval method for instance access 🧬🏛️
     */
    public function get(string $category, string $key): mixed
    {
        $class = $this->resolveCategory($category);

        if (!$class) {
            throw new \LogicException("RBN Discovery Error: Definition category [{$category}] not found.");
        }

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

        throw new \LogicException("RBN Discovery Error: Key [{$key}] not found in category [{$category}] ({$class}).");
    }

    /**
     * Internal Resolver logic ⚙️
     */
    public function resolveCategory(string $category): ?string
    {
        $this->ensureDiscovery();
        return $this->categories[strtolower($category)] ?? null;
    }

    /**
     * resolve() implementation for Definitions (Satisfy contract) 🧬🏹
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
        if ($this->isMapped)
            return;
        $this->isMapped = true;

        // [RBN 3.5] CIRCULAR GUARD 🛡️⚓
        // Pre-map the core namespace blueprint to satisfy early-boot requirements 🧬
        $this->categories['namespace'] = \Rbn\Framework\Core\Support\Definitions\System\NamespaceMap::class;
        $this->categories['database_master'] = \Rbn\Framework\Core\System\Config\Definitions\DbProfiles\MasterDbData::class;
        $this->categories['database_common'] = \Rbn\Framework\Core\System\Config\Definitions\DbProfiles\CommonDbData::class;
        $this->categories['database_project'] = \Rbn\Framework\Core\System\Config\Definitions\DbProfiles\ProjectDbData::class;

        $basePath = Paths::frameworkRoot() . DIRECTORY_SEPARATOR . 'Core' . DIRECTORY_SEPARATOR . 'Support' . DIRECTORY_SEPARATOR . 'Definitions';
        $baseNamespace = 'Rbn\Framework\Core\Support\\Definitions';

        if (!is_dir($basePath))
            return;

        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($basePath));
        foreach ($iterator as $file) {
            if ($file->isDir() || $file->getExtension() !== 'php')
                continue;

            $realPath = $file->getRealPath();
            require_once $realPath;

            $filePath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $realPath);
            $basePathDir = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $basePath);

            $relativePath = str_ireplace([$basePathDir, '.php'], ['', ''], $filePath);
            $className = $baseNamespace . str_replace(DIRECTORY_SEPARATOR, '\\', $relativePath);

            if (!class_exists($className, false))
                continue;

            $explicitCategory = method_exists($className, 'getDefinitionCategory') ? $className::getDefinitionCategory() : null;

            // [RBN 3.5] Property Fallback: Check for static property if method is missing 🧬🏛️
            if ($explicitCategory === null && property_exists($className, 'definitionCategory')) {
                $reflection = new \ReflectionClass($className);
                $fallbackValue = null;
                $explicitCategory = $reflection->getStaticPropertyValue('definitionCategory', $fallbackValue);
            }

            $categoryKey = $explicitCategory ?: strtolower(str_replace(['Map', 'Definitions', 'Blueprint'], '', $file->getBasename('.php')));

            $this->categories[strtolower($categoryKey)] = $className;
        }
    }
}
