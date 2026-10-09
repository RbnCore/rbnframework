<?php

namespace Rbn\Framework\Bundles\Internal\Backstage\Controllers;

use Rbn\Framework\Core\Base\Web\BaseController;
use Rbn\Framework\Core\Base\Attributes\Module;
use Rbn\Framework\Bundles\Internal\Backstage\Models\ModuleData;

/**
 * BackstageController - Main Dashboard for App Config Hub 🚀🛰️⚓
 * 
 * RBN Framework Standard.
 * This class serves as the root identity for the Backstage module ecosystem.
 */
#[Module(
    name: 'backstage',
    data: ModuleData::class,
    panel: 'developer',
    context: 'panel'
)]
class BackstageController extends BaseController
{
    public function index()
    {
        // 🎼 RBN Framework Render: Context automagically detected via Module Attribute!
        return $this->render('backstage_dash');
    }
}
