<?php
/**
 * [R-10 / XSS] Survival gorunumu. `$message` ve `$hint` istisna
 * METNINDEN gelir; istisna metni HTTP yolunu icerir
 * (`PageNotFoundException` / `ViewNotFoundException`). Once KACISLANMADAN
 * basiliyordu — `panic.view.php` icin `1419737` (R-05) ile kapatilan acigin
 * ayni sinifi. Duz metin ekran; girerken HTML gelmez.
 *
 * `$copyText` ise `json_encode` ile JS literali olarak basilir; bu yuzden
 * HARFEN BIRAKILIR (json_encode `<` `>` yerine unicode kacis koyar, `<script>`
 * kapanisi icin yeterlidir).
 */
$rbnE = static fn($deger, string $yedek = ''): string => htmlspecialchars(
    (string) ($deger ?? $yedek),
    ENT_QUOTES | ENT_SUBSTITUTE,
    'UTF-8'
);
?>
<div class="survival-container">
    <h1>[ <?= $rbnE($shieldName ?? null, 'RbnShield') ?> CRITICAL SYSTEM FAILURE ]</h1>
    <div class="survival-type"><?= $rbnE($type ?? null, 'Critical Error') ?></div>
    <div class="survival-msg"><?= $rbnE($message ?? null, 'A fatal error occurred.') ?></div>
    <div class="survival-hint-box"><b>DIAGNOSTIC REPORT</b><br><?= $rbnE($hint ?? null, 'No details available.') ?></div>
    <div class="survival-footer">
        <div><?= $rbnE($shieldName ?? null, 'RbnShield') ?> Engine <?= $rbnE($shieldVersion ?? null, 'v2.1') ?> <span style="opacity: 0.5; margin-left: 10px;">[ <?= $rbnE($now ?? null, date('Y-m-d H:i:s')) ?> ]</span></div>
        <div class="survival-actions">
            <button class="survival-btn" id="copyBtn" onclick="copySurvivalError()">Hatayı Kopyala</button>
            <button class="survival-btn" onclick="location.reload()">Yeniden Başlat</button>
        </div>
    </div>
</div>

<script>
    // 🎯 RBN 3.5: Masterpiece Global Diagnostic Payload 📼
    // Used by survival.js theme asset.
    window.survivalDiagnosticText = <?= json_encode($copyText ?? '') ?>;
</script>
