/**
 * core/rbnDom.js — Universal DOM Utilities & Micro-Interactions 🛠️🎭
 * Handles: Password Toggle, Password Generator, Copy-to-Clipboard, Phone Masking, Tooltips, Autocomplete Guard
 */
(function (window, document) {
    'use strict';

    /* ==========================================================================
       [ 0. RBN UNIVERSAL STORAGE & THEME ENGINE ] 🛡️💾🌐
       ========================================================================== */
    const rbnStorage = {
        /**
         * Güvenli localStorage okuyucu (Otomatik JSON parse ve fallback destekli) 📖
         */
        get(key, defaultValue = null) {
            try {
                const item = localStorage.getItem(key);
                if (item === null || item === undefined) return defaultValue;
                try {
                    return JSON.parse(item);
                } catch {
                    return item;
                }
            } catch (e) {
                return defaultValue;
            }
        },

        /**
         * Güvenli localStorage yazıcı (Otomatik JSON stringify) ✍️
         */
        set(key, value) {
            try {
                const val = (typeof value === 'object' && value !== null) ? JSON.stringify(value) : String(value);
                localStorage.setItem(key, val);
                return true;
            } catch (e) {
                return false;
            }
        },

        /**
         * Güvenli anahtar silici 🗑️
         */
        remove(key) {
            try {
                localStorage.removeItem(key);
                return true;
            } catch (e) {
                return false;
            }
        },

        /**
         * 🎨 Evrensel Tema Yönetimi (Dark / Light)
         */
        theme: {
            get(key = null, defaultTheme = 'dark') {
                const storageKey = key || document.documentElement.getAttribute('data-theme-key') || 'rbn_theme';
                return rbnStorage.get(storageKey, defaultTheme);
            },
            set(themeName, key = null) {
                const storageKey = key || document.documentElement.getAttribute('data-theme-key') || 'rbn_theme';
                rbnStorage.set(storageKey, themeName);
                document.documentElement.setAttribute('data-theme', themeName);
                this.updateIcons(themeName);
            },
            toggle(key = null) {
                const current = this.get(key);
                const next = current === 'dark' ? 'light' : 'dark';
                this.set(next, key);
                return next;
            },
            updateIcons(themeName = null) {
                const mode = themeName || this.get();
                const iconElements = document.querySelectorAll('#theme-icon, #theme-icon-mobile, .rbn-theme-icon, [data-rbn-theme-icon]');
                iconElements.forEach(icon => {
                    if (mode === 'light') {
                        icon.classList.remove('ri-moon-line', 'ri-moon-fill');
                        icon.classList.add('ri-sun-line');
                    } else {
                        icon.classList.remove('ri-sun-line', 'ri-sun-fill');
                        icon.classList.add('ri-moon-line');
                    }
                });
            },
            initToggleListeners() {
                this.updateIcons();
                if (this._listenerAttached) return;
                this._listenerAttached = true;
                
                document.addEventListener('click', (e) => {
                    const toggleBtn = e.target && e.target.closest ? e.target.closest('#theme-toggle, #theme-toggle-mobile, .yzg-theme-toggle, .rbn-theme-toggle, [data-rbn-theme-toggle]') : null;
                    if (toggleBtn) {
                        e.preventDefault();
                        this.toggle();
                    }
                });
            }
        },

        /**
         * 📐 Evrensel Sidebar Yönetimi
         */
        sidebar: {
            isCollapsed(key = 'rbn_admin_sidebar_collapsed') {
                const val = rbnStorage.get(key, false);
                return val === true || val === 'true';
            },
            setCollapsed(state, key = 'rbn_admin_sidebar_collapsed') {
                rbnStorage.set(key, Boolean(state));
            },
            init(sidebarId = 'sidebar', categoriesSelector = '#sidebar .rbn-dash-category') {
                try {
                    const sb = document.getElementById(sidebarId);
                    if (sb && this.isCollapsed()) {
                        sb.classList.add('collapsed');
                    }
                    const cats = rbnStorage.get('rbn_admin_sidebar_categories', {});
                    document.querySelectorAll(categoriesSelector).forEach(c => {
                        const id = c.getAttribute('data-category-id') || c.querySelector('.rbn-dash-section-header span')?.textContent?.trim();
                        if (id && cats[id] === true) c.classList.add('closed');
                    });
                } catch (e) {}
            }
        }
    };

    // Global Export
    window.rbnStorage = rbnStorage;
    window.rbnInitSidebar = () => rbnStorage.sidebar.init();

    /* 1. Evrensel Parola Göster/Gizle (Password Toggle) 👁️ */
    let _rbnPassToggleInitialized = false;
    function rbnInitPasswordToggles() {
        if (_rbnPassToggleInitialized) return;
        _rbnPassToggleInitialized = true;

        document.addEventListener('click', function (e) {
            const btn = e.target.closest('[data-rbn-password-toggle], .rbn-auth-pass-toggle, .password-toggle-btn, .rbn-pass-toggle');
            if (!btn) return;

            e.preventDefault();
            e.stopPropagation();

            const targetId = btn.getAttribute('data-rbn-password-toggle');
            let input = targetId ? document.getElementById(targetId) : null;

            if (!input) {
                const wrap = btn.closest('.rbn-auth-input-wrap, .rbn-auth-pass-wrap, .password-toggle-wrap, .input-group') || btn.parentElement;
                input = wrap ? wrap.querySelector('input') : null;
            }
            if (!input) return;

            const wasPass = input.getAttribute('type') === 'password' || input.type === 'password';
            input.setAttribute('type', wasPass ? 'text' : 'password');

            // İkonu değiştir
            const icon = btn.querySelector('i');
            if (icon) {
                if (wasPass) {
                    icon.classList.remove('ri-eye-line', 'bi-eye', 'fa-eye');
                    icon.classList.add('ri-eye-off-line');
                } else {
                    icon.classList.remove('ri-eye-off-line', 'bi-eye-slash', 'fa-eye-slash');
                    icon.classList.add('ri-eye-line');
                }
            }

            // Tooltip Güncellemesi
            const tooltipText = wasPass ? 'Şifreyi Gizle' : 'Şifreyi Göster';
            btn.setAttribute('data-tooltip', tooltipText);
            btn.setAttribute('data-bs-title', tooltipText);
            btn.setAttribute('data-rbn-tooltip', tooltipText);
        });
    }

    /* 2. Evrensel Parola Üretici (Password Generator) 🔑 */
    function rbnInitPasswordGenerators() {
        document.addEventListener('click', function (e) {
            const btn = e.target.closest('[data-rbn-password-generate]');
            if (!btn) return;

            e.preventDefault();
            const targetId = btn.getAttribute('data-rbn-password-generate');
            const length = parseInt(btn.getAttribute('data-rbn-password-length')) || 12;
            const input = document.getElementById(targetId);

            if (input) {
                const chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+~`|}{[]:;?><,./-=';
                let password = '';
                for (let i = 0; i < length; i++) {
                    password += chars.charAt(Math.floor(Math.random() * chars.length));
                }
                input.value = password;
                input.type = 'text';

                const toggleBtn = document.querySelector(`[data-rbn-password-toggle="${targetId}"]`);
                if (toggleBtn) {
                    const icon = toggleBtn.querySelector('i');
                    if (icon) {
                        if (icon.classList.contains('ri-eye-line')) {
                            icon.classList.remove('ri-eye-line');
                            icon.classList.add('ri-eye-off-line');
                        } else if (icon.classList.contains('bi-eye')) {
                            icon.classList.remove('bi-eye');
                            icon.classList.add('bi-eye-slash');
                        }
                    }
                }
            }
        });
    }

    /* 3. Evrensel Pano Kopyalama (Copy to Clipboard) 📋 */
    function rbnInitCopySystem() {
        document.addEventListener('click', function (e) {
            const btn = e.target.closest('[data-rbn-copy]');
            if (!btn) return;

            e.preventDefault();
            const target = btn.getAttribute('data-rbn-copy');
            let textToCopy = target;

            if (target && (target.startsWith('#') || target.startsWith('.'))) {
                const el = document.querySelector(target);
                if (el) textToCopy = el.value || el.innerText.trim();
            } else if (!target) {
                textToCopy = btn.innerText.trim();
            }

            if (textToCopy && navigator.clipboard) {
                navigator.clipboard.writeText(textToCopy).then(() => {
                    if (typeof RbnAlert !== 'undefined') {
                        RbnAlert.success('Kopyalandı', 'Panoya başarıyla kopyalandı.', true);
                    }
                });
            }
        });
    }

    /* 4. Evrensel Telefon & Rakam Maskeleme 🎭 */
    function rbnFormatPhone(phone) {
        if (!phone) return phone || '';
        let digits = phone.toString().replace(/\D/g, '').slice(0, 11);
        if (digits.length > 0 && digits[0] !== '0') digits = '0' + digits;

        let formatted = '';
        if (digits.length > 0) formatted += digits.substring(0, 1);
        if (digits.length > 1) formatted += ' (' + digits.substring(1, 4);
        if (digits.length > 4) formatted += ') ' + digits.substring(4, 7);
        if (digits.length > 7) formatted += ' ' + digits.substring(7, 9);
        if (digits.length > 9) formatted += ' ' + digits.substring(9, 11);
        return formatted;
    }

    function rbnInitMasking() {
        document.querySelectorAll('[data-format="phone"]').forEach(el => {
            const raw = el.dataset.raw || el.textContent.trim();
            el.dataset.raw = raw;
            el.textContent = rbnFormatPhone(raw);
        });

        document.addEventListener('input', function (e) {
            const input = e.target.closest('[data-rbn-mask], input[type="tel"]');
            if (!input) return;

            const mask = input.getAttribute('data-rbn-mask') || (input.type === 'tel' ? 'phone' : '');
            if (mask === 'phone') {
                input.value = rbnFormatPhone(input.value);
            } else if (mask === 'whatsapp' || mask === 'digits') {
                input.value = input.value.replace(/\D/g, '');
            }
        });
    }

    /* 5. Tooltip Engine (Auto-Converts title attribute to Sovereign Pure-CSS [data-tooltip]) 💬 */
    function rbnInitTooltips() {
        document.querySelectorAll('[title]:not([data-tooltip])').forEach(el => {
            // Icon font barındıran (i, svg vb.) etiketlerde ::before pseudo-elementini ezmemek için koruma 🛡️
            const classListStr = el.className || '';
            if (el.tagName === 'I' || (typeof classListStr === 'string' && (classListStr.includes('ri-') || classListStr.includes('bi-') || classListStr.includes('fa-')))) {
                return;
            }
            const titleText = el.getAttribute('title');
            if (titleText && titleText.trim() !== '') {
                el.setAttribute('data-tooltip', titleText.trim());
                el.removeAttribute('title'); // Tarayıcının varsayılan sarı kutusunu engelle
            }
        });
    }

    /* 6. Sovereign Input Protection (Autocomplete Guard) */
    function enforceAutocompleteOff() {
        document.querySelectorAll('form, input').forEach(el => {
            if (!el.hasAttribute('autocomplete')) {
                el.setAttribute('autocomplete', 'off');
            }
        });
    }

    /* 7. Dinamik Yıl Güncelleme */
    function updateCopyrightYear() {
        const year = new Date().getFullYear();
        document.querySelectorAll('#current-year, .rbn-current-year').forEach(el => {
            if (el) el.textContent = year;
        });
    }

    /* 8. Otomatik AOS Animasyon Başlatıcı (Animate On Scroll) 🎭 */
    function rbnInitAos() {
        if (typeof AOS !== 'undefined' && typeof AOS.init === 'function') {
            AOS.init({ duration: 800, once: true, easing: 'ease-in-out' });
        }
    }

    /* 9. Evrensel Çerez Bildirimi Motoru (Cookie Notice Engine) 🍪 */
    function rbnInitCookieNotice() {
        const bar = document.getElementById('rbnCookieNotice');
        const btn = document.getElementById('rbnAcceptCookies');
        if (!bar || !btn) return;

        const consentKey = 'rbn_cookie_consent';

        if (!localStorage.getItem(consentKey)) {
            setTimeout(() => {
                bar.classList.add('active');
                document.body.classList.add('has-cookie-notice');
            }, 1000);
        }

        btn.addEventListener('click', function () {
            const d = new Date();
            d.setTime(d.getTime() + (30 * 24 * 60 * 60 * 1000));
            document.cookie = consentKey + "=accepted; expires=" + d.toUTCString() + "; path=/";

            localStorage.setItem(consentKey, 'accepted');

            bar.classList.remove('active');
            document.body.classList.remove('has-cookie-notice');
            setTimeout(() => {
                bar.remove();
            }, 600);
        });
    }

    /* 10. Evrensel Yukarı Çık Butonu Motoru (Scroll To Top) 🚀 */
    function rbnInitScrollToTop() {
        const btn = document.getElementById('scrollToTopBtn') || document.querySelector('.rbn-scroll-top, [data-rbn-scroll-top]');
        if (!btn) return;

        const dashContent = document.querySelector('.rbn-dash-content');

        const checkScroll = function () {
            const windowScroll = window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop || 0;
            const containerScroll = dashContent ? dashContent.scrollTop : 0;
            const currentScroll = Math.max(windowScroll, containerScroll);

            if (currentScroll > 200) {
                btn.classList.add('active', 'show');
            } else {
                btn.classList.remove('active', 'show');
            }
        };

        window.addEventListener('scroll', checkScroll, { passive: true });
        if (dashContent) {
            dashContent.addEventListener('scroll', checkScroll, { passive: true });
        }

        // İlk yükleme kontrolü
        checkScroll();

        // Robust Click & Touch Handler
        const doScrollTop = function (e) {
            e.preventDefault();
            e.stopPropagation();
            if (dashContent && dashContent.scrollTop > 0) {
                dashContent.scrollTo({ top: 0, behavior: 'smooth' });
            }
            window.scrollTo({ top: 0, behavior: 'smooth' });
            document.documentElement.scrollTo({ top: 0, behavior: 'smooth' });
            document.body.scrollTo({ top: 0, behavior: 'smooth' });
        };

        btn.addEventListener('click', doScrollTop);
        btn.addEventListener('touchend', doScrollTop);
    }

    // Immediate & Ready Auto-Initialization 🚀
    function rbnBootstrapDom() {
        rbnStorage.theme.initToggleListeners();
        rbnInitAos();
        rbnInitCookieNotice();
        rbnInitScrollToTop();
        updateCopyrightYear();
        enforceAutocompleteOff();
        rbnInitMasking();
        rbnInitPasswordToggles();
        rbnInitPasswordGenerators();
        rbnInitCopySystem();
        rbnInitTooltips();
    }

    // Attach listeners immediately
    rbnBootstrapDom();

    // Also run on rbnReady and DOMContentLoaded
    if (typeof window.rbnReady === 'function') {
        window.rbnReady(rbnBootstrapDom);
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', rbnBootstrapDom);
    }

    // Exports
    window.rbnStorage = rbnStorage;
    window.rbnFormatPhone = rbnFormatPhone;
    window.updateCopyrightYear = updateCopyrightYear;

})(window, document);
