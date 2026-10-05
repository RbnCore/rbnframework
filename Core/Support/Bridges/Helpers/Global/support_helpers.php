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

if (!function_exists('next_version')) {
    /**
     * Standart sürüm sayacı: geçerli sürümden sonraki sürümü verir.
     * Sayı ELLE yazılmaz; kural `.agents/rules/versioning.md` içinde tek yerde.
     */
    function next_version(string $version): string
    {
        return \Rbn\Framework\Core\Support\Bridges\Helpers\Library\Version::next($version);
    }
}

if (!function_exists('version_is_valid')) {
    /**
     * Sürüm biçimi `A.B.C` kuralına uyuyor mu?
     */
    function version_is_valid(string $version): bool
    {
        return \Rbn\Framework\Core\Support\Bridges\Helpers\Library\Version::isValid($version);
    }
}

if (!function_exists('version_compare')) {
    /**
     * İki sürümü sayısal karşılaştırır (-1, 0, 1).
     */
    function version_compare(string $a, string $b): int
    {
        return \Rbn\Framework\Core\Support\Bridges\Helpers\Library\Version::compare($a, $b);
    }
}

if (!function_exists('version_parse')) {
    /**
     * Sürümü `{major, minor, patch}` dizisine ayırır.
     *
     * @return array{major:int,minor:int,patch:int}
     */
    function version_parse(string $version): array
    {
        return \Rbn\Framework\Core\Support\Bridges\Helpers\Library\Version::parse($version);
    }
}

if (!function_exists('version_initial')) {
    /**
     * Yeni uygulamaların başlangıç sürümü: `0.1.1`.
     */
    function version_initial(): string
    {
        return \Rbn\Framework\Core\Support\Bridges\Helpers\Library\Version::initial();
    }
}
