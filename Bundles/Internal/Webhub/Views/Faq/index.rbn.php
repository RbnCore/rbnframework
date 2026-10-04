<?php
/** @var array $faqs */
?>

<!-- ❓ 1. ÜST AKSİYON BARI -->
<div class="d-flex justify-content-between align-items-center mb-3 px-1 flex-wrap gap-2">
    <div>
        <h6 class="fw-bold mb-0 text-dark" style="font-family: var(--rbn-font-body, inherit);">
            <i class="ri-questionnaire-line text-primary me-1.5"></i>Sıkça Sorulan Sorular (SSS)
        </h6>
        <span class="text-muted small">Soru, cevap ve otomatik SEO Schema (FAQPage) yapılandırması.</span>
    </div>
    <button type="button"
        class="rbn-btn rbn-btn-primary rbn-btn-sm rounded-pill"
        data-rbn-modal="true" data-title="Yeni Soru Ekle" data-theme="primary" data-size="lg"
        data-type="webhub/faq" data-view="modal">
        <i class="ri-add-line"></i> Yeni Soru Ekle
    </button>
</div>

<!-- 📑 2. SSS LİSTESİ TABLOSU -->
<div class="rbn-table-container mb-4">
    <table class="rbn-table w-100" id="faqTable" data-rbn-sortable="true"
        data-sort-url="<?= $Route->url('webhub/faq/reorder', 'developer') ?>">
        <thead>
            <tr>
                <th style="width: 40px;"></th>
                <th>Soru</th>
                <th>Cevap (Önizleme)</th>
                <th class="text-center">Durum</th>
                <th class="pe-4 text-end" style="width: 120px;">İşlemler</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($faqs)): ?>
                <tr>
                    <td colspan="5" class="text-center py-5">
                        <i class="ri-questionnaire-line fs-1 text-muted opacity-50 d-block mb-2"></i>
                        <span class="text-muted small">Henüz soru eklenmemiş.</span>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($faqs as $faq): ?>
                    <tr data-id="<?= $faq['id'] ?>">
                        <td class="ps-4">
                            <i class="ri-draggable fs-5 text-muted sortable-handle" style="cursor: move;"></i>
                        </td>
                        <td>
                            <div class="fw-bold text-dark" style="font-size: 0.875rem;"><?= htmlspecialchars($faq['question']) ?></div>
                            <small class="text-muted font-monospace" style="font-size: 0.7rem;">
                                Eklenme: <?= now('d.m.Y H:i', $faq['created_at'] ?? null) ?>
                            </small>
                        </td>
                        <td>
                            <div class="text-muted small text-truncate" style="max-width: 320px;">
                                <?= htmlspecialchars(strip_tags($faq['answer'] ?? '')) ?>
                            </div>
                        </td>
                        <td class="text-center">
                            <div class="form-check form-switch d-flex justify-content-center">
                                <input class="form-check-input ajax-status-toggle" type="checkbox"
                                    <?= (int) ($faq['is_active'] ?? 0) === 1 ? 'checked' : '' ?>
                                    data-id="<?= $faq['id'] ?>"
                                    data-url="<?= $Route->url('webhub/faq/status', 'developer') ?>">
                            </div>
                        </td>
                        <td class="pe-4 text-end">
                            <div class="d-flex justify-content-end gap-1.5">
                                <button type="button"
                                    class="rbn-btn rbn-btn-outline rbn-btn-xs"
                                    data-rbn-modal="true" data-title="Soru Düzenle" data-theme="primary" data-size="lg"
                                    data-type="webhub/faq" data-id="<?= $faq['id'] ?>"
                                    data-tooltip="Düzenle">
                                    <i class="ri-edit-line"></i>
                                </button>
                                <button type="button"
                                    class="rbn-btn rbn-btn-outline rbn-btn-xs text-danger"
                                    data-rbn-confirm="true"
                                    data-url="<?= $Route->url('webhub/faq/delete/' . $faq['id'], 'developer') ?>"
                                    data-method="POST"
                                    data-title="Soru Silinsin mi?"
                                    data-message="Bu soru kaydı silinecektir."
                                    data-type="danger"
                                    data-tooltip="Sil">
                                    <i class="ri-delete-bin-line"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
