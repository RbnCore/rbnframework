<?php
/** @var array $menus */
?>

<!-- 🧭 1. ÜST AKSİYON BARI -->
<div class="d-flex justify-content-between align-items-center mb-3 px-1 flex-wrap gap-2">
    <div>
        <h6 class="fw-bold mb-0 text-dark" style="font-family: var(--rbn-font-body, inherit);">
            <i class="ri-menu-2-line text-warning me-1.5"></i>Menü Yapılandırması
        </h6>
        <span class="text-muted small">Frontend navigasyon ağacını sürükle-bırak yöntemiyle düzenleyin.</span>
    </div>
    <div class="d-flex gap-2 align-items-center">
        <span class="rbn-badge rbn-badge-neutral rbn-badge-sm font-monospace"><?= count($menus ?? []) ?> Öğe</span>
        <button type="button" class="rbn-btn rbn-btn-primary rbn-btn-sm rounded-pill"
            data-rbn-modal="true" data-title="Yeni Menü Öğesi Ekle" data-theme="primary" data-size="lg"
            data-type="webhub/navigation">
            <i class="ri-add-line"></i> Yeni Menü Ekle
        </button>
    </div>
</div>

<!-- 📑 2. LİSTE & SIRALAMA TABLOSU -->
<div class="rbn-table-container mb-4">
    <table class="rbn-table w-100" id="navigation-table" data-rbn-sortable="true"
        data-sort-url="<?= $Route->url('webhub/navigation/reorder', 'developer') ?>" data-sort-reload="false">
        <thead>
            <tr>
                <th style="width: 40px;"></th>
                <th>Menü Başlığı</th>
                <th>Link (URL)</th>
                <th>Hedef</th>
                <th class="text-center">Durum</th>
                <th class="pe-4 text-end" style="width: 120px;">İşlemler</th>
            </tr>
        </thead>
        <tbody id="sortable-menu">
            <?php if (empty($menus)): ?>
                <tr>
                    <td colspan="6" class="text-center py-5">
                        <i class="ri-menu-2-line fs-1 text-muted opacity-50 d-block mb-2"></i>
                        <span class="text-muted small">Henüz menü öğesi tanımlanmamış.</span>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($menus as $menu): ?>
                    <tr data-id="<?= $menu['id'] ?>">
                        <td class="ps-4">
                            <i class="ri-draggable fs-5 text-muted sortable-handle" style="cursor: move;"></i>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <?php if (!empty($menu['icon'])): ?>
                                    <div class="ra-stat-icon" style="width: 32px; height: 32px; font-size: 1rem; background: rgba(60, 50, 40, 0.05); color: var(--rbn-primary, #c56a3c);">
                                        <i class="<?= $menu['icon'] ?>"></i>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <span class="fw-bold text-dark d-block" style="font-size: 0.875rem;"><?= htmlspecialchars($menu['title']) ?></span>
                                    <?php if (!empty($menu['parent_id'])): ?>
                                        <span class="rbn-badge rbn-badge-neutral rbn-badge-xs">Alt Menü</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td>
                            <code class="text-primary font-monospace small bg-transparent p-0"><?= htmlspecialchars($menu['url'] ?? '') ?></code>
                        </td>
                        <td>
                            <span class="text-muted small"><?= ($menu['target'] ?? '_self') === '_blank' ? 'Yeni Sekme (_blank)' : 'Aynı Sayfa (_self)' ?></span>
                        </td>
                        <td class="text-center">
                            <div class="form-check form-switch d-flex justify-content-center">
                                <input class="form-check-input ajax-status-toggle" type="checkbox"
                                    <?= (int) ($menu['is_active'] ?? 0) === 1 ? 'checked' : '' ?>
                                    data-id="<?= $menu['id'] ?>"
                                    data-url="<?= $Route->url('webhub/navigation/toggle', 'developer') ?>">
                            </div>
                        </td>
                        <td class="pe-4 text-end">
                            <div class="d-flex justify-content-end gap-1.5">
                                <button type="button"
                                    class="rbn-btn rbn-btn-outline rbn-btn-xs"
                                    data-rbn-modal="true" data-title="Menü Düzenle" data-theme="primary" data-size="lg"
                                    data-type="webhub/navigation" data-id="<?= $menu['id'] ?>"
                                    data-tooltip="Düzenle">
                                    <i class="ri-edit-line"></i>
                                </button>
                                <button type="button"
                                    class="rbn-btn rbn-btn-outline rbn-btn-xs text-danger"
                                    data-rbn-confirm="true"
                                    data-url="<?= $Route->url('webhub/navigation/delete/' . $menu['id'], 'developer') ?>"
                                    data-method="POST"
                                    data-title="Menü Öğesi Silinsin mi?"
                                    data-message="<?= htmlspecialchars($menu['title']) ?> menü öğesi silinecektir."
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
