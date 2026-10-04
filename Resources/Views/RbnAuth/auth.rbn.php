<!-- Top Floating Brand Pill -->
<a href="/" class="d-inline-flex align-items-center gap-2 rbn-auth-brand-pill text-decoration-none">
    <div class="rbn-auth-brand-icon">
        <?php if (!empty($siteLogo)): ?>
            <img src="<?= $siteLogo ?>" alt="<?= $siteName ?>" style="height: 18px;">
        <?php else: ?>
            <i class="ri-shield-keyhole-fill fs-6"></i>
        <?php endif; ?>
    </div>
    <span class="fw-bold fs-sm text-white"><?= $siteName ?></span>
    <span class="rbn-auth-brand-badge">v<?= $siteVersion ?? '1.0' ?></span>
</a>

<!-- Elevated Studio Stage Card -->
<div class="rbn-auth-stage-card <?= ($view === 'lockscreen') ? 'text-center' : '' ?>">

<?php switch ($view): 

    // =========================================================================
    // 1. REGISTER (Kayıt Ol)
    // =========================================================================
    case 'register': ?>
        <!-- Segmented Navigation -->
        <div class="d-grid rbn-auth-nav-pill" style="grid-template-columns: 1fr 1fr; gap: 4px;">
            <a href="<?= $Route->url('login') ?>" class="text-center rbn-auth-nav-link">Giriş Yap</a>
            <a href="<?= $Route->url('register') ?>" class="text-center rbn-auth-nav-link active">Kayıt Ol</a>
        </div>

        <div class="text-center mb-4">
            <h1 class="fs-2 rbn-auth-title">Hesap Oluştur</h1>
            <p class="fs-sm text-muted mt-2 mb-0">Yeni hesabınızı saniyeler içinde oluşturun.</p>
        </div>

        <!-- Social Register -->
        <button type="button" class="rbn-btn w-100 rbn-auth-social d-flex align-items-center justify-content-center gap-2" onclick="if(window.rbnAlert) rbnAlert.info('Sosyal Kayıt', 'Google ile kayıt özelliği yakında aktif edilecektir.'); else alert('Yakında...');">
            <img src="https://fonts.gstatic.com/s/i/productlogos/googleg/v6/24px.svg" alt="Google" style="width: 18px; height: 18px;">
            <span class="fs-sm">Google İle Kayıt Ol</span>
        </button>

        <!-- Divider -->
        <div class="rbn-auth-divider">
            <div class="rbn-auth-divider-line"></div>
            <span class="rbn-auth-divider-text">veya bilgilerini gir</span>
            <div class="rbn-auth-divider-line"></div>
        </div>

        <form id="registerForm" action="<?= $Route->url('auth.register') ?>" method="POST" data-ajax="true" class="d-flex flex-column gap-3">
            @csrf
            <div class="rbn-auth-input-wrap">
                <label for="name"><i class="ri-user-3-fill"></i>Ad Soyad</label>
                <input type="text" id="name" name="name" placeholder="Adınız Soyadınız" required autofocus>
            </div>

            <div class="rbn-auth-input-wrap">
                <label for="username_reg"><i class="ri-at-fill"></i>Kullanıcı Adı</label>
                <input type="text" id="username_reg" name="username" placeholder="kullaniciadi" required value="<?= htmlspecialchars($_GET['username'] ?? '') ?>">
            </div>

            <div class="rbn-auth-input-wrap">
                <label for="email"><i class="ri-mail-fill"></i>E-posta Adresi</label>
                <input type="email" id="email" name="email" placeholder="ornek@alanadi.com" required autocomplete="username">
            </div>

            <div class="row g-2">
                <div class="col-md-6">
                    <div class="rbn-auth-input-wrap">
                        <label for="password"><i class="ri-lock-2-fill"></i>Şifre</label>
                        <input type="password" id="password" name="password" placeholder="••••••••" required autocomplete="new-password" class="pe-5">
                        <button type="button" class="rbn-auth-pass-toggle rbn-auth-eye-icon" data-rbn-password-toggle="password" data-tooltip="Şifreyi Göster">
                            <i class="ri-eye-line"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="rbn-auth-input-wrap">
                        <!-- [FW-ALTYAPI-3 / B · QA §4.3] Alan adı düzeltildi:
                             `password_confirm` -> `confirm_password`. Kayıt akışında
                             sunucu tarafında parola tekrarı doğrulaması yoktu ve
                             görünümdeki alan adı hiçbir sunucu kuralıyla eşleşmiyordu
                             (sıfırlama görünümü zaten `confirm_password` kullanıyor). -->
                        <label for="confirm_password"><i class="ri-lock-check-fill"></i>Şifre Tekrar</label>
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="••••••••" required autocomplete="new-password" class="pe-5">
                        <button type="button" class="rbn-auth-pass-toggle rbn-auth-eye-icon" data-rbn-password-toggle="confirm_password" data-tooltip="Şifreyi Göster">
                            <i class="ri-eye-line"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="d-flex align-items-center gap-2 fs-sm mt-1">
                <input class="rbn-auth-checkbox" type="checkbox" name="terms" value="1" id="terms" required>
                <label class="text-muted cursor-pointer user-select-none" for="terms">
                    <a href="#" class="text-warning text-decoration-none fw-semibold">Kullanım Koşulları</a>'nı kabul ediyorum.
                </label>
            </div>

            <input type="text" name="website" class="d-none" tabindex="-1" autocomplete="off">

            <button type="submit" class="rbn-btn rbn-auth-btn-submit w-100 mt-2" id="registerBtn">
                <span>Profilimi Oluştur</span>
                <i class="ri-arrow-right-line ms-1"></i>
            </button>
        </form>
        <?php break;

    // =========================================================================
    // 2. LOCKSCREEN (Ekran Kilidi)
    // =========================================================================
    case 'lockscreen': ?>
        <?php 
            $initials = '';
            if (isset($user['name'])) {
                $parts = explode(' ', $user['name']);
                $initials = strtoupper(substr($parts[0], 0, 1) . (isset($parts[1]) ? substr($parts[1], 0, 1) : ''));
            }
        ?>
        <div class="rbn-auth-lock-avatar mx-auto mb-3 fs-2 fw-bold">
            <?= $initials ?: '?' ?>
        </div>

        <div class="mb-4">
            <h1 class="fs-2 rbn-auth-title">Merhaba, <?= $user['name'] ?? 'Kullanıcı' ?></h1>
            <p class="fs-sm text-muted mt-2 mb-0">Oturumunuz kilitlendi. Devam etmek için şifrenizi girin.</p>
        </div>

        <form id="rbn-lockscreen-form" action="<?= $Route->url('auth.login') ?>" method="POST" class="d-flex flex-column gap-3">
            @csrf
            <input type="hidden" name="email" value="<?= $user['email'] ?? '' ?>">

            <div class="rbn-auth-input-wrap text-start">
                <label for="password"><i class="ri-lock-2-fill"></i>Şifre</label>
                <input type="password" id="password" name="password" placeholder="••••••••" required autofocus class="pe-5">
                <button type="button" class="rbn-auth-pass-toggle rbn-auth-eye-icon" data-rbn-password-toggle="password" data-tooltip="Şifreyi Göster">
                    <i class="ri-eye-line"></i>
                </button>
            </div>

            <button type="submit" class="rbn-btn rbn-auth-btn-submit w-100 mt-2">
                <span>Kilidi Aç</span>
                <i class="ri-lock-unlock-fill ms-1"></i>
            </button>
        </form>

        <div class="rbn-auth-back-wrap">
            <a href="<?= $Route->url('logout') ?>" class="rbn-auth-back-link">
                <i class="ri-user-shared-line"></i>
                <span>Başka hesap ile giriş yap</span>
            </a>
        </div>
        <?php break;

    // =========================================================================
    // 3. FORGOT PASSWORD (Şifremi Unuttum)
    // =========================================================================
    case 'forgot-password': ?>
        <div class="text-center mb-4">
            <h1 class="fs-2 rbn-auth-title">Şifremi Unuttum</h1>
            <p class="fs-sm text-muted mt-2 mb-0">Sıfırlama kodu almak için kayıtlı e-postanızı girin.</p>
        </div>

        <form id="forgotPasswordForm" action="<?= $Route->url('auth.password.forgot') ?>" method="POST" data-ajax="true" class="d-flex flex-column gap-3">
            @csrf
            <div class="rbn-auth-input-wrap text-start">
                <label for="email"><i class="ri-mail-fill"></i>E-posta Adresi</label>
                <input type="email" id="email" name="email" placeholder="ornek@alanadi.com" required autofocus>
            </div>

            <button type="submit" class="rbn-btn rbn-auth-btn-submit w-100 mt-2" id="forgotBtn">
                <span>Kod Gönder</span>
                <i class="ri-send-plane-fill ms-1"></i>
            </button>
        </form>

        <div class="rbn-auth-back-wrap">
            <p class="fs-sm text-muted m-0">
                Şifrenizi hatırladınız mı? <a href="<?= $Route->url('login') ?>" class="text-warning text-decoration-none fw-semibold">Giriş Yap</a>
            </p>
        </div>
        <?php break;

    // =========================================================================
    // 4. VERIFY CODE — [A0-6] KALDIRILDI (ölü akış)
    //    Eski 6 haneli kod formu, `reset_step` oturum değişkenini bekliyordu;
    //    o değişkeni yazan kod yok → akış uçtan uca ölüydü (A-03/YA-1).
    //    Şimdi akış tek bağlantı (token) üzerinden yürür; bu blok bir daha
    //    render edilmez, referans için burada bırakılmıştır.
    // =========================================================================

    // =========================================================================
    // 5. RESET PASSWORD (Şifre Yenileme)
    // =========================================================================
    case 'reset-password': ?>
        <div class="text-center mb-4">
            <h1 class="fs-2 rbn-auth-title">Şifre Yenileme</h1>
            <p class="fs-sm text-muted mt-2 mb-0">Lütfen yeni ve güçlü şifrenizi belirleyin.</p>
        </div>

        <?php /* [A0-6] Tek kullanımlık token form ile sunucuya taşınır. */ ?>
        <?php if (empty($token)) { ?>
            <div class="alert alert-warning" role="alert">
                Bağlantı bulunamadı. Lütfen <a href="<?= $Route->url('password.forgot') ?>" class="alert-link">yeni bir şifre sıfırlama bağlantısı</a> isteyin.
            </div>
        <?php } else { ?>
        <form id="resetPasswordForm" action="<?= $Route->url('auth.password.reset') ?>" method="POST" data-ajax="true" class="d-flex flex-column gap-3">
            @csrf
            <input type="hidden" name="token" id="token" value="<?= htmlspecialchars((string) $token, ENT_QUOTES, 'UTF-8') ?>">

            <div class="rbn-auth-input-wrap text-start">
                <label for="password"><i class="ri-lock-2-fill"></i>Yeni Şifre</label>
                <input type="password" id="password" name="password" placeholder="••••••••" required minlength="6" autofocus class="pe-5">
                <button type="button" class="rbn-auth-pass-toggle rbn-auth-eye-icon" data-rbn-password-toggle="password" data-tooltip="Şifreyi Göster">
                    <i class="ri-eye-line"></i>
                </button>
            </div>

            <div class="rbn-auth-input-wrap text-start">
                <label for="confirm_password"><i class="ri-lock-check-fill"></i>Şifre Tekrar</label>
                <input type="password" id="confirm_password" name="confirm_password" placeholder="••••••••" required minlength="6" class="pe-5">
                <button type="button" class="rbn-auth-pass-toggle rbn-auth-eye-icon" data-rbn-password-toggle="confirm_password" data-tooltip="Şifreyi Göster">
                    <i class="ri-eye-line"></i>
                </button>
            </div>

            <button type="submit" class="rbn-btn rbn-auth-btn-submit w-100 mt-2" id="resetBtn">
                <span>Şifreyi Güncelle</span>
                <i class="ri-save-fill ms-1"></i>
            </button>
        </form>
        <?php } ?>

        <div class="rbn-auth-back-wrap">
            <p class="fs-sm text-muted m-0">
                <a href="<?= $Route->url('login') ?>" class="text-muted text-decoration-none"><i class="ri-close-line me-1"></i>Vazgeç ve Giriş Yap</a>
            </p>
        </div>
        <?php break;

    // =========================================================================
    // 6. DEFAULT: LOGIN (Giriş Yap)
    // =========================================================================
    default: ?>
        <!-- Segmented Navigation -->
        <div class="d-grid rbn-auth-nav-pill" style="grid-template-columns: 1fr 1fr; gap: 4px;">
            <a href="<?= $Route->url('login') ?>" class="text-center rbn-auth-nav-link active">Giriş Yap</a>
            <a href="<?= $Route->url('register') ?>" class="text-center rbn-auth-nav-link">Kayıt Ol</a>
        </div>

        <div class="text-center mb-4">
            <h1 class="fs-2 rbn-auth-title">Hoş Geldiniz</h1>
            <p class="fs-sm text-muted mt-2 mb-0">Devam etmek için hesabınıza giriş yapın.</p>
        </div>

        <!-- Social Login -->
        <button type="button" class="rbn-btn w-100 rbn-auth-social d-flex align-items-center justify-content-center gap-2" onclick="if(window.rbnAlert) rbnAlert.info('Sosyal Giriş', 'Google ile giriş özelliği yakında aktif edilecektir.'); else alert('Yakında...');">
            <img src="https://fonts.gstatic.com/s/i/productlogos/googleg/v6/24px.svg" alt="Google" style="width: 18px; height: 18px;">
            <span class="fs-sm">Google İle Devam Et</span>
        </button>

        <!-- Divider -->
        <div class="rbn-auth-divider">
            <div class="rbn-auth-divider-line"></div>
            <span class="rbn-auth-divider-text">veya e-posta ile</span>
            <div class="rbn-auth-divider-line"></div>
        </div>

        <form id="loginForm" action="<?= $Route->url('auth.login') ?>" method="POST" data-ajax="true" class="d-flex flex-column gap-3">
            @csrf
            
            <!-- Email -->
            <div class="rbn-auth-input-wrap">
                <label for="email"><i class="ri-mail-fill"></i>E-posta Adresi</label>
                <input type="email" id="email" name="email" placeholder="ornek@alanadi.com" required autocomplete="email" autofocus>
            </div>

            <!-- Password -->
            <div class="rbn-auth-input-wrap">
                <label for="password"><i class="ri-lock-2-fill"></i>Şifre</label>
                <input type="password" id="password" name="password" placeholder="••••••••" required autocomplete="current-password" class="pe-5">
                
                <button type="button" class="rbn-auth-pass-toggle rbn-auth-eye-icon" data-rbn-password-toggle="password" data-tooltip="Şifreyi Göster">
                    <i class="ri-eye-line"></i>
                </button>
            </div>

            <!-- Options -->
            <div class="d-flex justify-content-between align-items-center fs-sm mt-1">
                <label class="d-inline-flex align-items-center gap-2 cursor-pointer text-muted user-select-none">
                    <input class="rbn-auth-checkbox" type="checkbox" name="remember_me" value="1" id="remember_me">
                    <span>Beni hatırla</span>
                </label>

                <a href="<?= $Route->url('password.forgot') ?>" class="text-warning text-decoration-none fw-semibold">
                    Şifremi Unuttum?
                </a>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="rbn-btn rbn-auth-btn-submit w-100 mt-3" id="loginBtn">
                <span>Giriş Yap</span>
                <i class="ri-arrow-right-line ms-1"></i>
            </button>
        </form>
        <?php break;

endswitch; ?>

    <?php if ($view !== 'lockscreen'): ?>
        <!-- Universal Back to Home inside Card with Semantic Class -->
        <div class="rbn-auth-back-wrap">
            <a href="/" class="rbn-auth-back-link">
                <i class="ri-arrow-left-line"></i>
                <span>Ana Sayfaya Dön</span>
            </a>
        </div>
    <?php endif; ?>

</div>

<!-- Universal Feature Bar (Single Line with Semantic Classes) -->
<?php if (!empty($siteFeatures)): ?>
    <div class="rbn-auth-features-bar">
        <?php foreach ($siteFeatures as $feature): ?>
            <div class="rbn-auth-feature-item">
                <i class="ri-checkbox-circle-fill text-warning fs-6"></i>
                <span><?= htmlspecialchars(ltrim($feature, '✨ ')) ?></span>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>


