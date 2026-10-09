<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\System\Paths\Contexts;

use Rbn\Framework\Core\System\Discovery\Clusters\Structure\Namespace\NamespaceContext;
use Rbn\Framework\Core\Support\Definitions\System\NamespaceMap;

/**
 * ModuleContext - The RBN Framework Path & Namespace Orchestrator 🛰️🪐⚓
 * 
 * RBN Framework: Unified context for module file paths and namespaces.
 * Handles deterministic resolution without directory scanning.
 */
class ModuleContext
{
    private string $name;
    private string $source;
    private string $root;
    private ?string $base = null;

    public function __construct(string $name, string $source, string $root, ?string $base = null)
    {
        $this->name = $name;
        $this->source = $source;
        $this->root = $root;
        $this->base = $base;
    }

    /**
     * Get physical root path of the module 📂
     */
    public function root(?string $path = null): string
    {
        return $this->root . ($path ? DIRECTORY_SEPARATOR . ltrim($path, '/\\') : '');
    }

    /**
     * Get the logical Namespace Context for this module 🧬
     * [RBN Framework] Returns a standardized NamespaceContext for architectural symmetry.
     */
    public function namespaces(): NamespaceContext
    {
        if ($this->base !== null) {
            return new NamespaceContext($this->base, $this->name);
        }

        $name = ucfirst($this->name);
        $map = NamespaceMap::MAP;

        // 1. Resolve via official Map 🗺️
        $prefix = $map[$this->source] ?? null;

        // 2. Convention Fallback 🎻
        if (!$prefix) {
            $prefix = match ($this->source) {
                'Project', 'Modules' => $map['Modules'] ?? 'Rbn\\Project\\Modules\\',
                default              => "Rbn\\Framework\\Bundles\\" . ucfirst($this->source) . "\\"
            };
        }

        $baseNamespace = $prefix . $name;

        // 🎼 RBN Framework: [RBN Framework NAMESPACE MERGE] 🧬⚓
        // Prevent doubling if the prefix already contains the module name.
        $prefixTrimmed = rtrim($prefix, '\\');
        if (str_ends_with($prefixTrimmed, $name) || str_ends_with($prefixTrimmed, "Rbn{$name}")) {
            $baseNamespace = $prefix;
        }

        return new NamespaceContext($baseNamespace, $this->name);
    }

    /**
     * Get the module's views directory 👁️
     * [RBN Framework] Hybrid: Checks local module storage THEN central framework resources.
     */
    public function views(?string $path = null): string
    {
        // 1. Try Local Module Storage
        $localPath = $this->root(is_dir($this->root('Resources' . DIRECTORY_SEPARATOR . 'Views')) ? 'Resources' . DIRECTORY_SEPARATOR . 'Views' : 'Views');

        if (!is_dir($localPath)) {
            // 2. Try Central Framework Resources (Nokta Atışı Fallback) 🎯
            $centralPath = \Rbn\Framework\Core\System\Paths\Paths::frameworkRoot() . DIRECTORY_SEPARATOR . 'Resources' . DIRECTORY_SEPARATOR . 'Views' . DIRECTORY_SEPARATOR . $this->name;
            if (is_dir($centralPath)) {
                $localPath = $centralPath;
            }
        }

        return $localPath . ($path ? DIRECTORY_SEPARATOR . ltrim($path, '/\\') : '');
    }

    /**
     * Get the module's controllers directory 🎮
     */
    public function controllers(?string $path = null): string
    {
        return $this->root('Controllers' . ($path ? DIRECTORY_SEPARATOR . ltrim($path, '/\\') : ''));
    }

    /**
     * Get the module's models directory 🧱
     */
    public function models(?string $path = null): string
    {
        return $this->root('Models' . ($path ? DIRECTORY_SEPARATOR . ltrim($path, '/\\') : ''));
    }

    /**
     * Get the module's handlers directory ⚙️
     */
    public function handlers(?string $path = null): string
    {
        return $this->root('Handlers' . ($path ? DIRECTORY_SEPARATOR . ltrim($path, '/\\') : ''));
    }

    /**
     * Get the module's providers directory 🛰️
     */
    public function providers(?string $path = null): string
    {
        return $this->root('Providers' . ($path ? DIRECTORY_SEPARATOR . ltrim($path, '/\\') : ''));
    }

    /**
     * Get the module's assets directory 🎨
     */
    public function assets(?string $path = null): string
    {
        $target = is_dir($this->root('Resources' . DIRECTORY_SEPARATOR . 'Assets')) ? 'Resources' . DIRECTORY_SEPARATOR . 'Assets' : 'Assets';
        return $this->root($target . ($path ? DIRECTORY_SEPARATOR . ltrim($path, '/\\') : ''));
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSource(): string
    {
        return $this->source;
    }
}
