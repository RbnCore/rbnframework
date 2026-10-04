<?php
/** @var array $pages */
?>

<!-- 📄 1. ÜST AKSİYON BARI -->
<div class="d-flex justify-content-between align-items-center mb-3 px-1 flex-wrap gap-2">
    <div>
        <h6 class="fw-bold mb-0 text-dark" style="font-family: var(--rbn-font-body, inherit);">
            <i class="ri-file-text-line text-info me-1.5"></i>Dinamik & Yasal Sayfalar
        </h6>
        <span class="text-muted small">Gizlilik politikası, KVKK ve özel sayfaları yönetin.</span>
    </div>
    <button type="button"
        class="rbn-btn rbn-btn-primary rbn-btn-sm rounded-pill"
        data-rbn-modal="true" data-title="Yeni Yasal Sayfa Oluştur" data-theme="info" data-size="lg"
        data-type="webhub/policy" data-view="modal_policy">
        <i class="ri-add-line"></i> Yeni Sayfa
    </button>
</div>

<!-- 📑 2. SAYFALAR TABLOSU -->
<div class="rbn-table-container mb-4">
    <table class="rbn-table w-100" id="pagesTable" data-rbn-sortable="true"
        data-sort-url="<?= $Route->url('webhub/policy/reorder', 'developer') ?>">
        <thead>
            <tr>
                <th style="width: 40px;"></th>
                <th>Sayfa Başlığı</th>
                <th>URL Bağlantısı</th>
                <th>Şablon</th>
                <th class="text-center">Durum</th>
                <th class="pe-4 text-end" style="width: 120px;">İşlemler</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($pages)): ?>
                <tr>
                    <td colspan="6" class="text-center py-5">
                        <i class="ri-file-text-line fs-1 text-muted opacity-50 d-block mb-2"></i>
                        <span class="text-muted small">Kayıtlı yasal sayfa bulunamadı.</span>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($pages as $page): ?>
                    <tr data-id="<?= $page['id'] ?>">
                        <td class="ps-4">
                            <i class="ri-draggable fs-5 text-muted sortable-handle" style="cursor: move;"></i>
                        </td>
                        <td>
                            <div class="fw-bold text-dark" style="font-size: 0.875rem;"><?= htmlspecialchars($page['title']) ?></div>
                            <small class="text-muted font-monospace" style="font-size: 0.7rem;">
                                Güncelleme: <?= now('d.m.Y H:i', $page['updated_at'] ?? null) ?>
                            </small>
                        </td>
                        <td>
                            <a href="<?= $Route->url('sayfa/' . $page['slug'], 'frontend') ?>" target="_blank"
                                class="text-decoration-none">
                                <code class="text-primary font-monospace small bg-transparent p-0">/sayfa/<?= htmlspecialchars($page['slug']) ?> <i class="ri-external-link-line" style="font-size: 0.65rem;"></i></code>
                            </a>
                        </td>
                        <td>
                            <span class="rbn-badge rbn-badge-neutral rbn-badge-xs font-monospace"><?= htmlspecialchars($page['template'] ?? 'default') ?></span>
                        </td>
                        <td class="text-center">
                            <div class="form-check form-switch d-flex justify-content-center">
                                <input class="form-check-input ajax-status-toggle" type="checkbox"
                                    <?= (int) ($page['is_active'] ?? 0) === 1 ? 'checked' : '' ?>
                                    data-id="<?= $page['id'] ?>"
                                    data-url="<?= $Route->url('webhub/policy/status', 'developer') ?>">
                            </div>
                        </td>
                        <td class="pe-4 text-end">
                            <div class="d-flex justify-content-end gap-1.5">
                                <button type="button"
                                    class="rbn-btn rbn-btn-outline rbn-btn-xs"
                                    data-rbn-modal="true" data-title="Sayfa Düzenle" data-theme="info" data-size="lg"
                                    data-type="webhub/policy" data-id="<?= $page['id'] ?>"
                                    data-tooltip="Düzenle">
                                    <i class="ri-edit-line"></i>
                                </button>
                                <button type="button"
                                    class="rbn-btn rbn-btn-outline rbn-btn-xs text-danger"
                                    data-rbn-confirm="true"
                                    data-url="<?= $Route->url('webhub/policy/delete/' . $page['id'], 'developer') ?>"
                                    data-method="POST"
                                    data-title="Sayfa Silinsin mi?"
                                    data-message="<?= htmlspecialchars($page['title']) ?> sayfası silinecektir."
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
