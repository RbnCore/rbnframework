<?php

namespace Rbn\Framework\Bundles\Internal\Webhub\Controllers;

use Rbn\Framework\Core\Base\Web\BaseController;
use Rbn\Framework\Core\Base\Attributes\Module;

#[Module(
    name: 'webhub',
    data: \Rbn\Framework\Bundles\Internal\Webhub\Models\ModuleData::class,
    service: 'webhub',
    panel: 'developer',
    context: 'panel',
    description: 'Frontend kimlik, SEO, navigasyon ve sayfa yönetim merkezi.',
    icon: 'bi-globe2'
)]
class WebhubController extends BaseController
{
    public function index()
    {
        $this->render('webhubdash');
    }

    /**
     * Filter settings array to keep only specified keys.
     */
    protected function filterSettings(array $settings, array $allowedKeys): array
    {
        return array_filter($settings, function ($s) use ($allowedKeys) {
            $key = is_object($s) ? ($s->setting_key ?? '') : ($s['setting_key'] ?? '');
            return in_array($key, $allowedKeys, true);
        });
    }
}
