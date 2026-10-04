<!-- 🌐 1. WEBHUB KOKPİT HERO BANNER -->
<div class="rbn-card p-4 mb-4" style="background: linear-gradient(135deg, rgba(197, 106, 60, 0.08) 0%, rgba(30, 41, 59, 0.04) 100%); border-left: 4px solid var(--rbn-primary, #c56a3c);">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3.5">
            <div class="ra-stat-icon flex-shrink-0" style="width: 58px; height: 58px; background: rgba(197, 106, 60, 0.12); color: var(--rbn-primary, #c56a3c); font-size: 1.75rem;">
                <i class="ri-global-line"></i>
            </div>
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <h4 class="fw-bold text-dark mb-0" style="font-family: var(--rbn-font-body, inherit);">Web Hub & Frontend Kontrol</h4>
                    <span class="rbn-badge rbn-badge-terracotta rbn-badge-xs">Frontend Engine</span>
                </div>
                <p class="text-muted small mb-0 lh-base">
                    Sitenizin marka kimliğini, SEO yapısını, navigasyon menülerini, entegrasyonlarını ve yasal sayfalarını merkezi olarak yönetin.
                </p>
            </div>
        </div>

        <a href="<?= $Route->url('/', 'frontend') ?>" target="_blank"
            class="rbn-btn rbn-btn-outline rounded-pill rbn-btn-sm">
            <i class="ri-external-link-line"></i> Web Sitesini Aç
        </a>
    </div>
</div>

