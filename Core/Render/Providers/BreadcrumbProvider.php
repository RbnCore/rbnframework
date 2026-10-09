<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Render\Providers;

use Rbn\Framework\Core\Base\Web\BaseRender;
use Rbn\Framework\Core\Support\Contracts\Base\BaseRenderInterface;

/**
 * BreadcrumbProvider - Navigation Path HTML Generator 🛰️🥖⚓
 * Part of RBN Framework.
 */
class BreadcrumbProvider extends BaseRender implements BaseRenderInterface
{
    /**
     * Orchestrates resolution, building, and renders HTML. 🚀⚓
     */
    public function resolveAndRender(array &$data, ?string $view): string
    {
        // 🎼 Step 1: Resolve context through pure Resolver
        $targetModule = $data['module_name'] ?? ($data['module'] ?? 'rbnadmin');
        
        $resolver = $this->resolver('breadcrumb');
        $context = $resolver ? $resolver->resolve([
            'moduleData' => $this->service('module')->resolve((string) $targetModule),
            'module' => (string) $targetModule,
            'panel' => $data['panel'] ?? 'admin',
            'panelPrefix' => $data['panel'] ?? 'admin',
            'view' => $view
        ]) : [];

        // 🎼 Step 2: Build navigation items through Builder
        $builder = $this->handler('breadcrumbBuilder');
        $navigation = [];
        if ($builder && !empty($context)) {
            $navigation = $builder->build(
                (string) $context['panelPrefix'],
                (string) $context['module'],
                $context['moduleData'],
                $view,
                $context['meta'],
                $context['subModules'],
                $context['cleanSegments']
            );
        }

        // 🎼 Step 3: Inject resolved page header metadata back into the view data context
        if (isset($navigation['page_title'])) {
            $data['page'] = [
                'title'        => $navigation['page_title'] ?? '',
                'icon'         => $navigation['page_icon'] ?? '',
                'description'  => $navigation['page_description'] ?? '',
                'back_url'     => $navigation['back_url'] ?? '/',
                'show_actions' => $navigation['show_actions'] ?? false
            ];
        }

        // 🎼 Step 4: Render the breadcrumbs list to HTML
        return $this->render(null, $navigation);
    }

    /**
     * Unified Render Entry Point 🏹
     */
    public function render(?string $view, array $data = []): string
    {
        $crumbs = $data['breadcrumbs'] ?? [];
        if (empty($crumbs)) {
            return '';
        }

        $html = '<nav aria-label="breadcrumb" class="rbn-breadcrumb-wrapper"><ol class="rbn-breadcrumb breadcrumb mb-0">';
        $count = count($crumbs);

        foreach ($crumbs as $index => $item) {
            $isLast = ($index === $count - 1);
            $activeClass = $isLast ? 'active' : '';
            
            $html .= '<li class="rbn-breadcrumb-item breadcrumb-item ' . $activeClass . '" ' . ($isLast ? 'aria-current="page"' : '') . '>';

            $text = (string) ($item['title'] ?? '');
            $icon = (string) ($item['icon'] ?? '');

            // 🎼 RBN Framework Icon Render
            if (!empty($icon)) {
                $html .= '<i class="' . htmlspecialchars($icon) . ' me-1 opacity-75"></i>';
            }

            $displayText = htmlspecialchars($text);

            if (!empty($item['url']) && !$isLast) {
                $html .= '<a href="' . $item['url'] . '" class="text-decoration-none transition-hover">' . $displayText . '</a>';
            } else {
                $html .= '<span class="fw-medium">' . $displayText . '</span>';
            }

            $html .= '</li>';
        }

        $html .= '</ol></nav>';
        return $html;
    }
}
