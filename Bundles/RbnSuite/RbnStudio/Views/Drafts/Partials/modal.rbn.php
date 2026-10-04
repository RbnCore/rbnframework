<?php
/** @var array|null $draft */
/** @var int|null $id */
/** @var bool $isEdit */
/** @var array $categories */

$action = $Route->url('studio/drafts/save', 'admin');
$categoriesList = $categories ?? [];
$drafts = $draft ?? [];
$selectedCatId = $drafts['category_id'] ?? 0;
$selectedCatName = '-- Kategori Seçiniz --';
if ($selectedCatId > 0) {
    foreach ($categoriesList as $cat) {
        if ($cat['id'] == $selectedCatId) {
            $selectedCatName = $cat['name'];
            break;
        }
    }
}
?>
<form action="<?= $action ?>" method="POST" id="draftForm" class="ajax-form" data-rbn-form="true" autocomplete="off">
    <?= $csrf_field ?? '' ?>
    <?php if ($isEdit): ?>
        <input type="hidden" name="id" value="<?= $id ?>">
    <?php endif; ?>
    <input type="hidden" name="bulk_mode" id="bulk_mode" value="0">

    <div class="modal-body p-4">
        <div class="row g-4">
            <?php if (!$isEdit): ?>
            <!-- Ekleme Modu Seçimi (Terracotta Type Cards) -->
            <div class="col-12">
                <label class="form-label fw-bold small text-muted text-uppercase mb-2">
                    <i class="ri-settings-4-line me-1 text-warning"></i> Ekleme Türü
                </label>
                <div class="row g-3">
                    <div class="col-6">
                        <div class="rbn-modal-type-card active d-flex align-items-center gap-3 p-3 cursor-pointer w-100 mb-0" id="card_single" onclick="switchDraftMode('single')">
                            <input class="form-check-input mt-0" type="radio" name="bulk_mode_radio" id="radio_single" value="0" checked>
                            <div>
                                <div class="fw-bold text-dark fs-6 d-flex align-items-center">
                                    <i class="ri-pencil-line text-warning fs-5 me-2"></i>
                                    <span>Tekil Fikir</span>
                                </div>
                                <div class="text-muted small mt-1">Tek bir blog başlığı girin</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="rbn-modal-type-card d-flex align-items-center gap-3 p-3 cursor-pointer w-100 mb-0" id="card_bulk" onclick="switchDraftMode('bulk')">
                            <input class="form-check-input mt-0" type="radio" name="bulk_mode_radio" id="radio_bulk" value="1">
                            <div>
                                <div class="fw-bold text-dark fs-6 d-flex align-items-center">
                                    <i class="ri-file-list-3-line text-warning fs-5 me-2"></i>
                                    <span>Çoklu / Toplu Ekle</span>
                                </div>
                                <div class="text-muted small mt-1">Birden çok başlığı tek seferde ekleyin</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="col-12">
                <!-- Tekli Ekle Alanı -->
                <div id="single-add-container">
                    <label class="form-label fw-bold small text-muted text-uppercase mb-2">
                        <i class="ri-font-size me-1 text-warning"></i> Konu Başlığı <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="title" id="single_title_input" class="rbn-form-input w-100" 
                        placeholder="Örn: Antalya Gayrimenkul Yatırımı Rehberi" value="<?= htmlspecialchars($drafts['title'] ?? '') ?>" <?= !$isEdit ? 'required' : ($isEdit ? 'required' : '') ?>>
                </div>
                
                <?php if (!$isEdit): ?>
                <!-- Toplu Ekle Alanı -->
                <div id="bulk-add-container" style="display: none;">
                    <label class="form-label fw-bold small text-muted text-uppercase mb-2">
                        <i class="ri-file-text-line me-1 text-warning"></i> Toplu Konu Başlıkları <span class="text-danger">*</span>
                    </label>
                    <textarea name="titles" id="bulk_titles_input" class="rbn-form-input w-100" rows="8"
                        placeholder="Konu başlıklarını noktalı virgül (;) ile ayırarak veya her satıra bir tane gelecek şekilde yazın.&#10;Örn:&#10;Antalya Gayrimenkul Yatırımı Rehberi;&#10;Lara ve Muratpaşa Bölge Analizi;&#10;Konut Kredisi Faiz Oranları Değerlendirmesi"></textarea>
                    <div class="form-text text-muted small mt-1">
                        <i class="ri-information-line me-1"></i> Her satır veya noktalı virgülle ayrılan başlık tek bir taslak fikir olarak kaydedilir.
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Kategori Seçimi (Sovereign Modal Dropdown) -->
            <div class="col-12">
                <label class="form-label fw-bold small text-muted text-uppercase mb-2">
                    <i class="ri-folder-line me-1 text-warning"></i> Kategori <span class="text-danger">*</span>
                </label>
                <div class="rbn-dropdown dropdown rbn-modal-dropdown w-100">
                    <input type="hidden" name="category_id" id="category_id_val" value="<?= $selectedCatId ?>" required>
                    <button class="rbn-form-input form-control w-100 d-flex justify-content-between align-items-center dropdown-toggle text-start" 
                        type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="selected-text font-weight-500">
                            <?= htmlspecialchars($selectedCatName) ?>
                        </span>
                        <i class="ri-arrow-down-s-line opacity-50"></i>
                    </button>
                    <ul class="rbn-dropdown-menu dropdown-menu shadow-lg py-2 w-100 mt-1">
                        <li>
                            <a class="rbn-dropdown-item dropdown-item py-2 d-flex align-items-center justify-content-between <?= empty($selectedCatId) ? 'active' : '' ?>"
                                href="javascript:void(0)"
                                data-value=""
                                data-label="-- Kategori Seçiniz --">
                                <span>-- Kategori Seçiniz --</span>
                            </a>
                        </li>
                        <?php foreach ($categoriesList as $cat): ?>
                        <li>
                            <a class="rbn-dropdown-item dropdown-item py-2 d-flex align-items-center justify-content-between <?= ($selectedCatId == $cat['id']) ? 'active' : '' ?>"
                                href="javascript:void(0)"
                                data-value="<?= $cat['id'] ?>"
                                data-label="<?= htmlspecialchars($cat['name']) ?>">
                                <span>
                                    <i class="<?= !empty($cat['icon']) ? htmlspecialchars($cat['icon']) : 'ri-folder-line' ?> text-warning me-1.5"></i>
                                    <?= htmlspecialchars($cat['name']) ?>
                                </span>
                                <?php if ($selectedCatId == $cat['id']): ?>
                                    <i class="ri-check-line text-warning"></i>
                                <?php endif; ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="modal-footer p-3 bg-light border-top d-flex justify-content-end align-items-center rounded-bottom-4">
        <button type="submit" class="rbn-btn rbn-btn-primary px-4 py-2 font-weight-bold shadow-sm d-flex align-items-center gap-1.5">
            <i class="ri-check-line fs-5"></i> Kaydet
        </button>
    </div>
