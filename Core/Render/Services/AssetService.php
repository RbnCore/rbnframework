<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Render\Services;

use Rbn\Framework\Core\Base\Services\BaseService;

/**
 * AssetService - Unified Asset Orchestrator 🎼🛰️⚓
 * 
 * RBN Framework: Kuyruk yönetimi ve orkestrasyon.
 */
class AssetService extends BaseService
{
    /**
     * Main Entry Point: Orchestrates the Logic (Handler) and Assembly (Compiler). 🎢⚓
     */
    public function prepare($source, string $appContext = 'frontend', int $priority = 10): self
    {
        $this->handler('assetBuilder')->prepare($appContext, $source);
        return $this;
    }

    /**
     * Context-Aware Orchestration: Prepares all base assets for a specific app context. 🛰️⚓
     */
    public function prepareContext(string $appContext, array $options = []): self
    {
        return $this->prepare($appContext, $appContext);
    }

    /**
     * Unified Render Entry Point: Orchestrates the Provider 🎨
     */
    public function render(?string $view = null, array $data = []): string
    {
        if ($view === 'header') return $this->renderHeader();
        if ($view === 'footer') return $this->renderFooter();

        if (empty($data['styles']) && empty($data['scripts'])) {
            $data = $this->handler('assetBuilder')->build();
        }

        return $this->provider('asset')->render($view, $data);
    }

    /**
     * Unified Render: Header (Styles & Head Scripts). 🏷️
     */
    public function renderHeader(): string
    {
        $built = $this->handler('assetBuilder')->build();
        
        $headScripts = [];
        foreach ($built['scripts'] as $script) {
            if ($script['renderInHead'] === true) {
                $headScripts[] = $script;
            }
        }

        return $this->render(null, [
            'styles' => $built['styles'],
            'scripts' => $headScripts
        ]);
    }

    /**
     * Unified Render: Footer (Bottom Scripts). ⚡
     */
    public function renderFooter(): string
    {
        $built = $this->handler('assetBuilder')->build();
        
        $footerScripts = [];
        foreach ($built['scripts'] as $script) {
            if ($script['renderInHead'] === false) {
                $footerScripts[] = $script;
            }
        }

        return $this->render(null, [
            'scripts' => $footerScripts
        ]);
    }

    /**
     * Queue Management: Add a single style.
     */
    public function addStyle(string $path, int $priority = 10): void
    {
        $this->handler('assetBuilder')->addStyle($path, $priority);
    }

    /**
     * Queue Management: Add a single script.
     */
    public function addScript(string $path, array $attrs = [], bool $inHead = false, int $priority = 10): void
    {
        $this->handler('assetBuilder')->addScript($path, $attrs, $inHead, $priority);
    }

    /**
     * Font Selector: Delegates resolution to AssetBuilder. 🏛️🎨⚓
     */
    public function addFont(string $name, int $priority = 15): self
    {
        $this->handler('assetBuilder')->addFont($name, $priority);
        return $this;
    }
}

