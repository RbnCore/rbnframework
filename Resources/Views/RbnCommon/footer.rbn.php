@php
$projectKey = project_key() ?: 'default';
$layoutsDir = \Rbn\Framework\Core\System\Paths\Paths::project()->layouts("Footers");
$projectFooterFile = $layoutsDir . DIRECTORY_SEPARATOR . $projectKey . ".rbn.php";

if (!file_exists($projectFooterFile)) {
$files = glob($layoutsDir . DIRECTORY_SEPARATOR . "*.rbn.php");
$projectFooterFile = !empty($files) ? $files[0] : null;
}
@endphp

@if(!($no_footer ?? false))
@if($projectFooterFile && file_exists($projectFooterFile))
<?php require $this->compile($projectFooterFile); ?>
@endif

@if(!($no_bottom_bar ?? false))
<!-- RBN Core Framework bottom bar 🌐 -->
<div class="rbn-footer-bottom-bar">
    <div class="rbn-footer-bottom-container">
        <div class="rbn-footer-copyright-info">
            <span>&copy; {{ date('Y') }} {{ $appName }}. Tüm hakları saklıdır.</span>
            <div class="rbn-footer-links-row">
                <span class="rbn-footer-divider d-none d-md-inline">|</span>
                <a href="/sitemap.xml" target="_blank" class="rbn-footer-sitemap-link">Site Haritası</a>
                @if($hasFeed ?? ($siteData['hasFeed'] ?? false))
                <span class="rbn-footer-divider">|</span>
                <a href="/feed" target="_blank" class="rbn-footer-sitemap-link">RSS Feed</a>
                @endif
            </div>
        </div>
        <div class="rbn-footer-powered-by">
            <span class="rbn-footer-powered-opacity">Powered by</span>
            <a href="{{ $rbn_url }}" target="_blank" rel="nofollow noopener" class="rbn-footer-powered-link">
                {{ $rbn_name }}
            </a>
        </div>
    </div>
</div>
@endif
@endif

<!-- Scroll To Top Button -->
<button id="scrollToTopBtn" class="rbn-scroll-to-top rbn-scroll-top scroll-to-top scroll-top" aria-label="Yukarı Çık"></button>

<!-- RBN Universal Modal Shell 🪟 -->
@import('framework', 'Resources/Views/RbnCommon/Components/universal_modal.rbn.php')

<!-- RBN Legal Core: Cookie Consent -->
{!! $service('render')->render('legal') !!}

<!-- RBN Framework Dynamic Assets -->
{!! $footerAssets ?? '' !!}

<script>
    if (window.rbnProcessQueue) window.rbnProcessQueue();
</script>

{!! $footer_scripts ?? '' !!}
</body>

</html>
