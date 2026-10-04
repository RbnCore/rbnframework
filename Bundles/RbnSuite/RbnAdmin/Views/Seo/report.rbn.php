<?php
// Skor rengini ve ikonunu belirleyelim (Referanstaki mantık)
$scoreColor = ($stats->score > 85 ? '#10b981' : ($stats->score > 65 ? '#f59e0b' : '#ef4444'));
$scoreIcon = ($stats->score > 85 ? 'ri-checkbox-circle-fill' : ($stats->score > 65 ? 'ri-alert-fill' : 'ri-shield-cross-fill'));
$scoreText = ($stats->score > 85 ? 'Kusursuz' : ($stats->score > 65 ? 'İyileştirilmeli' : 'Kritik Risk'));
?>
<!-- 🏛️ RBN Admin - SEO Intelligence Masterpiece (Reference Style) -->
<div class="row g-4 mb-5">
    <!-- 📄 SOL: SİSTEM ANALİZİ VE METRİKLER -->
    <div class="col-lg-6">

        <!-- 1. SİSTEM TAVSİYESİ -->
        <div class="rbn-card mb-4" style="background: #fdfdfd;">
            <div class="d-flex align-items-center mb-3">
                <div class="rbn-stat-icon me-3" style="background: <?= $scoreColor ?>15; width: 42px; height: 42px;">
                    <i class="ri-lightbulb-fill" style="color: <?= $scoreColor ?>; font-size: 1.2rem;"></i>
                </div>
                <h6 class="fw-bold mb-0 text-dark opacity-75" style="letter-spacing: -0.5px;">Sistem Tavsiyesi</h6>
            </div>
            <div class="lh-lg" style="font-size: 0.92rem; color: #475569;">
                "<?= $stats->advice ?>"
            </div>
        </div>

        <!-- 2. TEKNİK METRİKLER -->
        <div class="rbn-card p-0 overflow-hidden">
            <div class="p-4 border-bottom border-light d-flex justify-content-between align-items-center">
                <h6 class="fw-bolder mb-0 text-dark"><i class="ri-cpu-line me-2 text-primary"></i>Teknik Metrikler</h6>
                <div class="rbn-badge rbn-badge-secondary rbn-badge-pill px-3 py-2 fw-bold">
                    Skor: <span style="color: <?= $scoreColor ?>"><?= $stats->score ?></span>
                </div>
            </div>
            <div class="p-4">
                <div class="d-flex flex-column gap-2">
                    @foreach ($details as $item)
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom border-light">
                        <div class="d-flex align-items-center text-truncate pe-3">
                            @if ($item['passed'] ?? false)
                            <i class="ri-checkbox-circle-fill text-success me-2 fs-5"></i>
                            @else
                            <i class="ri-close-circle-fill text-danger me-2 fs-5"></i>
                            @endif
                            <div>
                                <h6 class="mb-0 fw-bold text-dark small"><?= $item['criteria'] ?></h6>
                            </div>
                        </div>
                        <div class="text-end">
                            <span class="rbn-badge <?= ($item['passed'] ?? false) ? 'rbn-badge-success' : 'rbn-badge-danger' ?> rbn-badge-pill px-2 py-1 small fw-bold">
                                +<?= $item['earned'] ?>
                            </span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- 🚀 SAĞ: GEMINI AI DANIŞMANI -->
    <div class="col-lg-6">
        <div class="rbn-card border-0 shadow-lg rounded-4 overflow-hidden h-100 position-relative p-0"
            style="background: #0f172a; min-height: 400px;">
            <!-- Glow Effect -->
            <div class="position-absolute top-0 end-0 opacity-10"
                style="width: 250px; height: 250px; background: radial-gradient(circle, #6366f1 0%, transparent 70%); transform: translate(20%, -20%); pointer-events: none;">
            </div>

            <div class="p-4 position-relative" style="z-index: 1;">
                <div class="d-flex align-items-center justify-content-between mb-4 pb-3 border-bottom border-white border-opacity-10">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle d-flex align-items-center justify-content-center p-2 me-3 shadow-sm"
                            style="background: linear-gradient(135deg, #4f46e5, #7c3aed); width: 42px; height: 42px;">
                            <i class="ri-sparkling-fill text-white fs-5"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold text-white mb-0" style="letter-spacing: -0.3px;">Gemini AI Analizi</h6>
                            <span class="text-white opacity-40" style="font-size: 0.75rem;">Otonom Stratejik Rapor</span>
                        </div>
                    </div>
                </div>

                <div class="ai-content text-white lh-base" style="font-size: 0.95rem; opacity: 0.9;">
                    @if (empty($stats->gemini))
                    <div class="ai-content text-white opacity-40 d-flex flex-column align-items-center justify-content-center pt-3 text-center">
                        <div class="mb-3 d-flex gap-1">
                            <span class="rbn-status-dot rbn-status-dot-blue rbn-status-pulse"></span>
                            <span class="rbn-status-dot rbn-status-dot-slate rbn-status-pulse"></span>
                            <span class="rbn-status-dot rbn-status-dot-cyan rbn-status-pulse"></span>
                        </div>
                        <p class="small fw-light lh-base">Analiz verisi henüz üretilmedi.<br>Lütfen yeni bir tarama başlatın.</p>
                    </div>
                    @else
                    <div class="gemini-response opacity-90">
                        <?= $stats->gemini ?>
                    </div>
                    @endif
                </div>
            </div>

            <div class="position-absolute bottom-0 start-0 w-100"
                style="height: 3px; background: linear-gradient(90deg, #4f46e5, #7c3aed, #db2777);"></div>
        </div>
    </div>
</div>