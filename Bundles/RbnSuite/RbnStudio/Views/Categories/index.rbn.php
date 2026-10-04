<!-- RBN STUDIO CATEGORIES INDEX VIEW -->
<div class="d-flex justify-content-between align-items-center mb-4 pb-2">
    <div class="d-flex align-items-center gap-4">
        <?php if ($hasBlog && $hasNews): ?>
        <ul class="nav rbn-tab-pills gap-1">
            <li class="nav-item">
                <a class="nav-link <?= ($currentType ?? 'blog') == 'blog' ? 'active' : '' ?>" href="?type=blog">
                    <i class="ri-article-line me-1"></i> Blog Kategorileri
                    <span class="badge ms-1"><?= $blogCount ?? 0 ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= ($currentType ?? 'blog') == 'news' ? 'active' : '' ?>" href="?type=news">
                    <i class="ri-newspaper-line me-1"></i> Haber Kategorileri
                    <span class="badge ms-1"><?= $newsCount ?? 0 ?></span>
                </a>
            </li>
        </ul>
        <?php else: ?>
        <div class="d-flex align-items-center gap-3">
            <div class="ra-stat-icon" style="width: 44px; height: 44px; background: rgba(197, 106, 60, 0.14); color: #c56a3c; border-radius: 12px;">
                <i class="<?= $hasNews ? 'ri-newspaper-line' : 'ri-folder-shared-line' ?> fs-4"></i>
            </div>
            <div>
                <div class="d-flex align-items-center gap-2">
                    <h4 class="mb-0 fw-bold text-dark fs-5"><?= $hasNews ? 'Haber Kategorileri' : 'Blog Kategorileri' ?></h4>
                    <span class="badge bg-white text-secondary border px-2 py-0.5 rounded-pill font-monospace" style="font-size: 0.72rem;"><?= count($categories) ?> Kategori</span>
                </div>
                <div class="text-muted" style="font-size: 0.8125rem;">İçerik türü için tanımlı kategoriler ve taksonomik sıra</div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <div class="d-flex gap-2">
        <button type="button"
            class="rbn-btn rbn-btn-primary px-4 py-2 fw-bold d-flex align-items-center gap-1"
            data-rbn-modal="true" data-type="RbnStudio/Categories" data-title="Yeni Kategori Tanımla" data-theme="primary"
            data-endpoint="<?= $Route->url('studio/categories/modal', 'admin') ?>"
            data-size="lg">
            <i class="ri-add-line fs-5"></i> Yeni Kategori Ekle
        </button>
    </div>
</div>

