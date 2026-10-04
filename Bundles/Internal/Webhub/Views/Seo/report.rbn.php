<?php
/** @var int $seoScore */
/** @var string $advice */
/** @var array $reportData */
/** @var array $pageScores */
/** @var array $display */
?>

<!-- 📊 1. ÜST AKSİYON BARI -->
<div class="d-flex justify-content-between align-items-center mb-4 px-1 flex-wrap gap-2">
    <div>
        <h6 class="fw-bold mb-0 text-dark" style="font-family: var(--rbn-font-body, inherit);">
            <i class="ri-robot-2-line text-primary me-1.5"></i>AI Destekli Detaylı SEO Raporu
        </h6>
        <span class="text-muted small">Yapay zeka analiz motorunun sistem geneli için çıkardığı teşhis ve metrikler.</span>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= $Route->url('webhub/seo/score', 'developer') ?>" class="rbn-btn rbn-btn-outline rbn-btn-sm rounded-pill">
            <i class="ri-arrow-left-line"></i> Skora Dön
        </a>
        <button type="button" class="rbn-btn rbn-btn-primary rbn-btn-sm rounded-pill action-confirm"
            data-url="<?= $Route->url('webhub/seo/scan', 'developer') ?>"
            data-title="Yeniden Tara"
            data-text="Sistem tekrar taranarak rapor güncellenecektir."
            data-redirect="true">
            <i class="ri-refresh-line"></i> Raporu Yenile
        </button>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- SOL: SİSTEM TAVSİYESİ VE TEKNİK METRİKLER -->
    <div class="col-12 col-lg-6">
        
        <!-- 1. Sistem Tavsiyesi Kartı -->
        <div class="rbn-card p-4 mb-4">
            <div class="d-flex align-items-center gap-3 mb-3">
                <div class="ra-stat-icon flex-shrink-0" style="background: rgba(217, 119, 6, 0.1); color: #d97706;">
                    <i class="ri-lightbulb-line fs-4"></i>
                </div>
                <div>
                    <h6 class="fw-bold text-dark mb-0" style="font-family: var(--rbn-font-body, inherit);">Sistem Tavsiyesi</h6>
                    <span class="text-muted small" style="font-size: 0.72rem;">Yapay Zeka Analiz Çıktısı</span>
                </div>
            </div>
            <div class="p-3 rounded-3" style="background: rgba(60, 50, 40, 0.03); border: 1px solid var(--rbn-border-subtle, #f0ebe1); line-height: 1.6; font-size: 0.875rem; color: #3c3228;">
                <?php if (empty($advice)): ?>
                    <span class="text-muted small italic">Sistem analizi için lütfen yeni bir tarama başlatın.</span>
                <?php else: ?>
                    <?= nl2br(htmlspecialchars($advice)) ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- 2. Teknik Metrikler Kartı -->
        <div class="rbn-card p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="ri-cpu-line text-primary fs-5"></i>
                    <h6 class="fw-bold text-dark mb-0" style="font-family: var(--rbn-font-body, inherit);">Teknik Kriterler</h6>
                </div>
                <span class="rbn-badge rbn-badge-terracotta rbn-badge-sm font-monospace">
                    Skor: <?= $seoScore ?> / 100
                </span>
            </div>

            <div class="d-flex flex-column gap-2">
                <?php if (empty($reportData)): ?>
                    <div class="text-center py-4 text-muted small">Henüz teknik tarama verisi bulunmuyor.</div>
                <?php else: ?>
                    <?php foreach ($reportData as $item): ?>
                        <div class="d-flex justify-content-between align-items-center p-2.5 rounded-3" style="background: rgba(60, 50, 40, 0.02); border: 1px solid var(--rbn-border-subtle, #f0ebe1);">
                            <div class="d-flex align-items-center gap-2 text-truncate pe-2">
                                <?php if (!empty($item['passed'])): ?>
                                    <i class="ri-checkbox-circle-line text-success fs-5 flex-shrink-0"></i>
                                <?php else: ?>
                                    <i class="ri-close-circle-line text-danger fs-5 flex-shrink-0"></i>
                                <?php endif; ?>
                                <span class="fw-medium text-dark small text-truncate"><?= htmlspecialchars($item['criteria'] ?? '') ?></span>
                            </div>
                            <span class="rbn-badge <?= !empty($item['passed']) ? 'rbn-badge-success' : 'rbn-badge-danger' ?> rbn-badge-xs font-monospace flex-shrink-0">
                                +<?= $item['earned'] ?? 0 ?> Puan
                            </span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <!-- SAĞ: SAYFA BAZLI ANALİZLER -->
    <div class="col-12 col-lg-6">
        <div class="rbn-card p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="ri-pages-line text-info fs-5"></i>
                    <h6 class="fw-bold text-dark mb-0" style="font-family: var(--rbn-font-body, inherit);">Sayfa Bazlı Sağlık Durumu</h6>
                </div>
            </div>

            <div class="d-flex flex-column gap-2">
                <?php if (empty($pageScores)): ?>
                    <div class="text-center py-4 text-muted small">Henüz sayfa bazlı veri taranmadı.</div>
                <?php else: ?>
                    <?php foreach ($pageScores as $page): ?>
                        <div class="p-3 rounded-3" style="background: rgba(60, 50, 40, 0.02); border: 1px solid var(--rbn-border-subtle, #f0ebe1);">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-bold text-dark small text-truncate pe-2"><?= htmlspecialchars($page['title'] ?? $page['url'] ?? '') ?></span>
                                <span class="rbn-badge <?= ($page['score'] ?? 0) >= 80 ? 'rbn-badge-success' : (($page['score'] ?? 0) >= 50 ? 'rbn-badge-warning' : 'rbn-badge-danger') ?> rbn-badge-xs font-monospace flex-shrink-0">
                                    <?= $page['score'] ?? 0 ?> Puan
                                </span>
                            </div>
                            <code class="text-primary font-monospace small bg-transparent p-0" style="font-size: 0.72rem;"><?= htmlspecialchars($page['url'] ?? '') ?></code>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
