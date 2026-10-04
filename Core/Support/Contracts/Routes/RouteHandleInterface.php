<?php

namespace Rbn\Framework\Core\Support\Contracts\Routes;

/**
 * RouteHandleInterface - The Controller Route Assistant Contract 🛡️🛣️
 */
interface RouteHandleInterface
{
    /**
     * Get the current request URI.
     */
    public function current(): string;

    /**
     * Generate a URL for a route name or path.
     */
    public function url(string $nameOrPath, $params = [], ?string $panel = null, ?string $module = null): string;

    /**
     * Get a simple URL (Proxy to url).
     */
    public function to(string $path, ?string $panel = null, ?string $module = null): string;

    /**
     * Check if the given path is active.
     */
    public function isActive(string $path, ?string $panel = null, ?string $module = null, bool $exact = false): bool;

    /**
     * Perform a redirect to a route.
     */
    public function redirect(string $path, ?string $panel = null, ?string $module = null): void;

    /**
     * Send an alert notification.
     */
    public function alert(string $type, string $message, ?string $path = null, ?string $title = null, array $data = []): void;

    /**
     * Standardized result handler for complex operations. 🛡️⚓
     * Automatically coordinates with Alert and Response engines.
     * Optionally supports 'method' guard before processing result.
     */
    public function handleResult($resultOrCondition, $optionsOrPath = '/'): void;
}
