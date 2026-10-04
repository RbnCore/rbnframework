<?php
/** @var array $groups */
?>

<!-- 🎛️ 1. ÜST AKSİYON BARI -->
<div class="d-flex justify-content-between align-items-center mb-3 px-1 flex-wrap gap-2">
    <div>
        <h6 class="fw-bold mb-0 text-dark" style="font-family: var(--rbn-font-body, inherit);">
            <i class="ri-settings-5-line text-warning me-1.5"></i>Sistem Ayar Grupları
        </h6>
        <span class="text-muted small">Uygulama ayarlarını kategorize edilmiş teknik gruplar üzerinden yönetin.</span>
    </div>
    <div class="d-flex gap-2 align-items-center">
        <span class="rbn-badge rbn-badge-neutral rbn-badge-sm font-monospace"><?= count($groups ?? []) ?> Grup</span>
        <button type="button" class="rbn-btn rbn-btn-primary rbn-btn-sm rounded-pill"
            data-rbn-modal="true" data-title="Yeni Ayar Grubu Ekle" data-theme="primary" data-size="md"
            data-endpoint="<?= $Route->url('backstage/settings/modal', 'developer') ?>"
            data-type="backstage/settings" 
            data-view="modal_group">
            <i class="ri-add-line"></i> Yeni Grup Ekle
        </button>
    </div>
</div>

<!-- 📦 2. AYAR GRUPLARI KARTLARI (Izgara) -->
<div class="row g-4 mb-4">
    <?php if (empty($groups)): ?>
        <div class="col-12 text-center py-5">
            <i class="ri-settings-5-line fs-1 text-muted opacity-50 d-block mb-2"></i>
            <span class="text-muted small">Henüz ayar grubu tanımlanmamış.</span>
        </div>
    <?php else: ?>
        <?php foreach ($groups as $group): ?>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="rbn-card h-100 p-4 d-flex flex-column justify-content-between position-relative">
                    <!-- Üst İki Buton (Düzenle / Sil) -->
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="ra-stat-icon" style="width: 44px; height: 44px; background: rgba(197, 106, 60, 0.1); color: var(--rbn-primary, #c56a3c); font-size: 1.35rem;">
                            <i class="<?= $group['group_icon'] ?: 'ri-folder-settings-line' ?>"></i>
                        </div>
                        <div class="d-inline-flex gap-1.5">
                            <button type="button" class="ra-table-btn"
                                data-rbn-modal="true" data-title="Grubu Düzenle" data-theme="dark" data-size="md"
                                data-endpoint="<?= $Route->url('backstage/settings/modal/' . $group['id'], 'developer') ?>"
                                data-type="backstage/settings" data-view="modal_group" data-id="<?= $group['id'] ?>"
                                data-tooltip="Düzenle">
                                <i class="ri-edit-line"></i>
                            </button>
                            <button type="button" class="ra-table-btn ra-table-btn-danger action-confirm"
                                data-url="<?= $Route->url('backstage/settings/group/delete/' . $group['id'], 'developer') ?>"
                                data-method="POST" data-title="Grubu Sil?"
                                data-text="<?= htmlspecialchars($group['group_label']) ?> grubu ve bağlı ayarları silinecektir."
                                data-tooltip="Sil">
                                <i class="ri-delete-bin-line"></i>
                            </button>
                        </div>
                    </div>

                    <!-- İçerik -->
                    <div class="mb-4">
                        <h6 class="fw-bold text-dark mb-1" style="font-family: var(--rbn-font-body, inherit);"><?= htmlspecialchars($group['group_label']) ?></h6>
                        <p class="text-muted small lh-base mb-0" style="font-size: 0.8125rem; min-height: 38px;">
                            <?= htmlspecialchars($group['group_description'] ?: 'Grup parametrelerini yönetin.') ?>
                        </p>
                    </div>

                    <!-- Gruba Git Butonu -->
                    <a href="<?= $Route->url('backstage/settings/group/' . $group['id'], 'developer') ?>"
                        class="rbn-btn rbn-btn-outline w-100 rbn-btn-sm rounded-pill justify-content-center">
                        <i class="ri-equalizer-line"></i> Ayarları Yönet
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
