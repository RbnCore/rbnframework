<?php
/** @var array $data */
/** @var int|null $id */
/** @var bool $isEdit */
/** @var array $icons */
?>

<form action="{{ $isEdit ? $Route->url('backstage/settings/updateGroup', 'developer') : $Route->url('backstage/settings/createGroup', 'developer') }}" method="POST" data-ajax="true" data-rbn-form="true">
    @csrf
    <input type="hidden" name="id" value="{{ $id ?? '' }}">

    <div class="modal-body p-4">
        <div class="row g-4">
            <div class="col-12">
                <label class="form-label fw-bold small text-muted text-uppercase mb-1">Grup Adı</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-type"></i></span>
                    <input type="text" name="group_label" class="form-control rounded-end-3"
                        value="{{ $data['group_label'] ?? '' }}" required placeholder="Örn: Kurumsal Kimlik">
                </div>
            </div>

            <div class="col-12">
                <label class="form-label fw-bold small text-muted text-uppercase mb-1">Grup İkonu</label>
                <select name="group_icon" class="form-select rounded-3" required>
                    <?php foreach ($icons ?? [] as $class => $label): ?>
                        <option value="{{ $class }}" {{ ($data['group_icon'] ?? 'bi-grid-3x3-gap') === $class ? 'selected' : '' }}>{{ $label }}</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12">
                <label class="form-label fw-bold small text-muted text-uppercase mb-1">Grup Açıklaması</label>
                <textarea name="group_description" class="form-control rounded-3" rows="3"
                    placeholder="Grup içeriği hakkında kısa bir not...">{{ $data['group_description'] ?? '' }}</textarea>
            </div>
        </div>
    </div>

    <div class="modal-footer border-0 pt-0 pb-4 px-4 bg-transparent justify-content-end">
        <button type="button" class="btn btn-sm btn-light rounded-pill px-4" data-bs-dismiss="modal">Vazgeç</button>
        <button type="submit" class="btn btn-sm btn-dark rounded-pill px-4 shadow-sm">
            <i class="bi bi-save-fill me-1"></i> {{ $isEdit ? 'Grubu Güncelle' : 'Grubu Kaydet' }}
        </button>
    </div>
</form>
