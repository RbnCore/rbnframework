<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Render\Providers\UI;

use Rbn\Framework\Core\Base\Web\BaseRender;
use Rbn\Framework\Core\Support\Contracts\Base\BaseRenderInterface;

/**
 * AjaxProvider - Sovereign Fragment Renderer ✂️🛰️⚓
 * 
 * RBN 3.5 "Masterpiece" Architecture.
 * Specialized provider for layout-free rendering.
 * Used for Modals, Tooltips, and AJAX partial updates.
 */
class AjaxProvider extends BaseRender implements BaseRenderInterface
{
    /**
     * Renders a raw view fragment without any layout wrappers.
     * 🎹 Sovereign Execution: Just the content, no noise.
     */
    public function render(?string $view, array $data = []): string
    {
        $renderService = $this->service('render');

        // 🎼 Step 1: Resolve the physical path for the fragment
        // We use 'panel' as context default to ensure admin paths are found, 
        // but 'frontend' fallback is integrated in Resolver.
        $appContext = $this->context ?? 'panel';
        $viewPath = $renderService->resolveViewPath($view, $data, 'auto', false, $appContext);

        // 🛡️ Guard: Fail-safe check
        if (!$viewPath || !file_exists($viewPath)) {
            $this->handleMissing($view ?? 'unknown');
            return ""; 
        }

        // 📽️ Step 2: Fragment-Only Buffering 🧬🛰️⚓
        ob_start();
        $this->viewEngine()->render($viewPath, $data);

        // 🧬 [RBN 3.5] MASTERPIECE AUTONOMOUS IMPORTS ✂️🛰️⚓
        // Ensure that imported fragments are also included in the AJAX response.
        if (isset($data['__imports']) && !empty($data['__imports'])) {
            $partialProvider = $this->service('render')->provider('partial');
            if ($partialProvider && method_exists($partialProvider, 'renderImports')) {
                echo $partialProvider->renderImports($data['__imports'], $data);
            }
        }

        return ob_get_clean();
    }
}
