<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\System\Discovery\Engine\Drivers;

use Rbn\Framework\Core\Base\Web\BaseController;
use Rbn\Framework\Core\Base\Attributes\Module as ModuleAttr;
use Rbn\Framework\Core\Support\Bridges\Traits\NormalizationTrait;
use Rbn\Framework\Core\System\Paths\Paths;
use Rbn\Framework\Core\System\Discovery\Clusters\Structure\Folder\FolderContext;
use ReflectionClass;

/**
 * ModuleDataDriver - Structural Metadata & Attribute Resolution Engine 🏺🏙️⚓
 * 
 * RBN Framework: Responsible for resolving and merging
 * module structural metadata from physical ModuleData manifests and Attributes.
 * Encapsulated purely inside Core/System/Discovery/Engine/Drivers cluster.
 */
class ModuleDataDriver
{
    use NormalizationTrait;

    /** @var object|null The Framework Orchestrator context */
    protected ?object $rbn = null;

    /** @var array Shared module metadata cache 🧠⚡ */
    private static array $moduleCache = [];

    /** @var array<string, ReflectionClass> Static reflection cache 🧠⚡ */
    private static array $reflectionCache = [];

    public function __construct(?object $rbn = null)
    {
        $this->rbn = $rbn;
    }

    private function getReflection(string $class): ReflectionClass
    {
        return self::$reflectionCache[$class] ??= new ReflectionClass($class);
    }

    /**
     * Resolve module identity (Name & Source) from a Class Name 🕵️‍♂️🛰️⚓
     * RBN Framework: Converts a ModuleData class into a standard Identity Pack.
     */
    public function resolveFromClass(string $bundleClass): array
    {
        if (!class_exists($bundleClass)) {
            return ['name' => '', 'source' => 'Backend'];
        }

        $reflect = $this->getReflection($bundleClass);

        // 🎼 RBN Framework: [RBN Framework DISCOVERY] - Read from #[Bundle] Attribute 🧬⚓
        $attributes = $reflect->getAttributes(\Rbn\Framework\Core\Base\Attributes\Bundle::class);
        if (!empty($attributes)) {
            $attr = $attributes[0]->newInstance();
            $ctx = strtolower((string) ($attr->context ?? ''));
            $source = ($ctx === 'frontend') ? 'Frontend' : (($ctx === 'backend') ? 'Backend' : ucfirst($ctx));
            return ['name' => $attr->name, 'source' => $source];
        }

        // 1. Detect Source (Modules\{Source}\...)
        $nsParts = explode('\\', $reflect->getNamespaceName());
        $modulesIndex = array_search('Modules', $nsParts);
        $source = ($modulesIndex !== false && isset($nsParts[$modulesIndex + 1])) ? $nsParts[$modulesIndex + 1] : 'Backend';

        // 2. Detect Name (from CONFIG or ClassName)
        $instance = new $bundleClass();
        $name = defined("$bundleClass::CONFIG") ? ($instance::CONFIG['module_name'] ?? null) : null;
        if (!$name) {
            $name = str_replace('ModuleData', '', $reflect->getShortName());
        }

        return ['name' => (string) $name, 'source' => (string) $source];
    }

