<?php
$trendLabels = array_keys($trendData ?? []);
$trendValues = array_values($trendData ?? []);
$organicCount = $botStats['organic'] ?? 0;
$botCount = $botStats['bot'] ?? 0;
$organicPercent = $totalHits > 0 ? round(($organicCount / $totalHits) * 100) : 0;
$botPercent = $totalHits > 0 ? round(($botCount / $totalHits) * 100) : 0;

$formattedStartDate = $this->helper('format')->formatDateTurkish($startDate, 'medium');
$formattedEndDate = $this->helper('format')->formatDateTurkish($endDate, 'medium');
?>

<!-- 1. TARİH ARALIĞI FİLTRE KARTI (Terracotta Craft Mimarisi) 🗓️ -->
<div class="rbn-card mb-4 p-4">
    <form method="GET" action="{{ $Route->url('webtraffic/report', 'admin') }}" id="report-filter-form" class="row g-3 align-items-end">
        <div class="col-12 col-md-3">
            <label for="start_date" class="ra-stat-label mb-1">Başlangıç Tarihi</label>
            <input type="date" name="start_date" id="start_date"
                class="form-control form-control-sm font-monospace fw-medium"
                value="{{ $startDate }}" max="{{ now('Y-m-d') }}">
        </div>
        <div class="col-12 col-md-3">
            <label for="end_date" class="ra-stat-label mb-1">Bitiş Tarihi</label>
            <input type="date" name="end_date" id="end_date"
                class="form-control form-control-sm font-monospace fw-medium"
                value="{{ $endDate }}" max="{{ now('Y-m-d') }}">
        </div>
        <div class="col-12 col-md-4">
            <label class="ra-stat-label mb-1">Hızlı Aralık Seçimi</label>
            <div class="d-flex gap-2 flex-wrap">
                <button type="button" class="rbn-btn rbn-btn-outline btn-sm px-3" onclick="setQuickRange(0)">Bugün</button>
                <button type="button" class="rbn-btn rbn-btn-outline btn-sm px-3" onclick="setQuickRange(7)">Son 7 Gün</button>
                <button type="button" class="rbn-btn rbn-btn-outline btn-sm px-3" onclick="setQuickRange(30)">Son 30 Gün</button>
            </div>
        </div>
        <div class="col-12 col-md-2 text-end">
            <button type="submit" class="rbn-btn rbn-btn-primary btn-sm w-100 py-2 fw-bold d-flex align-items-center justify-content-center gap-1">
                <i class="ri-filter-3-line"></i> Raporu Getir
            </button>
        </div>
    </form>
</div>

<!-- 2. DÖNEMLİK STAT KARTLARI (Terracotta Standart Metrikleri) 📊 -->
<div class="row g-4 mb-4">
    <!-- Toplam Hit -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="ra-stat-card ra-stat-terracotta h-100">
            <div class="d-flex align-items-center mb-3">
                <div class="ra-stat-icon me-3">
                    <i class="ri-flashlight-line"></i>
                </div>
                <div>
                    <div class="ra-stat-label">DÖNEMLİK TOPLAM HİT</div>
                    <h3 class="ra-stat-value"><?= number_format((int)$totalHits) ?></h3>
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center pt-2 border-top border-secondary border-opacity-10 small">
                <span class="text-muted"><i class="ri-calendar-line me-1 text-warning"></i><?= $formattedStartDate ?></span>
                <span class="text-muted fw-bold"><?= $formattedEndDate ?></span>
            </div>
        </div>
    </div>

    <!-- Popüler Lokasyon -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="ra-stat-card ra-stat-navy h-100">
            <div class="d-flex align-items-center mb-3">
                <div class="ra-stat-icon me-3">
                    <i class="ri-map-pin-2-line"></i>
                </div>
                <div class="flex-grow-1 min-w-0">
                    <div class="ra-stat-label">EN ÇOK ZİYARETÇİ</div>
                    <span class="ra-stat-value fs-4 text-truncate d-block" title="<?= htmlspecialchars($topLocation) ?>">
                        <?= htmlspecialchars($topLocation) ?>
                    </span>
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center pt-2 border-top border-primary border-opacity-10 small">
                <span class="text-muted">Lider Şehir / Bölge</span>
                <strong class="text-dark">Coğrafi Zirve</strong>
            </div>
        </div>
    </div>

    <!-- Organik Trafik -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="ra-stat-card ra-stat-success h-100">
            <div class="d-flex align-items-center mb-3">
                <div class="ra-stat-icon me-3">
                    <i class="ri-user-heart-line"></i>
                </div>
                <div>
                    <div class="ra-stat-label">ORGANİK TRAFİK</div>
                    <h3 class="ra-stat-value"><?= number_format((int)$organicCount) ?></h3>
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center pt-2 border-top border-success border-opacity-10 small">
                <span class="text-muted"><i class="ri-pie-chart-line me-1 text-success"></i>Trafik Oranı:</span>
                <strong class="text-success fw-bold">%<?= $organicPercent ?></strong>
            </div>
        </div>
    </div>

    <!-- Bot Trafiği -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="ra-stat-card ra-stat-warning h-100">
            <div class="d-flex align-items-center mb-3">
                <div class="ra-stat-icon me-3">
                    <i class="ri-robot-2-line"></i>
                </div>
                <div>
                    <div class="ra-stat-label">BOT TRAFİĞİ</div>
                    <h3 class="ra-stat-value"><?= number_format((int)$botCount) ?></h3>
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center pt-2 border-top border-warning border-opacity-10 small">
                <span class="text-muted"><i class="ri-shield-user-line me-1 text-warning"></i>Bot Oranı:</span>
                <strong class="text-muted fw-bold">%<?= $botPercent ?></strong>
            </div>
        </div>
    </div>
