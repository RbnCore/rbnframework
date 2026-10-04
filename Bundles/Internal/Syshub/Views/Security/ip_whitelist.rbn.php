<?php
/** @var array $stats */
/** @var array $whitelist */
?>

<!-- 📊 1. GÜVENLİ LİSTE İSTATİSTİKLERİ -->
<div class="row g-4 mb-4">
    <!-- Bilgi Kartı -->
    <div class="col-12 col-md-8">
        <div class="rbn-card p-4 h-100 d-flex flex-column justify-content-between">
            <div class="d-flex align-items-start justify-content-between flex-wrap gap-3">
                <div class="d-flex align-items-start gap-3">
                    <div class="ra-stat-icon flex-shrink-0" style="background: rgba(22, 163, 74, 0.08); color: #16a34a;">
                        <i class="ri-shield-check-line fs-4"></i>
                    </div>
                    <div>
                        <div class="fw-bold fs-6 text-dark mb-1" style="font-family: var(--rbn-font-body, inherit);">Güvenli Liste (Whitelist)</div>
                        <div class="text-muted small lh-lg">
                            <div class="d-flex align-items-center gap-1.5"><i class="ri-check-line text-success"></i> Bu listedeki IP adresleri Rate Limit ve IP Engelleme kurallarından muaf tutulur.</div>
                            <div class="d-flex align-items-center gap-1.5"><i class="ri-building-line text-primary"></i> Yönetici ofisleri, sabit IP'li geliştiriciler ve güvenli servisler eklenmelidir.</div>
                        </div>
                    </div>
                </div>
                <button type="button"
                    class="rbn-btn rbn-btn-primary rbn-btn-sm rounded-pill"
                    data-rbn-modal="true" data-title="Yeni Güvenli IP Ekle" data-theme="success" data-size="md"
                    data-type="security/ipwhitelist">
                    <i class="ri-add-line"></i> Yeni Güvenli IP Ekle
                </button>
            </div>
        </div>
    </div>

    <!-- Toplam Güvenli IP -->
    <div class="col-12 col-md-4">
        <div class="ra-stat-card ra-stat-success h-100 d-flex flex-column justify-content-between text-center">
            <div class="d-flex flex-column align-items-center justify-content-center">
                <div class="ra-stat-label">GÜVENLİ IP SAYISI</div>
                <h3 class="ra-stat-value fs-2"><?= number_format((int) ($stats['total_whitelisted'] ?? 0)) ?></h3>
            </div>
            <div class="d-flex justify-content-center align-items-center pt-2 border-top border-success border-opacity-10 small">
                <span class="text-success fw-bold"><i class="ri-shield-check-line me-1"></i>Muaf Kalkan Aktif</span>
            </div>
        </div>
    </div>
</div>

<!-- 📑 2. GÜVENLİ IP LİSTESİ TABLOSU -->
<form id="whitelistForm">
    @csrf
    <div class="rbn-table-container">
        <table class="rbn-table w-100" id="whitelist-table">
            <thead>
                <tr>
                    <th class="ps-4" style="width: 40px;">
                        <input type="checkbox" class="form-check-input" data-rbn-check-all="whitelist-table">
                    </th>
                    <th>IP Adresi</th>
                    <th>Etiket / Açıklama</th>
                    <th>Eklenme Tarihi</th>
                    <th class="text-end pe-4">İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($whitelist)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-5">
                            <i class="ri-checkbox-circle-line fs-1 text-success opacity-50 d-block mb-2"></i>
                            <span class="text-muted small">Güvenli listeye eklenmiş herhangi bir IP adresi bulunmuyor.</span>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($whitelist as $item): ?>
                        <tr>
                            <td class="ps-4">
                                <input type="checkbox" name="ids[]" value="<?= $item['id'] ?>" class="form-check-input select-item">
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <i class="ri-shield-check-line text-success fs-5"></i>
                                    <span class="fw-bold text-dark font-monospace" style="font-size: 0.8125rem;"><?= htmlspecialchars($item['ip']) ?></span>
                                </div>
                            </td>
                            <td>
                                <span class="text-dark small fw-medium"><?= htmlspecialchars($item['label'] ?? 'Sabit Güvenli IP') ?></span>
                            </td>
                            <td>
                                <span class="text-muted small font-monospace"><?= now('d.m.Y H:i', $item['created_at']) ?></span>
                            </td>
                            <td class="text-end pe-4">
                                <div class="d-flex justify-content-end gap-1.5">
                                    <button type="button"
                                        class="rbn-btn rbn-btn-outline rbn-btn-xs text-danger"
                                        data-rbn-confirm="true"
                                        data-url="<?= $Route->url('syshub/security/whitelist/delete/' . $item['id'], 'developer') ?>"
                                        data-method="POST"
                                        data-title="Listeden Çıkarılsın mı?"
                                        data-message="<?= htmlspecialchars($item['ip']) ?> güvenli listeden kaldırılacaktır."
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
</form>
