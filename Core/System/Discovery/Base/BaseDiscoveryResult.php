<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\System\Discovery\Base;

/**
 * BaseDiscoveryResult - Unified Metadata Container 📦🛰️⚓
 * 
 * RBN 3.5: Represents a generic successful discovery result.
 * Encapsulates the physical path and source layer information.
 */
abstract class BaseDiscoveryResult
{
    protected string $physicalPath;
    protected string $sourceType;
    protected string $module;

    public function __construct(string $physicalPath, string $sourceType, string $module)
    {
        $this->physicalPath = $physicalPath;
        $this->sourceType = $sourceType;
        $this->module = $module;
    }

    /**
     * Get the absolute physical path on disk 📂
     */
    public function getPath(): string
    {
        return $this->physicalPath;
    }

    /**
     * Get the source type (project, framework, suite) 🏗️
     */
    public function getSourceType(): string
    {
        return $this->sourceType;
    }

    /**
     * Get the module or layer name (e.g., 'Core', 'Project') 📦
     */
    public function getModule(): string
    {
        return $this->module;
    }

    /**
     * Get last modification time for versioning/caching 🛡️
     */
    public function getModifiedTime(): int
    {
        return file_exists($this->physicalPath) ? filemtime($this->physicalPath) : time();
    }
}
