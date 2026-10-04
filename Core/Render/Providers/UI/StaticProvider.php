<?php

namespace Rbn\Framework\Core\Render\Providers\UI;

use Rbn\Framework\Core\Base\Web\BaseRender;
use Rbn\Framework\Core\Support\Contracts\Base\BaseRenderInterface;

/**
 * StaticProvider - Simplified UI Layout Renderer 🛰️📄⚓
 * 
 * Part of the RBN 3.5 "Masterpiece" Architecture.
 */
class StaticProvider extends BaseRender implements BaseRenderInterface
{

    /**
     * Renders standard static view.
     */
    public function render(?string $view, array $data = []): string
    {
        // Hub Standard: Resolve logical name to physical path with 'static' context
        $path = $this->rbn->service('render')->resolveViewPath($view, $data, 'auto', false, 'static');

        if (!$path || !file_exists($path)) {
            $this->handleMissing($view ?? 'unknown');
        }

        ob_start();
        $this->viewEngine()->render($path, $data);
        return ob_get_clean();
    }
}
