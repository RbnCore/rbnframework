<?php
/** @var array|null $faq */
/** @var int|null $id */
/** @var bool $isEdit */

// 🎼 RBN Framework: Form Orchestration
$action = $Route->url('webhub/faq/save', 'developer');
?>

<div class="modal-body p-4">
    <form action="{{ $action }}" method="POST" id="faqForm" class="row g-4">
        @csrf
        <?php if ($isEdit): ?>
            <input type="hidden" name="id" value="{{ $id }}">
        <?php endif; ?>

        <!-- SORU -->
        <div class="col-12">
            <label class="form-label rbn-label">Soru <span class="text-danger">*</span></label>
            <div class="input-group input-group-merge">
                <span class="input-group-text bg-light border-end-0"><i
                        class="bi bi-question-circle text-primary"></i></span>
                <input type="text" name="question" class="form-control border-start-0 ps-0"
                    placeholder="Örn: Kargo ücreti ne kadar?" value="{{ $isEdit ? ($faq['question'] ?? '') : '' }}"
                    required>
            </div>
        </div>

        <!-- CEVAP -->
        <div class="col-12">
            <label class="form-label rbn-label">Cevap <span class="text-danger">*</span></label>
            <textarea name="answer" class="form-control rbn-textarea" rows="5"
                placeholder="Soruya detaylı bir cevap yazın..."
                required>{{ $isEdit ? ($faq['answer'] ?? '') : '' }}</textarea>
            <div class="form-text mt-2 opacity-75 italic">HTML etiketleri otomatik olarak temizlenir.</div>
        </div>

        <!-- YAN YANA AYARLAR -->
        <div class="col-12">
            <label class="form-label rbn-label">Görünürlük Durumu</label>
            <div class="rbn-select-wrapper">
                <select name="is_active" class="form-select shadow-none">
                    <option value="1" {{ ($isEdit && (int) ($faq['is_active'] ?? 1) === 1 ) ? 'selected' : '' }}>✅ Aktif (Yayınla)</option>
                    <option value="0" {{ ($isEdit && (int) ($faq['is_active'] ?? 1) === 0 ) ? 'selected' : '' }}>📂 Taslak (Gizli)</option>
                </select>
            </div>
        </div>
    </form>
</div>

<!-- MODAL FOOTER -->
<div class="modal-footer bg-light border-0 py-3 px-4 rounded-bottom-4 justify-content-end">

    <button type="submit" form="faqForm" class="btn btn-sm btn-primary rounded-pill px-4 shadow-sm fw-bold">
        <i class="bi {{ $isEdit ? 'bi-save-fill' : 'bi-plus-circle-fill' }} me-2"></i>
        {{ $isEdit ? 'Güncellemeleri Uygula' : 'Yeni Soru Oluştur' }}
    </button>
</div>
