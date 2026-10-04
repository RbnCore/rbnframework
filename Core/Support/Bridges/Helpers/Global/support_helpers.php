<?php

if (!function_exists('collect')) {
    /**
     * Create a new collection from the given items 🔱🧬
     */
    function collect($items = []): \Rbn\Framework\Core\Database\Engine\Collection
    {
        return new \Rbn\Framework\Core\Database\Engine\Collection($items);
    }
}

if (!function_exists('path_symmetric')) {
    /**
     * Morphs any path into a Systematic Symmetrical structure 🦸‍♂️🏛️
     */
    function path_symmetric(string $path): string
    {
        static $pathHelper = null;
        if ($pathHelper === null) {
            $pathHelper = new \Rbn\Framework\Core\Support\Bridges\Helpers\Library\PathHelper();
        }
        return $pathHelper->toSymmetric($path);
    }
}

if (!function_exists('path_candidates')) {
    /**
     * Generates all possible physical path candidates for a systematically structured path 🪐
     */
    function path_candidates(string $path): array
    {
        static $pathHelper = null;
        if ($pathHelper === null) {
            $pathHelper = new \Rbn\Framework\Core\Support\Bridges\Helpers\Library\PathHelper();
        }
        return $pathHelper->getPhysicalCandidates($path);
    }
}
