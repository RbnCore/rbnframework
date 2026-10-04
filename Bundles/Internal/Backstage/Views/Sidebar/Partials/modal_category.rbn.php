<form action="{{ $Route->url('backstage/sidebar/category/save', 'developer') }}" method="POST" data-ajax="true"
    data-rbn-form="true">
    @csrf
    <input type="hidden" name="id" value="{{ $category['id'] ?? '' }}">

    <div class="modal-body p-4">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label fw-bold small text-muted text-uppercase mb-1">Kategori Adı</label>
                <input type="text" name="category_name" class="form-control rounded-3"
                    value="{{ $category['category_name'] ?? '' }}" autocomplete="off" required>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold small text-muted text-uppercase mb-1">İkon</label>
                <select name="category_icon" class="form-select rounded-3">
                    <?php foreach ($icons as $class => $label): ?>
                        <option value="{{ $class }}" {{ ($category['category_icon'] ?? 'bi-folder' )==$class ? 'selected'
                            : '' }}>{{ $label }}</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12">
                <label class="form-label fw-bold small text-muted text-uppercase mb-1">Açıklama</label>
                <textarea name="category_description" class="form-control rounded-3"
                    rows="2">{{ $category['category_description'] ?? '' }}</textarea>
            </div>
            <div class="col-md-12">
                <label class="form-label fw-bold small text-muted text-uppercase mb-1">Yetki (Role)</label>
                <select name="required_role" class="form-select rounded-3">
                    <option value="" {{ empty($category['required_role']) ? 'selected' : '' }}>Herkes (Public)</option>
                    <?php foreach ($roles as $roleKey => $roleData): ?>
                        <option value="{{ $roleKey }}" {{ ($category['required_role'] ?? '' )==$roleKey ? 'selected' : ''
                            }}>
                            {{ $roleData['icon'] }} {{ $roleData['name'] }}
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>

    <div class="modal-footer border-0 pt-0 pb-4 px-4 bg-transparent">
        <button type="button" class="btn btn-sm btn-light rounded-pill px-4" data-bs-dismiss="modal">İptal</button>
        <button type="submit" class="btn btn-sm btn-primary rounded-pill px-4 shadow-sm">
            <i class="bi bi-check2 me-1"></i> Kaydet
        </button>
    </div>
</form>
