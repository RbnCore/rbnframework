<?php
/** @var string $table */
/** @var int $total */
/** @var array $table_metadata */
/** @var array $records */
/** @var array $columns */
/** @var int $page */
/** @var int $limit */
/** @var string $search */
?>

<!-- 📊 1. TABLO DETAY İSTATİSTİK KARTLARI -->
<div class="row g-4 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="ra-stat-card ra-stat-navy h-100 d-flex flex-column justify-content-between">
            <div class="d-flex align-items-center mb-2">
                <div class="ra-stat-icon me-3">
                    <i class="ri-table-2"></i>
                </div>
                <div class="min-w-0">
                    <div class="ra-stat-label">ŞU ANKİ TABLO</div>
                    <h3 class="ra-stat-value fs-6 text-truncate mb-0" title="<?= htmlspecialchars($table) ?>">
                        <?= htmlspecialchars($table) ?>
                    </h3>
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center pt-2 border-top border-primary border-opacity-10 small">
                <span class="text-muted">Motor:</span>
                <span class="text-dark fw-bold"><?= htmlspecialchars($table_metadata['Engine'] ?? 'InnoDB') ?></span>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="ra-stat-card ra-stat-terracotta h-100 d-flex flex-column justify-content-between">
            <div class="d-flex align-items-center mb-2">
                <div class="ra-stat-icon me-3">
                    <i class="ri-list-ordered"></i>
                </div>
                <div>
                    <div class="ra-stat-label"><?= !empty($search) ? 'BULUNAN KAYIT' : 'TOPLAM KAYIT' ?></div>
                    <h3 class="ra-stat-value fs-5 mb-0"><?= number_format($total) ?> Satır</h3>
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center pt-2 border-top border-secondary border-opacity-10 small">
                <span class="text-muted">Kayıt:</span>
                <span class="text-dark fw-bold">Toplam Satır</span>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="ra-stat-card ra-stat-warning h-100 d-flex flex-column justify-content-between">
            <div class="d-flex align-items-center mb-2">
                <div class="ra-stat-icon me-3">
                    <i class="ri-translate-2"></i>
                </div>
                <div>
                    <div class="ra-stat-label">COLLATION</div>
                    <h3 class="ra-stat-value fs-6 font-monospace mb-0"><?= htmlspecialchars($table_metadata['Collation'] ?? 'utf8mb4_unicode_ci') ?></h3>
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center pt-2 border-top border-warning border-opacity-10 small">
                <span class="text-muted">Karakter Seti:</span>
                <span class="text-success fw-bold">UTF-8 Standardı</span>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="ra-stat-card ra-stat-success h-100 d-flex flex-column justify-content-between">
            <div class="d-flex align-items-center mb-2">
                <div class="ra-stat-icon me-3">
                    <i class="ri-hard-drive-2-line"></i>
                </div>
                <div>
                    <div class="ra-stat-label">TABLO BOYUTU</div>
                    <h3 class="ra-stat-value fs-5 mb-0"><?= $table_metadata['Formatted_Size'] ?? '0 KB' ?></h3>
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center pt-2 border-top border-success border-opacity-10 small">
                <span class="text-muted">Hızlı İşlem:</span>
                <a href="<?= $Route->url('syshub/dbconsole/tables/structure/' . $table, 'developer') ?>" class="text-success fw-bold text-decoration-none">
                    Yapı <i class="ri-arrow-right-s-line"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- 📑 2. KAYITLAR TABLOSU -->
<?php if (empty($records)): ?>
    <div class="rbn-card p-5 text-center">
        <i class="ri-inbox-line fs-1 text-muted opacity-25 d-block mb-2"></i>
        <h6 class="fw-bold text-dark mb-1">Bu Tabloda Kayıt Bulunmuyor</h6>
        <p class="text-muted small mb-0">Tablo şeması mevcut fakat henüz veri eklenmemiş.</p>
    </div>
<?php else: ?>
    <div class="rbn-table-container">
        <table class="rbn-table w-100" id="browse-table">
            <thead>
                <tr>
                    <?php foreach ($columns as $col): ?>
                        <th><?= htmlspecialchars($col) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($records as $row): ?>
                    <tr>
                        <?php foreach ($columns as $col): 
                            $val = $row[$col] ?? null;
                            $strVal = is_null($val) ? 'NULL' : (string) $val;
                        ?>
                            <td>
                                <?php if (is_null($val)): ?>
                                    <span class="badge bg-light text-muted font-monospace small">NULL</span>
                                <?php else: ?>
                                    <span class="font-monospace text-dark small" style="white-space: nowrap; max-width: 260px; overflow: hidden; text-overflow: ellipsis; display: inline-block;" title="<?= htmlspecialchars($strVal) ?>">
                                        <?= htmlspecialchars(mb_strlen($strVal) > 60 ? mb_substr($strVal, 0, 60) . '…' : $strVal) ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
