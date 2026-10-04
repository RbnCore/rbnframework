<?php

declare(strict_types=1);

namespace Rbn\Framework\Bundles\Internal\Syshub\Controllers\Security;

use Rbn\Framework\Core\Base\Attributes\SubModule;
use Rbn\Framework\Bundles\Internal\Syshub\Controllers\SyshubController;

/**
 * IpBlockController - Manuel IP Engelleme Yönetimi 🚫🛡️
 * RBN 3.5 Masterpiece Standard.
 */
#[SubModule(
    entity: 'security/ipblock',
    service: 'syshub',
    handler: 'syshubSecurity',
    modal: 'Security/Partials/modal_ip_block'
)]
class IpBlockController extends SyshubController
{
    /**
     * IP Blokları Listesi 📊
     */
    public function index(): void
    {
        // 1. Fetch Blocks (Otonom Provider üzerinden)
        $blocks = $this->service->ipBlock()->getBlocks();

        // 🎼 Masterpiece MVC: Veri zenginleştirme mantığı Handler katmanına taşındı 🛰️⚓
        $blocks = $this->service->security()->enrich($blocks);

        $paginator = $this->paginate($blocks, 10);

        // 2. Statistics (Otonom Provider'dan harmanlanır)
        $stats = $this->service->ipBlock()->getStats();

        $this->render('Security/ip_blocks', [
            'blocks' => $paginator->items(),
            'paginator' => $paginator,
            'stats' => $stats,
        ]);
    }

    /**
     * IP Engeli Ekle 🔒
     */
    public function store(): void
    {
        // 1. Single Shot: Security + Validation + Data Retrieval
        $data = $this->request->form([
            'ip_address' => 'required',
            'reason' => 'required',
            'duration' => 'required'
        ]);

        // 2. Business Logic (Otonom Provider üzerinden)
        $result = $this->service->ipBlock()->block(
            $data['ip_address'],
            $data['reason'],
            (int) $data['duration']
        );

        $this->handleResult($result, "IP ({$data['ip_address']})");
    }

    /**
     * Tüm Engelleri Kaldır 🔓
     */
    public function clearAll(): void
    {
        $result = $this->service->ipBlock()->clearAll();

        $this->handleResult($result, 'Tüm engeller');
    }
}
