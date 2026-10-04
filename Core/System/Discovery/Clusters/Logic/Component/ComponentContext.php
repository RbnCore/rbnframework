<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\System\Discovery\Clusters\Logic\Component;

use Rbn\Framework\Core\System\Discovery\Base\BaseDiscoveryContext;
use Rbn\Framework\Core\Base\Attributes\SubModule;
use Rbn\Framework\Core\System\Discovery\Engine\DiscoveryEngine;
use ReflectionClass;

use Rbn\Framework\Core\Support\Definitions\System\ComponentTypes;

/**
 * ComponentContext - The Smart Discovery DNA 🧬🕵️‍♂️🧠⚓
 * 
 * RBN 3.5 Masterpiece: Autonomous component context.
 * Performs deterministic path calculation and suffix parsing with Zero-Tolerance reporting.
 * Inherits full DNA (Shield, Cache, Normalize) from BaseDiscoveryContext.
 */
class ComponentContext extends BaseDiscoveryContext
{
    /**
     * Unified Type Map - The Framework VIP Suffixes (Delegated to ComponentTypes Single Truth) 🎭⚙️
     */
    protected array $typeMap;

    public function __construct()
    {
        parent::__construct();
        $this->typeMap = ComponentTypes::typeMap();
    }

    /**
     * Resolve a component instance intelligently 🎁🛰️
     * 
     * @param object $context The calling component context (Service/Model/Controller)
     * @param string $type The component category (model, service, etc.)
     * @param string|null $targetProperty Optional explicit target property name
     * @param bool $mandatory Whether to trigger a diagnostic on failure
     * @return object|null
     */
    public function resolve(object $context, string $type, ?string $targetProperty = null, bool $mandatory = true): ?object
    {
        $typeSuffix = ucfirst($type);

        // 🎹 1. Niyet Okuma (Identify Intent) 🐾
        $property = $targetProperty ?? "target" . $typeSuffix;
        $fallback = $targetProperty ?? $type;

        // 🎼 RBN 3.5: [SOVEREIGN ATTRIBUTE DISCOVERY] ⚖️🛰️⚓
        // Check for #[SubModule] attribute on the context or its heritage chain.
        $name = null;
        $attribute = $this->getSubModuleAttribute($context);

        if ($attribute && property_exists($attribute, $type) && $attribute->{$type}) {
            $name = $attribute->{$type};
        }

        // RBN 3.5 Masterpiece: Sovereign Visibility Guard (Legacy Properties) 🛡️⚓
        if (!$name) {
            foreach ([$property, $fallback] as $key) {
                if ($key && property_exists($context, $key)) {
                    $reflection = new \ReflectionProperty($context, $key);
                    $name = $reflection->getValue($context);
                    if ($name)
                        break;
                }
            }
        }

        // Final Fallback to raw string names if no valid property was found
        $name ??= $targetProperty ?? $fallback ?? null;

        if (!$name) {
            return null;
        }

        $name = (string) $name;

        // 🎹 2. Keşif Stratejisi & Cache ⚡📦
        $cacheKey = "component.context:" . md5(get_class($context) . $type . $name);

        $fqcn = $this->cacheDiscovery($cacheKey, function () use ($context, $name, $type) {
            // A. Birinci Öncelik: Tip + Alias Korumalı Arama (Örn: service.rbn.test) 🛡️
            $hit = DiscoveryEngine::instance()->namespace()->find("{$type}.{$name}", $type);

            // B. İkinci Öncelik: Yalın Alias/İsim Araması 🏛️⚓
            if (!$hit) {
                $hit = DiscoveryEngine::instance()->namespace()->find($name, $type);
            }

            // C. Üçüncü Yol: Otonom Tahmin (Context-Based Deterministic Fallback) 🎯
            if (!$hit) {
                $hit = $this->calculateDeterministicPath($context, $name, $type);
            }

            return $hit;
        });

        // 🎻 3. Instance Üretimi & Güvenlik (The Awakening & Shield) 🛡️⚓
        if ($fqcn && class_exists($fqcn)) {
            return new $fqcn();
        }

        // 🛡️ RBN 3.5: Auto-Shield Diagnostic Gateway (Zero-Tolerance)
        if ($mandatory) {
            $this->triggerDiagnostic($name, $type);
        }

        return null;
    }

    /**
     * Resolve via Magic Suffix (The Automated Gateway) 🪄✨
     * 
     * @param object $context
     * @param string $name
     * @return object|null
     */
    public function resolveSuffix(object $context, string $name): ?object
    {
        foreach ($this->typeMap as $suffix => $type) {
            if (str_ends_with($name, $suffix)) {
                $target = substr($name, 0, -strlen($suffix));
                return $this->resolve($context, $type, $target, true);
            }
        }

        return null;
    }

    /**
     * Pinpoint Strategy: Calculate the physical class path based on local context 📐🎯
     * 
     * @param object $context
     * @param string $name
     * @param string $type
     * @return string|null
     */
    protected function calculateDeterministicPath(object $context, string $name, string $type): ?string
    {
        $currentFqcn = get_class($context);
        $typePlural = ComponentTypes::pluralize($type);

        $namespaceParts = explode('\\', $currentFqcn);
        array_pop($namespaceParts); // Remove Class name

        // 🎼 RBN 3.5: Masterpiece Layer-Aware Discovery 🛰️⚓
        // If we are in a known layer (Controllers/Services/Repositories/etc.), move up to Bundle Root.
        $lastLayer = end($namespaceParts);
        $knownLayers = array_merge(['Controllers'], array_map('ucfirst', array_values(ComponentTypes::PLURAL_MAP)));
        if (in_array($lastLayer, $knownLayers, true)) {
            array_pop($namespaceParts);
        }

        $baseNamespace = implode('\\', $namespaceParts);
        $targetName = ucfirst($name) . ucfirst($type);

        // Strategy A: Local Layer (e.g., Services/AuthService, Repositories/SdSeriesRepository)
        $localFqcn = "{$baseNamespace}\\{$typePlural}\\{$targetName}";
        if (class_exists($localFqcn)) {
            return $localFqcn;
        }

        // Strateji B: Yan Katman (Örn: AuthService)
        $siblingFqcn = "{$baseNamespace}\\{$targetName}";
        if (class_exists($siblingFqcn)) {
            return $siblingFqcn;
        }

        return null;
    }

    /**
     * Retrieve the #[SubModule] attribute from the context or its parent hierarchy. 🧬🛰️⚓
     * Supports Heritage Inheritance for the 'data' parameter.
     */
    protected function getSubModuleAttribute(object $context): ?SubModule
    {
        $reflection = new ReflectionClass($context);
        $attribute = null;

        // 🎻 RBN 3.5: [HERITAGE LOOKUP] 🏹🏙️⚓
        // Climb the inheritance chain to find the SubModule or Module identity.
        $current = $reflection;
        while ($current) {
            $attrs = $current->getAttributes(SubModule::class);
            if (!empty($attrs)) {
                $instance = $attrs[0]->newInstance();

                // If this is the first one found, capture it
                if ($attribute === null) {
                    $attribute = $instance;
                }

                // Check for #[Module] in the correct Base namespace
                $moduleAttrs = $current->getAttributes(\Rbn\Framework\Core\Base\Attributes\Module::class);

                if (!empty($moduleAttrs)) {
                    $moduleInstance = $moduleAttrs[0]->newInstance();
                    if (property_exists($moduleInstance, 'data')) {
                        $attribute->data = $moduleInstance->data;
                    }
                }

                if ($attribute->data !== null)
                    break;
            }
            $current = $current->getParentClass();
        }

        return $attribute;
    }
}

