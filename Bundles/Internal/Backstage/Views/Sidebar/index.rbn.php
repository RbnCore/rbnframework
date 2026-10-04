<?php
/** @var array $categories */
/** @var array $menus */
?>

<!-- 🎛️ 1. SIDEBAR YÖNETİMİ (KATEGORİLER & MENÜLER) -->
<div class="row g-4 mb-4">
    <!-- Sol Sütun: Kategoriler -->
    <div class="col-12 col-lg-5">
        <div class="rbn-card p-4 h-100">
            <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                <div>
                    <h6 class="fw-bold mb-0 text-dark" style="font-family: var(--rbn-font-body, inherit);">
                        <i class="ri-folder-5-line text-primary me-1"></i> Menü Kategorileri
                    </h6>
                    <span class="text-muted small">Sidebar ana başlık grupları</span>
                </div>
                <button type="button" class="rbn-btn rbn-btn-primary rbn-btn-xs rounded-pill"
                    data-rbn-modal="true" data-title="Yeni Kategori Ekle" data-size="md" data-type="backstage/sidebar"
                    data-view="modal_category">
                    <i class="ri-add-line"></i> Yeni Kategori
                </button>
            </div>

            <div class="d-flex flex-column gap-2" id="sortable-categories" data-rbn-sortable="true"
                data-sort-url="<?= $Route->url('backstage/sidebar/category/reorder', 'developer') ?>"
                data-sort-handle=".sortable-handle">
                <?php if (empty($categories)): ?>
                    <div class="text-center py-4 text-muted small">Kayıtlı kategori bulunamadı.</div>
                <?php else: ?>
                    <?php foreach ($categories as $cat): ?>
                        <div class="p-3 rounded-3 d-flex justify-content-between align-items-center"
                            style="background: rgba(60, 50, 40, 0.02); border: 1px solid var(--rbn-border-subtle, #f0ebe1);"
                            data-id="<?= $cat['id'] ?>">
                            <div class="d-flex align-items-center gap-3 min-w-0">
                                <i class="ri-draggable text-muted sortable-handle fs-5 flex-shrink-0" style="cursor: move;"></i>
                                <div class="ra-stat-icon flex-shrink-0" style="width: 38px; height: 38px; background: rgba(197, 106, 60, 0.1); color: var(--rbn-primary, #c56a3c); font-size: 1.15rem;">
                                    <i class="<?= $cat['category_icon'] ?: 'ri-folder-line' ?>"></i>
                                </div>
                                <div class="min-w-0 ps-1">
                                    <div class="fw-bold text-dark small text-truncate d-flex align-items-center gap-2 mb-0.5">
                                        <?= htmlspecialchars($cat['category_name']) ?>
                                        <?php if (!empty($cat['required_role'])): ?>
                                            <span class="rbn-badge rbn-badge-neutral rbn-badge-xs"><?= strtoupper(htmlspecialchars($cat['required_role'])) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <code class="text-muted font-monospace" style="font-size: 0.7rem;"><?= htmlspecialchars($cat['category_slug']) ?></code>
                                </div>
                            </div>

                            <div class="d-flex align-items-center gap-3 flex-shrink-0">
                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input ajax-status-toggle" type="checkbox"
                                        data-id="<?= $cat['id'] ?>"
                                        data-url="<?= $Route->url('backstage/sidebar/category/toggle', 'developer') ?>"
                                        <?= !empty($cat['is_active']) ? 'checked' : '' ?>>
                                </div>
                                <div class="d-inline-flex gap-2">
                                    <button type="button" class="rbn-btn rbn-btn-outline rbn-btn-xs"
                                        data-rbn-modal="true" data-type="backstage/sidebar" data-view="modal_category"
                                        data-id="<?= $cat['id'] ?>" data-tooltip="Düzenle">
                                        <i class="ri-edit-line"></i>
                                    </button>
                                    <button type="button" class="rbn-btn rbn-btn-outline rbn-btn-xs text-danger action-confirm"
                                        data-url="<?= $Route->url('backstage/sidebar/category/delete/' . $cat['id'], 'developer') ?>"
                                        data-method="POST" data-title="Kategori Silinsin mi?"
                                        data-text="<?= htmlspecialchars($cat['category_name']) ?> kategorisi silinecektir."
                                        data-tooltip="Sil">
                                        <i class="ri-delete-bin-line"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Sağ Sütun: Menü Öğeleri -->
    <div class="col-12 col-lg-7">
        <div class="rbn-card p-4 h-100">
            <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                <div>
                    <h6 class="fw-bold mb-0 text-dark" style="font-family: var(--rbn-font-body, inherit);">
                        <i class="ri-menu-2-line text-primary me-1.5"></i> Sidebar Menü Öğeleri
                    </h6>
                    <span class="text-muted small">Panel içi modül ve sayfa linkleri</span>
                </div>
                <button type="button" class="rbn-btn rbn-btn-primary rbn-btn-xs rounded-pill"
                    data-rbn-modal="true" data-title="Yeni Menü Öğesi Ekle" data-size="lg" data-type="backstage/sidebar"
                    data-view="modal_menu">
                    <i class="ri-add-line"></i> Yeni Menü Ekle
                </button>
            </div>

            <div class="d-flex flex-column gap-2" id="sortable-menus" data-rbn-sortable="true"
                data-sort-url="<?= $Route->url('backstage/sidebar/menu/reorder', 'developer') ?>"
                data-sort-handle=".sortable-handle">
                <?php if (empty($menus)): ?>
                    <div class="text-center py-4 text-muted small">Kayıtlı menü öğesi bulunamadı.</div>
                <?php else: ?>
                    <?php foreach ($menus as $menu): ?>
                        <div class="p-3 rounded-3 d-flex justify-content-between align-items-center"
                            style="background: rgba(60, 50, 40, 0.02); border: 1px solid var(--rbn-border-subtle, #f0ebe1);"
                            data-id="<?= $menu['id'] ?>">
                            <div class="d-flex align-items-center gap-3 min-w-0">
                                <i class="ri-draggable text-muted sortable-handle fs-5 flex-shrink-0" style="cursor: move;"></i>
                                <div class="ra-stat-icon flex-shrink-0" style="width: 38px; height: 38px; background: rgba(197, 106, 60, 0.1); color: var(--rbn-primary, #c56a3c); font-size: 1.15rem;">
                                    <i class="<?= $menu['menu_icon'] ?: 'ri-menu-line' ?>"></i>
                                </div>
                                <div class="min-w-0 ps-1">
                                    <div class="fw-bold text-dark small text-truncate d-flex align-items-center gap-2 mb-0.5">
                                        <?= htmlspecialchars($menu['menu_title']) ?>
                                        <?php if (!empty($menu['category_name'])): ?>
                                            <span class="rbn-badge rbn-badge-neutral rbn-badge-xs"><?= htmlspecialchars($menu['category_name']) ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($menu['required_role'])): ?>
                                            <span class="rbn-badge rbn-badge-danger rbn-badge-xs"><?= strtoupper(htmlspecialchars($menu['required_role'])) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <code class="text-muted font-monospace small bg-transparent p-0"><?= htmlspecialchars($menu['menu_route'] ?: $menu['menu_url'] ?: '---') ?></code>
                                </div>
                            </div>

                            <div class="d-flex align-items-center gap-3 flex-shrink-0">
                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input ajax-status-toggle" type="checkbox"
                                        data-id="<?= $menu['id'] ?>"
                                        data-url="<?= $Route->url('backstage/sidebar/menu/toggle', 'developer') ?>"
                                        <?= !empty($menu['is_active']) ? 'checked' : '' ?>>
                                </div>
                                <div class="d-inline-flex gap-2">
                                    <button type="button" class="rbn-btn rbn-btn-outline rbn-btn-xs"
                                        data-rbn-modal="true" data-type="backstage/sidebar" data-view="modal_menu"
                                        data-id="<?= $menu['id'] ?>" data-tooltip="Düzenle">
                                        <i class="ri-edit-line"></i>
                                    </button>
                                    <button type="button" class="rbn-btn rbn-btn-outline rbn-btn-xs text-danger action-confirm"
                                        data-url="<?= $Route->url('backstage/sidebar/menu/delete/' . $menu['id'], 'developer') ?>"
                                        data-method="POST" data-title="Menü Silinsin mi?"
                                        data-text="<?= htmlspecialchars($menu['menu_title']) ?> menüsü silinecektir."
                                        data-tooltip="Sil">
                                        <i class="ri-delete-bin-line"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>