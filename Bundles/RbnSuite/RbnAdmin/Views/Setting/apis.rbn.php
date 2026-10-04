<!-- 🗝️ Dedicated API Keys Settings Page -->
<div class="d-flex justify-content-end align-items-center gap-2 mb-4">
    <button type="button" class="rbn-btn rbn-btn-primary px-4 py-2 fw-bold d-flex align-items-center gap-1" 
            data-rbn-modal="true" data-type="add" data-title="Yeni API Ekle"
            data-endpoint="<?= $Route->url('admin.bot-settings.modal') ?>">
        <i class="ri-add-line fs-5"></i> Yeni API Ekle
    </button>
</div>

<div class="row g-4 mb-5">
    <div class="col-12">
        <form action="<?= $Route->url('admin.bot-settings.save') ?>" method="POST">
            @csrf
            <input type="hidden" name="project" value="<?= active_project_key() ?>">

            <div class="rbn-table-wrap mb-4">
                <div class="ra-traffic-card-header">
                    <div>
                        <h6 class="ra-traffic-card-title mb-0">
                            <i class="ri-key-2-line text-warning fs-5"></i> API Anahtarları Yapılandırması
                        </h6>
                        <span class="ra-stat-label text-muted">Sistemde kayıtlı aktif yapay zeka ve bot servis anahtarları</span>
                    </div>
                    <div class="rbn-badge rbn-badge-terracotta px-3 py-1 font-monospace fw-bold">
                        API KEYS
                    </div>
                </div>
                <div class="p-4 bg-white">
                    <?php 
                    $hasApis = false;
                    foreach ($options as $item): 
                        if (str_contains(strtolower($item['option_key']), 'cron')) continue;
                        $hasApis = true;
                        $keyLower = strtolower($item['option_key']);
                        $isSecret = str_contains($keyLower, 'key') || str_contains($keyLower, 'secret') || str_contains($keyLower, 'token');
                        $icon = str_replace(['bi bi-', 'bi-'], 'ri-', $item['icon'] ?? 'ri-key-2-line');
                    ?>
                        <div class="mb-4 pb-3 border-bottom" style="border-color: var(--rbn-admin-border-subtle, #e5ded3) !important;">
                            <label for="<?= $item['option_key'] ?>" class="ra-stat-label mb-2 d-flex align-items-center gap-1">
                                <i class="<?= $icon ?> text-warning"></i> <?= htmlspecialchars($item['label_tr']) ?>
                            </label>
                            <div class="d-flex align-items-center gap-2">
                                <input type="<?= $isSecret ? 'password' : 'text' ?>" class="form-control font-monospace flex-grow-1" 
                                       id="<?= $item['option_key'] ?>" name="settings[<?= $item['option_key'] ?>]" 
                                       value="<?= htmlspecialchars($item['option_value'] ?? '') ?>"
                                       placeholder="<?= htmlspecialchars($item['label_tr']) ?> değerini girin...">
                                <?php if ($isSecret): ?>
                                    <button type="button" class="rbn-btn rbn-btn-outline px-3" 
                                            data-rbn-password-toggle="<?= $item['option_key'] ?>" title="Göster/Gizle">
                                        <i class="ri-eye-line"></i>
                                    </button>
                                <?php endif; ?>
                                <div class="rbn-form-check rbn-form-switch fs-5 mb-0 ms-2">
                                    <input class="rbn-form-check-input rbn-status-toggle cursor-pointer" type="checkbox" role="switch" 
                                           data-rbn-status-toggle="true"
                                           id="active_<?= $item['option_key'] ?>" 
                                           data-id="<?= $item['id'] ?>"
                                           data-url="<?= $Route->url('admin.bot-settings.status') ?>"
                                           <?= ($item['is_active'] ?? 1) ? 'checked' : '' ?>>
                                </div>
                            </div>
                            <div class="form-text small text-muted mt-1">Sistem tarafından kullanılan <?= htmlspecialchars($item['label_tr']) ?> parametresi.</div>
                        </div>
                    <?php endforeach; 
                    if (!$hasApis): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="ri-key-line fs-1 mb-2 d-block text-warning opacity-50"></i>
                            <p class="mb-0 fw-bold text-dark">Kayıtlı API anahtarı bulunamadı.</p>
                            <small>Sağ üstteki butonu kullanarak yeni bir API anahtarı ekleyebilirsiniz.</small>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="p-3 border-top d-flex justify-content-end" style="background: var(--rbn-admin-surface, #fbf9f5); border-color: var(--rbn-admin-border-subtle, #e5ded3) !important;">
                    <button type="submit" class="rbn-btn rbn-btn-primary px-4 py-2 fw-bold d-flex align-items-center gap-1">
                        <i class="ri-save-line fs-5"></i> API Ayarlarını Güncelle
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
