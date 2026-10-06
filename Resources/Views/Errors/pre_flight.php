<?php
/**
 * [R-10 / XSS] `$error_type` ve `$error_message` istisna/preflight metninden
 * gelir ve HTTP yolunu icerebilir (`ViewNotFoundException` -> mutlak yol).
 * Once KACISLANMADAN basiliyordu; `panic.view.php` icin `1419737` (R-05) ile
 * kapatilan acigin ayni sinifi. Duz metin ekran; HTML girmez.
 */
$rbnE = static fn($deger, string $yedek = ''): string => htmlspecialchars(
    (string) ($deger ?? $yedek),
    ENT_QUOTES | ENT_SUBSTITUTE,
    'UTF-8'
);
?>
<!-- 🎯 Obsidian Edge Diagnostic View 🏹🪐🛡️ -->
<div class="row justify-content-center">
    <div class="col-lg-12 d-flex justify-content-center">

        <!-- 🛡️ Core Shield Layout 🕯️ -->
        <div class="error-card text-center" data-aos="zoom-in">

                <!-- 🛰️ Premium Icon Header -->
                <!-- 🛰️ Premium Icon Header (Top) -->
                <div class="mb-5" data-aos="fade-down" data-aos-delay="200">
                    <div class="d-inline-flex align-items-center justify-content-center mb-3"
                        style="width: 100px; height: 100px; background: rgba(16, 185, 129, 0.1); border-radius: 30px; border: 1px solid rgba(16, 185, 129, 0.2);">
                        <i class="ri-shield-keyhole-fill text-success"
                            style="font-size: 3.5rem; filter: drop-shadow(0 0 15px rgba(16, 185, 129, 0.4));"></i>
                    </div>
                    <div
                        style="color: #fbbf24; font-weight: 800; text-transform: uppercase; letter-spacing: 0.2em; font-size: 0.8rem;">
                        <?= $branding['shield_name'] ?? 'RbnShield' ?> Pre-Flight
                    </div>
                </div>

                <!-- 🏷️ Titles -->
                <div class="mb-5">
                    <h1 class="error-title" data-aos="fade-left" data-aos-delay="400">
                        <?= $rbnE($error_type ?? null, 'Sistem Uyarısı') ?>
                    </h1>
                    <p class="error-desc mx-auto" data-aos="fade-right" data-aos-delay="600">
                        <?= $rbnE($error_message ?? null, 'Sistemin başlatılması sırasında kritik bir eksiklik veya yapılandırma hatası tespit edildi.') ?>
                    </p>
                </div>

                <!-- 🕵️‍♂️ Diagnostic Detail Box (The Code Block) -->
                <div class="error-detail-box text-start mb-5" data-aos="fade-up" data-aos-delay="800">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <i class="ri-terminal-box-fill text-success"></i>
                        <span class="detail-label">DIAGNOSTIC_REPORT_STUB</span>
                    </div>
                    <div class="detail-content">
                        <?= nl2br(htmlspecialchars((string) ($solution_hint ?? 'Yapılandırma dosyası (.env veya project-settings.php) bulunamadı.'))) ?>
                    </div>
                </div>

                <div class="d-flex flex-md-nowrap flex-wrap justify-content-center gap-3" data-aos="fade-up"
                    data-aos-delay="1000">
                    <button onclick="window.location.reload();" class="btn error-btn d-flex align-items-center gap-2">
                        <i class="ri-refresh-line"></i>
                        Sistemi Yeniden Başlat
                    </button>
                    <button data-rbn-copy=".detail-content" class="btn error-btn error-btn-secondary d-flex align-items-center gap-2">
                        <i class="ri-clipboard-line"></i>
                        Teşhisi Kopyala
                    </button>
                    <button data-rbn-copy=".error-desc" class="btn error-btn error-btn-secondary d-flex align-items-center gap-2">
                        <i class="ri-lightbulb-line"></i>
                        Öneriyi Kopyala
                    </button>
                </div>

                <!-- 🏛️ Footer Branding -->
                <div class="mt-5 pt-5 border-top" style="border-color: rgba(255,255,255,0.05) !important;"
                    data-aos="fade-in" data-aos-delay="1200">
                    <div class="d-flex align-items-center justify-content-center gap-3">
                        <span
                            style="color: rgba(255,255,255,0.2); font-weight: 700; letter-spacing: 0.1em; font-size: 0.7rem;">
                            <?= $branding['shield_name'] ?? 'RbnShield' ?> <?= $branding['shield_version'] ?? 'v2.1' ?>
                        </span>
                        <span style="color: rgba(255,255,255,0.1);">|</span>
                        <span style="color: rgba(255,255,255,0.3); font-size: 0.7rem;">
                            Crafted by <span style="color: #ffffff; font-weight: 700;"> <?= $branding['author'] ?? 'RbnBilisim' ?></span>
                        </span>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
