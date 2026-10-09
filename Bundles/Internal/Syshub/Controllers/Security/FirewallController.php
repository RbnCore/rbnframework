<?php

declare(strict_types=1);

namespace Rbn\Framework\Bundles\Internal\Syshub\Controllers\Security;

use Rbn\Framework\Core\Base\Attributes\SubModule;
use Rbn\Framework\Bundles\Internal\Syshub\Controllers\SyshubController;

/**
 * FirewallController - RBN Shield Güvenlik Duvarı Ayarları 🛡️🛰️
 * RBN Framework Standard.
 */
#[SubModule(
    entity: 'firewall',
    service: 'syshub',
    handler: 'syshubSecurity'
)]
class FirewallController extends SyshubController
{
    /**
     * Firewall Ayarları Sayfası 🛡️📊
     */
    public function index(): void
    {
        // 🎼 RBN Framework: Veriyi otonom provider üzerinden çekiyoruz
        $config = $this->service->firewall()->getSettings();

        $this->render('Security/firewall', [
            'config' => $config,
            'page_title' => 'Firewall Ayarları',
            'health' => $this->service->firewall()->getHealthStatus()
        ]);
    }

    /**
     * Güvenlik Ayarlarını Güncelle (Toplu veya Tekli) 💾🔄
     */
    public function updatesetting(): void
    {
        // 1. Durum: Toplu Ayar Kaydı (Form)
        if ($this->request->has('settings')) {
            $data = $this->request->form(['settings' => 'required|array']);
            $result = $this->service->firewall()->updatesetting($data['settings']);
            $this->handleResult($result, 'Güvenlik ayarları', 'security/firewall');
            return;
        }

        // 2. Durum: Tekli Durum Güncelleme (Toggle)
        $data = $this->request->form([
            'id' => 'required',
            'value' => 'nullable'
        ]);

        $result = $this->service->firewall()->updatesetting((string) $data['id'], $data['value'] ?? null);

        // Explicitly pass 'status' to avoid heuristic confusion in handleResult
        $this->handleResult($result, 'Güvenlik ayarı', 'security/firewall', 'status');
    }
}
