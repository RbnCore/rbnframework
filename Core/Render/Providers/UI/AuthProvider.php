<?php
/**
 * Unified Render Hub 🎨
 * AuthRenderProvider: Standardized Identity Suite Renderer.
 */

namespace Rbn\Framework\Core\Render\Providers\UI;

use Rbn\Framework\Core\Base\Web\BaseRender;
use Rbn\Framework\Core\Support\Contracts\Base\BaseRenderInterface;

/**
 * AuthProvider - Identity Suite Layout Renderer 🛰️🔐⚓
 * 
 * Part of the RBN Framework Architecture.
 */
class AuthProvider extends BaseRender implements BaseRenderInterface
{
    /**
     * Internal: Access to the centralized ViewEngine.
     */

    /**
     * Renders an Identity Suite (Auth) view with specialized layouts and assets. 🎨🛰️⚓
     */
    public function render(?string $view, array $data = []): string
    {
        $render = $this->service('render');
        $context = $this->context ?? 'auth';

        // 🎼 Step 1: Resolve Render Layer Handler (Explicit Discovery Mode) 🏛️🎨⚓
        $uiHandler = $this->handler('auth');

        if ($uiHandler && method_exists($uiHandler, 'prepare')) {
            $data = $uiHandler->prepare($this->context ?? 'auth', $view, $data);
        }

        // 🎼 Step 2: RBN Framework SEO & Identity Application 🕵️‍♂️🛰️⚓
        // Use the module identity (pre-loaded in harmony) if not already set.
        $identity = (is_object($this->moduleData) && method_exists($this->moduleData, 'get')) ? $this->moduleData->get('identity', []) : [];
        if (!empty($identity) && !isset($data['branding'])) {
            $data['branding'] = $identity['ui']['slogan'] ?? 'RbnAuth';
        }

        // 🛣️ Step 3: Unified Path Resolution (Context-Aware) ⛩️
        $viewPath = $render->resolveViewPath($view, $data, 'auto', false, $context);
        if (!$viewPath || !file_exists($viewPath)) {
            $viewPath = $render->resolveViewPath('auth', $data, 'auto', false, $context);
        }
        $header = $render->resolveViewPath('Layouts/auth_header', $data, 'auto', false, $context);
        $footer = $render->resolveViewPath('Layouts/auth_footer', $data, 'auto', false, $context);

        // 🎬 Step 4: Final Orchestration 🏛️🏺⚓
        ob_start();

        if ($header && is_string($header) && file_exists($header)) {
            $this->viewEngine()->render($header, $data);
        }

        if ($viewPath && is_string($viewPath)) {
            $this->viewEngine()->render($viewPath, $data);
        }

        if ($footer && is_string($footer) && file_exists($footer)) {
            $this->viewEngine()->render($footer, $data);
        }

        return ob_get_clean();
    }
}
