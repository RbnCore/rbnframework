<?php
/**
 * RbnAdmin Sovereign Component - AI Telemetry Summary Stat Cards 📊🤖⚡
 * 
 * Shared component for AI Telemetry dashboard and main RbnAdmin dashboard.
 * Rendered with $summary, $totalCostTry, $textCostTry, $imageCostTry, $containerId
 */
$summary = $summary ?? ($aiReport['summary'] ?? []);
$totalCostTry = $totalCostTry ?? ($aiReport['totalCostTry'] ?? 0);
$textCostTry = $textCostTry ?? ($aiReport['textCostTry'] ?? 0);
$imageCostTry = $imageCostTry ?? ($aiReport['imageCostTry'] ?? 0);
$containerId = $containerId ?? 'ai-usage-table-stats';
?>
<!-- 📊 Top Summary Stat Cards (4 Cards: Tokens, Requests, Costs USD, Costs TRY) -->
<div class="row g-4 mb-4" id="<?= htmlspecialchars($containerId) ?>">
    <!-- Card 1: Token İstatistikleri -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="ra-stat-card ra-stat-secondary h-100">
            <div class="d-flex align-items-center mb-3">
                <div class="ra-stat-icon me-3">
                    <i class="ri-cpu-line"></i>
                </div>
                <div>
                    <div class="ra-stat-label">HARCANAN TOKEN</div>
                    <h3 class="ra-stat-value"><?= number_format((int) ($summary['total_tokens'] ?? 0)) ?></h3>
                </div>
            </div>
            <div
                class="d-flex justify-content-between align-items-center pt-2 border-top border-secondary border-opacity-10 small">
                <span class="text-muted"><i class="ri-arrow-up-circle-line me-1 text-primary"></i>Girdi:
                    <strong><?= number_format((int) ($summary['total_prompt_tokens'] ?? 0)) ?></strong></span>
                <span class="text-muted"><i class="ri-arrow-down-circle-line me-1 text-info"></i>Çıktı:
                    <strong><?= number_format((int) ($summary['total_output_tokens'] ?? 0)) ?></strong></span>
            </div>
        </div>
    </div>

    <!-- Card 2: AI İstek Sayıları -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="ra-stat-card ra-stat-warning h-100">
            <div class="d-flex align-items-center mb-3">
                <div class="ra-stat-icon me-3">
                    <i class="ri-send-plane-line"></i>
                </div>
                <div>
                    <div class="ra-stat-label">TOPLAM AI İSTEĞİ</div>
                    <h3 class="ra-stat-value"><?= number_format((int) ($summary['total_requests'] ?? 0)) ?></h3>
                </div>
            </div>
            <div
                class="d-flex justify-content-between align-items-center pt-2 border-top border-warning border-opacity-10 small">
                <span class="text-muted"><i class="ri-file-text-line me-1 text-info"></i>Metin:
                    <strong><?= number_format((int) ($summary['text_requests'] ?? 0)) ?></strong></span>
                <span class="text-muted"><i class="ri-image-line me-1 text-warning"></i>Görsel:
                    <strong><?= number_format((int) ($summary['image_requests'] ?? 0)) ?></strong></span>
            </div>
        </div>
    </div>

    <!-- Card 3: Maliyet İstatistikleri ($USD) -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="ra-stat-card ra-stat-success h-100">
            <div class="d-flex align-items-center mb-3">
                <div class="ra-stat-icon me-3">
                    <i class="ri-money-dollar-circle-line"></i>
                </div>
                <div>
                    <div class="ra-stat-label">MALİYET ($USD)</div>
                    <h3 class="ra-stat-value">$<?= number_format((float) ($summary['total_cost_usd'] ?? 0), 4) ?></h3>
                </div>
            </div>
            <div
                class="d-flex justify-content-between align-items-center pt-2 border-top border-success border-opacity-10 small">
                <span class="text-muted"><i class="ri-file-text-line me-1 text-info"></i>Metin:
                    <strong>$<?= number_format((float) ($summary['text_cost_usd'] ?? 0), 4) ?></strong></span>
                <span class="text-muted"><i class="ri-image-line me-1 text-warning"></i>Görsel:
                    <strong>$<?= number_format((float) ($summary['image_cost_usd'] ?? 0), 4) ?></strong></span>
            </div>
        </div>
    </div>

    <!-- Card 4: Maliyet İstatistikleri (₺TL) -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="ra-stat-card ra-stat-danger h-100">
            <div class="d-flex align-items-center mb-3">
                <div class="ra-stat-icon me-3">
                    <i class="ri-wallet-3-line"></i>
                </div>
                <div>
                    <div class="ra-stat-label">MALİYET (₺TL)</div>
                    <h3 class="ra-stat-value">₺<?= number_format((float) ($totalCostTry ?? 0), 2) ?></h3>
                </div>
            </div>
            <div
                class="d-flex justify-content-between align-items-center pt-2 border-top border-danger border-opacity-10 small">
                <span class="text-muted"><i class="ri-file-text-line me-1 text-info"></i>Metin:
                    <strong>₺<?= number_format((float) ($textCostTry ?? 0), 2) ?></strong></span>
                <span class="text-muted"><i class="ri-image-line me-1 text-warning"></i>Görsel:
                    <strong>₺<?= number_format((float) ($imageCostTry ?? 0), 2) ?></strong></span>
            </div>
        </div>
    </div>
</div>