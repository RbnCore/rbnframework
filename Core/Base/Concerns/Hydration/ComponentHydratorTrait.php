<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Base\Concerns\Hydration;

use Rbn\Framework\Core\Base\Attributes\SubModule;
use Rbn\Framework\Core\Base\Attributes\Module;
use Rbn\Framework\Core\Support\Contracts\Base\BaseControllerInterface;

/**
 * ComponentHydratorTrait - The "Awakening" Motor 🧬💉⚓
 * 
 * RBN Framework: Materializes physical satellites (Services, Providers, Models) from attribute names.
 * Ensures the DNA is fully hydrated with live objects.
 */
trait ComponentHydratorTrait
{
    /**
     * Materializes all defined satellites into physical properties. 💉
     * 
     * RBN Framework: Orchestrates Service, Provider, and Model Awakening.
     */
    protected function hydrateComponents(): void
    {
        if ($this->rbn && $activeController = $this->rbn->activeController()) {
            $reflection = new \ReflectionClass($activeController);

            // 🎼 RBN Framework: [HIERARCHICAL DISCOVERY] 🏛️🛰️⚓
            // Search class hierarchy for SubModule and Module attributes.
            $subModuleAttr = null;
            $moduleAttr = null;

            $current = $reflection;
            while ($current) {
                if (!$subModuleAttr) {
                    $subAttrs = $current->getAttributes(SubModule::class);
                    if (!empty($subAttrs))
                        $subModuleAttr = $subAttrs[0]->newInstance();
                }

                if (!$moduleAttr) {
                    $modAttrs = $current->getAttributes(Module::class);
                    if (!empty($modAttrs))
                        $moduleAttr = $modAttrs[0]->newInstance();
                }

                if ($subModuleAttr && $moduleAttr)
                    break;
                $current = $current->getParentClass();
            }

            // 🎻 RBN Framework: [RBN Framework RESOLUTION STRATEGY] ⚖️🚀⚓
            // Explicit attribute names are Mandatory, Derived names are Optional.
            $serviceType = ($subModuleAttr?->manager ?? $moduleAttr?->manager) ? 'manager' : 'service';
            $explicitService = $subModuleAttr?->service ?? $moduleAttr?->service ?? $subModuleAttr?->manager ?? $moduleAttr?->manager;
            $serviceName = $explicitService ?? $this->sub_module ?? $this->module ?? null;

            if ($serviceName) {
                // RBN Framework: Resiliency Guard 🛡️⚓
                // Derived names are optional to prevent Discovery Engine crashes on entry modules.
                $mandatory = (bool) $explicitService;
                $resolved = $this->discover()->resolve($this, $serviceType, $serviceName, $mandatory);

                if ($resolved) {
                    if ($serviceType === 'manager') {
                        $this->manager = $resolved;
                    } else {
                        $this->service = $resolved;
                    }
                }
            }

            // Provider & Model Awakening (Following the same resiliency pattern)
            $explicitProvider = $subModuleAttr?->provider;
            $providerName = $explicitProvider ?? $serviceName;

            $this->provider = $this->discover()->resolve($this, 'provider', $providerName, (bool) $explicitProvider);

            $explicitModel = $subModuleAttr?->model;
            $modelName = $explicitModel ?? $subModuleAttr?->entity ?? $serviceName;

            $this->model = $this->discover()->resolve($this, 'model', $modelName, (bool) $explicitModel);

            $this->handler = $subModuleAttr?->handler ? $this->discover()->resolve($this, 'handler', $subModuleAttr->handler, true) : null;
            
            // 🎼 RBN Framework: [MODAL DNA] - Synchronize modal view if defined
            if ($subModuleAttr?->modal && property_exists($this, 'modalView')) {
                $this->modalView = $subModuleAttr->modal;
            }

            $activeUnit = $this->manager ?? $this->service ?? null;
            if ($activeUnit && ($this instanceof BaseControllerInterface)) {
                $this->activeService = $activeUnit;
                
                // 🎼 RBN Framework: [DNA BRIDGE] Synchronize satellites with the active unit
                if ($this->provider && property_exists($activeUnit, 'provider')) {
                    $activeUnit->provider = $this->provider;
                }
                if ($this->handler && property_exists($activeUnit, 'handler')) {
                    $activeUnit->handler = $this->handler;
                }
                if ($this->model && property_exists($activeUnit, 'model')) {
                    $activeUnit->model = $this->model;
                }
            }
        }
    }
}
