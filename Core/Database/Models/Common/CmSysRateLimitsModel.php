<?php
declare(strict_types=1);

namespace Rbn\Framework\Core\Database\Models\Common;

use Rbn\Framework\Core\Base\Data\BaseModel;
use Rbn\Framework\Core\Support\Contracts\Base\BaseModelInterface;

/**
 * CmSysRateLimitsModel - Hız Sınırlama ve İstek Takip Katmanı ⏱️🛡️
 * 
 * RBN Framework: Consolidated model for managing rate limit attempts in rbncore_common.
 * 
 * @property int         $id
 * @property string      $project_key
 * @property string      $identifier
 * @property string      $action
 * @property string      $ip_address
 * @property string|null $country_code
 * @property string|null $user_agent
 * @property string|null $metadata
 * @property string      $created_at
 * @property string|null $updated_at
 */
class CmSysRateLimitsModel extends BaseModel implements BaseModelInterface
{
    protected string $connection = 'database_common';
    protected $table = 'cm_sys_rate_limits';
    protected bool $timestamps = false;
    protected bool $scoped = true; // 🛡️ RBN Framework: Multi-Tenant Scoping Switch

    /**
     * Record a new rate limit attempt.
     */
    public function recordAttempt(string $identifier, string $action, string $ip, string $userAgent, array $metadata = [], string $countryCode = 'XX'): bool
    {
        return $this->create([
            'identifier'   => $identifier,
            'action'       => $action,
            'ip_address'   => $ip,
            'country_code' => $countryCode,
            'user_agent'   => $userAgent,
            'metadata'     => json_encode($metadata),
            'created_at'   => now()
        ]) > 0;
    }

    /**
     * Get attempts count for an identifier and action within a window.
     */
    public function getAttemptsCount(string $identifier, string $action, int $windowStart): int
    {
        return $this->where('identifier', '=', $identifier)
            ->where('action', '=', $action)
            ->where('created_at', '>=', now('Y-m-d H:i:s', $windowStart))
            ->count();
    }

    /**
     * Get the last attempt record within a window.
     */
    public function getLastAttempt(string $identifier, string $action, int $windowStart): ?array
    {
        $result = $this->where('identifier', '=', $identifier)
            ->where('action', '=', $action)
            ->where('created_at', '>=', now('Y-m-d H:i:s', $windowStart))
            ->orderBy('created_at', 'DESC')
            ->first();

        return $result && is_object($result) && method_exists($result, 'toArray') ? $result->toArray() : (array) $result;
    }

    /**
     * Clean up expired rate limit records.
     */
    public function cleanExpired(string $identifier, string $action, int $window): int
    {
        $expiredTime = now('Y-m-d H:i:s', time() - $window);

        $query = $this->where('identifier', $identifier)
            ->where('action', $action)
            ->where('created_at', '<', $expiredTime);

        $deletedCount = $query->count();
        $query->delete();

        return (int) $deletedCount;
    }
}
