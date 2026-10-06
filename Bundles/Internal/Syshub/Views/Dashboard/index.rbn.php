<?php
/** @var array $stats */
/** @var bool $isMaintenance */
/** @var array $cacheStats */
/** @var array $logStats */
/** @var array $sessionStats */
/** @var string $totalClearableSize */
/** @var int $moduleCount */
/** @var string $frameworkVersion */
?>

<!-- 📊 1. RBN ADMIN STAT CARDS (2 Geniş Kart + 2 Kompakt Kart) -->
<div class="row g-4 mb-4" id="syshub-summary-stats">
    <!-- Card 1: PHP Runtime (Yatay Geniş - col-lg-4) -->
    <div class="col-12 col-md-6 col-lg-4">
        <div class="ra-stat-card ra-stat-navy h-100 d-flex flex-column justify-content-between">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div class="d-flex align-items-center">
                    <div class="ra-stat-icon me-3">
                        <i class="ri-server-line"></i>
                    </div>
                    <div>
                        <div class="ra-stat-label">PHP RUNTIME</div>
                        <h3 class="ra-stat-value">v<?= $stats['php_version'] ?></h3>
                    </div>
                </div>
                <a href="<?= $Route->url('syshub/fix-php-limits', 'developer') ?>"
                    data-rbn-confirm="true"
                    data-title="PHP Limitleri Optimize Edilsin mi?"
                    data-message="PHP limitleri (64M) otomatik olarak .user.ini ve .htaccess dosyalarına yazılacaktır. Devam etmek istiyor musunuz?"
                    data-type="warning"
                    class="rbn-btn rbn-btn-primary rbn-btn-xs"
                    data-tooltip="Limitleri Optimize Et">
                    <i class="ri-flashlight-line"></i> Optimize
                </a>
            </div>
            <div class="d-flex justify-content-between align-items-center pt-2 border-top border-primary border-opacity-10 small">
                <span class="text-muted"><i class="ri-cpu-line me-1 text-primary"></i>RAM: <strong><?= $stats['memory']['limit'] ?></strong></span>
                <span class="text-muted"><i class="ri-upload-2-line me-1 text-info"></i>Upload: <strong><?= $stats['upload_max'] ?></strong></span>
                <span class="text-muted"><i class="ri-file-upload-line me-1 text-secondary"></i>POST: <strong><?= $stats['post_max'] ?></strong></span>
            </div>
        </div>
    </div>

    <!-- Card 2: Sistem Durumu & Bakım (Yatay Geniş - col-lg-4) -->
    <div class="col-12 col-md-6 col-lg-4">
        <div class="ra-stat-card <?= $isMaintenance ? 'ra-stat-danger' : 'ra-stat-success' ?> h-100 d-flex flex-column justify-content-between">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div class="d-flex align-items-center">
                    <div class="ra-stat-icon me-3">
                        <i class="<?= $isMaintenance ? 'ri-lock-line' : 'ri-shield-check-line' ?>"></i>
                    </div>
                    <div>
                        <div class="ra-stat-label">SİSTEM DURUMU</div>
                        <h3 class="ra-stat-value"><?= $isMaintenance ? 'BAKIMDA' : 'ÇEVRİMİÇİ' ?></h3>
                    </div>
                </div>
                <a href="<?= $Route->url('syshub/maintenance', 'developer') ?>"
                    class="rbn-btn <?= $isMaintenance ? 'rbn-btn-danger' : 'rbn-btn-secondary' ?> rbn-btn-xs">
                    <i class="ri-settings-4-line"></i> Yönet
                </a>
            </div>
            <div class="d-flex justify-content-between align-items-center pt-2 border-top <?= $isMaintenance ? 'border-danger' : 'border-success' ?> border-opacity-10 small">
                <span class="d-flex align-items-center <?= $isMaintenance ? 'text-danger' : 'text-success' ?> fw-semibold">
                    <span class="ra-status-dot me-2 <?= $isMaintenance ? 'bg-danger' : 'bg-success' ?>"></span>
                    <?= $isMaintenance ? 'Korumalı' : 'Canlıda' ?>
                </span>
                <span class="text-muted fw-bold"><?= $security['allowed_ips_count'] ?? 0 ?> İzinli IP</span>
            </div>
        </div>
    </div>

    <!-- Card 3: Toplam Modül (Kompakt - col-lg-2) -->
    <div class="col-6 col-lg-2">
        <div class="ra-stat-card ra-stat-terracotta h-100 d-flex flex-column justify-content-between text-center">
            <div class="d-flex flex-column align-items-center justify-content-center">
                <div class="ra-stat-label">YÜKLÜ MODÜL</div>
                <h3 class="ra-stat-value"><?= $moduleCount ?></h3>
            </div>
            <div class="d-flex justify-content-center align-items-center pt-2 border-top border-secondary border-opacity-10 small">
                <span class="text-success fw-bold"><i class="ri-checkbox-circle-line me-1"></i>Aktif</span>
            </div>
        </div>
    </div>

    <!-- Card 4: Framework Sürümü (Kompakt - col-lg-2) -->
    <div class="col-6 col-lg-2">
        <div class="ra-stat-card ra-stat-warning h-100 d-flex flex-column justify-content-between text-center">
            <div class="d-flex flex-column align-items-center justify-content-center">
                <div class="ra-stat-label">FRAMEWORK</div>
                <h3 class="ra-stat-value">v<?= $frameworkVersion ?></h3>
            </div>
            <div class="d-flex justify-content-center align-items-center pt-2 border-top border-warning border-opacity-10 small">
                <span class="text-success fw-bold"><i class="ri-git-branch-line me-1"></i>Stabil</span>
            </div>
        </div>
    </div>
