<!-- 🤖 Bot Automation Dashboard View -->
<?php
$summary = $aiReport['summary'] ?? [];
$totalCostTry = $aiReport['totalCostTry'] ?? 0;
$textCostTry = $aiReport['textCostTry'] ?? 0;
$imageCostTry = $aiReport['imageCostTry'] ?? 0;
$containerId = 'botdash-ai-usage-stats';

$options = $options ?? [];
$cronJobs = $cronJobs ?? [];
$cronScheduleData = $cronScheduleData ?? [];
$totalTasks = $cronScheduleData['total_tasks'] ?? count($cronJobs);
$totalDailyRuns = $cronScheduleData['total_daily_runs'] ?? 0;
$totalApis = count($options);
?>

<!-- 📊 Section 1: AI Telemetry Stat Cards -->
@import('framework', 'Resources/Views/RbnAdmin/Components/ai_stat_cards.rbn.php')

<!-- 📊 Section 2: Bot Telemetry Stat Cards (Terracotta Craft Metrikleri) -->
<div class="row g-4 mb-4" id="botdash-automation-stats">
    <!-- Card 1: KAYITLI API ANAHTARI -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="ra-stat-card ra-stat-terracotta h-100">
            <div class="d-flex align-items-center mb-3">
                <div class="ra-stat-icon me-3">
                    <i class="ri-key-2-line"></i>
                </div>
                <div>
                    <div class="ra-stat-label">KAYITLI API ANAHTARI</div>
                    <h3 class="ra-stat-value"><?= number_format((int) $totalApis) ?></h3>
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center pt-2 border-top border-secondary border-opacity-10 small">
                <span class="text-muted"><i class="ri-robot-2-line me-1 text-warning"></i>Gemini & OpenRouter</span>
                <strong class="text-dark">Aktif Servisler</strong>
            </div>
        </div>
    </div>

    <!-- Card 2: OTONOM GÖREVLER -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="ra-stat-card ra-stat-navy h-100">
            <div class="d-flex align-items-center mb-3">
                <div class="ra-stat-icon me-3">
                    <i class="ri-cpu-line"></i>
                </div>
                <div>
                    <div class="ra-stat-label">OTONOM GÖREVLER</div>
                    <h3 class="ra-stat-value"><?= number_format((int) $totalTasks) ?></h3>
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center pt-2 border-top border-primary border-opacity-10 small">
                <span class="text-muted"><i class="ri-settings-4-line me-1 text-dark"></i>Zamanlanmış Robotlar</span>
                <strong class="text-dark">Master Hub</strong>
            </div>
        </div>
    </div>

    <!-- Card 3: GÜNLÜK TETİKLEME -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="ra-stat-card ra-stat-warning h-100">
            <div class="d-flex align-items-center mb-3">
                <div class="ra-stat-icon me-3">
                    <i class="ri-time-line"></i>
                </div>
                <div>
                    <div class="ra-stat-label">GÜNLÜK TETİKLEME</div>
                    <h3 class="ra-stat-value"><?= number_format((int) $totalDailyRuns) ?></h3>
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center pt-2 border-top border-warning border-opacity-10 small">
                <span class="text-muted"><i class="ri-alarm-line me-1 text-warning"></i>Zamanlanmış Yayın</span>
                <strong class="text-warning">Kez / Gün</strong>
            </div>
        </div>
    </div>

    <!-- Card 4: DAEMON DURUMU -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="ra-stat-card ra-stat-success h-100">
            <div class="d-flex align-items-center mb-3">
                <div class="ra-stat-icon me-3">
                    <i class="ri-shield-check-line"></i>
                </div>
                <div>
                    <div class="ra-stat-label">DAEMON DURUMU</div>
                    <h3 class="ra-stat-value text-success">Aktif</h3>
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center pt-2 border-top border-success border-opacity-10 small">
                <span class="text-muted"><span class="ra-status-dot me-1 bg-success"></span>Arka Plan Live</span>
                <strong class="text-success">RBN Engine</strong>
            </div>
        </div>
    </div>
</div>

<!-- 🎨 Bölüm Başlığı & İnce Çizgi -->
<div class="rbn-section-divider">
    <h6 class="ra-traffic-card-title mb-0 flex-shrink-0">
        <i class="ri-apps-fill text-warning fs-5"></i> Hızlı Erişim & Modül Yönlendirmeleri
    </h6>
    <div class="rbn-section-divider-line"></div>
</div>

