<?php

namespace Rbn\Framework\Core\Routes\Engine\Providers;

use Rbn\Framework\Core\Base\Services\BaseService;
use Rbn\Framework\Core\Support\Contracts\Routes\RouteHandleInterface;
use Rbn\Framework\Core\Support\Contracts\Base\BaseServiceInterface;

/**
 * RouteHandle - Contextual Route Assistant ($this->Route) 🛡️🛣️
 * 
 * High-performance controller assistant. Interface-driven and trait-free.
 * Synchronized with the official HTTP Request, Response, and Alert engines.
 */
class RouteHandle extends BaseService implements RouteHandleInterface
{
    protected $controller;
    protected $routeManager;

    public function __construct($controller)
    {
        $this->controller = $controller;
        $this->routeManager = self::get()->service('route');
    }

    /**
     * Get the current request URI 🛰️
     */
    public function current(): string
    {
        return request()->path() ?? '/';
    }

    /**
     * URL Generation logic 🧭
     */
    public function url(string $nameOrPath, $params = [], ?string $panel = null, ?string $module = null): string
    {
        if (!is_array($params)) {
            $module = $panel;
            $panel = $params;
            $params = [];
        }

        return $this->routeManager->url()->url($nameOrPath, $params, $panel, $module);
    }

    public function to(string $path, ?string $panel = null, ?string $module = null): string
    {
        return $this->url($path, [], $panel, $module);
    }

    public function isActive(string $path, ?string $panel = null, ?string $module = null, bool $exact = false): bool
    {
        return $this->routeManager->url()->isActive($path, $panel, $module, $exact);
    }

    /**
     * Perform a redirect 🛡️⚓
     */
    public function redirect(string $path, ?string $panel = null, ?string $module = null): void
    {
        $url = $this->url($path, [], $panel, $module);
        response()->redirect($url);
    }

    /**
     * Send an Alert and handle output automatically 🔔🛰️
     */
    public function alert(string $type, string $message, ?string $path = null, ?string $title = null, array $data = []): void
    {
        $url = $path ? $this->url($path) : null;
        alert()->send($type, $message, $url, $title, $data);
    }

    /**
     * RBN Framework Result Handler 🧩🎻
     * Automatically coordinates with Alert and Response engines based on request context.
     * Optionally supports 'method' guard before processing result.
     */
    public function handleResult($resultOrCondition, $optionsOrPath = '/'): void
    {
        // 1. Options Resolution
        $options = is_array($optionsOrPath) ? $optionsOrPath : ['path' => is_string($optionsOrPath) ? $optionsOrPath : '/'];

        // 🛡️ Optional Guard: Ensure the request method matches (if specified in options)
        if (isset($options['method']) && !request()->isMethod($options['method'])) {
            $this->alert('error', $options['error_message'] ?? 'Geçersiz İstek (Method Mismatch)', $options['path'] ?? '/');
            exit;
        }

        // 1.5. Condition & Success Detection
        $success = false;
        $data = null;
        $message = null;

        if (is_array($resultOrCondition)) {
            $success = $resultOrCondition['success'] ?? false;
            $data = $resultOrCondition['data'] ?? [];
            if (isset($resultOrCondition['display'])) {
                $data['display'] = $resultOrCondition['display'];
            }
            $message = $resultOrCondition['message'] ?? null;
        } elseif ($resultOrCondition instanceof BaseServiceInterface) {
            $success = $resultOrCondition->success();
        } else {
            $success = (bool) $resultOrCondition;
        }

        // 2. Options Resolution
        $options = is_array($optionsOrPath) ? $optionsOrPath : ['path' => is_string($optionsOrPath) ? $optionsOrPath : '/'];

        $path = $options['path'] ?? '/';
        $successPath = $options['success_path'] ?? $path;
        $errorPath = $options['error_path'] ?? $path;

        // Ayarlar (options) içinden gelen data'yı da hesaba kat (Eğer condition'dan gelmediyse)
        if (empty($data) && isset($options['data'])) {
            $data = $options['data'];
        }

        $finalMsg = $success
            ? ($options['success_message'] ?? $message ?? 'İşlem başarıyla tamamlandı.')
            : ($options['error_message'] ?? $message ?? 'İşlem sırasında bir hata oluştu.');

        // 3. Callback Hooks
        if ($success && isset($options['callback']) && is_callable($options['callback'])) {
            call_user_func($options['callback']);
        } elseif (!$success && isset($options['error_callback']) && is_callable($options['error_callback'])) {
            call_user_func($options['error_callback']);
        }

        // 4. Execution (AlertService handles the AJAX vs Normal logic) 🛰️
        $targetPath = $success ? $successPath : $errorPath;
        $this->alert($success ? 'success' : 'error', $finalMsg, $targetPath, null, $data ?? []);
    }
}
