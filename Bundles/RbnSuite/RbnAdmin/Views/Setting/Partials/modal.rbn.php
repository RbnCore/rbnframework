<form action="<?= $Route->url('admin.bot-settings.create') ?>" method="POST" id="addApiKeyForm" class="rbn-ajax-form rbn-page-refresh">
    @csrf
    <input type="hidden" name="project" value="<?= active_project_key() ?>">
    <div class="modal-body p-4">
        <div class="row g-3">
            <div class="col-12">
                <label for="modal_option_key" class="ra-stat-label mb-1">Eklenecek API Seçeneği</label>
                <div class="d-flex align-items-center gap-2">
                    <select class="form-control form-select font-monospace flex-grow-1" id="modal_option_key" name="option_key" required>
                        <option value="">Lütfen API seçin...</option>
                        <?php foreach ($apiKeys as $key => $label): ?>
                            <option value="<?= $key ?>"><?= htmlspecialchars($label) ?> (<?= $key ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-text small text-muted mt-1">Sistem whitelistinde tanımlı API seçeneklerinden birini seçin.</div>
            </div>
            <div class="col-12">
                <label for="modal_group_key" class="ra-stat-label mb-1">Grup Seçimi</label>
                <select class="form-control form-select font-monospace" id="modal_group_key" name="group_key">
                    <option value="api" selected>API Anahtarları (api)</option>
                    <option value="bot">Bot Yapılandırması (bot)</option>
                </select>
            </div>
            <div class="col-12">
                <label for="modal_option_value" class="ra-stat-label mb-1">API Değeri (Value)</label>
                <textarea class="form-control font-monospace" 
                          id="modal_option_value" name="option_value" rows="3" placeholder="API token veya gizli anahtar değeri..."></textarea>
            </div>
        </div>
    </div>
    <div class="modal-footer p-3 border-top d-flex gap-2 justify-content-end" style="background: var(--rbn-admin-surface, #fbf9f5); border-color: var(--rbn-admin-border-subtle, #e5ded3) !important;">
        <button type="button" class="rbn-btn rbn-btn-outline px-4" data-bs-dismiss="modal">İptal</button>
        <button type="submit" class="rbn-btn rbn-btn-primary px-4 fw-bold">
            <i class="ri-save-line me-1"></i> Kaydet
        </button>
    </div>
</form>