    /**
     * Resolve base module information via Explicit Attributes 🏺✨
     */
    public function getModuleInfo(string $module, string $moduleSource = 'auto'): array
    {
        $cacheKey = "{$module}:{$moduleSource}";
        if (isset(self::$moduleCache[$cacheKey])) {
            return self::$moduleCache[$cacheKey];
        }

        $info = $this->getBaseDefaults($module);

        // ⚓ RBN Framework [RBN Framework REDIRECT] 🏛️🛰️⚓
        if (strtolower($module) === 'dashboard') {
            $module = 'rbnadmin';
            $moduleSource = 'Suite';
        }

        // 🎼 RBN Framework: [RBN Framework DISCOVERY] 🎯🛰️⚓
        $activeController = $this->rbn?->activeController();
        $targetReflection = null;

        if ($activeController instanceof BaseController && ($activeController->module === $module)) {
            $targetReflection = $this->getReflection(get_class($activeController));
        } else {
            $context = Paths::module($module, $moduleSource);
            $baseNamespace = $context->namespaces()->getBase();
            $controllerClass = "{$baseNamespace}\\Controllers\\" . ucfirst($module) . 'Controller';

            if (class_exists($controllerClass)) {
                $targetReflection = $this->getReflection($controllerClass);
            }
        }

        // Öznitelikleri (Attributes) Ayıkla 🧬⚓
        if ($targetReflection) {
            $attributes = $targetReflection->getAttributes(ModuleAttr::class);
            if (!empty($attributes)) {
                $attr = $attributes[0]->newInstance();

                $data = $attr->data;
                if (is_string($data) && class_exists($data)) {
                    $configObj = new $data();
                    $fullMap = $configObj->CONFIG['map'] ?? [];
                    $info['map'] = $fullMap;
                    $info['sub_modules'] = $fullMap['sub_modules'] ?? [];
                }

                $info = array_merge($info, [
                    'module_name' => $attr->name,
                    'module_service' => $attr->service,
                    'module_title' => $attr->title ?? $attr->name,
                    'module_icon' => $attr->icon,
                    'module_description' => $attr->description,
                    'data' => $attr->data
                ]);
            }
        }

        // 🎻 [DATA SYNC] - RBN Framework FALLBACK 🛡️✨
        if (empty($info['data'])) {
            $context = Paths::module($module, $moduleSource);
            $baseNamespace = $context->namespaces()->getBase();
            
            $dataClass  = "{$baseNamespace}\\" . FolderContext::DATA . "\\" . FolderContext::MODULE_DATA;
            $modelClass = "{$baseNamespace}\\" . FolderContext::MODELS . "\\" . FolderContext::MODULE_DATA;
            $guessedDataClass = class_exists($dataClass) ? $dataClass : $modelClass;

            if (class_exists($guessedDataClass)) {
                $info['data'] = $guessedDataClass;
            }
        }

        // 🎻 [DATA SYNC] - Merge CONFIG or #[Bundle] from the declared Data Class.
        $dataClass = $info['data'] ?? null;
        if ($dataClass && class_exists($dataClass)) {
            $ref = $this->getReflection($dataClass);

            $bundleAttrs = $ref->getAttributes(\Rbn\Framework\Core\Base\Attributes\Bundle::class);
            if (!empty($bundleAttrs)) {
                $bundle = $bundleAttrs[0]->newInstance();
                $map = $bundle->map ?? [];
                $identity = $map['identity'] ?? [];
                $info = array_merge($info, [
                    'name'        => $identity['name'] ?? ($map['module_name'] ?? $bundle->name),
                    'title'       => $identity['title'] ?? ($map['title'] ?? ($bundle->title ?? ucfirst($bundle->name))),
                    'icon'        => $identity['icon'] ?? ($map['icon'] ?? ($bundle->icon ?? '')),
                    'description' => $identity['description'] ?? ($map['description'] ?? ($bundle->description ?? '')),
                    'map'         => $map,
                    'sub_modules' => $bundle->submodules ?: ($map['sub_modules'] ?? [])
                ]);
            } else {
                $config = $ref->getConstant('CONFIG') ?: [];
                if (isset($config['identity'])) {
                    $info = array_merge($info, $config['identity']);
                }
                $info = array_merge($info, $config);
            }

            $controllerMap = [];
            $buildControllerMap = function (array $items) use (&$buildControllerMap, &$controllerMap) {
                foreach ($items as $slug => $sub) {
                    if (isset($sub['controller'])) {
                        $controllerMap[$sub['controller']] = (string) $slug;
                    } else {
                        $ctrl = self::toPascalCase((string) $slug) . 'Controller';
                        $controllerMap[$ctrl] = (string) $slug;
                    }
                    if (!empty($sub['sub_modules'])) {
                        $buildControllerMap($sub['sub_modules']);
                    }
                }
            };
            $buildControllerMap($info['sub_modules'] ?? []);
            $info['controller_map'] = $controllerMap;
        }

        return self::$moduleCache[$cacheKey] = $info;
    }

    /**
     * Recursive search for a sub-module key within the module tree 🔍🛰️⚓
     */
    public function deepSearch(array $tree, string $target, ?string $normalizedTarget = null): ?array
    {
        $normalizedTarget ??= strtolower(str_replace(['-', '_'], '', $target));

        foreach ($tree as $key => $item) {
            $normalizedKey = strtolower(str_replace(['-', '_'], '', (string) $key));
            if ($normalizedKey === $normalizedTarget || (string) $key === $target) {
                return $item;
            }

            if (!empty($item['sub_modules'])) {
                if ($found = $this->deepSearch($item['sub_modules'], $target, $normalizedTarget)) {
                    return $found;
                }
            }
        }

        if ($activeCtrl = $this->rbn?->activeController()) {
            $class = is_object($activeCtrl) ? get_class($activeCtrl) : (string) $activeCtrl;
            $ctrlShort = $this->getReflection($class)->getShortName();
            foreach ($tree as $key => $item) {
                $ctrlName = $item['controller'] ?? (self::toPascalCase((string) $key) . 'Controller');
                if ($ctrlName === $ctrlShort) {
                    return $item;
                }
            }
        }

        return null;
    }

    /**
     * Fallback default values for module metadata 🛡️
     */
    private function getBaseDefaults(string $module): array
    {
        return [
            'module_title' => ucfirst(str_replace(['-', '_'], ' ', $module)),
            'module_description' => '',
            'module_icon' => '📄',
            'sub_modules' => []
        ];
    }
}
