<?php
/** @var array $settings */
?>

<!-- 🔌 1. FORM ÜSTÜ AKSİYON BARI -->
<div class="d-flex justify-content-between align-items-center mb-3 px-1 flex-wrap gap-2">
    <div>
        <h6 class="fw-bold mb-0 text-dark" style="font-family: var(--rbn-font-body, inherit);">
            <i class="ri-code-s-slash-line text-dark me-1.5"></i>Özel Script (JS/HTML) Yapılandırması
        </h6>
        <span class="text-muted small">Head, Body ve Footer scriptlerini <code>&lt;script&gt; ... &lt;/script&gt;</code> etiketleriyle ekleyin.</span>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= $Route->url('webhub/integrations/manage', 'developer') ?>"
            class="rbn-btn rbn-btn-outline rbn-btn-sm rounded-pill">
            <i class="ri-equalizer-line"></i> Mimariyi Yönet
        </a>
    </div>
</div>

<!-- 📋 2. ENTEGRASYONLAR FORMU KARTI -->
<div class="rbn-card p-4 mb-4">
    <form action="<?= $Route->url('webhub/integrations/bulkvalueupdate', 'developer') ?>" method="POST"
        id="integrationsForm" data-rbn-form="true" autocomplete="off">
        @csrf

        <?php if (empty($settings)): ?>
            <div class="text-center py-5">
                <i class="ri-code-s-slash-line fs-1 text-muted opacity-50 d-block mb-2"></i>
                <span class="text-muted small">Entegrasyon alanları veritabanında bulunamadı.</span>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($settings as $setting): ?>
                    <div class="col-12">
                        <div class="p-3 rounded-3" style="background: rgba(60, 50, 40, 0.03); border: 1px solid var(--rbn-border-subtle, #f0ebe1);">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="ra-stat-label mb-0">
                                    <i class="ri-terminal-line me-1 text-primary"></i>
                                    <?= htmlspecialchars($setting['label_tr'] ?? $setting['setting_name_tr'] ?? $setting['setting_name'] ?? '') ?>
                                    <code class="ms-1 font-monospace text-lowercase" style="font-size: 0.70rem;">[<?= $setting['setting_key'] ?>]</code>
                                </label>
                            </div>

                            <?php if (!empty($setting['description'])): ?>
                                <p class="text-muted small mb-2" style="font-size: 0.75rem;">
                                    <?= $setting['description'] ?>
                                </p>
                            <?php endif; ?>

                            <textarea name="settings[<?= $setting['setting_key'] ?>]"
                                class="form-control font-monospace"
                                style="font-size: 0.8125rem; line-height: 1.5; background: #0d1117; color: #e6edf3; border: 1px solid #30363d;"
                                rows="6" placeholder="<!-- Script veya HTML kodunu buraya yapıştırın -->" spellcheck="false"
                                autocomplete="off"><?= htmlspecialchars($setting['setting_value'] ?? '') ?></textarea>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4 pt-4 border-top">
                <button type="button" class="rbn-btn rbn-btn-outline rounded-pill px-4"
                    onclick="document.getElementById('integrationsForm').reset()">
                    <i class="ri-refresh-line"></i> Sıfırla
                </button>
                <button type="submit" class="rbn-btn rbn-btn-primary rounded-pill px-5">
                    <i class="ri-save-line"></i> Scriptleri Kaydet
                </button>
            </div>
        <?php endif; ?>
    </form>
</div>
