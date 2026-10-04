<?php

namespace Rbn\Framework\Bundles\RbnSuite\RbnAdmin\Controllers;

use Rbn\Framework\Core\Base\Attributes\SubModule;

/**
 * WebtrafficController - Analytics Dashboard
 */
#[SubModule(
    entity: 'webtraffic',
    service: 'analytics'
)]
class WebtrafficController extends RbnAdminController
{
    /**
     * Analytics Dashboard - Özet Ekran
     */
    public function index()
    {
        $traffic = $this->service->traffic();
        $stats = $traffic->summary();

        // Ortak grafikleri yükle
        $this->service('asset')->prepare('@fw/RbnCommon/js/components/rbnCharts.js', 'panel');

        // 🎼 RBN 3.5: Ziyaretçi konum bayraklarını GeoHelper ile çözümle 🌍
        $geoHelper = $this->helper('Geo');
        $countryFlags = $geoHelper->countryFlags();

        // Popüler Lokasyonu Çözümle (Genel & Bugün)
        if (isset($stats['top_location'])) {
            $topLoc = strtoupper(trim($stats['top_location']));
            if (isset($countryFlags[$topLoc])) {
                $stats['top_location_name'] = $topLoc === 'US' ? 'A.B.D' : $countryFlags[$topLoc]['name'];
                $stats['top_location_flag'] = 'fi fi-' . strtolower($topLoc) . ' flag-icon flag-icon-' . strtolower($topLoc);
            } else {
                $stats['top_location_name'] = $stats['top_location'] === 'US' ? 'A.B.D' : $stats['top_location'];
                $stats['top_location_flag'] = null;
            }
        }

        if (isset($stats['top_location_today'])) {
            $topLocToday = strtoupper(trim($stats['top_location_today']));
            if (isset($countryFlags[$topLocToday])) {
                $stats['top_location_today_name'] = $topLocToday === 'US' ? 'A.B.D' : $countryFlags[$topLocToday]['name'];
                $stats['top_location_today_flag'] = 'fi fi-' . strtolower($topLocToday) . ' flag-icon flag-icon-' . strtolower($topLocToday);
            } else {
                $stats['top_location_today_name'] = $stats['top_location_today'] === 'US' ? 'A.B.D' : $stats['top_location_today'];
                $stats['top_location_today_flag'] = null;
            }
        }

        // 2. Bugünün Son 5 Hitini Al (Özet Tablo İçin - En yeni en üstte)
        $hits = $traffic->query()->forToday()->limit(20)->get();
        $recentHits = array_filter($hits, fn($hit) => isset($hit['time']));
        usort($recentHits, function ($a, $b) {
            return strcmp($b['time'] ?? '', $a['time'] ?? '');
        });
        $recentHits = array_slice($recentHits, 0, 5);

        foreach ($recentHits as &$hit) {
            $hit['flagClass'] = null;
            $loc = trim($hit['location'] ?? '');
            $locLower = strtolower($loc);

            if (\is_local() || $locLower === 'localhost' || $locLower === 'local' || ($hit['ip'] ?? '') === '127.0.0.1' || ($hit['ip'] ?? '') === '::1') {
                $hit['flagClass'] = 'bi bi-geo-alt-fill';
                if (empty($hit['location'])) {
                    $hit['location'] = 'Localhost';
                }
            } elseif (!empty($loc) && !str_starts_with($loc, 'Bot')) {
                // Konum doğrudan 2 harfli ISO kodu olabilir (SG, BR, TR vb.) veya eski "🏳️ SG" şeklinde olabilir
                $parts = explode(' ', $loc);
                $isoCandidate = strtoupper(end($parts));

                if (isset($countryFlags[$isoCandidate])) {
                    $hit['location'] = $countryFlags[$isoCandidate]['name'];
                    $hit['flagClass'] = 'fi fi-' . strtolower($isoCandidate) . ' flag-icon flag-icon-' . strtolower($isoCandidate);
                } elseif ($isoCandidate === 'XX') {
                    $hit['location'] = 'Bilinmiyor';
                } else {
                    // Normal isim araması fallback (Örn: "Singapur" veya "sg" olarak kaydedilmişse)
                    $matchedIso = null;
                    foreach ($countryFlags as $iso => $info) {
                        if (strtolower($iso) === $locLower || strtolower($info['name']) === $locLower) {
                            $matchedIso = $iso;
                            break;
                        }
                    }
                    if ($matchedIso !== null) {
                        $hit['location'] = $countryFlags[$matchedIso]['name'];
                        $hit['flagClass'] = 'fi fi-' . strtolower($matchedIso) . ' flag-icon flag-icon-' . strtolower($matchedIso);
                    }
                }
            }
        }
        unset($hit);

        // 3. Render
        $this->render('Webtraffic/index', [
            'stats' => $stats,
            'recentHits' => $recentHits,
        ]);
    }

