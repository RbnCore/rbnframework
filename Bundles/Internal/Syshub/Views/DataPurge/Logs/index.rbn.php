<?php
/** @var array $stats */
/** @var array $logs */
?>

<!-- 📊 1. LOG KATEGORİ STATLARI & SÜPÜRGE -->
<div class="row g-4 mb-4">
    <!-- Sol Taraf: Log Tipleri Grid (3x2) -->
    <div class="col-12 col-lg-8">
        <div class="row g-3 h-100">
            <?php foreach ($stats as $type => $data): ?>
                <?php $config = $data['config']; 
                    $iconClass = str_replace('bi-', 'ri-', $config['icon'] ?? '');
                    if ($iconClass === 'ri-bug') $iconClass = 'ri-bug-line';
                    if ($iconClass === 'ri-shield-lock') $iconClass = 'ri-shield-keyhole-line';
                    if ($iconClass === 'ri-terminal') $iconClass = 'ri-terminal-box-line';
                    if ($iconClass === 'ri-envelope') $iconClass = 'ri-mail-line';
                    if ($iconClass === 'ri-database') $iconClass = 'ri-database-2-line';
                    if (!str_starts_with($iconClass, 'ri-')) $iconClass = 'ri-file-list-3-line';
                ?>
                <div class="col-12 col-sm-6 col-md-4">
                    <div class="rbn-card p-3 h-100 d-flex flex-column justify-content-between">
                        <div class="d-flex align-items-center gap-2.5 mb-2">
                            <div class="ra-stat-icon" style="width: 36px; height: 36px; font-size: 1.1rem; background: rgba(197, 106, 60, 0.08); color: var(--rbn-primary, #c56a3c);">
                                <i class="<?= $iconClass ?>"></i>
                            </div>
                            <div class="min-w-0">
                                <span class="ra-stat-label d-block text-truncate" style="font-size: 0.65rem;"><?= htmlspecialchars($config['title']) ?></span>
                                <div class="fw-bold text-dark font-monospace small"><?= $data['count'] ?> Dosya</div>
                            </div>
                        </div>
                        <div class="pt-1.5 border-top border-light d-flex justify-content-between align-items-center font-monospace text-muted" style="font-size: 0.68rem;">
                            <span>Boyut:</span>
                            <span class="text-dark fw-semibold"><?= $data['total_size'] ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Sağ Taraf: Süpürme Aksiyon Kartı -->
    <div class="col-12 col-lg-4">
        <div class="rbn-card h-100 p-4 text-center d-flex flex-column justify-content-between">
            <div class="d-flex flex-column align-items-center">
                <div class="ra-stat-icon mx-auto mb-3" style="width: 56px; height: 56px; background: rgba(220, 38, 38, 0.08); color: #dc2626; font-size: 1.65rem;">
                    <i class="ri-delete-bin-2-line"></i>
                </div>
                <h5 class="fw-bold text-dark mb-1" style="font-family: var(--rbn-font-body, inherit);">Tüm Logları Temizle</h5>
                <p class="text-muted small lh-base mb-3">Tüm sistem log dosyalarını kalıcı olarak siler ve disk alanını boşaltır.</p>
            </div>

            <button type="button"
                class="rbn-btn rbn-btn-danger w-100 rounded-pill py-2.5 justify-content-center"
                data-rbn-confirm="true"
                data-url="<?= $Route->url('syshub/datapurge/clear/logs', 'developer') ?>"
                data-method="POST"
                data-title="Tüm Loglar Temizlensin mi?"
                data-message="Tüm log dosyaları kalıcı olarak silinecektir. Emin misiniz?"
                data-type="danger">
                <i class="ri-fire-line"></i> Logları Sıfırla
            </button>
        </div>
    </div>
</div>

<!-- 📑 2. LOG DOSYALARI TABLOSU -->
<form id="logsForm" method="POST">
    @csrf
    <div class="rbn-table-container">
        <table class="rbn-table w-100" id="logs-table">
            <thead>
                <tr>
                    <th class="ps-4" style="width: 40px;">
                        <input type="checkbox" class="form-check-input" data-rbn-check-all="logs-table">
                    </th>
                    <th>Log Dosyası</th>
                    <th>Kategori</th>
                    <th>Boyut</th>
                    <th>Son Güncelleme</th>
                    <th class="text-end pe-4">İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-5">
                            <i class="ri-checkbox-circle-line fs-1 text-success opacity-50 d-block mb-2"></i>
                            <span class="text-muted small">Herhangi bir log dosyası bulunamadı.</span>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td class="ps-4">
                                <input type="checkbox" name="logs[]" value="<?= htmlspecialchars($log['relative']) ?>" class="form-check-input select-item bulk-check-item">
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <i class="ri-file-text-line text-muted fs-5"></i>
                                    <span class="fw-bold text-dark font-monospace" style="font-size: 0.8125rem;"><?= htmlspecialchars($log['name']) ?></span>
                                </div>
                            </td>
                            <td>
                                <span class="rbn-badge rbn-badge-neutral rbn-badge-xs"><?= htmlspecialchars($log['type']) ?></span>
                            </td>
                            <td>
                                <span class="font-monospace text-dark small"><?= $log['size'] ?></span>
                            </td>
                            <td>
                                <span class="text-muted small font-monospace"><?= now('d.m.Y H:i', $log['modified']) ?></span>
                            </td>
                            <td class="text-end pe-4">
                                <div class="d-flex justify-content-end gap-1.5">
                                    <a href="<?= $Route->url('syshub/datapurge/logs/view/' . $log['relative'], 'developer') ?>"
                                        class="rbn-btn rbn-btn-outline rbn-btn-xs" data-tooltip="Görüntüle">
                                        <i class="ri-eye-line"></i>
                                    </a>
                                    <a href="<?= $Route->url('syshub/datapurge/logs/download/' . $log['relative'], 'developer') ?>"
                                        class="rbn-btn rbn-btn-outline rbn-btn-xs" data-tooltip="İndir">
                                        <i class="ri-download-2-line"></i>
                                    </a>
                                    <button type="button"
                                        class="rbn-btn rbn-btn-outline rbn-btn-xs text-danger"
                                        data-rbn-confirm="true"
                                        data-url="<?= $Route->url('syshub/datapurge/logs/delete/' . $log['relative'], 'developer') ?>"
                                        data-method="POST"
                                        data-title="Log Silinsin mi?"
                                        data-message="<?= htmlspecialchars($log['name']) ?> log dosyası kalıcı olarak silinecektir."
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
