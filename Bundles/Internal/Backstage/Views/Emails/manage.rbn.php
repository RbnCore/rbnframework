<?php
/** @var array $settings */
?>

<!-- 🛠️ 1. ÜST AKSİYON BARI -->
<div class="d-flex justify-content-between align-items-center mb-3 px-1">
    <div class="d-flex align-items-center gap-2">
        <span class="rbn-badge rbn-badge-terracotta px-3 py-1.5 font-monospace" style="font-size: 0.8125rem;">
            <i class="ri-layers-line me-1"></i> Toplam <?= number_format(count($settings ?? [])) ?> Alan
        </span>
    </div>
    <button type="button" class="rbn-btn rbn-btn-primary rbn-btn-sm rounded-pill"
        data-rbn-modal="true" data-title="Yeni E-Posta Alanı Tanımla" data-theme="primary" data-size="lg"
        data-endpoint="<?= $Route->url('backstage/email/modal', 'developer') ?>" data-type="backstage/email" data-view="modal">
        <i class="ri-add-line"></i> Yeni Alan Tanımla
    </button>
</div>

<!-- 📑 2. MİMARİ YAPILANDIRMA TABLOSU -->
<div class="rbn-table-container mb-4">
    <table class="rbn-table w-100" id="email-manage-table" 
        data-rbn-sortable="true"
        data-sort-key="order"
        data-sort-url="<?= $Route->url('backstage/email/reorder', 'developer') ?>">
        <thead>
            <tr>
                <th style="width: 40px;"></th>
                <th>Görünür Ad (TR / EN)</th>
                <th>Anahtar (Key)</th>
                <th>Giriş Tipi</th>
                <th>Yetki Seviyesi</th>
                <th class="text-center">Durum</th>
                <th>Güncelleme</th>
                <th class="pe-4 text-end" style="width: 120px;">İşlemler</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($settings)): ?>
                <tr>
                    <td colspan="8" class="text-center py-5">
                        <i class="ri-equalizer-line fs-1 text-muted opacity-50 d-block mb-2"></i>
                        <span class="text-muted small">Henüz tanımlanmış bir e-posta alanı bulunmuyor.</span>
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
                                <span class="fw-bold text-dark" style="font-size: 0.875rem;"><?= htmlspecialchars($item['label_tr'] ?? '---') ?></span>
                                <span class="text-muted" style="font-size: 0.72rem;"><?= htmlspecialchars($item['label_en'] ?? $item['setting_key'] ?? '') ?></span>
                            </div>
                        </td>
                        <td>
                            <code class="text-primary font-monospace small bg-transparent p-0"><?= htmlspecialchars($item['setting_key']) ?></code>
                        </td>
                        <td>
                            <span class="rbn-badge rbn-badge-neutral rbn-badge-xs font-monospace">
                                <?= htmlspecialchars(!empty($item['field_type']) ? $item['field_type'] : 'text') ?>
                            </span>
                        </td>
                        <td>
                            <?php $isDev = ($item['required_role'] ?? '') === 'developer'; ?>
                            <span class="rbn-badge <?= $isDev ? 'rbn-badge-danger' : 'rbn-badge-info' ?> rbn-badge-xs font-monospace">
                                <?= strtoupper(htmlspecialchars($item['required_role'] ?? 'user')) ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <div class="form-check form-switch d-flex justify-content-center">
                                <input class="form-check-input ajax-status-toggle" type="checkbox"
                                    data-id="<?= $item['id'] ?>"
                                    data-url="<?= $Route->url('backstage/email/toggle', 'developer') ?>"
                                    <?= !empty($item['is_active']) ? 'checked' : '' ?>>
                            </div>
                        </td>
                        <td>
                            <span class="text-muted small font-monospace">
                                <?= is_numeric($item['updated_at'] ?? null) ? now('d.m.Y H:i', (int) $item['updated_at']) : htmlspecialchars($item['updated_at'] ?? '–') ?>
                            </span>
                        </td>
                        <td class="pe-4 text-end">
                            <div class="d-inline-flex gap-2">
                                <button type="button" class="ra-table-btn"
                                    data-rbn-modal="true" data-title="Alan Yapılandırmasını Düzenle" data-theme="dark"
                                    data-size="lg" data-endpoint="<?= $Route->url('backstage/email/modal/' . $item['id'], 'developer') ?>"
                                    data-type="backstage/email" data-view="modal" data-id="<?= $item['id'] ?>"
                                    data-tooltip="Düzenle">
                                    <i class="ri-edit-line"></i>
                                </button>
                                <button type="button" class="ra-table-btn ra-table-btn-danger action-confirm"
                                    data-url="<?= $Route->url('backstage/email/delete/' . $item['id'], 'developer') ?>"
                                    data-method="POST"
                                    data-title="E-Posta Alanı Silinsin mi?"
                                    data-text="<?= htmlspecialchars($item['label_tr'] ?? $item['setting_key']) ?> alanı kalıcı olarak silinecektir."
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