    /**
     * Tüm Ziyaretçi Kayıtları (Detaylı Tablo)
     */
    public function logs()
    {
        $selectedDate = $this->request->input('date') ?? \now('Y-m-d');
        $visitorType = $this->request->input('type') ?? 'all';

        // 1. Verileri Çek (Sovereign Discovery) 🛰️⚓
        $traffic = $this->service->traffic();
        $hits = $traffic->query()->forDate($selectedDate)->get();

        // 🎼 RBN 3.5: [DATA SANITIZATION] 🧬⚓ - Boş veya hatalı satırları ayıkla.
        $allHits = array_values(array_filter($hits, fn($hit) => isset($hit['time'])));

        // 🤖 Bot/Organik Filtresi Uygula
        if ($visitorType === 'organic') {
            $allHits = array_values(array_filter($allHits, function ($hit) {
                return !str_starts_with($hit['location'] ?? '', 'Bot');
            }));
        } elseif ($visitorType === 'bot') {
            $allHits = array_values(array_filter($allHits, function ($hit) {
                return str_starts_with($hit['location'] ?? '', 'Bot');
            }));
        }

        usort($allHits, function ($a, $b) {
            return strcmp($b['time'] ?? '', $a['time'] ?? '');
        });

        // 🎼 RBN 3.5: Ziyaretçi konum bayraklarını GeoHelper ile çözümle 🌍
        $geoHelper = $this->helper('Geo');
        $countryFlags = $geoHelper->countryFlags();
        foreach ($allHits as &$hit) {
            $hit['flagClass'] = null;
            $loc = trim($hit['location'] ?? '');
            $locLower = strtolower($loc);

            if (\is_local() || $locLower === 'localhost' || $locLower === 'local' || ($hit['ip'] ?? '') === '127.0.0.1' || ($hit['ip'] ?? '') === '::1') {
                $hit['flagClass'] = 'bi bi-geo-alt-fill';
                if (empty($hit['location'])) {
                    $hit['location'] = 'Localhost';
                }
            } elseif (!empty($loc) && !str_starts_with($loc, 'Bot')) {
                // Konum doğrudan 2 harfli ISO kodu olabilir (SG, BR, TR vb.) veya eski "🏳️ SG" şeklinde olabilir
                $parts = explode(' ', $loc);
                $isoCandidate = strtoupper(end($parts));

                if (isset($countryFlags[$isoCandidate])) {
                    $hit['location'] = $countryFlags[$isoCandidate]['name'];
                    $hit['flagClass'] = 'fi fi-' . strtolower($isoCandidate) . ' flag-icon flag-icon-' . strtolower($isoCandidate);
                } elseif ($isoCandidate === 'XX') {
                    $hit['location'] = 'Bilinmiyor';
                } else {
                    // Normal isim araması fallback (Örn: "Singapur" veya "sg" olarak kaydedilmişse)
                    $matchedIso = null;
                    foreach ($countryFlags as $iso => $info) {
                        if (strtolower($iso) === $locLower || strtolower($info['name']) === $locLower) {
                            $matchedIso = $iso;
                            break;
                        }
                    }
                    if ($matchedIso !== null) {
                        $hit['location'] = $countryFlags[$matchedIso]['name'];
                        $hit['flagClass'] = 'fi fi-' . strtolower($matchedIso) . ' flag-icon flag-icon-' . strtolower($matchedIso);
                    }
                }
            }
        }
        unset($hit);

        // 2. Pagination (RBN 3.5: Context-Aware Pagination) 📑
        $paginator = $this->paginate($allHits);

        $this->render('Webtraffic/logs', [
            'selectedDate' => $selectedDate,
            'paginator' => $paginator,
            'visitorType' => $visitorType,
        ]);
    }

