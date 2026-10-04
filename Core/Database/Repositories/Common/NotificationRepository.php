<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Database\Repositories\Common;

use Rbn\Framework\Core\Base\Data\BaseRepository;

/**
 * NotificationRepository - Shared Notifications Data Access & Query Repository 🔔🏛️⚓
 * 
 * RBN 3.5 Masterpiece: Enterprise Repository Pattern for Shared Notifications.
 * Located strictly under Core\Database\Repositories\Common for clean architecture.
 * 
 * @property \Rbn\Framework\Core\Database\Models\Common\CmLogNotificationsModel $notificationsModel
 */
class NotificationRepository extends BaseRepository
{
    /** @var string Target primary model alias */
    protected $targetModel = 'common.notification';

    /**
     * [D-51] Kiracı (proje) anahtarını çözer — **HTTP GİRDİSİ YOK**.
     *
     * ESKİ YAZIM: `request()->query('project') ?: project_key() ?: 'default'`
     * Veri katmanında HTTP okumasıydı (Anayasa §8 ihlali): CLI/Cron bağlamında
     * `request()` yoktur ve proje anahtarını **kullanıcının URL girdisi**
     * belirliyordu.
     *
     * YENİ: anahtar dışarıdan **parametre** olarak gelir (controller/servis
     * katmanı karar verir); verilmezse sunucu bağlamı kullanılır:
     * `active_project_key()` → `project_key()` → `'default'`.
     *
     * NOT: `CmLogNotificationsModel::scoped = true` olduğu için model zaten
     * aktif kiracının koşulunu AND'ler; buradaki anahtar yalnızca **daraltır**.
     *
     * @param string|null $projectKey Çağıran katmanın geçtiği anahtar (null → sunucu bağlamı).
     */
    protected function tenantKey(?string $projectKey = null): string
    {
        $anahtar = trim((string) ($projectKey ?? ''));
        if ($anahtar !== '') {
            return $anahtar;
        }

        if (function_exists('active_project_key')) {
            $aktif = trim((string) active_project_key());
            if ($aktif !== '') {
                return $aktif;
            }
        }
        return trim((string) project_key()) ?: 'default';
    }

    /**
     * Get filtered notifications with caching 🕊️
     */
    public function getNotifications(?string $type = null, ?string $projectKey = null): array
    {
        $projectKey = $this->tenantKey($projectKey);
        $key = "comm_noti_list_" . $projectKey . "_" . ($type ?? 'all');
        $ttl = $this->storage->cache()->getTtl('short');

        $model = $this->model('common.notification');

        return $this->storage->cache()->remember($key, function() use ($model, $type, $projectKey) {
            $query = $model->orderBy('created_at', 'DESC')
                ->where('project_key', $projectKey);

            if ($type === 'unread') $query->where('is_read', 0);
            elseif ($type === 'read') $query->where('is_read', 1);

            $notifications = $query->get();

            return [
                'notifications' => $notifications,
                'count' => count($notifications)
            ];
        }, $ttl);
    }

    /**
     * Get unread/read statistics 📊
     */
    public function getStatusStats(?string $projectKey = null): array
    {
        $projectKey = $this->tenantKey($projectKey);
        $model = $this->model('common.notification');

        return [
            'unread' => $model->where('is_read', 0)->where('project_key', $projectKey)->count(),
            'read'   => $model->where('is_read', 1)->where('project_key', $projectKey)->count(),
        ];
    }

    /**
     * Get detailed time-based statistics 📈
     */
    public function getDetailedStats(?string $projectKey = null): array
    {
        $current = now();
        $projectKey = $this->tenantKey($projectKey);
        $model = $this->model('common.notification');

        return [
            'today' => $model->where('project_key', $projectKey)->where('created_at', '>=', now('Y-m-d 00:00:00'))->count(),
            'week'  => $model->where('project_key', $projectKey)->where('created_at', '>=', now('Y-m-d 00:00:00', strtotime('monday this week', strtotime($current))))->count(),
            'month' => $model->where('project_key', $projectKey)->where('created_at', '>=', now('Y-m-01 00:00:00', strtotime($current)))->count(),
            'total' => $model->where('project_key', $projectKey)->count(),
        ];
    }

    /**
     * Mark a notification as read 👁️
     */
    public function markAsRead(int $id, ?string $projectKey = null): bool
    {
        try {
            $projectKey = $this->tenantKey($projectKey);
            $result = $this->model('common.notification')->query()
                ->where('id', $id)
                ->where('project_key', $projectKey)
                ->update([
                    'is_read' => 1,
                    'read_at' => now()
                ]);

            if ($result) {
                $this->storage->cache()->clearRelated('notification');
            }
            return (bool) $result;
        } catch (\Exception $e) {
            // D-52: sistem kanalına ham PDO mesajı (DSN/SQL parçası) YAZILMAZ; sınıf + kod yeter.
            $this->storage->logs()->channel('system')->error("NotificationRepository::markAsRead failed: " . get_class($e) . ' (kod ' . $e->getCode() . ')');
            $this->storage->logs()->debug("NotificationRepository::markAsRead failed: " . $e->getMessage(), [], 'debug');
            return false;
        }
    }

    /**
     * Delete a notification 🗑️
     */
    public function delete(int $id, ?string $projectKey = null): bool
    {
        try {
            $projectKey = $this->tenantKey($projectKey);
            $result = $this->model('common.notification')->query()
                ->where('id', $id)
                ->where('project_key', $projectKey)
                ->delete();
            if ($result) {
                $this->storage->cache()->clearRelated('notification');
            }
            return (bool) $result;
        } catch (\Exception $e) {
            // D-52: sistem kanalına ham PDO mesajı (DSN/SQL parçası) YAZILMAZ; sınıf + kod yeter.
            $this->storage->logs()->channel('system')->error("NotificationRepository::delete failed: " . get_class($e) . ' (kod ' . $e->getCode() . ')');
            $this->storage->logs()->debug("NotificationRepository::delete failed: " . $e->getMessage(), [], 'debug');
            return false;
        }
    }

    /**
     * Clear all read notifications 🧹
     */
    public function clearAllRead(?string $projectKey = null): bool
    {
        try {
            $projectKey = $this->tenantKey($projectKey);
            $result = $this->model('common.notification')->query()
                ->where('is_read', 1)
                ->where('project_key', $projectKey)
                ->delete();
            if ($result > 0) {
                $this->storage->cache()->clearRelated('notification');
            }
            return (bool) $result;
        } catch (\Exception $e) {
            // D-52: sistem kanalına ham PDO mesajı (DSN/SQL parçası) YAZILMAZ; sınıf + kod yeter.
            $this->storage->logs()->channel('system')->error("NotificationRepository cleanup failed: " . get_class($e) . ' (kod ' . $e->getCode() . ')');
            $this->storage->logs()->debug("NotificationRepository cleanup failed: " . $e->getMessage(), [], 'debug');
            return false;
        }
    }

    /**
     * Get the latest unread notifications formatted for the panel top bar 🔔
     */
    public function getLatest(int $limit = 5, ?string $projectKey = null): array
    {
        $projectKey = $this->tenantKey($projectKey);
        return $this->model('common.notification')
            ->where('is_read', 0)
            ->where('project_key', $projectKey)
            ->orderBy('id', 'DESC')
            ->limit($limit)
            ->get()->all();
    }
}
