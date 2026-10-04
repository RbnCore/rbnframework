<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Support\Contracts\Base;

use Rbn\Framework\Core\Support\Contracts\Discovery\DiscoveryInterface;
use Rbn\Framework\Core\Base\Web\Traits\Paginator;
use Rbn\Framework\Core\Render\View;

/**
 * BaseControllerInterface - The Air Traffic Controller 🛫🎻
 * 
 * RBN 3.5 RULES:
 * 1. THIN CONTROLLER: Orchestrate requests to services.
 * 2. FLUENT RENDERING: Must return ViewInstance for chained operations.
 * 3. DISCOVERY PROXIES: Standardized access to all core context hubs.
 */
interface BaseControllerInterface extends DiscoveryInterface
{
    // ====================================================================
    // VIEW CONTEXT & PRESENTATION 🎭
    // ====================================================================

    /**
     * Explicitly set data to the view context.
     */
    public function set(string|array $key, $value = null): self;

    /**
     * Get data from the view context.
     */
    public function get(string $key);

    /**
     * Render a view with data (Fluent API) 🎨
     * Now returns ViewInstance instead of void.
     */
    public function render(string $module, string $view, array $data = []): View;

    /**
     * Paginate a collection of items. 📑
     * 
     * @param array|null $items The raw list to paginate
     * @return Paginator
     */
    public function paginate(?array $items): Paginator;
}
