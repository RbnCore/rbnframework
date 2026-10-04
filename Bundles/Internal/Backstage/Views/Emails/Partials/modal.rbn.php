<?php
/** @var array $email */
/** @var int|null $id */
/** @var bool $isEdit */
/** @var string $entity */

$helper = $this->helper('renderField');
?>

<form action="{{ $isEdit ? $Route->url('backstage/email/update', 'developer') : $Route->url('backstage/email/create', 'developer') }}" 
      method="POST" 
      class="needs-validation" 
      data-ajax="true"
      data-rbn-form="true"
      novalidate>

    @csrf
    
    <?php if ($isEdit): ?>
        <input type="hidden" name="id" value="{{ $id }}">
    <?php endif; ?>

    <input type="hidden" name="group_id" value="{{ $email['group_id'] ?? 3 }}">

    <div class="modal-body p-4">
        <div class="row g-3">
            <!-- LOCALIZED LABELS -->
            <div class="col-md-6">
                <label class="form-label fw-bold small text-muted text-uppercase mb-1">Görünür Ad (TR)</label>
                <input type="text" name="label_tr" class="form-control rounded-3" 
                    value="{{ $email['label_tr'] ?? '' }}" 
                    placeholder="Örn: SMTP Sunucusu" required>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold small text-muted text-uppercase mb-1">Görünür Ad (EN)</label>
                <input type="text" name="label_en" class="form-control rounded-3" 
                    value="{{ $email['label_en'] ?? '' }}" 
                    placeholder="Örn: SMTP Host" required>
            </div>
            
            <!-- TECHNICAL KEY -->
            <div class="col-md-12">
                <label class="form-label fw-bold small text-muted text-uppercase mb-1">Ayar Anahtarı (Key)</label>
                <div class="input-group">
                    <?php if (!$isEdit): ?>
                        <span class="input-group-text bg-light text-muted small fw-bold">email_</span>
                    <?php endif; ?>
                    <input type="text" name="setting_key" class="form-control {{ $isEdit ? 'rounded-3' : 'rounded-end-3' }}" 
                        value="{{ $email['setting_key'] ?? '' }}" 
                        {{ $isEdit ? 'readonly' : '' }}
                        placeholder="smtp_host" required>
                </div>
                <div class="form-text small opacity-75">Sistem içinden çağrılacak teknik isim. {{ $isEdit ? '(Düzenlenemez)' : '' }}</div>
            </div>

            <!-- TYPE & ROLE -->
            <div class="col-md-6">
                <label class="form-label fw-bold small text-muted text-uppercase mb-1">Giriş (Input) Tipi</label>
                <select name="field_type" class="form-select rounded-3" required>
                    <?= $helper->parseOptions([
                        'text' => 'Yazı (Text)',
                        'textarea' => 'Uzun Yazı (Textarea)',
                        'select' => 'Seçim (Select)',
                        'checkbox' => 'Onay (Checkbox / Switch)',
                        'number' => 'Sayı (Number)',
                        'email' => 'E-Posta (Email)',
                        'tel' => 'Telefon (Tel)',
                        'color' => 'Renk (Color)'
                    ], $email['field_type'] ?? 'text') ?>
                </select>
            </div>
            
            <div class="col-md-6">
                <label class="form-label fw-bold small text-muted text-uppercase mb-1">Kullanıcı Yetkisi</label>
                <select name="required_role" class="form-select rounded-3" required>
                    <option value="developer" {{ ($email['required_role'] ?? 'developer') === 'developer' ? 'selected' : '' }}>Developer</option>
                    <option value="admin" {{ ($email['required_role'] ?? '') === 'admin' ? 'selected' : '' }}>Admin</option>
                </select>
            </div>

            <!-- HELP TEXT & OPTIONS -->
            <div class="col-12">
                <label class="form-label fw-bold small text-muted text-uppercase mb-1">Açıklama / Yardım Metni</label>
                <input type="text" name="help_text_tr" class="form-control rounded-3" 
                    value="{{ $email['help_text_tr'] ?? '' }}" 
                    placeholder="Ayara dair kısa açıklama...">
            </div>

            <div class="col-12">
                <label class="form-label fw-bold small text-muted text-uppercase mb-1">Seçenekler (JSON)</label>
                <textarea name="field_options" class="form-control rounded-3" rows="2" 
                    placeholder='{"key":"Value"} formatında options (Select tipi için)'>{{ $email['field_options'] ?? '' }}</textarea>
            </div>
        </div>
    </div>

    <div class="modal-footer bg-light border-top-0 p-3">
        <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">İptal</button>
        <button type="submit" class="btn {{ $isEdit ? 'btn-dark' : 'btn-primary' }} rounded-pill px-4 shadow-sm">
            <i class="bi {{ $isEdit ? 'bi-save' : 'bi-check-lg' }} me-1"></i> 
            {{ $isEdit ? 'Yapılandırmayı Güncelle' : 'Mimarileri Kaydet' }}
        </button>
    </div>
</form>
