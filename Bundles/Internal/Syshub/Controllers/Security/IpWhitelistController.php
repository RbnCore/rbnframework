<?php

declare(strict_types=1);

namespace Rbn\Framework\Bundles\Internal\Syshub\Controllers\Security;

use Rbn\Framework\Core\Base\Attributes\SubModule;
use Rbn\Framework\Bundles\Internal\Syshub\Controllers\SyshubController;

/**
 * WhitelistController - Güvenilir IP Listesi Yönetimi 🕊️🛡️
 * RBN Framework Standard.
 */
#[SubModule(
    entity: 'security/ipwhitelist',
    service: 'syshub',
    handler: 'syshubSecurity',
    modal: 'Security/Partials/modal_whitelist'
)]
class IpWhitelistController extends SyshubController
{
    /**
     * Whitelist Listesi 📊
     */
    public function index(): void
    {
        // 🎼 RBN Framework: Veriyi otonom provider üzerinden çekiyoruz
        $whitelists = $this->service->whitelist()->getList();

        // 🎼 RBN Framework MVC: Veri zenginleştirme mantığı Handler katmanına taşındı 🛰️⚓
        $whitelists = $this->service->security()->enrich($whitelists);

        $paginator = $this->paginate($whitelists, 10);

        $this->render('Security/ip_whitelist', [
            'whitelists' => $paginator->items(),
            'paginator' => $paginator,
            'stats' => [
                'total_whitelisted' => count($whitelists)
            ]
        ]);
    }

    /**
     * Whitelist Kaydet ➕
     */
    public function store(): void
    {
        // 1. Single Shot: Security + Validation + Data Retrieval
        $data = $this->request->form([
            'ip_address' => 'required',
            'label' => 'required'
        ]);

        // 2. Business Logic (Otonom Provider üzerinden)
        $result = $this->service->whitelist()->add(
            $data['ip_address'],
            $data['label']
        );

        $this->handleResult($result, 'IP Adresi');
    }

    /**
     * Whitelist Kayıt Sil 🗑️
     */
    public function destroy(string $ip): void
    {
        $result = $this->service->whitelist()->remove($ip);

        $this->handleResult($result, "IP ({$ip})");
    }
}
