<?php

namespace Rbn\Framework\Core\Services\System\Providers\Fluent;

use Rbn\Framework\Core\Base\Patterns\BaseChannel;

/**
 * NotificationChannel - Fluent Notification API 🔔🕊️⚓
 * RBN Framework Standard.
 * 
 * Bu sınıf bildirimlere akıcı bir erişim arayüzü sağlar.
 * Veri kaynağı olarak NotificationProvider'ı kullanır (SSOT).
 * 
 * @property \Rbn\Framework\Core\Database\Repositories\Common\NotificationRepository $notificationRepository
 */
class NotificationChannel extends BaseChannel
{
    public function __construct()
    {
        parent::__construct('comm_noti');
    }

    /** @var string Explicit data key for view packaging 🏹 */
    protected string $dataKey = 'notifications';

    /* ==========================================================================
       [ ACTIONS ] - Delegation to NotificationRepository ⚙️⚓
       ========================================================================== */

    public function find(int $id): array
    {
        return (array) ($this->repository('common.notification')?->find($id) ?: []);
    }

    public function markAsRead(int $id): bool
    {
        return (bool) $this->repository('common.notification')?->markAsRead($id);
    }

    public function delete(int $id): bool
    {
        return (bool) $this->repository('common.notification')?->delete($id);
    }

    public function clearAllRead(): bool
    {
        return (bool) $this->repository('common.notification')?->clearAllRead();
    }

    /* ==========================================================================
       [ FILTERS ] - Fluent State Management 🛰️⚓
       ========================================================================== */

    public function unread(): self
    {
        $this->status = 'unread';
        return $this;
    }

    public function read(): self
    {
        $this->status = 'read';
        return $this;
    }

    /**
     * Implementation of getting notification records via Provider 🛰️
     */
    public function get(): mixed
    {
        return $this->getFromCache('list', function () {
            $result = $this->repository('common.notification')?->getNotifications($this->status) ?: [];
            return $this->hydrate($result['notifications'] ?? []);
        });
    }

    /**
     * Implementation of counting notifications via Provider 📊
     */
    public function count(): int
    {
        return $this->getFromCache('count', function () {
            $result = $this->repository('common.notification')?->getNotifications($this->status) ?: [];
            return (int) ($result['count'] ?? count($result));
        });
    }

    /**
     * Get statistics for notifications via Provider 📈
     */
    public function stats(): array
    {
        return $this->getFromCache('stats', function () {
            $data = $this->repository('common.notification')?->getDetailedStats() ?: [];

            return [
                'today' => ['title' => 'Bugün', 'color' => 'success',   'icon' => 'bi bi-calendar-day',   'total' => $data['today'] ?? 0],
                'week'  => ['title' => 'Bu Hafta', 'color' => 'info',      'icon' => 'bi bi-calendar-week',  'total' => $data['week'] ?? 0],
                'month' => ['title' => 'Bu Ay',    'color' => 'primary',   'icon' => 'bi bi-calendar-month', 'total' => $data['month'] ?? 0],
                'total' => ['title' => 'Toplam',    'color' => 'secondary', 'icon' => 'bi bi-bell',           'total' => $data['total'] ?? 0],
            ];
        });
    }
}
