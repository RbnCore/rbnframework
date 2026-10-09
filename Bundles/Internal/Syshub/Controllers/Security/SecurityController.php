<?php

namespace Rbn\Framework\Bundles\Internal\Syshub\Controllers\Security;

use Rbn\Framework\Bundles\Internal\Syshub\Controllers\SyshubController;
use Rbn\Framework\Core\Base\Attributes\SubModule;

/**
 * SecurityController - SysHub Güvenlik Paneli (Ana Dashboard) 🛡️🛰️
 * Otonom kimlik keşfi ile donatılmıştır. Alt modüller kendi kontrolörlerinde izole yaşar.
 */
#[SubModule(
    entity: 'security',
    service: 'syshub',
    handler: 'syshub'
)]
class SecurityController extends SyshubController
{
    /**
     * Güvenlik Paneli - Genel Bakış 🛡️
     */
    public function index(): void
    {
        // 🎼 RBN Framework: Zayıf Kontrolör (Thin Controller), Kalın Servis (Fat Service).
        // Tüm veri derleme ve GeoIP haritalama işleri otonom Security Handler'a devredildi.
        $dashboardData = $this->service->security()->getSecurityDashboardData();

        // RBN Framework Render Standardı
        $this->render('Security/index', $dashboardData);
    }
}
