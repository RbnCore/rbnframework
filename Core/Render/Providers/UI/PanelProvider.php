<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Render\Providers\UI;

use Rbn\Framework\Core\Base\Web\BaseRender;
use Rbn\Framework\Core\Support\Contracts\Base\BaseRenderInterface;

/**
 * PanelProvider - Admin Dashboard Layout Renderer 🛰️📊⚓
 * 
 * Part of the RBN Framework Architecture.
 * Standardized orchestrator for administrative views.
 */
class PanelProvider extends BaseRender implements BaseRenderInterface
{
    /**
     * Renders an Administration (Panel) view with specialized layouts and assets.
     * RBN Framework: [RBN Framework COMPOSITION] 🎨🏛️⚓
     */
    public function render(?string $view, array $data = []): string
    {
        $renderService = $this->service('render');

        // 🎨 RBN Framework: Harmony Hub (BaseRender DNA Context - User, Route, SSoT) 🎻⚓
        $data = array_merge($this->bootHarmony(), $data);

        // 🎼 Step 1: Elite Preparation via Panel Handler 🛰️⚓
        $handler = $this->handler('panel');
        if ($handler) {
            $data = $handler->prepare('panel', $view, $data);
        }

        // 🎼 Step 1.1: Autonomous Navigation & Asset Discovery 🗺️🛰️⚓
        // RBN Framework: Zero-Magic ingestion from ModuleService manifest.
        $targetModule = ($this->module === 'panel' || empty($this->module)) ? 'rbnadmin' : $this->module;
        $targetModule = $data['module_name'] ?? ($data['module'] ?? $targetModule);
        $moduleMetadata = $this->service('module')->resolve((string) $targetModule);

        // 🎻 RBN Framework Asset Distribution: Distribute assets to the AssetService 🛰️⚓
        $viewParts = explode('/', (string) $view);
        $subModuleAction = strtolower(end($viewParts) ?: '');
        $targetSub = self::toCamelCase((string) ($data['sub_module'] ?? ($this->sub_module ?? $subModuleAction)));

        $subModuleData = $this->service('module')->findSubModule($moduleMetadata, (string) $targetSub);

        if (!empty($subModuleData['assets'])) {
            $assetService = $this->service('asset');
            $assetService->prepare($subModuleData['assets'], 'panel');

            // 🎼 RBN Framework: [SYNC] Re-render assets into data context to include module assets under core assets.
            $data['headerAssets'] = $assetService->render('header');
            $data['footerAssets'] = $assetService->render('footer');
        }

        // 🎻 Step 1.2: Resolve and Render Breadcrumbs via Single Entry Provider 🪐🚀
        $data['breadcrumbs'] = $this->provider('breadcrumb')->resolveAndRender($data, $view);

        // 🎼 Step 2: RBN Framework View & Layout Resolution 👁️🛰️
        // Smart Discovery: Finds the module-specific view or fallbacks to admin_index.
        $viewPath = $renderService->resolveViewPath($view, $data, 'auto', false, 'panel');

        // Dashboard Fallback: If 'dashboard' requested but not found, try 'admin_index'.
        if ($view === 'dashboard' && (!$viewPath || !file_exists($viewPath))) {
            $viewPath = $renderService->resolveViewPath('admin_index', ['module' => 'dashboard'], 'auto', false, 'panel');
        }

        // Standard Admin Layouts (RbnAdmin Package)
        $headerLayout = $renderService->resolveViewPath('RbnAdmin/Layouts/panel_header', $data, 'auto', false, 'panel');
        $footerLayout = $renderService->resolveViewPath('RbnAdmin/Layouts/panel_footer', $data, 'auto', false, 'panel');

        // 🛡️ Guard: Ensure main view exists
        if (!$viewPath || !file_exists($viewPath)) {
            $this->handleMissing($view ?? 'unknown');
        }

        ob_start();

        // 🛡️ RBN Framework Fragment Guard:
        if ($fragment = $this->resolveFragment($viewPath, $data)) {
            return $fragment;
        }

        // 🏛️ 3. Composition: [Header -> Content -> Footer] 🎨
        // Wrapped in the RBN Framework Buffer.

        // 🎼 Render Panel Header (with SEO & Header Assets)
        if ($headerLayout && file_exists($headerLayout)) {
            $this->viewEngine()->render($headerLayout, $data);
        }

        // 🎼 Render Dashboard Content
        $this->viewEngine()->render($viewPath, $data);

        // 🧬 [RBN Framework] RBN Framework AUTONOMOUS IMPORTS 🛰️⚓
        // (FOOTER'DAN ÖNCE, VİEW BİTER BİTMEZ EKLENİR!)
        if (isset($data['__imports']) && !empty($data['__imports'])) {
            $partialProvider = $this->rbn->service('render')->provider('partial');
            echo $partialProvider->renderImports($data['__imports'], $data);
        }

        // 🎼 Render Panel Footer (with Footer Assets)
        if ($footerLayout && file_exists($footerLayout)) {
            $this->viewEngine()->render($footerLayout, $data);
        }

        $output = ob_get_clean();

        return $this->normalizeHtmlUrls((string) $output);
    }
}
