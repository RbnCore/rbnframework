<!-- Sovereign System & Server Health Deck 🚀🖥️ (Powered by RBN Core CSS) -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="serverHealthCanvas" aria-labelledby="serverHealthLabel">
    
    <!-- Offcanvas Header -->
    <div class="offcanvas-header">
        <div class="d-flex align-items-center gap-2">
            <i class="ri-server-line text-primary fs-5"></i>
            <div>
                <h5 class="offcanvas-title fw-bold mb-0" id="serverHealthLabel">Sunucu Durumu</h5>
                <span class="text-muted small">RBN Sovereign Runtime Radar</span>
            </div>
        </div>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Kapat"></button>
    </div>

    <!-- Offcanvas Body -->
    <div class="offcanvas-body">
        <?php
        $phpVersion    = PHP_VERSION;
        $phpMemory     = ini_get('memory_limit');
        $maxUpload     = ini_get('upload_max_filesize');
        $postMax       = ini_get('post_max_size');
        $maxExecution  = ini_get('max_execution_time') . 's';
        $software      = $_SERVER['SERVER_SOFTWARE'] ?? 'PHP CLI / Built-in';
        $os            = PHP_OS_FAMILY . ' (' . php_uname('m') . ')';
        $ip            = $_SERVER['SERVER_ADDR'] ?? gethostbyname(gethostname());
        $dbDriver      = defined('PDO::ATTR_DRIVER_NAME') ? 'MySQL / MariaDB' : 'Database Active';
        $opcacheActive = function_exists('opcache_get_status') && !empty(opcache_get_status(false)['opcache_enabled']);
        ?>

        <!-- 1. SUNUCU KİMLİĞİ VE CANLI RADAR -->
        <div class="rbn-card mb-3 p-3">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="rbn-badge rbn-badge-success">
                    <span class="rbn-status-dot rbn-status-dot-emerald rbn-status-pulse"></span>
                    Sunucu Çevrimiçi
                </span>
                <span class="text-muted small font-monospace"><?= htmlspecialchars((string) $ip) ?></span>
            </div>
            <div class="fw-bold text-truncate mb-1" title="<?= htmlspecialchars((string) $software) ?>">
                <?= htmlspecialchars((string) $software) ?>
            </div>
            <div class="text-muted small d-flex align-items-center gap-1">
                <i class="ri-terminal-window-line"></i>
                <span><?= htmlspecialchars((string) $os) ?></span>
            </div>
        </div>

        <!-- 2. PHP RUNTIME VE STAT KARTLARI -->
        <div class="d-flex align-items-center justify-content-between mb-2 mt-3">
            <span class="rbn-page-eyebrow mb-0">PHP RUNTIME</span>
            <span class="rbn-badge rbn-badge-terracotta">v<?= $phpVersion ?></span>
        </div>

        <div class="row g-2 mb-3">
            <div class="col-6">
                <div class="rbn-card p-2.5 h-100 justify-content-center">
                    <span class="text-muted small">Bellek Limiti</span>
                    <div class="fw-bold fs-6 mt-1 d-flex align-items-center gap-1 text-primary">
                        <i class="ri-cpu-line"></i>
                        <span><?= $phpMemory ?></span>
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="rbn-card p-2.5 h-100 justify-content-center">
                    <span class="text-muted small">Maks. Yükleme</span>
                    <div class="fw-bold fs-6 mt-1 d-flex align-items-center gap-1 text-success">
                        <i class="ri-upload-cloud-line"></i>
                        <span><?= $maxUpload ?></span>
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="rbn-card p-2.5 h-100 justify-content-center">
                    <span class="text-muted small">Zaman Aşımı</span>
                    <div class="fw-bold fs-6 mt-1 d-flex align-items-center gap-1 text-danger">
                        <i class="ri-timer-line"></i>
                        <span><?= $maxExecution ?></span>
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="rbn-card p-2.5 h-100 justify-content-center">
                    <span class="text-muted small">POST Limiti</span>
                    <div class="fw-bold fs-6 mt-1 d-flex align-items-center gap-1 text-warning">
                        <i class="ri-file-upload-line"></i>
                        <span><?= $postMax ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. SERVİSLER VE VERİTABANI MOTORU -->
        <span class="rbn-page-eyebrow mb-2 mt-3">SERVİSLER & MOTOR</span>
        <div class="rbn-card p-0 overflow-hidden mb-4">
            <div class="p-3 d-flex align-items-center justify-content-between border-bottom">
                <div class="d-flex align-items-center gap-2 small">
                    <i class="ri-database-2-line text-primary"></i>
                    <span class="fw-medium">Veritabanı</span>
                </div>
                <span class="rbn-badge rbn-badge-primary"><?= $dbDriver ?></span>
            </div>

            <div class="p-3 d-flex align-items-center justify-content-between border-bottom">
                <div class="d-flex align-items-center gap-2 small">
                    <i class="ri-flashlight-line text-warning"></i>
                    <span class="fw-medium">OPcache</span>
                </div>
                <span class="rbn-badge <?= $opcacheActive ? 'rbn-badge-success' : 'rbn-badge-warning' ?>">
                    <?= $opcacheActive ? 'Aktif' : 'Devre Dışı' ?>
                </span>
            </div>

            <div class="p-3 d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2 small">
                    <i class="ri-shield-check-line text-primary"></i>
                    <span class="fw-medium">Framework</span>
                </div>
                <span class="rbn-badge rbn-badge-terracotta">RBN v@sys('FRAMEWORK_VERSION')</span>
            </div>
        </div>

        <!-- 4. HIZLI ERİŞİM BUTONU -->
        @hasrole(['developer', 'superadmin'])
        <a href="<?= $Route->url('syshub', 'developer') ?>" class="rbn-btn rbn-btn-primary w-100 justify-content-center">
            <i class="ri-dashboard-3-line"></i> Syshub Konsoluna Git
        </a>
        @endhasrole
    </div>
</div>
