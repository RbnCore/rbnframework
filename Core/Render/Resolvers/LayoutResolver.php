<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Render\Resolvers;

use Rbn\Framework\Core\Base\Web\BaseRender;

/**
 * LayoutResolver - Hierarchical View Orchestrator 🏛️🛰️⚓
 * 
 * RBN 3.5 Masterpiece: Responsible for managing view extensions, 
 * blocks, and hierarchical data inheritance.
 */
class LayoutResolver extends BaseRender
{
    /** @var array Content sections/blocks */
    protected array $sections = [];

    /** @var array Section names stack (LIFO) for nested support 📚 */
    protected array $sectionStack = [];

    /** @var string|null The parent layout path */
    protected ?string $extends = null;

    /**
     * Set the parent layout for the current view.
     */
    public function setExtends(string $layout): void
    {
        $this->extends = $layout;
    }

    /**
     * Get the defined parent layout.
     */
    public function getExtends(): ?string
    {
        return $this->extends;
    }

    /**
     * Reset extensions (for nested or sequential renders).
     */
    public function resetExtends(): void
    {
        $this->extends = null;
    }

    /**
     * Start recording a new content section (Stack-aware).
     */
    public function startSection(string $name): void
    {
        ob_start();
        $this->sectionStack[] = $name;
    }

    /**
     * Stop recording the current section and store the buffer (Stack-aware).
     */
    public function endSection(): void
    {
        if (empty($this->sectionStack)) {
            return;
        }

        $name = array_pop($this->sectionStack);
        $this->sections[$name] = ob_get_clean();
    }

    /**
     * Resolve and return a specific section's content.
     */
    public function getSection(string $name, string $default = ''): string
    {
        return $this->sections[$name] ?? $default;
    }

    /**
     * Get all currently defined sections.
     */
    public function getAllSections(): array
    {
        return $this->sections;
    }

    /**
     * Clear all sections (memory management).
     */
    public function clear(): void
    {
        $this->sections = [];
        $this->sectionStack = [];
        $this->extends = null;
    }
}
