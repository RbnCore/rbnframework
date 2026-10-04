<?php

namespace Rbn\Framework\Bundles\RbnSuite\RbnAdmin\Providers;

use Rbn\Framework\Core\Base\Services\BaseProvider;
use Rbn\Framework\Bundles\RbnSuite\RbnAdmin\Traits\TrafficAnalysisTrait;

/**
 * StatsProvider - Admin Analytics & User Statistics Specialist 📈🏛️⚓
 * RBN 3.5 Masterpiece Standard.
 * 
 * Veri madenciliği ve istatistiksel raporlama işlerini merkezi olarak yürütür.
 * 
 * @property \Rbn\Framework\Core\Database\Models\Project\UsersModel $UsersModel
 */
class AnalyticsProvider extends BaseProvider
{
    protected $targetModel = 'Users';

    use TrafficAnalysisTrait;

    /**
     * Get aggregated traffic stats for the admin dashboard 🛰️
     */
    public function getDashboardStats(): array
    {
        $traffic = $this->query(); // Trait'den gelen query metodu
        
        return [
            'active_now'      => $traffic->getActiveCount(5),
            'today_hits'      => $traffic->hits(),
            'total_hits'      => $traffic->count(),
            'seven_day_trend' => $traffic->forTrend(7)->get(),
            'device_stats'    => $traffic->forToday()->devices(),
            'top_pages'       => $traffic->forToday()->top('url', 5),
            'top_sources'     => $traffic->forToday()->top('referer', 5)
        ];
    }

    /**
     * Get summarized user statistics 👥
     */
    public function getUserStats(): array
    {
        $UsersModel = $this->UsersModel;
        
        if (!$UsersModel) {
            return [
                'total' => 0,
                'status' => [],
                'new_registrations' => 0
            ];
        }

        return [
            'total'             => $UsersModel->count(),
            'status'            => $this->getStatusDistribution($UsersModel),
            'roles'             => $this->getRoleDistribution($UsersModel),
            'new_registrations' => $this->getNewRegistrations($UsersModel, 7)
        ];
    }

    /**
     * Internal: Status Distribution Logic
     */
    private function getStatusDistribution($model): array
    {
        $results = $model->select('status, COUNT(*) as aggregate')
                         ->groupBy('status')
                         ->get();

        $stats = ['active' => 0, 'inactive' => 0, 'banned' => 0, 'pending' => 0];
        foreach ($results as $row) {
            $stats[$row['status']] = (int)$row['aggregate'];
        }

        return $stats;
    }

    /**
     * Internal: Role Distribution Logic
     */
    private function getRoleDistribution($model): array
    {
        $results = $model->select('role, COUNT(*) as aggregate')
                         ->groupBy('role')
                         ->get();

        $distribution = [];
        foreach ($results as $row) {
            $distribution[$row['role']] = (int)$row['aggregate'];
        }

        return $distribution;
    }

    /**
     * Internal: New Registrations Count
     */
    private function getNewRegistrations($model, int $days): int
    {
        $date = now('Y-m-d H:i:s', strtotime("-$days days"));
        return $model->where('created_at', '>=', $date)->count();
    }
}
