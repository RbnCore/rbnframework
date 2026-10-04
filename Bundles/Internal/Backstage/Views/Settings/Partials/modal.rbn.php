<?php
/** @var array $data */
/** @var int|null $id */
/** @var bool $isEdit */
/** @var array $groups */

$helper = $this->helper('renderField');
?>

<form action="{{ $isEdit ? $Route->url('backstage/settings/updateSetting', 'developer') : $Route->url('backstage/settings/createSetting', 'developer') }}" 
      method="POST" data-ajax="true" data-rbn-form="true">
    @csrf
    <input type="hidden" name="id" value="{{ $id ?? '' }}">
    <input type="hidden" name="redirect_url" value="{{ $_GET['redirect_url'] ?? $Route->url('backstage/settings/group/' . ($data['group_id'] ?? ''), 'developer') }}">
    
    <div class="modal-body p-4">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label fw-bold small text-muted text-uppercase mb-1">Ayar Adı (TR)</label>
                <input type="text" name="label_tr" class="form-control form-control-sm rounded-3" value="{{ $data['label_tr'] ?? '' }}" required>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold small text-muted text-uppercase mb-1">Ayar Grubu</label>
                <select name="group_id" class="form-select form-select-sm rounded-3" required>
                    <option value="">Grup Seçiniz...</option>
                    <?php foreach ($groups as $grp): ?>
                        <option value="{{ $grp['id'] }}" {{ ($data['group_id'] ?? '') == $grp['id'] ? 'selected' : '' }}>
                            {{ $grp['group_label'] }}
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold small text-muted text-uppercase mb-1">Alan Tipi</label>
                <select name="field_type" class="form-select form-select-sm rounded-3">
                    <?= $helper->parseOptions([
                        'text' => 'Text Input',
                        'textarea' => 'Textarea',
                        'switch' => 'Switch (On/Off)',
                        'select' => 'Select Dropdown',
                        'number' => 'Number'
                    ], $data['field_type'] ?? 'text') ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold small text-muted text-uppercase mb-1">Gerekli Yetki</label>
                <select name="required_role" class="form-select form-select-sm rounded-3">
                    <option value="admin" {{ ($data['required_role'] ?? '') === 'admin' ? 'selected' : '' }}>Admin (Görünür)</option>
                    <option value="developer" {{ ($data['required_role'] ?? 'developer') === 'developer' ? 'selected' : '' }}>Developer (Gizli)</option>
                </select>
            </div>
            <div class="col-12">
                <label class="form-label fw-bold small text-muted text-uppercase mb-1">Alan Seçenekleri (JSON - Select için)</label>
                <textarea name="field_options" class="form-control form-control-sm font-monospace rounded-3" rows="3">{{ $data['field_options'] ?? '' }}</textarea>
                <p class="text-muted small mt-1 mb-0">Örn: <code>{"Option 1":"val1", "Option 2":"val2"}</code></p>
            </div>
        </div>
    </div>

    <div class="modal-footer border-0 pt-0 pb-4 px-4 bg-transparent">
        <button type="button" class="btn btn-sm btn-light rounded-pill px-4" data-bs-dismiss="modal">İptal</button>
        <button type="submit" class="btn btn-sm btn-primary rounded-pill px-4 shadow-sm">
            <i class="bi bi-shield-check me-1"></i> {{ $isEdit ? 'Mimariyi Güncelle' : 'Ayarı Tanımla' }}
        </button>
    </div>
</form>
