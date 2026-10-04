<?php
/**
 * RBN 3.5 Masterpiece - Google Analytics 4 (GA4) Dashboard 📊🏛️
 */

$startDate = $startDate ?? '';
$endDate = $endDate ?? '';

$trendLabels = [];
$trendUsers = [];
$trendPageViews = [];

if ($isActive && isset($reportData['trend']) && !isset($reportData['error'])) {
    foreach ($reportData['trend'] as $day) {
        $dateStr = $day['date'];
        if (strlen($dateStr) === 8) {
            $formattedDate = substr($dateStr, 6, 2) . '/' . substr($dateStr, 4, 2);
        } else {
            $formattedDate = $dateStr;
        }
        $trendLabels[] = $formattedDate;
        $trendUsers[] = (int)($day['activeUsers'] ?? 0);
        $trendPageViews[] = (int)($day['screenPageViews'] ?? 0);
    }
}

$daysDiff = 30;
$start = $startDate ?? null;
$end = $endDate ?? null;
if ($start && $end) {
    $diff = strtotime($end) - strtotime($start);
    $daysDiff = (int)round($diff / (60 * 60 * 24)) + 1;
}
?>

<?php if (!$isActive): ?>
    <!-- ⚙️ SETUP GUIDE / PROPERTY ID MISSING -->
    <div class="rbn-card p-5 text-center my-4">
        <div class="py-3">
            <div class="rbn-stat-icon bg-warning-subtle text-warning rounded-circle mb-3 mx-auto" style="width: 70px; height: 70px; font-size: 2rem;">
                <i class="ri-sound-module-line"></i>
            </div>
            <h4 class="fw-black text-dark mb-2">Google Analytics Entegrasyonu Hazır Değil</h4>
            <div class="mx-auto text-muted small mb-4" style="max-width: 620px;">
                <p class="mb-4">
                    Google Analytics API'sinden veri çekebilmek için sitenizin <strong>GA4 Mülk Kimliği (Property ID)</strong> bilgisinin tanımlanması ve servis hesabı anahtarının yüklenmesi gerekmektedir.
                </p>
                
                <div class="text-start p-4 rounded-3 border mb-3" style="background: var(--rbn-admin-surface, #fbf9f5); border-color: var(--rbn-admin-border-subtle, #e5ded3) !important;">
                    <h6 class="ra-traffic-card-title mb-3"><i class="ri-information-line text-warning me-1"></i>Kurulum Adımları:</h6>
                    <ol class="d-flex flex-column gap-2 mb-0 ps-3 small text-dark">
                        <li>
                            <strong>Mülk Kimliğini (Property ID) Ekleyin:</strong>
                            Google Analytics panelinde <strong>Yönetici &gt; Mülk Ayarları</strong> sayfasından alacağınız sayıyı veritabanına <code>GOOGLE_ANALYTICS_KEY</code> anahtarıyla ekleyin.
                        </li>
                        <li>
                            <strong>Anahtar Dosyasını Yükleyin:</strong>
                            Google Cloud Console'dan aldığınız JSON anahtar dosyasını şu konuma kaydedin:
                            <code class="d-block bg-white p-2 border rounded-2 mt-2 text-danger">projects/&lt;proje_adi&gt;/Resources/Data/google-<?= active_project_key() ?>.json</code>
                        </li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

