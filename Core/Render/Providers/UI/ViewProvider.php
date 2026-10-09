<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Render\Providers\UI;

use Rbn\Framework\Core\Base\Web\BaseRender;
use Rbn\Framework\Core\Support\Contracts\Base\BaseRenderInterface;

/**
 * ViewProvider - Core UI Component Runner 🛰️👁️⚓
 * 
 * RBN Framework Architecture.
 * Responsible for rendering and including physical view files.
 * Enhanced with Harmony DNA Injection: Automatically provides core context to views.
 */
class ViewProvider extends BaseRender implements BaseRenderInterface
{
    /**
     * Unified Render Entry Point (Interface)
     * 
     * @param string|null $view The view path/identifier
     * @param array $data Data for the view
     * @param string $appContext The context (frontend, admin, etc.)
     * @return mixed Usually buffered string content.
     */
    public function render(?string $view, array $data = [], string $appContext = 'frontend'): mixed
    {
        ob_start();
        $this->include($view, $data, 'auto', $appContext);
        $output = ob_get_clean();

        // 🧬 [RBN Framework] RBN Framework AUTONOMOUS IMPORTS 🛰️⚓
        if (isset($data['__imports']) && !empty($data['__imports'])) {
            $partialProvider = $this->rbn->service('render')->provider('partial');
            $output .= $partialProvider->renderImports($data['__imports'], $data);
        }

        return $output;
    }

    /**
     * Harmony Hub: Include a view file with automated DNA injection 🎼🧬⚓
     * Supports both .php and .rbn.php (ViewEngine).
     */
    public function include(string $path, array $data = [], string $source = 'auto', string $appContext = 'frontend'): void
    {
        $path = str_replace(['.rbn.php', '.php'], '', $path);

        // 1. Use Resolver to find physical path 🕵️‍♂️⚓
        $fullPath = $this->viewResolver()->resolve($path, ['context' => $appContext]);

        if ($fullPath && file_exists($fullPath)) {
            
            // 🎼 RBN Framework: [HARMONY HUB INJECTION] 🎻🪐⚓
            // Lazy-booting the harmony DNA to avoid constructor loops.
            $harmonyData = array_merge($this->bootHarmony(), $data);

            // 2. Standard .rbn.php View Engine check 🎨
            if (str_ends_with($fullPath, '.rbn.php')) {
                $this->cluster('view_engine')->render($fullPath, $harmonyData);
                return;
            }

            // 3. Raw PHP Execution with DNA Extract 🧬
            extract($harmonyData, EXTR_SKIP);
            require $fullPath;

        } else {
            $this->handleMissing($path);
        }
    }

    /**
     * Resolve the physical file path (Delegated to ViewResolver).
     */
    public function resolve(string $path, array $data = [], string $source = 'auto', bool $strictRbn = false, string $appContext = 'frontend'): ?string
    {
        return $this->viewResolver()->resolve($path, ['context' => $appContext]);
    }
}
