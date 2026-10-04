<?php
/** @var array $page */
/** @var array $templates */
/** @var string $entity */
$id = (int) ($page['id'] ?? 0);
$isEdit = $id > 0;
$title = $isEdit ? 'Sayfayı Düzenle' : 'Yeni Sayfa Oluştur';
$action = $Route->url('webhub/policy/save', 'developer');
?>

<form action="{{ $action }}" method="POST" id="policyModalForm" data-ajax="true">
    @csrf
    <input type="hidden" name="entity" value="page">
    <?php if ($isEdit): ?>
        <input type="hidden" name="id" value="{{ $id }}">
    <?php endif; ?>

    <div class="modal-body p-4">
        <div class="row g-3">
            <div class="col-md-9">
                <label class="form-label fw-bold small text-muted text-uppercase">Sayfa Başlığı <span class="text-danger">*</span></label>
                <input type="text" name="title" value="{{ $page['title'] ?? '' }}" class="form-control form-control-sm rounded-3 shadow-sm border-light-subtle" placeholder="Örn: Hakkımızda" required>
            </div>

            <div class="col-md-3">
                <label class="form-label fw-bold small text-muted text-uppercase">Şablon</label>
                <select name="template" class="form-select form-select-sm rounded-3 shadow-sm border-light-subtle">
                    <?php foreach ($templates as $key => $tpl): ?>
                        <option value="{{ $key }}" <?= (($page['template'] ?? 'default') == $key) ? 'selected' : '' ?>>{{ $tpl['name'] }}</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12">
                <label class="form-label fw-bold small text-muted text-uppercase">URL Slug</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light border-light-subtle border-end-0 text-muted small">/sayfa/</span>
                    <input type="text" name="slug" id="policy_slug_v3" value="{{ $page['slug'] ?? '' }}" 
                           class="form-control border-light-subtle border-start-0 bg-light-subtle fw-medium" 
                           <?= $isEdit ? 'readonly' : '' ?> placeholder="sayfa-basligi" required>
                </div>
            </div>

            <div class="col-12">
                <label class="form-label fw-bold small text-muted text-uppercase">Sayfa İçeriği (HTML) <span class="text-danger">*</span></label>
                <textarea name="content" class="form-control form-control-sm rounded-3 shadow-sm border-light-subtle" rows="8" placeholder="Sayfa içeriğini buraya yazın..." required>{{ $page['content'] ?? '' }}</textarea>
            </div>

            <div class="col-12">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="show_in_footer" value="1" id="footerSwitchV3" <?= ($page['show_in_footer'] ?? 0) ? 'checked' : '' ?>>
                    <label class="form-check-label fw-medium small text-dark" for="footerSwitchV3">Footer menüsünde göster</label>
                </div>
            </div>
        </div>
    </div>

    <div class="modal-footer bg-light bg-opacity-50 border-top-0 px-4 py-3">
        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-4 fw-medium" data-bs-dismiss="modal">İptal</button>
        <button type="submit" class="btn btn-sm btn-info text-white rounded-pill px-5 shadow-sm fw-bold">
            <i class="bi bi-send-fill me-1"></i> {{ $isEdit ? 'Değişiklikleri Kaydet' : 'Sayfayı Yayınla' }}
        </button>
    </div>
</form>

<script>
    rbnReady(function() {
        const titleInput = document.querySelector('#policyModalForm input[name="title"]');
        const slugInput = document.getElementById('policy_slug_v3');
        const isEdit = <?= $isEdit ? 'true' : 'false' ?>;

        if (titleInput && slugInput && !isEdit) {
            titleInput.addEventListener('input', function() {
                if(typeof RbnUtils !== 'undefined') {
                    slugInput.value = RbnUtils.createSlug(this.value);
                }
            });
        }
    });
</script>
