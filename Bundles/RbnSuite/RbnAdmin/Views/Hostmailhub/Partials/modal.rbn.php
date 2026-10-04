<?php
/**
 * Create Email Account Modal View 📨
 * Terracotta Craft Sovereign Standard.
 */
?>

<form action="<?= $Route->url('hostmailhub/create', 'admin') ?>" method="POST" class="rbn-ajax-form rbn-page-refresh">
    @csrf
    <div class="modal-body p-4">
        <!-- Account Information -->
        <div class="mb-4">
            <label class="ra-stat-label mb-2 d-flex align-items-center gap-1">
                <i class="ri-at-line text-warning"></i> E-Posta Adresi
            </label>
            <div class="d-flex align-items-center gap-2">
                <input type="text" name="host_mail_address" class="form-control font-monospace flex-grow-1" placeholder="örn: info, destek" required autofocus autocomplete="off">
                <span class="text-muted fw-bold">@</span>
                <select name="domain" class="form-control form-select font-monospace fw-medium" style="max-width: 210px;" required>
                    <?php foreach ($domains as $dom): ?>
                        <option value="<?= htmlspecialchars($dom) ?>"><?= htmlspecialchars($dom) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-text small mt-1 text-muted">
                Sadece küçük harf, rakam ve nokta (.) kullanabilirsiniz.
            </div>
        </div>

        <!-- Password -->
        <div class="mb-4">
            <label class="ra-stat-label mb-2 d-flex align-items-center gap-1">
                <i class="ri-lock-password-line text-warning"></i> Şifre Belirleyin
            </label>
            <div class="d-flex align-items-center gap-2">
                <input type="password" name="host_mail_password" id="mail_password" class="form-control font-monospace flex-grow-1" placeholder="Güçlü bir şifre girin" required autocomplete="off">
                <button class="rbn-btn rbn-btn-outline px-3" type="button" data-rbn-password-toggle="mail_password" title="Göster/Gizle">
                    <i class="ri-eye-line"></i>
                </button>
                <button class="rbn-btn rbn-btn-primary px-3" type="button" data-rbn-password-generate="mail_password" title="Güçlü Şifre Üret">
                    <i class="ri-refresh-line me-1"></i> Üret
                </button>
            </div>
        </div>

        <!-- Quota -->
        <div class="mb-0">
            <label class="ra-stat-label mb-2 d-flex align-items-center gap-1">
                <i class="ri-database-2-line text-warning"></i> Depolama Kotası (MB)
            </label>
            <div class="d-flex align-items-center gap-2">
                <input type="number" name="host_mail_quota" class="form-control font-monospace flex-grow-1" value="1024" min="0" step="100">
                <span class="text-muted fw-bold">MB</span>
            </div>
            <div class="form-text small mt-1 text-muted">
                <i class="ri-information-line text-warning me-1"></i> <strong>0</strong> girilirse sınırsız (unlimited) kota atanır.
            </div>
        </div>
    </div>

    <div class="modal-footer p-3 border-top d-flex justify-content-end gap-2" style="background: var(--rbn-admin-surface, #fbf9f5); border-color: var(--rbn-admin-border-subtle, #e5ded3) !important;">
        <button type="button" class="rbn-btn rbn-btn-outline px-4" data-bs-dismiss="modal">Vazgeç</button>
        <button type="submit" class="rbn-btn rbn-btn-primary px-4 fw-bold">
            <i class="ri-add-circle-line me-1"></i> Hesabı Oluştur
        </button>
    </div>
</form>
