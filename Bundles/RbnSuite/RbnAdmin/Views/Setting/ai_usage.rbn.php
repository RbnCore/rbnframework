<!-- Top Action Bar -->
<div class="d-flex justify-content-end align-items-center mb-4 gap-2">
    <button type="button" class="rbn-btn rbn-btn-outline px-3 py-2 fw-semibold d-flex align-items-center gap-1"
        data-rbn-modal="true" data-type="pricing" data-size="xl"
        data-title="Resmi Google AI Model Fiyatlandırma Tarifesi"
        data-endpoint="<?= $Route->url('admin.bot-settings.modal') ?>">
        <i class="ri-price-tag-3-line text-warning"></i> Fiyatlandırma Tarifesi
    </button>
    <button type="button"
        class="rbn-btn rbn-btn-outline px-3 py-2 fw-semibold action-confirm text-danger"
        data-url="<?= $Route->url('admin.bot-settings.ai-usage-clear') ?>" data-rbn-type="delete"
        data-title="Logları Temizle"
        data-text="Bu projeye ait tüm AI kullanım ve telemetri logları silinecektir. Emin misiniz?">
        <i class="ri-delete-bin-line me-1"></i> Logları Temizle
    </button>
</div>

<!-- 📊 Top Summary Stat Cards Component -->
@import('framework', 'Resources/Views/RbnAdmin/Components/ai_stat_cards.rbn.php')

<!-- 🎛️ Filtreler (Terracotta Standartı) -->
<div class="row g-3 mb-4 rbn-table-filters align-items-center">
    <!-- Live Search Input -->
    <div class="rbn-search-col <?= !empty($hasFilter) ? 'col-md-5' : 'col-md-6' ?>">
        <div class="position-relative h-100">
            <input type="text" class="form-control"
                placeholder="Görev veya AI Modeli ismi ara..." data-rbn-table-search="ai-usage-table"
                value="<?= htmlspecialchars($selectedSearch ?? '') ?>">
            <i class="ri-search-line position-absolute top-50 end-0 translate-middle-y me-3 text-muted"></i>
        </div>
    </div>

    <!-- Task Dropdown Filter -->
    <div class="col-md-3">
        <div class="rbn-dropdown dropdown w-100 h-100 rbn-filter-dropdown">
            <button
                class="rbn-btn rbn-btn-outline w-100 h-100 justify-content-between dropdown-toggle"
                type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="text-secondary fw-medium">
                    <i class="ri-settings-4-line me-2 text-warning opacity-75"></i>
                    <span data-filter-current-label="true"><?= !empty($selectedTask) ? htmlspecialchars($selectedTask) : 'Tüm Görevler' ?></span>
                </span>
                <i class="ri-arrow-down-s-line opacity-50 pe-1"></i>
            </button>
            <ul class="rbn-dropdown-menu dropdown-menu shadow-lg py-2 w-100 mt-2">
                <li>
                    <a class="rbn-dropdown-item dropdown-item py-2 <?= empty($selectedTask) ? 'active' : '' ?>"
                        data-rbn-table-filter="ai-usage-table" data-filter-param="task_key" data-filter-value=""
                        data-filter-label="Tüm Görevler" href="javascript:void(0)">
                        Tüm Görevler
                    </a>
                </li>
                <?php foreach ($allTasks as $tKey): ?>
                    <li>
                        <a class="rbn-dropdown-item dropdown-item py-2 <?= $selectedTask === $tKey ? 'active' : '' ?>"
                            data-rbn-table-filter="ai-usage-table" data-filter-param="task_key"
                            data-filter-value="<?= htmlspecialchars($tKey) ?>"
                            data-filter-label="<?= htmlspecialchars($tKey) ?>" href="javascript:void(0)">
                            <?= htmlspecialchars($tKey) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <!-- Model Dropdown Filter -->
    <div class="col-md-3">
        <div class="rbn-dropdown dropdown w-100 h-100 rbn-filter-dropdown">
            <button
                class="rbn-btn rbn-btn-outline w-100 h-100 justify-content-between dropdown-toggle"
                type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="text-secondary fw-medium">
                    <i class="ri-cpu-line me-2 text-warning opacity-75"></i>
                    <span data-filter-current-label="true"><?= !empty($selectedModel) ? htmlspecialchars($selectedModel) : 'Tüm AI Modelleri' ?></span>
                </span>
                <i class="ri-arrow-down-s-line opacity-50 pe-1"></i>
            </button>
            <ul class="rbn-dropdown-menu dropdown-menu shadow-lg py-2 w-100 mt-2">
                <li>
                    <a class="rbn-dropdown-item dropdown-item py-2 <?= empty($selectedModel) ? 'active' : '' ?>"
                        data-rbn-table-filter="ai-usage-table" data-filter-param="ai_model" data-filter-value=""
                        data-filter-label="Tüm AI Modelleri" href="javascript:void(0)">
                        Tüm AI Modelleri
                    </a>
                </li>
                <?php foreach ($allModels as $mKey): ?>
                    <li>
                        <a class="rbn-dropdown-item dropdown-item py-2 <?= $selectedModel === $mKey ? 'active' : '' ?>"
                            data-rbn-table-filter="ai-usage-table" data-filter-param="ai_model"
                            data-filter-value="<?= htmlspecialchars($mKey) ?>"
                            data-filter-label="<?= htmlspecialchars($mKey) ?>" href="javascript:void(0)">
                            <?= htmlspecialchars($mKey) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <?php if (!empty($hasFilter)): ?>
        <div class="col-md-1">
            <a href="<?= $Route->url('admin.bot-settings.ai-usage') ?>"
                class="rbn-btn rbn-btn-outline w-100 h-100 d-flex align-items-center justify-content-center"
                title="Filtreleri Temizle">
                <i class="ri-filter-off-line"></i>
            </a>
        </div>
    <?php endif; ?>
