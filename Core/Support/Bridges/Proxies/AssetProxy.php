<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Support\Bridges\Proxies;

use Rbn\Framework\Core\Base\Patterns\BaseProxy;
use Rbn\Framework\Core\Render\Configs\AssetConfig;

/**
 * AssetProxy - Fluent Asset Resolver 🎭⚓
 * 
 * RBN Framework: Part of the decoupled Proxy Hub.
 * Specialized for scoped asset URL generation based on the centralized DNA.
 */
class AssetProxy extends BaseProxy
{
    /** @var string Current resolution scope (project, framework, module) */
    protected string $scope = 'project';

    /**
     * Create a new scoped Asset Proxy. 🛰️⚓
     * 
     * @param string $type The discovery target type
     * @param string $scope Initial resolution scope
     */
    public function __construct(string $type, string $scope = 'project')
    {
        // 🎼 RBN Framework: Symmetric initialization - Artık $rbn dışarıdan paslanmaz.
        parent::__construct($type);
        $this->scope = $scope;
    }

    /**
     * Magic Scoping Entry: Switch resolution scope fluently. 🛰️
     * 
     * Example: $asset->framework->css('style.css')
     */
    public function __get(string $name)
    {
        $setup = AssetConfig::PROXY_SETUP;
        if (isset($setup[$name]) || $name === 'project' || $name === 'framework') {
            return new self($this->type, $name);
        }

        // Fallback: Let the parent trait handle standard discovery if needed
        return parent::__get($name);
    }

    /**
     * Final Resolution: Generate the versioned URL string. 🎯
     * Leverages RenderFactory and AssetResolver motor via the Hub.
     */
    public function __call(string $name, array $args): string
    {
        $file = $args[0] ?? '';
        if (empty($file))
            return '';

        // 1. Resolve Resolver Component via inherited DNA Cluster 🛰️⚓
        $resolver = $this->cluster('asset');

        // 2. Prepare resolution tokens based on current instance scope
        $path = $file;
        $setup = AssetConfig::PROXY_SETUP;
        if (isset($setup[$this->scope]['token'])) {
            $path = $setup[$this->scope]['token'] . ltrim($file, '/');
        }

        // 3. Resolve versioned URL
        return $resolver->resolveUrl($path, $name, AssetConfig::VERSION);
    }
}
