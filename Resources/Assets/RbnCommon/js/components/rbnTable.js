/**
 * RbnKit — rbnTable Component Logic
 * Lightweight, high-performance table interactions.
 * @version 2.1.0
 */

(function () {
    'use strict';

    if (window.rbnTable && window.rbnTable._initialized) {
        return;
    }

    const rbnTable = {
        _initialized: true,
        init: function () {
            this.bindEvents();
            this.applyRowIndices();
            this.initDropdownZIndex();
            this.initBulkActions();
        },

    /**
     * .rbn-table içindeki dropdown'ların açıldığında satırın üzerine çıkmasını sağlar.
     */
    initDropdownZIndex: function () {
        document.addEventListener('click', function (e) {
            const toggle = e.target.closest('.rbn-table .dropdown-toggle, .rbn-table [data-bs-toggle="dropdown"]');
            if (toggle) {
                const tr = toggle.closest('tr');
                if (tr) {
                    // Diğer açık satırların z-indexini sıfırla
                    document.querySelectorAll('.rbn-table tr.is-dropdown-open').forEach(r => {
                        r.classList.remove('is-dropdown-open');
                        r.style.zIndex = '';
                        r.style.position = '';
                    });

                    tr.classList.add('is-dropdown-open');
                    tr.style.position = 'relative';
                    tr.style.zIndex = '1070';
                }
            } else if (!e.target.closest('.rbn-table .dropdown-menu')) {
                document.querySelectorAll('.rbn-table tr.is-dropdown-open').forEach(r => {
                    r.classList.remove('is-dropdown-open');
                    r.style.zIndex = '';
                    r.style.position = '';
                });
            }
        });
    },

    bindEvents: function () {
        // Unified Instant Live Filtering Engine (Search + Select Filters) ⚡
        const runLiveFilters = (targetTableId) => {
            const table = document.getElementById(targetTableId);
            if (!table) return;

            const searchInput = document.querySelector(`[data-rbn-table-search="${targetTableId}"]`);
            const searchQuery = searchInput ? searchInput.value.toLowerCase().trim() : '';

            const filterSelects = Array.from(document.querySelectorAll(`select[data-rbn-target-table="${targetTableId}"]`));

            const rows = table.querySelectorAll('tbody tr');
            let visibleCount = 0;

            rows.forEach(r => {
                const textContent = r.textContent.toLowerCase();
                const matchesSearch = !searchQuery || textContent.includes(searchQuery);

                let matchesFilters = true;
                for (const sel of filterSelects) {
                    const filterAttr = sel.getAttribute('data-rbn-filter-attr');
                    const colIndex = sel.hasAttribute('data-rbn-filter-col') ? parseInt(sel.getAttribute('data-rbn-filter-col'), 10) : null;
                    const val = sel.value.toLowerCase().trim();

                    if (val) {
                        if (filterAttr) {
                            const rowAttrVal = (r.getAttribute(`data-${filterAttr}`) || '').toLowerCase().trim();
                            if (rowAttrVal !== val) {
                                matchesFilters = false;
                                break;
                            }
                        } else if (colIndex !== null) {
                            const cell = r.children[colIndex];
                            const cellText = cell ? cell.textContent.toLowerCase().trim() : '';
                            if (!cellText.includes(val)) {
                                matchesFilters = false;
                                break;
                            }
                        }
                    }
                }

                if (matchesSearch && matchesFilters) {
                    r.style.display = '';
                    visibleCount++;
                } else {
                    r.style.display = 'none';
                }
            });

            // 4. Filtreyi Temizle (Reset) Butonunun Görünürlüğü
            const isFiltered = Boolean(searchQuery || filterSelects.some(s => s.value !== ''));
            document.querySelectorAll(`[data-rbn-table-reset="${targetTableId}"]`).forEach(btn => {
                btn.style.display = isFiltered ? 'inline-flex' : 'none';
            });

            const emptyState = document.getElementById(targetTableId + '-empty');
            if (emptyState) emptyState.style.display = visibleCount === 0 ? '' : 'none';
        };

        // Reset Butonlarına Tıklama Dinleyicisi
        document.querySelectorAll('[data-rbn-table-reset]').forEach(btn => {
            btn.addEventListener('click', () => {
                const targetTableId = btn.getAttribute('data-rbn-table-reset');
                const searchInput = document.querySelector(`[data-rbn-table-search="${targetTableId}"]`);
                if (searchInput) searchInput.value = '';

                document.querySelectorAll(`select[data-rbn-target-table="${targetTableId}"]`).forEach(sel => {
                    sel.value = '';
                });

                const sortSelect = document.querySelector(`select[data-rbn-sort-select="${targetTableId}"]`);
                if (sortSelect) {
                    sortSelect.value = 'id_asc';
                    const table = document.getElementById(targetTableId);
                    if (table) this.sortTable(table, 0, 'number', 'asc');
                }

                runLiveFilters(targetTableId);
            });
        });

        // 1. Search Input Binding
        document.querySelectorAll('[data-rbn-table-search]').forEach(input => {
            input.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') e.preventDefault();
            });

            input.addEventListener('input', (e) => {
                const targetTableId = input.getAttribute('data-rbn-table-search');
                runLiveFilters(targetTableId);
            });
        });

        // 2. Generic Filter Selects Binding (Attributes & Columns)
        document.querySelectorAll('select[data-rbn-filter-col], select[data-rbn-filter-attr]').forEach(sel => {
            sel.addEventListener('change', () => {
                const targetTableId = sel.getAttribute('data-rbn-target-table');
                runLiveFilters(targetTableId);
            });
        });

        // 3. Sort Select Binding (Fully Generic 'col_type_dir' Format ⚡)
        document.querySelectorAll('select[data-rbn-sort-select]').forEach(sel => {
            sel.addEventListener('change', () => {
                const targetTableId = sel.getAttribute('data-rbn-sort-select');
                const table = document.getElementById(targetTableId);
                if (!table) return;

                const val = sel.value;
                if (val.includes('_')) {
                    const parts = val.split('_');
                    // Format 1: colIndex_type_dir (Örn: 2_string_asc, 0_number_desc)
                    if (!isNaN(parts[0]) && parts.length >= 3) {
                        const col = parseInt(parts[0], 10);
                        const type = parts[1]; // 'string' | 'number' | 'date'
                        const dir = parts[2];  // 'asc' | 'desc'
                        this.sortTable(table, col, type, dir);
                        return;
                    }
                }

                // Fallback / Standart Aliaslar
                if (val === 'id_desc') this.sortTable(table, 0, 'number', 'desc');
                else if (val === 'name_asc') this.sortTable(table, 4, 'string', 'asc');
                else if (val === 'name_desc') this.sortTable(table, 4, 'string', 'desc');
                else this.sortTable(table, 0, 'number', 'asc');
            });
        });

        // 4. Initial state check for all reset buttons
        document.querySelectorAll('[data-rbn-table-reset]').forEach(btn => {
            const targetTableId = btn.getAttribute('data-rbn-table-reset');
            runLiveFilters(targetTableId);
        });

        // Universal Filtering functionality (Dropdowns, Tabs, Reset, etc.)
        document.querySelectorAll('[data-rbn-table-filter]').forEach(item => {
            item.addEventListener('click', (e) => {
                e.preventDefault();
                const targetTableId = item.getAttribute('data-rbn-table-filter');
                const param = item.getAttribute('data-filter-param') || 'filter';
                const value = item.getAttribute('data-filter-value');
                const label = item.getAttribute('data-filter-label') || item.textContent.trim();

                // Reset Action Handling (Clears URL params, input boxes and re-fetches cleanly)
                if (param === 'reset' || item.hasAttribute('data-filter-reset')) {
                    document.querySelectorAll(`[data-rbn-table-search="${targetTableId}"]`).forEach(inp => inp.value = '');
                    const cleanUrl = new URL(window.location.href);
                    cleanUrl.search = '';
                    window.history.replaceState({}, '', cleanUrl.toString());

                    // Hide reset button and expand search column back to col-md-6
                    document.querySelectorAll('.rbn-reset-wrapper').forEach(w => w.classList.add('d-none'));
                    document.querySelectorAll('.rbn-search-col').forEach(sc => {
                        sc.classList.remove('col-md-5');
                        sc.classList.add('col-md-6');
                    });

                    // Visual Reset for all filter dropdowns
                    document.querySelectorAll('.rbn-filter-dropdown').forEach(dropdown => {
                        const firstItem = dropdown.querySelector('.dropdown-menu .dropdown-item:first-child');
                        const toggleBtnLabel = dropdown.querySelector('.dropdown-toggle span[data-filter-current-label]');
                        if (firstItem && toggleBtnLabel) {
                            const defaultLabel = firstItem.getAttribute('data-filter-label') || firstItem.textContent.trim();
                            toggleBtnLabel.textContent = defaultLabel;
                            dropdown.querySelectorAll('.dropdown-item').forEach(i => i.classList.remove('active'));
                            firstItem.classList.add('active');
                        }
                    });

                    this.refreshTable(targetTableId, { search: '', task_key: '', ai_model: '', request_type: '', page: 1 });
                    return;
                }

                // Show reset button & shrink search column when any filter value is selected
                if (value !== undefined && value !== null && value !== '') {
                    document.querySelectorAll('.rbn-reset-wrapper').forEach(w => w.classList.remove('d-none'));
                    document.querySelectorAll('.rbn-search-col').forEach(sc => {
                        sc.classList.remove('col-md-6');
                        sc.classList.add('col-md-5');
                    });
                }

                // Visual Update: Dropdown & Segmented Nav Tabs
                const dropdown = item.closest('.dropdown');
                if (dropdown) {
                    const toggleBtn = dropdown.querySelector('.dropdown-toggle span[data-filter-current-label]');
                    if (toggleBtn) toggleBtn.textContent = label;

                    const menu = item.closest('.dropdown-menu');
                    if (menu) {
                        menu.querySelectorAll('.dropdown-item').forEach(i => i.classList.remove('active'));
                        item.classList.add('active');
                        menu.classList.remove('show');
                    }
                }

                const navTabs = item.closest('.rbn-table-tabs, .nav-tabs');
                if (navTabs) {
                    navTabs.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
                    item.classList.add('active');
                }

                // AJAX Refresh
                let params = { page: 1 };
                params[param] = value;
                this.refreshTable(targetTableId, params);
            });

            // Native Select / Input Change Handling (e.g. date pickers, dropdown selects)
            item.addEventListener('change', (e) => {
                const targetTableId = item.getAttribute('data-rbn-table-filter');
                const param = item.getAttribute('data-filter-param') || 'filter';
                const value = e.target.value;

                // If input is inside a dropdown, update the dropdown toggle label
                const dropdown = item.closest('.dropdown');
                if (dropdown) {
                    const toggleBtn = dropdown.querySelector('.dropdown-toggle [data-filter-current-label]');
                    if (toggleBtn) {
                        const icon = toggleBtn.querySelector('i');
                        const iconHtml = icon ? icon.outerHTML + ' ' : '';
                        const formattedVal = this.formatDateTurkish(value);
                        toggleBtn.innerHTML = iconHtml + formattedVal;
                    }
                    const menu = item.closest('.dropdown-menu');
                    if (menu) {
                        menu.querySelectorAll('.dropdown-item').forEach(i => i.classList.remove('active'));
                        menu.classList.remove('show');
                    }
                }

                let params = { page: 1 };
                params[param] = value;
                this.refreshTable(targetTableId, params);
            });
        });

        // Export to CSV functionality
        document.querySelectorAll('[data-rbn-table-export]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const targetTableId = btn.getAttribute('data-rbn-table-export');
                const filename = btn.getAttribute('data-export-filename') || (targetTableId + '-export.csv');
                if (typeof RbnExporter !== 'undefined') {
                    RbnExporter.exportTableToExcel('#' + targetTableId, filename);
                }
            });
        });

        // Sorting functionality (Clicking headers)
        document.querySelectorAll('.rbn-table th[data-sort]').forEach(th => {
            th.style.cursor = 'pointer';
            th.addEventListener('click', () => {
                const table = th.closest('table');
                const index = Array.from(th.parentNode.children).indexOf(th);
                const type = th.getAttribute('data-sort') || 'string';
                this.sortTable(table, index, type);
            });
        });

        // Cinematic Custom Dropdown Sorting functionality
        document.querySelectorAll('.dropdown-item[data-rbn-table-sort]').forEach(item => {
            item.addEventListener('click', (e) => {
                e.preventDefault();
                const targetTableId = item.getAttribute('data-rbn-table-sort');
                const table = document.getElementById(targetTableId);
                if (!table) return;

                const colIndex = parseInt(item.getAttribute('data-sort-col'), 10);
                const sortType = item.getAttribute('data-sort-type') || 'string';
                const sortDir = item.getAttribute('data-sort-dir') || 'asc';

                // Visual Update
                const toggleBtn = item.closest('.dropdown').querySelector('.dropdown-toggle span[data-sort-label]');
                if (toggleBtn) toggleBtn.textContent = item.textContent.trim();

                const menu = item.closest('.dropdown-menu');
                if (menu) {
                    menu.querySelectorAll('.dropdown-item').forEach(i => i.classList.remove('active'));
                    item.classList.add('active');
                    menu.classList.remove('show');
                }

                if (!isNaN(colIndex)) {
                    this.sortTable(table, colIndex, sortType, sortDir);
                }
            });
        });
    },

    formatDateTurkish: function (dateStr) {
        if (!dateStr || typeof dateStr !== 'string' || !dateStr.includes('-')) return dateStr;
        const parts = dateStr.split('-');
        if (parts.length !== 3) return dateStr;
        
        const months = ['Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'];
        const day = parseInt(parts[2], 10);
        const monthIndex = parseInt(parts[1], 10) - 1;
        const year = parts[0];

        if (monthIndex >= 0 && monthIndex < 12 && !isNaN(day)) {
            return `${day} ${months[monthIndex]} ${year}`;
        }
        return dateStr;
    },

    currentAbortController: null,

    refreshTable: function (tableId, newParams = {}) {
        const targetTable = document.getElementById(tableId);
        const url = new URL(window.location.href);

        // Update URL Parameters
        Object.keys(newParams).forEach(key => {
            if (newParams[key] === null || newParams[key] === '') {
                url.searchParams.delete(key);
            } else {
                url.searchParams.set(key, newParams[key]);
            }
        });

        if (this.currentAbortController) this.currentAbortController.abort();
        this.currentAbortController = new AbortController();

        if (targetTable) targetTable.style.opacity = '0.5';
        window.history.replaceState({}, '', url.toString());

        RbnService.get(url.toString(), {}, {
            signal: this.currentAbortController.signal,
            format: 'text',
            silent: true
        }).then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');

            // 0. Refresh Top Stat Cards (if <tableId>-stats container exists)
            const statsId = tableId + '-stats';
            const oldStats = document.getElementById(statsId);
            const newStats = doc.getElementById(statsId);
            if (oldStats && newStats) {
                oldStats.innerHTML = newStats.innerHTML;
            }

            // 1. Refresh Table Content
            const newTable = doc.getElementById(tableId);
            if (targetTable && newTable) {
                targetTable.innerHTML = newTable.innerHTML;
                targetTable.style.opacity = '1';
            }

            // 2. Refresh Pagination
            const wrapperId = tableId + '-pagination-wrapper';
            const oldWrapper = document.getElementById(wrapperId);
            const newWrapper = doc.getElementById(wrapperId);
            if (oldWrapper && newWrapper) oldWrapper.innerHTML = newWrapper.innerHTML;

            // 3. Refresh Empty State
            const emptyId = tableId + '-empty';
            const oldEmpty = document.getElementById(emptyId);
            const newEmpty = doc.getElementById(emptyId);
            if (oldEmpty && newEmpty) oldEmpty.outerHTML = newEmpty.outerHTML;

            // 4. Update Filter Toggle Labels (Synchronize server-rendered labels like Turkish date)
            const oldLabels = document.querySelectorAll('[data-filter-current-label]');
            const newLabels = doc.querySelectorAll('[data-filter-current-label]');
            oldLabels.forEach((lbl, idx) => {
                if (newLabels[idx]) {
                    lbl.innerHTML = newLabels[idx].innerHTML;
                }
            });

            // 5. Update Stats & Badges
            ['h4', 'h6', '.action-bulk', '.badge', '.stat-value', '.tab-count'].forEach(selector => {
                document.querySelectorAll(selector).forEach((el, i) => {
                    const newEl = doc.querySelectorAll(selector)[i];
                    if (newEl) el.innerHTML = newEl.innerHTML;
                });
            });

            if (typeof RbnBinders !== 'undefined') RbnBinders.initTooltips();
            this.applyRowIndices();
        }).catch(err => {
            if (err.name === 'AbortError') return;
            if (targetTable) targetTable.style.opacity = '1';
        });
    },

    applyRowIndices: function () {
        document.querySelectorAll('.rbn-table tbody tr').forEach((tr, index) => {
            tr.style.setProperty('--row-index', index);
        });
    },

    filterTable: function (tableId, query) {
        const table = document.getElementById(tableId);
        if (!table) return;

        const rows = table.querySelectorAll('tbody tr');
        let visibleCount = 0;

        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            if (text.includes(query)) {
                row.style.display = '';
                row.style.setProperty('--row-index', visibleCount++);
                // Re-trigger reveal animation
                row.style.animation = 'none';
                row.offsetHeight; // force reflow
                row.style.animation = 'rbnFadeUp 0.4s ease forwards';
            } else {
                row.style.display = 'none';
            }
        });

        // Handle empty state if needed
        const emptyState = document.getElementById(tableId + '-empty');
        if (emptyState) {
            emptyState.style.display = visibleCount === 0 ? '' : 'none';
        }
    },

    sortTable: function (table, column, type, explicitDir = null) {
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));

        let isAsc;
        if (explicitDir) {
            isAsc = explicitDir === 'asc';
        } else {
            isAsc = table.getAttribute('data-sort-order') !== 'asc';
        }

        const sortedRows = rows.sort((a, b) => {
            const aCell = a.children[column];
            const bCell = b.children[column];
            let aVal = (type === 'number' || type === 'int') 
                ? (aCell.getAttribute('data-sort-num') || aCell.getAttribute('data-sort-val') || aCell.textContent.trim())
                : (aCell.getAttribute('data-sort-val') || aCell.textContent.trim());
            let bVal = (type === 'number' || type === 'int') 
                ? (bCell.getAttribute('data-sort-num') || bCell.getAttribute('data-sort-val') || bCell.textContent.trim())
                : (bCell.getAttribute('data-sort-val') || bCell.textContent.trim());

            if (type === 'number' || type === 'int' || type === 'size') {
                aVal = this.parseNumericValue(aVal);
                bVal = this.parseNumericValue(bVal);
                return isAsc ? aVal - bVal : bVal - aVal;
            }

            if (type === 'date') {
                aVal = new Date(aVal).getTime() || 0;
                bVal = new Date(bVal).getTime() || 0;
                return isAsc ? aVal - bVal : bVal - aVal;
            }

            return isAsc ? aVal.localeCompare(bVal, 'tr') : bVal.localeCompare(aVal, 'tr');
        });

        while (tbody.firstChild) tbody.removeChild(tbody.firstChild);

        sortedRows.forEach((row, index) => {
            row.style.setProperty('--row-index', index);
            tbody.appendChild(row);
        });

        table.setAttribute('data-sort-order', isAsc ? 'asc' : 'desc');
    },

    parseNumericValue: function (val) {
        // Clean up common units (MB, KB, Bytes)
        const num = parseFloat(val.replace(/[^0-9.]/g, ''));
        if (val.includes('GB')) return num * 1024 * 1024;
        if (val.includes('MB')) return num * 1024;
        if (val.includes('KB')) return num;
        return isNaN(num) ? 0 : num;
    },

    /* ========================================================
       TOPLU İŞLEM MOTORU (Bulk Actions Engine) 📋⚡
       ======================================================== */
    initBulkActions: function () {
        // 1. "Tümünü Seç" (Master checkbox)
        document.addEventListener('change', (e) => {
            if (e.target && e.target.classList.contains('bulk-check-all')) {
                const isChecked = e.target.checked;
                const table = e.target.closest('table') || document;
                const checkboxes = table.querySelectorAll('.bulk-check-item');

                checkboxes.forEach(cb => {
                    cb.checked = isChecked;
                    this.toggleRowHighlight(cb);
                });
            }
        });

        // 2. Tekil checkbox elemanları
        document.addEventListener('change', (e) => {
            if (e.target && e.target.classList.contains('bulk-check-item')) {
                this.toggleRowHighlight(e.target);
            }
        });

        // 3. Toplu İşlem Butonları (.action-bulk)
        document.addEventListener('click', (e) => {
            const btn = e.target.closest('.action-bulk, [data-rbn-bulk-action]');
            if (!btn) return;

            e.preventDefault();
            const targetId = btn.getAttribute('data-target');
            let container = targetId ? (document.getElementById(targetId) || document) : (btn.closest('.bulk-wrapper') || btn.closest('.bulk-container') || document);

            const selected = [];
            container.querySelectorAll('.bulk-check-item:checked').forEach(cb => {
                selected.push(cb.value);
            });

            if (selected.length === 0) {
                if (typeof window.RbnAlert !== 'undefined') {
                    window.RbnAlert.warning('Seçim Yapılmadı', 'Lütfen işlem yapmak için en az bir öğe seçin.');
                }
                return;
            }

            const url = btn.getAttribute('data-url') || btn.getAttribute('href');
            const action = btn.getAttribute('data-action') || 'delete';
            const title = btn.getAttribute('data-title') || 'Toplu İşlem Onayı';
            const message = btn.getAttribute('data-message') || `${selected.length} öğe üzerinde işlem yapılacaktır. Emin misiniz?`;

            if (typeof window.RbnAlert !== 'undefined' && typeof window.RbnAlert.ajaxConfirm === 'function') {
                window.RbnAlert.ajaxConfirm({
                    title: title,
                    text: message,
                    url: url,
                    method: 'POST',
                    data: { ids: selected, action: action },
                    onSuccess: () => {
                        window.location.reload();
                    }
                });
            } else if (confirm(`${title}\n${message}`) && url) {
                if (typeof window.RbnService !== 'undefined') {
                    window.RbnService.post(url, { ids: selected, action: action }).then(() => window.location.reload());
                }
            }
        });
    },

    toggleRowHighlight: function (checkbox) {
        const row = checkbox.closest('tr');
        if (row) {
            if (checkbox.checked) {
                row.classList.add('row-selected');
            } else {
                row.classList.remove('row-selected');
            }
        }
    }
};

// Global Exports
window.rbnTable = rbnTable;
window.RbnBulkAction = rbnTable; // Geriye dönük uyumluluk 🛡️

// Global Initialization
if (typeof rbnReady === 'function') {
    rbnReady(function () {
        rbnTable.init();
    });
} else {
    document.addEventListener('DOMContentLoaded', function () {
        rbnTable.init();
    });
}

})();