<!-- 🎛️ 2. WEBHUB MODÜL KARTLARI (Kusursuz Hizalı Izgara) -->
<div class="row g-4 mb-4">

    <!-- 1. KİMLİK & MARKA -->
    <div class="col-12 col-md-6 col-xl-3">
        <div class="rbn-card h-100 p-4 d-flex flex-column justify-content-between">
            <div>
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="ra-stat-icon flex-shrink-0" style="background: rgba(22, 163, 74, 0.1); color: #16a34a;">
                        <i class="ri-store-2-line fs-4"></i>
                    </div>
                    <div>
                        <div class="fw-bold fs-6 text-dark" style="font-family: var(--rbn-font-body, inherit);">Kimlik & Marka</div>
                        <span class="text-muted small" style="font-size: 0.72rem;">Firma Bilgileri</span>
                    </div>
                </div>
                <p class="text-muted small lh-base mb-4" style="font-size: 0.8125rem; min-height: 38px;">
                    Firma adı, slogan, logo, favicon ve iletişim bilgilerini yapılandırın.
                </p>
            </div>
            <a href="<?= $Route->url('webhub/identity', 'developer') ?>"
                class="rbn-btn rbn-btn-outline w-100 rbn-btn-sm rounded-pill justify-content-center">
                <i class="ri-edit-line"></i> Bilgileri Düzenle
            </a>
        </div>
    </div>

    <!-- 2. SEO AYARLARI -->
    <div class="col-12 col-md-6 col-xl-3">
        <div class="rbn-card h-100 p-4 d-flex flex-column justify-content-between">
            <div>
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="ra-stat-icon flex-shrink-0" style="background: rgba(37, 99, 235, 0.1); color: #2563eb;">
                        <i class="ri-search-eye-line fs-4"></i>
                    </div>
                    <div>
                        <div class="fw-bold fs-6 text-dark" style="font-family: var(--rbn-font-body, inherit);">SEO Ayarları</div>
                        <span class="text-muted small" style="font-size: 0.72rem;">Meta & Tarama</span>
                    </div>
                </div>
                <p class="text-muted small lh-base mb-4" style="font-size: 0.8125rem; min-height: 38px;">
                    Global meta etiketler, arama motoru optimizasyonu ve SEO analiz merkezi.
                </p>
            </div>
            <a href="<?= $Route->url('webhub/seo', 'developer') ?>"
                class="rbn-btn rbn-btn-outline w-100 rbn-btn-sm rounded-pill justify-content-center">
                <i class="ri-settings-4-line"></i> SEO Yönetimi
            </a>
        </div>
    </div>

    <!-- 3. NAVİGASYON -->
    <div class="col-12 col-md-6 col-xl-3">
        <div class="rbn-card h-100 p-4 d-flex flex-column justify-content-between">
            <div>
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="ra-stat-icon flex-shrink-0" style="background: rgba(217, 119, 6, 0.1); color: #d97706;">
                        <i class="ri-menu-2-line fs-4"></i>
                    </div>
                    <div>
                        <div class="fw-bold fs-6 text-dark" style="font-family: var(--rbn-font-body, inherit);">Navigasyon</div>
                        <span class="text-muted small" style="font-size: 0.72rem;">Menü & Linkler</span>
                    </div>
                </div>
                <p class="text-muted small lh-base mb-4" style="font-size: 0.8125rem; min-height: 38px;">
                    Frontend navbar ve footer menülerini sürükle-bırak ile dinamik olarak yapılandırın.
                </p>
            </div>
            <a href="<?= $Route->url('webhub/navigation', 'developer') ?>"
                class="rbn-btn rbn-btn-outline w-100 rbn-btn-sm rounded-pill justify-content-center">
                <i class="ri-list-check"></i> Menüleri Yönet
            </a>
        </div>
    </div>

    <!-- 4. YASAL SAYFALAR -->
    <div class="col-12 col-md-6 col-xl-3">
        <div class="rbn-card h-100 p-4 d-flex flex-column justify-content-between">
            <div>
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="ra-stat-icon flex-shrink-0" style="background: rgba(14, 165, 233, 0.1); color: #0ea5e9;">
                        <i class="ri-file-text-line fs-4"></i>
                    </div>
                    <div>
                        <div class="fw-bold fs-6 text-dark" style="font-family: var(--rbn-font-body, inherit);">Yasal Sayfalar</div>
                        <span class="text-muted small" style="font-size: 0.72rem;">KVKK & Sözleşmeler</span>
                    </div>
                </div>
                <p class="text-muted small lh-base mb-4" style="font-size: 0.8125rem; min-height: 38px;">
                    Gizlilik Sözleşmesi, Çerez Politikası ve dinamik kurumsal sayfaları yönetin.
                </p>
            </div>
            <a href="<?= $Route->url('webhub/policy', 'developer') ?>"
                class="rbn-btn rbn-btn-outline w-100 rbn-btn-sm rounded-pill justify-content-center">
                <i class="ri-file-edit-line"></i> Sayfaları Aç
            </a>
        </div>
    </div>

    <!-- 5. SSS YÖNETİMİ -->
    <div class="col-12 col-md-6 col-xl-3">
        <div class="rbn-card h-100 p-4 d-flex flex-column justify-content-between">
            <div>
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="ra-stat-icon flex-shrink-0" style="background: rgba(124, 58, 237, 0.1); color: #7c3aed;">
                        <i class="ri-questionnaire-line fs-4"></i>
                    </div>
                    <div>
                        <div class="fw-bold fs-6 text-dark" style="font-family: var(--rbn-font-body, inherit);">SSS Yönetimi</div>
                        <span class="text-muted small" style="font-size: 0.72rem;">Soru & Cevap</span>
                    </div>
                </div>
                <p class="text-muted small lh-base mb-4" style="font-size: 0.8125rem; min-height: 38px;">
                    Sıkça sorulan sorular, cevaplar ve SEO Schema yapılandırması.
                </p>
            </div>
            <a href="<?= $Route->url('webhub/faq', 'developer') ?>"
                class="rbn-btn rbn-btn-outline w-100 rbn-btn-sm rounded-pill justify-content-center">
                <i class="ri-question-answer-line"></i> SSS'leri Yönet
            </a>
        </div>
    </div>

    <!-- 6. ENTEGRASYONLAR -->
    <div class="col-12 col-md-6 col-xl-3">
        <div class="rbn-card h-100 p-4 d-flex flex-column justify-content-between">
            <div>
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="ra-stat-icon flex-shrink-0" style="background: rgba(30, 41, 59, 0.08); color: #1e293b;">
                        <i class="ri-plug-line fs-4"></i>
                    </div>
                    <div>
                        <div class="fw-bold fs-6 text-dark" style="font-family: var(--rbn-font-body, inherit);">Entegrasyonlar</div>
                        <span class="text-muted small" style="font-size: 0.72rem;">Scripts & Tags</span>
                    </div>
                </div>
                <p class="text-muted small lh-base mb-4" style="font-size: 0.8125rem; min-height: 38px;">
                    Analytics, Tag Manager ve Özel Script (Head, Body, Footer) kod yönetimi.
                </p>
            </div>
            <a href="<?= $Route->url('webhub/integrations', 'developer') ?>"
                class="rbn-btn rbn-btn-outline w-100 rbn-btn-sm rounded-pill justify-content-center">
                <i class="ri-code-s-slash-line"></i> Kodları Yönet
            </a>
        </div>
    </div>

    <!-- 7. REKLAM YÖNETİMİ -->
    <div class="col-12 col-md-6 col-xl-3">
        <div class="rbn-card h-100 p-4 d-flex flex-column justify-content-between">
            <div>
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="ra-stat-icon flex-shrink-0" style="background: rgba(220, 38, 38, 0.1); color: #dc2626;">
                        <i class="ri-advertisement-line fs-4"></i>
                    </div>
                    <div>
                        <div class="fw-bold fs-6 text-dark" style="font-family: var(--rbn-font-body, inherit);">Reklam Yönetimi</div>
                        <span class="text-muted small" style="font-size: 0.72rem;">Google AdSense</span>
                    </div>
                </div>
                <p class="text-muted small lh-base mb-4" style="font-size: 0.8125rem; min-height: 38px;">
                    Google AdSense entegrasyonu, reklam durumları ve şablon slot ayarları.
                </p>
            </div>
            <a href="<?= $Route->url('webhub/integrations/adsense', 'developer') ?>"
                class="rbn-btn rbn-btn-outline w-100 rbn-btn-sm rounded-pill justify-content-center">
                <i class="ri-money-dollar-circle-line"></i> Reklamları Yönet
            </a>
        </div>
    </div>

</div>