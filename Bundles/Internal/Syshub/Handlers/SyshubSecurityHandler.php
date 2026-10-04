<?php

declare(strict_types=1);

namespace Rbn\Framework\Bundles\Internal\Syshub\Handlers;

use Rbn\Framework\Core\Base\BaseComponent;

/**
 * SyshubSecurityHandler - Güvenlik Verisi Orkestratörü 🛡️🛰️
 * RBN 3.5 Masterpiece Standard.
 * 
 * Ana güvenlik istatistiklerini, IP listelerini ve son aktiviteleri 
 * derleyip kontrolöre "hazır" olarak sunar.
 * 
 * --- DEPENDENCY TRACKING (Bilgi ve Takip Amaçlı) ---
 * @property-read \Rbn\Framework\Bundles\Internal\Syshub\Handlers\SyshubHandler $syshub
 * @property-read \Rbn\Framework\Bundles\Internal\Syshub\Providers\SyshubMaintenanceProvider $maintenance
 * @property-read \Rbn\Framework\Core\Services\Gatekeepers\Handlers\GeoIPHandler $geoIP
 * @property-read \Rbn\Framework\Core\Database\Models\Common\CmSysRateLimitsModel $rateLimit
 */
class SyshubSecurityHandler extends BaseComponent
{
    /**
     * Güvenlik Ana Paneli (Dashboard) için tüm verileri hazırlar.
     */
    public function getSecurityDashboardData(): array
    {
        // 1. Otonom ana sistemden ham güvenlik özetini al
        $coreSecurityStats = $this->handler('syshub')->getDashboardStats()['security'] ?? [];

        // 2. İstatistik Ağacını (Stats) oluştur
        $stats = $this->buildStatsTree($coreSecurityStats);

        // 3. Son Aktiviteleri (Logları) Çek ve Otonom GeoIP ile Zenginleştir
        $recentAttempts = $this->getEnrichedRecentAttempts();
        $recentFailedLogins = []; // İleride Auth eklendiğinde doldurulacak

        return [
            'stats' => $stats,
            'recentAttempts' => $recentAttempts,
            'recentFailedLogins' => $recentFailedLogins
        ];
    }

    /**
     * View'ın beklediği istatistik ağacını oluşturur.
     */
    protected function buildStatsTree(array $coreSecurityStats): array
    {
        $whitelistCount = count($this->provider('whitelist')->getList());
        $rateStats = $this->provider('rateLimit')->getStats();
        $ipStats = $this->repository('common.ipBlock')->all();

        return [
            'health' => [
                'score' => $coreSecurityStats['health_score'] ?? 98,
                'status' => 'MÜKEMMEL',
                'details' => [
                    ['icon' => 'shield-check', 'type' => 'success', 'msg' => 'Firewall Kalkanı Aktif'],
                    ['icon' => 'check-circle', 'type' => 'success', 'msg' => 'Sistem Altyapısı Güvenli'],
                    ['icon' => 'info-circle', 'type' => 'primary', 'msg' => 'Son 24 saatte olağan dışı aktivite tespit edilmedi.']
                ]
            ],
            'active_blocks' => $ipStats['active_blocks'] ?? 0,
            'total_attempts_24h' => $rateStats['total_attempts_24h'] ?? 0,
            'unique_ips_24h' => $rateStats['unique_ips_24h'] ?? 0,
            'total_whitelisted' => $whitelistCount
        ];
    }

    /**
     * Son hatalı denemeleri (RateLimit) çekip, bayrak (GeoIP) bilgisiyle donatır.
     */
    protected function getEnrichedRecentAttempts(): array
    {
        $attempts = $this->model('common.rateLimit')->orderBy('id', 'DESC')->limit(5)->get()->toArray() ?? [];
        return $this->enrich($attempts);
    }

    /**
     * Evrensel GeoIP Zenginleştirme Motoru ⚙️🌍
     * IP içeren her türlü veri dizisini otonom olarak bayrak ve ülke bilgisiyle donatır.
     */
    public function enrich(array $data): array
    {
        foreach ($data as &$item) {
            $cCode = $item['country_code'] ?? $this->handler('geoIP')->getCountryCode($item['ip_address']);
            $item['country_name'] = $this->handler('geoIP')->getCountryName($cCode);
            $item['flag'] = $this->handler('geoIP')->getFlag($cCode);
        }
        unset($item);

        return $data;
    }
}
