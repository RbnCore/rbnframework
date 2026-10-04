<form id="updateMaintenanceMessageForm" class="ajax-form rbn-form p-4"
    action="<?= $Route->url('syshub/maintenance/update', 'developer') ?>" method="POST"
    data-success-action="reload">
    @csrf

    <div class="mb-3">
        <label for="maintenance_message" class="ra-stat-label mb-1">
            Ziyaretçilere Gösterilecek Mesaj <span class="text-danger">*</span>
        </label>
        <textarea class="form-control" id="maintenance_message"
            name="maintenance_message" rows="4" placeholder="Örn: Sistemimizde bakım çalışması yapılmaktadır."
            required><?= htmlspecialchars($maintenanceMessage ?? '') ?></textarea>
        <div class="text-muted small mt-1" style="font-size: 0.72rem;">
            <i class="ri-information-line me-1"></i> Bakım modu açıkken siteyi ziyaret eden kullanıcılara gösterilecek bilgilendirme metnidir.
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2 pt-3 border-top">
        <button type="button" class="rbn-btn rbn-btn-outline rounded-pill px-4" data-bs-dismiss="modal">İptal</button>
        <button type="submit" class="rbn-btn rbn-btn-primary rounded-pill px-4">
            <i class="ri-save-line"></i> Mesajı Güncelle
        </button>
    </div>
</form>
