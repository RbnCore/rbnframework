<?php
/** @var array $notification */
/** @var int $id */
$noti = $notification;
?>

<?php if (!$noti): ?>
    <div class="rbn-alert rbn-alert-warning m-4 small shadow-sm">
        <i class="ri-error-warning-line me-2"></i>Bildirim bulunamadı (ID: {{ $id }})
    </div>
<?php return; endif; ?>

<div class="modal-body p-4">
    <!-- Header: Bildirim Tipi ve Önem Derecesi -->
    <div class="d-flex align-items-center justify-content-between mb-4 pb-4 border-bottom border-light">
        <div class="d-flex align-items-center">
            <div class="rbn-avatar rbn-avatar-lg rbn-avatar-primary me-3">
                <i class="<?= str_replace(['bi bi-', 'bi-'], 'ri-', $noti['icon'] ?? 'ri-notification-3-line') ?> fs-4"></i>
            </div>
            <div>
                <h6 class="fw-bold mb-0 text-dark">{{ $noti['title'] }}</h6>
                <div class="text-muted small">
                    <span class="rbn-badge rbn-badge-primary rbn-badge-sm mt-1" style="font-size: 0.65rem;">
                        {{ strtoupper($noti['type'] ?? 'BILDIRIM') }}
                    </span>
                </div>
            </div>
        </div>
        <div class="text-end">
            <div class="text-muted small mb-1 fw-bold text-uppercase" style="font-size: 0.6rem; letter-spacing: 0.5px;">OLUŞTURULMA</div>
            <div class="rbn-badge rbn-badge-secondary rbn-badge-pill px-3 fw-bold">
                <i class="ri-calendar-line me-1 text-primary"></i>{{ now('d.m.Y H:i', strtotime($noti['created_at'])) }}
            </div>
        </div>
    </div>

    <!-- Bildirim Mesajı -->
    <div class="mb-0">
        <label class="form-label fw-bold small text-muted text-uppercase" style="letter-spacing: 0.5px;">Bildirim İçeriği</label>
        <div class="rbn-card p-4 border-start border-primary border-4" style="min-height: 120px;">
            <p class="mb-0 text-dark lh-lg">{{ $noti['message'] }}</p>
        </div>
    </div>
</div>
