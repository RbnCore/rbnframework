<?php 
    $isEdit = ($user['id'] > 0);
    $action = $isEdit ? $Route->url('users/update/' . $user['id'], 'admin', '') : $Route->url('users/store', 'admin', '');
    $btnText = $isEdit ? 'Bilgileri Güncelle' : 'Kullanıcıyı Kaydet';
    $formId = $isEdit ? 'editUserForm' : 'addUserForm';
?>
<form action="{{ $action }}" method="POST" autocomplete="off" id="{{ $formId }}">
    @csrf
    <div class="modal-body p-4">
        <div class="row g-3">
            <div class="col-6">
                <label class="form-label small fw-bold text-muted text-uppercase mb-1">Tam Ad</label>
                <input type="text" name="name" class="rbn-form-input form-control" placeholder="Örn: Ahmet Yılmaz" value="{{ $user['name'] ?? '' }}" autocomplete="off" required>
            </div>
            <div class="col-6">
                <label class="form-label small fw-bold text-muted text-uppercase mb-1">Kullanıcı Adı</label>
                <input type="text" name="username" class="rbn-form-input form-control" placeholder="ahmetyilmaz" value="{{ $user['username'] ?? '' }}" autocomplete="off" required>
            </div>
            <div class="col-12">
                <label class="form-label small fw-bold text-muted text-uppercase mb-1">E-posta Adresi</label>
                <input type="email" name="email" class="rbn-form-input form-control" placeholder="ahmet@example.com" value="{{ $user['email'] ?? '' }}" autocomplete="off" required>
            </div>
            <div class="col-12">
                <label class="form-label small fw-bold text-muted text-uppercase mb-1">Kullanıcı Yetkisi</label>
                <div class="rbn-dropdown dropdown rbn-modal-dropdown w-100">
                    <input type="hidden" name="role" id="modal_user_role" value="{{ $user['role'] ?? 'user' }}" required>
                    <button class="rbn-form-input form-control w-100 d-flex justify-content-between align-items-center dropdown-toggle text-start" 
                        type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="selected-text">
                            @php
                                $selectedRole = $roles[$user['role'] ?? 'user'] ?? ['name' => 'Kullanıcı', 'icon' => '👤'];
                            @endphp
                            {{ $selectedRole['icon'] ?? '' }} {{ $selectedRole['name'] ?? 'Kullanıcı' }}
                        </span>
                        <i class="ri-arrow-down-s-line opacity-50"></i>
                    </button>
                    <ul class="rbn-dropdown-menu dropdown-menu shadow-lg py-2 w-100 mt-1">
                        @foreach($roles as $key => $role)
                        <li>
                            <a class="rbn-dropdown-item dropdown-item py-2 d-flex align-items-center justify-content-between {{ ($user['role'] ?? 'user') === $key ? 'active' : '' }}"
                                href="javascript:void(0)"
                                data-value="{{ $key }}"
                                data-label="{{ $role['icon'] ?? '' }} {{ $role['name'] }}">
                                <span>{{ $role['icon'] ?? '' }} {{ $role['name'] }}</span>
                                @if(($user['role'] ?? 'user') === $key)
                                    <i class="ri-check-line text-warning"></i>
                                @endif
                            </a>
                        </li>
                        @endforeach
                    </ul>
                </div>
            </div>
            <div class="col-12">
                <div class="alert rbn-alert d-flex align-items-center gap-2 py-2 px-3 mb-0 ra-security-tip-alert">
                    <i class="ri-information-fill text-warning flex-shrink-0"></i>
                    <small class="mb-0 fw-medium text-dark">Şifreyi değiştirmek istemiyorsanız boş bırakın.</small>
                </div>
            </div>
            <div class="col-12">
                <label class="form-label small fw-bold text-muted text-uppercase mb-1">Yeni Şifre (Opsiyonel)</label>
                <input type="password" name="password" class="rbn-form-input form-control" placeholder="••••••••••••" autocomplete="new-password">
            </div>
        </div>
    </div>

    <div class="modal-footer d-flex justify-content-end gap-2 border-top">
        <button type="button" class="rbn-btn rbn-btn-secondary px-4" data-bs-dismiss="modal">İptal</button>
        <button type="submit" class="btn rbn-btn-terracotta px-4 fw-bold">
            <i class="ri-checkbox-circle-fill me-2"></i>{{ $btnText }}
        </button>
    </div>
</form>
