<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Support\Bridges\Helpers\Library;

/**
 * PathHelper - Universal Path Morphology & Symmetry Hub (Zero Hardcoded) 🪐✨🏛️
 * 
 * RBN 3.5: "Masterpiece Architecture" - Dynamic Discovery & Branding.
 * Centralizes regex logic for all Discovery clusters and Asset TAGGING.
 * 
 * NO HARDCODED TOKENS: Fetches @fw/, @project/ e.t.c from FrameworkIdentity.
 */
class PathHelper
{
    /**
     * Morphs any path into a Systematic Symmetrical structure.
     * Logic: If path starts with a proxy token and is followed by type/module, flips to module/type.
     * Example: @fw/css/RbnShield/error.css -> @fw/RbnShield/css/error.css
     */
    public function toSymmetric(string $path): string
    {
        $setup = \Rbn\Framework\Core\Render\Configs\AssetConfig::PROXY_SETUP;
        $tokens = array_column($setup, 'token');
        $cleanPath = ltrim($path, '/');

        // Create a dynamic regex pattern from discovered tokens
        $tokenPattern = '';
        if (!empty($tokens)) {
            $escapedTokens = array_map(fn($t) => preg_quote($t, '/'), $tokens);
            $tokenPattern = '(' . implode('|', $escapedTokens) . ')?';
        }

        $regex = '/^' . $tokenPattern . '(css|js|img|fonts|json|views)\/([^\/]+)\/(.*)$/i';

        if (preg_match($regex, $cleanPath, $matches)) {
            $token = $matches[1]; // @fw/ or empty
            $type = $matches[2];
            $module = $matches[3];
            $subPath = $matches[4];

            return $token . $module . '/' . $type . '/' . $subPath;
        }

        // Handle Alias Colon Mapping (RbnShield:Errors/view)
        if (preg_match('/^([^\/:]+):(.*)$/i', $path, $matches)) {
            return $matches[1] . '/' . $matches[2];
        }

        return $path;
    }

    /**
     * Generates all possible physical path candidates for a systematically structured path.
     * Useful for Resolver clusters when searching files on disk.
     */
    public function getPhysicalCandidates(string $realPath): array
    {
        $candidates = [];

        // Logic: Try to reverse-morph Module/Type/SubPath back into standard physical structures
        // Format: ModuleName/type/Path (RbnShield/css/error.css)
        if (preg_match('/^([^\/]+)\/(css|js|img|fonts|json|views)\/(.*)$/i', $realPath, $matches)) {
            $module = $matches[1];
            $type = $matches[2];
            $subPath = $matches[3];

            // Candidate 1: type/module/path (Standard Type-prefixed)
            $candidates[] = $type . '/' . $module . '/' . $subPath;

            // Candidate 2: module/path (Flat mixed-content module like RbnKit)
            $candidates[] = $module . '/' . $subPath;
        }

        // Fallback: Always include the original path as a candidate
        $candidates[] = $realPath;

        return array_unique($candidates);
    }

    /**
     * Cleans proxy tokens from the path using dynamic definitions.
     */
    public function cleanProxy(string $path): string
    {
        $setup = \Rbn\Framework\Core\Render\Configs\AssetConfig::PROXY_SETUP;
        $tokens = array_column($setup, 'token');
        return str_replace($tokens, '', $path);
    }

    /**
     * Checks if a path belongs to the framework via proxy token or proxy path.
     */
    public function isFramework(string $path): bool
    {
        $setup = \Rbn\Framework\Core\Render\Configs\AssetConfig::PROXY_SETUP;

        $fwToken = $setup['framework']['token'] ?? '@fw/';
        $fwProxy = ($setup['framework']['path'] ?? 'framework-assets') . '/';

        return str_starts_with($path, $fwToken) || str_starts_with(ltrim($path, '/'), $fwProxy);
    }
}