</div>

<!-- 3. ZİYARETÇİ TRENDİ ÇİZGİ GRAFİĞİ (Terracotta Chart) 📈 -->
<div class="rbn-card p-4 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="ra-traffic-card-title mb-0">
            <i class="ri-line-chart-line text-warning fs-5"></i> Dönem İçi Ziyaretçi Trendi
        </h6>
        <span class="ra-stat-label text-muted">GÜNLÜK HİT DAĞILIMI</span>
    </div>
    <div style="height: 340px; width: 100%;">
        <canvas id="rangeTrendChart" class="w-100 h-100"></canvas>
    </div>
</div>

<!-- 4. DAĞILIM GRAFİKLERİ (Cihaz ve Kalite) 🥧 -->
<div class="row g-4 mb-4">
    <!-- Cihaz Dağılımı -->
    <div class="col-12 col-lg-6">
        <div class="rbn-card p-4 h-100 d-flex flex-column justify-content-between">
            <h6 class="ra-traffic-card-title mb-3">
                <i class="ri-computer-line text-warning fs-5"></i> Dönem İçi Cihaz Dağılımı
            </h6>
            <div style="height: 220px; width: 100%;" class="position-relative my-2">
                <canvas id="rangeDeviceChart" class="w-100 h-100"></canvas>
            </div>
            <div class="ra-traffic-legend-wrap">
                <div>
                    <span class="ra-traffic-legend-dot" style="background: #1a365d;"></span>
                    <span class="ra-traffic-legend-label">Masaüstü</span>
                    <div class="ra-traffic-legend-value">{{ $deviceStats['PC'] ?? 0 }}</div>
                </div>
                <div>
                    <span class="ra-traffic-legend-dot" style="background: #c56a3c;"></span>
                    <span class="ra-traffic-legend-label">Mobil</span>
                    <div class="ra-traffic-legend-value">{{ $deviceStats['Mobile'] ?? 0 }}</div>
                </div>
                <div>
                    <span class="ra-traffic-legend-dot" style="background: #d97706;"></span>
                    <span class="ra-traffic-legend-label">Tablet</span>
                    <div class="ra-traffic-legend-value">{{ $deviceStats['Tablet'] ?? 0 }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Trafik Kalitesi / Bot -->
    <div class="col-12 col-lg-6">
        <div class="rbn-card p-4 h-100 d-flex flex-column justify-content-between">
            <h6 class="ra-traffic-card-title mb-3">
                <i class="ri-shield-check-line text-success fs-5"></i> Trafik Kalitesi (Bot / Organik)
            </h6>
            <div style="height: 220px; width: 100%;" class="position-relative my-2">
                <canvas id="rangeBotChart" class="w-100 h-100"></canvas>
            </div>
            <div class="ra-traffic-legend-wrap">
                <div>
                    <span class="ra-traffic-legend-dot" style="background: #16a34a;"></span>
                    <span class="ra-traffic-legend-label">Organik</span>
                    <div class="ra-traffic-legend-value">{{ $organicCount }}</div>
                </div>
                <div>
                    <span class="ra-traffic-legend-dot" style="background: #a89f91;"></span>
                    <span class="ra-traffic-legend-label">Bot Ziyaret</span>
                    <div class="ra-traffic-legend-value">{{ $botCount }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 5. LİSTE TABLOLARI (En Çok Ziyaret Edilen Sayfalar & Kaynaklar) 📋 -->
<div class="row g-4 mb-5">
    <!-- Sayfalar -->
    <div class="col-12 col-lg-6">
        <div class="rbn-table-wrap h-100">
            <div class="ra-traffic-card-header">
                <h6 class="ra-traffic-card-title mb-0">
                    <i class="ri-file-text-line text-warning fs-5"></i> En Çok Ziyaret Edilen Sayfalar
                </h6>
            </div>
            <div class="table-responsive">
                <table class="rbn-table rbn-table-hover rbn-table-fixed align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Sayfa URL</th>
                            <th class="text-center pe-4" style="width: 120px; white-space: nowrap;">Hit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($topPages as $url => $count)
                        <tr>
                            <td class="ps-4 ra-traffic-url-cell">
                                <span class="font-monospace small text-dark fw-medium ra-traffic-url-text" title="{{ htmlspecialchars($url) }}">{{ $url }}</span>
                            </td>
                            <td class="text-center pe-4" style="white-space: nowrap;">
                                <span class="rbn-badge rbn-badge-terracotta px-3 py-1 font-monospace fw-bold">{{ number_format($count) }}</span>
                            </td>
                        </tr>
                        @endforeach
                        @if(empty($topPages))
                        <tr>
                            <td colspan="2" class="text-center py-5 text-muted small">
                                <i class="ri-file-search-line fs-2 text-warning opacity-50 d-block mb-1"></i>
                                Bu aralıkta sayfa ziyareti bulunamadı.
                            </td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Kaynaklar -->
    <div class="col-12 col-lg-6">
        <div class="rbn-table-wrap h-100">
            <div class="ra-traffic-card-header">
                <h6 class="ra-traffic-card-title mb-0">
                    <i class="ri-compass-3-line text-success fs-5"></i> Ana Trafik Kaynakları
                </h6>
            </div>
            <div class="table-responsive">
                <table class="rbn-table rbn-table-hover rbn-table-fixed align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Kaynak</th>
                            <th class="text-center pe-4" style="width: 120px; white-space: nowrap;">Hit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($topSources as $source => $count)
                        <tr>
                            <td class="ps-4 ra-traffic-url-cell">
                                @if($source === 'Direct')
                                <span class="rbn-badge rbn-badge-secondary rbn-badge-xs">
                                    <i class="ri-flashlight-line me-1 text-warning"></i>Doğrudan (Direct)
                                </span>
                                @else
                                <span class="text-dark fw-bold small ra-traffic-url-text" title="{{ htmlspecialchars($source) }}"><i class="ri-external-link-line me-1 text-muted"></i>{{ $source }}</span>
                                @endif
                            </td>
                            <td class="text-center pe-4" style="white-space: nowrap;">
                                <span class="rbn-badge rbn-badge-secondary px-3 py-1 font-monospace fw-bold">{{ number_format($count) }}</span>
                            </td>
                        </tr>
                        @endforeach
                        @if(empty($topSources))
                        <tr>
                            <td colspan="2" class="text-center py-5 text-muted small">
                                <i class="ri-radar-line fs-2 text-warning opacity-50 d-block mb-1"></i>
                                Bu aralıkta kaynak verisi bulunamadı.
                            </td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Data Bridge for External JS Charts -->
<div id="webtraffic-range-data" 
     style="display: none;"
     data-trend-labels='<?= json_encode($trendLabels) ?>' 
     data-trend-values='<?= json_encode($trendValues) ?>'
     data-pc='<?= (int)($deviceStats['PC'] ?? 0) ?>'
     data-mobile='<?= (int)($deviceStats['Mobile'] ?? 0) ?>'
     data-tablet='<?= (int)($deviceStats['Tablet'] ?? 0) ?>'
     data-organic='<?= (int)$organicCount ?>'
     data-bot='<?= (int)$botCount ?>'>
</div>
