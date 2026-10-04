<?php
$totalTaskCount = count($tasks ?? []);
$totalDailyRuns = (int)($cronScheduleData['total_daily_runs'] ?? 0);

if ($totalDailyRuns === 0 && !empty($tasks)) {
    foreach ($tasks as $t) {
        $hoursCount = !empty($t['hours_string']) ? count(array_filter(explode(',', $t['hours_string']))) : 0;
        if ($hoursCount > 0) {
            $totalDailyRuns += $hoursCount;
        } else {
            $freq = (int)($t['frequency'] ?? 60);
            $totalDailyRuns += ($freq > 0 ? (int)floor(1440 / $freq) : 1);
        }
    }
}

// 🔀 Ön eke (Prefix) göre dinamik gruplama
$groupedTasks = [];
if (!empty($tasks)) {
    foreach ($tasks as $task) {
        $key = $task['task_key'] ?? 'genel';
        $parts = explode('_', $key);
        $prefix = count($parts) > 1 ? $parts[0] : 'Diğer';
        $groupedTasks[$prefix][] = $task;
    }
}
?>

<!-- Üst Özet Rozetleri (Terracotta Craft Metrikleri) -->
<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <span class="rbn-badge rbn-badge-secondary px-3 py-2 font-monospace fs-xs d-inline-flex align-items-center gap-1.5">
            <i class="ri-cpu-line text-warning"></i> Toplam <?= number_format($totalTaskCount) ?> Görev Kayıtlı
        </span>
        <span class="rbn-badge rbn-badge-secondary px-3 py-2 font-monospace fs-xs d-inline-flex align-items-center gap-1.5">
            <i class="ri-flashlight-line text-warning"></i> Günde Toplam <?= number_format($totalDailyRuns) ?> Tetikleme
        </span>
        <span class="rbn-badge rbn-badge-terracotta px-3 py-2 font-monospace fs-xs d-inline-flex align-items-center gap-1.5">
            <i class="ri-stack-line"></i> <?= count($groupedTasks) ?> Görev Grubu
        </span>
    </div>
</div>

<!-- Gruplanmış Görev Kartları -->
<?php if (!empty($groupedTasks)): ?>
    <?php foreach ($groupedTasks as $prefix => $groupTasks): ?>
        <!-- 🎨 Ön Ek Bölüm Başlığı & İnce Çizgi -->
        <div class="rbn-section-divider">
            <h6 class="ra-traffic-card-title mb-0 flex-shrink-0 d-inline-flex align-items-center">
                <span class="rbn-badge rbn-badge-terracotta font-monospace px-3 py-1 me-2 rounded-2 fs-xs"><?= strtoupper(htmlspecialchars($prefix)) ?></span>
                <span>Grubu Görevleri</span>
                <span class="rbn-badge rbn-badge-secondary px-2 py-0.5 rounded-pill fs-2xs ms-2 font-monospace">
                    <?= count($groupTasks) ?> Görev
                </span>
            </h6>
            <div class="rbn-section-divider-line"></div>
        </div>

        <div class="row g-4 mb-4">
            <?php foreach ($groupTasks as $task): 
                $isActive = (int)($task['is_active'] ?? 0) === 1;
                $rawClass = $task['task_class'] ?? $task['task_class?'] ?? '';
                $classParts = !empty($rawClass) ? explode('\\', $rawClass) : [];
                $shortClass = !empty($classParts) ? end($classParts) : 'Task';

                // Görev başına günlük tetiklenme hesabı
                $hoursCount = !empty($task['hours_string']) ? count(array_filter(explode(',', $task['hours_string']))) : 0;
                if ($hoursCount > 0) {
                    $taskDailyRuns = $hoursCount;
                } else {
                    $freq = (int)($task['frequency'] ?? 60);
                    $taskDailyRuns = $freq > 0 ? (int)floor(1440 / $freq) : 1;
                }
            ?>
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="rbn-card p-4 h-100 transition-all hover-translate-y d-flex flex-column justify-content-between">
                        <div>
                            <!-- Kart Header: İkon & Rozetler -->
                            <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
                                <div class="ra-stat-icon" style="background: rgba(197, 106, 60, 0.12); color: #c56a3c; width: 42px; height: 42px;">
                                    <i class="ri-cpu-line fs-5"></i>
                                </div>
                                <div class="d-flex align-items-center gap-1.5">
                                    <span class="rbn-badge rbn-badge-secondary font-monospace px-2.5 py-1 fs-2xs">
                                        <i class="ri-history-line me-1"></i><?= $taskDailyRuns ?> Kez / Gün
                                    </span>
                                </div>
                            </div>

                            <!-- Başlık & Açıklama -->
                            <h6 class="fw-bold text-dark mb-1 d-flex align-items-center justify-content-between">
                                <span class="text-truncate me-2"><?= htmlspecialchars($task['task_name'] ?? $task['task_key']) ?></span>
                            </h6>
                            <p class="text-muted small mb-3" style="min-height: 38px; line-height: 1.45;">
                                <?= htmlspecialchars($task['description'] ?? 'Bu robot için özel bir açıklama tanımlanmamış.') ?>
                            </p>

                            <!-- Kod Bloğu & Sınıf Adı -->
                            <div class="p-2.5 rounded-3 mb-3" style="background: var(--rbn-admin-surface, #fbf9f5); border: 1px solid var(--rbn-admin-border-subtle, #e5ded3);">
                                <div class="d-flex align-items-center justify-content-between">
                                    <span class="font-monospace fs-2xs text-muted">HANDLER</span>
                                    <code class="text-dark fw-bold fs-xs bg-transparent p-0"><?= htmlspecialchars($shortClass) ?></code>
                                </div>
                            </div>
                        </div>

                        <!-- Kart Footer: Durum Switch & Son Çalışma -->
                        <div class="pt-3 border-top d-flex align-items-center justify-content-between" style="border-color: var(--rbn-admin-border-subtle, #e5ded3) !important;">
                            <div class="d-flex align-items-center gap-2">
                                <span class="ra-status-dot <?= $isActive ? 'bg-success' : 'bg-secondary' ?>"></span>
                                <span class="small font-monospace <?= $isActive ? 'text-success fw-bold' : 'text-muted' ?>">
                                    <?= $isActive ? 'Aktif Görev' : 'Pasif' ?>
                                </span>
                            </div>
                            <span class="text-muted x-small font-monospace">
                                <i class="ri-time-line me-0.5"></i> <?= !empty($task['last_run_at']) ? date('H:i:s', strtotime($task['last_run_at'])) : 'Henüz Çalışmadı' ?>
                            </span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <div class="rbn-card p-5 text-center my-4">
        <i class="ri-robot-2-line fs-1 text-warning opacity-50 mb-3 d-block"></i>
        <h5 class="fw-bold text-dark mb-1">Kayıtlı Otonom Görev Bulunamadı</h5>
        <p class="text-muted small mb-0">Bu projede henüz tanımlı bir robot veya cron görevi bulunmuyor.</p>
    </div>
<?php endif; ?>