    /**
     * Gelişmiş Tarihsel Trafik Raporu 📊📈
     */
    public function report()
    {
        // Varsayılan tarih aralığı son 7 gün
        $startDate = $this->request->input('start_date') ?? \now('Y-m-d', strtotime('-6 days'));
        $endDate = $this->request->input('end_date') ?? \now('Y-m-d');

        $traffic = $this->service->traffic();

        // Ortak grafikleri yükle
        $this->service('asset')->prepare('@fw/RbnCommon/js/components/rbnCharts.js', 'panel');

        // Seçilen tarih aralığındaki tüm logları çek
        $hits = $traffic->query()->forRange($startDate, $endDate)->get();
        $allHits = array_values(array_filter($hits, fn($hit) => isset($hit['time'])));

        // İstatistikleri hesapla
        $totalHits = count($allHits);

        $deviceStats = ['PC' => 0, 'Mobile' => 0, 'Tablet' => 0];
        $botStats = ['organic' => 0, 'bot' => 0];
        $topPages = [];
        $topSources = [];
        $trendData = [];

        // Trend haritası için tarihleri sıfırla
        $start = strtotime($startDate);
        $end = strtotime($endDate);
        for ($current = $start; $current <= $end; $current = strtotime("+1 day", $current)) {
            $dateStr = date('Y-m-d', $current);
            $trendData[$dateStr] = 0;
        }

        $geoHelper = $this->helper('Geo');
        $countryFlags = $geoHelper->countryFlags();
        $locationStats = [];

        foreach ($allHits as $hit) {
            // Trend verisi
            $hitDate = $hit['date'] ?? \now('Y-m-d');
            if (isset($trendData[$hitDate])) {
                $trendData[$hitDate]++;
            }

            // Cihaz Dağılımı
            $ua = strtolower($hit['ua'] ?? '');
            if (str_contains($ua, 'ipad') || str_contains($ua, 'tablet')) {
                $deviceStats['Tablet']++;
            } elseif (str_contains($ua, 'mobile') || str_contains($ua, 'android') || str_contains($ua, 'iphone')) {
                $deviceStats['Mobile']++;
            } else {
                $deviceStats['PC']++;
            }

            // Lokasyon / Ülke Dağılımı
            $loc = trim($hit['location'] ?? 'Bilinmiyor');
            $locationStats[$loc] = ($locationStats[$loc] ?? 0) + 1;

            // Trafik Kalitesi (Bot / Organik)
            if (str_starts_with($loc, 'Bot')) {
                $botStats['bot']++;
            } else {
                $botStats['organic']++;
            }

            // En Çok Ziyaret Edilen Sayfalar
            $url = $hit['url'] ?? 'Unknown';
            $topPages[$url] = ($topPages[$url] ?? 0) + 1;

            // Trafik Kaynakları
            $source = $hit['referer'] ?? 'Direct';
            $topSources[$source] = ($topSources[$source] ?? 0) + 1;
        }

        // Popüler Lokasyonu Bul
        $topLocation = 'Bilinmiyor';
        if (!empty($locationStats)) {
            // Botları ve localhost'u ayıkla
            $locs = $locationStats;
            foreach (array_keys($locs) as $locKey) {
                if (str_starts_with($locKey, 'Bot') || str_starts_with($locKey, '🤖') || str_contains($locKey, 'Bot')) {
                    unset($locs[$locKey]);
                }
            }
            if (count($locs) > 1) {
                unset($locs['Localhost']);
            }
            arsort($locs);
            $topLocationIso = key($locs) ?: 'Bilinmiyor';

            $topLocationIsoUpper = strtoupper($topLocationIso);
            if (isset($countryFlags[$topLocationIsoUpper])) {
                $topLocation = $topLocationIsoUpper === 'US' ? 'A.B.D' : $countryFlags[$topLocationIsoUpper]['name'];
            } else {
                $topLocation = $topLocationIsoUpper === 'US' ? 'A.B.D' : $topLocationIso;
            }
        }

        // Sayfa ve Kaynakları sırala ve sınırla
        arsort($topPages);
        $topPages = array_slice($topPages, 0, 10, true);

        arsort($topSources);
        $topSources = array_slice($topSources, 0, 10, true);

        // Render
        $this->render('Webtraffic/report', [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'totalHits' => $totalHits,
            'deviceStats' => $deviceStats,
            'botStats' => $botStats,
            'topPages' => $topPages,
            'topSources' => $topSources,
            'trendData' => $trendData,
            'topLocation' => $topLocation,
        ]);
    }

