<?php
/** @var array $database_info */
/** @var array $tables */
?>

<!-- 📊 1. METRİK STAT KARTLARI -->
<div class="row g-4 mb-4">
    <!-- DB Info -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="ra-stat-card ra-stat-navy h-100 d-flex flex-column justify-content-between">
            <div class="d-flex align-items-center mb-2">
                <div class="ra-stat-icon me-3">
                    <i class="ri-database-2-line"></i>
                </div>
                <div class="min-w-0">
                    <div class="ra-stat-label">VERİTABANI</div>
                    <h3 class="ra-stat-value fs-6 text-truncate mb-0" title="<?= htmlspecialchars($database_info['name'] ?? '-') ?>">
                        <?= htmlspecialchars($database_info['name'] ?? '-') ?>
                    </h3>
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center pt-2 border-top border-primary border-opacity-10 small">
                <span class="text-muted">Tür:</span>
                <span class="text-dark fw-bold"><?= htmlspecialchars($database_info['type'] ?? 'MySQL') ?></span>
            </div>
        </div>
    </div>

    <!-- Table Count -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="ra-stat-card ra-stat-terracotta h-100 d-flex flex-column justify-content-between">
            <div class="d-flex align-items-center mb-2">
                <div class="ra-stat-icon me-3">
                    <i class="ri-table-line"></i>
                </div>
                <div>
                    <div class="ra-stat-label">TOPLAM TABLO</div>
                    <h3 class="ra-stat-value fs-5 mb-0"><?= number_format($database_info['tables'] ?? 0) ?></h3>
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center pt-2 border-top border-secondary border-opacity-10 small">
                <span class="text-muted">Boyut:</span>
                <span class="text-dark fw-bold"><?= htmlspecialchars($database_info['size'] ?? '0 MB') ?></span>
            </div>
        </div>
    </div>

    <!-- Query Time -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="ra-stat-card ra-stat-warning h-100 d-flex flex-column justify-content-between">
            <div class="d-flex align-items-center mb-2">
                <div class="ra-stat-icon me-3">
                    <i class="ri-timer-line"></i>
                </div>
                <div>
                    <div class="ra-stat-label">SORGUSU SÜRESİ</div>
                    <h3 class="ra-stat-value fs-5 font-monospace mb-0" id="stat-time">—</h3>
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center pt-2 border-top border-warning border-opacity-10 small">
                <span class="text-muted">Birim:</span>
                <span class="text-dark fw-bold">Milisaniye</span>
            </div>
        </div>
    </div>

    <!-- Affected Rows -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="ra-stat-card ra-stat-success h-100 d-flex flex-column justify-content-between">
            <div class="d-flex align-items-center mb-2">
                <div class="ra-stat-icon me-3">
                    <i class="ri-list-check-2"></i>
                </div>
                <div>
                    <div class="ra-stat-label">ETKİLENEN SATIR</div>
                    <h3 class="ra-stat-value fs-5 font-monospace mb-0" id="stat-rows">—</h3>
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center pt-2 border-top border-success border-opacity-10 small">
                <span class="text-muted">Kayıt:</span>
                <span class="text-success fw-bold" id="stat-status">Hazır</span>
            </div>
        </div>
    </div>
</div>

