<!-- 1. STAT METRİK KARTLARI (Terracotta Craft Mimarisi) 📊 -->
<div class="row g-4 mb-4">
    <?php foreach ($detailedStats as $key => $stat): 
        $color = $stat['color'] ?? 'primary';
        $icon = str_replace(['bi bi-', 'bi-'], 'ri-', $stat['icon'] ?? 'ri-mail-line');
    ?>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="ra-stat-card ra-stat-<?= $color ?> h-100">
                <div class="d-flex align-items-center mb-2">
                    <div class="ra-stat-icon me-3">
                        <i class="<?= $icon ?> fs-4"></i>
                    </div>
                    <div>
                        <div class="ra-stat-label"><?= htmlspecialchars($stat['title']) ?></div>
                        <h3 class="ra-stat-value"><?= number_format((int)$stat['total']) ?></h3>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- 2. ARAMA VE FİLTRE ÇUBUĞU 🔍 -->
<div class="row g-3 mb-4 align-items-center rbn-table-filters">
    <div class="col-md-6">
        <div class="position-relative">
            <input type="text" id="contactFilter" class="rbn-form-input form-control"
                placeholder="Gönderen adı, e-posta veya konu ile ara..." data-rbn-table-search="contact-table">
            <i class="ri-search-line search-icon"></i>
        </div>
    </div>
    <div class="col-md-3">
        <div class="rbn-dropdown dropdown w-100 rbn-filter-dropdown">
            <button class="rbn-btn rbn-btn-outline w-100 justify-content-between dropdown-toggle"
                type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="text-secondary fw-medium" data-sort-label>
                    <i class="ri-sort-desc me-2 opacity-50"></i>Sıralama
                </span>
                <i class="ri-arrow-down-s-line opacity-50"></i>
            </button>
            <ul class="rbn-dropdown-menu dropdown-menu shadow-sm py-2 w-100 mt-2">
                <li><a class="rbn-dropdown-item dropdown-item py-2" href="#" data-rbn-table-sort="contact-table" data-sort-col="3"
                        data-sort-type="string" data-sort-dir="desc"><i class="ri-checkbox-circle-line me-2 opacity-75"></i>Durum</a></li>
                <li><a class="rbn-dropdown-item dropdown-item py-2" href="#" data-rbn-table-sort="contact-table" data-sort-col="2"
                        data-sort-type="date" data-sort-dir="desc"><i class="ri-calendar-check-line me-2 opacity-75"></i>En Yeni Tarih</a></li>
                <li><a class="rbn-dropdown-item dropdown-item py-2" href="#" data-rbn-table-sort="contact-table" data-sort-col="2"
                        data-sort-type="date" data-sort-dir="asc"><i class="ri-history-line me-2 opacity-75"></i>En Eski Tarih</a></li>
            </ul>
        </div>
    </div>
    <div class="col-md-3 text-end">
        <?php if ($currentType === 'trash' && !empty($messages)): ?>
            <button type="button"
                class="rbn-btn rbn-btn-danger w-100 rbn-btn-pill fw-bold action-confirm"
                data-url="<?= $Route->url('contact/empty-trash', 'admin') ?>" data-method="POST" data-title="Çöpü Boşalt?"
                data-text="Çöp kutusundaki tüm mesajlar kalıcı olarak silinecektir.">
                <i class="ri-delete-bin-3-line me-2"></i> Çöpü Boşalt
            </button>
        <?php endif; ?>
    </div>
</div>

