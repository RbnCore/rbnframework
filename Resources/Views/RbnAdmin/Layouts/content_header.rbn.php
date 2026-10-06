<?php
/**
 * RbnAdmin Sovereign Component - Autonomous Unified Page Banner & Header 🏛️💎
 * Hallmark · component: ra-hero-banner · genre: terracotta-editorial
 */

// 1. Proje ve Domain Bilgileri
$groupProjects = group_projects();
$projectKey = active_project_key();
$activeProjectName = $projectKey;
$activeProjectDomain = '';
foreach ($groupProjects as $proj) {
    if ($proj['project_key'] === $projectKey) {
        $activeProjectName = $proj['project_name'];
        $activeProjectDomain = $proj['domain'] ?? '';
        break;
    }
}
if (empty($activeProjectDomain)) {
    $activeProjectDomain = parse_url(\Rbn\Framework\Core\System\Paths\Paths::project()->baseUrl(), PHP_URL_HOST) ?: 'localhost';
}

// 2. Sayfa Bilgileri (Doğrudan $page üzerinden otonom okunur)
$pageTitle = $page['title'] ?? 'Yönetim Paneli';
$pageDesc = $page['description'] ?? '';
$pageEyebrow = $page['eyebrow'] ?? '';
$pageIcon = $page['icon'] ?? 'ri-shield-flash-line';
$pageBadge = $page['badge'] ?? 'Live Monitoring';
?>

<div class="row g-3 mb-4 position-relative">
    <div class="col-12">
        <!-- 🏛️ Unified Hero Banner (Her Sayfada Otomatik & Tekil Başlık) -->
        <div class="card border-0 shadow-lg ra-hero-banner">
            <div class="ra-hero-pattern"></div>
            
            <div class="d-flex align-items-center justify-content-between gap-4 ra-hero-content w-100">
                <!-- Sol: İkon, Eyebrow, Başlık, Domain ve Açıklama -->
                <div class="ra-hero-left flex-grow-1 min-w-0">
                    <div class="ra-hero-icon-outer shadow-lg flex-shrink-0">
                        <span class="ra-hero-icon-inner">
                            <?php if (str_starts_with($pageIcon, 'ri-') || str_starts_with($pageIcon, 'bi-') || str_starts_with($pageIcon, 'fa')): ?>
                                <i class="<?= $pageIcon ?>"></i>
                            <?php else: ?>
                                <?= $pageIcon ?>
                            <?php endif; ?>
                        </span>
                    </div>
                    
                    <div class="ra-hero-text-wrap flex-grow-1 min-w-0">
                        <?php if (!empty($pageEyebrow)): ?>
                            <div class="text-warning fw-bold mb-1 small text-uppercase" style="letter-spacing: 0.05em; line-height: 1;">
                                <?= htmlspecialchars((string) $pageEyebrow) ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="d-flex align-items-center gap-2 gap-sm-3 flex-wrap">
                            <h1 class="ra-hero-title mb-0">
                                <?= htmlspecialchars((string) $pageTitle) ?>
                            </h1>
                            <span class="badge ra-badge-domain">
                                <i class="ri-global-line me-1"></i><?= htmlspecialchars($activeProjectDomain) ?>
                            </span>
                        </div>
                        
                        <?php if (!empty($pageDesc)): ?>
                            <p class="ra-hero-desc mb-0">
                                <?= htmlspecialchars((string) $pageDesc) ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Sağ: 1. Breadcrumb, 2. Live Monitoring, 3. Butonlar (Dikey Kolon Hizalama) -->
                <div class="ra-hero-right d-flex flex-column align-items-end justify-content-center gap-2 flex-shrink-0 ms-auto text-end my-auto">
                    <!-- Satır 1: Breadcrumb -->
                    <?php if (!empty($breadcrumbs)): ?>
                        <div class="rbn-dash-breadcrumb d-none d-lg-block mb-1">
                            <?= $breadcrumbs ?>
                        </div>
                    <?php endif; ?>

                    <!-- Satır 2: Live Monitoring Rozeti -->
                    <?php if (!empty($pageBadge)): ?>
                        <div class="mb-1">
                            <span class="badge ra-badge-status">
                                <span class="ra-status-dot"></span>
                                <?= htmlspecialchars((string) $pageBadge) ?>
                            </span>
                        </div>
                    <?php endif; ?>

                    <!-- Satır 3: Aksiyon Butonları -->
                    <div class="d-flex align-items-center justify-content-end gap-2 flex-wrap">
                        <button type="button" class="rbn-dash-action-btn" onclick="location.reload()" data-tooltip="Sayfayı Yenile">
                            <i class="ri-refresh-line"></i> <span>Yenile</span>
                        </button>
                        
                        <?php if (($page['show_actions'] ?? false) || (!empty($page['back_url']) && $page['back_url'] !== '/')): ?>
                            <a href="<?= $page['back_url'] ?? '#' ?>" class="rbn-dash-action-btn" data-tooltip="Geri Dön">
                                <i class="ri-arrow-left-line"></i> <span>Geri Dön</span>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>