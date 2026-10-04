<?php
/** @var string $policyUrl */
/** @var string $consentKey */
?>

<div id="rbnCookieNotice" class="rbn-cookie-bar">
    <div class="rbn-cookie-content">
        <i class="ri-cookie-line me-2"></i>
        Size daha iyi bir deneyim sunabilmek için çerezleri kullanıyoruz. Sitemizi kullanarak <a
            href="<?= $policyUrl ?>"<?= isset($cookieDisabled) && $cookieDisabled ? ' target="_blank" rel="noopener noreferrer"' : '' ?>>çerez politikamızı</a> kabul etmiş sayılırsınız.
    </div>
    <div class="rbn-cookie-actions">
        <button type="button" id="rbnAcceptCookies" class="rbn-cookie-btn">Kabul Et ve Kapat</button>
    </div>
</div>
