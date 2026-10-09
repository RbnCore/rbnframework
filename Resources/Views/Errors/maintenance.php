<div class="error-card" data-aos="zoom-in">
    <!-- Icon -->
    <div class="error-icon-placeholder">
        <i class="ri-tools-fill"></i>
    </div>

    <h2 class="error-title">Kısa Bir Mola</h2>

    <div class="error-badge">Bakım Modu</div>

    <p class="error-desc">
        <?= htmlspecialchars((string) ($maintenance_message ?? 'Sistemimiz şu anda bakımdadır.')) ?>
    </p>

    <!-- Footer Info -->
    <div class="mt-5 pt-4 border-top">
        <div class="d-flex align-items-center justify-content-center gap-2 text-muted small">
            <span class="fw-bold"><?= htmlspecialchars((string) ($shieldName ?? 'RbnShield')) ?></span>
            <span
                class="badge bg-soft-secondary text-secondary rounded-pill px-2">v<?= htmlspecialchars((string) ($shieldVersion ?? '')) ?></span>
            <span class="mx-2 text-silver opacity-25">|</span>
            <span>Created by <a href="<?= htmlspecialchars((string) ($seoMeta['author-url'] ?? '#')) ?>" target="_blank"
                    class="text-secondary text-decoration-none fw-bold"><?= htmlspecialchars((string) ($seoMeta['author'] ?? 'RbnBilisim')) ?></a></span>
        </div>
    </div>
</div>
