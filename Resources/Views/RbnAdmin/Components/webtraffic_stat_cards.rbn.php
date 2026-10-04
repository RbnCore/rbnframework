<!-- 🌐 Web Traffic Summary Stat Cards Component (Terracotta Craft Metrikleri) 📊 -->
<div class="row g-4 mb-4" id="webtraffic-table-stats">
    <!-- Card 1: Anlık Aktif Ziyaretçi -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="ra-stat-card ra-stat-warning h-100">
            <div class="d-flex align-items-center mb-3">
                <div class="ra-stat-icon me-3">
                    <i class="ri-user-voice-line"></i>
                </div>
                <div>
                    <div class="ra-stat-label">ANLIK AKTİF</div>
                    <h3 class="ra-stat-value"><?= (int) ($stats['active_now'] ?? 0) ?></h3>
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center pt-2 border-top border-warning border-opacity-10 small">
                <span class="d-flex align-items-center text-warning fw-semibold">
                    <span class="ra-status-dot me-2 bg-warning"></span> Canlı Trafik
                </span>
                <span class="text-muted fw-bold">Lokal IP</span>
            </div>
        </div>
    </div>

    <!-- Card 2: Bugünkü Trafik -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="ra-stat-card ra-stat-navy h-100">
            <div class="d-flex align-items-center mb-3">
                <div class="ra-stat-icon me-3">
                    <i class="ri-flashlight-line"></i>
                </div>
                <div>
                    <div class="ra-stat-label">BUGÜNKÜ HİT</div>
                    <h3 class="ra-stat-value"><?= number_format((int) ($stats['today_hits'] ?? 0)) ?></h3>
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center pt-2 border-top border-primary border-opacity-10 small">
                <span class="text-muted"><i class="ri-user-3-line me-1 text-success"></i>Organik: <strong><?= number_format((int) ($stats['bot_stats']['organic'] ?? 0)) ?></strong></span>
                <span class="text-muted"><i class="ri-robot-line me-1 text-purple"></i>Bot: <strong><?= number_format((int) ($stats['bot_stats']['bot'] ?? 0)) ?></strong></span>
            </div>
        </div>
    </div>

    <!-- Card 3: Toplam Sistem Trafiği -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="ra-stat-card ra-stat-success h-100">
            <div class="d-flex align-items-center mb-3">
                <div class="ra-stat-icon me-3">
                    <i class="ri-earth-line"></i>
                </div>
                <div>
                    <div class="ra-stat-label">TOPLAM TRAFİK</div>
                    <h3 class="ra-stat-value"><?= number_format((int) ($stats['total_hits'] ?? 0)) ?></h3>
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center pt-2 border-top border-success border-opacity-10 small">
                <span class="text-muted"><i class="ri-user-3-line me-1 text-success"></i>Organik: <strong><?= number_format((int) ($stats['total_bot_stats']['organic'] ?? 0)) ?></strong></span>
                <span class="text-muted"><i class="ri-robot-line me-1 text-purple"></i>Bot: <strong><?= number_format((int) ($stats['total_bot_stats']['bot'] ?? 0)) ?></strong></span>
            </div>
        </div>
    </div>

    <!-- Card 4: Popüler Lokasyonlar -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="ra-stat-card ra-stat-terracotta h-100">
            <div class="d-flex align-items-center mb-3">
                <div class="ra-stat-icon me-3">
                    <i class="ri-map-pin-2-line"></i>
                </div>
                <div class="flex-grow-1 min-w-0">
                    <div class="ra-stat-label">POPÜLER LOKASYON</div>
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="ra-stat-value fs-4 text-truncate" title="<?= htmlspecialchars($stats['top_location_today_name'] ?? 'Bilinmiyor') ?>">
                            <?= htmlspecialchars($stats['top_location_today_name'] ?? 'Bilinmiyor') ?>
                        </span>
                        <?php if (!empty($stats['top_location_today_flag'])): ?>
                            <span class="<?= htmlspecialchars($stats['top_location_today_flag']) ?> shadow-sm rounded-1 fs-5 ms-1"></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center pt-2 border-top border-secondary border-opacity-10 small">
                <span class="text-muted">Bugün: <strong><?= htmlspecialchars($stats['top_location_today_name'] ?? 'Bilinmiyor') ?></strong></span>
                <span class="text-muted">Genel: <strong><?= htmlspecialchars($stats['top_location_name'] ?? 'Bilinmiyor') ?></strong></span>
            </div>
        </div>
    </div>
</div>