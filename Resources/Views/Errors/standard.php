<?php
/** @var string $title @var string $message @var string|null $hint
 *  @var string $shield_name @var string $shield_version @var int $code */

/**
 * [R-10 / XSS] Bu gorsunumdeki `$message`, `$desc`, `$hint`, `$title`
 * degerleri istisna MESAJINDAN gelir ve istisna mesaji HTTP YOLUNU
 * ICERIR: `PageNotFoundException` -> "Page Not Found: /{$uri}".
 * Once bu degerler KACISLANMADAN basiliyordu; yani
 *   GET /yok-aaa<b>kutu-iceri</b>
 * HTTP 404 govdesine HAM `<b>` etiketi olarak yaziliyordu (olculdu:
 * `error-title">Page Not Found: /yok-aaa<b>kutu-iceri</b></h1>`).
 * Ayni sinifta `<script>` varyanti da yansidi.
 *
 * Bu, `panic.view.php` icin `1419737` (R-05) ile kapatilan acigin AYNI
 * KARSIGI: panik sayfasi kacisli, kardes hata sayfalari degil.
 *
 * Duzeltme: `htmlspecialchars(ENT_QUOTES, 'UTF-8')`. Bu ekranlar zaten
 * duz metin gostermek icin; hicbir yerde kasıtlı HTML girmezler.
 */
$rbnE = static fn($deger, string $yedek = ''): string => htmlspecialchars(
    (string) ($deger ?? $yedek),
    ENT_QUOTES | ENT_SUBSTITUTE,
    'UTF-8'
);
?>

<div class="error-card">

    <!-- Sol: Koyu Panel — Hata Kodu -->
    <div class="error-left">
        <div class="error-code-big"><?= $rbnE($code, '404') ?></div>
        <div class="error-code-label">Hata Kodu</div>
    </div>

    <!-- Sağ: İçerik -->
    <div class="error-right">

        <div class="error-status">
            <span class="status-dot"></span>
            <?= $rbnE($title, 'Sistem Hatası') ?>
        </div>

        <h1 class="error-title"><?= $rbnE($message, 'Bir sorun oluştu.') ?></h1>

        <p class="error-desc">
            <i class="ri-information-fill"></i>
            <?= $rbnE($desc, 'Bir sorun oluştu.') ?>
        </p>

        <?php if (!empty($hint)): ?>
            <div class="hint-box">
                <i class="ri-lightbulb-fill hint-icon"></i>
                <span><?= $rbnE($hint) ?></span>
            </div>
        <?php endif; ?>

        <div class="error-btn-group">
            <a href="/" class="error-btn error-btn-primary">
                <i class="ri-home-4-fill"></i> Anasayfa
            </a>
            <button onclick="window.history.back()" class="error-btn error-btn-secondary">
                <i class="ri-arrow-left-line"></i> Geri Dön
            </button>
        </div>

        <div class="footer-branding">
            <?= $rbnE($shield_name, 'RbnShield') ?> <?= $rbnE($shield_version, 'v2.1') ?>
        </div>

    </div>

</div>
