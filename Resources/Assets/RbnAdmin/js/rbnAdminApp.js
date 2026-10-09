/**
 * rbnAdminApp.js — Master Admin Panel Application Suite 💻🛰️⚓
 * Part of the RBN Framework Architecture.
 * 
 * Consolidates Admin Core Logic: Layout & Sidebar, Panel Counters & Modals, Theme Switcher, and Session Timeout Monitor.
 */
(function () {
    'use strict';

    // ==========================================================================
    // 1. LAYOUT & SIDEBAR CONTROLLER (RBN Framework .rbn-dash-* Architecture)
    const SIDEBAR_STORAGE_KEY = 'rbn_admin_sidebar_collapsed';
    const CATEGORY_STORAGE_KEY = 'rbn_admin_sidebar_categories';

    function getSidebarElement() {
        return document.querySelector('.rbn-dash-sidebar') || document.getElementById('sidebar');
    }

    // ⚡ İLK YÜKLEME (SSoT: rbnDom.js motorunu çağırır, mükerrer kod barındırmaz)
    if (typeof window.rbnInitSidebar === 'function') {
        window.rbnInitSidebar();
    }

    function createBackdrop() {
        let backdrop = document.querySelector('.rbn-sidebar-backdrop');
        if (!backdrop) {
            backdrop = document.createElement('div');
            backdrop.className = 'rbn-sidebar-backdrop';
            backdrop.style.cssText = 'position:fixed;inset:0;background:rgba(17,24,39,0.55);backdrop-filter:blur(3px);z-index:999;transition:opacity 0.2s ease;';
            backdrop.addEventListener('click', function () {
                const sidebar = getSidebarElement();
                if (sidebar) sidebar.classList.remove('show');
                backdrop.remove();
            });
            document.body.appendChild(backdrop);
        }
    }

    function removeBackdrop() {
        const backdrop = document.querySelector('.rbn-sidebar-backdrop');
        if (backdrop) backdrop.remove();
    }

    function toggleSidebar() {
        const sidebar = getSidebarElement();
        if (!sidebar) return;

        const isMobile = window.innerWidth <= 768;

        if (isMobile) {
            const isShown = sidebar.classList.toggle('show');
            if (isShown) {
                createBackdrop();
            } else {
                removeBackdrop();
            }
            return;
        }

        const isCollapsed = sidebar.classList.toggle('collapsed');
        document.documentElement.classList.toggle('sidebar-collapsed', isCollapsed);

        // Durumu localStorage'a kaydet (sadece masaüstünde)
        if (window.rbnStorage) {
            window.rbnStorage.sidebar.setCollapsed(isCollapsed, SIDEBAR_STORAGE_KEY);
        } else {
            try {
                localStorage.setItem(SIDEBAR_STORAGE_KEY, isCollapsed ? 'true' : 'false');
            } catch (e) {}
        }

        if (isCollapsed) {
            cleanupDropdowns();
        }
    }

    function cleanupDropdowns() {
        document.querySelectorAll('.rbn-dash-sidebar .rbn-dash-dropdown').forEach(item => {
            item.classList.remove('active', 'show');
            const menu = item.querySelector('.rbn-dash-dropdown-menu');
            if (menu) menu.style.cssText = '';
        });
    }

    function getCategoryStates() {
        if (window.rbnStorage) {
            return window.rbnStorage.get(CATEGORY_STORAGE_KEY, {});
        }
        try {
            return JSON.parse(localStorage.getItem(CATEGORY_STORAGE_KEY) || '{}');
        } catch (e) {
            return {};
        }
    }

    function saveCategoryState(categoryId, isClosed) {
        if (!categoryId) return;
        const states = getCategoryStates();
        states[categoryId] = isClosed;
        if (window.rbnStorage) {
            window.rbnStorage.set(CATEGORY_STORAGE_KEY, states);
        } else {
            try {
                localStorage.setItem(CATEGORY_STORAGE_KEY, JSON.stringify(states));
            } catch (e) {}
        }
    }

    function toggleCategory(element) {
        const category = element.closest('.rbn-dash-category');
        if (!category) return;

        const categoryId = category.getAttribute('data-category-id') || category.querySelector('.rbn-dash-section-header span')?.textContent?.trim();
        const isClosed = category.classList.toggle('closed');
        saveCategoryState(categoryId, isClosed);
    }

    function toggleDropdown(element) {
        const dropdownItem = element.closest('.rbn-dash-dropdown');
        if (!dropdownItem) return;

        const isActive = dropdownItem.classList.contains('active');
        const sidebar = getSidebarElement();
        const isCollapsed = sidebar ? sidebar.classList.contains('collapsed') : false;

        // Close other dropdowns
        document.querySelectorAll('.rbn-dash-sidebar .rbn-dash-dropdown').forEach(item => {
            if (item !== dropdownItem) {
                item.classList.remove('active', 'show');
                const menu = item.querySelector('.rbn-dash-dropdown-menu');
                if (menu) menu.style.cssText = '';
            }
        });

        if (!isActive) {
            dropdownItem.classList.add('active', 'show');
            if (isCollapsed) {
                const link = dropdownItem.querySelector('.rbn-dash-nav-link');
                const menu = dropdownItem.querySelector('.rbn-dash-dropdown-menu');
                if (link && menu) {
                    const rect = link.getBoundingClientRect();
                    menu.style.position = 'fixed';
                    menu.style.top = rect.top + 'px';
                    menu.style.left = (rect.right + 10) + 'px';
                    menu.style.display = 'block';
                    menu.style.zIndex = '1050';
                }
            }
        } else {
            dropdownItem.classList.remove('active', 'show');
            const menu = dropdownItem.querySelector('.rbn-dash-dropdown-menu');
            if (menu) menu.style.cssText = '';
        }
    }

    function setupLayoutHandlers() {
        // Document-level delegated click for maximum reliability without duplicate firings
        document.addEventListener('click', function (e) {
            const dropdownLink = e.target.closest('.rbn-dash-sidebar .rbn-dash-dropdown > .rbn-dash-nav-link');
            if (dropdownLink) {
                // If element already has inline onclick="rbnAdminToggleDropdown(this)", do not double-toggle
                if (!dropdownLink.getAttribute('onclick')) {
                    e.preventDefault();
                    toggleDropdown(dropdownLink);
                }
                return;
            }

            const sectionHeader = e.target.closest('.rbn-dash-sidebar .rbn-dash-section-header');
            if (sectionHeader) {
                if (!sectionHeader.getAttribute('onclick')) {
                    e.preventDefault();
                    toggleCategory(sectionHeader);
                }
                return;
            }

            const sidebar = getSidebarElement();
            if (sidebar && sidebar.classList.contains('collapsed')) {
                if (!e.target.closest('.rbn-dash-sidebar')) {
                    cleanupDropdowns();
                }
            }
        });

        document.querySelectorAll('.rbn-dash-sidebar .rbn-dash-dropdown').forEach(item => {
            item.addEventListener('mouseenter', function () {
                const sidebar = getSidebarElement();
                if (sidebar && sidebar.classList.contains('collapsed')) {
                    const link = this.querySelector('.rbn-dash-nav-link');
                    const menu = this.querySelector('.rbn-dash-dropdown-menu');
                    if (link && menu) {
                        const rect = link.getBoundingClientRect();
                        menu.style.position = 'fixed';
                        menu.style.top = rect.top + 'px';
                        menu.style.left = (rect.right + 10) + 'px';
                        menu.style.display = 'block';
                        menu.style.zIndex = '1050';
                    }
                }
            });

            item.addEventListener('mouseleave', function () {
                const sidebar = getSidebarElement();
                if (sidebar && sidebar.classList.contains('collapsed')) {
                    if (!this.classList.contains('active')) {
                        const menu = this.querySelector('.rbn-dash-dropdown-menu');
                        if (menu) menu.style.cssText = '';
                    }
                }
            });
        });

        window.addEventListener('resize', function () {
            const sidebar = getSidebarElement();
            if (sidebar && sidebar.classList.contains('collapsed')) {
                cleanupDropdowns();
            }
        });

        document.addEventListener('click', function (e) {
            const sidebar = getSidebarElement();
            if (sidebar && sidebar.classList.contains('collapsed')) {
                if (!e.target.closest('.rbn-dash-sidebar')) {
                    cleanupDropdowns();
                }
            }
        });
    }

    // Expose global layout functions (Namespaced & RBN Framework)
    window.rbnAdminToggleSidebar = toggleSidebar;
    window.rbnAdminToggleDropdown = toggleDropdown;
    window.rbnAdminToggleCategory = toggleCategory;

    // Backward Compatibility Aliases
    window.toggleSidebar = toggleSidebar;
    window.toggleDropdown = toggleDropdown;
    window.toggleCategory = toggleCategory;


    // ==========================================================================
    // 2. PANEL COUNTERS & GLOBAL MODALS
    // ==========================================================================
    function initGlobalModalEvents() {
        const modalEl = document.getElementById('universalRbnModal') || document.getElementById('universalAdminModal');
        if (!modalEl) return;

        modalEl.addEventListener('hidden.bs.modal', function (event) {
            const trigger = (typeof RbnModal !== 'undefined') ? RbnModal.lastTrigger : document.querySelector('.rbn-page-refresh-active');

            if (!trigger) return;

            const isRefreshRequested = trigger.classList.contains('rbn-page-refresh') ||
                trigger.classList.contains('rbn-page-refresh-active') ||
                event.target.getAttribute('data-refresh') === 'true';

            if (isRefreshRequested) {
                refreshNavbarCounters();
                updateLocalRowStatus(trigger);

                trigger.classList.remove('rbn-page-refresh-active');
                if (typeof RbnModal !== 'undefined') RbnModal.lastTrigger = null;
            }
        });
    }

    function updateLocalRowStatus(trigger) {
        const id = trigger.getAttribute('data-id');
        let containers = [
            trigger.closest('tr'),
            trigger.closest('.noti-row'),
            trigger.closest('.message-row'),
            trigger.closest('.dropdown-item')
        ].filter(el => el !== null);

        if (id) {
            const extraRows = document.querySelectorAll(`[data-id="${id}"]`);
            extraRows.forEach(row => {
                if (!containers.includes(row)) containers.push(row);
            });
        }

        if (containers.length === 0) return;

        containers.forEach(container => {
            if (container.classList.contains('dropdown-item')) {
                container.style.transition = 'all 0.4s ease';
                container.style.opacity = '0';
                container.style.transform = 'translateX(20px)';
                container.style.pointerEvents = 'none';

                setTimeout(() => {
                    const li = container.closest('li');
                    if (li) li.remove();
                }, 400);
                return;
            }

            const badges = container.querySelectorAll('.badge');
            badges.forEach(badge => {
                const text = badge.textContent.trim().toUpperCase();
                if (['YENİ', 'NEW', 'OKUNMADI', 'BEKLEYEN'].includes(text)) {
                    badge.textContent = 'OKUNDU';
                    badge.className = 'badge bg-light text-muted border rounded-pill px-3 fw-medium';
                }
            });

            const icons = container.querySelectorAll('.bi-circle-fill.text-primary');
            icons.forEach(icon => icon.remove());

            container.classList.remove('fw-bold', 'border-primary');
            container.querySelectorAll('.fw-bold').forEach(el => el.classList.remove('fw-bold'));
        });
    }

    function refreshNavbarCounters() {
        const navbar = document.querySelector('.top-navbar');
        const url = navbar ? navbar.getAttribute('data-counts-url') : null;

        if (!url || typeof RbnService === 'undefined') return;

        RbnService.get(url, {}, { silent: true }).then(response => {
            if (response.success && response.data) {
                updateCounterBadge('notifications-count', response.data.noti);
                updateCounterBadge('messages-count', response.data.msg);
            }
        }).catch(error => console.error('[RBN Panel] Failed to refresh counters:', error));
    }

    function updateCounterBadge(id, count) {
        const badge = document.getElementById(id);
        if (!badge) return;

        const displayCount = count > 99 ? '99+' : count;
        badge.textContent = displayCount;

        if (count > 0) {
            badge.classList.remove('d-none');
        } else {
            badge.classList.add('d-none');
        }
    }


    // ==========================================================================
    // 3. THEME SWITCHER CONTROLLER
    // ==========================================================================
    function hexToHsl(hex) {
        if (!hex) return { h: 38, s: '92%', l: '50%' };
        hex = hex.replace(/^#/, '');
        if (hex.length === 3) hex = hex.split('').map(c => c + c).join('');
        const num = parseInt(hex, 16);
        if (isNaN(num)) return { h: 38, s: '92%', l: '50%' };
        const r = (num >> 16) & 255;
        const g = (num >> 8) & 255;
        const b = num & 255;

        const rNorm = r / 255, gNorm = g / 255, bNorm = b / 255;
        const max = Math.max(rNorm, gNorm, bNorm), min = Math.min(rNorm, gNorm, bNorm);
        let h = 0, s = 0, l = (max + min) / 2;

        if (max !== min) {
            const d = max - min;
            s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
            switch (max) {
                case rNorm: h = (gNorm - bNorm) / d + (gNorm < bNorm ? 6 : 0); break;
                case gNorm: h = (bNorm - rNorm) / d + 2; break;
                case bNorm: h = (rNorm - gNorm) / d + 4; break;
            }
            h /= 6;
        }
        return {
            h: Math.round(h * 360),
            s: Math.round(s * 100) + '%',
            l: Math.round(l * 100) + '%'
        };
    }

    function applyPreviewColors(primary, secondary) {
        const root = document.documentElement;
        if (primary) {
            root.style.setProperty('--rbn-primary', primary);
            root.style.setProperty('--theme-600', primary);
            root.style.setProperty('--theme-500', primary);
            root.style.setProperty('--theme-400', primary);
            const hsl = hexToHsl(primary);
            root.style.setProperty('--rbn-theme-h', hsl.h);
            root.style.setProperty('--rbn-theme-s', hsl.s);
            root.style.setProperty('--rbn-theme-l', hsl.l);
        }
        if (secondary) {
            root.style.setProperty('--rbn-secondary', secondary);
            root.style.setProperty('--rbn-bg-dark', secondary);
        }
    }

    function initThemeCustomizer() {
        const customizerPanel = document.querySelector('.rbn-theme-customizer-panel');
        if (!customizerPanel) return;

        const presetCards = customizerPanel.querySelectorAll('.rbn-preset-card');
        const hexPrimary = document.getElementById('hex_primary');
        const hexSecondary = document.getElementById('hex_secondary');
        const colorPrimaryPicker = document.getElementById('picker_primary');
        const colorSecondaryPicker = document.getElementById('picker_secondary');
        const themeForm = document.getElementById('customThemeForm');

        // Preset cards click handler
        presetCards.forEach(card => {
            card.addEventListener('click', function () {
                presetCards.forEach(c => c.classList.remove('active'));
                this.classList.add('active');

                const primary = this.getAttribute('data-primary') || this.getAttribute('data-preset-primary');
                const secondary = this.getAttribute('data-secondary') || this.getAttribute('data-preset-secondary');

                if (hexPrimary) hexPrimary.value = primary;
                if (hexSecondary) hexSecondary.value = secondary;
                if (colorPrimaryPicker) colorPrimaryPicker.value = primary;
                if (colorSecondaryPicker) colorSecondaryPicker.value = secondary;

                applyPreviewColors(primary, secondary);
            });
        });

        // Picker primary input
        if (colorPrimaryPicker && hexPrimary) {
            colorPrimaryPicker.addEventListener('input', function () {
                hexPrimary.value = this.value;
                applyPreviewColors(this.value, hexSecondary ? hexSecondary.value : null);
            });
        }

        // Hex primary input
        if (hexPrimary && colorPrimaryPicker) {
            hexPrimary.addEventListener('input', function () {
                if (/^#[0-9A-Fa-f]{6}$/.test(this.value)) {
                    colorPrimaryPicker.value = this.value;
                    applyPreviewColors(this.value, hexSecondary ? hexSecondary.value : null);
                }
            });
        }

        // Picker secondary input
        if (colorSecondaryPicker && hexSecondary) {
            colorSecondaryPicker.addEventListener('input', function () {
                hexSecondary.value = this.value;
                applyPreviewColors(hexPrimary ? hexPrimary.value : null, this.value);
            });
        }

        // Hex secondary input
        if (hexSecondary && colorSecondaryPicker) {
            hexSecondary.addEventListener('input', function () {
                if (/^#[0-9A-Fa-f]{6}$/.test(this.value)) {
                    colorSecondaryPicker.value = this.value;
                    applyPreviewColors(hexPrimary ? hexPrimary.value : null, this.value);
                }
            });
        }

        // Form submit handler
        if (themeForm) {
            themeForm.addEventListener('submit', function (e) {
                e.preventDefault();
                const saveUrl = this.getAttribute('data-save-url');
                const submitBtn = document.getElementById('btnSaveTheme');

                if (!saveUrl) return;

                const primary = hexPrimary ? hexPrimary.value : '#f59e0b';
                const secondary = hexSecondary ? hexSecondary.value : '#0f172a';

                applyPreviewColors(primary, secondary);

                const data = {
                    theme_color_primary: primary,
                    theme_color_secondary: secondary
                };

                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Kaydediliyor...';
                }

                if (typeof RbnService !== 'undefined') {
                    RbnService.post(saveUrl, data)
                        .then(res => {
                            if (typeof RbnAlert !== 'undefined') {
                                RbnAlert.success('Tema başarıyla kaydedildi!', 'Tema Güncellendi');
                            }
                            if (submitBtn) {
                                submitBtn.disabled = false;
                                submitBtn.innerHTML = '<i class="ri-check-line me-1"></i> Kaydedildi!';
                                setTimeout(() => {
                                    submitBtn.innerHTML = '<i class="ri-save-line me-1"></i> Rengi Kaydet & Uygula';
                                }, 2000);
                            }
                        })
                        .catch(err => {
                            if (submitBtn) {
                                submitBtn.disabled = false;
                                submitBtn.innerHTML = '<i class="ri-save-line me-1"></i> Rengi Kaydet & Uygula';
                            }
                        });
                } else {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<i class="ri-save-line me-1"></i> Rengi Kaydet & Uygula';
                    }
                }
            });
        }
    }

    window.ThemeSwitcher = { applyPreviewColors };


    // ==========================================================================
    // 4. SESSION TIMEOUT & LOCKSCREEN CONTROLLER
    // ==========================================================================
    let timeoutTimer;
    const securityConfig = window.RBN_SECURITY || {};
    const timeoutSeconds = parseInt(securityConfig.SESSION_TIMEOUT) || 1800; // Default 30 mins
    const lockscreenUrl = securityConfig.LOCKSCREEN_URL || '/lockscreen';

    function resetInactivityTimer() {
        if (timeoutTimer) clearTimeout(timeoutTimer);
        timeoutTimer = setTimeout(redirectAfterTimeout, timeoutSeconds * 1000);
    }

    function redirectAfterTimeout() {
        const currentPath = window.location.pathname;
        if (currentPath.includes('lockscreen') || currentPath.includes('login') || currentPath.includes('logout')) {
            return;
        }
        window.location.href = lockscreenUrl + '?action=lock';
    }

    function initInactivityMonitor() {
        if (timeoutSeconds <= 0) return;

        const events = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart'];
        events.forEach(name => {
            document.addEventListener(name, resetInactivityTimer, { passive: true });
        });

        resetInactivityTimer();
    }


    // ==========================================================================
    // 5. MASTER INITIALIZATION
    // ==========================================================================
    function initMasterApp() {
        if (typeof window.rbnInitSidebar === 'function') {
            window.rbnInitSidebar();
        }
        setupLayoutHandlers();
        initGlobalModalEvents();
        initThemeCustomizer();
        initInactivityMonitor();

        // Server Health Offcanvas Akıllı Kapanma Kontrolleri (ESC, Dış Tıklama & Mouseleave) 🚪✨
        const healthCanvas = document.getElementById('serverHealthCanvas') || document.getElementById('themeCustomizerCanvas');
        if (healthCanvas) {
            // 1. Mouse üzerinden ayrılınca yumuşakça kapat
            healthCanvas.addEventListener('mouseleave', () => {
                if (typeof bootstrap !== 'undefined' && bootstrap.Offcanvas) {
                    const bsOffcanvas = bootstrap.Offcanvas.getInstance(healthCanvas);
                    if (bsOffcanvas) bsOffcanvas.hide();
                } else {
                    healthCanvas.classList.remove('show');
                    document.querySelectorAll('.offcanvas-backdrop').forEach(el => el.remove());
                }
            });

            // 2. ESC Tuşuna basınca veya dışarı tıklayınca kapatma güvencesi
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && healthCanvas.classList.contains('show')) {
                    if (typeof bootstrap !== 'undefined' && bootstrap.Offcanvas) {
                        const bsOffcanvas = bootstrap.Offcanvas.getInstance(healthCanvas);
                        if (bsOffcanvas) bsOffcanvas.hide();
                    } else {
                        healthCanvas.classList.remove('show');
                        document.querySelectorAll('.offcanvas-backdrop').forEach(el => el.remove());
                    }
                }
            });

            // 3. Çekmece dışına tıklandığında anında kapat
            document.addEventListener('click', (e) => {
                if (healthCanvas.classList.contains('show') && !healthCanvas.contains(e.target) && !e.target.closest('[data-bs-target="#serverHealthCanvas"]')) {
                    if (typeof bootstrap !== 'undefined' && bootstrap.Offcanvas) {
                        const bsOffcanvas = bootstrap.Offcanvas.getInstance(healthCanvas);
                        if (bsOffcanvas) bsOffcanvas.hide();
                    } else {
                        healthCanvas.classList.remove('show');
                        document.querySelectorAll('.offcanvas-backdrop').forEach(el => el.remove());
                    }
                }
            });
        }
    }

    function startMaster() {
        initMasterApp();
        requestAnimationFrame(() => {
            document.body.classList.add('rbn-ready');
        });
    }

    if (typeof window.rbnReady === 'function') {
        window.rbnReady(startMaster);
    } else if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', startMaster);
    } else {
        startMaster();
    }

})();
