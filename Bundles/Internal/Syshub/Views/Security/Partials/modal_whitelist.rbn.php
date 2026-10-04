<form action="<?= $Route->url('syshub/security/whitelist/create', 'developer') ?>" method="POST" data-ajax="true">
    @csrf
    <div class="p-4">
        <div class="mb-3">
            <label class="ra-stat-label mb-1">IP Adresi</label>
            <input type="text" name="ip_address" class="form-control font-monospace"
                placeholder="Örn: 192.168.1.1 veya @self" value="" required>
            <div class="text-muted small mt-1" style="font-size: 0.72rem;">Kendi IP adresinizi eklemek güvenli erişim açısından önerilir.</div>
        </div>
        <div class="mb-3">
            <label class="ra-stat-label mb-1">Etiket / Açıklama</label>
            <input type="text" name="label" class="form-control"
                placeholder="Örn: Ofis Ana IP, Geliştirici Ev" required>
        </div>
    </div>
    <div class="d-flex justify-content-end gap-2 px-4 pb-4">
        <button type="button" class="rbn-btn rbn-btn-outline rounded-pill px-4" data-bs-dismiss="modal">İptal</button>
        <button type="submit" class="rbn-btn rbn-btn-primary rounded-pill px-4">
            <i class="ri-shield-check-line"></i> Listeye Ekle
        </button>
    </div>
</form>
