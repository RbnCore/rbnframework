<?php
/**
 * Change Email Password Modal View 🔐
 * Terracotta Craft RBN Framework Standard.
 */
?>

<form action="<?= $Route->url('hostmailhub/change-password', 'admin') ?>" method="POST" class="rbn-ajax-form rbn-page-refresh">
    @csrf
    <input type="hidden" name="email" value="<?= htmlspecialchars($email_user ?? '') ?>">
    <input type="hidden" name="domain" value="<?= htmlspecialchars($email_domain ?? '') ?>">

    <div class="modal-body p-4">
        <!-- Account Information -->
        <div class="mb-4">
            <label class="ra-stat-label mb-2 d-flex align-items-center gap-1">
                <i class="ri-at-line text-warning"></i> E-Posta Adresi
            </label>
            <div>
                <input type="text" class="form-control font-monospace fw-bold" style="background: var(--rbn-admin-surface, #fbf9f5); color: var(--rbn-admin-text-display, #1e1b18);" value="<?= htmlspecialchars($email ?? '') ?>" readonly>
            </div>
        </div>

        <!-- Password -->
        <div class="mb-0">
            <label class="ra-stat-label mb-2 d-flex align-items-center gap-1">
                <i class="ri-lock-password-line text-warning"></i> Yeni Şifre Belirleyin
            </label>
            <div class="d-flex align-items-center gap-2">
                <input type="password" name="password" id="change_mail_password" class="form-control font-monospace flex-grow-1" placeholder="Yeni güçlü bir şifre girin" required autocomplete="off" autofocus>
                <button class="rbn-btn rbn-btn-outline px-3" type="button" data-rbn-password-toggle="change_mail_password" title="Göster/Gizle">
                    <i class="ri-eye-line"></i>
                </button>
                <button class="rbn-btn rbn-btn-primary px-3" type="button" data-rbn-password-generate="change_mail_password" title="Güçlü Şifre Üret">
                    <i class="ri-refresh-line me-1"></i> Üret
                </button>
            </div>
        </div>
    </div>

    <div class="modal-footer p-3 border-top d-flex justify-content-end gap-2" style="background: var(--rbn-admin-surface, #fbf9f5); border-color: var(--rbn-admin-border-subtle, #e5ded3) !important;">
        <button type="button" class="rbn-btn rbn-btn-outline px-4" data-bs-dismiss="modal">Vazgeç</button>
        <button type="submit" class="rbn-btn rbn-btn-primary px-4 fw-bold">
            <i class="ri-save-line me-1"></i> Şifreyi Güncelle
        </button>
    </div>
</form>
