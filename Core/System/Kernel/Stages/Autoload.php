<?php

namespace Rbn\Framework\Core\System\Kernel\Stages;

use Rbn\Framework\Core\System\Paths\Paths;
use Rbn\Framework\Core\System\Kernel\Kernel;
use Rbn\Framework\Core\System\Kernel\Base\BaseStage;
use Rbn\Framework\Core\System\Discovery\Clusters\Logic\Definition\Definition;

/**
 * Autoload - Registers Composer and custom Module autoloader.
 */
class Autoload extends BaseStage
{
    /**
     * Stage Execution Handler (Kernel Pipeline Entry Point) ⚓
     */
    public function handle(Kernel $kernel): void
    {
        self::boot($kernel);
    }

    /**
     * SSoT Autoload Engine: Boots and registers all project & framework PSR-4 mappings. 🚀
     */
    public static function boot(?Kernel $kernel = null): \Composer\Autoload\ClassLoader
    {
        $workspace = Paths::workspace();
        $project = Paths::project();
        $frameworkRoot = Paths::frameworkRoot();
        $frameworkPackages = Paths::framework()->packages();
        $frameworkBundles = Paths::framework()->bundles();

        $vendorPath = $frameworkRoot . DIRECTORY_SEPARATOR . 'vendor';

        // 1. Composer Autoload
        /** @var \Composer\Autoload\ClassLoader $loader */
        $loader = require $vendorPath . '/autoload.php';

        // [RBN 3.0] Project Root Autoloader (Derived from North Star Prefix)
        $projectPrefix = Definition::get('namespace', 'PROJECT_PREFIX') ?: 'Rbn\Project\\';
        if ($project && $project->root() && is_dir($project->root())) {
            $loader->addPsr4($projectPrefix, $project->root() . '/');
            $loader->addPsr4($projectPrefix . 'Core\\', $project->root() . '/Core/');
            $loader->addPsr4($projectPrefix . 'App\\', $project->root() . '/App/');
            $loader->addPsr4($projectPrefix . 'Modules\\', $project->root() . '/Modules/');
        }

        // [RBN 3.0] Framework Root Security (v5.0 Micro-Kernel)
        $frameworkPrefix = Definition::get('namespace', 'FRAMEWORK_PREFIX') ?: 'Rbn\Framework\\';
        if ($frameworkPrefix) {
            $loader->addPsr4($frameworkPrefix, $frameworkRoot . '/');

            // High-Speed Hub Resolution (Modular Architecture)
            $loader->addPsr4($frameworkPrefix . 'Core\\', $frameworkRoot . DIRECTORY_SEPARATOR . 'Core/');
            $loader->addPsr4($frameworkPrefix . 'Resources\\', $frameworkRoot . DIRECTORY_SEPARATOR . 'Resources/');
            $loader->addPsr4($frameworkPrefix . 'Packages\\', $frameworkPackages . DIRECTORY_SEPARATOR);
            $loader->addPsr4($frameworkPrefix . 'Bundles\RbnSuite\\', $frameworkBundles . DIRECTORY_SEPARATOR . 'RbnSuite/');
            $loader->addPsr4($frameworkPrefix . 'Bundles\Internal\\', $frameworkBundles . DIRECTORY_SEPARATOR . 'Internal/');
        }

        if ($kernel !== null) {
            $kernel->set('loader', $loader);
        }

        return $loader;
    }
}
