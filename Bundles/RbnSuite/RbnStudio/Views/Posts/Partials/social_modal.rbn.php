<?php
/** @var array $post */
/** @var int $id */
/** @var string $type */
/** @var array $platforms */
/** @var string $publishedUrl */
/** @var string|null $imageUrl */
/** @var string $projectKey */

$typePrefix = ($type === 'news') ? 'news' : 'posts';
$actionUrl = $Route->url("admin.studio.{$typePrefix}.social-share");

$socialSummary = !empty($post['social_summary']) 
    ? trim((string) $post['social_summary']) 
    : (!empty($post['summary']) ? trim((string) $post['summary']) : trim((string) ($post['title'] ?? '')));

$socialHashtags = !empty($post['social_hashtags']) ? trim((string) $post['social_hashtags']) : '';
?>
<form action="<?= $actionUrl ?>" method="POST" id="socialShareForm" class="ajax-form" data-rbn-form="true" autocomplete="off">
    <?= $csrf_field ?? '' ?>
    <input type="hidden" name="id" value="<?= $id ?>">

    <div class="modal-body p-4">
        <?php if (empty($platforms)): ?>
            <div class="alert alert-warning d-flex align-items-center gap-3 p-3 rounded-3 mb-0">
                <i class="ri-error-warning-fill fs-3 text-warning"></i>
                <div>
                    <div class="fw-bold">Tanımlı Sosyal Medya Hesabı Bulunamadı</div>
                    <small class="text-muted">Bu proje için sistemde aktif Instagram veya Facebook API anahtarı tanımlı değil. Lütfen API Ayarlarından hesaplarınızı bağlayın.</small>
                </div>
            </div>
        <?php else: ?>
            <!-- 1. HEDEF PLATFORM SEÇİMİ -->
            <div class="mb-4">
                <label class="form-label fw-bold small text-muted text-uppercase mb-2">
                    <i class="ri-share-forward-line me-1 text-warning"></i> Paylaşım Yapılacak Platformlar <span class="text-danger">*</span>
                </label>
                <div class="row g-3">
                    <?php foreach ($platforms as $key => $plat): ?>
                    <div class="col-sm-6">
                        <label class="rbn-modal-type-card active d-flex align-items-center gap-3 p-3 cursor-pointer w-100 mb-0" style="border: 2px solid <?= $plat['color'] ?>25; background: <?= $plat['color'] ?>08;">
                            <input class="form-check-input mt-0" type="checkbox" name="platforms[]" value="<?= $key ?>" checked style="width: 1.25rem; height: 1.25rem;">
                            <div class="flex-grow-1">
                                <div class="fw-bold text-dark fs-6 d-flex align-items-center gap-2">
                                    <i class="<?= $plat['icon'] ?> fs-5" style="color: <?= $plat['color'] ?>;"></i>
                                    <span><?= htmlspecialchars($plat['name']) ?></span>
                                    <span class="badge ms-auto" style="background: <?= $plat['color'] ?>15; color: <?= $plat['color'] ?>; font-size: 0.7rem;">
                                        <?= htmlspecialchars($plat['badge']) ?>
                                    </span>
                                </div>
                                <div class="text-muted small mt-0.5" style="font-size: 0.75rem;">
                                    ID: <?= htmlspecialchars($plat['account_id'] ?? ($plat['page_id'] ?? 'Bağlı')) ?>
                                </div>
                            </div>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- 2. GÖRSEL VE METİN ÖNİZLEME & DÜZENLEME -->
            <div class="row g-3">
                <!-- Sol: Görsel Önizleme -->
                <div class="col-md-4">
                    <label class="form-label fw-bold small text-muted text-uppercase mb-2">
                        <i class="ri-image-line me-1 text-warning"></i> Paylaşılacak Görsel
                    </label>
                    <div class="rounded-3 overflow-hidden border shadow-sm position-relative" style="aspect-ratio: 16/9; background: var(--rbn-admin-surface, #fbf9f5);">
                        <?php 
                            $displayImg = !empty($previewImageUrl) ? $previewImageUrl : ($imageUrl ?? ''); 
                        ?>
                        <?php if (!empty($displayImg)): ?>
                            <img src="<?= htmlspecialchars($displayImg) ?>" alt="Kapak Görseli" class="w-100 h-100 object-fit-cover">
                        <?php else: ?>
                            <div class="w-100 h-100 d-flex flex-column align-items-center justify-content-center text-muted p-2 text-center">
                                <i class="ri-image-line fs-2 opacity-50 mb-1"></i>
                                <small style="font-size: 0.75rem;">Görsel Yok<br>(Sadece Metin/Link)</small>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php if (empty($imageUrl) && isset($platforms['instagram'])): ?>
                        <div class="text-danger small mt-1.5" style="font-size: 0.72rem;">
                            <i class="ri-information-line"></i> Instagram görsel olmadan paylaşılamaz.
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Sağ: Caption & Hashtag -->
                <div class="col-md-8">
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted text-uppercase mb-1">
                            <i class="ri-file-text-line me-1 text-warning"></i> Sosyal Medya Açıklaması (Caption)
                        </label>
                        <textarea name="caption" class="form-control rbn-form-input w-100" rows="6" style="line-height: 1.5; white-space: pre-wrap;" placeholder="Gönderi metni..."><?= htmlspecialchars($socialSummary) ?></textarea>
                    </div>

                    <div>
                        <label class="form-label fw-bold small text-muted text-uppercase mb-1">
                            <i class="ri-hashtag me-1 text-warning"></i> Etiketler (Hashtags)
                        </label>
                        <input type="text" name="hashtags" class="form-control rbn-form-input w-100" placeholder="#etiket1 #etiket2" value="<?= htmlspecialchars($socialHashtags) ?>">
                    </div>
                </div>
            </div>

            <!-- 3. CANLI LİNK GÖSTERİMİ -->
            <div class="mt-3 p-2.5 rounded-3 bg-light border d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2 overflow-hidden">
                    <i class="ri-link text-primary fs-5 flex-shrink-0"></i>
                    <span class="text-truncate small text-secondary" style="font-size: 0.8rem;" title="<?= htmlspecialchars($publishedUrl) ?>">
                        <?= htmlspecialchars($publishedUrl) ?>
                    </span>
                </div>
                <span class="badge bg-secondary-subtle text-secondary small flex-shrink-0" style="font-size: 0.7rem;">Bağlantı Otomatik Eklenir</span>
            </div>
        <?php endif; ?>
    </div>

    <div class="modal-footer p-3 bg-light border-top d-flex justify-content-between align-items-center rounded-bottom-4">
        <button type="button" class="rbn-btn rbn-btn-outline px-3 py-2" data-bs-dismiss="modal">
            İptal
        </button>

        <?php if (!empty($platforms)): ?>
        <button type="submit" class="rbn-btn rbn-btn-primary px-4 py-2 font-weight-bold shadow-sm d-flex align-items-center gap-2">
            <i class="ri-send-plane-fill fs-5"></i>
            <span>Şimdi Paylaş</span>
        </button>
        <?php endif; ?>
    </div>
</form>
