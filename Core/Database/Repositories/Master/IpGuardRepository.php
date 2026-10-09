<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Database\Repositories\Master;

use Rbn\Framework\Core\Base\Data\BaseRepository;

/**
 * IpGuardRepository - The RBN Framework Access Strategist Repository 🧠🛡️⚓
 * 
 * RBN Framework Standard.
 * Master repository for IP Blacklisting, Whitelisting and Multi-tenant Security.
 * Located strictly under Core\Database\Repositories\Master for clean architecture.
 * 
 * @property \Rbn\Framework\Core\Database\Models\Master\MasterIpBlocksModel $masterIpBlocksModel
 * @property \Rbn\Framework\Core\Database\Models\Master\MasterIpWhitelistModel $masterIpWhitelistModel
 */
class IpGuardRepository extends BaseRepository
{
    /** @var string Target primary model alias */
    protected $targetModel = 'master.ipBlock';

    /**
     * Fetch all blocked IPs (Strategic Fetch) 🔍
     */
    public function fetch(array $options = []): array
    {
        return $this->model('master.ipBlock')
            ->when($options['is_active'] ?? null, function ($q) {
                return $q->where('blocked_until', '>', now());
            })
            ->when($options['ip_address'] ?? null, fn($q, $val) => $q->where('ip_address', $val))
            ->all();
    }

    /**
     * Save/Block an IP (Strategic Save) 💾
     */
    public function save(array $data): bool
    {
        $ip = $data['ip_address'] ?? '';
        $reason = $data['reason'] ?? 'Sistem tarafından engellendi.';
        $duration = $data['duration'] ?? 1440; // 1 Day
        $projectId = $data['project_id'] ?? null;
        $countryCode = $data['country_code'] ?? 'XX';

        if (empty($ip)) {
            return false;
        }

        try {
            return (bool) $this->blockIp($ip, $reason, (int) $duration, $projectId, $countryCode);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Blocks an IP address with a specific reason and duration.
     * Core business logic transplanted from the model. 🧠🛡️
     */
    public function blockIp(string $ip, string $reason, int $durationMinutes = 1440, ?int $projectId = null, string $countryCode = 'XX'): int
    {
        $projectId = $projectId ?? project_id();
        $blockedUntil = now('Y-m-d H:i:s', time() + ($durationMinutes * 60));

        // 1. Master Veritabanına Yaz (Tüm sitelerde engellemek için)
        $masterId = (int) $this->model('master.ipBlock')->create([
            'ip_address' => $ip,
            'country_code' => $countryCode,
            'reason' => $reason,
            'project_id' => $projectId,
            'blocked_until' => $blockedUntil,
            'created_at' => now()
        ]);

        // 2. Local Proje Veritabanına Yaz (Bu sitede engellemek için)
        try {
            $this->model('common.ipBlock')->create([
                'ip_address' => $ip,
                'country_code' => $countryCode,
                'reason' => $reason,
                'project_id' => $projectId,
                'blocked_until' => $blockedUntil
            ]);
        } catch (\Throwable $e) {
            // Hataları yut
        }

        return $masterId;
    }

    /**
     * Remove from blacklist (Destroy) 🗑️
     */
    public function destroy(?int $id = null): bool
    {
        if (!$id) {
            return false;
        }
        return (bool) $this->model('master.ipBlock')->destroy($id);
    }

    /**
     * Strategic Whitelist Check (Extension) 🕊️
     */
    public function isWhitelisted(string $ip): bool
    {
        return $this->model('master.ipWhitelist')
            ->where('ip_address', '=', $ip)
            ->exists();
    }

    /**
     * Adds an IP to the trusted whitelist.
     * Core business logic transplanted from the model. 🧠🕊️
     */
    public function addToWhitelist(string $ip, string $label = '', string $countryCode = 'XX'): int
    {
        if ($ip === '@self') {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        }

        return (int) $this->model('master.ipWhitelist')->create([
            'ip_address' => $ip,
            'country_code' => $countryCode,
            'label' => $label,
            'created_at' => now()
        ]);
    }
}
