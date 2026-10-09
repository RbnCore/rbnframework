<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Render\Services;

use Rbn\Framework\Core\Base\Services\BaseService;

/**
 * RenderService - The Grand Orchestrator for all rendering operations. 🎭🎻⚓
 *
 * RBN Framework: Autonomous Orchestration Hub.
 * This service coordinates data collection (Handlers) and dispatches 
 * the final payload to specialized Providers.
 */
class RenderService extends BaseService
{
    /**
     * Boot the Render Service 🚀
     */
    public function boot(): void
    {
        // 🎼 RBN Framework: DNA ready.
    }

    /**
     * Unified Render Entry Point 🚀
     * 
     * Orchestrates the entire pipeline: 
     * 1. Resolves Type/Alias 
     * 2. Prepares Context (Handlers)
     * 3. Dispatches to Provider
     * 
     * @return mixed The rendered content (HTML, XML, JSON etc.)
     */
    public function render(string $type, array $params = []): mixed
    {
        $view = $params['view'] ?? null;
        $data = $params['data'] ?? $params; // Support both ['data' => []] and direct array

        // 1. Context Preparation (Data Gathering via Handlers) 🎡
        $preparedData = $this->prepareContext($type, (string) $view, $data);

        // 2. Provider Resolution & Execution 🏗️
        $provider = $this->provider($type);
        if (!$provider) {
            $this->abort(500, "Render Provider not found for type: {$type}");
        }

        // 🎯 RBN Framework: returning instead of echoing.
        // Special Case: Delegation to RBN Framework AssetService 🎼
        if ($type === 'asset') {
            return $this->assetService->render($view, $preparedData);
        }

        // Special Case: Delegation to RBN Framework SeoService 🏹
        if ($type === 'seo' || $type === 'meta') {
            return $this->seoService->render($view, $preparedData);
        }

        // [RBN Framework] RBN Framework: Passing the render type (context) to the provider.
        return $provider->render($view, $preparedData, $type);
    }

    /**
     * Data Collection Hub 📥 (Dynamic Resolution via Discovery)
     */
    protected function prepareContext(string $type, string $view, array $data): array
    {
        // Special Case: Delegation to RBN Framework AssetService 🎼
        if ($type === 'asset') {
            $this->assetService->prepare($view, $data['appContext'] ?? 'frontend');
            return []; // Data is stored in Service state.
        }

        try {
            /** @var \Rbn\Framework\Core\Base\Web\BaseRender $context */
            $context = $this->handler($type);
            return $context ? $context->prepare($type, $view, $data) : $data;
        } catch (\Throwable $e) {
            return $data;
        }
    }

    /**
     * Compatibility Bridge: Resolve a physical view path via the Resolver Cluster. 👁️🛰️
     * 
     * @param string $path Logical view path
     * @param array $data View data (for context)
     * @param string $source Source type (auto, suite, project)
     * @param bool $checkFile Verify physical existence (Legacy Arg)
     * @param string $appContext The context (frontend, panel, auth, static)
     * @return string|null
     */
    public function resolveViewPath(string $path, array $data = [], string $source = 'auto', bool $checkFile = false, string $appContext = 'frontend'): ?string
    {
        // 🎼 RBN Framework Nomenclature Normalization 🎹
        if ($appContext === 'admin') {
            $appContext = 'panel';
        }

        // 🎼 RBN Framework: Delegate to the RBN Framework View Resolver cluster (Pass module if present)
        $options = ['context' => $appContext];
        if (isset($data['module'])) {
            $options['module'] = $data['module'];
        }

        return $this->resolver('view')->resolve($path, $options);
    }

    /**
     * Unified Access to the View Engine Cluster 🪐🚀
     */
    public function viewEngine()
    {
        return $this->cluster('view_engine');
    }

    /**
     * Legacy Alias: Support for RBN bundles 🕰️
     */
    public function engine()
    {
        return $this->viewEngine();
    }
}
