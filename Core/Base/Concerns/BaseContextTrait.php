<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Base\Concerns;

use Rbn\Framework\Core\Base\Services\BaseService;
use Rbn\Framework\Core\System\Discovery\Engine\DiscoveryEngine;
use Rbn\Framework\Core\Base\Concerns\Identity\SovereignIdentity;
use Rbn\Framework\Core\Base\Concerns\Identity\IdentityExtractorTrait;
use Rbn\Framework\Core\Base\Concerns\Hydration\ComponentHydratorTrait;
use Rbn\Framework\Core\Base\Concerns\Data\ResolvesProjectConfigTrait;

/**
 * BaseContextTrait - The Root DNA of all Contexts 🧬⚓
 * 
 * RBN 3.5: Centralized property holder and bootstrapper for all context traits.
 * Now provides the Universal map() Orchestrator for all Components.
 */
trait BaseContextTrait
{
    /** RBN 3.5 Masterpiece: Sovereign Specialized DNA 🏗️🧬⚓ */
    use SovereignIdentity, IdentityExtractorTrait, ComponentHydratorTrait, ResolvesProjectConfigTrait;

    /**
     * Lazy-loading Discovery Engine Accessor 🛰️⚙️
     * Ensures the engine is only initialized when actually needed.
     */
    protected function discover(): DiscoveryEngine
    {
        if ($this->discover === null) {
            // Prevent DiscoveryEngine from trying to boot itself
            if ($this instanceof DiscoveryEngine) {
                return $this;
            }

            // 🛡️ B-08: `discover()` bir LAZY GETTER; `beforeBoot()` gibi erken
            // kancalardan cagrilirsa `$this->rbn` henuz atanmamis olabilir.
            // O durumda motor `rbn=null` ile kuruluyor ve tum cozumlemeler
            // bos DNA ile calisiyordu. `bootBaseContext()` zaten ayni atamayi
            // yaptigi icin NORMAL boot sirasinda bu satir HIC BIR SEYI
            // DEGISTIRMEZ; yalniz riskli sira kapanir.
            $this->rbn ??= BaseService::get();

            $this->discover = DiscoveryEngine::instance($this->rbn);
        }
        return $this->discover;
    }

    /**
     * Boot the Core DNA (The Hub Sync) 🧬⚓
     */
    protected function bootBaseContext(): void
    {
        // 🎼 RBN 3.5: [ATOMIC DNA AWAKENING] 🧬🏙️⚓
        $this->rbn = BaseService::get();

        // 🎼 RBN 3.5: [SOVEREIGN REGISTRATION] 🏙️🛰️⚓
        if ($this instanceof \Rbn\Framework\Core\Support\Contracts\Base\BaseControllerInterface) {
            $this->rbn?->setActiveController($this);
        }

        // 🎼 RBN 3.5: [SOVEREIGN INHERITANCE] - Sync DNA with the Active Controller 🧠🛰️⚓
        if ($this->rbn && $activeController = $this->rbn->activeController()) {

            // 🎻 RBN 3.5: [SOVEREIGN IDENTITY EXTRACTION] ⚖️🛰️⚓
            $this->extractIdentity();

            // 🎼 RBN 3.5: [SATELLITE MATERIALIZATION / HYDRATION] 🛰️⚓靶
            if ($this === $activeController) {
                $this->hydrateComponents();
            }

            $this->module ??= $activeController->module ?? null;
            $this->panel ??= $activeController->panel ?? null;
            $this->context ??= $activeController->context ?? null;
            $this->sub_module ??= $activeController->sub_module ?? null;
            $this->hub ??= $activeController->hub ?? null;
        }

        // 🎼 RBN 3.5: Eager DNA Initialization 🎻🛰️
        $this->discover = $this->discover();

        if ($this->rbn) {
            $this->module ??= $this->rbn->module ?? null;
            $this->sub_module ??= $this->rbn->sub_module ?? null;
            $this->hub ??= $this->rbn->hub ?? null;
        }

        // 🎼 RBN 3.5: [PROJECT IDENTITY AWAKENING] - Global SSoT Injection 🚀🛰️⚓
        $projectData = \Rbn\Framework\Core\System\Kernel\Bootstrap::getAppContext('project_data');

        // 1. Veritabanı Verilerini Atla (Ana Kimlik) 🏛️
        if (!empty($projectData)) {
            $this->appName = (string) ($projectData['project_name'] ?? '');
            $this->projectKey = (string) ($projectData['project_key'] ?? '');
            $this->projectGroup = (string) ($projectData['project_group'] ?? $projectData['project_key'] ?? '');
            $this->appVersion = (string) ($projectData['version'] ?? '1.0');
        }

        // 🎨 If this is a Web Controller, share it with the View engine automatically.
        if ($this instanceof \Rbn\Framework\Core\Base\Web\BaseController) {
            $this->set('appName', $this->appName);
            $this->set('projectKey', $this->projectKey);
            $this->set('projectGroup', $this->projectGroup);
            $this->set('appVersion', $this->appVersion);
        }
    }

    /* ==========================================================================
       [ THE ORCHESTRATOR ] - Universal Discovery & Mapping 🏛️🎻🛰️
       ========================================================================== */

    /**
     * UNIVERSAL MAP ORCHESTRATOR 🏛️🎻🛰️
     * 
     * RBN 3.5 Masterpiece: Single entry point for Registry Population.
     * Consolidates manual mappings with autonomous module and project discovery.
     * Physically defined here to provide inheritance for ALL RBN Components.
     */
    public function map(string $type, array $manual = []): array
    {
        // 🎼 RBN 3.5: Masterpiece Unification - Using the centralized Discovery Engine accessor
        $folders = $this->discover()->folders();
        $auto = [];

        if (method_exists($folders, 'find')) {
            $auto[] = $folders->find($type, 'modules') ?? [];
            $auto[] = $folders->find($type, 'project') ?? [];
        }

        return array_merge_recursive($manual, ...$auto);
    }
}
