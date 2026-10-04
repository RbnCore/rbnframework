<?php
/** @var bool $isMaintenance */
/** @var string $maintenanceMessage */
/** @var array $whitelists */
/** @var array $stats */
?>

<!-- 📊 1. BAKIM MODU & ERİŞİM KOKPİTİ -->
<div class="row g-4 mb-4">
    <!-- Sol Taraf: Bakım Durum Kartı & 3'lü Metrik Izgarası -->
    <div class="col-12 col-lg-8">
        <!-- Ana Bakım Kartı -->
        <div class="rbn-card p-4 mb-4">
            <div class="d-flex align-items-center gap-4">
                <!-- Sol Taraf: Kilit / Kalkan İkonu -->
                <div class="flex-shrink-0 text-center">
                    <div class="ra-stat-icon" style="width: 72px; height: 72px; font-size: 2rem; background: <?= $isMaintenance ? 'rgba(220, 38, 38, 0.1)' : 'rgba(22, 163, 74, 0.1)' ?>; color: <?= $isMaintenance ? '#dc2626' : '#16a34a' ?>;">
                        <i class="<?= $isMaintenance ? 'ri-lock-2-line' : 'ri-lock-unlock-line' ?>"></i>
                    </div>
                </div>

                <!-- Sağ Taraf: Durum Bilgisi & Açıklamalar -->
                <div class="flex-grow-1 min-w-0">
                    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                        <div>
                            <div class="ra-stat-label">BAKIM MODU YÖNETİMİ</div>
                            <h4 class="fw-bold text-dark fs-5 mb-0" style="font-family: var(--rbn-font-body, inherit);">
                                <?= $isMaintenance ? 'Sistem Bakımda (Ziyarete Kapalı)' : 'Sistem Yayında (Erişilebilir)' ?>
                            </h4>
                        </div>
                        <span class="rbn-badge <?= $isMaintenance ? 'rbn-badge-danger' : 'rbn-badge-success' ?> rbn-badge-sm">
                            <?= $isMaintenance ? 'Bakım Modu Açık' : 'Canlı Yayın' ?>
                        </span>
                    </div>

                    <div class="row g-2 mt-1">
                        <div class="col-12 col-md-6">
                            <div class="d-flex align-items-center small text-muted">
                                <i class="ri-shield-check-line text-success me-1.5 flex-shrink-0"></i>
                                <span class="text-truncate">İzinli IP adresleri kesintisiz erişir.</span>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="d-flex align-items-center small text-muted">
                                <i class="ri-global-line text-primary me-1.5 flex-shrink-0"></i>
                                <span class="text-truncate">Mod kapatıldığında anında tüm kullanıcılara açılır.</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3'lü İstatistik Kartları -->
        <div class="row g-3">
            <div class="col-12 col-sm-4">
                <div class="ra-stat-card <?= $isMaintenance ? 'ra-stat-danger' : 'ra-stat-success' ?> h-100 p-3">
                    <div class="ra-stat-label">SİSTEM AĞI</div>
                    <h3 class="ra-stat-value fs-5 mb-0"><?= $isMaintenance ? 'KAPALI (BAKIM)' : 'AÇIK MİMARİ' ?></h3>
                </div>
            </div>

            <div class="col-12 col-sm-4">
                <div class="ra-stat-card ra-stat-navy h-100 p-3">
                    <div class="ra-stat-label">SİZİN IP'NİZ</div>
                    <h3 class="ra-stat-value fs-5 font-monospace mb-0"><?= htmlspecialchars($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1') ?></h3>
                </div>
            </div>

            <div class="col-12 col-sm-4">
                <div class="ra-stat-card ra-stat-terracotta h-100 p-3">
                    <div class="ra-stat-label">İZİNLİ LİSTE</div>
                    <h3 class="ra-stat-value fs-5 font-monospace mb-0"><?= number_format((int) ($stats['total_whitelisted'] ?? 0)) ?> IP</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Sağ Taraf: Erişim Yönetimi & Durum Butonu -->
    <div class="col-12 col-lg-4">
        <div class="rbn-card h-100 p-4 text-center d-flex flex-column justify-content-between">
            <div class="d-flex flex-column align-items-center">
                <div class="ra-stat-icon mx-auto mb-3" style="width: 56px; height: 56px; background: rgba(60, 50, 40, 0.06); color: var(--rbn-primary, #c56a3c); font-size: 1.65rem;">
                    <i class="ri-shut-down-line"></i>
                </div>
                <h5 class="fw-bold text-dark mb-1" style="font-family: var(--rbn-font-body, inherit);">Erişim Yönetimi</h5>
                <p class="text-muted small lh-base mb-3">Sistem genelindeki güvenlik ve genel ziyaretçi erişim durumlarını tek tıkla kontrol edin.</p>
            </div>

            <div class="d-flex flex-column gap-2 mt-auto">
                <button type="button"
                    class="rbn-btn <?= $isMaintenance ? 'rbn-btn-primary' : 'rbn-btn-danger' ?> w-100 rounded-pill py-2.5 justify-content-center"
                    data-rbn-confirm="true"
                    data-url="<?= $Route->url('syshub/maintenance/status', 'developer') ?>"
                    data-method="POST"
                    data-title="Bakım Modunu <?= $isMaintenance ? 'Kapatmak' : 'Açmak' ?> İstiyor musunuz?"
                    data-message="Bu işlem tüm sistemin genel ziyaretçilere erişilebilirliğini anında değiştirecektir."
                    data-type="<?= $isMaintenance ? 'success' : 'danger' ?>">
                    <i class="<?= $isMaintenance ? 'ri-play-circle-line' : 'ri-stop-circle-line' ?>"></i>
                    <?= $isMaintenance ? 'Sistemi Ziyarete Aç' : 'Sistemi Bakıma Al' ?>
                </button>

                <a href="<?= $Route->url('syshub/security/firewall', 'developer') ?>"
                    class="rbn-btn rbn-btn-outline w-100 rounded-pill rbn-btn-sm justify-content-center">
                    <i class="ri-shield-keyhole-line"></i> Firewall Ayarlarına Git
                </a>
            </div>
        </div>
    </div>
</div>

<!-- 📢 2. SİSTEM BAKIM MESAJI KARTI -->
<div class="rbn-card p-4 mb-4">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3" style="min-width: 0; flex: 1;">
            <div class="ra-stat-icon flex-shrink-0" style="background: rgba(217, 119, 6, 0.1); color: #d97706;">
                <i class="ri-message-3-line fs-4"></i>
            </div>
            <div style="min-width: 0; flex: 1;">
                <div class="ra-stat-label mb-1">SİSTEM BAKIM MESAJI</div>
                <div class="text-dark fw-medium text-truncate small" style="line-height: 1.5;">
                    <?= htmlspecialchars($maintenanceMessage ?: 'Sistemimizde bakım çalışması yapılmaktadır.') ?>
                </div>
            </div>
        </div>

        <button type="button"
            class="rbn-btn rbn-btn-outline rbn-btn-sm rounded-pill flex-shrink-0"
            data-rbn-modal="true" data-title="Bakım Mesajını Güncelle" data-theme="warning" data-size="lg"
            data-type="maintenance" data-view="modal_update_message">
            <i class="ri-edit-line"></i> Mesajı Düzenle
        </button>
    </div>
</div>

<!-- 📑 3. İZİNLİ IP LİSTESİ TABLOSU -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h6 class="fw-bold text-dark mb-0" style="font-family: var(--rbn-font-body, inherit);">Bakım Muafiyeti (İzinli IP Adresleri)</h6>
        <span class="text-muted small">Bu IP adresleri bakım modu açıkken dahi sisteme tam erişebilir.</span>
    </div>
    <button type="button"
        class="rbn-btn rbn-btn-primary rbn-btn-sm rounded-pill"
        data-rbn-modal="true" data-title="Yeni İzinli IP Ekle" data-theme="success" data-size="md"
        data-type="maintenance" data-view="modal_add_ip">
        <i class="ri-add-line"></i> Yeni IP Ekle
    </button>
</div>

<form id="maintenanceIpsForm">
    @csrf
    <div class="rbn-table-container">
        <table class="rbn-table w-100" id="maintenance-ips-table">
            <thead>
                <tr>
                    <th class="ps-4">IP Adresi</th>
                    <th>Statü</th>
                    <th>Eklenme Tarihi</th>
                    <th class="text-end pe-4" style="width: 120px;">İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($whitelists)): ?>
                    <tr>
                        <td colspan="4" class="text-center py-5">
                            <i class="ri-checkbox-circle-line fs-1 text-success opacity-50 d-block mb-2"></i>
                            <span class="text-muted small">Bakım modundan muaf tanımlı özel bir IP adresi bulunmuyor.</span>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($whitelists as $item): ?>
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="ri-shield-check-line text-success fs-5"></i>
                                    <span class="fw-bold text-dark font-monospace" style="font-size: 0.8125rem;"><?= htmlspecialchars($item['ip_address'] ?? $item['id']) ?></span>
                                    <?php if (($item['ip_address'] ?? $item['id']) === ($_SERVER['REMOTE_ADDR'] ?? '')): ?>
                                        <span class="rbn-badge rbn-badge-success rbn-badge-xs">Sizin IP'niz</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <span class="rbn-badge rbn-badge-neutral rbn-badge-xs">Muaf Kalkan</span>
                            </td>
                            <td>
                                <span class="text-muted small font-monospace">
                                    <?= is_numeric($item['created_at'] ?? null) ? now('d.m.Y H:i', (int) $item['created_at']) : htmlspecialchars($item['created_at'] ?? '–') ?>
                                </span>
                            </td>
                            <td class="text-end pe-4">
                                <div class="d-flex justify-content-end gap-1.5">
                                    <button type="button"
                                        class="rbn-btn rbn-btn-outline rbn-btn-xs text-danger"
                                        data-rbn-confirm="true"
                                        data-url="<?= $Route->url('syshub/maintenance/delete/' . urlencode($item['ip_address'] ?? $item['id']), 'developer') ?>"
                                        data-method="POST"
                                        data-title="İzinli IP Silinsin mi?"
                                        data-message="<?= htmlspecialchars($item['ip_address'] ?? $item['id']) ?> IP adresinin bakım muafiyeti kaldırılacaktır."
                                        data-type="danger"
                                        data-tooltip="Sil">
                                        <i class="ri-delete-bin-line"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</form>
