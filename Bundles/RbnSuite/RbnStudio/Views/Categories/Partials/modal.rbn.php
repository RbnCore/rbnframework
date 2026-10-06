<?php
/** @var array|null $category */
/** @var int|null $id */
/** @var bool $isEdit */
/** @var bool $hasBlog */
/** @var bool $hasNews */
/** @var string $currentType */

$action = $Route->url('studio/categories/save', 'admin');
$cat = $category ?? [];
$selectedType = $cat['type'] ?? ($currentType ?? 'blog');
?>
<form action="<?= $action ?>" method="POST" id="categoryForm" class="ajax-form" data-rbn-form="true" autocomplete="off">
    @csrf
    <?php if ($isEdit): ?>
        <input type="hidden" name="id" value="<?= $id ?>">
    <?php endif; ?>

    <div class="modal-body p-4">
        <div class="row g-4">
            <!-- Kategori Adı -->
            <div class="col-12">
                <label class="ra-stat-label mb-2 d-block">
                    <i class="ri-folder-line me-1 text-warning"></i> KATEGORİ ADI <span class="text-danger">*</span>
                </label>
                <input type="text" name="name" class="form-control"
                    placeholder="Örn: Teknoloji & Donanım" value="<?= htmlspecialchars($cat['name'] ?? '') ?>" required>
            </div>

            <!-- Kategori Türü (Dropdown Standard) -->
            <?php if (($hasBlog ?? false) && ($hasNews ?? false)): ?>
            <div class="col-12">
                <label class="ra-stat-label mb-2 d-block">
                    <i class="ri-price-tag-3-line me-1 text-warning"></i> KATEGORİ TÜRÜ <span class="text-danger">*</span>
                </label>
                <div class="rbn-dropdown dropdown rbn-modal-dropdown w-100">
                    <input type="hidden" name="type" id="modal_cat_type" value="<?= htmlspecialchars($selectedType) ?>" required>
                    <button class="rbn-form-input form-control w-100 d-flex justify-content-between align-items-center dropdown-toggle text-start" 
                        type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="selected-text font-weight-500">
                            <?= ($selectedType === 'news') ? '📰 Haber Kategorisi' : '📝 Blog Kategorisi' ?>
                        </span>
                        <i class="ri-arrow-down-s-line opacity-50"></i>
                    </button>
                    <ul class="rbn-dropdown-menu dropdown-menu shadow-lg py-2 w-100 mt-1">
                        <li>
                            <a class="rbn-dropdown-item dropdown-item py-2 d-flex align-items-center justify-content-between <?= ($selectedType === 'blog') ? 'active' : '' ?>"
                                href="javascript:void(0)"
                                data-value="blog"
                                data-label="📝 Blog Kategorisi">
                                <span>📝 Blog Kategorisi</span>
                                <?php if ($selectedType === 'blog'): ?>
                                    <i class="ri-check-line text-warning"></i>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li>
                            <a class="rbn-dropdown-item dropdown-item py-2 d-flex align-items-center justify-content-between <?= ($selectedType === 'news') ? 'active' : '' ?>"
                                href="javascript:void(0)"
                                data-value="news"
                                data-label="📰 Haber Kategorisi">
                                <span>📰 Haber Kategorisi</span>
                                <?php if ($selectedType === 'news'): ?>
                                    <i class="ri-check-line text-warning"></i>
                                <?php endif; ?>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            <?php else: ?>
                <input type="hidden" name="type" value="<?= htmlspecialchars($selectedType) ?>">
            <?php endif; ?>

            <!-- Simge (Icon) -->
            <div class="col-md-6">
                <label class="ra-stat-label mb-2 d-block">
                    <i class="ri-star-line me-1 text-warning"></i> SİMGE (REMIX ICON)
                </label>
                <input type="text" name="icon" class="form-control font-monospace"
                    placeholder="Örn: ri-folder-line, ri-cpu-line" value="<?= htmlspecialchars($cat['icon'] ?? 'ri-folder-line') ?>">
            </div>

            <!-- Durum Dropdown (RBN Dropdown Standard) -->
            <div class="col-md-6">
                <label class="ra-stat-label mb-2 d-block">
                    <i class="ri-checkbox-circle-line me-1 text-warning"></i> YAYIN DURUMU <span class="text-danger">*</span>
                </label>
                <div class="rbn-dropdown dropdown rbn-modal-dropdown w-100">
                    <input type="hidden" name="is_active" id="modal_cat_is_active" value="<?= (($cat['is_active'] ?? 1) == 1) ? '1' : '0' ?>" required>
                    <button class="rbn-form-input form-control w-100 d-flex justify-content-between align-items-center dropdown-toggle text-start" 
                        type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="selected-text font-weight-500">
                            <?= (($cat['is_active'] ?? 1) == 1) ? '🟢 Aktif (Yayında)' : '⚪ Pasif (Gizli)' ?>
                        </span>
                        <i class="ri-arrow-down-s-line opacity-50"></i>
                    </button>
                    <ul class="rbn-dropdown-menu dropdown-menu shadow-lg py-2 w-100 mt-1">
                        <li>
                            <a class="rbn-dropdown-item dropdown-item py-2 d-flex align-items-center justify-content-between <?= (($cat['is_active'] ?? 1) == 1) ? 'active' : '' ?>"
                                href="javascript:void(0)"
                                data-value="1"
                                data-label="🟢 Aktif (Yayında)">
                                <span>🟢 Aktif (Yayında)</span>
                                <?php if (($cat['is_active'] ?? 1) == 1): ?>
                                    <i class="ri-check-line text-warning"></i>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li>
                            <a class="rbn-dropdown-item dropdown-item py-2 d-flex align-items-center justify-content-between <?= (($cat['is_active'] ?? 1) == 0) ? 'active' : '' ?>"
                                href="javascript:void(0)"
                                data-value="0"
                                data-label="⚪ Pasif (Gizli)">
                                <span>⚪ Pasif (Gizli)</span>
                                <?php if (($cat['is_active'] ?? 1) == 0): ?>
                                    <i class="ri-check-line text-warning"></i>
                                <?php endif; ?>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Açıklama -->
            <div class="col-12">
                <label class="ra-stat-label mb-2 d-block">
                    <i class="ri-file-text-line me-1 text-warning"></i> AÇIKLAMA
                </label>
                <textarea name="description" class="form-control" rows="3"
                    placeholder="Kategori hakkında kısa açıklama..."><?= htmlspecialchars($cat['description'] ?? '') ?></textarea>
            </div>
        </div>
    </div>

    <div class="modal-footer p-3 d-flex justify-content-end align-items-center" style="background: var(--rbn-admin-surface, #fbf9f5); border-color: var(--rbn-admin-border-subtle, #e5ded3) !important;">
        <button type="submit" class="rbn-btn rbn-btn-primary px-4 py-2 fw-bold d-flex align-items-center gap-1">
            <i class="ri-check-line fs-5"></i> Kaydet
        </button>
    </div>
</form>