</form>

<script>
window.switchDraftMode = function(mode) {
    var singleContainer = document.getElementById('single-add-container');
    var bulkContainer = document.getElementById('bulk-add-container');
    var bulkHiddenInput = document.getElementById('bulk_mode');
    var singleTitleInput = document.getElementById('single_title_input');
    var bulkTitlesInput = document.getElementById('bulk_titles_input');
    var cardSingle = document.getElementById('card_single');
    var cardBulk = document.getElementById('card_bulk');
    var radioSingle = document.getElementById('radio_single');
    var radioBulk = document.getElementById('radio_bulk');

    if (mode === 'bulk') {
        if (cardSingle) cardSingle.classList.remove('active');
        if (cardBulk) cardBulk.classList.add('active');
        if (radioBulk) radioBulk.checked = true;

        if (singleContainer) singleContainer.style.display = 'none';
        if (bulkContainer) bulkContainer.style.display = 'block';
        if (bulkHiddenInput) bulkHiddenInput.value = '1';
        if (singleTitleInput) singleTitleInput.removeAttribute('required');
        if (bulkTitlesInput) bulkTitlesInput.setAttribute('required', 'required');
    } else {
        if (cardBulk) cardBulk.classList.remove('active');
        if (cardSingle) cardSingle.classList.add('active');
        if (radioSingle) radioSingle.checked = true;

        if (singleContainer) singleContainer.style.display = 'block';
        if (bulkContainer) bulkContainer.style.display = 'none';
        if (bulkHiddenInput) bulkHiddenInput.value = '0';
        if (singleTitleInput) singleTitleInput.setAttribute('required', 'required');
        if (bulkTitlesInput) bulkTitlesInput.removeAttribute('required');
    }
};
</script>
