<?php
/** @var array $config */
?>

<form action="<?= $Route->url('syshub/security/firewall/updatesetting', 'developer') ?>" method="POST" id="firewallSettingsForm">
    @csrf
    <div class="row g-4">
        <!-- Sol Taraf: Ana Koruma Ayarları & Eşik Değerleri -->
        <div class="col-12 col-lg-8">
            <!-- 1. Aktif Koruma Katmanları (Switches) -->
            <div class="rbn-card p-0 overflow-hidden mb-4">
                <div class="px-4 py-3 border-bottom d-flex align-items-center gap-2 bg-light">
                    <i class="ri-shield-check-line text-success fs-5"></i>
                    <span class="fw-bold fs-6 text-dark" style="font-family: var(--rbn-font-body, inherit);">Aktif Koruma Katmanları</span>
                </div>
                <div class="p-4">
                    <!-- IP Guard -->
                    <div class="d-flex align-items-center justify-content-between mb-4 pb-4 border-bottom border-light">
                        <div class="d-flex align-items-center">
                            <div class="ra-stat-icon me-3" style="background: rgba(220, 38, 38, 0.1); color: #dc2626;">
                                <i class="ri-shield-cross-line fs-4"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-1 text-dark">IP Guard (Otomatik Engelleme)</h6>
                                <p class="text-muted small mb-0">Şüpheli aktiviteler ve saldırı denemeleri yapan IP adreslerini otomatik olarak yasaklar.</p>
                            </div>
                        </div>
                        <div class="form-check form-switch fs-4">
                            <input class="form-check-input ms-0 ajax-status-toggle" type="checkbox"
                                data-id="shield_ip_guard" value="1"
                                data-url="<?= $Route->url('syshub/security/firewall/updatesetting', 'developer') ?>"
                                <?= ($config['shield_ip_guard'] ?? '1') == '1' ? 'checked' : '' ?>>
                        </div>
                    </div>

                    <!-- Rate Limit -->
                    <div class="d-flex align-items-center justify-content-between mb-4 pb-4 border-bottom border-light">
                        <div class="d-flex align-items-center">
                            <div class="ra-stat-icon me-3" style="background: rgba(37, 99, 235, 0.1); color: #2563eb;">
                                <i class="ri-pulse-line fs-4"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-1 text-dark">Hız Sınırlama (Rate Limiting)</h6>
                                <p class="text-muted small mb-0">Belirli bir zaman diliminde aynı IP'den gelen aşırı istekleri kısıtlar.</p>
                            </div>
                        </div>
                        <div class="form-check form-switch fs-4">
                            <input class="form-check-input ms-0 ajax-status-toggle" type="checkbox"
                                data-id="shield_rate_limit" value="1"
                                data-url="<?= $Route->url('syshub/security/firewall/updatesetting', 'developer') ?>"
                                <?= ($config['shield_rate_limit'] ?? '1') == '1' ? 'checked' : '' ?>>
                        </div>
                    </div>

                    <!-- GeoIP Filtering -->
                    <div class="d-flex align-items-center justify-content-between mb-0">
                        <div class="d-flex align-items-center">
                            <div class="ra-stat-icon me-3" style="background: rgba(14, 165, 233, 0.1); color: #0ea5e9;">
                                <i class="ri-global-line fs-4"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-1 text-dark">GeoIP Analizi ve Filtreleme</h6>
                                <p class="text-muted small mb-0">Trafiklerin ülke kaynaklarını analiz eder ve lokasyon bazlı denetim sağlar.</p>
                            </div>
                        </div>
                        <div class="form-check form-switch fs-4">
                            <input class="form-check-input ms-0 ajax-status-toggle" type="checkbox"
                                data-id="shield_geoip_status" value="1"
                                data-url="<?= $Route->url('syshub/security/firewall/updatesetting', 'developer') ?>"
                                <?= ($config['shield_geoip_status'] ?? '1') == '1' ? 'checked' : '' ?>>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Sınır Değerleri & Kurallar -->
            <div class="rbn-card p-0 overflow-hidden">
                <div class="px-4 py-3 border-bottom d-flex align-items-center gap-2 bg-light">
                    <i class="ri-equalizer-line text-warning fs-5"></i>
                    <span class="fw-bold fs-6 text-dark" style="font-family: var(--rbn-font-body, inherit);">Sınır Değerleri ve Kurallar</span>
                </div>
                <div class="p-4">
                    <div class="row g-4">
                        <div class="col-12 col-md-6">
                            <label class="ra-stat-label mb-1">Maksimum İstek Sayısı</label>
                            <input type="number" name="settings[shield_max_attempts]" class="form-control font-monospace"
                                value="<?= $config['shield_max_attempts'] ?? '10' ?>" min="1">
                            <div class="text-muted small mt-1" style="font-size: 0.72rem;">Zaman penceresi içinde izin verilen maks. istek.</div>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="ra-stat-label mb-1">Zaman Penceresi (Saniye)</label>
                            <input type="number" name="settings[shield_time_window]" class="form-control font-monospace"
                                value="<?= $config['shield_time_window'] ?? '60' ?>" min="1">
                            <div class="text-muted small mt-1" style="font-size: 0.72rem;">İsteklerin sayılacağı zaman aralığı.</div>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="ra-stat-label mb-1">Engelleme Süresi (Dakika)</label>
                            <input type="number" name="settings[shield_block_duration]" class="form-control font-monospace"
                                value="<?= $config['shield_block_duration'] ?? '1440' ?>" min="1">
                            <div class="text-muted small mt-1" style="font-size: 0.72rem;">Kural ihlali yapan IP'nin yasaklı kalacağı süre.</div>
                        </div>
                    </div>

                    <div class="mt-4 pt-4 border-top border-light d-flex justify-content-end gap-2">
                        <button type="button" class="rbn-btn rbn-btn-outline rounded-pill px-4"
                            onclick="document.getElementById('firewallSettingsForm').reset()">
                            <i class="ri-refresh-line"></i> Sıfırla
                        </button>
                        <button type="submit" class="rbn-btn rbn-btn-primary rounded-pill px-4">
                            <i class="ri-save-line"></i> Kuralları Güncelle
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sağ Taraf: Güvenlik Bilgi ve Protokol Paneli -->
        <div class="col-12 col-lg-4">
            <div class="rbn-card p-4 h-100 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <div class="ra-stat-icon flex-shrink-0" style="background: rgba(197, 106, 60, 0.1); color: var(--rbn-primary, #c56a3c);">
                            <i class="ri-shield-star-line fs-4"></i>
                        </div>
                        <div>
                            <div class="fw-bold fs-6 text-dark" style="font-family: var(--rbn-font-body, inherit);">Güvenlik Protokolleri</div>
                            <span class="text-muted small" style="font-size: 0.72rem;">Otomatik Koruma</span>
                        </div>
                    </div>

                    <!-- Protokol Maddeleri -->
                    <div class="d-flex flex-column gap-3 mb-4">
                        <div class="p-3 rounded-3" style="background: rgba(60, 50, 40, 0.03); border: 1px solid var(--rbn-border-subtle, #f0ebe1);">
                            <div class="fw-bold text-dark small mb-1 d-flex align-items-center gap-1.5">
                                <i class="ri-robot-line text-danger"></i> Bot & Saldırı Kalkanı
                            </div>
                            <p class="text-muted small mb-0" style="font-size: 0.75rem;">
                                IP Guard, bilinen saldırı paternlerini otonom olarak tanır ve saniyeler içinde karantinaya alır.
                            </p>
                        </div>

                        <div class="p-3 rounded-3" style="background: rgba(60, 50, 40, 0.03); border: 1px solid var(--rbn-border-subtle, #f0ebe1);">
                            <div class="fw-bold text-dark small mb-1 d-flex align-items-center gap-1.5">
                                <i class="ri-flashlight-line text-warning"></i> Dinamik Hız Denetimi
                            </div>
                            <p class="text-muted small mb-0" style="font-size: 0.75rem;">
                                Rate Limit, ani trafik yüklerini dağıtarak sunucunuzu DDoS denemelerine karşı korur.
                            </p>
                        </div>

                        <div class="p-3 rounded-3" style="background: rgba(60, 50, 40, 0.03); border: 1px solid var(--rbn-border-subtle, #f0ebe1);">
                            <div class="fw-bold text-dark small mb-1 d-flex align-items-center gap-1.5">
                                <i class="ri-global-line text-info"></i> Katmanlı Doğrulama
                            </div>
                            <p class="text-muted small mb-0" style="font-size: 0.75rem;">
                                Zaman penceresi ve IP bazlı kontroller, kullanıcı kimliğini doğrulamak için katmanlı analiz yapar.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Uyarı Kutusu -->
                <div class="p-3 rounded-3 border border-warning border-opacity-25 bg-warning bg-opacity-10">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <i class="ri-alert-line text-warning"></i>
                        <span class="fw-bold small text-dark">Sistem Uyarısı</span>
                    </div>
                    <p class="small text-muted mb-0" style="font-size: 0.72rem;">
                        Çok düşük eşik değerleri normal kullanıcı erişimini kısıtlayabilir. Değerleri özenle belirleyin.
                    </p>
                </div>
            </div>
        </div>
    </div>
</form>