<?php elseif (isset($reportData['error']) || isset($reportData['api_error'])): ?>
    <!-- ❌ API CONNECTION ERROR BANNER -->
    <div class="rbn-card p-5 text-center my-4">
        <div class="py-3">
            <div class="rbn-stat-icon bg-danger-subtle text-danger rounded-circle mb-3 mx-auto" style="width: 70px; height: 70px; font-size: 2rem;">
                <i class="ri-error-warning-line"></i>
            </div>
            <h5 class="fw-black text-dark mb-2">Google Analytics API Bağlantı Hatası</h5>
            <div class="font-monospace small mb-4 mx-auto p-3 col-md-8 text-start rounded-3 bg-white border border-danger border-opacity-25 text-danger">
                <i class="ri-close-circle-fill me-2"></i>
                <?= htmlspecialchars($reportData['message'] ?? $reportData['api_error'] ?? 'Bilinmeyen API Hatası') ?>
            </div>
            <div class="mx-auto text-muted small text-start p-4 rounded-3 border col-md-9 text-dark" style="background: var(--rbn-admin-surface, #fbf9f5); border-color: var(--rbn-admin-border-subtle, #e5ded3) !important;">
                <h6 class="ra-traffic-card-title mb-2"><i class="ri-question-line me-2 text-danger"></i>Olası Çözümler ve Kontroller:</h6>
                <ul class="d-flex flex-column gap-2 mb-0 ps-3">
                    <li>Google Cloud projenizde <strong>Google Analytics Data API</strong> servisinin etkinleştirildiğinden emin olun.</li>
                    <li>Servis Hesabı (Service Account) e-posta adresine Google Analytics panelinde <strong>Görüntüleyici</strong> yetkisi verildiğinden emin olun.</li>
                    <li>Mülk Kimliğinin (Property ID: <code><?= htmlspecialchars($reportData['property_id'] ?? 'Belirtilmemiş') ?></code>) doğruluğunu kontrol edin.</li>
                </ul>
            </div>
        </div>
    </div>

