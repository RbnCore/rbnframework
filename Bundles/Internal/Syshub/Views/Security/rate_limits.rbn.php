<?php
/** @var array $stats */
/** @var array $attempts */
?>

<!-- 📊 1. RATE LIMIT İSTATİSTİKLERİ -->
<div class="row g-4 mb-4">
    <!-- Bilgi Kartı -->
    <div class="col-12 col-lg-8">
        <div class="row g-4 h-100">
            <div class="col-12">
                <div class="rbn-card p-4">
                    <div class="d-flex align-items-start gap-3">
                        <div class="ra-stat-icon flex-shrink-0" style="background: rgba(37, 99, 235, 0.08); color: #2563eb;">
                            <i class="ri-pulse-line fs-4"></i>
                        </div>
                        <div>
                            <div class="fw-bold fs-6 text-dark mb-1" style="font-family: var(--rbn-font-body, inherit);">Hız Sınırı (Rate Limit) Bilgisi</div>
                            <div class="text-muted small lh-lg">
                                <div class="d-flex align-items-center gap-1.5"><i class="ri-shield-check-line text-success"></i> Rate limit sistemi tekrarlayan şüpheli istekleri anlık tespit ederek sistemi korur.</div>
                                <div class="d-flex align-items-center gap-1.5"><i class="ri-forbid-line text-danger"></i> Limit aşımları otomatik olarak geçici IP engellerine dönüştürülür.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Metrik Kartları -->
            <div class="col-12 col-sm-6">
                <div class="ra-stat-card ra-stat-terracotta h-100 d-flex flex-column justify-content-between">
                    <div class="d-flex align-items-center mb-2">
                        <div class="ra-stat-icon me-3">
                            <i class="ri-history-line"></i>
                        </div>
                        <div>
                            <div class="ra-stat-label">TÜM ZAMANLAR</div>
                            <h3 class="ra-stat-value fs-5 mb-0"><?= number_format((int) ($stats['total_attempts'] ?? 0)) ?></h3>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center pt-2 border-top border-secondary border-opacity-10 small">
                        <span class="text-muted">Kayıt:</span>
                        <span class="text-dark fw-bold">Toplam İstek</span>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6">
                <div class="ra-stat-card ra-stat-navy h-100 d-flex flex-column justify-content-between">
                    <div class="d-flex align-items-center mb-2">
                        <div class="ra-stat-icon me-3">
                            <i class="ri-timer-flash-line"></i>
                        </div>
                        <div>
                            <div class="ra-stat-label">24 SAATLİK DENEME</div>
                            <h3 class="ra-stat-value fs-5 mb-0"><?= number_format((int) ($stats['attempts_24h'] ?? 0)) ?></h3>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center pt-2 border-top border-primary border-opacity-10 small">
                        <span class="text-muted">Aralık:</span>
                        <span class="text-success fw-bold">Son 24 Saat</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sağ Taraf: Geçmişi Temizle Aksiyonu -->
    <div class="col-12 col-lg-4">
        <div class="rbn-card h-100 p-4 text-center d-flex flex-column justify-content-between">
            <div class="d-flex flex-column align-items-center">
                <div class="ra-stat-icon mx-auto mb-3" style="width: 56px; height: 56px; background: rgba(220, 38, 38, 0.08); color: #dc2626; font-size: 1.65rem;">
                    <i class="ri-delete-bin-2-line"></i>
                </div>
                <h5 class="fw-bold text-dark mb-1" style="font-family: var(--rbn-font-body, inherit);">Kayıtları Temizle</h5>
                <p class="text-muted small lh-base mb-3">Tüm limit aşımı ve şüpheli istek geçmişi kayıtlarını siler.</p>
                <span class="rbn-badge rbn-badge-neutral rbn-badge-xs mb-3 font-monospace">Toplam <?= count($limits ?? []) ?> Kayıt</span>
            </div>

            <button type="button"
                class="rbn-btn rbn-btn-danger w-100 rounded-pill py-2.5 justify-content-center"
                data-rbn-confirm="true"
                data-url="<?= $Route->url('syshub/security/ratelimits/clear', 'developer') ?>"
                data-method="POST"
                data-title="İstek Kayıtları Temizlensin mi?"
                data-message="Tüm hız sınırı ve deneme kayıtları silinecektir. Devam edilsin mi?"
                data-type="danger">
                <i class="ri-fire-line"></i> Günlükleri Temizle
            </button>
        </div>
    </div>
</div>

<!-- 📑 2. DENEME KAYITLARI TABLOSU -->
<form id="rateLimitsForm">
    @csrf
    <div class="rbn-table-container">
        <table class="rbn-table w-100" id="rate-limits-table">
            <thead>
                <tr>
                    <th class="ps-4" style="width: 40px;">
                        <input type="checkbox" class="form-check-input" data-rbn-check-all="rate-limits-table">
                    </th>
                    <th>IP Adresi</th>
                    <th>İşlem Anahtarı (Action)</th>
                    <th>İstek Sayısı</th>
                    <th>Son Deneme</th>
                    <th class="text-end pe-4">İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($limits)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-5">
                            <i class="ri-checkbox-circle-line fs-1 text-success opacity-50 d-block mb-2"></i>
                            <span class="text-muted small">Herhangi bir izinsiz istek denemesi kaydedilmedi.</span>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($limits as $item): ?>
                        <tr>
                            <td class="ps-4">
                                <input type="checkbox" name="ids[]" value="<?= $item['id'] ?>" class="form-check-input select-item">
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <i class="ri-pulse-line text-warning fs-5"></i>
                                    <span class="fw-bold text-dark font-monospace" style="font-size: 0.8125rem;"><?= htmlspecialchars($item['ip']) ?></span>
                                </div>
                            </td>
                            <td>
                                <span class="rbn-badge rbn-badge-neutral rbn-badge-xs font-monospace"><?= htmlspecialchars($item['action']) ?></span>
                            </td>
                            <td>
                                <span class="font-monospace text-dark fw-bold small"><?= $item['attempts'] ?> İstek</span>
                            </td>
                            <td>
                                <span class="text-muted small font-monospace"><?= now('d.m.Y H:i:s', $item['last_attempt']) ?></span>
                            </td>
                            <td class="text-end pe-4">
                                <div class="d-flex justify-content-end gap-1.5">
                                    <button type="button"
                                        class="rbn-btn rbn-btn-outline rbn-btn-xs text-danger"
                                        data-rbn-confirm="true"
                                        data-url="<?= $Route->url('syshub/security/ratelimits/blockIp/' . $item['ip'], 'developer') ?>"
                                        data-method="POST"
                                        data-title="IP Engellensin mi?"
                                        data-message="<?= htmlspecialchars($item['ip']) ?> IP adresi hemen karantinaya alınacaktır."
                                        data-type="danger"
                                        data-tooltip="Hemen Engelle">
                                        <i class="ri-forbid-2-line"></i>
                                    </button>
                                    <button type="button"
                                        class="rbn-btn rbn-btn-outline rbn-btn-xs text-muted"
                                        data-rbn-confirm="true"
                                        data-url="<?= $Route->url('syshub/security/ratelimits/delete/' . $item['id'], 'developer') ?>"
                                        data-method="POST"
                                        data-title="Kaydı Sil?"
                                        data-message="Bu deneme kaydı silinecektir."
                                        data-type="warning"
                                        data-tooltip="Kaydı Sil">
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
