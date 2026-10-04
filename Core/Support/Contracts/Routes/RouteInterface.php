<?php

namespace Rbn\Framework\Core\Support\Contracts\Routes;

/**
 * RouteInterface - The Grand Routing Contract 🛡️🛣️⚓
 */
interface RouteInterface
{
    /**
     * Add a GET route.
     */
    public function get(string $path, $handler): self;

    /**
     * Add a POST route.
     */
    public function post(string $path, $handler): self;

    /**
     * Match the current request to a registered route.
     */
    public function match(?string $method = null, ?string $uri = null): ?array;

    /**
     * Execute the matched route handler.
     */
    public function dispatch(): void;

    /**
     * Get the URL generator component.
     */
    public function url(): object;
}
