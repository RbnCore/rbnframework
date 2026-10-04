<?php

declare(strict_types=1);

namespace Rbn\Framework\Bundles\RbnSuite\RbnAdmin\Controllers;

use Rbn\Framework\Core\Base\Attributes\SubModule;
/**
 * NotificationController - The Sovereign Notification Hub 🔔🏛️⚓
 * RBN 3.5 Masterpiece Standard.
 */
#[SubModule(
    entity: 'notification',
    service: 'communication'
)]
class NotificationController extends RbnAdminController
{
    public function index(): void
    {
        // 🪐 [FLUENT ENGINE] - Sovereign Channel Orchestration
        $channel = $this->service->notifications();

        $this->paginate($channel->get())->to('notifications');

        $this->render('Notification/index', [
            'stats' => $this->service->getNotificationStats(),
            'detailedStats' => $channel->stats()
        ]);
    }

    /**
     * [STRATEGIC DATA HOOK] Logic for notification retrieval in modals 🎻🔔
     */
    protected function getModalData($id): array
    {
        $id = ($id && is_numeric($id)) ? (int) $id : (int) $this->request->query('id');

        // 🎻 [IDENTITY RESOLUTION VIA SERVICE]
        $item = $this->service->notifications()->find($id);

        // 👀 [INTERACTION] - Mark as read automatically
        if ($item && !($item['is_read'] ?? 0)) {
            $this->service->notifications()->markAsRead($id);
            $item['is_read'] = 1;
        }

        return ['notification' => $item, 'id' => $id];
    }

    /** --- Action Mutations (Strategic Transitions) ⚙️⚓ --- */

    /**
     * Bildirim Silme İşlemi 🚮
     */
    public function delete($id): void
    {
        $result = $this->service->notifications()->delete((int) $id);
        $this->handleResult($result, 'Bildirim başarıyla silindi.', '/notification');
    }

    /**
     * Tüm Okunmuş Bildirimleri Temizle 🧹
     */
    public function clear(): void
    {
        $result = $this->service->notifications()->clearAllRead();
        $this->handleResult($result, 'Tüm okunmuş bildirimler temizlendi.', '/notification');
    }

    /** --- Contextual Helpers (AJAX Bridge) 📡 --- */

    /**
     * Get unread counts for navbar (AJAX) 🔔
     */
    public function getCounts(): void
    {
        $this->response->success([
            'noti' => $this->service->notifications()->unread()->count(),
            'msg' => $this->service->contacts()->unread()->count()
        ], 'Counts fetched');
    }
}