</div>

<!-- 📋 AI Telemetry & Usage Logs Table (Terracotta Table Standard) -->
<div class="rbn-table-wrap mb-5">
    <div class="ra-traffic-card-header">
        <h6 class="ra-traffic-card-title mb-0">
            <i class="ri-file-list-3-line text-warning fs-5"></i> Yapay Zeka İstek Günlükleri
        </h6>
        <div class="rbn-badge rbn-badge-terracotta px-3 py-1 font-monospace fw-bold">
            <?= count($logs ?? []) ?> Kayıt
        </div>
    </div>
    <div class="table-responsive">
        <table class="rbn-table rbn-table-hover align-middle mb-0" id="ai-usage-table">
            <thead>
                <tr>
                    <th class="ps-4" data-sort="string">Görev / İstek Tipi</th>
                    <th data-sort="string">Kullanılan Model</th>
                    <th data-sort="number">Prompt Token</th>
                    <th data-sort="number">Yanıt Token</th>
                    <th data-sort="number">Görsel</th>
                    <th data-sort="number">Maliyet (USD)</th>
                    <th class="pe-4 text-end" data-sort="date">Zaman</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="ri-file-search-line fs-2 text-warning opacity-50 d-block mb-1"></i>
                            Henüz kayıtlı bir AI kullanım logu bulunamadı.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td class="ps-4">
                                <span class="fw-bold text-dark d-block"><?= htmlspecialchars($log['task_key'] ?? 'Bilinmiyor') ?></span>
                                <span class="text-muted font-monospace x-small"><?= htmlspecialchars($log['request_type'] ?? 'text') ?></span>
                            </td>
                            <td>
                                <span class="rbn-badge rbn-badge-secondary font-monospace"><?= htmlspecialchars($log['ai_model'] ?? 'Gemini') ?></span>
                            </td>
                            <td class="font-monospace text-dark">
                                <?= number_format((int)($log['prompt_tokens'] ?? 0)) ?>
                            </td>
                            <td class="font-monospace text-dark">
                                <?= number_format((int)($log['candidate_tokens'] ?? 0)) ?>
                            </td>
                            <td class="font-monospace text-dark">
                                <?= number_format((int)($log['images_count'] ?? 0)) ?>
                            </td>
                            <td class="font-monospace fw-bold text-dark">
                                <span class="rbn-badge rbn-badge-terracotta px-2.5 py-1">
                                    $<?= number_format((float)($log['cost_usd'] ?? 0), 5) ?>
                                </span>
                            </td>
                            <td class="pe-4 text-end font-monospace text-muted small">
                                <?= !empty($log['created_at']) ? date('d.m.Y H:i', strtotime($log['created_at'])) : '-' ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>