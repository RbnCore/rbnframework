<?php
$trendLabels = array_keys($stats['seven_day_trend'] ?? []);
$trendValues = array_values($stats['seven_day_trend'] ?? []);
?>

<!-- 1. ÜST AKSİYON BUTONLARI & BİLGİLENDİRME BANNERI 📊 -->
<div class="d-flex justify-content-end gap-2 mb-4">
    <a href="{{ $Route->url('webtraffic/report', 'admin') }}"
        class="btn rbn-btn-taupe px-3 py-2 fw-semibold d-flex align-items-center gap-2">
        <i class="ri-file-chart-line"></i> Gelişmiş Tarihsel Raporlar
    </a>
    <a href="{{ $Route->url('webtraffic/google-analytics', 'admin') }}"
        class="btn rbn-btn-secondary px-3 py-2 fw-semibold d-flex align-items-center gap-2">
        <i class="ri-google-fill text-danger"></i> Google Analytics
    </a>
</div>

<!-- Bilgilendirme Notu -->
<div class="alert rbn-alert d-flex align-items-center gap-3 p-3 mb-4 ra-security-tip-alert">
    <i class="ri-information-fill text-warning fs-5 flex-shrink-0"></i>
    <div class="small text-dark lh-base">
        <strong>Analitik Bilgilendirmesi:</strong> Sunucu tabanlı lokal kayıtlarımız bot ve organik trafiği doğrudan IP/istek seviyesinde sayar. Google Analytics gibi tarayıcı tabanlı harici servislerin raporları ile (AdBlocker engellemeleri, gizlilik tercihleri ve çerez politikaları nedeniyle) doğal farklar oluşabilir.
    </div>
</div>

<!-- 2. METRİK STAT KARTLARI 📈 -->
@import('framework', 'Resources/Views/RbnAdmin/Components/webtraffic_stat_cards.rbn.php')

