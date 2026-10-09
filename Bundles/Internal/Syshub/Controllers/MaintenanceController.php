<?php
declare(strict_types=1);

namespace Rbn\Framework\Bundles\Internal\Syshub\Controllers;

use Rbn\Framework\Core\Base\Attributes\SubModule;

/**
 * MaintenanceController - RBN Framework Guard Controller 🚧🛡️
 * Otonom kimlik keşfi ve standart aksiyon yönetimi ile donatılmıştır.
 */
#[SubModule(
    entity: 'maintenance',
    service: 'syshub'
)]
class MaintenanceController extends SyshubController
{
    /**
     * BakÄ±m Modu SayfasÄ±
     */
    public function index(): void
    {
        $ipsArray = $this->service->maintenance()->getWhitelistedIps();
        $mappedIps = array_map(fn($ip) => [
            'id' => $ip,
            'ip_address' => $ip,
            'created_at' => now()
        ], $ipsArray);

        $paginator = $this->paginate($mappedIps, 15);

        $this->render('Maintenance/index', [
            'isMaintenance' => $this->service->maintenance()->isMaintenance(),
            'maintenanceMessage' => $this->service->maintenance()->getMaintenanceMessage(),
            'whitelists' => $paginator->items(),
            'paginator' => $paginator,
            'stats' => ['total_whitelisted' => count($ipsArray)]
        ]);
    }

    /**
     * Otonom Modallar İçin Veri Sağlayıcı (RBN Framework) 💉
     */
    protected function getModalData($id = null, ?string $view = null): array
    {
        // ActionControllerTrait bazen $view parametresini göndermeyebilir, request'ten yakalıyoruz.
        $targetView = $view ?? $this->request->input('view');

        if ($targetView === 'modal_update_message') {
            return [
                'maintenanceMessage' => $this->service->maintenance()->getMaintenanceMessage()
            ];
        }

        return [];
    }
}




