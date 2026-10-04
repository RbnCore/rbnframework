<?php
/** @var array $settings */
?>

<!-- 🏢 1. FORM ÜSTÜ AKSİYON BARI -->
<div class="d-flex justify-content-between align-items-center mb-3 px-1 flex-wrap gap-2">
    <div>
        <h6 class="fw-bold mb-0 text-dark" style="font-family: var(--rbn-font-body, inherit);">
            <i class="ri-store-2-line text-success me-1.5"></i>Firma Kimlik Bilgileri
        </h6>
        <span class="text-muted small">Marka, logo, slogan ve iletişim alanlarını tek ekrandan yönetin.</span>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= $Route->url('webhub/identity/manage', 'developer') ?>"
            class="rbn-btn rbn-btn-outline rbn-btn-sm rounded-pill">
            <i class="ri-equalizer-line"></i> Mimariyi Yönet
        </a>
        <button type="button" class="rbn-btn rbn-btn-primary rbn-btn-sm rounded-pill"
            data-rbn-modal="true" data-title="Yeni Kimlik Alanı Tanımla" data-theme="primary" data-size="lg"
            data-type="webhub/identity">
            <i class="ri-add-line"></i> Yeni Alan
        </button>
    </div>
</div>

<!-- 📋 2. KİMLİK FORMU KARTI -->
<div class="rbn-card p-4 mb-4">
    <form action="<?= $Route->url('webhub/identity/bulkValueUpdate', 'developer') ?>" method="POST"
        id="identityForm" data-rbn-form="true" autocomplete="off">
        @csrf
        <input type="hidden" name="project" value="<?= active_project_key() ?>">

        <?php if (empty($settings)): ?>
            <div class="text-center py-5">
                <i class="ri-store-2-line fs-1 text-muted opacity-50 d-block mb-2"></i>
                <span class="text-muted small">Henüz kimlik bilgisi kaydı bulunmuyor. Sağ üstteki <b>Yeni Alan</b> butonunu kullanarak mimariyi oluşturun.</span>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($settings as $setting): ?>
                    <?php
                    $isFullWidth = ($setting['field_type'] ?? '') === 'textarea' || str_contains($setting['setting_key'] ?? '', 'address');
                    $col = $isFullWidth ? 'col-12' : 'col-12 col-md-6';
                    ?>
                    <div class="<?= $col ?>">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="ra-stat-label mb-0">
                                <?= htmlspecialchars($setting['label_tr'] ?? $setting['label_en']) ?>
                                <code class="ms-1 text-lowercase font-monospace" style="font-size: 0.68rem;">[<?= $setting['setting_key'] ?>]</code>
                            </label>
                            <?php if (!empty($setting['help_text_tr'])): ?>
                                <span class="text-muted small" style="font-size: 0.7rem;"><?= htmlspecialchars($setting['help_text_tr']) ?></span>
                            <?php endif; ?>
                        </div>

                        <?php if ($setting['field_type'] === 'textarea'): ?>
                            <textarea name="settings[<?= $setting['setting_key'] ?>]" class="form-control"
                                rows="3" autocomplete="off"><?= htmlspecialchars($setting['setting_value'] ?? '') ?></textarea>
                        <?php elseif ($setting['field_type'] === 'select'): ?>
                            <?php $opts = json_decode($setting['field_options'] ?? '[]', true) ?? []; ?>
                            <select name="settings[<?= $setting['setting_key'] ?>]" class="form-select" autocomplete="off">
                                <?php foreach ($opts as $val => $label): ?>
                                    <option value="<?= $val ?>" <?= ($setting['setting_value'] ?? '') == $val ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($label) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php else: ?>
                            <input type="text" name="settings[<?= $setting['setting_key'] ?>]"
                                value="<?= htmlspecialchars($setting['setting_value'] ?? '') ?>" class="form-control"
                                placeholder="<?= htmlspecialchars($setting['label_tr'] ?? $setting['label_en']) ?>" autocomplete="off">
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4 pt-4 border-top">
                <button type="button" class="rbn-btn rbn-btn-outline rounded-pill px-4"
                    onclick="document.getElementById('identityForm').reset()">
                    <i class="ri-refresh-line"></i> Sıfırla
                </button>
                <button type="submit" class="rbn-btn rbn-btn-primary rounded-pill px-5">
                    <i class="ri-save-line"></i> Değişiklikleri Kaydet
                </button>
            </div>
        <?php endif; ?>
    </form>
</div>
