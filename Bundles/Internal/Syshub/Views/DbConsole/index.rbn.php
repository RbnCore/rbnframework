<?php
/** @var array $dbInfo */
/** @var string $purgeAllUrl */
?>

<!-- 📊 1. DB STAT CARDS (4 Sovereign Kart) -->
<div class="row g-4 mb-4">
    <!-- Card 1: Veritabanı Adı -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="ra-stat-card ra-stat-navy h-100 d-flex flex-column justify-content-between">
            <div class="d-flex align-items-center mb-3">
                <div class="ra-stat-icon me-3">
                    <i class="ri-database-2-line"></i>
                </div>
                <div class="min-w-0">
                    <div class="ra-stat-label">VERİTABANI ADI</div>
                    <h3 class="ra-stat-value fs-5 text-truncate mb-0" title="<?= htmlspecialchars($dbInfo['name']) ?>">
                        <?= htmlspecialchars($dbInfo['name']) ?>
                    </h3>
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center pt-2 border-top border-primary border-opacity-10 small">
                <span class="text-muted">Motor:</span>
                <span class="text-dark fw-bold"><?= htmlspecialchars($dbInfo['type']) ?></span>
            </div>
        </div>
    </div>

    <!-- Card 2: Toplam Tablo -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="ra-stat-card ra-stat-terracotta h-100 d-flex flex-column justify-content-between">
            <div class="d-flex align-items-center mb-3">
                <div class="ra-stat-icon me-3">
                    <i class="ri-table-line"></i>
                </div>
                <div>
                    <div class="ra-stat-label">TABLO SAYISI</div>
                    <h3 class="ra-stat-value"><?= number_format((int) $dbInfo['tables']) ?></h3>
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center pt-2 border-top border-secondary border-opacity-10 small">
                <span class="text-muted">Kapsam:</span>
                <span class="text-dark fw-bold">Aktif Şema</span>
            </div>
        </div>
    </div>

    <!-- Card 3: SQL Terminal Erişimi -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="ra-stat-card ra-stat-success h-100 d-flex flex-column justify-content-between">
            <div class="d-flex align-items-center mb-3">
                <div class="ra-stat-icon me-3">
                    <i class="ri-terminal-box-line"></i>
                </div>
                <div>
                    <div class="ra-stat-label">SQL KONSOLU</div>
                    <h3 class="ra-stat-value fs-5 text-success mb-0">Terminal Aktif</h3>
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center pt-2 border-top border-success border-opacity-10 small">
                <span class="text-muted">Mod:</span>
                <a href="<?= $Route->url('syshub/dbconsole/console', 'developer') ?>" class="text-success fw-bold text-decoration-none">
                    Konsolu Aç <i class="ri-arrow-right-s-line"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- Card 4: Tablo Gezgini -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="ra-stat-card ra-stat-warning h-100 d-flex flex-column justify-content-between">
            <div class="d-flex align-items-center mb-3">
                <div class="ra-stat-icon me-3">
                    <i class="ri-search-eye-line"></i>
                </div>
                <div>
                    <div class="ra-stat-label">TABLO GEZGİNİ</div>
                    <h3 class="ra-stat-value fs-5 text-warning mb-0">Şema & Kayıtlar</h3>
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center pt-2 border-top border-warning border-opacity-10 small">
                <span class="text-muted">Gözat:</span>
                <a href="<?= $Route->url('syshub/dbconsole/tables', 'developer') ?>" class="text-warning fw-bold text-decoration-none">
                    Tabloları Gör <i class="ri-arrow-right-s-line"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- 🎛️ 2. VERİTABANI HIZLI YÖNETİM MERKEZİ (6 Eşit Sovereign Kart) -->
