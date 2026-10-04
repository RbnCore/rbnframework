<?php

namespace Rbn\Framework\Core\Services\System\Providers\Fluent;

use Rbn\Framework\Core\Base\Patterns\BaseChannel;

/**
 * ContactChannel - Fluent Contact Message API 📩🕊️⚓
 * RBN 3.5 Masterpiece Standard.
 * 
 * Bu sınıf iletişim mesajlarına akıcı bir erişim arayüzü sağlar.
 * Veri kaynağı olarak ContactProvider'ı kullanır (SSOT).
 * 
 * @property \Rbn\Framework\Core\Database\Repositories\Common\ContactRepository $contactRepository
 */
class ContactChannel extends BaseChannel
{
    public function __construct()
    {
        parent::__construct('comm_contact');
    }

    /** @var string Explicit data key for view packaging 🏹 */
    protected string $dataKey = 'messages';

    /* ==========================================================================
       [ ACTIONS ] - Delegation to ContactRepository ⚙️⚓
       ========================================================================== */

    public function find(int $id): array
    {
        return $this->repository('common.contact')?->find($id) ?: [];
    }

    public function markAsRead(int $id): bool
    {
        return (bool) $this->repository('common.contact')?->markAsRead($id);
    }

    public function moveTrash(int $id): bool
    {
        return (bool) $this->repository('common.contact')?->moveTrash($id);
    }

    public function restore(int $id): bool
    {
        return (bool) $this->repository('common.contact')?->restore($id);
    }

    public function delete(int $id): bool
    {
        return (bool) $this->repository('common.contact')?->delete($id);
    }

    public function emptyTrash(): int
    {
        return (int) $this->repository('common.contact')?->emptyTrash();
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

    public function trash(): self
    {
        $this->status = 'trash';
        return $this;
    }

    /**
     * Implementation of getting contact messages via Provider 🛰️
     */
    public function get(): mixed
    {
        return $this->getFromCache('list', function () {
            $result = $this->repository('common.contact')?->getMessages($this->status) ?: [];
            return $this->hydrate($result['messages'] ?? []);
        });
    }

    /**
     * Implementation of counting contact messages via Provider 📊
     */
    public function count(): int
    {
        return $this->getFromCache('count', function () {
            $result = $this->repository('common.contact')?->getMessages($this->status) ?: [];
            return (int) ($result['count'] ?? count($result));
        });
    }

    /**
     * Get statistics for contact messages via Provider 📈
     */
    public function stats(): array
    {
        return $this->getFromCache('stats', function () {
            $data = $this->repository('common.contact')?->getDetailedStats() ?: [];

            return [
                'today' => ['title' => 'Bugün', 'color' => 'success',   'icon' => 'bi bi-calendar-day',   'total' => $data['today'] ?? 0],
                'week'  => ['title' => 'Bu Hafta', 'color' => 'info',      'icon' => 'bi bi-calendar-week',  'total' => $data['week'] ?? 0],
                'month' => ['title' => 'Bu Ay',    'color' => 'primary',   'icon' => 'bi bi-calendar-month', 'total' => $data['month'] ?? 0],
                'total' => ['title' => 'Toplam',    'color' => 'secondary', 'icon' => 'bi bi-envelope',       'total' => $data['total'] ?? 0],
            ];
        }) ?: [];
    }
}