<!-- 🚀 Section 3: Navigation Hub Cards -->
<div class="row g-4 mb-4" id="botdash-navigation-hub">
    <!-- Nav Card 1: API Anahtarları -->
    <div class="col-12 col-md-4">
        <a href="<?= $Route->url('admin.bot-settings.apis') ?>" class="text-decoration-none">
            <div class="rbn-card p-4 h-100 transition-all hover-translate-y">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="ra-stat-icon" style="background: rgba(197, 106, 60, 0.12); color: #c56a3c;">
                        <i class="ri-key-2-line"></i>
                    </div>
                    <i class="ri-arrow-right-line fs-5 text-muted"></i>
                </div>
                <h6 class="fw-bold text-dark mb-1">API Yapılandırması</h6>
                <p class="text-muted small mb-0">Gemini, OpenRouter ve servis API anahtarlarını yönetin.</p>
            </div>
        </a>
    </div>

    <!-- Nav Card 2: Otonom Bot Görevleri -->
    <div class="col-12 col-md-4">
        <a href="<?= $Route->url('admin.bot-settings.tasks') ?>" class="text-decoration-none">
            <div class="rbn-card p-4 h-100 transition-all hover-translate-y">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="ra-stat-icon" style="background: rgba(26, 54, 93, 0.12); color: #1a365d;">
                        <i class="ri-cpu-line"></i>
                    </div>
                    <i class="ri-arrow-right-line fs-5 text-muted"></i>
                </div>
                <h6 class="fw-bold text-dark mb-1">Otonom Bot Görevleri</h6>
                <p class="text-muted small mb-0">Projede tanımlı otonom servis ve bot görevlerini inceleyin.</p>
            </div>
        </a>
    </div>

    <!-- Nav Card 3: AI Kullanım Analizi -->
    <div class="col-12 col-md-4">
        <a href="<?= $Route->url('admin.bot-settings.ai-usage') ?>" class="text-decoration-none">
            <div class="rbn-card p-4 h-100 transition-all hover-translate-y">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="ra-stat-icon" style="background: rgba(22, 163, 74, 0.12); color: #16a34a;">
                        <i class="ri-pie-chart-line"></i>
                    </div>
                    <i class="ri-arrow-right-line fs-5 text-muted"></i>
                </div>
                <h6 class="fw-bold text-dark mb-1">AI Maliyet & Analiz</h6>
                <p class="text-muted small mb-0">Harcanan tokenlar ve detaylı maliyet raporlarını inceleyin.</p>
            </div>
        </a>
    </div>
</div>

<!-- 🎨 Zamanlayıcı Ayraç Bölümü -->
<div class="rbn-section-divider">
    <h6 class="ra-traffic-card-title mb-0 flex-shrink-0">
        <i class="ri-history-line text-warning fs-5"></i> Zamanlayıcı & Log Yönetimi
    </h6>
    <div class="rbn-section-divider-line"></div>
</div>

<!-- 🚀 2 Alt Yönlendirme Kartı -->
<div class="row g-4 mb-4" id="botdash-cron-hub">
    <!-- Alt Card 1: Zamanlanmış Görev Ayarları -->
    <div class="col-12 col-md-6">
        <a href="<?= $Route->url('admin.cron.index') ?>" class="text-decoration-none">
            <div class="rbn-card p-4 h-100 transition-all hover-translate-y">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="ra-stat-icon" style="background: rgba(217, 119, 6, 0.12); color: #d97706;">
                        <i class="ri-time-line"></i>
                    </div>
                    <i class="ri-arrow-right-line fs-5 text-muted"></i>
                </div>
                <h6 class="fw-bold text-dark mb-1">Zamanlanmış Görevler</h6>
                <p class="text-muted small mb-0">Botların çalışma günleri, saatleri ve frekanslarını düzenleyin.</p>
            </div>
        </a>
    </div>

    <!-- Alt Card 2: Cron Log Geçmişi -->
    <div class="col-12 col-md-6">
        <a href="<?= $Route->url('admin.cronlogs.index') ?>" class="text-decoration-none">
            <div class="rbn-card p-4 h-100 transition-all hover-translate-y">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="ra-stat-icon" style="background: rgba(168, 159, 145, 0.18); color: #78716c;">
                        <i class="ri-terminal-box-line"></i>
                    </div>
                    <i class="ri-arrow-right-line fs-5 text-muted"></i>
                </div>
                <h6 class="fw-bold text-dark mb-1">Cron Log Geçmişi</h6>
                <p class="text-muted small mb-0">Zamanlayıcıların çalışma kayıtlarını ve günlüklerini izleyin.</p>
            </div>
        </a>
    </div>
</div>