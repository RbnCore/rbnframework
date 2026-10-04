<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Base\Web\Traits\Controller;

use Rbn\Framework\Core\Render\View;
use Rbn\Framework\Core\Base\Web\Traits\Paginator;
use Rbn\Framework\Core\Database\Engine\Collection;

/**
 * ViewTrait - Standard View Rendering Orchestration 🎨🛰️
 * 
 * RBN 3.5: Fulfills the BaseControllerInterface requirements.
 */
trait ViewTrait
{
    /** @var array Request-level view context 🎭 */
    protected array $viewContext = [];

    /** @var View|null Holds the last rendered view explicitly for the Dispatcher 🛡️⚓ */
    protected ?View $activeView = null;

    /**
     * Get the active view (Used by Dispatcher if return is omitted) 🕵️‍♂️
     */
    public function getActiveView(): ?View
    {
        return $this->activeView;
    }

    /**
     * Explicitly set data to the view context (Fluent API) 🖍️⚓
     */
    public function set(string|array $key, $value = null): self
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->viewContext[(string) $k] = $v;
            }
        } else {
            $this->viewContext[$key] = $value;
        }
        return $this;
    }

    /**
     * Import a partial/template for automatic rendering at the end 🚀🛰️⚓
     */
    public function import(string $path): self
    {
        $this->viewContext['__imports'] = array_merge($this->viewContext['__imports'] ?? [], (array) $path);
        return $this;
    }

    /**
     * Get data from the view context 🔍⚓
     */
    public function get(string $key)
    {
        return $this->viewContext[$key] ?? null;
    }

    /**
     * Render a view file with fluent context injection 🎨🛰️
     * 
     * 🎼 RBN 3.5: [POLYMORPHIC RENDER] 🎻🛰️⚓
     * Supports:
     * 1. render('view', [data]) - Sovereign Mode
     * 2. render('module', 'view', [data]) - Legacy Mode
     */
    public function render(string $arg1, $arg2 = [], $arg3 = []): View
    {
        // 🎼 RBN 3.5: Masterpiece Logic - Determine signature type
        if (is_array($arg2)) {
            // Modern Signature: render($view, $data)
            $view = $arg1;
            $data = $arg2;
            $module = $this->module ?? 'Frontend';

            // 🎯 RBN 3.5 Masterpiece: Type is actually the Context (panel, auth, frontend) 🪐🛰️⚓
            // This ensures the correct RenderProvider (like PanelProvider) is triggered.
            $type = $this->context ?? 'frontend';
        } else {
            // Legacy Signature: render($module, $view, $data)
            $module = $arg1;
            $view = (string) $arg2;
            $data = (array) $arg3;
            $type = $module;
        }

        // 🎯 RBN 3.5: [AUTOMATIC HUB DISCOVERY] 🛰️⚓
        // Inject the sovereign module context if it exists on the controller.
        if (!isset($data['module'])) {
            $data['module'] = $module;
        }

        // 🎯 RBN 3.5: Masterpiece Unified View gateway.
        $viewObj = View::render($view, array_merge($this->viewContext, $data), $type);

        // 🛡️ Save to active context for the Dispatcher (Auto-Return Emulator)
        $this->activeView = $viewObj;

        return $viewObj;
    }

    /**
     * Paginate a collection of items 📑⚓
     */
    public function paginate($items, int $perPage = 10): Paginator
    {
        if ($items instanceof Collection) {
            $items = $items->toArray();
        }
        $items = $items ?: [];
        $paginator = new Paginator($items, $this);
        $paginator->perPage($perPage);

        // Auto-inject into view context for lazy developers 🚀🎨
        $this->set('items', $paginator->items())
            ->set('total_items', $paginator->total())
            ->set('pagination', $paginator->links());

        return $paginator;
    }
}
