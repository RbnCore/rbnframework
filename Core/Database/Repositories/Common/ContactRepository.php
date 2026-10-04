<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Database\Repositories\Common;

use Rbn\Framework\Core\Base\Data\BaseRepository;

/**
 * ContactRepository - Shared Communication Data Access & Query Repository 📩🏛️⚓
 * 
 * RBN 3.5 Masterpiece: Enterprise Repository Pattern for Shared Contact Messages.
 * Located strictly under Core\Database\Repositories\Common for clean architecture.
 * 
 * @property \Rbn\Framework\Core\Database\Models\Common\CmLogContactsModel $contactMessagesModel
 */
class ContactRepository extends BaseRepository
{
    /** @var string Target primary model alias */
    protected $targetModel = 'common.contactMessage';

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
     * `active_project_key()` → `project_key()` → `'default'`. Bu, aynı katmandaki
     * kardeş repository'lerin (`SettingsRepository`, `FaqRepository`,
     * `CronLogRepository`) zaten kullandığı kuraldır.
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
     * Get filtered messages with caching 🕊️
     */
    public function getMessages(?string $type = null, ?string $projectKey = null): array
    {
        $projectKey = $this->tenantKey($projectKey);
        $key = "comm_contact_list_" . $projectKey . "_" . ($type ?? 'all');
        $ttl = $this->storage->cache()->getTtl('short');

        $model = $this->model('common.contactMessage');

        return $this->storage->cache()->remember($key, function() use ($model, $type, $projectKey) {
            $query = $model->orderBy('created_at', 'DESC')
                ->where('project_key', $projectKey);

            if ($type === 'unread') $query->where('is_read', 0);
            elseif ($type === 'read') $query->where('is_read', 1);
            elseif ($type === 'trash') $query->where('is_read', 2);

            $messages = $query->get();

            return [
                'messages' => $messages,
                'count' => count($messages)
            ];
        }, $ttl);
    }

    /**
     * Get statistics for contact messages 📈
     */
    public function getStats(?string $projectKey = null): array
    {
        $projectKey = $this->tenantKey($projectKey);
        $model = $this->model('common.contactMessage');

        return [
            'unread' => $model->where('is_read', 0)->where('project_key', $projectKey)->count(),
            'read'   => $model->where('is_read', 1)->where('project_key', $projectKey)->count(),
            'trash'  => $model->where('is_read', 2)->where('project_key', $projectKey)->count(),
        ];
    }

    /**
     * Get detailed time-based statistics 📊
     */
    public function getDetailedStats(?string $projectKey = null): array
    {
        $current = now();
        $projectKey = $this->tenantKey($projectKey);
        $model = $this->model('common.contactMessage');

        return [
            'today' => $model->where('is_read', '!=', 2)->where('project_key', $projectKey)->where('created_at', '>=', now('Y-m-d 00:00:00'))->count(),
            'week'  => $model->where('is_read', '!=', 2)->where('project_key', $projectKey)->where('created_at', '>=', now('Y-m-d 00:00:00', strtotime('monday this week', strtotime($current))))->count(),
            'month' => $model->where('is_read', '!=', 2)->where('project_key', $projectKey)->where('created_at', '>=', now('Y-m-01 00:00:00', strtotime($current)))->count(),
            'total' => $model->where('is_read', '!=', 2)->where('project_key', $projectKey)->count(),
        ];
    }

    /**
     * Mark a message as read 👁️
     */
    public function markAsRead(int $id, ?string $projectKey = null): bool
    {
        try {
            $projectKey = $this->tenantKey($projectKey);
            $result = $this->model('common.contactMessage')->query()
                ->where('id', $id)
                ->where('project_key', $projectKey)
                ->update([
                    'is_read' => 1,
                    'read_at' => now()
                ]);

            if ($result) {
                $this->storage->cache()->clearRelated('contact');
            }
            return (bool) $result;
        } catch (\Exception $e) {
            // D-52: sistem kanalına ham PDO mesajı (DSN/SQL parçası) YAZILMAZ; sınıf + kod yeter.
            $this->storage->logs()->channel('system')->error("ContactRepository::markAsRead failed: " . get_class($e) . ' (kod ' . $e->getCode() . ')');
            $this->storage->logs()->debug("ContactRepository::markAsRead failed: " . $e->getMessage(), [], 'debug');
            return false;
        }
    }

    /**
     * Move a message to trash (is_read = 2) 🚮
     */
    public function moveTrash(int $id, ?string $projectKey = null): bool
    {
        try {
            $projectKey = $this->tenantKey($projectKey);
            $result = $this->model('common.contactMessage')->query()
                ->where('id', $id)
                ->where('project_key', $projectKey)
                ->update([
                    'is_read' => 2
                ]);
            if ($result) {
                $this->storage->cache()->clearRelated('contact');
            }
            return (bool) $result;
        } catch (\Exception $e) {
            // D-52: sistem kanalına ham PDO mesajı (DSN/SQL parçası) YAZILMAZ; sınıf + kod yeter.
            $this->storage->logs()->channel('system')->error("ContactRepository::moveTrash failed: " . get_class($e) . ' (kod ' . $e->getCode() . ')');
            $this->storage->logs()->debug("ContactRepository::moveTrash failed: " . $e->getMessage(), [], 'debug');
            return false;
        }
    }

    /**
     * Restore a message from trash ♻️
     */
    public function restore(int $id, ?string $projectKey = null): bool
    {
        try {
            $projectKey = $this->tenantKey($projectKey);
            $result = $this->model('common.contactMessage')->query()
                ->where('id', $id)
                ->where('project_key', $projectKey)
                ->update([
                    'is_read' => 1,
                    'read_at' => now()
                ]);
            if ($result) {
                $this->storage->cache()->clearRelated('contact');
            }
            return (bool) $result;
        } catch (\Exception $e) {
            // D-52: sistem kanalına ham PDO mesajı (DSN/SQL parçası) YAZILMAZ; sınıf + kod yeter.
            $this->storage->logs()->channel('system')->error("ContactRepository::restore failed: " . get_class($e) . ' (kod ' . $e->getCode() . ')');
            $this->storage->logs()->debug("ContactRepository::restore failed: " . $e->getMessage(), [], 'debug');
            return false;
        }
    }

    /**
     * Permanently delete a message 🧨
     */
    public function delete(int $id, ?string $projectKey = null): bool
    {
        try {
            $projectKey = $this->tenantKey($projectKey);
            $result = $this->model('common.contactMessage')->query()
                ->where('id', $id)
                ->where('project_key', $projectKey)
                ->delete();
            if ($result) {
                $this->storage->cache()->clearRelated('contact');
            }
            return (bool) $result;
        } catch (\Exception $e) {
            // D-52: sistem kanalına ham PDO mesajı (DSN/SQL parçası) YAZILMAZ; sınıf + kod yeter.
            $this->storage->logs()->channel('system')->error("ContactRepository::delete failed: " . get_class($e) . ' (kod ' . $e->getCode() . ')');
            $this->storage->logs()->debug("ContactRepository::delete failed: " . $e->getMessage(), [], 'debug');
            return false;
        }
    }

    /**
     * Empty entire trash (delete all messages with is_read = 2) 🧹
     */
    public function emptyTrash(?string $projectKey = null): int
    {
        try {
            $projectKey = $this->tenantKey($projectKey);
            $count = $this->model('common.contactMessage')->query()
                ->where('is_read', 2)
                ->where('project_key', $projectKey)
                ->delete();
            if ($count > 0) {
                $this->storage->cache()->clearRelated('contact');
            }
            return (int) $count;
        } catch (\Exception $e) {
            // D-52: sistem kanalına ham PDO mesajı (DSN/SQL parçası) YAZILMAZ; sınıf + kod yeter.
            $this->storage->logs()->channel('system')->error("ContactRepository::emptyTrash failed: " . get_class($e) . ' (kod ' . $e->getCode() . ')');
            $this->storage->logs()->debug("ContactRepository::emptyTrash failed: " . $e->getMessage(), [], 'debug');
            return 0;
        }
    }

    /**
     * Get the latest unread contact messages formatted for the panel top bar 📩
     */
    public function getLatest(int $limit = 5, ?string $projectKey = null): array
    {
        $projectKey = $this->tenantKey($projectKey);
        return $this->model('common.contactMessage')
            ->where('is_read', 0)
            ->where('project_key', $projectKey)
            ->orderBy('id', 'DESC')
            ->limit($limit)
            ->get()->all();
    }
}