    /**
     * Google Analytics Entegrasyon Raporu 🤖📊
     */
    public function googleAnalytics()
    {
        $analyticsService = $this->service('analytics');
        $isActive = $analyticsService->isGoogleAnalyticsActive();
        
        $startDate = $this->request->input('start_date');
        $endDate = $this->request->input('end_date');

        // Apply fallback defaults if not provided (default last 30 days)
        if (empty($startDate) || empty($endDate)) {
            $startDate = date('Y-m-d', strtotime('-29 days'));
            $endDate = date('Y-m-d');
        }

        $reportData = [];
        if ($isActive) {
            $this->service('asset')->prepare('@fw/RbnAdmin/js/rbnWebtraffic.js', 'panel');
            $reportData = $analyticsService->getGoogleAnalyticsReports($startDate, $endDate);
        }

        $this->render('Webtraffic/google', [
            'isActive' => $isActive,
            'reportData' => $reportData,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }

    /**
     * Google Analytics Türkiye Harita Raporu 🤖🗺️
     */
    public function googleAnalyticsMap()
    {
        $analyticsService = $this->service('analytics');
        $isActive = $analyticsService->isGoogleAnalyticsActive();
        
        $startDate = $this->request->input('start_date');
        $endDate = $this->request->input('end_date');

        // Apply fallback defaults if not provided (default last 30 days)
        if (empty($startDate) || empty($endDate)) {
            $startDate = date('Y-m-d', strtotime('-29 days'));
            $endDate = date('Y-m-d');
        }

        $reportData = [];
        $cityStats = [];
        $maxUsers = 1;
        if ($isActive) {
            $reportData = $analyticsService->getGoogleAnalyticsReports($startDate, $endDate, 'map');
            
            // Build dynamic TR City Mapping via DataHelper & TextHelper
            $dataHelper = $this->helper('Data');
            $textHelper = $this->helper('Text');
            $cityMapping = [];
            if ($dataHelper && $textHelper) {
                $locations = $dataHelper->get('tr-locations');
                if (is_array($locations)) {
                    foreach (array_keys($locations) as $province) {
                        $provSlug = $textHelper->turkishSlug($province);
                        $provEn = strtolower($textHelper->toEnglishAlphabet($province));
                        $cityMapping[$provEn] = $provSlug;
                        $cityMapping[strtolower($province)] = $provSlug;
                        $cityMapping[$provSlug] = $provSlug;
                    }
                }
            }

            // Resolve city stats for heatmap
            if (!empty($reportData['cities'])) {
                foreach ($reportData['cities'] as $city) {
                    $name = trim($city['city'] ?? '');
                    $users = (int) ($city['activeUsers'] ?? 0);
                    if ($users > $maxUsers) {
                        $maxUsers = $users;
                    }

                    $cleanKey = $textHelper ? strtolower($textHelper->toEnglishAlphabet($name)) : strtolower($name);
                    $slug = $cityMapping[$cleanKey] ?? ($cityMapping[strtolower($name)] ?? ($textHelper ? $textHelper->turkishSlug($name) : strtolower($name)));

                    if ($slug) {
                        $cityStats[$slug] = ($cityStats[$slug] ?? 0) + $users;
                    }
                }
            }
        }

        $this->render('Webtraffic/google_map', [
            'isActive' => $isActive,
            'reportData' => $reportData,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'cityStats' => $cityStats,
            'maxUsers' => $maxUsers
        ]);
    }
}
