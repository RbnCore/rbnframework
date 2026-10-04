<!-- 1. STAT METRİK KARTLARI (Terracotta Craft Mimarisi) 📊 -->
<div class="row g-4 mb-4">
    <?php foreach ($detailedStats as $key => $stat): 
        $color = $stat['color'] ?? 'primary';
        $icon = str_replace(['bi bi-', 'bi-'], 'ri-', $stat['icon'] ?? 'ri-notification-3-line');
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

<!-- 2. ARAMA, FİLTRE VE AKSİYON ÇUBUĞU 🔍 -->
<div class="row g-3 mb-4 align-items-center rbn-table-filters">
    <div class="col-md-6">
        <div class="position-relative">
            <input type="text" id="notiFilter" class="rbn-form-input form-control"
                placeholder="Bildirim içeriği, başlık veya ID ile ara..." data-rbn-table-search="noti-table">
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
                <li><a class="rbn-dropdown-item dropdown-item py-2" href="#" data-rbn-table-sort="noti-table" data-sort-col="3"
                        data-sort-type="string" data-sort-dir="desc"><i class="ri-checkbox-circle-line me-2 opacity-75"></i>Durum (Yeni Önce)</a></li>
                <li><a class="rbn-dropdown-item dropdown-item py-2" href="#" data-rbn-table-sort="noti-table" data-sort-col="2"
                        data-sort-type="date" data-sort-dir="desc"><i class="ri-calendar-check-line me-2 opacity-75"></i>En Yeni Tarih</a></li>
                <li><a class="rbn-dropdown-item dropdown-item py-2" href="#" data-rbn-table-sort="noti-table" data-sort-col="2"
                        data-sort-type="date" data-sort-dir="asc"><i class="ri-history-line me-2 opacity-75"></i>En Eski Tarih</a></li>
                <li>
                    <hr class="rbn-dropdown-divider dropdown-divider my-1">
                </li>
                <li><a class="rbn-dropdown-item dropdown-item py-2" href="#" data-rbn-table-sort="noti-table" data-sort-col="0"
                        data-sort-type="string" data-sort-dir="asc"><i class="ri-hashtag me-2 opacity-75"></i>ID (A-Z)</a></li>
            </ul>
        </div>
    </div>
    <div class="col-md-3 text-end">
        <button type="button"
            class="rbn-btn rbn-btn-danger w-100 rbn-btn-pill fw-bold action-confirm"
            data-url="<?= $Route->url('notification/clear', 'admin') ?>"
            data-method="POST"
            data-title="Okunanları Temizle?"
            data-text="Okunmuş tüm bildirimler kalıcı olarak silinecektir."
            id="clearAllReadBtn">
            <i class="ri-delete-bin-3-line me-2"></i> Okunanları Sıfırla
        </button>
    </div>
</div>

<!-- 3. BİLDİRİM TABLOSU (Sovereign Table Mimarisi) 🔔 -->
<div class="rbn-table-wrap mb-4">
    <div class="table-responsive">
        <table class="rbn-table rbn-table-hover rbn-table-fixed align-middle mb-0" id="noti-table">
            <thead>
                <tr>
                    <th class="ps-4" style="width: 90px;" data-sort="string"># ID</th>
                    <th data-sort="string">BİLDİRİM DETAYI</th>
                    <th style="width: 170px;" data-sort="date">TARİH</th>
                    <th class="text-center" style="width: 130px; white-space: nowrap;" data-sort="string">DURUM</th>
                    <th class="text-end pe-4" style="width: 130px; white-space: nowrap;">İŞLEMLER</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($notifications)):
                    foreach ($notifications as $i => $noti): ?>
                        <tr class="noti-row" data-id="<?= $noti['id'] ?>" style="--row-index: <?= $i ?>">
                            <td class="ps-4 font-monospace text-muted small">#<?= $noti['id'] ?></td>
                            <td class="ra-traffic-url-cell">
                                <div class="d-flex align-items-center">
                                    <div class="rbn-avatar rbn-avatar-sm rbn-avatar-primary me-3 flex-shrink-0">
                                        <i class="<?= str_replace(['bi bi-', 'bi-'], 'ri-', $noti['icon'] ?? 'ri-notification-line') ?>"></i>
                                    </div>
                                    <div class="overflow-hidden min-w-0 flex-grow-1">
                                        <div class="fw-bold text-dark mb-0 small text-truncate"><?= htmlspecialchars($noti['title']) ?></div>
                                        <div class="text-muted text-truncate d-block x-small" title="<?= htmlspecialchars($noti['message']) ?>">
                                            <?= htmlspecialchars($noti['message']) ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="text-muted small">
                                    <i class="ri-calendar-line me-1 text-warning"></i><?= now('d.m.Y', strtotime($noti['created_at'])) ?>
                                    <br>
                                    <i class="ri-time-line me-1 opacity-75"></i><?= now('H:i:s', strtotime($noti['created_at'])) ?>
                                </div>
                            </td>
                            <td class="text-center" style="white-space: nowrap;">
                                <?php if (!$noti['is_read']): ?>
                                    <span class="rbn-badge rbn-badge-primary rbn-badge-sm">
                                        <span class="rbn-status-dot rbn-status-dot-blue me-1"></span> YENİ
                                    </span>
                                <?php else: ?>
                                    <span class="rbn-badge rbn-badge-secondary rbn-badge-sm">OKUNDU</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-4" style="white-space: nowrap;">
                                <div class="d-flex justify-content-end gap-1">
                                    <button type="button"
                                        class="rbn-btn rbn-btn-outline rbn-btn-xs notification-modal-btn rbn-page-refresh"
                                        data-rbn-modal="true" data-type="RbnAdmin/Notification" data-id="<?= $noti['id'] ?>"
                                        data-title="Bildirim Detayı" data-theme="primary"
                                        data-tooltip="Görüntüle">
                                        <i class="ri-eye-line"></i>
                                    </button>
                                    <button type="button"
                                        class="rbn-btn rbn-btn-outline-danger rbn-btn-xs action-confirm"
                                        data-url="<?= $Route->url('notification/delete/' . $noti['id'], 'admin') ?>"
                                        data-method="POST" data-title="Bildirim Silinsin mi?"
                                        data-text="Bu bildirim sistemden kalıcı olarak temizlenecektir."
                                        data-tooltip="Sil">
                                        <i class="ri-delete-bin-line"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach;
                endif; ?>
            </tbody>
        </table>

        <!-- Search Empty State -->
        <div id="noti-table-empty" class="py-5 text-center bg-white"
            style="display: <?= empty($notifications) ? 'block' : 'none' ?>;">
            <div class="py-5">
                <div class="rbn-stat-icon mx-auto mb-3 bg-secondary-subtle text-secondary" style="width: 56px; height: 56px; font-size: 1.75rem;">
                    <i class="<?= empty($notifications) ? 'ri-inbox-line' : 'ri-search-line' ?>"></i>
                </div>
                <h5 class="fw-bold"><?= empty($notifications) ? 'Bildirim Bulunamadı' : 'Sonuç Bulunamadı' ?></h5>
                <p class="text-muted small">
                    <?= empty($notifications) ? 'Şu an sistemde aktif bir bildirim kaydı bulunmuyor.' : 'Arama kriterlerinize uygun bildirim bulunamadı.' ?>
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
