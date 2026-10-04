<?php
/** @var array|null $navigation */
/** @var array $parents */
$actionUrl = $Route->url('webhub/navigation/save', 'developer');
?>

<form action="{{ $actionUrl }}" method="POST" id="navigationForm" data-ajax="true" autocomplete="off">
    @csrf

    @if($isEdit)
    <input type="hidden" name="id" value="{{ $navigation['id'] }}">
    @else
    <input type="hidden" name="is_active" value="1">
    <input type="hidden" name="order_num" value="99">
    @endif

    <div class="modal-body p-4">
        <div class="row g-4">
            <!-- SATIR 1: BAŞLIK & URL -->
            <div class="col-md-6">
                <label class="form-label fw-bold small text-muted text-uppercase mb-1">
                    <i class="bi bi-fonts me-1 text-primary"></i> Menü Başlığı <span class="text-danger">*</span>
                </label>
                <input type="text" name="title" class="form-control rounded-3 py-2" placeholder="Örn: Hakkımızda"
                    value="{{ $navigation['title'] ?? '' }}" required>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold small text-muted text-uppercase mb-1">
                    <i class="bi bi-link-45deg me-1 text-primary"></i> Menü Linki (URL) <span
                        class="text-danger">*</span>
                </label>
                <input type="text" name="url" class="form-control rounded-3 py-2" placeholder="URL veya /hakkimizda"
                    value="{{ $navigation['url'] ?? '' }}" required>
            </div>

            <!-- SATIR 2: ÜST MENÜ (HİYERARŞİ) -->
            <div class="col-12">
                <label class="form-label fw-bold small text-muted text-uppercase mb-1">
                    <i class="bi bi-diagram-2 me-1 text-primary"></i> Üst Menü (Hiyerarşi)
                </label>
                <select name="parent_id" class="form-select rounded-3 py-2">
                    <option value="0">-- Ana Menü Olarak Kalsın (Root) --</option>
                    <?php foreach ($parents as $parent): ?>
                        <option value="{{ $parent['id'] }}" {{ ($navigation['parent_id'] ?? 0)==$parent['id'] ? 'selected'
                            : '' }}>
                            {{ $parent['title'] }}
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="form-text small opacity-75 mt-1">Bu öğeyi başka bir menünün altına eklemek için seçin.</div>
            </div>

            <!-- SATIR 3: GÖSTERİLECEĞİ YER -->
            <div class="col-12">
                <label class="form-label fw-bold small text-muted text-uppercase mb-1">
                    <i class="bi bi-display me-1 text-primary"></i> Gösterileceği Yer (Konum)
                </label>
                <select name="show_in" class="form-select rounded-3 py-2">
                    <?php foreach ($showInOptions as $val => $label): ?>
                        <option value="{{ $val }}" {{ ($navigation['show_in'] ?? 0) == $val ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="form-text small opacity-75 mt-1">Bu menü öğesinin hangi menü alanında listeleneceğini belirler.</div>
            </div>

            <!-- SATIR 4: HEDEF & İKON -->
            <div class="col-md-6">
                <label class="form-label fw-bold small text-muted text-uppercase mb-1">
                    <i class="bi bi-box-arrow-up-right me-1 text-primary"></i> Açılış Hedefi
                </label>
                <select name="target" class="form-select rounded-3 py-2">
                    <option value="_self" {{ ($navigation['target'] ?? '_self' )==='_self' ? 'selected' : '' }}>Aynı
                        Sekme (_self)</option>
                    <option value="_blank" {{ ($navigation['target'] ?? '' )==='_blank' ? 'selected' : '' }}>Yeni Sekme
                        (_blank)</option>
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold small text-muted text-uppercase mb-1">
                    <i class="bi bi-star me-1 text-primary"></i> İkon (Bootstrap Sınıfı)
                </label>
                <input type="text" name="icon" class="form-control rounded-3 py-2" placeholder="bi-house"
                    value="{{ $navigation['icon'] ?? '' }}">
            </div>

            <input type="hidden" name="project_key" value="{{ $navigation['project_key'] ?? active_project_key() }}">

            @if(!$isEdit)
            <div class="col-12">
                <div
                    class="alert alert-info rounded-4 border-0 mt-2 small d-flex align-items-center bg-opacity-10 text-primary mb-0">
                    <i class="bi bi-info-circle-fill fs-5 me-3"></i>
                    <span>Yeni eklenen menü en sona eklenecektir. Sıralamayı ana sayfadan sürükleyerek
                        değiştirebilirsiniz.</span>
                </div>
            </div>
            @endif
        </div>
    </div>

    <div class="modal-footer border-0 pt-0 pb-4 px-4 bg-transparent mt-0">
        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-4"
            data-bs-dismiss="modal">Vazgeç</button>
        <button type="submit" class="btn btn-sm btn-primary rounded-pill px-5 shadow-sm">
            <i class="bi bi-check-lg me-1"></i> {{ $isEdit ? 'Değişiklikleri Kaydet' : 'Menü Öğesini Ekle' }}
        </button>
    </div>
</form>
