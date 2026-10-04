<!-- Hallmark · component: rbn-profile · genre: terracotta-editorial · theme: rbn-theme -->
<div class="container-fluid px-0">
    
    <!-- 1. TOP STATS METRICS (Terracotta ra-stat-* Standardı) 📊 -->
    <div class="row g-3 mb-4">
        <!-- Kayıt Tarihi -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="ra-stat-card ra-stat-navy h-100 p-3">
                <div class="d-flex align-items-center mb-2">
                    <div class="ra-stat-icon me-3">
                        <i class="ri-calendar-check-line"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="ra-stat-label">Kayıt Tarihi</div>
                        <div class="fw-bold text-dark small text-truncate mt-1">
                            <?= $this->helper('format')->formatDateTurkish($profile['created_at'] ?? now()) ?>
                        </div>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center border-top small text-muted">
                    <span><i class="ri-history-line me-1 text-warning"></i>Hesap Yaşı</span>
                    <strong class="text-dark">Aktif Üye</strong>
                </div>
            </div>
        </div>

        <!-- Son Güncelleme -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="ra-stat-card ra-stat-warning h-100 p-3">
                <div class="d-flex align-items-center mb-2">
                    <div class="ra-stat-icon me-3">
                        <i class="ri-refresh-line"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="ra-stat-label">Son Güncelleme</div>
                        <div class="fw-bold text-dark small text-truncate mt-1">
                            <?= $this->helper('format')->formatDateTurkish($profile['updated_at'] ?? now()) ?>
                        </div>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center border-top small text-muted">
                    <span><i class="ri-edit-line me-1 text-warning"></i>Durum</span>
                    <strong class="text-dark">Güncel</strong>
                </div>
            </div>
        </div>

        <!-- Yetki Seviyesi -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="ra-stat-card ra-stat-terracotta h-100 p-3">
                <div class="d-flex align-items-center mb-2">
                    <div class="ra-stat-icon me-3">
                        <i class="ri-shield-check-line"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="ra-stat-label">Yetki Seviyesi</div>
                        <div class="fw-bold text-dark small text-uppercase text-truncate mt-1">
                            <?= htmlspecialchars((string)($profile['role'] ?? 'user')) ?>
                        </div>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center border-top small text-muted">
                    <span><i class="ri-key-line me-1 text-danger"></i>Rol</span>
                    <strong class="text-dark">Yönetici</strong>
                </div>
            </div>
        </div>

        <!-- Şifre Güvenlik Skoru -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="ra-stat-card ra-stat-success h-100 p-3">
                <div class="d-flex align-items-center mb-2">
                    <div class="ra-stat-icon me-3">
                        <i class="ri-lock-password-line"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="ra-stat-label">Şifre Skoru</div>
                        <div class="fw-bold text-success small mt-1">
                            <?= (int)($profile['password_score'] ?? 0) ?> / 100
                        </div>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center border-top small text-muted">
                    <span><i class="ri-shield-flash-line me-1 text-success"></i>Güvenlik</span>
                    <strong class="text-success fw-bold">Yüksek</strong>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. FORMS & USER IDENTITY (Kişisel Bilgiler & Güvenlik) ✍️ -->
    <div class="row g-4">
        <!-- Sol: Kullanıcı Profili ve Kişisel Bilgiler -->
        <div class="col-12 col-lg-6">
            <div class="card border-0 h-100 shadow-sm p-4">
                
                <!-- Kompakt Kullanıcı Kimlik Başlığı (Eşit Hiza) -->
                <div class="d-flex align-items-center justify-content-between mb-4 border-bottom border-light ra-card-header-equal">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rbn-avatar rbn-avatar-lg rbn-avatar-terracotta shadow-sm flex-shrink-0 fw-bold fs-5">
                            <?= $this->helper('text')->getInitials($profile['name'] ?? 'K') ?>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-0 text-dark">
                                <?= htmlspecialchars((string) ($profile['name'] ?? 'Kullanıcı')) ?>
                            </h5>
                            <small class="text-muted d-flex align-items-center gap-1 mt-1">
                                <i class="ri-mail-line text-warning"></i> <?= htmlspecialchars((string) ($profile['email'] ?? '')) ?>
                            </small>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        <span class="badge ra-badge-domain py-1 px-2">
                            #ID: <?= (int)($profile['id'] ?? 0) ?>
                        </span>
                        <?php $isActive = ($profile['status'] ?? 'active') === 'active'; ?>
                        <span class="badge ra-badge-status py-1 px-2">
                            <span class="ra-status-dot"></span>
                            <?= $isActive ? 'Aktif' : 'Pasif' ?>
                        </span>
                    </div>
                </div>

                <form action="<?= $Route->url('admin/profile/update', 'admin') ?>" method="POST" id="profileForm" autocomplete="off">
                    <!-- FW-BASE-3 (BULGU-2): `z_users` tablosunda TEK `name`
                         kolonu vardir; `firstname`/`lastname`/`phone_number`
                         kolonlari YOK (eski form "Unknown column" -> 500). -->
                    <div class="mb-3">
                        <label class="form-label text-uppercase fw-bold text-muted small">Ad Soyad</label>
                        <input type="text" class="form-control rbn-form-input" name="name"
                            value="<?= htmlspecialchars((string) ($profile['name'] ?? '')) ?>" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label text-uppercase fw-bold text-muted small">E-Posta Adresi</label>
                        <input type="email" class="form-control rbn-form-input" name="email"
                            value="<?= htmlspecialchars((string) ($profile['email'] ?? '')) ?>" required>
                    </div>

                    <div class="pt-2 mt-auto">
                        @csrf
                        <button type="submit" class="btn rbn-btn-terracotta w-100 py-2">
                            <i class="ri-check-line me-2"></i> Bilgileri Güncelle
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Sağ: Güvenlik & Şifre -->
        <div class="col-12 col-lg-6">
            <div class="card border-0 h-100 shadow-sm p-4">
                <div class="d-flex align-items-center justify-content-between mb-4 border-bottom border-light ra-card-header-equal">
                    <div class="d-flex align-items-center gap-3">
                        <div class="ra-stat-icon flex-shrink-0 ra-avatar-profile">
                            <i class="ri-shield-keyhole-line"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-0 text-dark">Güvenlik & Şifre</h5>
                            <small class="text-muted d-flex align-items-center gap-1 mt-1">
                                Hesap şifrenizi güvenli bir şekilde yenileyin.
                            </small>
                        </div>
                    </div>
                </div>

                <div class="alert rbn-alert d-flex align-items-center gap-3 mb-4 p-3 ra-security-tip-alert">
                    <i class="ri-shield-check-fill fs-5 text-warning flex-shrink-0"></i>
                    <div class="small text-dark">
                        <strong>Güvenlik İpucu:</strong> En az 8 karakter, büyük/küçük harf ve rakam içermelidir.
                    </div>
                </div>

                <form action="<?= $Route->url('admin/profile/change-password', 'admin') ?>" method="POST" id="passwordForm" autocomplete="off">
                    <div class="mb-3">
                        <label class="form-label text-uppercase fw-bold text-muted small">Yeni Şifre</label>
                        <div class="d-flex align-items-center gap-2">
                            <input type="password" class="form-control rbn-form-input flex-grow-1" id="new_password" name="new_password" placeholder="En az 8 karakter" required>
                            <button type="button" class="ra-pass-toggle-btn" data-rbn-password-toggle="new_password" data-tooltip="Şifreyi Göster/Gizle" data-tooltip-pos="left">
                                <i class="ri-eye-line"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label text-uppercase fw-bold text-muted small">Yeni Şifre (Tekrar)</label>
                        <div class="d-flex align-items-center gap-2">
                            <input type="password" class="form-control rbn-form-input flex-grow-1" id="repeat_password" name="repeat_password" placeholder="Şifrenizi tekrar yazın" required>
                            <button type="button" class="ra-pass-toggle-btn" data-rbn-password-toggle="repeat_password" data-tooltip="Şifreyi Göster/Gizle" data-tooltip-pos="left">
                                <i class="ri-eye-line"></i>
                            </button>
                        </div>
                    </div>

                    <div class="pt-2 mt-auto">
                        @csrf
                        <button type="submit" class="btn rbn-btn-secondary w-100 py-2">
                            <i class="ri-lock-2-line me-2"></i> Şifreyi Kaydet
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>
