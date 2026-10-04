<?php
/** @var array $settings */
/** @var array $settingIds */
/** @var array $settingActives */
?>

<!-- 📢 1. FORM ÜSTÜ AKSİYON BARI -->
<div class="d-flex justify-content-between align-items-center mb-3 px-1 flex-wrap gap-2">
    <div>
        <h6 class="fw-bold mb-0 text-dark" style="font-family: var(--rbn-font-body, inherit);">
            <i class="ri-advertisement-line text-danger me-1.5"></i>Google AdSense & Reklam Yönetimi
        </h6>
        <span class="text-muted small">Sitenizin tasarımını bozmadan reklam yerleşimlerini ve kodlarını yönetin.</span>
    </div>
</div>

<?php if (!isset($settings['adsense_status'])): ?>
    <!-- SETUP KARTI -->
    <div class="rbn-card p-5 text-center mb-4">
        <div class="ra-stat-icon mx-auto mb-3" style="width: 64px; height: 64px; background: rgba(220, 38, 38, 0.1); color: #dc2626; font-size: 2rem;">
            <i class="ri-advertisement-line"></i>
        </div>
        <h5 class="fw-bold text-dark mb-2" style="font-family: var(--rbn-font-body, inherit);">AdSense Ayarları Henüz Eklenmemiş</h5>
        <p class="text-muted small mx-auto mb-4" style="max-width: 500px; line-height: 1.6;">
            Bu proje için Google AdSense veritabanı alanları henüz tanımlanmamış. Aşağıdaki butona tıklayarak gerekli ayar şablonunu otomatik olarak oluşturabilirsiniz.
        </p>
        <form action="<?= $Route->url('webhub/integrations/setupadsense', 'developer') ?>" method="POST" data-rbn-form="true">
            @csrf
            <button type="submit" class="rbn-btn rbn-btn-danger rounded-pill px-5">
                <i class="ri-database-2-line"></i> Ayarları DB'ye Ekle
            </button>
        </form>
    </div>
<?php else: ?>
    <!-- 📋 ADSENSE FORMU KARTI -->
    <div class="rbn-card p-4 mb-4">
        <form action="<?= $Route->url('webhub/integrations/bulkvalueupdate', 'developer') ?>" method="POST"
            id="adsenseForm" data-rbn-form="true" autocomplete="off">
            @csrf

            <!-- 1. GENEL YAYIN DURUMU -->
            <div class="p-3 rounded-3 mb-4" style="background: rgba(60, 50, 40, 0.03); border: 1px solid var(--rbn-border-subtle, #f0ebe1);">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="fw-bold text-dark mb-1 d-flex align-items-center gap-1.5" style="font-family: var(--rbn-font-body, inherit);">
                            <i class="ri-shut-down-line text-danger"></i> Reklam Yayın Durumu
                        </h6>
                        <p class="text-muted small mb-0" style="font-size: 0.75rem;">Tüm sitedeki reklam yayınını tek bir anahtarla anında açıp kapatabilirsiniz.</p>
                    </div>
                    <div class="form-check form-switch fs-4">
                        <input type="hidden" name="settings[adsense_status]" value="0">
                        <input class="form-check-input ms-0 ajax-status-toggle" type="checkbox" name="settings[adsense_status]" value="1" id="adsense_status"
                            data-url="<?= $Route->url('webhub/integrations/toggle', 'developer') ?>"
                            data-id="<?= $settingIds['adsense_status'] ?? '' ?>"
                            <?= !empty($settingActives['adsense_status']) ? 'checked' : '' ?>>
                    </div>
                </div>
            </div>

            <!-- 2. REKLAM YERLEŞİMLERİ IZGARASI -->
            <div class="row g-4">
                <!-- AdSense Client ID -->
                <div class="col-12 col-md-6">
                    <label class="ra-stat-label mb-1">AdSense Client / Yayıncı ID</label>
                    <input type="text" name="settings[adsense_client_id]"
                        value="<?= htmlspecialchars($settings['adsense_client_id'] ?? '') ?>" class="form-control font-monospace"
                        placeholder="ca-pub-XXXXXXXXXXXXXXXX" autocomplete="off">
                    <div class="text-muted small mt-1" style="font-size: 0.72rem;">Google AdSense yayıncı kimlik kodunuz.</div>
                </div>

                <!-- Otomatik Reklamlar Switch -->
                <div class="col-12 col-md-6">
                    <label class="ra-stat-label mb-1">Otomatik Reklamlar (Auto Ads)</label>
                    <div class="p-2.5 rounded-3 border d-flex justify-content-between align-items-center bg-white" style="border-color: var(--rbn-border-subtle, #f0ebe1) !important;">
                        <span class="small text-muted">Google yapay zekasının yerleşim yapmasına izin ver</span>
                        <div class="form-check form-switch fs-5 mb-0">
                            <input type="hidden" name="settings[adsense_auto_ads]" value="0">
                            <input class="form-check-input ms-0" type="checkbox" name="settings[adsense_auto_ads]" value="1"
                                <?= !empty($settings['adsense_auto_ads']) ? 'checked' : '' ?>>
                        </div>
                    </div>
                </div>

                <!-- Slot Alanları -->
                <?php
                $slots = [
                    'adsense_header_slot' => 'Header / Üst Reklam Alanı Slot Kodu',
                    'adsense_sidebar_slot' => 'Sidebar / Yan Menü Slot Kodu',
                    'adsense_content_top_slot' => 'İçerik Başı Slot Kodu',
                    'adsense_content_bottom_slot' => 'İçerik Sonu Slot Kodu',
                    'adsense_footer_slot' => 'Footer / Alt Reklam Slot Kodu',
                ];
                ?>

                <?php foreach ($slots as $slotKey => $slotLabel): ?>
                    <div class="col-12 col-md-6">
                        <label class="ra-stat-label mb-1"><?= $slotLabel ?></label>
                        <input type="text" name="settings[<?= $slotKey ?>]"
                            value="<?= htmlspecialchars($settings[$slotKey] ?? '') ?>" class="form-control font-monospace"
                            placeholder="1234567890" autocomplete="off">
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4 pt-4 border-top">
                <button type="button" class="rbn-btn rbn-btn-outline rounded-pill px-4"
                    onclick="document.getElementById('adsenseForm').reset()">
                    <i class="ri-refresh-line"></i> Sıfırla
                </button>
                <button type="submit" class="rbn-btn rbn-btn-primary rounded-pill px-5">
                    <i class="ri-save-line"></i> AdSense Ayarlarını Kaydet
                </button>
            </div>
        </form>
    </div>
<?php endif; ?>
