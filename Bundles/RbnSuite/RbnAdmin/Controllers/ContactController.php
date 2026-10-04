<?php

declare(strict_types=1);

namespace Rbn\Framework\Bundles\RbnSuite\RbnAdmin\Controllers;

use Rbn\Framework\Core\Base\Attributes\SubModule;

/**
 * ContactController - Relocated to Logic Hub 📩🏛️⚓
 * RBN 3.5 Masterpiece Standard.
 */
#[SubModule(
    entity: 'contact',
    service: 'communication'
)]
class ContactController extends RbnAdminController
{

    /**
     * Mesajları Listele (Sovereign Index) 📑📩⚓
     */
    public function index(?string $type = null): void
    {
        $type = $type ?? (string) $this->request->query('type', 'unread');

        // 🪐 [FLUENT ENGINE] - Channel Orchestration
        $channel = $this->service->contacts()->status($type);

        $this->paginate($channel->get())->to('messages');

        $this->render('Contact/index', [
            'stats' => $this->service->getContactStats(),
            'detailedStats' => $channel->stats(),
            'currentType' => $type,
            'is_read' => ($type === 'read' ? 1 : ($type === 'trash' ? -1 : 0))
        ]);
    }

    /** --- Contextual Aliases 🛰️ --- */

    public function unread(): void
    {
        $this->index('unread');
    }
    public function read(): void
    {
        $this->index('read');
    }
    public function trash(): void
    {
        $this->index('trash');
    }

    /**
     * [STRATEGIC DATA HOOK] Prepare data for the modal 🎻📩
     */
    protected function getModalData($id): array
    {
        $id = ($id && is_numeric($id)) ? (int) $id : (int) $this->request->query('id');

        // 🎻 [IDENTITY RESOLUTION VIA SERVICE]
        $msg = $this->service->contacts()->find($id);

        // 👀 [INTERACTION] - Mark as read automatically
        if ((int) ($msg['is_read'] ?? 0) === 0) {
            $this->service->contacts()->markAsRead($id);
            $msg['is_read'] = 1;
        }

        return ['contact' => $msg, 'id' => $id];
    }

    /** --- Action Mutations (Strategic Transitions) ⚙️⚓ --- */

    /**
     * Mesajı Çöpe Taşı 🚮
     */
    public function delete($id): void
    {
        $result = $this->service->contacts()->moveTrash((int) $id);
        $this->handleResult($result, 'Mesaj çöp kutusuna taşındı.', "/contact/read");
    }

    /**
     * Mesajı Geri Yükle ♻️
     */
    public function restore($id): void
    {
        $result = $this->service->contacts()->restore((int) $id);
        $this->handleResult($result, 'Mesaj gelen kutusuna geri yüklendi.', '/contact/trash');
    }

    /**
     * Mesajı Sistemden Kalıcı Olarak Sil 🚮❌
     */
    public function destroy($id): void
    {
        $result = $this->service->contacts()->delete((int) $id);
        $this->handleResult($result, 'Mesaj sistemden kalıcı olarak temizlendi.', '/contact/trash');
    }

    /**
     * Çöp Kutusunu Tamamen Boşalt 🧹
     */
    public function clear(): void
    {
        $result = $this->service->contacts()->emptyTrash();
        $this->handleResult($result, 'Çöp kutusu başarıyla temizlendi.', '/contact/trash');
    }
}
