<?php
/** @var array|null $setting */
$isEdit = !empty($setting);
$actionUrl = $isEdit 
    ? $Route->url('webhub/identity/save', 'developer') 
    : $Route->url('webhub/identity/create', 'developer');
?>

<form action="{{ $actionUrl }}" method="POST" id="identityModalForm" data-ajax="true">
    @csrf
    
    @if($isEdit)
        <input type="hidden" name="id" value="{{ $setting['id'] }}">
    @endif

    <div class="modal-body p-4">
        <div class="row g-3">
            <!-- Alan Adı (TR) -->
            <div class="col-md-6">
                <label class="form-label fw-bold small text-muted text-uppercase">Alan Adı (TR) <span class="text-danger">*</span></label>
                <input type="text" name="label_tr" class="form-control form-control-sm rounded-3"
                    placeholder="Örn: Firma Ünvanı" value="{{ $setting['label_tr'] ?? '' }}" required>
            </div>

            <!-- Alan Adı (EN) -->
            <div class="col-md-6">
                <label class="form-label fw-bold small text-muted text-uppercase">Alan Adı (EN) <span class="text-danger">*</span></label>
                <input type="text" name="label_en" class="form-control form-control-sm rounded-3"
                    placeholder="Örn: Company Title" value="{{ $setting['label_en'] ?? '' }}" required>
            </div>

            <!-- Setting Key -->
            <div class="col-md-6">
                <label class="form-label fw-bold small text-muted text-uppercase">Anahtar (Key)</label>
                <input type="text" name="setting_key"
                    class="form-control form-control-sm rounded-3 fw-bold text-primary bg-light"
                    style="pointer-events: none;"
                    placeholder="otomatik-olusturulur" value="{{ $setting['setting_key'] ?? '' }}" readonly required>
                <div class="form-text small opacity-50">Key otonom olarak oluşturulur.</div>
            </div>

            <!-- Alan Tipi -->
            <div class="col-md-6">
                <label class="form-label fw-bold small text-muted text-uppercase">Giriş Tipi <span class="text-danger">*</span></label>
                <select name="field_type" class="form-select form-select-sm rounded-3" required>
                    <option value="text" {{ ($setting['field_type'] ?? '') === 'text' ? 'selected' : '' }}>Kısa Metin (Text)</option>
                    <option value="textarea" {{ ($setting['field_type'] ?? '') === 'textarea' ? 'selected' : '' }}>Uzun Metin (Textarea)</option>
                    <option value="select" {{ ($setting['field_type'] ?? '') === 'select' ? 'selected' : '' }}>Seçim Listesi (Select)</option>
                    <option value="url" {{ ($setting['field_type'] ?? '') === 'url' ? 'selected' : '' }}>Link (URL)</option>
                </select>
            </div>

            <!-- Yetki Rolü -->
            <div class="col-md-6">
                <label class="form-label fw-bold small text-muted text-uppercase">Gerekli Rol</label>
                <select name="required_role" class="form-select form-select-sm rounded-3" required>
                    <option value="user" {{ ($setting['required_role'] ?? '') === 'user' ? 'selected' : '' }}>User</option>
                    <option value="admin" {{ ($setting['required_role'] ?? '') === 'admin' ? 'selected' : '' }}>Admin</option>
                    <option value="developer" {{ ($setting['required_role'] ?? 'developer') === 'developer' ? 'selected' : '' }}>Developer</option>
                </select>
            </div>

            <!-- Açıklama -->
            <div class="col-md-6">
                <label class="form-label fw-bold small text-muted text-uppercase">Açıklama / İpucu</label>
                <input type="text" name="help_text_tr" class="form-control form-control-sm rounded-3"
                    placeholder="Ayarın amacını kısaca yazın" value="{{ $setting['help_text_tr'] ?? '' }}">
            </div>

            <!-- Select Seçenekleri (Dinamik) -->
            <div class="col-12 mt-3 pt-2" id="identity-options-area" style="{{ ($setting['field_type'] ?? '') === 'select' ? '' : 'display: none;' }}">
                <label class="form-label fw-bold small text-muted text-uppercase">Seçenekler (JSON)</label>
                <textarea name="field_options" class="form-control form-control-sm rounded-3 font-monospace" rows="2"
                    placeholder='{"key": "Value"}'>{{ $setting['field_options'] ?? '' }}</textarea>
                <div class="form-text small">Sadece 'Select' tipi seçilirse gereklidir. Örn: <code>{"tr":"Turkish", "en":"English"}</code></div>
            </div>
        </div>
    </div>

    <div class="modal-footer border-0 pt-0 pb-4 px-4">
        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-4"
            data-bs-dismiss="modal">İptal</button>
        <button type="submit" class="btn btn-sm btn-primary rounded-pill px-4 shadow-sm">
            <i class="bi bi-check-lg me-1"></i> {{ $isEdit ? 'Değişiklikleri Kaydet' : 'Alanı Kaydet' }}
        </button>
    </div>
</form>

<script>
    rbnReady(function () {
        const form = document.getElementById('identityModalForm');
        const nameInput = form.querySelector('input[name="label_en"]');
        const keyInput = form.querySelector('input[name="setting_key"]');
        const typeSelect = form.querySelector('select[name="field_type"]');
        const optionsArea = document.getElementById('identity-options-area');

        // Dinamik Slug Üretimi (Sadece Ekleme Modunda)
        @if(!$isEdit)
        if (nameInput && keyInput) {
            nameInput.addEventListener('input', function () {
                if (typeof RbnUtils !== 'undefined' && typeof RbnUtils.createSlug === 'function') {
                    keyInput.value = RbnUtils.createSlug(this.value);
                }
            });
        }
        @endif

        // Select Tipi Kontrolü
        if (typeSelect && optionsArea) {
            typeSelect.addEventListener('change', function () {
                if (this.value === 'select') {
                    $(optionsArea).slideDown();
                } else {
                    $(optionsArea).slideUp();
                }
            });
        }
    });
</script>
