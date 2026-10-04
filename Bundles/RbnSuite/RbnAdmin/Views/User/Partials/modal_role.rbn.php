<?php
/** @var array $user */
?>

<form action="{{ $Route->url('users/update-role', 'admin', '') }}" method="POST" class="rbn-form">
    @csrf
    <input type="hidden" name="id" value="{{ $user['id'] }}">
    
    <div class="modal-body p-4">
        <div class="mb-4 text-center">
            <div class="rbn-avatar rbn-avatar-lg rbn-avatar-terracotta mx-auto mb-3 fw-bold fs-4">
                {{ strtoupper(substr($user['name'] ?? 'K', 0, 1)) }}
            </div>
            <h6 class="fw-bold mb-1 text-dark">{{ $user['name'] ?? '' }}</h6>
            <p class="text-muted small mb-0 font-monospace">{{ $user['email'] ?? '' }}</p>
        </div>

        <div class="mb-0">
            <label class="form-label fw-bold small text-muted text-uppercase mb-2">
                <i class="ri-shield-keyhole-line me-1 text-warning"></i> Yetki Seviyesi Seçin
            </label>
            <div class="d-flex flex-column gap-2">
                <label class="ra-stat-card p-3 d-flex align-items-center gap-3 rbn-cursor-pointer mb-0">
                    <input class="form-check-input mt-0" type="radio" name="role" value="user" id="role_user" {{ ($user['role'] ?? 'user') === 'user' ? 'checked' : '' }}>
                    <div class="flex-grow-1">
                        <span class="fw-bold text-dark d-block">Standart Kullanıcı (User)</span>
                        <small class="text-muted">Profilini yönetebilir, temel site özelliklerini kullanabilir.</small>
                    </div>
                </label>
                <label class="ra-stat-card p-3 d-flex align-items-center gap-3 rbn-cursor-pointer mb-0">
                    <input class="form-check-input mt-0" type="radio" name="role" value="editor" id="role_editor" {{ ($user['role'] ?? '') === 'editor' ? 'checked' : '' }}>
                    <div class="flex-grow-1">
                        <span class="fw-bold text-dark d-block">Editör (Editor)</span>
                        <small class="text-muted">İçerik yönetebilir, blog ve sayfa ekleyebilir.</small>
                    </div>
                </label>
                <label class="ra-stat-card p-3 d-flex align-items-center gap-3 rbn-cursor-pointer mb-0">
                    <input class="form-check-input mt-0" type="radio" name="role" value="admin" id="role_admin" {{ ($user['role'] ?? '') === 'admin' ? 'checked' : '' }}>
                    <div class="flex-grow-1">
                        <span class="fw-bold text-danger d-block">Yönetici (Admin)</span>
                        <small class="text-muted">Site ayarlarını ve tüm kullanıcıları yönetebilir.</small>
                    </div>
                </label>
            </div>
        </div>
    </div>

    <div class="modal-footer d-flex justify-content-end gap-2 border-top">
        <button type="button" class="rbn-btn rbn-btn-secondary px-4" data-bs-dismiss="modal">İptal</button>
        <button type="submit" class="btn rbn-btn-terracotta px-4 fw-bold">
            <i class="ri-checkbox-circle-line me-1"></i> Yetkiyi Güncelle
        </button>
    </div>
</form>
