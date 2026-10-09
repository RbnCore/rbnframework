<?php
declare(strict_types=1);

namespace Rbn\Framework\Core\Support\Definitions\System;

use Rbn\Framework\Core\Base\Data\BaseConfig;

/**
 * NamespaceMap - The Centralized Namespace Blueprint 🧬🗺️
 * 
 * RBN Framework: Provides high-performance categorical namespace mapping.
 * Centralizing this here allows the framework to resolve 
 * components across Core, Bundles, and Project modules without duplication.
 */
class NamespaceMap extends BaseConfig
{
    /**
     * Explicit Definition Identity 🧬🏛️
     */
    protected static ?string $definitionCategory = 'namespace';

    /**
     * Namespace Roots 🌳
     */
    public const FRAMEWORK_PREFIX = 'Rbn\Framework\\';
    public const PROJECT_PREFIX = 'Rbn\Project\\';

    /**
     * Registry Hub Configuration 🏛️
     */
    public const COMPONENT_REGISTRY = \Rbn\Framework\Core\System\Registries\ComponentRegistry::class;

    /**
     * Point-and-Shoot Controller Map 🎯
     * 
     * Key: Prefix used in route definitions (e.g., 'Render', 'Suite', 'Modules')
     * Value: Base Namespace for that prefix.
     */
    public const MAP = [
        // Framework Core Layers (Unified Engine Architecture)
        'Framework' => 'Rbn\\Framework\\',
        'Framework.Core' => 'Rbn\\Framework\\Core\\',
        'Base' => 'Rbn\\Framework\\Core\\Base\\',
        'Database' => 'Rbn\\Framework\\Core\\Database\\',
        'Support' => 'Rbn\\Framework\\Core\\Support\\',
        'Helpers' => 'Rbn\\Framework\\Core\\Support\\Bridges\\Helpers\\',
        'Services' => 'Rbn\\Framework\\Core\\Services\\',
        'Http' => 'Rbn\\Framework\\Core\\Http\\',
        'Render' => 'Rbn\\Framework\\Core\\Render\\',
        'Routes' => 'Rbn\\Framework\\Core\\Routes\\',
        'System' => 'Rbn\\Framework\\Core\\System\\',

        //Framework Bundles
        'Internal' => 'Rbn\\Framework\\Bundles\\Internal\\',
        'Suite' => 'Rbn\\Framework\\Bundles\\RbnSuite\\',

        // Framework RbnSuite Layer
        'RbnSuite' => 'Rbn\\Framework\\Bundles\\RbnSuite\\',
        'RbnAdmin' => 'Rbn\\Framework\\Bundles\\RbnSuite\\RbnAdmin\\',
        'RbnAuth' => 'Rbn\\Framework\\Bundles\\RbnSuite\\RbnAuth\\',
        'RbnStudio' => 'Rbn\\Framework\\Bundles\\RbnSuite\\RbnStudio\\',

        // Framework Packages (RBN Framework Service Hub) 📦
        'Packages' => 'Rbn\\Framework\\Packages\\',

        // Project Layer (Modules & Application) 🚀
        'App' => 'Rbn\\Project\\App\\',
        'Project' => 'Rbn\\Project\\',
        'Project.App' => 'Rbn\\Project\\App\\',
        'Project.Core' => 'Rbn\\Project\\Core\\',
        'Modules' => 'Rbn\\Project\\Modules\\',
        'Project.Modules' => 'Rbn\\Project\\Modules\\',
        'Backend' => 'Rbn\\Project\\Modules\\Backend\\',
        'Frontend' => 'Rbn\\Project\\Modules\\Frontend\\',
    ];
}
