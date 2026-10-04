<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\System\Discovery\Engine\Drivers;

use Rbn\Framework\Core\System\Discovery\Engine\DiscoveryEngine;

/**
 * MetadataDriver - Specialized Constant & Config Discovery 🧬🛰️
 * 
 * RBN 3.5: Handles the discovery of constants and configuration metadata.
 * Centralizes all non-instance discovery logic.
 */
class MetadataDriver
{
    /** @var object The Framework Orchestrator */
    public object $rbn;

    public function __construct(object $rbn)
    {
        $this->rbn = $rbn;
    }

    /* ==========================================================================
       [ DISCOVERY API ] - Metadata Endpoints 🔍
       ========================================================================== */

    /**
     * Discovery & Resolve a Constant by name 🧬
     */
    public function constant(string $name)
    {
        // 1. Resolve Class Name (FQCN) via Discovery Namespace Resolver 🧬
        $class = DiscoveryEngine::instance()->namespace()->find($name, 'constant');

        if ($class && class_exists($class)) {
            return $class;
        }

        return null;
    }

    /**
     * Resolve a Configuration Key 🛰️
     */
    public function config(string $key)
    {
        // RBN 3.5 Bridge: Delegate to the orchestrator's config system
        if (method_exists($this->rbn, 'config')) {
            return $this->rbn->config($key);
        }

        return null;
    }

    /**
     * Resolve a Validation Path/FQCN or Constant Value 💎
     */
    public function validation(string $path)
    {
        // 1. Check for Dot-Notation (e.g., validation.TEMP_DOMAINS) 🧬
        if (strpos($path, '.') !== false) {
            [$category, $key] = explode('.', $path, 2);
            return DiscoveryEngine::instance()->cluster('validation')->get($category, $key);
        }

        // 2. Fallback: Resolve via Discovery Namespace Resolver 🧬
        $class = DiscoveryEngine::instance()->namespace()->find($path, 'validation');

        if ($class && class_exists($class)) {
            return $class;
        }

        return null;
    }
}
