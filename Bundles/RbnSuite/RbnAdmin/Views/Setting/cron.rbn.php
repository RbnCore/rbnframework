<!-- ⏰ Dedicated Cron Settings Page -->
<div class="d-flex justify-content-end align-items-center gap-2 mb-4">
    <a href="<?= $Route->url('admin.cronlogs.index') ?>" class="rbn-btn rbn-btn-outline px-3 py-2 fw-semibold d-flex align-items-center gap-1">
        <i class="ri-terminal-box-line text-warning"></i> Cron Log Geçmişi
    </a>
</div>

<div class="row g-4 mb-5">
    <div class="col-12">
        <form action="<?= $Route->url('admin.cron.save') ?>" method="POST">
            @csrf
            <input type="hidden" name="project" value="<?= active_project_key() ?>">

            <div class="rbn-table-wrap mb-4">
                <div class="ra-traffic-card-header">
                    <div>
                        <h6 class="ra-traffic-card-title mb-0">
                            <i class="ri-time-line text-warning fs-5"></i> Zamanlayıcı Görev Listesi
                        </h6>
                        <span class="ra-stat-label text-muted">Botların otomatik tetikleme ve zamanlama parametrelerini yapılandırın</span>
                    </div>
                    <div class="rbn-badge rbn-badge-terracotta px-3 py-1 font-monospace fw-bold">
                        CRON ENGINE
                    </div>
                </div>
                <div class="p-4 bg-white">
                    <?php if (!empty($cronJobs) && count($cronJobs) > 0): ?>
                        <?php foreach ($cronJobs as $job): ?>
                            <div class="p-4 rounded-3 mb-4" style="background: var(--rbn-admin-surface, #fbf9f5); border: 1px solid var(--rbn-admin-border-subtle, #e5ded3);">
                                <div class="d-flex align-items-center justify-content-between mb-4 pb-3 border-bottom" style="border-color: var(--rbn-admin-border-subtle, #e5ded3) !important;">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="ra-stat-icon flex-shrink-0" style="background: rgba(197, 106, 60, 0.12); color: #c56a3c; width: 44px; height: 44px;">
                                            <i class="ri-settings-4-line fs-5"></i>
                                        </div>
                                        <div>
                                            <h6 class="fw-bold text-dark mb-1 fs-6"><?= htmlspecialchars($job['name']) ?></h6>
                                            <div class="small text-muted font-monospace d-flex align-items-center gap-1">
                                                <span>Görev Anahtarı:</span>
                                                <code class="text-secondary fw-bold bg-transparent p-0"><?= htmlspecialchars($job['task_key']) ?></code>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="rbn-form-check rbn-form-switch fs-5 mb-0">
                                        <input class="rbn-form-check-input rbn-status-toggle cursor-pointer" type="checkbox" role="switch" 
                                               name="cron[<?= $job['id'] ?>][is_active]" value="1"
                                               data-rbn-status-toggle="true"
                                               id="cron_active_<?= $job['id'] ?>" 
                                               data-id="<?= $job['id'] ?>"
                                               data-url="<?= $Route->url('cron/toggle-cron', 'admin') ?>"
                                               <?= $job['is_active'] ? 'checked' : '' ?>>
                                    </div>
                                </div>
                                <div class="row g-4">
                                    <div class="col-12">
                                        <label class="ra-stat-label mb-2 d-block">ÇALIŞMA GÜNLERİ</label>
                                        <div class="d-flex flex-wrap gap-3 p-3 bg-white rounded-3 border" style="border-color: var(--rbn-admin-border-subtle, #e5ded3) !important;">
                                            <?php foreach ($daysOfWeek as $dayNum => $dayName): 
                                                $checked = in_array($dayNum, $job['selected_days']) ? 'checked' : '';
                                            ?>
                                                <div class="rbn-form-check rbn-form-check-inline mb-0">
                                                    <input class="rbn-form-check-input" type="checkbox" 
                                                           name="cron[<?= $job['id'] ?>][days][]" 
                                                           value="<?= $dayNum ?>" 
                                                           id="day_<?= $job['id'] ?>_<?= $dayNum ?>" 
                                                           <?= $checked ?>>
                                                    <label class="form-check-label small fw-medium text-dark" for="day_<?= $job['id'] ?>_<?= $dayNum ?>"><?= $dayName ?></label>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label for="cron_hours_<?= $job['id'] ?>" class="ra-stat-label mb-1">ÇALIŞMA SAATLERİ (Virgülle Ayırın)</label>
                                        <input type="text" class="form-control font-monospace" 
                                               id="cron_hours_<?= $job['id'] ?>" name="cron[<?= $job['id'] ?>][hours]" 
                                               value="<?= htmlspecialchars($job['hours_string']) ?>" placeholder="Örn: 1, 11, 18">
                                        <div class="form-text small text-muted mt-1">Görevin tetikleneceği saatler (0-23 arası).</div>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="cron_freq_<?= $job['id'] ?>" class="ra-stat-label mb-1">ÇALIŞMA SIKLIĞI (Dakika)</label>
                                        <input type="number" class="form-control font-monospace" 
                                               id="cron_freq_<?= $job['id'] ?>" name="cron[<?= $job['id'] ?>][frequency]" 
                                               value="<?= (int)$job['frequency'] ?>" min="1" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="cron_task_type_<?= $job['id'] ?>" class="ra-stat-label mb-1">GÖREV TİPİ (Task Type)</label>
                                        <input type="text" class="form-control font-monospace" 
                                               id="cron_task_type_<?= $job['id'] ?>" name="cron[<?= $job['id'] ?>][task_type]" 
                                               value="<?= htmlspecialchars($job['task_type'] ?? '') ?>" placeholder="Belirtilmemiş">
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-5 text-muted">
                            <i class="ri-time-line fs-1 mb-2 d-block text-warning opacity-50"></i>
                            <p class="mb-0 fw-bold text-dark">Bu projeye atanmış herhangi bir zamanlanmış görev (cron) bulunamadı.</p>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="p-3 border-top d-flex justify-content-end" style="background: var(--rbn-admin-surface, #fbf9f5); border-color: var(--rbn-admin-border-subtle, #e5ded3) !important;">
                    <button type="submit" class="rbn-btn rbn-btn-primary px-4 py-2 fw-bold d-flex align-items-center gap-1">
                        <i class="ri-save-line fs-5"></i> Zamanlayıcı Ayarlarını Güncelle
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
