<?php

namespace Rbn\Framework\Core\System\Discovery\Clusters\Structure\Namespace;

use Rbn\Framework\Core\System\Discovery\Base\BaseDiscoveryContext;
use Rbn\Framework\Core\Base\Services\BaseService;
use Rbn\Framework\Core\System\Discovery\Clusters\Structure\Folder\FolderContext;

/**
 * NamespaceContext - Handles logical PSR-4 path resolution for a module.
 * 
 * RBN 3.5 Masterpiece: Autonomous namespace context.
 * Inherits full DNA (Shield, Cache, Normalize) from BaseDiscoveryContext.
 */
class NamespaceContext extends BaseDiscoveryContext
{
    private string $baseNamespace;
    private string $moduleName;

    public function __construct(string $baseNamespace, string $moduleName, ?BaseService $rbn = null)
    {
        parent::__construct($rbn);
        $this->baseNamespace = rtrim($baseNamespace, '\\');
        $this->moduleName = $moduleName;
    }

    /**
     * Get the base namespace of the module.
     * e.g., 'Modules\Bio' or 'Rbn\Framework\Modules\Syshub'
     */
    public function getBase(): string
    {
        return $this->baseNamespace;
    }

    /**
     * Resolve an exact full class name for a given component.
     * Strictly enforces the folder structure (e.g., Services/XService).
     * 
     * @param string $layer The architectural layer (Services, Models, Helpers)
     * @param string $className The specific class name (e.g., BioService)
     * @return string The fully qualified class name to attempt loading
     */
    public function resolveClass(string $layer, string $className): string
    {
        // Example: Modules\Bio\Services\BioService
        return $this->baseNamespace . '\\' . $layer . '\\' . $className;
    }

    /**
     * Resolve an exact full class name expecting it in the root of the module.
     * (Legacy fallback support)
     * 
     * @param string $className The specific class name (e.g., BioService)
     * @return string
     */
    public function resolveRootClass(string $className): string
    {
        // Example: Modules\Bio\BioService
        return $this->baseNamespace . '\\' . $className;
    }

    /**
     * Resolve an array of possible FQCNs considering case variations.
     * This is useful for Linux (production) environments where file names are case-sensitive.
     * 
     * @param string $layer The architectural layer (Services, Models, Helpers)
     * @param string $className The specific class name (e.g., SyshubService)
     * @return array List of possible fully qualified class names
     */
    public function resolveVariations(string $layer, string $className): array
    {
        return [
            $this->resolveClass($layer, $className),                 // Expected: Services\SyshubService
            $this->resolveClass($layer, lcfirst($className)),        // Fallback: Services\syshubService
            $this->resolveRootClass($className),                     // Legacy: \SyshubService
            $this->resolveRootClass(lcfirst($className))             // Legacy Fallback: \syshubService
        ];
    }

    /**
     * Get the ModuleData manifesto class name for this module. 🛰️🏛️⚓
     * RBN 3.5: Prioritizes new 'Models\ModuleData' but falls back to legacy 'ModuleConfig'.
     */
    public function moduleDataClass(): string
    {
        // 1. New Masterpiece Standard: Data\ModuleData 🏛️
        $dataStandard = $this->baseNamespace . '\\' . FolderContext::DATA . '\\' . FolderContext::MODULE_DATA;
        if (class_exists($dataStandard)) {
            return $dataStandard;
        }

        // 2. Backward Compatibility: Models\ModuleData 🏛️
        $modelStandard = $this->baseNamespace . '\\' . FolderContext::MODELS . '\\' . FolderContext::MODULE_DATA;
        if (class_exists($modelStandard)) {
            return $modelStandard;
        }

        // 3. Legacy Fallback: Configs\ModuleConfig (For backward compatibility during migration) 🕰️
        return $this->baseNamespace . '\\Configs\\ModuleConfig';
    }
}
