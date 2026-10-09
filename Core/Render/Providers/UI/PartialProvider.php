<?php

namespace Rbn\Framework\Core\Render\Providers\UI;

use Rbn\Framework\Core\Base\Web\BaseRender;
use Rbn\Framework\Core\Support\Contracts\Base\BaseRenderInterface;

/**
 * PartialProvider - Standard UI Partial Renderer 🛰️🧩⚓
 *
 * Part of the RBN Framework Architecture.
 */
class PartialProvider extends BaseRender implements BaseRenderInterface
{

    /**
     * Renders a partial view.
     */
    public function render(?string $view, array $data = []): string
    {
        // Hub Standard: Resolve logical name to physical path. Partials use 'auto' context.
        $path = $this->rbn->service('render')->resolveViewPath($view, $data, 'auto', false, $data['appContext'] ?? 'frontend');

        if (!$path || !file_exists($path)) {
            $this->handleMissing($view ?? 'unknown');
        }

        // 🛡️ RBN Framework Fragment Awareness:
        // We render everything into a buffer, but if a fragment is requested,
        // we strictly return only that section.
        ob_start();
        $this->viewEngine()->render($path, $data);
        $fullOutput = ob_get_clean();

        if (isset($data['__fragment'])) {
            return $this->layoutResolver()->getSection($data['__fragment']);
        }

        return $fullOutput;
    }

    /**
     * RBN Framework: Autonomous Import Orchestration 🛰️⚓
     * Renders multiple partials into a single HTML block.
     */
    public function renderImports(array $imports, array $data = []): string
    {
        $output = '';
        foreach ($imports as $import) {
            $output .= "\n<!-- [FRAMEWORK] Autonomous Import: {$import} -->\n";
            $output .= $this->render($import, $data);
        }
        return $output;
    }
}