<!-- 🖥️ 2. CONSOLE & TERMINAL LAYOUT -->
<div class="row g-4">
    <!-- Sol Taraf: Tablo Listesi -->
    <div class="col-12 col-lg-3">
        <div class="rbn-card p-0 h-100 overflow-hidden">
            <div class="px-4 py-3 border-bottom d-flex align-items-center justify-content-between bg-light">
                <div class="d-flex align-items-center gap-2">
                    <i class="ri-table-line text-primary"></i>
                    <span class="fw-bold fs-6 text-dark" style="font-family: var(--rbn-font-body, inherit);">Tablolar</span>
                </div>
                <span class="rbn-badge rbn-badge-neutral rbn-badge-xs"><?= count($tables) ?></span>
            </div>
            <div class="p-2" id="table-list" style="max-height: 540px; overflow-y: auto;">
                <?php foreach ($tables as $tbl): ?>
                    <div class="p-2 rounded-2 d-flex align-items-center justify-content-between rbn-table-hover cursor-pointer"
                        onclick="RbnSqlConsole.insertTableName('<?= htmlspecialchars($tbl['name']) ?>')"
                        data-tooltip="<?= htmlspecialchars($tbl['rows']) ?> kayıt"
                        style="transition: background 0.15s ease;">
                        <div class="d-flex align-items-center gap-2 min-w-0">
                            <i class="ri-table-2 text-muted" style="font-size: 0.85rem;"></i>
                            <span class="font-monospace text-truncate small text-dark"><?= htmlspecialchars($tbl['name']) ?></span>
                        </div>
                        <span class="rbn-badge rbn-badge-neutral rbn-badge-xs font-monospace"><?= $tbl['rows'] ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Sağ Taraf: SQL Editör & Sonuç Terminali -->
    <div class="col-12 col-lg-9">
        <!-- Terminal v3 Editör Kartı -->
        <div class="rbn-terminal-v3 mb-4">
            <!-- Terminal Header -->
            <div class="rbn-terminal-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="rbn-terminal-dots">
                        <span class="rbn-terminal-dot rbn-terminal-dot-red"></span>
                        <span class="rbn-terminal-dot rbn-terminal-dot-yellow"></span>
                        <span class="rbn-terminal-dot rbn-terminal-dot-green"></span>
                    </div>
                    <span class="rbn-terminal-title">sql_console — <?= htmlspecialchars($database_info['name'] ?? 'db') ?></span>
                </div>
                <!-- Snippets -->
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="text-white opacity-50 small me-2" style="font-size: 0.78rem;">Şablon:</span>
                    <button type="button" class="rbn-btn rbn-btn-secondary rbn-btn-xs font-monospace px-2.5 py-1" onclick="RbnSqlConsole.setSnippet('SELECT * FROM `table_name` LIMIT 100;')">SELECT</button>
                    <button type="button" class="rbn-btn rbn-btn-secondary rbn-btn-xs font-monospace px-2.5 py-1" onclick="RbnSqlConsole.setSnippet('SHOW TABLES;')">SHOW</button>
                    <button type="button" class="rbn-btn rbn-btn-secondary rbn-btn-xs font-monospace px-2.5 py-1" onclick="RbnSqlConsole.setSnippet('DESCRIBE `table_name`;')">DESC</button>
                    <button type="button" class="rbn-btn rbn-btn-danger rbn-btn-xs font-monospace px-2.5 py-1 ms-1" onclick="RbnSqlConsole.clearEditor()">Temizle</button>
                </div>
            </div>

            <!-- Terminal Textarea Content -->
            <div class="p-0">
                <textarea id="sql-editor" class="w-100 border-0 font-monospace p-3 rbn-terminal-success"
                    style="min-height: 160px; outline: none; background: transparent; font-size: 0.875rem; line-height: 1.6;"
                    placeholder="-- SQL sorgunuzu buraya yazın...&#10;-- Ctrl+Enter ile çalıştırın."></textarea>
            </div>

            <!-- Action Footer -->
            <div class="rbn-terminal-header py-2" style="background: rgba(22, 27, 34, 0.85); border-top: 1px solid var(--rbn-term-border); border-bottom: none;">
                <span class="font-monospace small text-muted">
                    <i class="ri-keyboard-line me-1"></i> Ctrl+Enter → Çalıştır &nbsp;|&nbsp; Ctrl+L → Temizle
                </span>
                <button type="button" id="btn-run" class="rbn-btn rbn-btn-primary rbn-btn-sm" onclick="RbnSqlConsole.run()">
                    <i class="ri-play-fill"></i> Sorguyu Çalıştır
                </button>
            </div>
        </div>

        <!-- Sonuç Terminali / Tablosu -->
        <div class="rbn-terminal-v3">
            <!-- Header -->
            <div class="rbn-terminal-header">
                <div class="d-flex align-items-center gap-2">
                    <i class="ri-terminal-box-line text-success fs-5"></i>
                    <span class="rbn-terminal-title text-white fw-bold">Sorgu Sonuçları</span>
                    <span id="result-badge" class="rbn-badge rbn-badge-neutral rbn-badge-xs font-monospace" style="display: none;">0 satır</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span id="result-type-badge" class="rbn-badge rbn-badge-success rbn-badge-xs font-monospace" style="display: none;"></span>
                    <button type="button" class="rbn-btn rbn-btn-secondary rbn-btn-xs" onclick="RbnSqlConsole.exportCsv()" id="btn-export" style="display: none;">
                        <i class="ri-download-2-line"></i> CSV İndir
                    </button>
                </div>
            </div>

            <!-- Terminal Log Area -->
            <div id="result-terminal" class="rbn-terminal-content font-monospace" style="font-size: 0.82rem; min-height: 80px; max-height: 180px;">
                <div class="rbn-terminal-line rbn-terminal-muted">[<?= date('H:i:s') ?>] Terminal hazır. SQL sorgusu bekleniyor...</div>
            </div>

            <!-- Result Table Container -->
            <div id="result-table-wrapper" class="rbn-table-container border-top" style="display: none; max-height: 400px; overflow: auto; border-color: var(--rbn-term-border) !important;">
                <table class="rbn-table w-100" id="result-table">
                    <thead id="result-thead"></thead>
                    <tbody id="result-tbody"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    rbnReady(function () {
        const RbnSqlConsole = {
            lastData: [],
            init: function () {
                const editor = document.getElementById('sql-editor');
                if (!editor) return;

                editor.addEventListener('keydown', (e) => {
                    if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                        e.preventDefault();
                        this.run();
                    }
                    if ((e.ctrlKey || e.metaKey) && e.key === 'l') {
                        e.preventDefault();
                        this.clearEditor();
                    }
                });
            },

            run: function () {
                const query = document.getElementById('sql-editor').value.trim();
                if (!query) {
                    this.printTerminal('error', 'Lütfen bir SQL sorgusu yazın.');
                    return;
                }

                const btn = document.getElementById('btn-run');
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Çalışıyor...';

                this.printTerminal('info', 'Sorgu yürütülüyor: ' + (query.length > 80 ? query.substring(0, 80) + '…' : query));

                const t0 = performance.now();

                fetch('<?= $Route->url("syshub/dbconsole/runQuery", "developer") ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify({ query: query })
                })
                .then(r => r.json())
                .then(res => {
                    const elapsed = Math.round(performance.now() - t0);
                    document.getElementById('stat-time').textContent = (res.execution_time ?? elapsed) + ' ms';

                    if (res.status === 'error' || !res.success) {
                        this.printTerminal('error', 'HATA: ' + (res.message || 'Bilinmeyen hata'));
                        this.hideTable();
                        document.getElementById('stat-rows').textContent = '—';
                        return;
                    }

                    const type = (res.type || 'SELECT').toUpperCase();
                    const typeBadge = document.getElementById('result-type-badge');
                    typeBadge.textContent = type;
                    typeBadge.style.display = 'inline-block';

                    if (type === 'SELECT' || Array.isArray(res.data)) {
                        const rows = res.data || [];
                        this.lastData = rows;
                        document.getElementById('stat-rows').textContent = rows.length;
                        this.printTerminal('success', '✓ ' + rows.length + ' satır döndü (' + (res.execution_time ?? elapsed) + ' ms)');
                        this.renderTable(rows);
                    } else {
                        const affected = res.affected_rows ?? 0;
                        document.getElementById('stat-rows').textContent = affected;
                        this.printTerminal('success', '✓ ' + (res.message || affected + ' satır etkilendi.') + ' (' + (res.execution_time ?? elapsed) + ' ms)');
                        this.hideTable();
                    }
                })
                .catch(err => {
                    this.printTerminal('error', 'Bağlantı hatası: ' + err.message);
                    this.hideTable();
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="ri-play-fill"></i> Sorguyu Çalıştır';
                });
            },

            renderTable: function (data) {
                const wrapper = document.getElementById('result-table-wrapper');
                const thead = document.getElementById('result-thead');
                const tbody = document.getElementById('result-tbody');
                const badge = document.getElementById('result-badge');

                if (!data || !data.length) {
                    thead.innerHTML = '';
                    tbody.innerHTML = '<tr><td class="text-center py-4 text-muted">Sonuç kümesi boş (0 satır).</td></tr>';
                    badge.style.display = 'none';
                    wrapper.style.display = 'block';
                    document.getElementById('btn-export').style.display = 'none';
                    return;
                }

                const cols = Object.keys(data[0]);
                thead.innerHTML = '<tr>' + cols.map(c => '<th>' + this.esc(c) + '</th>').join('') + '</tr>';
                tbody.innerHTML = data.map(row =>
                    '<tr>' + cols.map(c => {
                        const v = row[c] ?? '';
                        const s = String(v);
                        return '<td style="white-space:nowrap;max-width:300px;overflow:hidden;text-overflow:ellipsis;" title="' + this.esc(s) + '">' + this.esc(s.length > 80 ? s.substring(0, 80) + '…' : s) + '</td>';
                    }).join('') + '</tr>'
                ).join('');

                badge.style.display = 'inline';
                badge.textContent = data.length + ' satır';
                wrapper.style.display = 'block';
                document.getElementById('btn-export').style.display = 'inline-flex';
            },

            hideTable: function () {
                document.getElementById('result-table-wrapper').style.display = 'none';
                document.getElementById('result-badge').style.display = 'none';
                document.getElementById('btn-export').style.display = 'none';
            },

            printTerminal: function (type, msg) {
                const el = document.getElementById('result-terminal');
                const now = new Date().toLocaleTimeString('tr-TR');
                const line = document.createElement('div');
                line.className = 'rbn-terminal-line';
                line.innerHTML = '<span class="rbn-terminal-muted">[' + now + ']</span> <span class="rbn-terminal-' + type + '">' + this.esc(msg) + '</span>';
                el.appendChild(line);
                el.scrollTop = el.scrollHeight;
            },

            setSnippet: function (sql) {
                const editor = document.getElementById('sql-editor');
                if (editor) { editor.value = sql; editor.focus(); }
            },

            insertTableName: function (name) {
                const editor = document.getElementById('sql-editor');
                if (!editor) return;
                const pos = editor.selectionStart;
                const before = editor.value.substring(0, pos);
                const after = editor.value.substring(pos);
                editor.value = before + '`' + name + '`' + after;
                editor.selectionStart = editor.selectionEnd = pos + name.length + 2;
                editor.focus();
            },

            clearEditor: function () {
                const editor = document.getElementById('sql-editor');
                if (editor) editor.value = '';
            },

            exportCsv: function () {
                if (!this.lastData.length) return;
                const cols = Object.keys(this.lastData[0]);
                const rows = [cols.join(',')].concat(this.lastData.map(r => cols.map(c => '"' + String(r[c] ?? '').replace(/"/g, '""') + '"').join(',')));
                const blob = new Blob([rows.join('\n')], { type: 'text/csv;charset=utf-8;' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url; a.download = 'sql_result.csv'; a.click();
                URL.revokeObjectURL(url);
            },

            esc: function (str) {
                return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
            }
        };

        window.RbnSqlConsole = RbnSqlConsole;
        RbnSqlConsole.init();
    });
</script>
