<?php
/** @var array $stats */
/** @var array $blocks */
?>

<!-- 📊 1. GÜVENLİK VE ENGEL İSTATİSTİKLERİ -->
<div class="row g-4 mb-4">
    <!-- Sol Taraf: Bilgi Kartı ve Metrikler -->
    <div class="col-12 col-lg-8">
        <div class="row g-4 h-100">
            <!-- Bilgi Kartı -->
            <div class="col-12">
                <div class="rbn-card p-4">
                    <div class="d-flex align-items-start justify-content-between flex-wrap gap-3">
                        <div class="d-flex align-items-start gap-3">
                            <div class="ra-stat-icon flex-shrink-0" style="background: rgba(220, 38, 38, 0.08); color: #dc2626;">
                                <i class="ri-shield-cross-line fs-4"></i>
                            </div>
                            <div>
                                <div class="fw-bold fs-6 text-dark mb-1" style="font-family: var(--rbn-font-body, inherit);">IP Engel Bilgisi</div>
                                <div class="text-muted small lh-lg">
                                    <div class="d-flex align-items-center gap-1.5"><i class="ri-close-circle-line text-danger"></i> Engellenen IP adresleri sistem erişiminden tamamen men edilir.</div>
                                    <div class="d-flex align-items-center gap-1.5"><i class="ri-time-line text-warning"></i> Süreli engeller bitiş tarihinde otonom olarak sisteme erişebilir.</div>
                                </div>
                            </div>
                        </div>
                        <button type="button"
                            class="rbn-btn rbn-btn-danger rbn-btn-sm rounded-pill"
                            data-rbn-modal="true" data-title="Yeni IP Engelle" data-theme="danger" data-size="md"
                            data-type="security/ipblock">
                            <i class="ri-add-line"></i> Yeni IP Engelle
                        </button>
                    </div>
                </div>
            </div>

            <!-- Metrik Kartları -->
            <div class="col-12 col-sm-4">
                <div class="ra-stat-card ra-stat-danger h-100 d-flex flex-column justify-content-between">
                    <div class="d-flex align-items-center mb-2">
                        <div class="ra-stat-icon me-3">
                            <i class="ri-forbid-2-line"></i>
                        </div>
                        <div>
                            <div class="ra-stat-label">TOPLAM ENGEL</div>
                            <h3 class="ra-stat-value fs-5 mb-0"><?= number_format((int) ($stats['total_blocked'] ?? 0)) ?></h3>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center pt-2 border-top border-danger border-opacity-10 small">
                        <span class="text-muted">Kapsam:</span>
                        <span class="text-dark fw-bold">Kara Liste</span>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-4">
                <div class="ra-stat-card ra-stat-navy h-100 d-flex flex-column justify-content-between">
                    <div class="d-flex align-items-center mb-2">
                        <div class="ra-stat-icon me-3">
                            <i class="ri-time-line"></i>
                        </div>
                        <div>
                            <div class="ra-stat-label">SÜRELİ ENGEL</div>
                            <h3 class="ra-stat-value fs-5 mb-0"><?= number_format((int) ($stats['temporary_blocks'] ?? 0)) ?></h3>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center pt-2 border-top border-primary border-opacity-10 small">
                        <span class="text-muted">Durum:</span>
                        <span class="text-dark fw-bold">Zaman Ayarlı</span>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-4">
                <div class="ra-stat-card ra-stat-warning h-100 d-flex flex-column justify-content-between">
                    <div class="d-flex align-items-center mb-2">
                        <div class="ra-stat-icon me-3">
                            <i class="ri-lock-line"></i>
                        </div>
                        <div>
                            <div class="ra-stat-label">KALICI ENGEL</div>
                            <h3 class="ra-stat-value fs-5 mb-0"><?= number_format((int) ($stats['permanent_blocks'] ?? 0)) ?></h3>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center pt-2 border-top border-warning border-opacity-10 small">
                        <span class="text-muted">Tip:</span>
                        <span class="text-danger fw-bold">Süresiz</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sağ Taraf: Tümünü Temizle Aksiyonu -->
    <div class="col-12 col-lg-4">
        <div class="rbn-card h-100 p-4 text-center d-flex flex-column justify-content-between">
            <div class="d-flex flex-column align-items-center">
                <div class="ra-stat-icon mx-auto mb-3" style="width: 56px; height: 56px; background: rgba(220, 38, 38, 0.08); color: #dc2626; font-size: 1.65rem;">
                    <i class="ri-delete-bin-2-line"></i>
                </div>
                <h5 class="fw-bold text-dark mb-1" style="font-family: var(--rbn-font-body, inherit);">Engelleri Temizle</h5>
                <p class="text-muted small lh-base mb-3">Sistemdeki tüm aktif IP engellemelerini kaldırır ve kara listeyi sıfırlar.</p>
                <span class="rbn-badge rbn-badge-danger rbn-badge-xs mb-3">Toplam <?= count($blocks) ?> Engel Kayıtlı</span>
            </div>

            <button type="button"
                class="rbn-btn rbn-btn-danger w-100 rounded-pill py-2.5 justify-content-center"
                data-rbn-confirm="true"
                data-url="<?= $Route->url('syshub/security/ipBlock/clearAll', 'developer') ?>"
                data-method="POST"
                data-title="Tüm Engeller Kaldırılsın mı?"
                data-message="Sistemdeki tüm IP engelleri kalıcı olarak kaldırılacaktır. Emin misiniz?"
                data-type="danger">
                <i class="ri-fire-line"></i> Tümünü Sıfırla
            </button>
        </div>
    </div>
