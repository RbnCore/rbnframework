<?php
/** @var array $group */
/** @var array $settings */
?>

<!-- 🛠️ 1. ÜST AKSİYON BARI -->
<div class="d-flex justify-content-between align-items-center mb-3 px-1 flex-wrap gap-2">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <a href="<?= $Route->url('backstage/settings', 'developer') ?>" class="rbn-btn rbn-btn-outline rbn-btn-xs rounded-pill">
                <i class="ri-arrow-left-line"></i> Gruplara Dön
            </a>
            <h6 class="fw-bold mb-0 text-dark" style="font-family: var(--rbn-font-body, inherit);">
                <i class="ri-list-settings-line text-primary me-1"></i><?= htmlspecialchars($group['group_label'] ?? 'Ayar Grubu') ?>
            </h6>
        </div>
        <span class="text-muted small"><?= htmlspecialchars($group['group_description'] ?: 'Bu gruba ait sistem ayar parametreleri.') ?></span>
    </div>
    <div class="d-flex gap-2 align-items-center">
        <span class="rbn-badge rbn-badge-neutral rbn-badge-sm font-monospace"><?= count($settings ?? []) ?> Ayar</span>
        <button type="button" class="rbn-btn rbn-btn-primary rbn-btn-sm rounded-pill"
            data-rbn-modal="true" data-title="Yeni Ayar Tanımla" data-theme="dark" data-size="lg"
            data-endpoint="<?= $Route->url('backstage/settings/modal', 'developer') ?>?groupId=<?= $group['id'] ?>"
            data-type="backstage/settings"
            data-view="modal">
            <i class="ri-add-line"></i> Yeni Ayar Ekle
        </button>
    </div>
</div>

<!-- 📑 2. AYARLAR TABLOSU -->
<div class="rbn-table-container mb-4">
    <table class="rbn-table w-100" id="group-settings-table">
        <thead>
            <tr>
                <th style="width: 40px;"></th>
                <th>Ayar Adı / Key</th>
                <th>Veri Tipi</th>
                <th>Yetki Seviyesi</th>
                <th class="text-center">Durum</th>
                <th class="pe-4 text-end" style="width: 120px;">İşlemler</th>
            </tr>
        </thead>
        <tbody id="sortable-settings" data-rbn-sortable="true"
            data-sort-url="<?= $Route->url('backstage/settings/sort', 'developer') ?>"
            data-sort-handle=".sortable-handle">
            <?php if (empty($settings)): ?>
                <tr>
                    <td colspan="6" class="text-center py-5">
                        <i class="ri-settings-5-line fs-1 text-muted opacity-50 d-block mb-2"></i>
                        <span class="text-muted small">Bu grupta henüz tanımlanmış bir ayar bulunmuyor.</span>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($settings as $item): ?>
                    <tr data-id="<?= $item['id'] ?>">
                        <td class="ps-4">
                            <i class="ri-draggable fs-5 text-muted sortable-handle" style="cursor: move;"></i>
                        </td>
                        <td>
                            <div class="d-flex flex-column">
                                <span class="fw-bold text-dark" style="font-size: 0.875rem;"><?= htmlspecialchars($item['label_tr']) ?></span>
                                <code class="text-muted font-monospace" style="font-size: 0.7rem;"><?= htmlspecialchars($item['setting_key']) ?></code>
                            </div>
                        </td>
                        <td>
                            <span class="rbn-badge rbn-badge-neutral rbn-badge-xs font-monospace">
                                <?= htmlspecialchars($item['field_type'] ?? 'text') ?>
                            </span>
                        </td>
                        <td>
                            <?php $isDev = ($item['required_role'] ?? '') === 'developer'; ?>
                            <span class="rbn-badge <?= $isDev ? 'rbn-badge-danger' : 'rbn-badge-info' ?> rbn-badge-xs font-monospace">
                                <?= strtoupper(htmlspecialchars($item['required_role'] ?? 'admin')) ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <div class="form-check form-switch d-flex justify-content-center">
                                <input class="form-check-input ajax-status-toggle" type="checkbox"
                                    data-id="<?= $item['id'] ?>"
                                    data-url="<?= $Route->url('backstage/settings/toggle', 'developer') ?>"
                                    <?= !empty($item['is_active']) ? 'checked' : '' ?>>
                            </div>
                        </td>
                        <td class="pe-4 text-end">
                            <div class="d-inline-flex gap-2">
                                <button type="button" class="ra-table-btn"
                                    data-rbn-modal="true" data-title="Ayar Düzenle" data-theme="dark" data-size="lg"
                                    data-endpoint="<?= $Route->url('backstage/settings/modal/' . $item['id'], 'developer') ?>"
                                    data-type="backstage/settings" data-view="modal" data-id="<?= $item['id'] ?>"
                                    data-tooltip="Düzenle">
                                    <i class="ri-edit-line"></i>
                                </button>
                                <button type="button" class="ra-table-btn ra-table-btn-danger action-confirm"
                                    data-url="<?= $Route->url('backstage/settings/delete/' . $item['id'], 'developer') ?>"
                                    data-method="POST" data-title="Ayar Silinsin mi?"
                                    data-text="<?= htmlspecialchars($item['label_tr'] ?? $item['setting_key']) ?> ayarı kalıcı olarak silinecektir."
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
