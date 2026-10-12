<?php
/** @var array $log */
/** @var string $content */
?>

<!-- 📊 1. LOG META KARTLARI -->
<div class="row g-4 mb-4">
    <!-- Log Adı -->
    <div class="col-12 col-md-4">
        <div class="ra-stat-card ra-stat-navy h-100 d-flex flex-column justify-content-between">
            <div class="d-flex align-items-center mb-2">
                <div class="ra-stat-icon me-3">
                    <i class="ri-file-text-line"></i>
                </div>
                <div class="min-w-0">
                    <div class="ra-stat-label">LOG ADI</div>
                    <h3 class="ra-stat-value fs-6 text-truncate mb-0" title="<?= htmlspecialchars($log['name']) ?>">
                        <?= htmlspecialchars($log['name']) ?>
                    </h3>
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center pt-2 border-top border-primary border-opacity-10 small">
                <span class="text-muted">Kategori:</span>
                <span class="text-dark fw-bold"><?= htmlspecialchars($log['type'] ?? 'Genel') ?></span>
            </div>
        </div>
    </div>

    <!-- Zaman Damgası -->
    <div class="col-12 col-md-4">
        <div class="ra-stat-card ra-stat-terracotta h-100 d-flex flex-column justify-content-between">
            <div class="d-flex align-items-center mb-2">
                <div class="ra-stat-icon me-3">
                    <i class="ri-time-line"></i>
                </div>
                <div>
                    <div class="ra-stat-label">ZAMAN DAMGASI</div>
                    <h3 class="ra-stat-value fs-6 mb-0">
                        <?= now('d.m.Y H:i:s', $log['modified']) ?>
                    </h3>
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center pt-2 border-top border-secondary border-opacity-10 small">
                <span class="text-muted">Kayıt:</span>
                <span class="text-success fw-bold">Sunucu Saati</span>
            </div>
        </div>
    </div>

    <!-- Log Boyutu -->
    <div class="col-12 col-md-4">
        <div class="ra-stat-card ra-stat-warning h-100 d-flex flex-column justify-content-between">
            <div class="d-flex align-items-center mb-2">
                <div class="ra-stat-icon me-3">
                    <i class="ri-database-2-line"></i>
                </div>
                <div>
                    <div class="ra-stat-label">LOG BOYUTU</div>
                    <h3 class="ra-stat-value fs-6 mb-0">
                        <?= $log['formatted_size'] ?>
                    </h3>
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center pt-2 border-top border-warning border-opacity-10 small">
                <span class="text-muted">Boyut:</span>
                <span class="text-dark fw-bold"><?= number_format($log['size']) ?> Bytes</span>
            </div>
        </div>
    </div>
</div>

<!-- 🖥️ 2. LOG ÇIKTISI & TERMİNAL GÖRÜNÜMÜ -->
<div class="rbn-card p-0 overflow-hidden mb-4">
    <div class="d-flex justify-content-between align-items-center px-4 py-3 border-bottom" style="background: #1e293b;">
        <div class="d-flex align-items-center gap-2 text-white">
            <i class="ri-terminal-box-line text-success fs-5"></i>
            <span class="fw-bold fs-6 font-monospace"><?= htmlspecialchars($log['name']) ?> Günlük Çıktısı</span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button type="button" class="rbn-btn rbn-btn-secondary rbn-btn-xs"
                onclick="RbnUtils.copy(document.getElementById('code-block').textContent)">
                <i class="ri-file-copy-line"></i> Kopyala
            </button>
            <a href="<?= $Route->url('syshub/datapurge/logs/download/' . $log['relative'], 'developer') ?>"
                class="rbn-btn rbn-btn-primary rbn-btn-xs">
                <i class="ri-download-2-line"></i> İndir
            </a>
            <button type="button"
                class="rbn-btn rbn-btn-danger rbn-btn-xs"
                data-rbn-confirm="true"
                data-url="<?= $Route->url('syshub/datapurge/logs/delete/' . $log['relative'], 'developer') ?>"
                data-method="POST"
                data-title="Log Silinsin mi?"
                data-message="<?= htmlspecialchars($log['name']) ?> kalıcı olarak silinecektir."
                data-type="danger">
                <i class="ri-delete-bin-line"></i> Sil
            </button>
        </div>
    </div>

    <div class="p-4 font-monospace" style="max-height: 600px; overflow-y: auto; background: #0f172a; color: #f1f5f9; font-size: 0.82rem; line-height: 1.6;" id="terminal-scroll-area">
        <?php if (empty($content)): ?>
            <div class="text-center py-5 text-muted">
                <i class="ri-file-damage-line fs-1 d-block mb-2 opacity-25"></i>
                Bu log dosyası boş görünüyor.
            </div>
        <?php else: ?>
            <pre id="code-block" class="mb-0 text-success"
                style="font-family: inherit; font-size: 0.82rem; line-height: 1.6; white-space: pre-wrap; word-break: break-all;"><?= htmlspecialchars($content) ?></pre>
        <?php endif; ?>
    </div>

    <div class="d-flex justify-content-between align-items-center px-4 py-2.5 border-top" style="background: #1e293b;">
        <span class="rbn-badge rbn-badge-success rbn-badge-xs">Canlı Akış</span>
        <div class="d-flex gap-1.5">
            <button type="button" class="rbn-btn rbn-btn-secondary rbn-btn-xs" onclick="RbnUtils.scrollTo('terminal-scroll-area', 'top')">
                <i class="ri-arrow-up-line"></i> Başa
            </button>
            <button type="button" class="rbn-btn rbn-btn-secondary rbn-btn-xs" onclick="RbnUtils.scrollTo('terminal-scroll-area', 'bottom')">
                <i class="ri-arrow-down-line"></i> Sona
            </button>
        </div>
    </div>
</div>
