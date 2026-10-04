<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Base\Concerns\Identity;

use Rbn\Framework\Core\Base\Attributes\SubModule;
use Rbn\Framework\Core\Base\Attributes\Module;

/**
 * IdentityExtractorTrait - The Sovereign Attribute Decoder 🕵️‍♂️⚓
 * 
 * RBN 3.5: Orchestrates the extraction of Module and SubModule attribute metadata.
 * Bridges the gap between raw attributes and SovereignIdentity.
 */
trait IdentityExtractorTrait
{
    /**
     * Extracts identity information from current context or active controller. 🕵️‍♂️
     * 
     * RBN 3.5: Synchronizes module and sub-module attributes directly to DNA.
     * Supports Hierarchical (Recursive) discovery from the current component ($this).
     */
    protected function extractIdentity(): void
    {
        // 🎼 RBN 3.5: [SOVEREIGN HIERARCHY DISCOVERY] 🏛️🛰️⚓
        // Priority: Current instance attributes > Active controller delegation
        $target = ($this instanceof \Rbn\Framework\Core\Base\Web\BaseController) ? $this : ($this->rbn?->activeController() ?? $this);
        $reflection = new \ReflectionClass($target);

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

        // 🎻 RBN 3.5: [DNA SYNCHRONIZATION] 🎻🛰️⚓
        if ($moduleAttr) {
            $this->module ??= static::toPascalCase((string) $moduleAttr->name);
            $this->panel ??= $moduleAttr->panel;
            $this->context ??= $moduleAttr->context;
            $this->moduleData ??= $moduleAttr->data;

            if ($moduleAttr->service) {
                $this->activeService ??= $this->service($moduleAttr->service);
            }
        }

        if ($subModuleAttr) {
            $repoKey = $subModuleAttr->repository ?? null;
            $serviceKey = $subModuleAttr->service ?? null;

            if ($repoKey !== null && $repoKey !== '') {
                $this->activeService = $this->repository($repoKey);
            } elseif ($serviceKey !== null && $serviceKey !== '') {
                $this->activeService = $this->service($serviceKey);
            }
            $subId = $subModuleAttr->entity ?? $subModuleAttr->name ?? null;
            $this->sub_module ??= $subId ? static::toPascalCase((string) $subId) : null;

            // 🎼 RBN 3.5: [AUTONOMOUS PROPERTY INJECTION] 💉🛰️⚓
            $this->entityName ??= $subModuleAttr->entityName ?? $subModuleAttr->entity ?? null;
            $this->bulkInputKey ??= $subModuleAttr->bulkInputKey ?? null;
            $this->modalView ??= $subModuleAttr->modal ?? null;
        }

        // Final Metadata Resolution (Masterpiece Standard)
        $data = $this->moduleData ?? $subModuleAttr?->data ?? $moduleAttr?->data ?? (method_exists($target, 'getModuleData') ? $target->getModuleData() : ($target->moduleData ?? null));

        if (is_string($data) && class_exists($data) && !($this instanceof $data)) {
            $data = new $data();
        }

        $this->moduleData = $data;
    }
}
