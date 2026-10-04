<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Database\Repositories\Common;

use Rbn\Framework\Core\Base\Data\BaseRepository;

/**
 * IpBlockRepository - Shared IP Blocking Data Access & Query Repository 🚫🏛️⚓
 * 
 * RBN 3.5 Masterpiece Standard.
 * Enterprise Repository Pattern for managing blacklisted and blocked IP addresses.
 * Located strictly under Core\Database\Repositories\Common for clean architecture.
 * 
 * @property \Rbn\Framework\Core\Database\Models\Common\CmSysIpBlocksModel $ipBlocksModel
 */
class IpBlockRepository extends BaseRepository
{
    /** @var string Target primary model alias */
    protected $targetModel = 'common.ipBlock';

    /**
     * Tüm aktif engelleri getirir.
     */
    public function getBlocks(): array
    {
        return $this->model('common.ipBlock')
            ->orderBy('id', 'DESC')
            ->get()->toArray() ?? [];
    }

    /**
     * Yeni bir IP engeli oluşturur.
     */
    public function block(string $ip, string $reason, int $durationMinutes = 1440): bool
    {
        return (bool) $this->model('common.ipBlock')->create([
            'ip_address' => $ip,
            'reason' => $reason,
            'blocked_until' => now('Y-m-d H:i:s', strtotime("+{$durationMinutes} minutes"))
        ]);
    }

    /**
     * Engeli kaldırır.
     */
    public function unblock(int $id): bool
    {
        return (bool) $this->model('common.ipBlock')->where('id', $id)->delete();
    }

    /**
     * Tüm engelleri temizler.
     */
    public function clearAll(): bool
    {
        return (bool) $this->model('common.ipBlock')->truncate();
    }

    /**
     * Engel istatistiklerini hesaplar.
     */
    public function getStats(): array
    {
        $all = $this->getBlocks();
        $total = count($all);
        $temp = 0;
        $perm = 0;

        foreach ($all as $item) {
            if (!empty($item['is_permanent'])) {
                $perm++;
            } else {
                $temp++;
            }
        }

        return [
            'total_blocked' => $total,
            'temporary_blocks' => $temp,
            'permanent_blocks' => $perm,
        ];
    }
}
