<form action="{{ $Route->url('backstage/sidebar/menu/save', 'developer') }}" method="POST" data-ajax="true"
    data-rbn-form="true">
    @csrf
    <input type="hidden" name="id" value="{{ $menu['id'] ?? '' }}">

    <div class="modal-body p-4">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label fw-bold small text-muted text-uppercase mb-1">Menü Başlığı</label>
                <input type="text" name="menu_title" class="form-control rounded-3"
                    value="{{ $menu['menu_title'] ?? '' }}" autocomplete="off" required>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold small text-muted text-uppercase mb-1">Menü Linki (URL)</label>
                <input type="text" name="menu_url" class="form-control rounded-3" value="{{ $menu['menu_url'] ?? '' }}"
                    autocomplete="off" required>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold small text-muted text-uppercase mb-1">Kategori</label>
                <select name="category_id" class="form-select rounded-3" required>
                    <option value="0">Kategorisiz</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="{{ $cat['id'] }}" {{ ($menu['category_id'] ?? 0)==$cat['id'] ? 'selected' : '' }}>{{
                            $cat['category_name'] }}</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold small text-muted text-uppercase mb-1">Üst Menü</label>
                <select name="parent_id" class="form-select rounded-3">
                    <option value="0">Yok (Ana Menü)</option>
                    <?php foreach ($parents as $p): ?>
                        <?php if (($menu['id'] ?? 0) != $p['id']): ?>
                            <option value="{{ $p['id'] }}" {{ ($menu['parent_id'] ?? 0)==$p['id'] ? 'selected' : '' }}>{{
                                $p['menu_title'] }}</option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold small text-muted text-uppercase mb-1">İkon</label>
                <select name="menu_icon" class="form-select rounded-3">
                    <?php foreach ($icons as $class => $label): ?>
                        <option value="{{ $class }}" {{ ($menu['menu_icon'] ?? 'bi-app' )==$class ? 'selected' : '' }}>{{
                            $label }}</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold small text-muted text-uppercase mb-1">Yetki (Role)</label>
                <select name="required_role" class="form-select rounded-3">
                    <option value="" {{ empty($menu['required_role']) ? 'selected' : '' }}>Herkes (Public)</option>
                    <?php foreach ($roles as $roleKey => $roleData): ?>
                        <option value="{{ $roleKey }}" {{ ($menu['required_role'] ?? '' )==$roleKey ? 'selected' : '' }}>
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
