<?php
/** @var array $contact */
/** @var int $id */
$msg = $contact; // Consistent naming
?>

<?php if (!$msg): ?>
    <div class="rbn-alert rbn-alert-warning m-4 small shadow-sm">
        <i class="ri-error-warning-line me-2"></i>Mesaj bulunamadı (ID: {{ $id }})
    </div>
<?php return; endif; ?>

<div class="modal-body p-4">
    <!-- Header: Gönderen Bilgileri -->
    <div class="d-flex align-items-center justify-content-between mb-4 pb-4 border-bottom border-light">
        <div class="d-flex align-items-center">
            <div class="rbn-avatar rbn-avatar-lg rbn-avatar-primary me-3">
                <i class="ri-user-3-fill fs-4"></i>
            </div>
            <div>
                <h6 class="fw-bold mb-0 text-dark">{{ $msg['name'] }}</h6>
                <div class="text-muted small">
                    <i class="ri-mail-line me-1 text-primary"></i>{{ $msg['email'] }}
                </div>
            </div>
        </div>
        <div class="text-end">
            <div class="text-muted small mb-1 fw-bold text-uppercase" style="font-size: 0.6rem; letter-spacing: 0.5px;">GÖNDERİM TARİHİ</div>
            <div class="rbn-badge rbn-badge-secondary rbn-badge-pill px-3 fw-bold">
                <i class="ri-history-line me-1 text-primary"></i>{{ now('d.m.Y H:i', strtotime($msg['created_at'])) }}
            </div>
        </div>
    </div>

    <!-- Konu Alanı -->
    <div class="mb-4">
        <label class="form-label fw-bold small text-muted text-uppercase" style="letter-spacing: 0.5px;">Konu Başlığı</label>
        <div class="p-3 bg-slate-subtle rounded-3 border border-light">
            <h6 class="fw-bold text-dark mb-0"><?= htmlspecialchars($msg['subject'] ?: '(Konu Belirtilmemiş)') ?></h6>
        </div>
    </div>

    <!-- Mesaj İçeriği -->
    <div class="mb-0">
        <label class="form-label fw-bold small text-muted text-uppercase" style="letter-spacing: 0.5px;">Mesaj İçeriği</label>
        <div class="rbn-card p-4 border-start border-primary border-4" style="min-height: 150px;">
            <p class="mb-0 text-dark lh-lg" style="white-space: pre-wrap;"><?= htmlspecialchars($msg['message']) ?></p>
        </div>
    </div>
</div>

<div class="modal-footer bg-light p-3 border-top border-light d-flex justify-content-end gap-2">
    <button type="button" class="rbn-btn rbn-btn-outline rbn-btn-pill px-4" data-bs-dismiss="modal">Kapat</button>
    <a href="mailto:{{ $msg['email'] }}?subject=Re: {{ $msg['subject'] }}" class="rbn-btn rbn-btn-primary rbn-btn-pill px-5 fw-bold">
        <i class="ri-reply-line me-1"></i> Yanıtla
    </a>
</div>
