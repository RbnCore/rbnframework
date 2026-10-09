<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Render\Handlers\UI;

use Rbn\Framework\Core\Base\Web\BaseRender;

/**
 * FrontendHandler - Data Preparation for Website Views 🛰️🌐⚓
 * 
 * RBN Framework Architecture.
 * Leverages autonomous DNA for site-wide context and orchestrates the SEO engine.
 */
class FrontendHandler extends BaseRender
{
    /**
     * Entry Point: Merges user data with system-level view context.
     */
    public function prepare(string $type, ?string $view, array $options = []): array
    {
        // 🎼 1. Primary Context Discovery
        $data = $options;
        $data['appContext'] = 'frontend';
        $data['view'] = $view;

        // 🛰️ 2. RBN Framework SEO Orchestration
        // Handlers only provide overrides; the engine handles the DB/Config hierarchy.
        $builder = $this->handler('seoBuilder');
        if ($builder) {
            $builder->prepare('project', $options['seo'] ?? []);
        }
 
        $data['seoHtml'] = $this->service('seo')->render();
        $data['metadata'] = $builder ? $builder->payload() : [];

        // 🎼 3. Auto-Prepare Static Assets
        $this->assetService()->prepareContext('frontend', $options);
        $data['headerAssets'] = $this->assetService()->renderHeader();
        $data['footerAssets'] = $this->assetService()->renderFooter();
        $data['appConfigHtml'] = '';

        // 🎼 RBN Framework: Centralized Head State & Ready Queue Hub (Frontend Context) 🛰️⚓
        $renderedHeadState = $this->provider('partial')->render('RbnCommon/head_state', ['context' => 'frontend']);
        $data['headStateHtml'] = "\n    <!-- [FRAMEWORK HEAD STATE ENGINE] -->\n" . $renderedHeadState . "\n";

        return $data;
    }
}