<?php else: ?>
    <!-- 1. TARİH ARALIĞI FİLTRESİ 🗓️ -->
    <div class="rbn-card mb-4 p-4">
        <form method="GET" action="{{ $Route->url('webtraffic/google-analytics', 'admin') }}" id="report-filter-form" class="row g-3 align-items-end">
            <div class="col-12 col-md-3">
                <label for="start_date" class="ra-stat-label mb-1">Başlangıç Tarihi</label>
                <input type="date" name="start_date" id="start_date" class="form-control form-control-sm font-monospace fw-medium" value="{{ $startDate }}" max="{{ now('Y-m-d') }}">
            </div>
            <div class="col-12 col-md-3">
                <label for="end_date" class="ra-stat-label mb-1">Bitiş Tarihi</label>
                <input type="date" name="end_date" id="end_date" class="form-control form-control-sm font-monospace fw-medium" value="{{ $endDate }}" max="{{ now('Y-m-d') }}">
            </div>
            <div class="col-12 col-md-4">
                <label class="ra-stat-label mb-1">Hızlı Aralık Seçimi</label>
                <div class="d-flex gap-2 flex-wrap">
                    <button type="button" class="rbn-btn rbn-btn-outline btn-sm px-3" onclick="setQuickRange(7)">Son 7 Gün</button>
                    <button type="button" class="rbn-btn rbn-btn-outline btn-sm px-3" onclick="setQuickRange(30)">Son 30 Gün</button>
                    <button type="button" class="rbn-btn rbn-btn-outline btn-sm px-3" onclick="setQuickRange(90)">Son 90 Gün</button>
                </div>
            </div>
            <div class="col-12 col-md-2 text-end">
                <button type="submit" class="rbn-btn rbn-btn-primary btn-sm w-100 py-2 fw-bold d-flex align-items-center justify-content-center gap-1">
                    <i class="ri-filter-3-line"></i> Filtrele
                </button>
            </div>
        </form>
    </div>

    <!-- 2. CANLI BAĞLANTI & HARİTA KÖPRÜSÜ 🌐 -->
    <div class="d-flex justify-content-between align-items-center mb-4 p-3 rounded-3 flex-wrap gap-2" style="background: rgba(22, 163, 74, 0.08); border: 1px solid rgba(22, 163, 74, 0.2);">
        <div class="d-flex align-items-center gap-2 text-success">
            <span class="ra-status-dot me-1 bg-success"></span>
            <span class="small fw-bold">Bağlantı Aktif: Google Analytics 4 canlı verileri senkronize.</span>
        </div>
        <div class="d-flex align-items-center gap-3">
            <div class="text-muted small">
                <i class="ri-time-line me-1"></i> Son Güncelleme: <strong><?= $reportData['cached_at'] ?? 'Az Önce' ?></strong>
            </div>
            <a href="{{ $Route->url('webtraffic/google-analytics/map', 'admin') }}" class="rbn-btn rbn-btn-primary btn-sm px-3 py-1 fw-bold">
                <i class="ri-map-2-line me-1"></i> Türkiye Haritası
            </a>
        </div>
    </div>

    <!-- 3. DÖNEMLİK STAT KARTLARI 📊 -->
    <div class="row g-4 mb-4">
        <!-- Aktif Kullanıcılar -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="ra-stat-card ra-stat-terracotta h-100">
                <div class="d-flex align-items-center mb-3">
                    <div class="ra-stat-icon me-3">
                        <i class="ri-team-line"></i>
                    </div>
                    <div>
                        <div class="ra-stat-label">TOPLAM KULLANICI</div>
                        <h3 class="ra-stat-value"><?= number_format($reportData['summary']['activeUsers'] ?? 0) ?></h3>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center pt-2 border-top border-secondary border-opacity-10 small">
                    <span class="text-muted">Tekil Ziyaretçi</span>
                    <strong class="text-dark">Son <?= $daysDiff ?> Gün</strong>
                </div>
            </div>
        </div>

        <!-- Sayfa Görüntüleme -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="ra-stat-card ra-stat-success h-100">
                <div class="d-flex align-items-center mb-3">
                    <div class="ra-stat-icon me-3">
                        <i class="ri-eye-line"></i>
                    </div>
                    <div>
                        <div class="ra-stat-label">SAYFA GÖRÜNTÜLEME</div>
                        <h3 class="ra-stat-value"><?= number_format($reportData['summary']['screenPageViews'] ?? 0) ?></h3>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center pt-2 border-top border-success border-opacity-10 small">
                    <span class="text-muted">Taranan Sayfa</span>
                    <strong class="text-success">Son <?= $daysDiff ?> Gün</strong>
                </div>
            </div>
        </div>

        <!-- Oturumlar -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="ra-stat-card ra-stat-navy h-100">
                <div class="d-flex align-items-center mb-3">
                    <div class="ra-stat-icon me-3">
                        <i class="ri-chat-3-line"></i>
                    </div>
                    <div>
                        <div class="ra-stat-label">OTURUM SAYISI</div>
                        <h3 class="ra-stat-value"><?= number_format($reportData['summary']['sessions'] ?? 0) ?></h3>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center pt-2 border-top border-primary border-opacity-10 small">
                    <span class="text-muted">Toplam Oturum</span>
                    <strong class="text-dark">Son <?= $daysDiff ?> Gün</strong>
                </div>
            </div>
        </div>

        <!-- Bounce Rate -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="ra-stat-card ra-stat-warning h-100">
                <div class="d-flex align-items-center mb-3">
                    <div class="ra-stat-icon me-3">
                        <i class="ri-door-open-line"></i>
                    </div>
                    <div>
                        <div class="ra-stat-label">HEMEN ÇIKMA ORANI</div>
                        <h3 class="ra-stat-value"><?= $reportData['summary']['bounceRate'] ?? 0.0 ?>%</h3>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center pt-2 border-top border-warning border-opacity-10 small">
                    <span class="text-muted">Tek Sayfada Çıkış</span>
                    <strong class="text-warning">Son <?= $daysDiff ?> Gün</strong>
                </div>
            </div>
        </div>
    </div>

    <!-- 4. TREND GRAFİĞİ & CİHAZ TABLOSU 📈 -->
    <div class="row g-4 mb-4">
        <!-- Trend Grafiği -->
        <div class="col-12 col-lg-8">
            <div class="rbn-card p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="ra-traffic-card-title mb-0">
                        <i class="ri-line-chart-line text-warning fs-5"></i> Ziyaretçi Trendi (Son 7 Gün)
                    </h6>
                    <span class="ra-stat-label text-muted">GA4 AKTİF KULLANICILAR</span>
                </div>
                <div style="height: 300px; width: 100%;">
                    <canvas id="ga4TrendChart" class="w-100 h-100"></canvas>
                </div>
            </div>
        </div>

        <!-- Cihaz Kategorileri -->
        <div class="col-12 col-lg-4">
            <div class="rbn-table-wrap h-100">
                <div class="ra-traffic-card-header">
                    <h6 class="ra-traffic-card-title mb-0">
                        <i class="ri-computer-line text-warning fs-5"></i> Cihaz Dağılımı
                    </h6>
                </div>
                <div class="table-responsive">
                    <table class="rbn-table rbn-table-hover align-middle mb-0" style="table-layout: fixed; width: 100%;">
                        <thead>
                            <tr>
                                <th class="ps-4">Cihaz</th>
                                <th class="text-end pe-4" style="width: 120px; white-space: nowrap;">Ziyaretçiler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($reportData['devices'])): ?>
                                <tr>
                                    <td colspan="2" class="text-center py-5 text-muted small">Cihaz verisi bulunamadı.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($reportData['devices'] as $device): ?>
                                    <tr>
                                        <td class="ps-4 fw-bold text-dark text-truncate">
                                            <i class="ri-<?= $device['deviceCategory'] === 'mobile' ? 'smartphone-line' : ($device['deviceCategory'] === 'desktop' ? 'computer-line' : 'tablet-line') ?> me-2 text-warning"></i>
                                            <?= ucfirst($device['deviceCategory'] ?? 'Bilinmiyor') ?>
                                        </td>
                                        <td class="text-end pe-4 font-monospace fw-bold text-dark" style="white-space: nowrap;">
                                            <span class="rbn-badge rbn-badge-secondary px-3 py-1"><?= number_format((int)($device['activeUsers'] ?? 0)) ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- 5. POPÜLER SAYFALAR & TRAFİK KAYNAKLARI 📋 -->
    <div class="row g-4 mb-4">
        <!-- En Popüler Sayfalar -->
        <div class="col-12 col-lg-5">
            <div class="rbn-table-wrap h-100">
                <div class="ra-traffic-card-header">
                    <h6 class="ra-traffic-card-title mb-0">
                        <i class="ri-file-text-line text-success fs-5"></i> En Çok Ziyaret Edilen Sayfalar
                    </h6>
                </div>
                <div class="table-responsive">
                    <table class="rbn-table rbn-table-hover rbn-table-fixed align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4">Sayfa URL</th>
                                <th class="text-end pe-4" style="width: 120px;">Görüntüleme</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($reportData['top_pages'])): ?>
                                <tr>
                                    <td colspan="2" class="text-center py-5 text-muted small">Veri bulunamadı.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($reportData['top_pages'] as $page): ?>
                                    <tr>
                                        <td class="ps-4 text-dark ra-traffic-url-cell">
                                            <a href="<?= htmlspecialchars($page['pagePath'] ?? '/') ?>" target="_blank" class="text-decoration-none text-dark d-block" title="<?= htmlspecialchars(($page['pagePath'] ?? '') . ' — ' . ($page['pageTitle'] ?? '')) ?>">
                                                <span class="font-monospace small fw-semibold ra-traffic-url-text" style="color: var(--rbn-craft-russet, var(--rbn-primary, #c56a3c));"><?= htmlspecialchars($page['pagePath'] ?? '') ?></span>
                                                <span class="d-block text-muted small mt-1 ra-traffic-url-text"><?= htmlspecialchars($page['pageTitle'] ?? '') ?></span>
                                            </a>
                                        </td>
                                        <td class="text-end pe-4 font-monospace fw-bold text-dark" style="white-space: nowrap;">
                                            <span class="rbn-badge rbn-badge-terracotta px-3 py-1"><?= number_format((int)($page['screenPageViews'] ?? 0)) ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Trafik Kaynakları -->
        <div class="col-12 col-lg-7">
            <div class="rbn-table-wrap h-100">
                <div class="ra-traffic-card-header">
                    <h6 class="ra-traffic-card-title mb-0">
                        <i class="ri-filter-3-line text-warning fs-5"></i> Trafik Kanalları ve Kaynakları
                    </h6>
                </div>
                <div class="table-responsive">
                    <table class="rbn-table rbn-table-hover rbn-table-fixed align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4">Kaynak / Aracı (Source / Medium)</th>
                                <th class="text-end pe-4" style="width: 140px;">Başlatılan Oturum</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($reportData['sources'])): ?>
                                <tr>
                                    <td colspan="2" class="text-center py-5 text-muted small">Veri bulunamadı.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($reportData['sources'] as $source): ?>
                                    <tr>
                                        <td class="ps-4 text-dark font-monospace fw-medium ra-traffic-url-cell">
                                            <span class="ra-traffic-url-text" title="<?= htmlspecialchars($source['sessionSourceMedium'] ?? 'Direct') ?>"><?= htmlspecialchars($source['sessionSourceMedium'] ?? 'Direct') ?></span>
                                        </td>
                                        <td class="text-end pe-4 font-monospace fw-bold text-dark" style="white-space: nowrap;">
                                            <span class="rbn-badge rbn-badge-secondary px-3 py-1"><?= number_format((int)($source['sessions'] ?? 0)) ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- 6. COĞRAFYA & TEKNOLOJİ 🌍 -->
    <div class="row g-4 mb-5">
        <!-- Coğrafi Dağılım -->
        <div class="col-12 col-lg-6">
            <div class="rbn-table-wrap h-100">
                <div class="ra-traffic-card-header">
                    <h6 class="ra-traffic-card-title mb-0">
                        <i class="ri-map-pin-line text-warning fs-5"></i> Coğrafi Konum Dağılımı (Ülke / Şehir)
                    </h6>
                </div>
                <div class="table-responsive">
                    <table class="rbn-table rbn-table-hover rbn-table-fixed align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4">Ülke / Şehir</th>
                                <th class="text-end pe-4" style="width: 140px; white-space: nowrap;">Aktif Ziyaretçiler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($reportData['geography'])): ?>
                                <tr>
                                    <td colspan="2" class="text-center py-5 text-muted small">Veri bulunamadı.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($reportData['geography'] as $geo): ?>
                                    <tr>
                                        <td class="ps-4 text-dark ra-traffic-url-cell">
                                            <span class="fw-bold d-block ra-traffic-url-text" title="<?= htmlspecialchars($geo['country'] ?? 'Bilinmiyor') ?>"><?= htmlspecialchars($geo['country'] ?? 'Bilinmiyor') ?></span>
                                            <span class="text-muted small ra-traffic-url-text" title="<?= htmlspecialchars($geo['city'] ?? 'Bilinmiyor') ?>"><?= htmlspecialchars($geo['city'] ?? 'Bilinmiyor') ?></span>
                                        </td>
                                        <td class="text-end pe-4 font-monospace fw-bold text-dark" style="white-space: nowrap;">
                                            <span class="rbn-badge rbn-badge-secondary px-3 py-1"><?= number_format((int)($geo['activeUsers'] ?? 0)) ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Teknoloji Dağılımı -->
        <div class="col-12 col-lg-6">
            <div class="rbn-table-wrap h-100">
                <div class="ra-traffic-card-header">
                    <h6 class="ra-traffic-card-title mb-0">
                        <i class="ri-cpu-line text-warning fs-5"></i> Teknoloji Dağılımı (Tarayıcı / OS)
                    </h6>
                </div>
                <div class="table-responsive">
                    <table class="rbn-table rbn-table-hover rbn-table-fixed align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4">Tarayıcı / İşletim Sistemi</th>
                                <th class="text-end pe-4" style="width: 140px; white-space: nowrap;">Aktif Ziyaretçiler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($reportData['technology'])): ?>
                                <tr>
                                    <td colspan="2" class="text-center py-5 text-muted small">Veri bulunamadı.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($reportData['technology'] as $tech): ?>
                                    <tr>
                                        <td class="ps-4 text-dark ra-traffic-url-cell">
                                            <span class="fw-bold d-block ra-traffic-url-text" title="<?= htmlspecialchars($tech['browser'] ?? 'Bilinmiyor') ?>"><?= htmlspecialchars($tech['browser'] ?? 'Bilinmiyor') ?></span>
                                            <span class="text-muted small ra-traffic-url-text" title="<?= htmlspecialchars($tech['operatingSystem'] ?? 'Bilinmiyor') ?>"><?= htmlspecialchars($tech['operatingSystem'] ?? 'Bilinmiyor') ?></span>
                                        </td>
                                        <td class="text-end pe-4 font-monospace fw-bold text-dark" style="white-space: nowrap;">
                                            <span class="rbn-badge rbn-badge-secondary px-3 py-1"><?= number_format((int)($tech['activeUsers'] ?? 0)) ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart Rendering Script -->
    <script>
    rbnReady(function() {
        var labels = <?= json_encode($trendLabels) ?>;
        var users = <?= json_encode($trendUsers) ?>;

        if (typeof RbnCharts !== 'undefined' && RbnCharts.initLineChart) {
            RbnCharts.initLineChart('ga4TrendChart', labels, users, {
                borderColor: '#c56a3c',
                gradientStart: 'rgba(197, 106, 60, 0.25)',
                gradientEnd: 'rgba(197, 106, 60, 0.01)',
                tension: 0.35
            });
        }
    });
    </script>
<?php endif; ?>
