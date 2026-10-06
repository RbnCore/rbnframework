<?php
/**
 * Rbn Framework - Backend Footer Component
 * Hallmark · component: rbn-footer · genre: modern-minimal
 */
?>

</main>
</div> <!-- .rbn-dash-main-scroll end -->

<!-- Admin/Backend Footer Content (At Bottom) -->
<footer class="rbn-dash-footer d-flex justify-content-between align-items-center px-4">
    <div class="text-muted small">
        &copy; <?= date('Y') ?> <a href="@sys('FRAMEWORK_URL')" target="_blank"
            class="text-muted text-decoration-none fw-semibold">@sys('FRAMEWORK_NAME')</a>
        v@sys('FRAMEWORK_VERSION')
    </div>
    <div class="text-muted d-flex align-items-center gap-2">
        <span class="small opacity-75">Created by</span>
        <a href="@sys('DEVELOPER_URL')" target="_blank" class="rbn-footer-developer">
            @sys('DEVELOPER_NAME')
        </a>
    </div>
</footer>

</div> <!-- .rbn-dash-content end -->
</div> <!-- .rbn-dash-layout end -->

@import('framework', 'Resources/Views/RbnAdmin/Components/server_health_drawer.rbn.php')
@import('framework', 'Resources/Views/RbnCommon/Components/universal_modal.rbn.php')
<?php $isTemplate = true; ?>
@import('framework', 'Resources/Views/System/redirect_loading.rbn.php')

<!-- Scroll To Top Button 🚀 (rbnDom.js rbnInitScrollToTop uyumlu) -->
<button type="button" id="scrollToTopBtn" class="rbn-scroll-top rbn-scroll-top-btn" data-rbn-scroll-top aria-label="Yukarı Çık" title="Yukarı Çık">
    <i class="ri-arrow-up-line"></i>
</button>

<div class="sidebar-overlay d-md-none" onclick="rbnAdminToggleSidebar()"></div>

<script>
    // RBN Sovereign Security Bridge 🛰️⚓
    window.RBN_SECURITY = <?= json_encode($rbnSecurity ?? []) ?>;
</script>

<!-- App Dynamic Assets (JS) -->
<?= $footerAssets ?? '' ?>

<!-- Dynamic Script Initialization -->
<script>
    if (window.rbnProcessQueue) window.rbnProcessQueue();
</script>

</body>

</html>