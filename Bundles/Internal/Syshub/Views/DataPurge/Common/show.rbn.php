<?php
/** @var string $filename */
/** @var array $content */
/** @var array $moduleConfig */
/** @var string $type */
?>

<!-- 📊 1. DOSYA META KARTLARI -->
<div class="row g-4 mb-4">
    <!-- Dosya Adı -->
    <div class="col-12 col-md-4">
        <div class="ra-stat-card ra-stat-navy h-100 d-flex flex-column justify-content-between">
            <div class="d-flex align-items-center mb-2">
                <div class="ra-stat-icon me-3">
                    <i class="ri-file-code-line"></i>
                </div>
                <div class="min-w-0">
                    <div class="ra-stat-label">DOSYA ADI</div>
                    <h3 class="ra-stat-value fs-6 text-truncate mb-0" title="<?= htmlspecialchars($filename) ?>">
                        <?= htmlspecialchars($filename) ?>
                    </h3>
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center pt-2 border-top border-primary border-opacity-10 small">
                <span class="text-muted">Birim:</span>
                <span class="text-dark fw-bold"><?= $moduleConfig['title'] ?></span>
            </div>
        </div>
    </div>

    <!-- Değiştirilme Tarihi -->
    <div class="col-12 col-md-4">
        <div class="ra-stat-card ra-stat-terracotta h-100 d-flex flex-column justify-content-between">
            <div class="d-flex align-items-center mb-2">
                <div class="ra-stat-icon me-3">
                    <i class="ri-time-line"></i>
                </div>
                <div>
                    <div class="ra-stat-label">ZAMAN DAMGASI</div>
                    <h3 class="ra-stat-value fs-6 mb-0">
                        <?= now('d.m.Y H:i:s', $content['mtime'] ?? time()) ?>
                    </h3>
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center pt-2 border-top border-secondary border-opacity-10 small">
                <span class="text-muted">Kayıt:</span>
                <span class="text-success fw-bold">Sunucu Saati</span>
            </div>
        </div>
    </div>

    <!-- Dosya Boyutu -->
    <div class="col-12 col-md-4">
        <div class="ra-stat-card ra-stat-warning h-100 d-flex flex-column justify-content-between">
            <div class="d-flex align-items-center mb-2">
                <div class="ra-stat-icon me-3">
                    <i class="ri-database-2-line"></i>
                </div>
                <div>
                    <div class="ra-stat-label">DOSYA BOYUTU</div>
                    <h3 class="ra-stat-value fs-6 mb-0">
                        <?= isset($content['size']) ? number_format($content['size'] / 1024, 2) . ' KB' : '—' ?>
                    </h3>
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center pt-2 border-top border-warning border-opacity-10 small">
                <span class="text-muted">Boyut:</span>
                <span class="text-dark fw-bold"><?= number_format($content['size'] ?? 0) ?> Bytes</span>
            </div>
        </div>
    </div>
</div>

<!-- 🖥️ 2. SOVEREIGN TERMINAL ÇIKTISI -->
<div class="rbn-card p-0 overflow-hidden mb-4">
    <div class="d-flex justify-content-between align-items-center px-4 py-3 border-bottom" style="background: #1e293b;">
        <div class="d-flex align-items-center gap-2 text-white">
            <i class="ri-terminal-box-line text-success fs-5"></i>
            <span class="fw-bold fs-6 font-monospace"><?= $moduleConfig['title'] ?> Veri Çıktısı</span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button type="button" class="rbn-btn rbn-btn-secondary rbn-btn-xs"
                onclick="RbnUtils.copy(document.getElementById('code-block').textContent)">
                <i class="ri-file-copy-line"></i> Kopyala
            </button>
            <button type="button"
                class="rbn-btn rbn-btn-danger rbn-btn-xs"
                data-rbn-confirm="true"
                data-url="<?= $Route->url('syshub/datapurge/' . $type . '/delete/' . urlencode($filename), 'developer') ?>"
                data-method="POST"
                data-title="Dosya Silinsin mi?"
                data-message="<?= htmlspecialchars($filename) ?> kalıcı olarak silinecektir."
                data-type="danger">
                <i class="ri-delete-bin-line"></i> Sil
            </button>
        </div>
    </div>

    <div class="p-4 font-monospace" style="max-height: 600px; overflow-y: auto; background: #0f172a; color: #f1f5f9; font-size: 0.82rem; line-height: 1.6;" id="terminal-scroll-area">
        <?php if (empty($content['raw']) && empty($content['data'])): ?>
            <div class="text-center py-5 text-muted">
                <i class="ri-file-damage-line fs-1 d-block mb-2 opacity-25"></i>
                Bu dosya içeriği boş görünüyor.
            </div>
        <?php else: ?>
            <div class="text-white opacity-50 mb-1 d-flex align-items-center">
                <i class="ri-arrow-right-s-line me-1 text-success"></i> rbn@framework:~$ stat <?= htmlspecialchars($filename) ?>
            </div>
            <div class="mb-3 ps-3 text-white opacity-40 small">
                File: <?= htmlspecialchars($filename) ?><br>
                Size: <?= $content['size'] ?? 0 ?> bytes<br>
                Modified: <?= now('M d Y H:i:s', $content['mtime'] ?? time()) ?><br>
                Type: <?= $moduleConfig['title'] ?>
            </div>

            <div class="text-white opacity-50 mb-1 d-flex align-items-center">
                <i class="ri-arrow-right-s-line me-1 text-success"></i> rbn@framework:~$ cat <?= htmlspecialchars($filename) ?>
            </div>
            <div class="ps-3">
                <pre id="code-block" class="mb-0 text-success"
                    style="font-family: inherit; font-size: 0.82rem; line-height: 1.6; white-space: pre-wrap; word-break: break-all;"><?php 
                    if (is_array($content['data'] ?? null)) {
                        echo htmlspecialchars(json_encode($content['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                    } else {
                        echo htmlspecialchars($content['raw'] ?? (is_string($content) ? $content : ''));
                    }
                ?></pre>
            </div>
        <?php endif; ?>
    </div>

    <div class="d-flex justify-content-between align-items-center px-4 py-2.5 border-top" style="background: #1e293b;">
        <span class="rbn-badge rbn-badge-success rbn-badge-xs">Terminal Modu</span>
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
