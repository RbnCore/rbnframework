<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Render\Builders;

use Rbn\Framework\Core\Base\BaseComponent;
use Rbn\Framework\Core\Render\Configs\BreadcrumbConfig;

/**
 * BreadcrumbBuilder - Navigational trail construction orchestrator 🗺️🎨⚓
 * Part of RBN 3.5 Masterpiece.
 */
class BreadcrumbBuilder extends BaseComponent
{
    /** @var array Constructed breadcrumbs */
    protected array $breadcrumbs = [];

    /**
     * Reset the builder state.
     */
    public function reset(): self
    {
        $this->breadcrumbs = [];
        return $this;
    }

    /**
     * Build the breadcrumbs array using resolved metadata and loops.
     */
    public function build(
        ?string $panelPrefix = null,
        ?string $module = null,
        array|object $moduleData = [],
        ?string $view = null,
        array $meta = [],
        array $subModules = [],
        ?array $segments = null
    ): array {
        $resolver = $this->resolver('breadcrumb');
        $path = $this->request->path();
        $segments = $segments ?? array_filter(explode('/', trim($path, '/')));

        $pageTitle = $meta['title'] ?? '';
        $pageIcon = $meta['icon'] ?? '';
        $pageDesc = $meta['description'] ?? '';

        $this->reset();
        $currentPathSegments = [];

        // 🎻 1. Dashboard Root (Sovereign Anchor)
        $dashboardData = $this->service('module')->resolve('dashboard');
        $dashboardIcon = $dashboardData['module_icon'] ?? 'ri-dashboard-line';
        $this->add('Yönetim Paneli', (string) $this->service('route')->to('dashboard', 'admin'), $dashboardIcon);

        // 🎻 2. Build Trail via Autonomous Discovery
        foreach ($segments as $index => $segment) {
            if (is_numeric($segment) || in_array(strtolower((string) $segment), BreadcrumbConfig::NAME_BLACKLIST)) {
                $currentPathSegments[] = $segment;
                continue;
            }

            $currentPathSegments[] = $segment;
            $routePath = implode('/', $currentPathSegments);
            $fullPath = (string) $this->service('route')->to($routePath, $panelPrefix);

            // 🕵️‍♂️ Autonomous Logic: Identify Cluster
            $segLower = strtolower((string) $segment);
            $modLower = strtolower((string) $module);
            $isModuleRoot = ($segLower === $modLower);
            $match = $resolver ? $resolver->resolveSegmentMatch($segment, $subModules) : null;
            $action = BreadcrumbConfig::ACTION_ICON_MAP[strtolower((string) $segment)] ?? null;

            // 🎼 Metadata Priority: Map Match > Action Map > Page Root > Label
            $title = (string) ($match['title'] ?? ($action['label'] ?? ($isModuleRoot ? $pageTitle : ucwords(str_replace(['-', '_'], ' ', (string) $segment)))));
            $icon = (string) ($match['icon'] ?? ($action['icon'] ?? ($isModuleRoot ? $pageIcon : '')));

            $isActive = ($fullPath === (string) $this->service('route')->to(trim($path, '/')));
            $this->add($title, $fullPath, $icon, $isActive);

            // 🥁 Update page headers if this is a primary match
            if ($match || $isModuleRoot) {
                $pageTitle = $title;
                $pageIcon = ($resolver ? $resolver->normalizeIcon($icon) : $icon) ?: $pageIcon;
                $pageDesc = (string) ($match['description'] ?? $pageDesc);

                // 🛑 Nokta Atışı Fren: Terminal Alt Modül
                if (!empty($match['terminal']) || !empty($match['stop'])) {
                    break;
                }

                if ($match && !empty($match['sub_modules'])) {
                    $subModules = $match['sub_modules'];
                }
            }

            // 🛑 Legacy Break (Config bazlı)
            if (in_array(strtolower((string) $segment), BreadcrumbConfig::TERMINAL_ACTIONS)) {
                break;
            }
        }

        return [
            'breadcrumbs' => $this->breadcrumbs,
            'page_title' => $pageTitle,
            'page_icon' => $pageIcon,
            'page_description' => $pageDesc,
            'back_url' => (string) ((count($this->breadcrumbs) > 1) ? ($this->breadcrumbs[count($this->breadcrumbs) - 2]['url'] ?? '/') : '/'),
            'show_actions' => (count($this->breadcrumbs) > 1)
        ];
    }

    /**
     * Add a breadcrumb step.
     */
    public function add(string $title, string $url, string $icon = '', bool $active = false): self
    {
        $resolver = $this->resolver('breadcrumb');
        $normalizedIcon = $resolver ? $resolver->normalizeIcon($icon) : $icon;

        $this->breadcrumbs[] = [
            'title' => $title,
            'url' => $url,
            'icon' => $normalizedIcon,
            'active' => $active
        ];
        return $this;
    }

}
