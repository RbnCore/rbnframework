<!-- RBN Framework LOG DETAIL TERMINAL VIEWER -->
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <a href="{{ $Route->url('admin.cronlogs.index') }}" class="rbn-btn rbn-btn-outline rbn-btn-sm px-3 py-1.5 fw-semibold d-flex align-items-center gap-1.5">
        <i class="ri-arrow-left-line text-warning"></i> Log Listesine Dön
    </a>
</div>

<div class="rbn-table-wrap mb-5 overflow-hidden">
    <!-- Card Header -->
    <div class="ra-traffic-card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <i class="ri-terminal-box-line text-warning fs-5"></i>
            <h6 class="ra-traffic-card-title mb-0 font-monospace fs-6">
                {{ $filename }}
            </h6>
            <span class="rbn-badge rbn-badge-terracotta font-monospace ms-2 px-2.5 py-0.5" style="font-size: 0.72rem;">
                @if($size > 1048576)
                    {{ round($size / 1048576, 2) }} MB
                @else
                    {{ round($size / 1024, 2) }} KB
                @endif
            </span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button class="rbn-btn rbn-btn-outline rbn-btn-sm px-3 py-1.5 fw-semibold d-flex align-items-center gap-1.5"
                onclick="RbnUtils.copy(document.getElementById('log-code-block').textContent)">
                <i class="ri-file-copy-line text-warning"></i> Kopyala
            </button>
            <button type="button"
                class="rbn-btn rbn-btn-outline rbn-btn-sm px-3 py-1.5 fw-semibold action-confirm text-danger d-flex align-items-center gap-1.5"
                data-url="{{ $Route->url('admin.cronlogs.delete') }}"
                data-id="{{ $logId }}"
                data-rbn-type="delete"
                data-title="Log Kaydını Sil"
                data-text="{{ $filename }} kalıcı olarak silinecektir."
                data-redirect="{{ $Route->url('admin.cronlogs.index') }}">
                <i class="ri-delete-bin-line"></i> Sil
            </button>
        </div>
    </div>

    <!-- Terminal Area -->
    <div class="rbn-terminal rounded-0 border-0 m-0" id="terminal-scroll-area"
        style="max-height: 600px; overflow-y: auto; background-color: #151311 !important;">
        <div class="p-4" style="font-size: 0.85rem; line-height: 1.6; font-family: monospace;">
            @if(empty($logs))
                <div class="text-center py-5 text-muted">
                    <i class="ri-file-damage-line fs-1 d-block mb-2 opacity-25 text-white"></i>
                    Bu log dosyası boş görünüyor.
                </div>
            @else
                <div class="text-white opacity-75 mb-1 d-flex align-items-center">
                    <i class="ri-arrow-right-s-line me-2 small text-warning"></i> rbn@framework:~$ cat {{ $filename }}
                </div>
                <div class="mb-3 ps-4">
                    <pre id="log-code-block" class="mb-0"
                        style="font-family: inherit; font-size: 0.85rem; line-height: 1.6; white-space: pre-wrap; word-break: break-all; tab-size: 4; color: #f59e0b !important; background: transparent; border: 0; padding: 0; margin: 0;"><?php 
                        foreach ($logs as $log) {
                            $time = date('Y-m-d H:i:s', strtotime($log['timestamp']));
                            $level = strtoupper($log['level']);
                            $msg = $log['message'];
                            
                            if ($level === 'ERROR' || $level === 'CRITICAL') {
                                echo "[{$time}] <span class='text-danger fw-bold'>[{$level}]</span> {$msg}\n";
                                if (!empty($log['context']) && !empty($log['context']['trace'])) {
                                    $trace = is_array($log['context']['trace']) ? print_r($log['context']['trace'], true) : $log['context']['trace'];
                                    echo "<span class='text-secondary'>Stack Trace:</span>\n<span style='color: #ff7b72;'>" . htmlspecialchars($trace) . "</span>\n";
                                }
                            } elseif ($level === 'WARNING') {
                                echo "[{$time}] <span class='text-warning fw-bold'>[{$level}]</span> {$msg}\n";
                            } else {
                                echo "[{$time}] <span class='text-success fw-bold'>[{$level}]</span> {$msg}\n";
                            }
                        }
                    ?></pre>
                </div>
            @endif
        </div>
    </div>

    <!-- Footer Controls -->
    <div class="p-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2"
        style="background: #1e1b18; border-top: 1px solid rgba(255,255,255,0.08) !important;">
        <div class="d-flex align-items-center">
            <span class="rbn-badge rbn-badge-success rbn-badge-xs font-monospace">
                Terminal Modu
            </span>
            <span class="rbn-badge rbn-badge-secondary rbn-badge-xs font-monospace ms-2">
                Decoded CLI Output
            </span>
        </div>
        <div class="d-inline-flex gap-2">
            <button class="rbn-btn rbn-btn-outline btn-sm px-3 py-1 text-white border-secondary border-opacity-25"
                onclick="document.getElementById('terminal-scroll-area').scrollTop = document.getElementById('terminal-scroll-area').scrollHeight">
                <i class="ri-arrow-down-line me-1"></i> Sona Git
            </button>
            <button class="rbn-btn rbn-btn-outline btn-sm px-3 py-1 text-white border-secondary border-opacity-25"
                onclick="document.getElementById('terminal-scroll-area').scrollTop = 0">
                <i class="ri-arrow-up-line me-1"></i> Başa Git
            </button>
        </div>
    </div>
</div>
