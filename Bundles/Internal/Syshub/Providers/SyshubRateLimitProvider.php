<?php

declare(strict_types=1);

namespace Rbn\Framework\Bundles\Internal\Syshub\Providers;

use Rbn\Framework\Core\Base\Services\BaseProvider;

/**
 * SyshubRateLimitProvider - Otonom Hız Sınırlama Veri Katmanı 🛡️⚓
 * RBN 3.5 Masterpiece Standard.
 * 
 * Bu provider, hiçbir aracı servis kullanmadan doğrudan RateLimitModel ile konuşur.
 * 
 * --- DEPENDENCY TRACKING ---
 * @property-read \Rbn\Framework\Core\Database\Models\Common\CmSysRateLimitsModel $rateLimit
 */
class SyshubRateLimitProvider extends BaseProvider
{
    /**
     * Otonom model keşfi için hedef model tanımı 🛰️🎡
     */
    protected $targetModel = 'common.rateLimit';

    /**
     * Tüm deneme kayıtlarını getirir.
     */
    public function getRecords(int $limit = 0): array
    {
        $query = $this->model('common.rateLimit')->orderBy('id', 'DESC');

        if ($limit > 0) {
            $query->limit($limit);
        }

        $records = $query->get()->toArray();

        // 🌍 GeoIP Mapping (Masterpiece Data Enrichment) 🛰️⚓
        $geoIP = $this->handler('geoIP');
        if ($geoIP) {
            foreach ($records as &$record) {
                $cCode = $record['country_code'] ?? $geoIP->getCountryCode($record['ip_address']);
                $record['country_name'] = $geoIP->getCountryName($cCode);
                $record['flag'] = $geoIP->getFlag($cCode);
            }
            unset($record);
        }

        return $records;
    }

    /**
     * Hız sınırlama istatistiklerini hesaplar.
     */
    public function getStats(): array
    {
        $model = $this->model('common.rateLimit');
        $lastRecord = $model->orderBy('created_at', 'DESC')->first();
        $dayAgo = date('Y-m-d H:i:s', strtotime('-24 hours'));

        return [
            'total_attempts' => $model->count(),
            'total_attempts_24h' => $model->where('created_at', '>=', $dayAgo)->count(),
            'unique_ips' => $model->groupBy('ip_address')->count(),
            'unique_ips_24h' => $model->where('created_at', '>=', $dayAgo)->groupBy('ip_address')->count(),
            'last_attempt' => $lastRecord->created_at ?? '-'
        ];
    }

    /**
     * Kayıt Sil 🗑️
     */
    public function destroy(int $id): bool
    {
        return (bool) $this->model('common.rateLimit')->where('id', $id)->delete();
    }

    /**
     * Toplu Kayıt Sil 🗑️
     */
    public function destroyBulk(array $ids): int
    {
        return (int) $this->model('common.rateLimit')->whereIn('id', $ids)->delete();
    }

    /**
     * Tüm kayıtları temizler 🧹
     */
    public function clearAll(): bool
    {
        return (bool) $this->model('common.rateLimit')->truncate();
    }
}