</div>

<!-- 📑 2. ENGELLİ IP LİSTESİ TABLOSU -->
<form id="ipBlocksForm">
    @csrf
    <div class="rbn-table-container">
        <table class="rbn-table w-100" id="ip-blocks-table">
            <thead>
                <tr>
                    <th class="ps-4" style="width: 40px;">
                        <input type="checkbox" class="form-check-input" data-rbn-check-all="ip-blocks-table">
                    </th>
                    <th>IP Adresi</th>
                    <th>Neden / Açıklama</th>
                    <th>Kalan Süre</th>
                    <th>Engelleme Tarihi</th>
                    <th class="text-end pe-4">İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($blocks)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-5">
                            <i class="ri-checkbox-circle-line fs-1 text-success opacity-50 d-block mb-2"></i>
                            <span class="text-muted small">Aktif engellenmiş herhangi bir IP adresi bulunmuyor.</span>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($blocks as $block): ?>
                        <tr>
                            <td class="ps-4">
                                <input type="checkbox" name="ids[]" value="<?= $block['id'] ?>" class="form-check-input select-item">
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <i class="ri-shield-cross-line text-danger fs-5"></i>
                                    <span class="fw-bold text-dark font-monospace" style="font-size: 0.8125rem;"><?= htmlspecialchars($block['ip']) ?></span>
                                </div>
                            </td>
                            <td>
                                <span class="text-dark small"><?= htmlspecialchars($block['reason'] ?? 'Şüpheli Aktivite') ?></span>
                            </td>
                            <td>
                                <?php if (!empty($block['is_permanent'])): ?>
                                    <span class="rbn-badge rbn-badge-danger rbn-badge-xs">Süresiz</span>
                                <?php else: ?>
                                    <span class="rbn-badge rbn-badge-neutral rbn-badge-xs font-monospace"><?= $block['remaining_time'] ?? '–' ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="text-muted small font-monospace"><?= now('d.m.Y H:i', $block['created_at']) ?></span>
                            </td>
                            <td class="text-end pe-4">
                                <div class="d-flex justify-content-end gap-1.5">
                                    <button type="button"
                                        class="rbn-btn rbn-btn-outline rbn-btn-xs text-danger"
                                        data-rbn-confirm="true"
                                        data-url="<?= $Route->url('syshub/security/ipBlock/delete/' . $block['id'], 'developer') ?>"
                                        data-method="POST"
                                        data-title="Engeli Kaldır?"
                                        data-message="<?= htmlspecialchars($block['ip']) ?> IP adresinin engeli kaldırılacaktır."
                                        data-type="warning"
                                        data-tooltip="Engeli Kaldır">
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
</form>
