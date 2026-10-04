<!-- Hallmark · component: cron-logs-panel · genre: terracotta-craft -->
<div class="d-flex justify-content-between align-items-center mb-4 pb-1 flex-wrap gap-3">
    <!-- Segmented Pill Tab Motoru -->
    <ul class="nav rbn-tab-pills">
        <li class="nav-item">
            <a class="nav-link {{ $activeTab === 'db' ? 'active' : '' }}" 
               href="{{ $Route->url('admin.cronlogs.index', ['tab' => 'db']) }}">
                <i class="ri-database-2-line me-1 text-warning"></i> Veritabanı Geçmişi
                <span class="badge ms-1.5">{{ $totalDbCount ?? 0 }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $activeTab === 'files' ? 'active' : '' }}" 
               href="{{ $Route->url('admin.cronlogs.index', ['tab' => 'files']) }}">
                <i class="ri-file-code-line me-1 text-warning"></i> Log Dosyaları (.jsonl)
                <span class="badge ms-1.5">{{ $totalFilesCount ?? 0 }}</span>
            </a>
        </li>
    </ul>

    <!-- Üst Aksiyonlar -->
    <div class="d-flex align-items-center gap-2">
        <a href="{{ $Route->url('admin.cron.index') }}" class="rbn-btn rbn-btn-outline rbn-btn-sm px-3 py-1.5 fw-semibold d-flex align-items-center gap-1.5">
            <i class="ri-settings-4-line text-warning"></i> Cron Ayarları
        </a>
        @if($activeTab === 'files' && !empty($files))
        <button class="rbn-btn rbn-btn-outline rbn-btn-sm px-3 py-1.5 action-confirm text-danger fw-semibold d-flex align-items-center gap-1.5"
                data-url="{{ $Route->url('admin.cronlogs.clear') }}"
                data-rbn-type="delete"
                data-title="Tüm Logları Temizle"
                data-text="Sistemdeki tüm cron log dosyaları silinecektir. Emin misiniz?"
                data-reload="true">
            <i class="ri-delete-bin-line"></i> Tümünü Temizle
        </button>
        @endif
    </div>
</div>