</div>

<!-- 🎛️ 2. ANA MODÜL KARTLARI (Standart Eşet Izgara) -->
<div class="row g-4">

    <!-- 1. VERİ TEMİZLİĞİ -->
    <div class="col-12 col-md-6 col-xl-3">
        <div class="rbn-card h-100 p-4 d-flex flex-column justify-content-between">
            <div>
                <!-- Header -->
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="ra-stat-icon flex-shrink-0" style="background: rgba(37, 99, 235, 0.1); color: #2563eb;">
                        <i class="ri-delete-bin-line fs-4"></i>
                    </div>
                    <div>
                        <div class="fw-bold fs-6 text-dark" style="font-family: var(--rbn-font-body, inherit);">Veri Temizliği</div>
                        <span class="text-muted small" style="font-size: 0.72rem;">Cache & Loglar</span>
                    </div>
                </div>

                <p class="text-muted small lh-base mb-3" style="font-size: 0.8125rem; min-height: 38px;">
                    Önbellek, sistem logları ve oturum verilerini otonom temizleyin.
                </p>

                <!-- Data Box -->
                <div class="d-flex flex-column gap-2 mb-4 p-3 rounded-3" style="background: rgba(60, 50, 40, 0.03); border: 1px solid var(--rbn-border-subtle, #f0ebe1);">
                    <div class="d-flex justify-content-between align-items-center font-monospace" style="font-size: 0.75rem;">
                        <span class="text-muted">Cache:</span>
                        <span class="fw-bold text-dark"><?= $cacheStats['total_files'] ?> Dosya (<?= $cacheStats['total_size'] ?? '0 B' ?>)</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center font-monospace" style="font-size: 0.75rem;">
                        <span class="text-muted">Loglar:</span>
                        <span class="fw-bold text-dark"><?= $logStats['total_files'] ?> Dosya (<?= $logStats['total_size'] ?? '0 B' ?>)</span>
                    </div>
                </div>
            </div>

            <!-- Action Button -->
            <a href="<?= $Route->url('syshub/datapurge', 'developer') ?>"
                class="rbn-btn rbn-btn-primary w-100 rbn-btn-sm rounded-pill justify-content-center">
                <i class="ri-brush-line"></i> Temizliği Başlat
            </a>
        </div>
    </div>

    <!-- 2. VERİTABANI KONSOLU -->
    <div class="col-12 col-md-6 col-xl-3">
        <div class="rbn-card h-100 p-4 d-flex flex-column justify-content-between">
            <div>
                <!-- Header -->
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="ra-stat-icon flex-shrink-0" style="background: rgba(22, 163, 74, 0.1); color: #16a34a;">
                        <i class="ri-terminal-box-line fs-4"></i>
                    </div>
                    <div>
                        <div class="fw-bold fs-6 text-dark" style="font-family: var(--rbn-font-body, inherit);">Veritabanı</div>
                        <span class="text-muted small" style="font-size: 0.72rem;">SQL & Tablolar</span>
                    </div>
                </div>

                <p class="text-muted small lh-base mb-3" style="font-size: 0.8125rem; min-height: 38px;">
                    SQL terminali, tablo gezgini ve veritabanı optimizasyon merkezi.
                </p>

                <!-- Data Box / Sub-actions -->
                <div class="d-flex gap-2 mb-4 p-2 rounded-3 align-items-center justify-content-between" style="background: rgba(60, 50, 40, 0.03); border: 1px solid var(--rbn-border-subtle, #f0ebe1); height: 68px;">
                    <a href="<?= $Route->url('syshub/db-console/optimize', 'developer') ?>"
                        data-rbn-confirm="true"
                        data-title="Tablolar Optimize Edilsin mi?"
                        data-message="Tüm veritabanı tabloları analiz edilip optimize edilecektir. Onaylıyor musunuz?"
                        data-type="success"
                        class="rbn-btn rbn-btn-outline rbn-btn-xs flex-grow-1 justify-content-center">
                        <i class="ri-magic-line text-success"></i> Optimize
                    </a>
                    <a href="<?= $Route->url('syshub/db-console/cleanInstall', 'developer') ?>"
                        data-rbn-confirm="true"
                        data-title="Önbellek Tabloları Sıfırlansın mı?"
                        data-message="DİKKAT! Sistem dışı oluşan geçici önbellek tabloları silinerek sıfırlanacaktır. Devam edilsin mi?"
                        data-type="danger"
                        class="rbn-btn rbn-btn-outline rbn-btn-xs flex-grow-1 justify-content-center text-danger">
                        <i class="ri-restart-line"></i> Sıfırla
                    </a>
                </div>
            </div>

            <!-- Action Button -->
            <a href="<?= $Route->url('syshub/dbconsole', 'developer') ?>"
                class="rbn-btn rbn-btn-outline w-100 rbn-btn-sm rounded-pill justify-content-center">
                <i class="ri-terminal-line"></i> Konsola Git
            </a>
        </div>
    </div>

    <!-- 3. GÜVENLİK MERKEZİ -->
    <div class="col-12 col-md-6 col-xl-3">
        <div class="rbn-card h-100 p-4 d-flex flex-column justify-content-between">
            <div>
                <!-- Header -->
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="ra-stat-icon flex-shrink-0" style="background: rgba(220, 38, 38, 0.1); color: #dc2626;">
                        <i class="ri-shield-check-line fs-4"></i>
                    </div>
                    <div>
                        <div class="fw-bold fs-6 text-dark" style="font-family: var(--rbn-font-body, inherit);">Güvenlik</div>
                        <span class="text-muted small" style="font-size: 0.72rem;">Firewall & Limits</span>
                    </div>
                </div>

                <p class="text-muted small lh-base mb-3" style="font-size: 0.8125rem; min-height: 38px;">
                    IP engelleme, güvenlik duvarı kuralları ve istek hız sınırı yönetimi.
                </p>

                <!-- Data Box -->
                <div class="d-flex flex-column gap-2 mb-4 p-3 rounded-3" style="background: rgba(60, 50, 40, 0.03); border: 1px solid var(--rbn-border-subtle, #f0ebe1);">
                    <div class="d-flex justify-content-between align-items-center font-monospace" style="font-size: 0.75rem;">
                        <span class="text-muted">Firewall:</span>
                        <span class="fw-bold text-success">Aktif Koruma</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center font-monospace" style="font-size: 0.75rem;">
                        <span class="text-muted">İzinli IP:</span>
                        <span class="fw-bold text-dark"><?= $security['allowed_ips_count'] ?? 0 ?> Adet</span>
                    </div>
                </div>
            </div>

            <!-- Action Button -->
            <a href="<?= $Route->url('syshub/security', 'developer') ?>"
                class="rbn-btn rbn-btn-outline w-100 rbn-btn-sm rounded-pill justify-content-center">
                <i class="ri-shield-keyhole-line"></i> Güvenlik Kalkanı
            </a>
        </div>
    </div>

    <!-- 4. BAKIM MODU (MAINTENANCE) -->
    <div class="col-12 col-md-6 col-xl-3">
        <div class="rbn-card h-100 p-4 d-flex flex-column justify-content-between">
            <div>
                <!-- Header -->
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="ra-stat-icon flex-shrink-0" style="background: <?= $isMaintenance ? 'rgba(220, 38, 38, 0.1)' : 'rgba(22, 163, 74, 0.1)' ?>; color: <?= $isMaintenance ? '#dc2626' : '#16a34a' ?>;">
                        <i class="<?= $isMaintenance ? 'ri-lock-2-line' : 'ri-lock-unlock-line' ?> fs-4"></i>
                    </div>
                    <div>
                        <div class="fw-bold fs-6 text-dark" style="font-family: var(--rbn-font-body, inherit);">Bakım Modu</div>
                        <span class="text-muted small" style="font-size: 0.72rem;">Erişim & Kalkan</span>
                    </div>
                </div>

                <p class="text-muted small lh-base mb-3" style="font-size: 0.8125rem; min-height: 38px;">
                    Sistem bakım modu yapılandırması, muaf IP tanımları ve ziyaretçi mesajı.
                </p>

                <!-- Data Box -->
                <div class="d-flex flex-column gap-2 mb-4 p-3 rounded-3" style="background: rgba(60, 50, 40, 0.03); border: 1px solid var(--rbn-border-subtle, #f0ebe1);">
                    <div class="d-flex justify-content-between align-items-center font-monospace" style="font-size: 0.75rem;">
                        <span class="text-muted">Durum:</span>
                        <span class="fw-bold <?= $isMaintenance ? 'text-danger' : 'text-success' ?>"><?= $isMaintenance ? 'Bakımda' : 'Yayında' ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center font-monospace" style="font-size: 0.75rem;">
                        <span class="text-muted">İzinli IP:</span>
                        <span class="fw-bold text-dark"><?= count($security['allowed_ips'] ?? []) ?> Adet</span>
                    </div>
                </div>
            </div>

            <!-- Action Button -->
            <a href="<?= $Route->url('syshub/maintenance', 'developer') ?>"
                class="rbn-btn rbn-btn-outline w-100 rbn-btn-sm rounded-pill justify-content-center">
                <i class="ri-tools-line"></i> Bakım Yönetimi
            </a>
        </div>
    </div>

</div>
