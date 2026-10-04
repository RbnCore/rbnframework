<!-- Hallmark · component: rbn-admin-navbar · genre: modern-minimal -->
<header class="rbn-dash-navbar rbn-admin-navbar" data-counts-url="<?= $Route->url('notification/counts', 'admin') ?>">
    <div class="rbn-dash-navbar-left">
        <button class="rbn-dash-toggle-btn" onclick="rbnAdminToggleSidebar()" data-tooltip="Menüyü Daralt/Genişlet" data-tooltip-pos="bottom">
            <i class="ri-menu-line"></i>
        </button>

        <div class="rbn-admin-status-group d-none d-md-flex align-items-center ms-3">
            <div class="rbn-admin-status-indicator px-3 py-1">
                <span class="rbn-status-dot rbn-status-dot-emerald rbn-status-pulse me-2"></span>
                <span class="rbn-admin-status-text text-muted small fw-medium">Sistem Aktif</span>
            </div>
            <div class="rbn-admin-time-indicator px-3 py-1 border-start border-end" data-tooltip="Sunucu Saati" data-tooltip-pos="bottom">
                <i class="ri-time-line me-1 text-muted small"></i>
                <span class="text-muted small">
                    <?= now('H:i') ?>
                </span>
            </div>
            <a href="<?= url('/') ?>" target="_blank" class="rbn-admin-site-pill px-3 py-1"
                data-rbn-redirect="true" data-title="Canlı Siteye Gidiyorsunuz..."
                data-message="Canlı Siteye Yönlendiriliyorsunuz"
                data-sub-message="Lütfen bekleyin, canlı site yükleniyor.">
                <i class="ri-global-line me-1"></i>
                <span class="small fw-bold">Canlı Site</span>
                <i class="ri-arrow-right-up-line ms-1 small opacity-50"></i>
            </a>
        </div>
    </div>

    <!-- Center Project Switcher Dropdown -->
    <?php
    $groupProjects = group_projects();
    $activeKey = active_project_key();

    if (count($groupProjects) > 1):
        $activeProjectName = $activeKey;
        foreach ($groupProjects as $proj) {
            if ($proj['project_key'] === $activeKey) {
                $activeProjectName = $proj['project_name'] ?? $activeKey;
                break;
            }
        }
        ?>
        <div class="rbn-admin-navbar-center d-none d-md-block">
            <div class="rbn-dropdown dropdown">
                <button class="rbn-admin-project-switcher-btn dropdown-toggle border-0" type="button"
                    id="projectSwitcherCenterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="ri-briefcase-line text-theme"></i>
                    <span>Aktif Proje : <strong class="ms-1"><?= htmlspecialchars((string) $activeProjectName) ?></strong></span>
                    <i class="ri-arrow-down-s-line switcher-caret-icon ms-1"></i>
                </button>
                <ul class="rbn-dropdown-menu dropdown-menu dropdown-menu-start shadow-lg"
                    aria-labelledby="projectSwitcherCenterDropdown">
                    <li>
                        <h6 class="rbn-dropdown-header dropdown-header">🏢 Proje Değiştir</h6>
                    </li>
                    <li>
                        <hr class="rbn-dropdown-divider dropdown-divider">
                    </li>
                    <?php foreach ($groupProjects as $proj):
                        $projKey = $proj['project_key'];
                        if ((bool) ($Route->resolveProjectData('admin_panel_disabled', $projKey) ?? false)) {
                            continue;
                        }
                        $adminUrl = $Route->url('switch-project/' . $projKey, [], 'admin', 'RbnAdmin');
                        $isCurrent = ($projKey === $activeKey);
                        ?>
                        <li>
                            <a class="rbn-dropdown-item dropdown-item d-flex align-items-center justify-content-between py-2 <?= $isCurrent ? 'active fw-bold' : '' ?>"
                                href="<?= $adminUrl ?>">
                                <span><?= htmlspecialchars((string) ($proj['project_name'] ?? $projKey)) ?></span>
                                <?php if ($isCurrent): ?>
                                    <i class="ri-check-line ms-2 text-primary"></i>
                                <?php endif; ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    <?php endif; ?>

    <div class="rbn-dash-navbar-actions">
        <!-- Notification Dropdown -->
        <div class="rbn-dropdown dropdown">
            <button class="rbn-dash-navbar-btn dropdown-toggle" type="button" id="notificationDropdown" data-bs-toggle="dropdown"
                data-bs-display="static" aria-expanded="false" data-tooltip="Bildirimler" data-tooltip-pos="bottom">
                <i class="ri-notification-3-line fs-5"></i>
                <?php if (($comm->noti['count'] ?? 0) > 0): ?>
                    <span class="rbn-dash-navbar-badge" id="notifications-count">
                        <?= $comm->noti['count'] > 99 ? '99+' : $comm->noti['count'] ?>
                    </span>
                <?php else: ?>
                    <span class="rbn-dash-navbar-badge d-none" id="notifications-count">0</span>
                <?php endif; ?>
            </button>
            <ul class="rbn-dropdown-menu dropdown-menu dropdown-menu-end" aria-labelledby="notificationDropdown">
                <li>
                    <h6 class="rbn-dropdown-header dropdown-header">🔔 Bildirimler</h6>
                </li>
                <?php if (!empty($comm->noti['notifications'] ?? [])): ?>
                    <?php foreach (array_slice($comm->noti['notifications'], 0, 5) as $notification): ?>
                        <li>
                            <a href="javascript:void(0);" class="rbn-dropdown-item dropdown-item rbn-page-refresh"
                                data-rbn-modal="true" data-type="notification" data-id="<?= $notification['id'] ?>"
                                data-title="Bildirim Detayı" data-theme="info"
                                data-endpoint="<?= $Route->url('notification/detail-modal/' . $notification['id'], 'admin') ?>">
                                <div class="notification-icon">
                                    <i class="ri-notification-fill"></i>
                                </div>
                                <div class="notification-content">
                                    <div class="item-title">
                                        <?= htmlspecialchars((string) $notification['title']) ?>
                                    </div>
                                    <p class="item-desc">
                                        <?= htmlspecialchars((string) $notification['message']) ?>
                                        <br>
                                        <small class="text-muted">
                                             <?= $this->helper('format')->getRelativeTime($notification['created_at']) ?>
                                        </small>
                                    </p>
                                </div>
                            </a>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li>
                        <div class="dropdown-item-text empty-state">
                            <i class="ri-notification-off-line"></i>
                            <p>Henüz bildirim yok</p>
                        </div>
                    </li>
                <?php endif; ?>
                <li>
                    <hr class="rbn-dropdown-divider dropdown-divider">
                </li>
                <li>
                    <a class="rbn-dropdown-item dropdown-item view-all" href="<?= $Route->url('notification', 'admin', 'dashboard') ?>">
                        <div class="notification-icon">
                            <i class="ri-eye-line"></i>
                        </div>
                        <div class="notification-content">
                            <div class="item-title">Tümünü Gör</div>
                            <p class="item-desc">Tüm bildirimleri görüntüle</p>
                        </div>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Messages Dropdown -->
        @hasrole(['developer', 'admin'])
        <div class="rbn-dropdown dropdown">
            <button class="rbn-dash-navbar-btn dropdown-toggle" type="button" id="messagesDropdown" data-bs-toggle="dropdown"
                data-bs-display="static" aria-expanded="false" data-tooltip="Mesajlar" data-tooltip-pos="bottom">
                <i class="ri-mail-line fs-5"></i>
                <?php if (($comm->msg['count'] ?? 0) > 0): ?>
                    <span class="rbn-dash-navbar-badge bg-danger" id="messages-count">
                        <?= ($comm->msg['count'] ?? 0) > 99 ? '99+' : ($comm->msg['count'] ?? 0) ?>
                    </span>
                <?php else: ?>
                    <span class="rbn-dash-navbar-badge bg-danger d-none" id="messages-count">0</span>
                <?php endif; ?>
            </button>
            <ul class="rbn-dropdown-menu dropdown-menu dropdown-menu-end" aria-labelledby="messagesDropdown">
                <li>
                    <h6 class="rbn-dropdown-header dropdown-header">✉️ Mesajlar</h6>
                </li>
                <li>
                    <hr class="rbn-dropdown-divider dropdown-divider">
                </li>

                <?php if (!empty($comm->msg['messages'] ?? [])): ?>
                    <?php foreach (array_slice($comm->msg['messages'], 0, 5) as $message): ?>
                        <li>
                            <a href="javascript:void(0);" class="rbn-dropdown-item dropdown-item rbn-page-refresh"
                                data-rbn-modal="true" data-type="contact" data-id="<?= $message['id'] ?>"
                                data-title="Mesaj Detayı" data-theme="primary"
                                data-endpoint="<?= $Route->url('contact/detail-modal/' . $message['id'], 'admin') ?>">
                                <div class="notification-icon">
                                    <i class="ri-user-fill"></i>
                                </div>
                                <div class="notification-content">
                                    <div class="item-title">
                                        <?= htmlspecialchars((string) $message['name']) ?>
                                    </div>
                                    <p class="item-desc">
                                        <?php if (!empty($message['subject'])): ?>
                                            <strong><?= $this->helper('text')->cutText($message['subject'], 30) ?>:</strong>
                                        <?php endif; ?>
                                    <p class="mb-0 text-muted small">
                                        <?= $this->helper('text')->cutText($message['message'], 45) ?>
                                        <br>
                                        <small class="text-muted">
                                            <i class="ri-time-line me-1"></i>
                                            <?= $this->helper('format')->getRelativeTime($message['created_at']) ?>
                                        </small>
                                    </p>
                                </div>
                            </a>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li>
                        <div class="dropdown-item-text empty-state">
                            <i class="ri-inbox-line"></i>
                            <p>Henüz mesaj yok</p>
                        </div>
                    </li>
                <?php endif; ?>

                <li>
                    <hr class="rbn-dropdown-divider dropdown-divider">
                </li>
                <li>
                    <a class="rbn-dropdown-item dropdown-item view-all" href="<?= $Route->url('contact', 'admin', 'dashboard') ?>">
                        <div class="notification-icon">
                            <i class="ri-eye-line"></i>
                        </div>
                        <div class="notification-content">
                            <div class="item-title">Tümünü Gör</div>
                            <p class="item-desc">Tüm mesajları görüntüle</p>
                        </div>
                    </a>
                </li>
            </ul>
        </div>
        @endhasrole

        <!-- User Profile Dropdown -->
        <div class="rbn-dropdown dropdown d-inline-block">
            <button class="rbn-admin-user-profile dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown"
                data-bs-display="static" aria-expanded="false">
                <div class="rbn-admin-user-avatar">
                    <i class="ri-user-fill"></i>
                </div>
                <div class="d-none d-sm-flex align-items-center">
                    <span class="rbn-admin-avatar-username ms-1">@<?= htmlspecialchars($user['username'] ?? 'user') ?></span>
                </div>
            </button>
            <ul class="rbn-dropdown-menu dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                <li>
                    <h6 class="rbn-dropdown-header dropdown-header">
                        👋 Hoşgeldin,
                        <?= htmlspecialchars($user['name'] ?? 'Kullanıcı') ?>!
                    </h6>
                </li>
                <li>
                    <div class="dropdown-item-text py-2">
                        <div class="d-flex flex-column gap-2">
                            <div class="d-flex align-items-center text-muted small">
                                <span class="me-2">📧</span>
                                <span>
                                    <?= htmlspecialchars($user['email'] ?? '') ?>
                                </span>
                            </div>
                            <div class="d-flex align-items-center text-muted small">
                                <span class="me-2">🎭</span>
                                <span>Rol: <span class="fw-bold text-dark">
                                        <?= ucfirst($user['role'] ?? 'user') ?>
                                    </span></span>
                            </div>
                            <div class="d-flex align-items-center text-muted small">
                                <span class="me-2">🕒</span>
                                <span>Son Giriş: <span class="fw-bold text-dark">
                                        <?= isset($user['last_login_at']) ? date('d.m.Y H:i', strtotime($user['last_login_at'])) : 'Bilinmiyor' ?>
                                    </span></span>
                            </div>
                        </div>
                    </div>
                </li>
                <li>
                    <hr class="rbn-dropdown-divider dropdown-divider">
                </li>
                <li>
                    <?php
                    $profilePanel = in_array(($user['role'] ?? ''), ['admin', 'developer', 'moderator']) ? 'admin' : 'user';
                    ?>
                    <a class="rbn-dropdown-item dropdown-item" href="<?= $Route->url('users/profile', $profilePanel, 'dashboard') ?>">
                        <i class="ri-user-settings-line fs-5 text-theme"></i>
                        <div>
                            <div class="item-title mb-0">Profilim</div>
                            <p class="item-desc mb-0">Profil bilgilerini görüntüle ve düzenle</p>
                        </div>
                    </a>
                </li>
                <li>
                    <hr class="rbn-dropdown-divider dropdown-divider">
                </li>
                <li>
                    <a class="rbn-dropdown-item dropdown-item text-danger" href="<?= $Route->url('logout') ?>">
                        <i class="ri-logout-box-r-line fs-5 text-theme"></i>
                        <div>
                            <div class="item-title mb-0">Güvenli Çıkış Yap</div>
                            <p class="item-desc mb-0">Oturumu güvenli şekilde sonlandır</p>
                        </div>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Admin & Developer Settings -->
        @hasrole(['developer', 'admin'])
        <div class="rbn-dropdown dropdown d-inline-block">
            <button class="rbn-dash-navbar-btn dropdown-toggle" type="button" id="adminSettingsDropdown"
                data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false" data-tooltip="Sistem Ayarları" data-tooltip-pos="bottom">
                <i class="ri-settings-3-line fs-5"></i>
            </button>
            <ul class="rbn-dropdown-menu dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-4 py-3"
                aria-labelledby="adminSettingsDropdown">
                <li>
                    <h6 class="rbn-dropdown-header dropdown-header">⚙️ Admin Ayarları</h6>
                </li>
                <li>
                    <a class="rbn-dropdown-item dropdown-item" href="<?= $Route->url('settings', 'admin', 'dashboard') ?>">
                        <i class="ri-settings-4-line fs-5 text-primary"></i>
                        <div>
                            <div class="item-title mb-0">Genel Ayarlar</div>
                            <p class="item-desc mb-0">Sistem ve iletişim ayarları</p>
                        </div>
                    </a>
                </li>
                <li>
                    <a class="rbn-dropdown-item dropdown-item" href="<?= $Route->url('users', 'admin', 'dashboard') ?>">
                        <i class="ri-shield-user-line fs-5 text-theme"></i>
                        <div>
                            <div class="item-title mb-0">Kullanıcı Yönetimi</div>
                            <p class="item-desc mb-0">Rol ve yetki yönetimi</p>
                        </div>
                    </a>
                </li>
                <li>
                    <a class="rbn-dropdown-item dropdown-item" href="<?= $Route->url('users/activities', 'admin', '') ?>">
                        <i class="ri-history-line fs-5 text-theme"></i>
                        <div>
                            <div class="item-title mb-0">Kullanıcı Hareketleri</div>
                            <p class="item-desc mb-0">Sistem üzerindeki kullanıcı aktiviteleri</p>
                        </div>
                    </a>
                </li>
            </ul>
        </div>
        @endhasrole
    </div>
</header>