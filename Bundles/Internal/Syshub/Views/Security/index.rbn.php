<?php
/** @var array $stats */
/** @var array $recentAttempts */
?>

<!-- 📊 1. GÜVENLİK METRİK & SAĞLIK KOKPİTİ -->
<div class="row g-4 mb-4">
    <!-- Sol Taraf: Sağlık Skoru & 4 Küçük Metrik -->
    <div class="col-12 col-lg-8">
        <!-- Sağlık Skoru Kartı (Yatay Düzen: Skor Solda, Açıklamalar Sağda) -->
        <div class="rbn-card p-4 mb-4">
            <div class="d-flex align-items-center gap-4">
                <!-- Sol Taraf: Dairesel Skor -->
                <div class="flex-shrink-0 text-center">
                    <div class="position-relative d-inline-block" style="width: 90px; height: 90px;">
                        <svg width="90" height="90" viewBox="0 0 100 100" style="transform: rotate(-90deg);">
                            <circle cx="50" cy="50" r="42" fill="transparent" stroke="rgba(60, 50, 40, 0.08)" stroke-width="8" />
                            <circle cx="50" cy="50" r="42" fill="transparent"
                                stroke="<?= $stats['health']['score'] >= 75 ? '#16a34a' : ($stats['health']['score'] >= 50 ? '#d97706' : '#dc2626') ?>"
                                stroke-width="8" stroke-dasharray="263.89"
                                stroke-dashoffset="<?= 263.89 - (263.89 * $stats['health']['score'] / 100) ?>"
                                stroke-linecap="round" />
                        </svg>
                        <div class="position-absolute top-50 start-50 translate-middle text-center">
                            <span class="d-block fw-bold fs-4 mb-0 font-monospace text-dark"><?= $stats['health']['score'] ?></span>
                            <span class="ra-stat-label d-block" style="font-size: 0.6rem;">SKOR</span>
                        </div>
                    </div>
                </div>

                <!-- Sağ Taraf: Detaylar ve Başlık -->
                <div class="flex-grow-1 min-w-0">
                    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                        <div>
                            <div class="ra-stat-label">GÜVENLİK SAĞLIK DURUMU</div>
                            <h4 class="fw-bold text-dark fs-5 mb-0" style="font-family: var(--rbn-font-body, inherit);"><?= $stats['health']['status'] ?></h4>
                        </div>
                        <span class="rbn-badge <?= $stats['health']['score'] >= 75 ? 'rbn-badge-success' : ($stats['health']['score'] >= 50 ? 'rbn-badge-warning' : 'rbn-badge-danger') ?> rbn-badge-sm">
                            <?= $stats['health']['score'] ?>% Güvenli
                        </span>
                    </div>
                    <div class="row g-2 mt-1">
                        <?php foreach (array_slice($stats['health']['details'], 0, 2) as $detail): ?>
                            <div class="col-12 col-md-6">
                                <div class="d-flex align-items-center small text-muted">
                                    <i class="ri-checkbox-circle-line text-success me-1.5 flex-shrink-0"></i>
                                    <span class="text-truncate"><?= htmlspecialchars($detail['msg'] ?? '') ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4'lü İstatistik Izgarası -->
        <div class="row g-3">
            <div class="col-6 col-md-3">
                <div class="ra-stat-card ra-stat-danger h-100 p-3">
                    <div class="ra-stat-label" style="font-size: 0.65rem;">AKTİF ENGEL</div>
                    <h3 class="ra-stat-value fs-5 font-monospace mb-0"><?= $stats['active_blocks'] ?> IP</h3>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="ra-stat-card ra-stat-navy h-100 p-3">
                    <div class="ra-stat-label" style="font-size: 0.65rem;">24S DENEME</div>
                    <h3 class="ra-stat-value fs-5 font-monospace mb-0"><?= number_format($stats['total_attempts_24h']) ?></h3>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="ra-stat-card ra-stat-terracotta h-100 p-3">
                    <div class="ra-stat-label" style="font-size: 0.65rem;">TEKİL IP</div>
                    <h3 class="ra-stat-value fs-5 font-monospace mb-0"><?= number_format($stats['unique_ips_24h']) ?></h3>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="ra-stat-card ra-stat-success h-100 p-3">
                    <div class="ra-stat-label" style="font-size: 0.65rem;">GÜVENLİ LİSTE</div>
                    <h3 class="ra-stat-value fs-5 font-monospace mb-0"><?= $stats['total_whitelisted'] ?> IP</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Sağ Taraf: Firewall Hızlı Ayar Kartı -->
    <div class="col-12 col-lg-4">
        <div class="rbn-card h-100 p-4 text-center d-flex flex-column justify-content-between">
            <div class="d-flex flex-column align-items-center">
                <div class="ra-stat-icon mx-auto mb-3" style="width: 56px; height: 56px; background: rgba(197, 106, 60, 0.08); color: var(--rbn-primary, #c56a3c); font-size: 1.65rem;">
                    <i class="ri-shield-keyhole-line"></i>
                </div>
                <h5 class="fw-bold text-dark mb-1" style="font-family: var(--rbn-font-body, inherit);">Firewall Ayarları</h5>
                <p class="text-muted small lh-base mb-3">Sistem genelindeki gelişmiş güvenlik, koruma kalkanı ve güvenlik duvarı kurallarını yönetin.</p>
            </div>

            <a href="<?= $Route->url('syshub/security/firewall', 'developer') ?>"
                class="rbn-btn rbn-btn-primary w-100 rounded-pill py-2.5 justify-content-center">
                <i class="ri-settings-4-line"></i> Firewall'u Yapılandır
            </a>
        </div>
    </div>