<div class="rbn-table-wrap mb-5">
    <div class="ra-traffic-card-header">
        <div>
            <h6 class="ra-traffic-card-title mb-0">
                <i class="ri-folder-shared-line text-warning fs-5"></i> İçerik Kategorileri
            </h6>
            <span class="ra-stat-label text-muted">Blog ve Haber içeriklerinizi kategorize edin ve hiyerarşiyi düzenleyin</span>
        </div>
        <div class="rbn-badge rbn-badge-terracotta px-3 py-1 font-monospace fw-bold">
            CATEGORY ENGINE
        </div>
    </div>
    <div class="table-responsive">
        <table class="rbn-table align-middle mb-0" id="categoriesTable" data-rbn-sortable="true" data-sort-url="<?= $Route->url('studio/categories/reorder', 'admin') ?>">
            <thead>
                <tr>
                    <th style="width: 40px;"></th>
                    <th style="width: 60px;">Simge</th>
                    <th>Kategori Adı</th>
                    <th>Tür</th>
                    <th>Slug</th>
                    <th class="text-center">Durum</th>
                    <th class="text-end pe-4" style="width: 140px;">İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $category): ?>
                <tr class="transition-all" data-id="<?= $category['id'] ?>">
                    <td class="text-center">
                        <i class="ri-drag-move-2-line text-muted sortable-handle opacity-50 fs-5 cursor-pointer" data-tooltip="Sıralamak İçin Sürükleyin"></i>
                    </td>
                    <td class="text-center">
                        <?php
                            $iconClass = !empty($category['icon']) ? trim($category['icon']) : 'ri-folder-line';
                        ?>
                        <div class="ra-stat-icon mx-auto" style="width: 36px; height: 36px; background: rgba(197, 106, 60, 0.12); color: #c56a3c;">
                            <i class="<?= htmlspecialchars($iconClass) ?> fs-5"></i>
                        </div>
                    </td>
                    <td>
                        <div class="fw-bold text-dark fs-6 mb-0"><?= htmlspecialchars($category['name']) ?></div>
                        <?php if (!empty($category['description'])): ?>
                            <div class="text-muted small"><?= htmlspecialchars($category['description']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (($category['type'] ?? 'blog') === 'news'): ?>
                            <span class="rbn-badge rbn-badge-danger rbn-badge-xs">
                                <i class="ri-newspaper-line me-1"></i> Haber
                            </span>
                        <?php else: ?>
                            <span class="rbn-badge rbn-badge-terracotta rbn-badge-xs">
                                <i class="ri-article-line me-1"></i> Blog
                            </span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <code class="text-secondary fw-bold bg-transparent p-0 font-monospace"><?= htmlspecialchars($category['slug']) ?></code>
                    </td>
                    <td class="text-center">
                        <div class="rbn-form-check rbn-form-switch d-inline-block mb-0">
                            <input class="rbn-form-check-input rbn-status-toggle cursor-pointer" type="checkbox" role="switch"
                                   data-rbn-status-toggle="true"
                                   data-id="<?= $category['id'] ?>"
                                   data-url="<?= $Route->url('studio/categories/status', 'admin') ?>"
                                   <?= ($category['is_active'] ?? 1) ? 'checked' : '' ?>>
                        </div>
                    </td>
                    <td class="text-end pe-4">
                        <div class="d-inline-flex gap-1">
                            <button type="button" class="ra-table-btn"
                                data-rbn-modal="true"
                                data-type="RbnStudio/Categories"
                                data-id="<?= $category['id'] ?>"
                                data-title="Kategori Düzenle"
                                data-theme="primary"
                                data-endpoint="<?= $Route->url('studio/categories/modal', 'admin') ?>"
                                data-size="lg"
                                data-tooltip="Düzenle">
                                <i class="ri-edit-line text-warning"></i>
                            </button>
                            <button type="button" class="ra-table-btn action-confirm"
                                data-rbn-type="delete"
                                data-url="<?= $Route->url('studio/categories/delete/' . $category['id'], 'admin') ?>"
                                data-method="POST" data-ajax="true"
                                data-title="Kategoriyi Sil?"
                                data-text="Bu kategoriyi (<?= htmlspecialchars($category['name']) ?>) silmek istediğinize emin misiniz? Bu işlem geri alınamaz!"
                                data-tooltip="Sil">
                                <i class="ri-delete-bin-line text-danger"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>

                <?php if (empty($categories)): ?>
                <tr>
                    <td colspan="7" class="text-center py-5 text-muted">
                        <i class="ri-folder-forbid-line fs-1 d-block mb-2 text-warning opacity-50"></i>
                        <p class="mb-0 fw-bold text-dark">Bu türde henüz kayıtlı kategori bulunmuyor.</p>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <?php if (!empty($categories) && isset($pager)): ?>
    <div class="d-flex justify-content-between align-items-center p-3 border-top" style="background: var(--rbn-admin-surface, #fbf9f5); border-color: var(--rbn-admin-border-subtle, #e5ded3) !important;">
        <div class="text-muted small font-monospace">
            Toplam <strong><?= $pager->total() ?></strong> kategori listeleniyor.
        </div>
        <div>
            <?= $pager->links() ?>
        </div>
    </div>
    <?php endif; ?>
</div>