<div class="row g-4">

    <!-- 1. Clean Install -->
    <div class="col-12 col-md-6 col-xl-4">
        <div class="rbn-card h-100 p-4 d-flex flex-column justify-content-between">
            <div>
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="ra-stat-icon flex-shrink-0" style="background: rgba(220, 38, 38, 0.1); color: #dc2626;">
                        <i class="ri-delete-bin-line fs-4"></i>
                    </div>
                    <div>
                        <div class="fw-bold fs-6 text-dark" style="font-family: var(--rbn-font-body, inherit);">Clean Install</div>
                        <span class="rbn-badge rbn-badge-success rbn-badge-xs mt-0.5">Güvenli Sıfırlama</span>
                    </div>
                </div>
                <p class="text-muted small lh-base mb-4" style="min-height: 38px;">
                    Sadece ShieldConfig'de izin verilen sistem tablolarını (Loglar, Aktiviteler vb.) güvenle temizler.
                </p>
            </div>
            <button type="button"
                class="rbn-btn rbn-btn-danger w-100 rounded-pill rbn-btn-sm justify-content-center"
                data-rbn-confirm="true"
                data-url="<?= $Route->url('syshub/dbconsole/cleanInstall', 'developer') ?>"
                data-title="Temizlik Onayı"
                data-message="Sadece izin verilen sistem tabloları sıfırlanacaktır. Devam etmek istiyor musunuz?"
                data-type="danger">
                <i class="ri-restart-line"></i> Sıfırlamayı Çalıştır
            </button>
        </div>
    </div>

    <!-- 2. Seed Admin -->
    <div class="col-12 col-md-6 col-xl-4">
        <div class="rbn-card h-100 p-4 d-flex flex-column justify-content-between">
            <div>
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="ra-stat-icon flex-shrink-0" style="background: rgba(37, 99, 235, 0.1); color: #2563eb;">
                        <i class="ri-user-add-line fs-4"></i>
                    </div>
                    <div>
                        <div class="fw-bold fs-6 text-dark" style="font-family: var(--rbn-font-body, inherit);">Admin Kullanıcısı (Seed)</div>
                        <span class="rbn-badge rbn-badge-neutral rbn-badge-xs mt-0.5 font-monospace">admin@rbn.com</span>
                    </div>
                </div>
                <p class="text-muted small lh-base mb-4" style="min-height: 38px;">
                    Sistem için varsayılan yönetici (admin) kullanıcısını ve rol yetkilerini otomatik oluşturur.
                </p>
            </div>
            <button type="button"
                class="rbn-btn rbn-btn-primary w-100 rounded-pill rbn-btn-sm justify-content-center"
                data-rbn-confirm="true"
                data-url="<?= $Route->url('syshub/dbconsole/seedAdmin', 'developer') ?>"
                data-title="Yönetici Oluşturulsun mu?"
                data-message="Varsayılan admin kullanıcısı sisteme eklenecektir. Onaylıyor musunuz?"
                data-type="primary">
                <i class="ri-user-star-line"></i> Admin Kullanıcısı Yükle
            </button>
        </div>
    </div>

    <!-- 3. DB Optimize -->
    <div class="col-12 col-md-6 col-xl-4">
        <div class="rbn-card h-100 p-4 d-flex flex-column justify-content-between">
            <div>
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="ra-stat-icon flex-shrink-0" style="background: rgba(217, 119, 6, 0.1); color: #d97706;">
                        <i class="ri-flashlight-line fs-4"></i>
                    </div>
                    <div>
                        <div class="fw-bold fs-6 text-dark" style="font-family: var(--rbn-font-body, inherit);">Tablo Optimizasyonu</div>
                        <span class="rbn-badge rbn-badge-terracotta rbn-badge-xs mt-0.5">Performans Motoru</span>
                    </div>
                </div>
                <p class="text-muted small lh-base mb-4" style="min-height: 38px;">
                    Tüm tabloları optimize eder, overhead boşlukları temizler ve sorgu hızını artırır.
                </p>
            </div>
            <button type="button"
                class="rbn-btn rbn-btn-primary w-100 rounded-pill rbn-btn-sm justify-content-center"
                data-rbn-confirm="true"
                data-url="<?= $Route->url('syshub/dbconsole/optimize', 'developer') ?>"
                data-title="Tablolar Optimize Edilsin mi?"
                data-message="Veritabanı tabloları analiz edilip optimize edilecektir. Devam edilsin mi?"
                data-type="warning">
                <i class="ri-magic-line"></i> Tüm Tabloları Optimize Et
            </button>
        </div>
    </div>

    <!-- 4. SQL Console -->
    <div class="col-12 col-md-6 col-xl-4">
        <div class="rbn-card h-100 p-4 d-flex flex-column justify-content-between">
            <div>
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="ra-stat-icon flex-shrink-0" style="background: rgba(22, 163, 74, 0.1); color: #16a34a;">
                        <i class="ri-terminal-box-line fs-4"></i>
                    </div>
                    <div>
                        <div class="fw-bold fs-6 text-dark" style="font-family: var(--rbn-font-body, inherit);">SQL Konsol & Terminal</div>
                        <span class="rbn-badge rbn-badge-success rbn-badge-xs mt-0.5">Dinamik Sorgu</span>
                    </div>
                </div>
                <p class="text-muted small lh-base mb-4" style="min-height: 38px;">
                    Ham SQL sorguları çalıştırabileceğiniz ve sonuçları anlık görebileceğiniz terminal.
                </p>
            </div>
            <a href="<?= $Route->url('syshub/dbconsole/console', 'developer') ?>"
                class="rbn-btn rbn-btn-outline w-100 rounded-pill rbn-btn-sm justify-content-center">
                <i class="ri-terminal-line"></i> Terminali Aç
            </a>
        </div>
    </div>

    <!-- 5. Table Explorer -->
    <div class="col-12 col-md-6 col-xl-4">
        <div class="rbn-card h-100 p-4 d-flex flex-column justify-content-between">
            <div>
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="ra-stat-icon flex-shrink-0" style="background: rgba(14, 165, 233, 0.1); color: #0ea5e9;">
                        <i class="ri-table-2 fs-4"></i>
                    </div>
                    <div>
                        <div class="fw-bold fs-6 text-dark" style="font-family: var(--rbn-font-body, inherit);">Tablo Gezgini</div>
                        <span class="rbn-badge rbn-badge-neutral rbn-badge-xs mt-0.5">Şema İstatistikleri</span>
                    </div>
                </div>
                <p class="text-muted small lh-base mb-4" style="min-height: 38px;">
                    Veritabanındaki tüm tabloları boyut, satır sayısı ve collation bilgileriyle inceler.
                </p>
            </div>
            <a href="<?= $Route->url('syshub/dbconsole/tables', 'developer') ?>"
                class="rbn-btn rbn-btn-outline w-100 rounded-pill rbn-btn-sm justify-content-center">
                <i class="ri-eye-line"></i> Tabloları İncele
            </a>
        </div>
    </div>

    <!-- 6. Unicode Dönüşüm -->
    <div class="col-12 col-md-6 col-xl-4">
        <div class="rbn-card h-100 p-4 d-flex flex-column justify-content-between">
            <div>
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="ra-stat-icon flex-shrink-0" style="background: rgba(197, 106, 60, 0.1); color: var(--rbn-primary, #c56a3c);">
                        <i class="ri-translate-2 fs-4"></i>
                    </div>
                    <div>
                        <div class="fw-bold fs-6 text-dark" style="font-family: var(--rbn-font-body, inherit);">Unicode Collation</div>
                        <span class="rbn-badge rbn-badge-terracotta rbn-badge-xs mt-0.5 font-monospace">utf8mb4_unicode_ci</span>
                    </div>
                </div>
                <p class="text-muted small lh-base mb-4" style="min-height: 38px;">
                    Tüm tabloların karakter setini tek tıkla standart <strong>utf8mb4_unicode_ci</strong> formatına dönüştürür.
                </p>
            </div>
            <button type="button"
                class="rbn-btn rbn-btn-primary w-100 rounded-pill rbn-btn-sm justify-content-center"
                data-rbn-confirm="true"
                data-url="<?= $Route->url('syshub/dbconsole/convertAllCollations', 'developer') ?>"
                data-method="POST"
                data-title="Collation Dönüştürülsün mü?"
                data-message="Tüm tabloların collation'ı utf8mb4_unicode_ci olarak güncellenecektir. Onaylıyor musunuz?"
                data-type="warning">
                <i class="ri-refresh-line"></i> Tümünü Dönüştür
            </button>
        </div>
    </div>

</div>
