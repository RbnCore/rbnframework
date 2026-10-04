<?php
/** @var int $seoScore */
/** @var array $display */
/** @var string $lastScan */
?>

<!-- 🎯 1. SEO ANALİZ MOTORU KOKPİTİ -->
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="rbn-card p-5 text-center">
            
            <!-- Skor Çemberi & SVG Halkası (Merkezi) -->
            <div class="d-flex justify-content-center mb-4">
                <div class="position-relative d-inline-flex align-items-center justify-content-center" style="width: 140px; height: 140px;">
                    <svg viewBox="0 0 100 100" style="width: 100%; height: 100%; transform: rotate(-90deg);">
                        <circle cx="50" cy="50" r="42" stroke="rgba(60, 50, 40, 0.08)" stroke-width="8" fill="none"></circle>
                        <circle cx="50" cy="50" r="42" stroke="var(--rbn-primary, #c56a3c)" stroke-width="8" stroke-dasharray="264" stroke-dashoffset="<?= 264 - (264 * max(0, min(100, (int) $seoScore)) / 100) ?>" stroke-linecap="round" fill="none" style="transition: stroke-dashoffset 1s ease-in-out;"></circle>
                    </svg>
                    <div class="position-absolute top-50 start-50 translate-middle d-flex flex-column align-items-center justify-content-center">
                        <span class="fw-bold font-monospace" style="font-size: 2.25rem; line-height: 1; color: var(--rbn-admin-text-dark, #2b2623);"><?= $seoScore ?></span>
                        <span class="text-muted font-monospace" style="font-size: 0.65rem; letter-spacing: 0.5px;">PUAN</span>
                    </div>
                </div>
            </div>

            <!-- Başlık & Açıklama -->
            <div class="mb-4">
                <div class="d-inline-flex align-items-center gap-2 mb-2">
                    <span class="rbn-badge rbn-badge-terracotta rbn-badge-sm">Site İçi SEO Sağlığı</span>
                </div>
                <h4 class="fw-bold text-dark mb-2" style="font-family: var(--rbn-font-body, inherit);">Gerçek Zamanlı SEO Analiz Motoru</h4>
                <p class="text-muted small mb-0 mx-auto" style="max-width: 580px; line-height: 1.6;">
                    Sistemdeki sayfalar, meta veriler, başlık hiyerarşisi ve teknik parametreler derinlemesine taranarak hesaplanan <b>gerçek</b> SEO skorudur.
                </p>
            </div>

            <!-- Aksiyon Butonları -->
            <div class="d-flex flex-wrap align-items-center justify-content-center gap-3">
                <a href="<?= $Route->url('webhub/seo/report', 'developer') ?>"
                    class="rbn-btn rbn-btn-outline rounded-pill px-4 py-2.5">
                    <i class="ri-file-chart-line text-primary"></i> Son Raporu İncele
                </a>

                <button type="button" class="rbn-btn rbn-btn-primary rounded-pill px-5 py-2.5 action-confirm"
                    data-url="<?= $Route->url('webhub/seo/scan', 'developer') ?>"
                    data-title="SEO Taramasını Başlat"
                    data-text="Tüm sistem içerikleri baştan taranarak yeni bir Yapay Zeka SEO Raporu oluşturulacaktır."
                    data-redirect="false">
                    <i class="ri-radar-line"></i> Yeni Tarama Başlat
                </button>
            </div>

            <div class="mt-4 pt-3 border-top d-flex justify-content-center align-items-center gap-2 text-muted small" style="font-size: 0.75rem;">
                <i class="ri-history-line"></i> Son Tarama Tarihi: <b class="text-dark font-monospace"><?= htmlspecialchars($lastScan ?? 'Henüz yapılmadı') ?></b>
            </div>

        </div>
    </div>
</div>
