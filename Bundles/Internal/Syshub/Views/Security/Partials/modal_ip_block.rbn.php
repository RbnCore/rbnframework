<form action="<?= $Route->url('syshub/security/ipBlock/create', 'developer') ?>" method="POST" data-ajax="true">
    @csrf
    <div class="p-4">
        <div class="mb-3">
            <label class="ra-stat-label mb-1">IP Adresi</label>
            <input type="text" name="ip_address" class="form-control font-monospace" placeholder="Örn: 192.168.1.1" required>
        </div>
        <div class="mb-3">
            <label class="ra-stat-label mb-1">Engelleme Nedeni</label>
            <input type="text" name="reason" class="form-control" placeholder="Örn: Şüpheli Giriş Denemesi" required>
        </div>
        <div class="mb-3">
            <label class="ra-stat-label mb-1">Süre (Dakika)</label>
            <select name="duration" class="form-select font-monospace">
                <option value="60">1 Saat</option>
                <option value="1440" selected>1 Gün (24 Saat)</option>
                <option value="10080">1 Hafta</option>
                <option value="43200">1 Ay</option>
                <option value="525600000">Süresiz (Kalıcı)</option>
            </select>
        </div>
    </div>
    <div class="d-flex justify-content-end gap-2 px-4 pb-4">
        <button type="button" class="rbn-btn rbn-btn-outline rounded-pill px-4" data-bs-dismiss="modal">İptal</button>
        <button type="submit" class="rbn-btn rbn-btn-danger rounded-pill px-4">
            <i class="ri-forbid-2-line"></i> IP Engelle
        </button>
    </div>
</form>
