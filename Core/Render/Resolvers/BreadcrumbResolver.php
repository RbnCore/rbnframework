<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Render\Resolvers;

use Rbn\Framework\Core\Base\Web\BaseRender;

/**
 * BreadcrumbResolver - Pure metadata and configuration resolver for navigation 🧠🛰️⚓
 * Part of RBN Framework.
 */
class BreadcrumbResolver extends BaseRender
{
    /**
     * Resolves the navigation context. 🚀⚓
     */
    public function resolve(array $options = []): array
    {
        // 🎼 RBN Framework: [RBN Framework HYDRATION] 🏺🛰️⚓
        $panelPrefix = $options['panelPrefix'] ?? ($this->rbn->activeController()->panel ?? 'admin');
        $module      = $options['module']      ?? ($this->rbn->activeController()->module ?? 'dashboard');
        $moduleData  = $options['moduleData']  ?? ($this->rbn->activeController()->moduleData ?? []);
        $subModules  = $moduleData['sub_modules'] ?? ($options['subModules'] ?? []);

        // 🎼 RBN Framework Segment Sterilization 🛡️✨
        $path        = $this->request->path();
        $rawSegments = array_values(array_filter(explode('/', trim($path, '/'))));
        $basePrefix  = strtolower((string) $this->service('route')->getBasePrefix());
        
        $systemRoles = array_keys(\Rbn\Framework\Bundles\RbnSuite\RbnAuth\Models\AuthRole::ROLES);
        $systemSkip  = array_merge(['rbn', 'panel', $basePrefix, strtolower((string) $panelPrefix)], $systemRoles);

        $cleanSegments = array_values(array_filter($rawSegments, function ($s) use ($systemSkip) {
            return !in_array(strtolower((string) $s), $systemSkip);
        }));

        $meta = $this->resolveInitialMetadata($moduleData, (string) $module, $options);

        return [
            'panelPrefix' => $panelPrefix,
            'module' => $module,
            'moduleData' => $moduleData,
            'subModules' => $subModules,
            'cleanSegments' => $cleanSegments,
            'meta' => $meta
        ];
    }

    /**
     * Extracts module identity through DNA or manual config.
     */
    public function resolveInitialMetadata(array|object $moduleData, string $module, array $options): array
    {
        $source = (array) $moduleData;
        if (isset($source['identity'])) {
            $source = (array) $source['identity'];
        }

        $title = (string) ($source['title'] ?? ($options['title'] ?? ucwords(str_replace(['-', '_'], ' ', $module))));
        $icon = $this->normalizeIcon((string) ($source['icon'] ?? ($options['icon'] ?? 'bi bi-box-seam')));
        $desc = (string) ($source['description'] ?? ($options['description'] ?? ''));

        return ['title' => $title, 'icon' => $icon, 'description' => $desc];
    }

    /**
     * Resolves matching sub-module configuration.
     */
    public function resolveSegmentMatch(string $segment, array $subModules): ?array
    {
        return $subModules[$segment] ?? null;
    }

    /**
     * Normalizes Bootstrap icon classes.
     */
    public function normalizeIcon(string $icon): string
    {
        if (empty($icon)) {
            return '';
        }
        return (str_starts_with($icon, 'bi-') && !str_contains($icon, ' ')) ? 'bi ' . $icon : $icon;
    }
}