<!-- Main Card & Data Table -->
<div class="rbn-table-wrap mb-5">
    <!-- Header Bar -->
    <div class="ra-traffic-card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h6 class="ra-traffic-card-title mb-0 d-flex align-items-center gap-2">
                <i class="ri-history-line text-warning fs-5"></i>
                <span class="fw-bold text-dark fs-6">{{ $activeTab === 'db' ? 'Cron Çalıştırma Günlükleri' : 'Fiziksel Log Dosyaları' }}</span>
                <span class="rbn-badge rbn-badge-terracotta px-2.5 py-0.5 font-monospace fw-bold" style="font-size: 0.72rem;">
                    {{ $pager->total() }} KAYIT
                </span>
            </h6>
            <div class="text-muted small mt-0.5">
                {{ $activeTab === 'db' ? 'Zamanlanmış görevlerin execution süresi, durumu ve tetiklenme detayları' : 'Storage/logs/cron klasöründeki ham .jsonl günlük dosyaları' }}
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button type="button" class="rbn-btn rbn-btn-ghost rbn-btn-sm" onclick="location.reload()" data-tooltip="Yenile">
                <i class="ri-refresh-line"></i>
            </button>
        </div>
    </div>

    @if($activeTab === 'db')
    <!-- Database Logs Table -->
    <div class="table-responsive">
        <table class="rbn-table rbn-table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th class="ps-4" style="width: 80px;">ID</th>
                    <th>Görev Bilgisi</th>
                    <th>Başlangıç</th>
                    <th>Bitiş</th>
                    <th>Süre</th>
                    <th>Durum</th>
                    <th class="text-end pe-4" style="width: 110px;">İşlemler</th>
                </tr>
            </thead>
            <tbody>
                @foreach($dbLogs as $log)
                <tr>
                    <td class="ps-4 font-monospace text-muted small">#{{ $log['id'] }}</td>
                    <td>
                        <div class="d-flex flex-column">
                            <span class="fw-bold text-dark font-monospace fs-6">
                                {{ $log['job_name'] ?? ('Görev #' . $log['job_id']) }}
                            </span>
                            @if(!empty($log['task_key']))
                                <span class="text-muted small font-monospace d-flex align-items-center gap-1 mt-0.5">
                                    <i class="ri-terminal-line text-warning small"></i> {{ $log['task_key'] }}
                                </span>
                            @endif
                        </div>
                    </td>
                    <td class="font-monospace text-muted small">{{ $log['started_at'] }}</td>
                    <td class="font-monospace text-muted small">{{ $log['finished_at'] }}</td>
                    <td>
                        <span class="rbn-badge rbn-badge-neutral font-monospace fw-bold">
                            {{ number_format((float) $log['duration'], 3) }}s
                        </span>
                    </td>
                    <td>
                        @if($log['status'] === 'success')
                            <span class="rbn-badge rbn-badge-success rbn-badge-xs font-monospace">
                                <span class="ra-status-dot bg-success me-1"></span> SUCCESS
                            </span>
                        @elseif($log['status'] === 'failed' || $log['status'] === 'error')
                            <span class="rbn-badge rbn-badge-danger rbn-badge-xs font-monospace">
                                <span class="ra-status-dot bg-danger me-1"></span> FAILED
                            </span>
                        @else
                            <span class="rbn-badge rbn-badge-warning rbn-badge-xs font-monospace">
                                <span class="ra-status-dot bg-warning me-1"></span> {{ strtoupper($log['status']) }}
                            </span>
                        @endif
                    </td>
                    <td class="text-end pe-4">
                        <div class="d-flex justify-content-end gap-1">
                            <a href="{{ $Route->url('admin.cronlogs.view', ['id' => $log['id']]) }}" 
                               class="ra-table-btn" data-tooltip="Log Detayını İncele">
                                <i class="ri-eye-line"></i>
                            </a>
                            <button type="button" class="ra-table-btn ra-table-btn-danger action-confirm"
                                    data-url="{{ $Route->url('admin.cronlogs.delete') }}" 
                                    data-id="{{ $log['id'] }}" 
                                    data-rbn-type="delete"
                                    data-title="Log Kaydını Sil"
                                    data-text="Bu log kaydı veritabanından kalıcı olarak silinecektir. Emin misiniz?"
                                    data-reload="true" data-tooltip="Kaydı Sil">
                                <i class="ri-delete-bin-line"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                @endforeach

                @if(empty($dbLogs))
                <tr>
                    <td colspan="7" class="text-center py-5 text-muted">
                        <i class="ri-database-2-line fs-1 d-block mb-2 text-warning opacity-50"></i>
                        Henüz veritabanında kayıtlı cron çalıştırma geçmişi bulunamadı.
                    </td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>
    @else
    <!-- File Logs Table -->
    <div class="table-responsive">
        <table class="rbn-table rbn-table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th class="ps-4">Log Dosyası</th>
                    <th>Dosya Boyutu</th>
                    <th>Son Güncelleme</th>
                    <th class="text-end pe-4" style="width: 110px;">İşlemler</th>
                </tr>
            </thead>
            <tbody>
                @foreach($files as $file)
                <tr>
                    <td class="ps-4">
                        <div class="d-flex align-items-center gap-2">
                            <i class="ri-file-code-line text-warning fs-5"></i>
                            <span class="fw-bold text-dark font-monospace">{{ $file['name'] }}</span>
                        </div>
                    </td>
                    <td class="font-monospace text-dark">
                        @if($file['size'] > 1048576)
                            {{ round($file['size'] / 1048576, 2) }} MB
                        @else
                            {{ round($file['size'] / 1024, 2) }} KB
                        @endif
                    </td>
                    <td class="font-monospace text-muted small">
                        {{ date('Y-m-d H:i:s', $file['modified']) }}
                    </td>
                    <td class="text-end pe-4">
                        <div class="d-flex justify-content-end gap-1">
                            <a href="{{ $Route->url('admin.cronlogs.view', ['file' => $file['name']]) }}" 
                               class="ra-table-btn" data-tooltip="Dosya İçeriğini İncele">
                                <i class="ri-eye-line"></i>
                            </a>
                            <button type="button" class="ra-table-btn ra-table-btn-danger action-confirm"
                                    data-url="{{ $Route->url('admin.cronlogs.delete') }}" 
                                    data-id="{{ $file['name'] }}" 
                                    data-rbn-type="delete"
                                    data-title="Log Dosyasını Sil"
                                    data-text="{{ $file['name'] }} dosyası silinecek. Emin misiniz?"
                                    data-reload="true" data-tooltip="Dosyayı Sil">
                                <i class="ri-delete-bin-line"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                @endforeach

                @if(empty($files))
                <tr>
                    <td colspan="4" class="text-center py-5 text-muted">
                        <i class="ri-file-damage-line fs-1 d-block mb-2 text-warning opacity-50"></i>
                        Henüz kayıtlı cron log dosyası bulunamadı.
                    </td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>
    @endif

    <!-- Footer & Pagination -->
    <div class="d-flex justify-content-between align-items-center p-3 px-4 border-top flex-wrap gap-2" style="border-color: var(--rbn-admin-border-subtle, #e5ded3) !important;">
        <div class="text-muted small font-monospace">
            Toplam <strong class="text-dark">{{ $pager->total() }}</strong> kayıt listeleniyor.
        </div>
        @if(isset($pager) && $pager->hasPages())
        <div>
            {!! $pager->links() !!}
        </div>
        @endif
    </div>
</div>