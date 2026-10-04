<?php
/** @var array $moduleConfig */
/** @var array $stats */
/** @var array $files */
/** @var string $type */
?>

<!-- 📊 1. MODÜL STAT & SÜPÜRGE KOKPİTİ -->
<div class="row g-4 mb-4">
    <!-- Sol Taraf: Sistem Bilgisi ve Metrikler -->
    <div class="col-12 col-lg-8">
        <div class="row g-4 h-100">
            <!-- Modül Bilgi Kartı -->
            <div class="col-12">
                <div class="rbn-card p-4">
                    <div class="d-flex align-items-start gap-3">
                        <div class="ra-stat-icon flex-shrink-0" style="background: rgba(197, 106, 60, 0.08); color: var(--rbn-primary, #c56a3c);">
                            <i class="ri-information-line fs-4"></i>
                        </div>
                        <div>
                            <div class="fw-bold fs-6 text-dark mb-1" style="font-family: var(--rbn-font-body, inherit);"><?= $moduleConfig['title'] ?> Hakkında</div>
                            <div class="text-muted small lh-lg">
                                <?php foreach ($moduleConfig['info_points'] as $point): ?>
                                    <div class="d-flex align-items-center gap-1.5"><i class="ri-check-line text-success"></i> <?= $point ?></div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dosya Sayısı & Toplam Boyut Stat Kartları -->
            <div class="col-12 col-sm-6">
                <div class="ra-stat-card ra-stat-terracotta h-100 d-flex flex-column justify-content-between">
                    <div class="d-flex align-items-center mb-2">
                        <div class="ra-stat-icon me-3">
                            <i class="ri-file-list-3-line"></i>
                        </div>
                        <div>
                            <div class="ra-stat-label">TOPLAM DOSYA</div>
                            <h3 class="ra-stat-value"><?= number_format($stats['total_files'] ?? 0) ?></h3>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center pt-2 border-top border-secondary border-opacity-10 small">
                        <span class="text-muted">Durum:</span>
                        <span class="text-dark fw-bold">Aktif Kayıtlar</span>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6">
                <div class="ra-stat-card ra-stat-navy h-100 d-flex flex-column justify-content-between">
                    <div class="d-flex align-items-center mb-2">
                        <div class="ra-stat-icon me-3">
                            <i class="ri-database-2-line"></i>
                        </div>
                        <div>
                            <div class="ra-stat-label">KAPLANAN ALAN</div>
                            <h3 class="ra-stat-value fs-2"><?= $stats['total_size'] ?? '0 B' ?></h3>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center pt-2 border-top border-primary border-opacity-10 small">
                        <span class="text-muted">Depolama:</span>
                        <span class="text-dark fw-bold">Storage Klasörü</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sağ Taraf: Süpürme Aksiyon Kartı -->
    <div class="col-12 col-lg-4">
        <div class="rbn-card h-100 p-4 text-center d-flex flex-column justify-content-between">
            <div class="d-flex flex-column align-items-center">
                <div class="ra-stat-icon mx-auto mb-3" style="width: 56px; height: 56px; background: rgba(220, 38, 38, 0.08); color: #dc2626; font-size: 1.65rem;">
                    <i class="ri-delete-bin-2-line"></i>
                </div>
                <h5 class="fw-bold text-dark mb-1" style="font-family: var(--rbn-font-body, inherit);">Tümünü Temizle</h5>
                <p class="text-muted small lh-base mb-3"><?= $moduleConfig['description'] ?></p>
            </div>

            <button type="button"
                class="rbn-btn rbn-btn-danger w-100 rounded-pill py-2.5 justify-content-center"
                data-rbn-confirm="true"
                data-url="<?= $Route->url('syshub/datapurge/clear/' . $type, 'developer') ?>"
                data-method="POST"
                data-title="<?= $moduleConfig['title'] ?> Temizlensin mi?"
                data-message="<?= $moduleConfig['confirm_text'] ?>"
                data-type="danger">
                <i class="ri-fire-line"></i> Şimdi Süpür
            </button>
        </div>
    </div>
</div>

<!-- 📑 2. DOSYA LİSTESİ TABLOSU -->
<?php if (!empty($files)): ?>
    <div class="rbn-table-container">
        <table class="rbn-table w-100" id="common-table">
            <thead>
                <tr>
                    <th class="ps-4" style="width: 40px;">
                        <input type="checkbox" class="form-check-input" data-rbn-check-all="common-table">
                    </th>
                    <th>Dosya Adı</th>
                    <th>Boyut</th>
                    <th>Son Değiştirilme</th>
                    <th class="text-end pe-4">İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($files as $file): ?>
                    <tr>
                        <td class="ps-4">
                            <input type="checkbox" class="form-check-input select-item" value="<?= htmlspecialchars($file['name']) ?>">
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <i class="ri-file-code-line text-muted fs-5"></i>
                                <span class="fw-bold text-dark font-monospace" style="font-size: 0.8125rem;"><?= htmlspecialchars($file['name']) ?></span>
                            </div>
                        </td>
                        <td>
                            <span class="rbn-badge rbn-badge-neutral font-monospace"><?= $file['formatted_size'] ?? '0 B' ?></span>
                        </td>
                        <td>
                            <span class="text-muted small font-monospace"><?= $file['modified_at'] ?? '–' ?></span>
                        </td>
                        <td class="text-end pe-4">
                            <div class="d-flex justify-content-end gap-1.5">
                                <?php if (!empty($file['view_url'])): ?>
                                    <a href="<?= $file['view_url'] ?>" class="rbn-btn rbn-btn-outline rbn-btn-xs" data-tooltip="Görüntüle">
                                        <i class="ri-eye-line"></i>
                                    </a>
                                <?php endif; ?>
                                <button type="button"
                                    class="rbn-btn rbn-btn-outline rbn-btn-xs text-danger"
                                    data-rbn-confirm="true"
                                    data-url="<?= $file['delete_url'] ?? '#' ?>"
                                    data-method="POST"
                                    data-title="Dosya Silinsin mi?"
                                    data-message="<?= htmlspecialchars($file['name']) ?> dosyası silinecektir. Emin misiniz?"
                                    data-type="danger"
                                    data-tooltip="Sil">
                                    <i class="ri-delete-bin-line"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <div class="rbn-card p-5 text-center">
        <i class="ri-checkbox-circle-line fs-1 text-success opacity-50 d-block mb-2"></i>
        <h6 class="fw-bold text-dark mb-1">Depo Tamamen Temiz!</h6>
        <p class="text-muted small mb-0">Bu modüle ait temizlenecek herhangi bir geçici dosya bulunmuyor.</p>
    </div>
<?php endif; ?>
