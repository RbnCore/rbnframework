<div class="p-3">
    <div class="rbn-table-wrap">
        <div class="ra-traffic-card-header">
            <h6 class="ra-traffic-card-title mb-0">
                <i class="ri-price-tag-3-line text-warning fs-5"></i> Resmi Google AI Model Tarifeleri
            </h6>
        </div>
        <div class="table-responsive">
            <table class="rbn-table rbn-table-hover align-middle mb-0" id="ai-pricing-table">
                <thead>
                    <tr>
                        <th class="ps-4" data-sort="string">Model Adı</th>
                        <th data-sort="number">Girdi (Prompt / 1M Token)</th>
                        <th data-sort="number">Çıktı (Candidates / 1M Token)</th>
                        <th class="pe-4 text-end" data-sort="number">Görsel Başı Maliyet</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pricingMap as $mName => $pData): ?>
                        <tr>
                            <td class="ps-4 font-monospace fw-bold text-dark"><?= htmlspecialchars($mName) ?></td>
                            <td class="font-monospace text-dark">
                                <?= isset($pData['input']) ? '$' . number_format($pData['input'], 4) . ' / 1M Token' : '-' ?>
                            </td>
                            <td class="font-monospace text-dark">
                                <?= isset($pData['output']) ? '$' . number_format($pData['output'], 4) . ' / 1M Token' : '-' ?>
                            </td>
                            <td class="pe-4 text-end font-monospace text-dark">
                                <?= isset($pData['per_image']) ? '$' . number_format($pData['per_image'], 4) . ' / Görsel' : '-' ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
