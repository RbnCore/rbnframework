<?php

namespace Rbn\Framework\Core\Support\Contracts\Base;

/**
 * BaseRenderInterface - Unified Rendering Contract 🏹
 * 
 * Part of the RBN Modern Hub.
 * Defines a flexible entry point for all rendering operations (HTML, XML, Metadata).
 */
interface BaseRenderInterface
{
    /**
     * Executes the rendering logic for the specific type.
     * 
     * @param string|null $view Optional view file path or name (for visual renders)
     * @param array $data Pre-prepared data package from Context
     * @return mixed Return value varies by provider (string, array, or void)
     */
    public function render(?string $view, array $data = []): mixed;
}