<!-- 3. MESAJ LİSTESİ TABLOSU (Tablo Mimarisi) 📩 -->
<div class="rbn-table-wrap mb-4">
    <div class="p-3 border-bottom border-light">
        <ul class="rbn-table-tabs rbn-tab-pills">
            <li class="nav-item">
                <a class="nav-link <?= $currentType === 'unread' ? 'active' : '' ?>"
                    href="<?= $Route->url('contact/unread', 'admin') ?>">
                    <i class="ri-error-warning-line me-1"></i>Bekleyenler
                    <?php if ($stats['unread'] > 0): ?>
                        <span class="tab-count"><?= $stats['unread'] ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentType === 'read' ? 'active' : '' ?>"
                    href="<?= $Route->url('contact/read', 'admin') ?>">
                    <i class="ri-mail-open-line me-1"></i>Okunmuşlar
                    <?php if (($stats['read'] ?? 0) > 0): ?>
                        <span class="tab-count"><?= $stats['read'] ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentType === 'trash' ? 'active' : '' ?>"
                    href="<?= $Route->url('contact/trash', 'admin') ?>">
                    <i class="ri-delete-bin-line me-1"></i>Çöp Kutusu
                    <?php if ($stats['trash'] > 0): ?>
                        <span class="tab-count"><?= $stats['trash'] ?></span>
                    <?php endif; ?>
                </a>
            </li>
        </ul>
    </div>

    <div class="table-responsive">
        <table class="rbn-table rbn-table-hover rbn-table-fixed align-middle mb-0" id="contact-table">
            <thead>
                <tr>
                    <th class="ps-4" style="width: 90px;" data-sort="string"># ID</th>
                    <th data-sort="string">GÖNDEREN / MESAJ</th>
                    <th style="width: 170px;" data-sort="date">TARİH</th>
                    <th class="text-center" style="width: 130px; white-space: nowrap;" data-sort="string">DURUM</th>
                    <th class="text-end pe-4" style="width: 140px; white-space: nowrap;">İŞLEMLER</th>
                </tr>
            </thead>
            <tbody>
                    <?php if (!empty($messages)):
                        foreach ($messages as $i => $msg): ?>
                            <tr class="contact-row" data-id="<?= $msg['id'] ?>" style="--row-index: <?= $i ?>">
                                <td class="ps-4 font-monospace text-muted small">#<?= $msg['id'] ?></td>
                                <td class="ra-traffic-url-cell">
                                    <div class="d-flex align-items-center">
                                        <div class="rbn-avatar rbn-avatar-sm rbn-avatar-primary me-3 flex-shrink-0">
                                            <?= mb_strtoupper(mb_substr($msg['name'] ?? 'M', 0, 1)) ?>
                                        </div>
                                        <div class="overflow-hidden min-w-0 flex-grow-1">
                                            <div class="fw-bold text-dark mb-0 small text-truncate"><?= htmlspecialchars($msg['name']) ?></div>
                                            <div class="text-muted text-truncate d-block x-small" title="<?= htmlspecialchars(($msg['subject'] ?: 'Konu Yok') . ': ' . $msg['message']) ?>">
                                                <strong class="text-dark"><?= htmlspecialchars($msg['subject'] ?: 'Konu Yok') ?>:</strong>
                                                <?= htmlspecialchars($msg['message']) ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="text-muted small">
                                        <i class="ri-calendar-line me-1 text-warning"></i><?= now('d.m.Y', strtotime($msg['created_at'])) ?>
                                        <br>
                                        <i class="ri-time-line me-1 opacity-75"></i><?= now('H:i', strtotime($msg['created_at'])) ?>
                                    </div>
                                </td>
                                <td class="text-center" style="white-space: nowrap;">
                                    <?php if ($msg['is_read'] == 0): ?>
                                        <span class="rbn-badge rbn-badge-primary rbn-badge-sm">
                                            <span class="rbn-status-dot rbn-status-dot-blue me-1"></span> YENİ
                                        </span>
                                    <?php elseif ($msg['is_read'] == 1): ?>
                                        <span class="rbn-badge rbn-badge-secondary rbn-badge-sm">OKUNDU</span>
                                    <?php else: ?>
                                        <span class="rbn-badge rbn-badge-danger rbn-badge-sm">ÇÖP</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-4" style="white-space: nowrap;">
                                    <div class="d-flex justify-content-end gap-1">
                                        <button type="button"
                                            class="rbn-btn rbn-btn-outline rbn-btn-xs rbn-page-refresh"
                                            data-rbn-modal="true" data-type="RbnAdmin/Contact" data-id="<?= $msg['id'] ?>"
                                            data-title="Mesaj Detayı" data-theme="primary"
                                            data-tooltip="Oku">
                                            <i class="ri-eye-line"></i>
                                        </button>

                                        <?php if ($msg['is_read'] == 2): ?>
                                            <button type="button"
                                                class="rbn-btn rbn-btn-outline-success rbn-btn-xs action-confirm"
                                                data-url="<?= $Route->url('contact/restore/' . $msg['id'], 'admin') ?>"
                                                data-method="POST" data-title="Mesaj Kurtarılsın mı?"
                                                data-text="Bu mesaj gelen kutusuna geri taşınacaktır." data-tooltip="Kurtar">
                                                <i class="ri-restart-line"></i>
                                            </button>
                                            <button type="button"
                                                class="rbn-btn rbn-btn-outline-danger rbn-btn-xs action-confirm"
                                                data-url="<?= $Route->url('contact/destroy/' . $msg['id'], 'admin') ?>"
                                                data-method="POST" data-title="Kalıcı Olarak Sil?"
                                                data-text="Bu mesaj sistemden tamamen silinecektir!" data-tooltip="Kalıcı Sil">
                                                <i class="ri-close-circle-line"></i>
                                            </button>
                                        <?php else: ?>
                                            <button type="button"
                                                class="rbn-btn rbn-btn-outline-danger rbn-btn-xs action-confirm"
                                                data-url="<?= $Route->url('contact/delete/' . $msg['id'], 'admin') ?>"
                                                data-method="POST" data-title="Mesaj Silinsin mi?"
                                                data-text="Bu mesaj çöp kutusuna taşınacaktır." data-tooltip="Sile Taşı">
                                                <i class="ri-delete-bin-line"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Search Empty State -->
            <div id="contact-table-empty" class="py-5 text-center bg-white"
                style="display: <?= empty($messages) ? 'block' : 'none' ?>;">
                <div class="py-5">
                    <div class="rbn-stat-icon mx-auto mb-3 bg-secondary-subtle text-secondary" style="width: 56px; height: 56px; font-size: 1.75rem;">
                        <i class="<?= empty($messages) ? 'ri-inbox-line' : 'ri-search-line' ?>"></i>
                    </div>
                    <h5 class="fw-bold"><?= empty($messages) ? 'Mesaj Bulunamadı' : 'Sonuç Bulunamadı' ?></h5>
                    <p class="text-muted small">
                        <?= empty($messages) ? 'Şu an bu kategoride herhangi bir mesaj kaydı bulunmuyor.' : 'Arama kriterlerinize uygun mesaj bulunamadı.' ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

<?php if (!empty($pagination)): ?>
<div class="mt-4">
    <?= $pagination ?>
</div>
<?php endif; ?>
