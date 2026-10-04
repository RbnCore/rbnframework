<?php
/** @var array $email */
/** @var int|null $id */
/** @var bool $isEdit */
?>

<form action="{{ $Route->url('backstage/email/update-structural', 'developer') }}" method="POST"
    class="needs-validation" data-ajax="true" data-rbn-form="true" novalidate>

    @csrf
    <input type="hidden" name="id" value="{{ $id }}">

    <div class="modal-body p-4">
        <div class="alert alert-info border-0 rounded-4 bg-opacity-10 mb-4">
            <div class="d-flex align-items-center">
                <i class="bi bi-shield-lock-fill fs-3 me-3 text-info"></i>
                <div>
                    <h6 class="mb-1 fw-bold text-dark">Erişim Yetkisi Güncelleme</h6>
                    <p class="small mb-0 opacity-75"><b>{{ $email['label_tr'] ?? $email['setting_key'] }}</b> ayarı için
                        yönetici erişim seviyesini belirleyin.</p>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-12">
                <label class="form-label fw-bold small text-muted text-uppercase mb-1">Yeni Yetki Seviyesi</label>
                <select name="required_role" class="form-select form-select-lg rounded-4 shadow-sm" required>
                    <option value="developer" {{ ($email['required_role'] ?? 'developer' )==='developer' ? 'selected'
                        : '' }}>Developer (Sadece Yazılımcılar)</option>
                    <option value="admin" {{ ($email['required_role'] ?? '' )==='admin' ? 'selected' : '' }}>Admin
                        (Yöneticiler Görebilir)</option>
                </select>
                <div class="form-text mt-2 small px-1">
                    <i class="bi bi-info-circle me-1"></i> Developer yetkisi olan ayarlar standart admin panelinde
                    gizlenir.
                </div>
            </div>
        </div>
    </div>

    <div class="modal-footer bg-light border-top-0 p-3">
        <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">İptal</button>
        <button type="submit" class="btn btn-info text-white rounded-pill px-4 shadow-sm fw-bold">
            <i class="bi bi-shield-check me-1"></i> Yetkiyi Hemen Güncelle
        </button>
    </div>
</form>
