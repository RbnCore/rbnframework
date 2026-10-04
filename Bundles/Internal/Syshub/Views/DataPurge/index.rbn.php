<?php
/** @var array $diskUsage */
/** @var array $rootStats */
/** @var string $purgeAllUrl */
/** @var array $subModules */
/** @var array $allStats */
?>

<!-- 📊 1. STORAGE STATS & NUKE ROW (4 Sovereign Kart) -->
<div class="row g-4 mb-4">
    <!-- Card 1: Disk Kullanımı -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="ra-stat-card ra-stat-navy h-100 d-flex flex-column justify-content-between">
            <div class="d-flex align-items-center mb-3">
                <div class="ra-stat-icon me-3">
                    <i class="ri-hard-drive-2-line"></i>
                </div>
                <div>
                    <div class="ra-stat-label">DİSK DOLULUK</div>
                    <h3 class="ra-stat-value"><?= $diskUsage['percentage'] ?><span class="fs-6 text-muted font-monospace">%</span></h3>
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center pt-2 border-top border-primary border-opacity-10 small">
                <span class="text-muted"><i class="ri-pie-chart-line me-1 text-primary"></i>Kullanılan:</span>
                <span class="text-dark fw-bold font-monospace"><?= $diskUsage['used'] ?? '–' ?></span>
            </div>
        </div>
    </div>

    <!-- Card 2: Toplam Dosya -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="ra-stat-card ra-stat-terracotta h-100 d-flex flex-column justify-content-between">
            <div class="d-flex align-items-center mb-3">
                <div class="ra-stat-icon me-3">
                    <i class="ri-folders-line"></i>
                </div>
                <div>
                    <div class="ra-stat-label">TOPLAM DOSYA</div>
                    <h3 class="ra-stat-value"><?= number_format($rootStats['total_files']) ?></h3>
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center pt-2 border-top border-secondary border-opacity-10 small">
                <span class="text-muted"><i class="ri-folder-info-line me-1 text-warning"></i>Kapsam:</span>
                <span class="text-dark fw-bold">Storage Klasörü</span>
            </div>
        </div>
    </div>

    <!-- Card 3: Toplam Boyut -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="ra-stat-card ra-stat-warning h-100 d-flex flex-column justify-content-between">
            <div class="d-flex align-items-center mb-3">
                <div class="ra-stat-icon me-3">
                    <i class="ri-database-2-line"></i>
                </div>
                <div>
                    <div class="ra-stat-label">TOPLAM BOYUT</div>
                    <h3 class="ra-stat-value fs-2"><?= $rootStats['total_size'] ?></h3>
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center pt-2 border-top border-warning border-opacity-10 small">
                <span class="text-muted"><i class="ri-temp-hot-line me-1 text-warning"></i>Geçici Veriler:</span>
                <span class="text-success fw-bold">Temizlenebilir</span>
            </div>
        </div>
    </div>

    <!-- Card 4: Nükleer Temizlik (Purge All) -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="ra-stat-card ra-stat-danger h-100 d-flex flex-column justify-content-between">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div class="d-flex align-items-center">
                    <div class="ra-stat-icon me-3">
                        <i class="ri-delete-bin-2-line"></i>
                    </div>
                    <div>
                        <div class="ra-stat-label">TOPLU TEMİZLİK</div>
                        <h3 class="ra-stat-value fs-5 text-danger mb-0">Nükleer Süpürge</h3>
                    </div>
                </div>
            </div>
            <div class="pt-2 border-top border-danger border-opacity-10">
                <button type="button"
                    class="rbn-btn rbn-btn-danger rbn-btn-xs w-100 justify-content-center"
                    data-rbn-confirm="true"
                    data-url="<?= $purgeAllUrl ?>"
                    data-method="POST"
                    data-title="Tüm Geçici Veriler Silinsin mi?"
                    data-message="Seçili projeye ait Cache, Loglar ve Oturumlar dahil tüm geçici veriler kalıcı olarak silinecektir. Devam edilsin mi?"
                    data-type="danger">
                    <i class="ri-fire-line"></i> Şimdi Temizle
                </button>
            </div>
        </div>
    </div>
</div>

<!-- 🎛️ 2. ALT MODÜL KARTLARI (Sovereign Standart Grid) -->
<div class="row g-4">
    <?php foreach ($subModules as $key => $sub):
        $stats = $allStats[$key] ?? ['total_files' => 0, 'total_size' => '0 B'];
        
        // RemixIcon dönüşümü
        $iconClass = str_replace('bi-', 'ri-', $sub['icon'] ?? '');
        if ($iconClass === 'ri-trash3' || $iconClass === 'ri-trash3-fill') $iconClass = 'ri-delete-bin-line';
        if ($iconClass === 'ri-files') $iconClass = 'ri-file-list-3-line';
        if ($iconClass === 'ri-database-fill') $iconClass = 'ri-database-2-line';
        if ($iconClass === 'ri-clock-history') $iconClass = 'ri-history-line';
        if ($iconClass === 'ri-shield-lock') $iconClass = 'ri-shield-keyhole-line';
        if ($iconClass === 'ri-speedometer') $iconClass = 'ri-dashboard-2-line';
        if (!str_starts_with($iconClass, 'ri-')) $iconClass = 'ri-folder-line';
    ?>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="rbn-card h-100 p-4 d-flex flex-column justify-content-between">
                <div>
                    <!-- Header -->
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="ra-stat-icon flex-shrink-0" style="background: rgba(197, 106, 60, 0.08); color: var(--rbn-primary, #c56a3c);">
                            <i class="<?= $iconClass ?> fs-4"></i>
                        </div>
                        <div>
                            <div class="fw-bold fs-6 text-dark" style="font-family: var(--rbn-font-body, inherit);"><?= $sub['title'] ?></div>
                            <span class="text-muted small" style="font-size: 0.72rem;">Sistem Deposu</span>
                        </div>
                    </div>

                    <!-- Data Box -->
                    <div class="d-flex flex-column gap-2 mb-4 p-3 rounded-3" style="background: rgba(60, 50, 40, 0.03); border: 1px solid var(--rbn-border-subtle, #f0ebe1);">
                        <div class="d-flex justify-content-between align-items-center font-monospace" style="font-size: 0.75rem;">
                            <span class="text-muted">Dosya Sayısı:</span>
                            <span class="fw-bold text-dark"><?= number_format($stats['total_files']) ?> Adet</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center font-monospace" style="font-size: 0.75rem;">
                            <span class="text-muted">Toplam Boyut:</span>
                            <span class="fw-bold text-dark"><?= $stats['formatted_size'] ?? '0 B' ?></span>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="d-flex gap-2 w-100">
                    <a href="<?= $sub['url'] ?>"
                        class="rbn-btn rbn-btn-outline rbn-btn-sm rounded-pill flex-grow-1 justify-content-center">
                        <i class="ri-eye-line"></i> İncele
                    </a>
                    <button type="button"
                        class="rbn-btn rbn-btn-outline rbn-btn-xs rounded-pill justify-content-center text-danger"
                        data-rbn-confirm="true"
                        data-url="<?= $Route->url('syshub/datapurge/clear/' . $key, 'developer') ?>"
                        data-method="POST"
                        data-title="<?= $sub['title'] ?> Temizlensin mi?"
                        data-message="Tüm <?= strtolower($sub['title']) ?> dosyaları kalıcı olarak silinecektir. Bu işlem geri alınamaz."
                        data-type="danger"
                        data-tooltip="Temizle">
                        <i class="ri-delete-bin-line"></i>
                    </button>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
