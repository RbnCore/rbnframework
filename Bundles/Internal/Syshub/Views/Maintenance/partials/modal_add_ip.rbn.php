<form id="addMaintenanceIpForm" class="ajax-form rbn-form p-4"
    action="<?= $Route->url('syshub/maintenance/create', 'developer') ?>" method="POST" data-success-action="reload">
    @csrf

    <div class="mb-3">
        <label for="ip_address" class="ra-stat-label mb-1">
            IP Adresi <span class="text-danger">*</span>
        </label>
        <input type="text" class="form-control font-monospace"
            id="ip_address" name="ip_address" placeholder="Örn: 192.168.1.10" value="<?= htmlspecialchars($_SERVER['REMOTE_ADDR'] ?? '') ?>"
            required>
        <div class="text-muted small mt-1" style="font-size: 0.72rem;">
            <i class="ri-information-line me-1"></i> Şu anki IP adresiniz otomatik doldurulmuştur.
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2 pt-3 border-top">
        <button type="button" class="rbn-btn rbn-btn-outline rounded-pill px-4" data-bs-dismiss="modal">İptal</button>
        <button type="submit" class="rbn-btn rbn-btn-primary rounded-pill px-4">
            <i class="ri-save-line"></i> Listeye Ekle
        </button>
    </div>
</form>