</div>

<!-- 🎛️ 2. GÜVENLİK ALT MODÜLLERİ (3 Eşit Sovereign Kart) -->
<div class="row g-4 mb-4">
    <!-- 1. Son İzinsiz Denemeler -->
    <div class="col-12 col-md-4">
        <div class="rbn-card h-100 p-4 d-flex flex-column justify-content-between">
            <div>
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="ra-stat-icon flex-shrink-0" style="background: rgba(37, 99, 235, 0.1); color: #2563eb;">
                        <i class="ri-pulse-line fs-4"></i>
                    </div>
                    <div>
                        <div class="fw-bold fs-6 text-dark" style="font-family: var(--rbn-font-body, inherit);">İzinsiz Denemeler</div>
                        <span class="rbn-badge rbn-badge-neutral rbn-badge-xs">Hız & İstek Limitleri</span>
                    </div>
                </div>
                <p class="text-muted small lh-base mb-4" style="min-height: 38px;">
                    Uygulama genelindeki kısıtlı işlem denemelerini ve IP bazlı hız limitlerini detaylıca inceleyin.
                </p>
            </div>
            <a href="<?= $Route->url('syshub/security/ratelimits', 'developer') ?>"
                class="rbn-btn rbn-btn-outline w-100 rounded-pill rbn-btn-sm justify-content-center">
                <i class="ri-history-line"></i> Geçmişi İncele
            </a>
        </div>
    </div>

    <!-- 2. IP Engelleri -->
    <div class="col-12 col-md-4">
        <div class="rbn-card h-100 p-4 d-flex flex-column justify-content-between">
            <div>
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="ra-stat-icon flex-shrink-0" style="background: rgba(220, 38, 38, 0.1); color: #dc2626;">
                        <i class="ri-forbid-2-line fs-4"></i>
                    </div>
                    <div>
                        <div class="fw-bold fs-6 text-dark" style="font-family: var(--rbn-font-body, inherit);">IP Engelleme</div>
                        <span class="rbn-badge rbn-badge-danger rbn-badge-xs">Kara Liste</span>
                    </div>
                </div>
                <p class="text-muted small lh-base mb-4" style="min-height: 38px;">
                    Yasaklanmış IP adreslerini görüntüleyin, engelleme sürelerini düzenleyin veya erişim yasağını kaldırın.
                </p>
            </div>
            <a href="<?= $Route->url('syshub/security/ipblock', 'developer') ?>"
                class="rbn-btn rbn-btn-outline w-100 rounded-pill rbn-btn-sm justify-content-center text-danger">
                <i class="ri-shield-cross-line"></i> Engelleri Yönet
            </a>
        </div>
    </div>

    <!-- 3. Güvenli Liste -->
    <div class="col-12 col-md-4">
        <div class="rbn-card h-100 p-4 d-flex flex-column justify-content-between">
            <div>
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="ra-stat-icon flex-shrink-0" style="background: rgba(22, 163, 74, 0.1); color: #16a34a;">
                        <i class="ri-shield-check-line fs-4"></i>
                    </div>
                    <div>
                        <div class="fw-bold fs-6 text-dark" style="font-family: var(--rbn-font-body, inherit);">Güvenli Liste</div>
                        <span class="rbn-badge rbn-badge-success rbn-badge-xs">Beyaz Liste</span>
                    </div>
                </div>
                <p class="text-muted small lh-base mb-4" style="min-height: 38px;">
                    Güvenilen statik IP adreslerini tanımlayın ve firewall kısıtlamalarından muaf tutun.
                </p>
            </div>
            <a href="<?= $Route->url('syshub/security/ipwhitelist', 'developer') ?>"
                class="rbn-btn rbn-btn-outline w-100 rounded-pill rbn-btn-sm justify-content-center text-success">
                <i class="ri-checkbox-circle-line"></i> İzinli IP Listesi
            </a>
        </div>
    </div>
</div>
