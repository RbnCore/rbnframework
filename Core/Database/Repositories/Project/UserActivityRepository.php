<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Database\Repositories\Project;

use Rbn\Framework\Core\Base\Data\BaseRepository;

/**
 * UserActivityRepository - Specialized Data Repository for Audit Trails & User Activities 🕵️‍♂️📜📊
 * 
 * RBN 3.5 Masterpiece Standard.
 * Handles activity logging, historical analysis, and log maintenance.
 * 
 * @property \Rbn\Framework\Core\Database\Models\Project\UserActivitiesModel $userActivitiesModel
 */
class UserActivityRepository extends BaseRepository
{
    /** @var string Target primary model alias */
    protected $targetModel = 'project.userActivity';

    /* ==========================================================================
       [ LOGGING WORKER ] 🖊️📜🛰️⚓
       ========================================================================== */

    /**
     * Logs a failed security activity 🕵️‍♂️📜
     */
    public function logFailedActivity(string $type, string $identity, string $reason = '', string $accountType = 'user', ?string $countryCode = null): bool
    {
        return (bool) $this->model('project.userActivity')->create([
            'email' => $identity,
            'account_type' => $accountType,
            'activity_type' => 'failed_' . $type,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            'country_code' => $countryCode,
            'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 500),
            'details' => $reason
        ]);
    }

    /**
     * Logs a successful security activity ✅
     */
    public function logSuccessfulActivity(string $type, int $userId, string $identity, string $accountType = 'user', ?string $countryCode = null): bool
    {
        return (bool) $this->model('project.userActivity')->create([
            'user_id' => $userId,
            'email' => $identity,
            'account_type' => $accountType,
            'activity_type' => 'successful_' . $type,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            'country_code' => $countryCode,
            'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 500)
        ]);
    }

    /* ==========================================================================
       [ AUDITING WORKER ] 🕵️‍♂️📑📊
       ========================================================================== */

    /**
     * Fetch Activity Logs with Deep Filtering 🔍📑
     */
    public function getActivities(array $filters = []): array
    {
        $query = $this->model('project.userActivity')->query();

        $accountType = $filters['account_type'] ?? 'user';
        if ($accountType !== 'all') {
            $query->where('account_type', $accountType);
        }

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('email', 'LIKE', '%' . $filters['search'] . '%')
                    ->orWhere('ip_address', 'LIKE', '%' . $filters['search'] . '%');
            });
        }

        if (!empty($filters['type']) && $filters['type'] !== 'all') {
            $query->where('activity_type', $filters['type']);
        }

        $order = $filters['order'] ?? 'desc';
        return $query->orderBy('created_at', $order)->get()->all();
    }

    /**
     * Get Activity Statistics for Dashboard/Header 📊
     */
    public function stats(): array
    {
        $model = $this->model('project.userActivity');
        return [
            'total' => $model->count(),
            'today' => $model->where('created_at', '>=', date('Y-m-d 00:00:00'))->count(),
            'system_errors' => $model->where('activity_type', 'system_error')->count()
        ];
    }

    /**
     * Check if any standard user activities exist 🕵️‍♂️
     */
    public function hasUserActivities(): bool
    {
        return $this->model('project.userActivity')->where('account_type', 'user')->exists();
    }

    /**
     * Clear standard user activity logs 🗑️🛡️
     */
    public function clear(): bool
    {
        return (bool) $this->model('project.userActivity')->where('account_type', 'user')->delete();
    }
}
