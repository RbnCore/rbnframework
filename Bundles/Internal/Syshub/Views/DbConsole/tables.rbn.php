<?php
/** @var array $database_info */
/** @var array $tables */
/** @var int $total */
/** @var string $search */
?>

<!-- 📊 1. TABLO İSTATİSTİK KARTLARI -->
<div class="row g-4 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="ra-stat-card ra-stat-navy h-100 d-flex flex-column justify-content-between">
            <div class="d-flex align-items-center mb-2">
                <div class="ra-stat-icon me-3">
                    <i class="ri-database-2-line"></i>
                </div>
                <div class="min-w-0">
                    <div class="ra-stat-label">VERİTABANI ADI</div>
                    <h3 class="ra-stat-value fs-6 text-truncate mb-0" title="<?= htmlspecialchars($database_info['name']) ?>">
                        <?= htmlspecialchars($database_info['name']) ?>
                    </h3>
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center pt-2 border-top border-primary border-opacity-10 small">
                <span class="text-muted">Motor:</span>
                <span class="text-dark fw-bold"><?= htmlspecialchars($database_info['type'] ?? 'MySQL') ?></span>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="ra-stat-card ra-stat-terracotta h-100 d-flex flex-column justify-content-between">
            <div class="d-flex align-items-center mb-2">
                <div class="ra-stat-icon me-3">
                    <i class="ri-hard-drive-2-line"></i>
                </div>
                <div>
                    <div class="ra-stat-label">TOPLAM BOYUT</div>
                    <h3 class="ra-stat-value fs-5 mb-0"><?= $database_info['size'] ?></h3>
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center pt-2 border-top border-secondary border-opacity-10 small">
                <span class="text-muted">Kullanım:</span>
                <span class="text-dark fw-bold">Disk Alanı</span>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="ra-stat-card ra-stat-warning h-100 d-flex flex-column justify-content-between">
            <div class="d-flex align-items-center mb-2">
                <div class="ra-stat-icon me-3">
                    <i class="ri-table-line"></i>
                </div>
                <div>
                    <div class="ra-stat-label"><?= !empty($search) ? 'BULUNAN TABLO' : 'TABLO SAYISI' ?></div>
                    <h3 class="ra-stat-value fs-5 mb-0"><?= number_format($total) ?> Tablo</h3>
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center pt-2 border-top border-warning border-opacity-10 small">
                <span class="text-muted">Kapsam:</span>
                <span class="text-dark fw-bold">Aktif Şema</span>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="ra-stat-card ra-stat-success h-100 d-flex flex-column justify-content-between">
            <div class="d-flex align-items-center mb-2">
                <div class="ra-stat-icon me-3">
                    <i class="ri-list-ordered"></i>
                </div>
                <div>
                    <div class="ra-stat-label">TOPLAM SATIR</div>
                    <h3 class="ra-stat-value fs-5 mb-0"><?= number_format($database_info['records'] ?? 0) ?></h3>
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center pt-2 border-top border-success border-opacity-10 small">
                <span class="text-muted">Kayıt:</span>
                <span class="text-success fw-bold">Toplam Satır</span>
            </div>
        </div>
    </div>
</div>

<!-- 📑 2. TABLO LİSTESİ -->
<form id="tableListForm">
    @csrf
    <div class="rbn-table-container">
        <table class="rbn-table w-100" id="tables-explorer-table">
            <thead>
                <tr>
                    <th class="ps-4" style="width: 40px;">
                        <input type="checkbox" class="form-check-input" data-rbn-check-all="tables-explorer-table">
                    </th>
                    <th>Tablo Adı</th>
                    <th>Satır Sayısı</th>
                    <th>Boyut</th>
                    <th>Collation</th>
                    <th class="text-end pe-4">İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($tables)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-5">
                            <i class="ri-table-line fs-1 text-muted opacity-25 d-block mb-2"></i>
                            <span class="text-muted small">Tablo bulunamadı.</span>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($tables as $tbl): ?>
                        <tr>
                            <td class="ps-4">
                                <input type="checkbox" name="tables[]" value="<?= htmlspecialchars($tbl['name']) ?>" class="form-check-input select-item">
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <i class="ri-table-2 text-muted fs-5"></i>
                                    <a href="<?= $Route->url('syshub/dbconsole/tables/browse/' . $tbl['name'], 'developer') ?>" class="fw-bold text-dark font-monospace text-decoration-none" style="font-size: 0.8125rem;">
                                        <?= htmlspecialchars($tbl['name']) ?>
                                    </a>
                                </div>
                            </td>
                            <td>
                                <span class="font-monospace text-dark small"><?= number_format($tbl['rows']) ?></span>
                            </td>
                            <td>
                                <span class="rbn-badge rbn-badge-neutral rbn-badge-xs font-monospace"><?= $tbl['size'] ?></span>
                            </td>
                            <td>
                                <span class="text-muted small font-monospace"><?= htmlspecialchars($tbl['collation'] ?? 'utf8mb4_unicode_ci') ?></span>
                            </td>
                            <td class="text-end pe-4">
                                <div class="d-flex justify-content-end gap-1.5">
                                    <a href="<?= $Route->url('syshub/dbconsole/tables/browse/' . $tbl['name'], 'developer') ?>"
                                        class="rbn-btn rbn-btn-outline rbn-btn-xs" data-tooltip="Kayıtları Gör">
                                        <i class="ri-eye-line"></i>
                                    </a>
                                    <a href="<?= $Route->url('syshub/dbconsole/tables/structure/' . $tbl['name'], 'developer') ?>"
                                        class="rbn-btn rbn-btn-outline rbn-btn-xs" data-tooltip="Yapıyı İncele">
                                        <i class="ri-layout-grid-line"></i>
                                    </a>
                                    <button type="button"
                                        class="rbn-btn rbn-btn-outline rbn-btn-xs text-danger"
                                        data-rbn-confirm="true"
                                        data-url="<?= $Route->url('syshub/dbconsole/tables/truncate/' . $tbl['name'], 'developer') ?>"
                                        data-method="POST"
                                        data-title="Tablo Boşaltılsın mı?"
                                        data-message="<?= htmlspecialchars($tbl['name']) ?> tablosundaki tüm kayıtlar silinecektir."
                                        data-type="danger"
                                        data-tooltip="Boşalt (TRUNCATE)">
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
