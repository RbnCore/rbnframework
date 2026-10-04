<?php
// If AdSense is disabled globally or publisher ID is empty, do not render anything
if (empty($adSettings['adsense_status']) || empty($adSettings['adsense_client_id'])) {
    return;
}

$position = $position ?? 'head';
$clientId = htmlspecialchars($adSettings['adsense_client_id']);

// Check if we are in local development environment
$isLocalDev = is_local();
?>

<?php if ($position === 'head'): ?>
    <!-- Google AdSense Core Script (Only loaded in production when window.rbnEnableAdBlockModal is set) -->
    <?php if (!$isLocalDev): ?>
        <script>
            window.rbnEnableAdBlockModal = true;
        </script>
        <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=<?= $clientId ?>" crossorigin="anonymous"></script>
    <?php endif; ?>

<?php elseif ($position === 'left_skyscraper' && !empty($adSettings['ads_slot_left_skyscraper'])): ?>
    <!-- Sol Yapışkan Dikey Reklam (Left Skyscraper) -->
    <?php if ($isLocalDev): ?>
        <div class="ds-banner ds-left">
            SOL PANEL<br>(160x600)
        </div>
    <?php else: ?>
        <div class="ds-banner ds-left">
            <ins class="adsbygoogle"
                 style="display:inline-block;width:160px;height:600px"
                 data-ad-client="<?= $clientId ?>"
                 data-ad-slot="<?= htmlspecialchars($adSettings['ads_slot_left_skyscraper']) ?>"></ins>
            <script>
                 (adsbygoogle = window.adsbygoogle || []).push({});
            </script>
        </div>
    <?php endif; ?>

<?php elseif ($position === 'right_skyscraper' && !empty($adSettings['ads_slot_right_skyscraper'])): ?>
    <!-- Sağ Yapışkan Dikey Reklam (Right Skyscraper) -->
    <?php if ($isLocalDev): ?>
        <div class="ds-banner ds-right">
            SAĞ PANEL<br>(160x600)
        </div>
    <?php else: ?>
        <div class="ds-banner ds-right">
            <ins class="adsbygoogle"
                 style="display:inline-block;width:160px;height:600px"
                 data-ad-client="<?= $clientId ?>"
                 data-ad-slot="<?= htmlspecialchars($adSettings['ads_slot_right_skyscraper']) ?>"></ins>
            <script>
                 (adsbygoogle = window.adsbygoogle || []).push({});
            </script>
        </div>
    <?php endif; ?>

<?php elseif ($position === 'feed' && !empty($adSettings['ads_slot_feed'])): ?>
    <!-- Akış İçi Grid Reklam Slotu (In-Feed Grid) -->
    <?php if ($isLocalDev): ?>
        <div class="ds-feed">
            AKIŞ İÇİ REKLAM ALANI
        </div>
    <?php else: ?>
        <div class="my-4 text-center ad-container ad-feed">
            <ins class="adsbygoogle"
                 style="display:block"
                 data-ad-format="fluid"
                 data-ad-layout-key="-fb+5w+4e-db+86"
                 data-ad-client="<?= $clientId ?>"
                 data-ad-slot="<?= htmlspecialchars($adSettings['ads_slot_feed']) ?>"></ins>
            <script>
                 (adsbygoogle = window.adsbygoogle || []).push({});
            </script>
        </div>
    <?php endif; ?>

<?php elseif ($position === 'sidebar' && !empty($adSettings['ads_slot_sidebar'])): ?>
    <!-- Kenar Çubuğu Reklam Slotu (Sidebar) -->
    <?php if ($isLocalDev): ?>
        <div class="ds-sidebar">
            YAN PANEL REKLAM ALANI<br>(300x250 veya Duyarlı)
        </div>
    <?php else: ?>
        <div class="ds-sidebar-wrapper">
            <ins class="adsbygoogle"
                 style="display:block"
                 data-ad-client="<?= $clientId ?>"
                 data-ad-slot="<?= htmlspecialchars($adSettings['ads_slot_sidebar']) ?>"
                 data-ad-format="auto"
                 data-full-width-responsive="true"></ins>
            <script>
                 (adsbygoogle = window.adsbygoogle || []).push({});
            </script>
        </div>
    <?php endif; ?>

<?php elseif ($position === 'content_top' && !empty($adSettings['ads_slot_content_top'])): ?>
    <!-- Yazı Başı Reklam Slotu (Content Top) -->
    <?php if ($isLocalDev): ?>
        <div class="ds-content-top">
            YAZI BAŞI REKLAM ALANI
        </div>
    <?php else: ?>
        <div class="my-4 text-center ad-container ad-content-top">
            <ins class="adsbygoogle"
                 style="display:block"
                 data-ad-client="<?= $clientId ?>"
                 data-ad-slot="<?= htmlspecialchars($adSettings['ads_slot_content_top']) ?>"
                 data-ad-format="auto"
                 data-full-width-responsive="true"></ins>
            <script>
                 (adsbygoogle = window.adsbygoogle || []).push({});
            </script>
        </div>
    <?php endif; ?>

<?php elseif ($position === 'content_bottom' && !empty($adSettings['ads_slot_content_bottom'])): ?>
    <!-- Yazı Sonu Reklam Slotu (Content Bottom) -->
    <?php if ($isLocalDev): ?>
        <div class="ds-content-bottom">
            YAZI SONU REKLAM ALANI<br>(Yatay Banner veya Duyarlı)
        </div>
    <?php else: ?>
        <div class="my-4 text-center ad-container ad-content-bottom">
            <ins class="adsbygoogle"
                 style="display:block"
                 data-ad-client="<?= $clientId ?>"
                 data-ad-slot="<?= htmlspecialchars($adSettings['ads_slot_content_bottom']) ?>"
                 data-ad-format="auto"
                 data-full-width-responsive="true"></ins>
            <script>
                 (adsbygoogle = window.adsbygoogle || []).push({});
            </script>
        </div>
    <?php endif; ?>
<?php endif; ?>
