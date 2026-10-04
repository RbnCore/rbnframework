<?php
/** @var array $smtp */
/** @var array $sender */
/** @var array $general */
/** @var array $mapped */
/** @var array|null $enabled */
?>

<!-- 📧 1. ÜST AKSİYON BARI -->
<div class="d-flex justify-content-between align-items-center mb-3 px-1 flex-wrap gap-2">
    <div>
        <h6 class="fw-bold mb-0 text-dark" style="font-family: var(--rbn-font-body, inherit);">
            <i class="ri-mail-settings-line text-danger me-1.5"></i>E-Posta & SMTP Yapılandırması
        </h6>
        <span class="text-muted small">Sunucu parametrelerini, gönderici kimliğini ve admin bildirim adreslerini yönetin.</span>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= $Route->url('backstage/email/manage', 'developer') ?>"
            class="rbn-btn rbn-btn-outline rbn-btn-sm rounded-pill">
            <i class="ri-equalizer-line"></i> Teknik Yapılandırma
        </a>
    </div>
</div>

<!-- 📋 2. E-POSTA FORMU & SEKMELER -->
<form action="<?= $Route->url('backstage/email/update', 'developer') ?>" method="POST" data-ajax="true"
    data-rbn-form="true">
    @csrf
    <div class="row g-4">

        <!-- SOL: SEKME MENÜSÜ & AKTİF/PASİF KARTI -->
        <div class="col-12 col-lg-4 col-xl-3">
            <div class="rbn-card p-3 mb-4">
                <div class="ra-stat-label mb-2 px-2">YAPILANDIRMA SEKMELERİ</div>
                <div class="nav flex-column nav-pills gap-1.5" role="tablist">
                    <button type="button" class="rbn-btn rbn-btn-outline justify-content-start active w-100 rounded-3 py-2.5"
                        data-bs-toggle="pill" data-bs-target="#tab-smtp" role="tab">
                        <i class="ri-server-line text-primary fs-5 me-2"></i>
                        <span class="fw-bold small">Sunucu Ayarları (SMTP)</span>
                    </button>
                    <button type="button" class="rbn-btn rbn-btn-outline justify-content-start w-100 rounded-3 py-2.5"
                        data-bs-toggle="pill" data-bs-target="#tab-sender" role="tab">
                        <i class="ri-send-plane-line text-success fs-5 me-2"></i>
                        <span class="fw-bold small">Gönderici Kimliği</span>
                    </button>
                    <button type="button" class="rbn-btn rbn-btn-outline justify-content-start w-100 rounded-3 py-2.5"
                        data-bs-toggle="pill" data-bs-target="#tab-general" role="tab">
                        <i class="ri-admin-line text-info fs-5 me-2"></i>
                        <span class="fw-bold small">Admin Alıcıları</span>
                    </button>
                </div>
            </div>

            <!-- Mail Gönderimi Genel Switch -->
            <?php if ($enabled): ?>
                <div class="rbn-card p-3 mb-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="fw-bold text-dark small mb-0.5"><?= htmlspecialchars($enabled['label_tr'] ?? $enabled['setting_key']) ?></div>
                            <span class="text-muted" style="font-size: 0.7rem;">Sistem e-posta çıkışı</span>
                        </div>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input ajax-status-toggle" type="checkbox"
                                data-id="<?= $enabled['id'] ?>"
                                data-url="<?= $Route->url('backstage/email/toggle', 'developer') ?>"
                                <?= !empty($enabled['is_active']) ? 'checked' : '' ?>>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- SAĞ: SEKME İÇERİKLERİ FORMU -->
        <div class="col-12 col-lg-8 col-xl-9">
            <div class="rbn-card p-4">
                <div class="tab-content">

                    <!-- 1. SUNUCU AYARLARI (SMTP) -->
                    <div class="tab-pane fade show active" id="tab-smtp" role="tabpanel">
                        <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom" style="font-family: var(--rbn-font-body, inherit);">
                            <i class="ri-server-line text-primary me-1"></i> SMTP Sunucu Parametreleri
                        </h6>
                        <div class="row g-4">
                            <?php foreach ($smtp as $field): ?>
                                <div class="col-12 col-md-6">
                                    <label class="ra-stat-label mb-1">
                                        <?= htmlspecialchars($field['label_tr'] ?? $field['setting_key']) ?>
                                        <code class="ms-1 text-lowercase font-monospace" style="font-size: 0.68rem;">[<?= $field['setting_key'] ?>]</code>
                                    </label>
                                    <input type="<?= str_contains($field['setting_key'], 'password') ? 'password' : 'text' ?>"
                                        name="settings[<?= $field['setting_key'] ?>]"
                                        value="<?= htmlspecialchars($field['setting_value'] ?? '') ?>"
                                        class="form-control font-monospace" autocomplete="off">
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- 2. GÖNDERİCİ KİMLİĞİ -->
                    <div class="tab-pane fade" id="tab-sender" role="tabpanel">
                        <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom" style="font-family: var(--rbn-font-body, inherit);">
                            <i class="ri-send-plane-line text-success me-1"></i> Gönderici & Yanıt Adresleri
                        </h6>
                        <div class="row g-4">
                            <?php foreach ($sender as $field): ?>
                                <div class="col-12 col-md-6">
                                    <label class="ra-stat-label mb-1">
                                        <?= htmlspecialchars($field['label_tr'] ?? $field['setting_key']) ?>
                                        <code class="ms-1 text-lowercase font-monospace" style="font-size: 0.68rem;">[<?= $field['setting_key'] ?>]</code>
                                    </label>
                                    <input type="text" name="settings[<?= $field['setting_key'] ?>]"
                                        value="<?= htmlspecialchars($field['setting_value'] ?? '') ?>"
                                        class="form-control font-monospace" autocomplete="off">
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- 3. ADMİN AYARLARI -->
                    <div class="tab-pane fade" id="tab-general" role="tabpanel">
                        <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom" style="font-family: var(--rbn-font-body, inherit);">
                            <i class="ri-admin-line text-info me-1"></i> Yönetici Alıcı & Bildirim Adresleri
                        </h6>
                        <div class="row g-4">
                            <?php foreach ($general as $field): ?>
                                <div class="col-12 col-md-6">
                                    <label class="ra-stat-label mb-1">
                                        <?= htmlspecialchars($field['label_tr'] ?? $field['setting_key']) ?>
                                        <code class="ms-1 text-lowercase font-monospace" style="font-size: 0.68rem;">[<?= $field['setting_key'] ?>]</code>
                                    </label>
                                    <input type="text" name="settings[<?= $field['setting_key'] ?>]"
                                        value="<?= htmlspecialchars($field['setting_value'] ?? '') ?>"
                                        class="form-control font-monospace" autocomplete="off">
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                </div>

                <div class="d-flex justify-content-end gap-2 mt-4 pt-4 border-top">
                    <button type="submit" class="rbn-btn rbn-btn-primary rounded-pill px-5">
                        <i class="ri-save-line"></i> E-Posta Ayarlarını Kaydet
                    </button>
                </div>
            </div>
        </div>

    </div>
</form>
