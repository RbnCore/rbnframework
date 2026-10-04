<?php
// FW-A0-K1-DEBUG-KAPISI-99 (K-2): GET/POST verisi basilmadan ONCE hassas alanlar
// maskelenir. Kaynak: PreBoot::maskSensitiveData() (TEK merkez, sozel anahtar listesi).
$rbnMask = static function (array $data): array {
    if (class_exists(\Rbn\Framework\Core\System\Kernel\Base\PreBoot::class)) {
        return \Rbn\Framework\Core\System\Kernel\Base\PreBoot::maskSensitiveData($data);
    }

    // PreBoot yuklenemezse (erken boot hatasi) fail-closed: gosterme.
    return [];
};
$rbnGetSafe = $rbnMask($_GET);
$rbnPostSafe = $rbnMask($_POST);
?>
<div class="dev-error-container">
    <!-- 🟥 Red Header -->
    <div class="dev-error-header d-flex justify-content-between align-items-center">
        <div class="dev-header-content pe-4">
            <h1>
                <span style="font-size: 1.5em; margin-right: 0.5rem; vertical-align: middle;">⚠️</span>
                Development Error Detected
            </h1>
            <p class="dev-error-message mb-0" id="mainErrorMessage">
                <?= htmlspecialchars((string) ($message ?? $errorMessage ?? 'Beklenmedik bir hata oluştu.')) ?>
            </p>
        </div>
        <div class="dev-header-action">
            <button class="dev-header-copy-btn" data-rbn-copy="#mainErrorMessage" data-tooltip="Hata mesajını panoya kopyala">
                <i class="ri-file-copy-2-line"></i> <span>Copy</span>
            </button>
        </div>
    </div>

    <div class="dev-error-body">
        <!-- ⬜ White Body: Error Meta Information -->
        <?php if (isset($exception)): ?>
            <div class="dev-error-meta">
                <div class="dev-error-meta-item">
                    <span><i class="ri-file-code-line"></i> <strong>File:</strong></span>
                    <code><?= htmlspecialchars((string) ($exception->getFile())) ?></code>
                </div>
                <div class="dev-error-meta-item">
                    <span><i class="ri-bug-fill"></i> <strong>Type:</strong></span>
                    <code><?= htmlspecialchars((string) (get_class($exception))) ?></code>
                </div>
                <div class="dev-error-meta-item">
                    <span><i class="ri-hashtag"></i> <strong>Line:</strong></span>
                    <code><?= htmlspecialchars((string) ($exception->getLine())) ?></code>
                </div>
            </div>
        <?php endif; ?>

        <!-- ⚡ Quick Actions -->
        <div class="dev-quick-actions">
            <button class="rbn-btn rbn-btn-success" onclick="location.reload()">
                <i class="ri-refresh-line"></i> Retry
            </button>
            <button class="rbn-btn rbn-btn-secondary" onclick="window.history.back()">
                <i class="ri-arrow-left-line"></i> Go Back
            </button>
            <a href="/admin" class="rbn-btn rbn-btn-primary">
                <i class="ri-home-4-line"></i> Dashboard
            </a>
            <button class="rbn-btn rbn-btn-cyan ms-auto" data-rbn-copy=".dev-error-container">
                <i class="ri-file-copy-2-line"></i> Copy Full Report
            </button>
        </div>

        <!-- 📜 Stack Trace Section -->
        <?php if (isset($exception)): ?>
            <div class="dev-section">
                <div class="dev-section-title" data-bs-toggle="collapse" data-bs-target="#stackTrace">
                    <span><i class="ri-list-check-2"></i> Stack Trace Diagnosis</span>
                    <i class="ri-arrow-down-s-line toggle-icon"></i>
                </div>
                <div id="stackTrace" class="dev-section-content collapse show">
                    <div class="position-relative">
                        <div class="dev-stack-trace">
                            <?php $trace = $exception->getTrace(); ?>
                            <?php foreach ($trace as $index => $item): ?>
                                <div class="dev-stack-trace-line">
                                    <strong>#<?= htmlspecialchars((string) ($index)) ?></strong>
                                    <?php if (isset($item['file'])): ?>
                                        <span class="dev-stack-trace-file"><?= htmlspecialchars((string) ($item['file'])) ?></span>:<?= htmlspecialchars((string) ($item['line'])) ?>
                                    <?php endif; ?>
                                    <br>&nbsp;&nbsp;➔ <?= htmlspecialchars((string) (($item['class'] ?? '') . ($item['type'] ?? '') . $item['function'])) ?>()
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- 📊 Request Data Section -->
        <div class="dev-section">
            <div class="dev-section-title collapsed" data-bs-toggle="collapse" data-bs-target="#requestData">
                <span><i class="ri-database-2-line"></i> Request Data Environment</span>
                <i class="ri-arrow-down-s-line toggle-icon"></i>
            </div>
            <div id="requestData" class="dev-section-content collapse">
                <h6 class="text-danger fw-bold mb-3"><i class="ri-links-line"></i> GET Parameters</h6>
                <?php if (!empty($rbnGetSafe)): ?>
                    <table class="dev-data-table">
                        <tbody>
                            <?php foreach ($rbnGetSafe as $key => $value): ?>
                                <tr>
                                    <td class="key"><?= htmlspecialchars((string) ($key)) ?></td>
                                    <td class="value"><?= htmlspecialchars((string) (is_array($value) ? json_encode($value) : $value)) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">No GET parameters found.</div>
                <?php endif; ?>

                <h6 class="text-danger fw-bold mt-4 mb-3"><i class="ri-send-plane-fill"></i> POST Parameters</h6>
                <?php if (!empty($rbnPostSafe)): ?>
                    <table class="dev-data-table">
                        <tbody>
                            <?php foreach ($rbnPostSafe as $key => $value): ?>
                                <tr>
                                    <td class="key"><?= htmlspecialchars((string) ($key)) ?></td>
                                    <td class="value"><?= htmlspecialchars((string) (is_array($value) ? json_encode($value) : $value)) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">No POST parameters found.</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- 🧪 Server Information Section -->
        <div class="dev-section">
            <div class="dev-section-title collapsed" data-bs-toggle="collapse" data-bs-target="#serverInfo">
                <span><i class="ri-server-line"></i> Server Intelligence</span>
                <i class="ri-arrow-down-s-line toggle-icon"></i>
            </div>
            <div id="serverInfo" class="dev-section-content collapse">
                <table class="dev-data-table">
                    <tbody>
                        <tr><td class="key">Request URI</td><td class="value"><?= htmlspecialchars((string) ($_SERVER['REQUEST_URI'] ?? 'N/A')) ?></td></tr>
                        <tr><td class="key">Request Method</td><td class="value"><?= htmlspecialchars((string) ($_SERVER['REQUEST_METHOD'] ?? 'N/A')) ?></td></tr>
                        <tr><td class="key">User Agent</td><td class="value"><?= htmlspecialchars((string) ($_SERVER['HTTP_USER_AGENT'] ?? 'N/A')) ?></td></tr>
                        <tr><td class="key">Memory Usage</td><td class="value"><?= htmlspecialchars((string) (round(memory_get_usage() / 1024 / 1024, 2))) ?> MB</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
