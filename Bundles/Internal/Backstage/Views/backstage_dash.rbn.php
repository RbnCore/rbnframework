<!-- 🎛️ BACKSTAGE MODÜL KARTLARI (3'lü Kusursuz Izgara) -->
<div class="row g-4 mb-4">

    <!-- 1. SIDEBAR YÖNETİMİ -->
    <div class="col-12 col-md-4">
        <div class="rbn-card h-100 p-4 d-flex flex-column justify-content-between">
            <div>
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="ra-stat-icon flex-shrink-0" style="background: rgba(37, 99, 235, 0.1); color: #2563eb;">
                        <i class="ri-layout-left-line fs-4"></i>
                    </div>
                    <div>
                        <div class="fw-bold fs-6 text-dark" style="font-family: var(--rbn-font-body, inherit);">Sidebar Yönetimi</div>
                        <span class="text-muted small" style="font-size: 0.72rem;">Menü & Hiyerarşi</span>
                    </div>
                </div>
                <p class="text-muted small lh-base mb-4" style="font-size: 0.8125rem; min-height: 38px;">
                    Admin paneli sidebar menülerini, kategorileri, sıralamayı ve yetki seviyelerini düzenleyin.
                </p>
            </div>
            <a href="<?= $Route->url('backstage/sidebar', 'developer') ?>"
                class="rbn-btn rbn-btn-outline w-100 rbn-btn-sm rounded-pill justify-content-center">
                <i class="ri-menu-unfold-line"></i> Menüleri Yönet
            </a>
        </div>
    </div>

    <!-- 2. E-POSTA AYARLARI -->
    <div class="col-12 col-md-4">
        <div class="rbn-card h-100 p-4 d-flex flex-column justify-content-between">
            <div>
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="ra-stat-icon flex-shrink-0" style="background: rgba(220, 38, 38, 0.1); color: #dc2626;">
                        <i class="ri-mail-settings-line fs-4"></i>
                    </div>
                    <div>
                        <div class="fw-bold fs-6 text-dark" style="font-family: var(--rbn-font-body, inherit);">E-Posta Ayarları</div>
                        <span class="text-muted small" style="font-size: 0.72rem;">SMTP & Bildirim</span>
                    </div>
                </div>
                <p class="text-muted small lh-base mb-4" style="font-size: 0.8125rem; min-height: 38px;">
                    SMTP sunucusu, port, şifreleme ve gönderici kimliği gibi kritik e-posta yapılandırmaları.
                </p>
            </div>
            <a href="<?= $Route->url('backstage/email', 'developer') ?>"
                class="rbn-btn rbn-btn-outline w-100 rbn-btn-sm rounded-pill justify-content-center">
                <i class="ri-mail-send-line"></i> SMTP Yapılandır
            </a>
        </div>
    </div>

    <!-- 3. AYAR MİMARİSİ -->
    <div class="col-12 col-md-4">
        <div class="rbn-card h-100 p-4 d-flex flex-column justify-content-between">
            <div>
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="ra-stat-icon flex-shrink-0" style="background: rgba(217, 119, 6, 0.1); color: #d97706;">
                        <i class="ri-settings-5-line fs-4"></i>
                    </div>
                    <div>
                        <div class="fw-bold fs-6 text-dark" style="font-family: var(--rbn-font-body, inherit);">Ayar Mimarisi</div>
                        <span class="text-muted small" style="font-size: 0.72rem;">Gruplar & Parametreler</span>
                    </div>
                </div>
                <p class="text-muted small lh-base mb-4" style="font-size: 0.8125rem; min-height: 38px;">
                    Sistem ayar anahtarları, veri tipleri, varsayılanlar ve geliştirici mimarisi kurgusu.
                </p>
            </div>
            <a href="<?= $Route->url('backstage/settings', 'developer') ?>"
                class="rbn-btn rbn-btn-outline w-100 rbn-btn-sm rounded-pill justify-content-center">
                <i class="ri-tools-line"></i> Mimariyi Yönet
            </a>
        </div>
    </div>

</div>