<!-- 3. GÖRSEL GRAFİK ANALİTİĞİ (Trend, Cihazlar & Trafik Kalitesi) 🎨 -->
<div class="row g-4 mb-4">
    <!-- Trend Çizgi Grafiği (6 Birim) -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm p-0 h-100 overflow-hidden">
            <div class="p-4 border-bottom border-light d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                    <i class="ri-line-chart-line text-warning fs-5"></i> Ziyaretçi Trendi (Son 7 Gün)
                </h6>
            </div>
            <div class="p-3 p-md-4">
                <div class="w-100" style="height: 340px;">
                    <canvas id="trafficTrendChart" class="w-100 h-100"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Cihaz Dağılımı Donut Grafiği (3 Birim) -->
    <div class="col-lg-3">
        <div class="card border-0 shadow-sm p-0 h-100 overflow-hidden d-flex flex-column justify-content-between">
            <div class="p-4 border-bottom border-light">
                <h6 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                    <i class="ri-computer-line text-warning fs-5"></i> Cihaz Dağılımı (Bugün)
                </h6>
            </div>
            <div class="p-4 d-flex flex-column align-items-center justify-content-between flex-grow-1">
                <div class="position-relative w-100" style="height: 220px;">
                    <canvas id="deviceDistributionChart" class="w-100 h-100"></canvas>
                </div>

                <!-- Cihaz Lejantı -->
                <div class="ra-traffic-legend-wrap">
                    <div>
                        <span class="ra-traffic-legend-dot" style="background: #1a365d;"></span>
                        <span class="ra-traffic-legend-label">PC</span>
                        <div class="ra-traffic-legend-value">{{ $stats['device_stats']['PC'] }}</div>
                    </div>
                    <div>
                        <span class="ra-traffic-legend-dot" style="background: #c56a3c;"></span>
                        <span class="ra-traffic-legend-label">MOBİL</span>
                        <div class="ra-traffic-legend-value">{{ $stats['device_stats']['Mobile'] }}</div>
                    </div>
                    <div>
                        <span class="ra-traffic-legend-dot" style="background: #d97706;"></span>
                        <span class="ra-traffic-legend-label">TABLET</span>
                        <div class="ra-traffic-legend-value">{{ $stats['device_stats']['Tablet'] }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bot / Kalite Dağılımı Donut Grafiği (3 Birim) -->
    <div class="col-lg-3">
        <div class="card border-0 shadow-sm p-0 h-100 overflow-hidden d-flex flex-column justify-content-between">
            <div class="p-4 border-bottom border-light">
                <h6 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                    <i class="ri-shield-check-line text-success fs-5"></i> Trafik Kalitesi (Bugün)
                </h6>
            </div>
            <div class="p-4 d-flex flex-column align-items-center justify-content-between flex-grow-1">
                <div class="position-relative w-100" style="height: 220px;">
                    <canvas id="botDistributionChart" class="w-100 h-100"></canvas>
                </div>

                <!-- Bot Lejantı -->
                <div class="ra-traffic-legend-wrap">
                    <div>
                        <span class="ra-traffic-legend-dot" style="background: #16a34a;"></span>
                        <span class="ra-traffic-legend-label">ORGANİK</span>
                        <div class="ra-traffic-legend-value">{{ $stats['bot_stats']['organic'] ?? 0 }}</div>
                    </div>
                    <div>
                        <span class="ra-traffic-legend-dot" style="background: #a89f91;"></span>
                        <span class="ra-traffic-legend-label">BOT</span>
                        <div class="ra-traffic-legend-value">{{ $stats['bot_stats']['bot'] ?? 0 }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 4. EN ÇOK ZİYARET EDİLEN SAYFALAR & KAYNAKLAR 📊 -->
<div class="row g-4 mb-4">
    <!-- En Çok Ziyaret Edilen Sayfalar -->
    <div class="col-md-7">
        <div class="card border-0 shadow-sm p-0 h-100 overflow-hidden">
            <div class="p-4 border-bottom border-light">
                <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                    <i class="ri-file-text-line text-warning fs-5"></i> En Çok Ziyaret Edilen Sayfalar (Bugün)
                </h6>
            </div>
            <div class="p-0">
                <div class="table-responsive">
                    <table class="rbn-table rbn-table-hover align-middle mb-0" style="table-layout: fixed; width: 100%;">
                        <thead>
                            <tr>
                                <th class="ps-4">SAYFA URL</th>
                                <th class="text-center pe-4" style="width: 100px;">HİT</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($stats['top_pages'] as $url => $count)
                            <tr>
                                <td class="ps-4 ra-traffic-url-cell">
                                    <span class="font-monospace small text-dark fw-medium ra-traffic-url-text" title="{{ htmlspecialchars($url) }}">{{ $url }}</span>
                                </td>
                                <td class="text-center pe-4" style="white-space: nowrap;">
                                    <span class="rbn-badge rbn-badge-terracotta px-3 py-1">{{ $count }}</span>
                                </td>
                            </tr>
                            @endforeach
                            @if(empty($stats['top_pages']))
                            <tr>
                                <td colspan="2" class="text-center py-5 text-muted small">Henüz veri toplanmadı.</td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Ana Trafik Kaynakları -->
    <div class="col-md-5">
        <div class="card border-0 shadow-sm p-0 h-100 overflow-hidden">
            <div class="p-4 border-bottom border-light">
                <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                    <i class="ri-compass-3-line text-success fs-5"></i> Ana Trafik Kaynakları
                </h6>
            </div>
            <div class="p-0">
                <div class="table-responsive">
                    <table class="rbn-table rbn-table-hover align-middle mb-0" style="table-layout: fixed; width: 100%;">
                        <thead>
                            <tr>
                                <th class="ps-4">KAYNAK</th>
                                <th class="text-center pe-4" style="width: 100px;">HİT</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($stats['top_sources'] as $source => $count)
                            <tr>
                                <td class="ps-4 ra-traffic-url-cell">
                                    @if($source === 'Direct')
                                    <span class="rbn-badge rbn-badge-secondary rbn-badge-square font-monospace rbn-badge-xs">
                                        <i class="ri-flashlight-line me-1 text-warning"></i>Doğrudan
                                    </span>
                                    @else
                                    <span class="text-dark small fw-bold ra-traffic-url-text" title="{{ htmlspecialchars($source) }}">{{ $source }}</span>
                                    @endif
                                </td>
                                <td class="text-center pe-4" style="white-space: nowrap;">
                                    <span class="rbn-badge rbn-badge-secondary px-3 py-1">{{ $count }}</span>
                                </td>
                            </tr>
                            @endforeach
                            @if(empty($stats['top_sources']))
                            <tr>
                                <td colspan="2" class="text-center py-5 text-muted small">Henüz veri toplanmadı.</td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 5. SON ZİYARETÇİ HAREKETLERİ (Sovereign rbn-table-wrap Mimarisi) 📋 -->
<div class="rbn-table-wrap mb-5">
    <div class="ra-traffic-card-header d-flex justify-content-between align-items-center">
        <h6 class="ra-traffic-card-title mb-0">
            <i class="ri-history-line text-warning fs-5"></i> Son 5 Ziyaretçi Hareketi (Bugün)
        </h6>
        <a href="{{ $Route->url('webtraffic/logs', 'admin') }}"
            class="btn rbn-btn-secondary btn-sm px-3 fw-semibold">
            Tümünü Gör <i class="ri-arrow-right-line ms-1 text-warning"></i>
        </a>
    </div>
    <div class="table-responsive">
        <table class="rbn-table rbn-table-hover rbn-table-fixed align-middle mb-0" id="visitor-preview-table">
            <thead>
                <tr>
                    <th class="ps-4" style="width: 140px;">Tarih</th>
                    <th style="width: 100px;">Saat</th>
                    <th style="width: 180px;">IP / Lokasyon</th>
                    <th>Ziyaret Edilen URL</th>
                    <th class="pe-4" style="width: 220px;">Cihaz / Tarayıcı</th>
                </tr>
            </thead>
            <tbody>
                @if(!empty($recentHits))
                @foreach($recentHits as $hit)
                @php
                $rawTime = $hit['time'];
                if (strlen($rawTime) > 8) {
                    $datePart = date('Y-m-d', strtotime($rawTime));
                    $timePart = date('H:i:s', strtotime($rawTime));
                } else {
                    $datePart = now('Y-m-d');
                    $timePart = $rawTime;
                }
                $formattedDate = $this->helper('format')->formatDateTurkish($datePart, 'medium');
                $uaInfo = $this->helper('format')->parseUserAgent($hit['ua'] ?? '');
                @endphp
                <tr>
                    <td class="ps-4">
                        <span class="small fw-bold text-dark">{{ $formattedDate }}</span>
                    </td>
                    <td>
                        <span class="ra-traffic-time-badge">{{ $timePart }}</span>
                    </td>
                    <td>
                        <span class="font-monospace text-dark fw-bold d-block text-truncate" title="{{ $hit['ip'] }}">{{ $hit['ip'] }}</span>
                        @if(!empty($hit['location']))
                            @if(str_starts_with($hit['location'], 'Bot'))
                            <span class="rbn-badge rbn-badge-secondary rbn-badge-xs mt-1 d-inline-block">
                                <i class="ri-robot-2-line me-1"></i>{{ $hit['location'] }}
                            </span>
                            @else
                            <span class="rbn-badge rbn-badge-terracotta rbn-badge-xs mt-1 d-inline-block">
                                @if(!empty($hit['flagClass']))
                                <span class="{{ $hit['flagClass'] }} me-1"></span>
                                @else
                                <i class="ri-map-pin-2-fill me-1"></i>
                                @endif
                                {{ $hit['location'] }}
                            </span>
                            @endif
                        @endif
                    </td>
                    <td class="ra-traffic-url-cell">
                        <span class="font-monospace small text-dark ra-traffic-url-text" title="{{ htmlspecialchars($hit['url']) }}">{{ $hit['url'] }}</span>
                    </td>
                    <td class="pe-4">
                        <div class="d-flex align-items-center gap-1 flex-wrap">
                            <span class="ra-traffic-browser-pill" title="{{ htmlspecialchars($uaInfo['raw']) }}">
                                <i class="{{ $uaInfo['icon'] }} text-warning"></i>
                                <span>{{ $uaInfo['browser'] }}</span>
                            </span>
                            <span class="badge bg-light text-muted border small">{{ $uaInfo['os'] }}</span>
                        </div>
                    </td>
                </tr>
                @endforeach
                @else
                <tr>
                    <td colspan="5" class="text-center py-5">
                        <i class="ri-pulse-line fs-1 text-muted opacity-25 d-block mb-2"></i>
                        <span class="text-muted small">Henüz veri kaydedilmedi.</span>
                    </td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>
    @if(!empty($recentHits))
    <div class="p-3 border-top border-light text-center">
        <a href="{{ $Route->url('webtraffic/logs', 'admin') }}" class="small fw-bold text-warning text-decoration-none">
            Tüm Detaylı Kayıtları İncele <i class="ri-arrow-right-s-line small"></i>
        </a>
    </div>
    @endif
</div>

<!-- Data Bridge for External JS Charts -->
<div id="webtraffic-data" style="display: none;" data-trend-labels='<?= json_encode($trendLabels) ?>'
    data-trend-values='<?= json_encode($trendValues) ?>' data-pc='<?= (int) ($stats['device_stats']['PC'] ?? 0) ?>'
    data-mobile='<?= (int) ($stats['device_stats']['Mobile'] ?? 0) ?>'
    data-tablet='<?= (int) ($stats['device_stats']['Tablet'] ?? 0) ?>'
    data-organic='<?= (int) ($stats['bot_stats']['organic'] ?? 0) ?>'
    data-bot='<?= (int) ($stats['bot_stats']['bot'] ?? 0) ?>'>
</div>