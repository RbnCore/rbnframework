<?php
/**
 * RbnMail / Hostmailhub Management View 🏰📧
 * Terracotta Craft Sovereign Standard.
 */
?>

<div class="rbn-mail-container mb-5">
    <!-- 1. STATS GRID (Terracotta Stat Cards) 📊 -->
    <div class="row g-4 mb-4 position-relative" style="z-index: 1050;">
        <!-- Toplam Hesap -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="ra-stat-card ra-stat-terracotta h-100">
                <div class="d-flex align-items-center mb-3">
                    <div class="ra-stat-icon me-3">
                        <i class="ri-mail-star-line"></i>
                    </div>
                    <div>
                        <div class="ra-stat-label">TOPLAM HESAP</div>
                        <h3 class="ra-stat-value"><?= count($accounts) ?></h3>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center pt-2 border-top border-secondary border-opacity-10 small">
                    <span class="text-muted">Kayıtlı Posta Kutusu</span>
                    <strong class="text-dark"><?= count($accounts) ?> Aktif</strong>
                </div>
            </div>
        </div>

        <!-- Sunucu Durumu -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="ra-stat-card ra-stat-success h-100">
                <div class="d-flex align-items-center mb-3">
                    <div class="ra-stat-icon me-3">
                        <i class="ri-server-line"></i>
                    </div>
                    <div>
                        <div class="ra-stat-label">SUNUCU DURUMU</div>
                        <h3 class="ra-stat-value text-success">Çevrimiçi</h3>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center pt-2 border-top border-success border-opacity-10 small">
                    <span class="text-muted">cPanel / Mail Daemon</span>
                    <strong class="text-success fw-bold"><i class="ri-checkbox-circle-fill me-1"></i>Senkronize</strong>
                </div>
            </div>
        </div>

        <!-- Varsayılan Kota -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="ra-stat-card ra-stat-navy h-100">
                <div class="d-flex align-items-center mb-3">
                    <div class="ra-stat-icon me-3">
                        <i class="ri-hard-drive-3-line"></i>
                    </div>
                    <div>
                        <div class="ra-stat-label">VARSAYILAN KOTA</div>
                        <h3 class="ra-stat-value"><?= $default_quota ?> <span class="fs-6 text-muted fw-normal">MB</span></h3>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center pt-2 border-top border-primary border-opacity-10 small">
                    <span class="text-muted">Standart Limit</span>
                    <strong class="text-dark">Kutu Başına</strong>
                </div>
            </div>
        </div>

        <!-- Aktif Proje Seçimi (Terracotta Craft Custom Dropdown) -->
        <div class="col-12 col-sm-6 col-xl-3 position-relative" style="z-index: 10;">
            <div class="rbn-card p-4 h-100 d-flex flex-column justify-content-center overflow-visible">
                <div class="ra-stat-label mb-2 d-flex align-items-center gap-1">
                    <i class="ri-projector-2-line text-warning"></i> AKTİF PROJE SEÇİMİ
                </div>
                <?php
                $activeProjObj = null;
                foreach ($projects as $p) {
                    if ($p['project_key'] === $projectKey) {
                        $activeProjObj = $p;
                        break;
                    }
                }
                $activeProjName = $activeProjObj['project_name'] ?? $projectKey;
                $activeProjDomain = $activeProjObj['domain'] ?? '';
                ?>
                <div class="rbn-dropdown dropdown position-relative">
                    <button class="rbn-btn rbn-btn-outline w-100 py-2 px-3 text-start d-flex align-items-center justify-content-between dropdown-toggle font-monospace small"
                        type="button" id="hostmailProjectDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="text-truncate me-2 fw-medium text-dark">
                            <?= htmlspecialchars($activeProjName) ?>
                            <?php if (!empty($activeProjDomain)): ?>
                                <span class="text-muted">(<?= htmlspecialchars($activeProjDomain) ?>)</span>
                            <?php endif; ?>
                        </span>
                        <i class="ri-arrow-down-s-line text-muted"></i>
                    </button>
                    <ul class="rbn-dropdown-menu dropdown-menu dropdown-menu-end shadow-lg w-100 py-2"
                        aria-labelledby="hostmailProjectDropdown">
                        <li>
                            <h6 class="rbn-dropdown-header dropdown-header">🏢 Proje Seçimi</h6>
                        </li>
                        <li>
                            <hr class="rbn-dropdown-divider dropdown-divider">
                        </li>
                        <?php foreach ($projects as $proj): 
                            $isCur = ($proj['project_key'] === $projectKey);
                        ?>
                            <li>
                                <a class="rbn-dropdown-item dropdown-item d-flex align-items-center justify-content-between py-2 <?= $isCur ? 'active fw-bold' : '' ?>"
                                    href="?project=<?= urlencode($proj['project_key']) ?>">
                                    <div class="text-truncate me-2">
                                        <div class="small fw-bold"><?= htmlspecialchars($proj['project_name']) ?></div>
                                        <?php if (!empty($proj['domain'])): ?>
                                            <div class="x-small text-muted font-monospace"><?= htmlspecialchars($proj['domain']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($isCur): ?>
                                        <i class="ri-check-line ms-2 text-primary"></i>
                                    <?php endif; ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. TABLE FILTERS & ACTIONS (Terracotta Header) 🔍 -->
    <div class="d-flex align-items-center gap-3 mb-4 flex-wrap">
        <div class="position-relative flex-grow-1" style="min-width: 250px;">
            <input type="text" class="form-control" placeholder="E-posta hesaplarında ara..."
                data-rbn-table-search="mail-accounts-table">
            <i class="ri-search-line position-absolute top-50 end-0 translate-middle-y me-3 text-muted"></i>
        </div>
        <button class="rbn-btn rbn-btn-primary px-4 py-2 fw-bold d-flex align-items-center gap-1" data-rbn-modal="true" data-type="hostmailhub"
            data-endpoint="<?= $Route->url('hostmailhub/modal', 'admin') ?>"
            data-view="modal" data-title="Yeni E-Posta Hesabı Oluştur" data-size="md" data-theme="primary">
            <i class="ri-add-line fs-5"></i> Yeni E-Posta Hesabı
        </button>
    </div>

    <!-- 3. MAIL LIST TABLE (Terracotta Table Standard) 📋 -->
    <div class="rbn-table-wrap mb-5">
        <div class="ra-traffic-card-header">
            <h6 class="ra-traffic-card-title mb-0">
                <i class="ri-mail-star-line text-warning fs-5"></i> E-Posta Hesapları
            </h6>
            <div class="rbn-badge rbn-badge-terracotta px-3 py-1 font-monospace fw-bold">
                <?= count($accounts) ?> Kayıtlı Hesap
            </div>
        </div>
        <div class="table-responsive">
            <table class="rbn-table rbn-table-hover align-middle mb-0" id="mail-accounts-table">
                <thead>
                    <tr>
                        <th class="ps-4" data-sort="string">Hesap Bilgileri</th>
                        <th data-sort="size" style="width: 280px;">Kota Kullanımı</th>
                        <th class="text-center" style="width: 120px;">Durum</th>
                        <th class="pe-4 text-end" style="width: 160px;">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($accounts as $acc): ?>
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="ra-stat-icon" style="width: 36px; height: 36px; font-size: 1.1rem; border-radius: 8px;">
                                        <i class="ri-mail-line"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark mb-0 font-monospace"><?= $acc['email'] ?></div>
                                        <div class="small text-muted font-monospace opacity-75"><?= $acc['login'] ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php
                                $quota = (int) ($acc['quota'] ?? ($acc['diskquota'] ?? 0));
                                $diskused = (float) ($acc['diskused'] ?? 0);
                                $percent = $quota > 0 ? round(($diskused / $quota) * 100, 1) : 0;
                                $barBg = $percent > 80 ? '#ef4444' : ($percent > 50 ? '#d97706' : '#c56a3c');
                                ?>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="progress flex-grow-1" style="height: 6px; background-color: var(--rbn-admin-border-subtle, #e5ded3); border-radius: 999px;">
                                        <div class="progress-bar" role="progressbar"
                                            style="width: <?= $percent ?>%; background-color: <?= $barBg ?>; border-radius: 999px;"></div>
                                    </div>
                                    <span class="small font-monospace fw-medium text-dark text-nowrap">
                                        <?= $diskused ?> / <?= $quota == 0 ? '∞' : $quota ?> MB
                                    </span>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="rbn-badge rbn-badge-success rbn-badge-xs">
                                    <span class="ra-status-dot me-1 bg-success"></span> Aktif
                                </span>
                            </td>
                            <td class="pe-4 text-end">
                                <div class="d-flex align-items-center justify-content-end gap-1">
                                    <a href="<?= $Route->url('hostmailhub/eternalLink', 'admin') ?>?email=<?= urlencode($acc['email']) ?>"
                                        target="_blank" data-rbn-redirect="true" data-title="Webmail Yönlendiriliyor..."
                                        data-message="Webmail Hesabınıza Yönlendiriliyorsunuz"
                                        data-sub-message="Lütfen bekleyin, gelen kutunuz güvenli bir şekilde yükleniyor..."
                                        data-ajax-param="get_session=1" class="rbn-btn rbn-btn-outline btn-sm px-2 py-1"
                                        data-tooltip="Webmail'e Git (SSO)">
                                        <i class="ri-external-link-line"></i>
                                    </a>
                                    <button type="button" class="rbn-btn rbn-btn-outline btn-sm px-2 py-1" data-rbn-modal="true" data-type="hostmailhub"
                                        data-endpoint="<?= $Route->url('hostmailhub/modal', 'admin') ?>"
                                        data-view="change_password" data-id="<?= $acc['email'] ?>"
                                        data-title="Şifre Değiştir" data-size="md" data-theme="primary"
                                        data-tooltip="Şifre Değiştir">
                                        <i class="ri-lock-password-line text-warning"></i>
                                    </button>
                                    <button type="button" class="rbn-btn rbn-btn-outline btn-sm px-2 py-1 action-confirm"
                                        data-rbn-type="delete"
                                        data-url="<?= $Route->url('hostmailhub/delete/' . urlencode($acc['user']), 'admin') ?>?domain=<?= urlencode($acc['domain']) ?>"
                                        data-method="POST" data-ajax="true" data-title="E-Posta Hesabını Sil?"
                                        data-text="Bu e-posta hesabını (<?= htmlspecialchars($acc['email']) ?>) tamamen silmek istediğinize emin misiniz? Bu işlem geri alınamaz!"
                                        data-tooltip="Hesabı Sil">
                                        <i class="ri-delete-bin-line text-danger"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Empty State Container -->
        <div id="mail-accounts-table-empty" class="text-center py-5"
            style="<?= empty($accounts) ? '' : 'display: none;' ?>">
            <div class="text-muted">
                <i class="ri-mail-forbid-line fs-1 mb-3 d-block text-warning opacity-50"></i>
                <p class="mb-0 fw-bold text-dark">Henüz bir e-posta hesabı bulunmuyor veya cPanel bağlantısı yapılamadı.</p>
                <small class="text-muted">Yeni bir hesap oluşturarak başlayabilirsiniz.</small>
            </div>
        </div>
    </div>
</div>