/**
 * ==========================================================================
 * RbnModal v2.0 — The Universal Zero-Dependency Modal Engine 🪟⚡
 * ==========================================================================
 * 100% Native Pure JavaScript (Zero Bootstrap JS & Zero jQuery Dependency).
 * Works universally on Frontend (Clients), Admin Panel, and Auth pages.
 * Handles: AJAX Modals, Dynamic Forms, Geo Logic, Backdrops & Keyboard Accessibility.
 */
(function (window, document) {
    'use strict';

    const RbnModal = {
        defaultModalId: 'universalRbnModal',
        activeModal: null,
        activeBackdrop: null,
        lastTrigger: null,

        /**
         * Initialize event listeners for data-attributes (Universal Event Delegation)
         */
        init: function () {
            const self = this;

            // 1. Modal Tetikleyicileri ([data-rbn-modal="true"], [data-bs-toggle="modal"], [data-rbn-toggle="modal"])
            document.addEventListener('click', function (e) {
                const trigger = e.target.closest('[data-rbn-modal="true"], [data-rbn-toggle="modal"], .notification-modal-btn');
                if (trigger) {
                    e.preventDefault();
                    self.lastTrigger = trigger;
                    self.handleTrigger(trigger);
                    return;
                }

                // Modal Kapatma Butonları ([data-bs-dismiss="modal"], [data-rbn-dismiss="modal"], .btn-close)
                const closeBtn = e.target.closest('[data-bs-dismiss="modal"], [data-rbn-dismiss="modal"], .btn-close, .modal-close-btn');
                if (closeBtn) {
                    e.preventDefault();
                    const modalEl = closeBtn.closest('.modal, .rbn-modal');
                    if (modalEl) self.close(modalEl);
                    return;
                }

                // Modal Dışına (Backdrop / Karartmaya) Tıklanınca Kapatma
                if (e.target.classList.contains('modal') && !e.target.hasAttribute('data-bs-backdrop-static')) {
                    self.close(e.target);
                }
            });

            // 2. ESC Tuşu ile Modal Kapatma
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && self.activeModal) {
                    self.close(self.activeModal);
                }
            });

            // 3. Modal İçi Arama Dinleyicisi (Türkçe Harf Duyarlı)
            const turkishLower = (str) => {
                return (str || '').replace(/İ/g, "i").replace(/I/g, "ı").toLowerCase();
            };

            document.addEventListener('input', function (e) {
                if (e.target.classList.contains('rbn-modal-search')) {
                    const term = turkishLower(e.target.value.trim());
                    const container = e.target.closest('.dropdown-menu, .rbn-dropdown-menu');
                    if (!container) return;

                    const items = container.querySelectorAll('.dropdown-item, .rbn-dropdown-item');
                    items.forEach(item => {
                        const text = turkishLower(item.textContent);
                        item.parentElement.style.display = text.includes(term) ? 'block' : 'none';
                    });
                }
            });

            // 4. Modal İçi Dropdown Seçimi
            document.addEventListener('click', function (e) {
                if (e.target.classList.contains('rbn-modal-search')) {
                    e.stopPropagation();
                }

                const item = e.target.closest('.rbn-modal-dropdown .dropdown-item, .modal .dropdown-item');
                if (item) {
                    const container = item.closest('.rbn-modal-dropdown, .dropdown');
                    if (!container) return;

                    const btn = container.querySelector('.dropdown-toggle');
                    const input = container.querySelector('input[type="hidden"]');
                    const selectedText = btn ? (btn.querySelector('.selected-text') || btn) : null;

                    const value = item.getAttribute('data-value') || item.getAttribute('value');
                    const label = item.getAttribute('data-label') || item.textContent.trim();

                    if (selectedText) selectedText.innerHTML = label;
                    if (input) {
                        input.value = value;
                        input.dispatchEvent(new Event('change', { bubbles: true }));

                        // Smart Geo Logic
                        if (input.getAttribute('data-rbn-geo') === 'country') {
                            RbnModal.handleGeoLogic(value, container.closest('.modal-body') || document);
                        }
                    }

                    container.querySelectorAll('.dropdown-item, .rbn-dropdown-item').forEach(el => el.classList.remove('active'));
                    item.classList.add('active');

                    // Seçim yapıldığı an dropdown menüsünü anında kapat 🚀
                    const menu = container.querySelector('.dropdown-menu, .rbn-dropdown-menu');
                    if (menu) {
                        menu.classList.remove('show');
                    }
                }
            });
        },

        /**
         * Handle trigger click and route to modal open
         */
        handleTrigger: function (el) {
            let targetId = el.getAttribute('data-target') || el.getAttribute('data-bs-target') || el.getAttribute('href');
            if (targetId && targetId.startsWith('#')) targetId = targetId.substring(1);

            const modalEl = document.getElementById(targetId || this.defaultModalId);
            if (!modalEl) {
                console.warn(`RbnModal: Modal element #${targetId || this.defaultModalId} bulunamadı.`);
                return;
            }

            let detectedType = el.getAttribute('data-type');
            if (!detectedType) {
                const page = document.querySelector('.rbn-module-page');
                if (page) {
                    const module = page.getAttribute('data-module');
                    const sub = page.getAttribute('data-submodule');
                    detectedType = sub ? `${module}/${sub}` : module;
                }
            }

            const config = {
                modalId: modalEl.id,
                type: detectedType || 'generic',
                id: el.getAttribute('data-id') || null,
                theme: el.getAttribute('data-theme') || 'primary',
                title: el.getAttribute('data-title') || '',
                size: el.getAttribute('data-size') || 'md',
                view: el.getAttribute('data-view') || 'modal',
                endpoint: el.getAttribute('data-endpoint') || null
            };

            this.open(config);
        },

        /**
         * Open modal (Pure Native CSS/DOM - Zero Bootstrap JS)
         */
        open: function (config) {
            const modalId = typeof config === 'string' ? config : (config.modalId || this.defaultModalId);
            const modalEl = document.getElementById(modalId);
            if (!modalEl) return;

            const modalDialog = modalEl.querySelector('.modal-dialog, .rbn-modal-dialog');
            const modalTitle = modalEl.querySelector('.modal-title, .rbn-modal-title');
            const modalBody = modalEl.querySelector('.modal-body, .rbn-modal-body');

            if (typeof config === 'object') {
                if (modalDialog && config.size) {
                    modalDialog.className = `modal-dialog modal-dialog-centered modal-${config.size}`;
                }
                if (modalTitle && config.title) {
                    modalTitle.innerHTML = config.title;
                }
            }

            const showModalDirectly = () => {
                this.showBackdrop();
                modalEl.classList.add('show');
                modalEl.removeAttribute('aria-hidden');
                modalEl.setAttribute('aria-modal', 'true');
                document.body.classList.add('modal-open');
                this.activeModal = modalEl;
                document.dispatchEvent(new CustomEvent('rbnModalOpened', { detail: { modal: modalEl, config: config } }));
            };

            // AJAX İçerik Varsa: Önce İçeriği Çek, Hazır Olduğunda Tek Seferde Pürüzsüz Aç!
            if (typeof config === 'object' && config.endpoint && modalBody) {
                let params = new URLSearchParams();
                if (config.type) params.append('type', config.type);
                if (config.id && config.id !== 'null') params.append('id', config.id);
                if (config.view) params.append('view', config.view);

                const separator = config.endpoint.includes('?') ? '&' : '?';
                const fullUrl = `${config.endpoint}${separator}${params.toString()}`;

                if (typeof window.RbnService !== 'undefined') {
                    window.RbnService.get(fullUrl, {}, { format: 'text', silent: true })
                        .then(html => {
                            modalBody.innerHTML = html;
                            
                            // Gelen içerik kendi modal-header'ına sahipse, dış kabuktakini gizle
                            const shellHeader = modalEl.querySelector('.modal-content > .modal-header');
                            const innerHeader = modalBody.querySelector('.modal-header');
                            if (shellHeader) {
                                shellHeader.style.display = innerHeader ? 'none' : '';
                            }

                            // AJAX ile gelen modal içindeki scriptleri çalıştır ⚡
                            modalBody.querySelectorAll('script').forEach(oldScript => {
                                const newScript = document.createElement('script');
                                Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
                                newScript.appendChild(document.createTextNode(oldScript.innerHTML));
                                oldScript.parentNode.replaceChild(newScript, oldScript);
                            });
                            showModalDirectly();
                            document.dispatchEvent(new CustomEvent('rbnModalLoaded', { detail: { config, container: modalBody } }));
                        })
                        .catch(err => {
                            modalBody.innerHTML = `<div class="alert alert-danger m-3">İçerik yüklenemedi.</div>`;
                            showModalDirectly();
                        });
                    return;
                }
            }

            showModalDirectly();
        },

        /**
         * Close modal (Pure Native)
         */
        close: function (modalOrId) {
            const modalEl = typeof modalOrId === 'string' ? document.getElementById(modalOrId) : (modalOrId || this.activeModal);
            if (!modalEl) return;

            modalEl.classList.remove('show');
            modalEl.setAttribute('aria-hidden', 'true');
            modalEl.removeAttribute('aria-modal');

            this.hideBackdrop();
            document.body.classList.remove('modal-open');
            this.activeModal = null;

            document.dispatchEvent(new CustomEvent('rbnModalClosed', { detail: { modal: modalEl } }));
        },

        /**
         * Show Backdrop Element
         */
        showBackdrop: function () {
            if (this.activeBackdrop) return;

            const backdrop = document.createElement('div');
            backdrop.className = 'modal-backdrop fade show';
            document.body.appendChild(backdrop);
            this.activeBackdrop = backdrop;
        },

        /**
         * Hide Backdrop Element
         */
        hideBackdrop: function () {
            if (this.activeBackdrop) {
                this.activeBackdrop.remove();
                this.activeBackdrop = null;
            }
            document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
        },

        /**
         * AJAX Content Loader
         */
        loadContent: function (config, container) {
            if (!config.endpoint || typeof window.RbnService === 'undefined') return;

            let params = new URLSearchParams();
            if (config.type) params.append('type', config.type);
            if (config.id && config.id !== 'null') params.append('id', config.id);
            if (config.view) params.append('view', config.view);

            const separator = config.endpoint.includes('?') ? '&' : '?';
            const fullUrl = `${config.endpoint}${separator}${params.toString()}`;

            window.RbnService.get(fullUrl, {}, { format: 'text', silent: true })
                .then(html => {
                    container.innerHTML = html;
                    container.style.opacity = '0';
                    container.style.transition = 'opacity 0.18s ease';
                    requestAnimationFrame(() => {
                        container.style.opacity = '1';
                    });
                    document.dispatchEvent(new CustomEvent('rbnModalLoaded', { detail: { config, container } }));
                })
                .catch(err => {
                    container.innerHTML = `
                        <div class="alert alert-danger m-3">
                            <i class="ri-error-warning-line me-2"></i> İçerik yüklenirken bir hata oluştu.
                        </div>
                    `;
                    console.error('RbnModal Load Error:', err);
                });
        },

        /**
         * Verileri Bir Modal Formuna Doldurarak Açar (Universal Data Modal)
         */
        openDataModal: function (modalId, formId, actionUrl, data = {}, onBeforeShow = null) {
            const modalElement = document.getElementById(modalId);
            const form = document.getElementById(formId);
            if (!modalElement || !form) return;

            if (actionUrl) {
                form.action = actionUrl + (data.id || '');
            }

            for (let key in data) {
                let field = form.querySelector('#edit_' + key) || form.querySelector('[name="' + key + '"]');
                if (field) {
                    if (field.tagName === 'SELECT') {
                        field.value = data[key];
                        field.dispatchEvent(new Event('change'));
                    } else if (field.type === 'checkbox') {
                        field.checked = !!data[key];
                    } else if (field.type !== 'file') {
                        field.value = data[key];
                    }
                }
            }

            if (typeof onBeforeShow === 'function') {
                onBeforeShow(data, form);
            }

            this.open(modalId);
        },

        /**
         * Smart Geo Logic Handler (Global)
         */
        handleGeoLogic: function (countryCode, container) {
            const cityInput = container.querySelector('[data-rbn-geo="city"]');
            if (!cityInput) return;

            const cityContainer = cityInput.closest('.rbn-modal-dropdown, .dropdown');
            if (!cityContainer) return;

            const cityBtn = cityContainer.querySelector('.dropdown-toggle');
            const cityText = cityBtn ? cityBtn.querySelector('.selected-text') : null;

            if (countryCode !== 'TR') {
                if (cityText) cityText.innerText = 'Global / Yurt Dışı';
                cityInput.value = 'Global';
                if (cityBtn) {
                    cityBtn.classList.add('disabled', 'bg-light', 'opacity-75');
                    cityBtn.style.pointerEvents = 'none';
                }
            } else {
                if (cityInput.value === 'Global') {
                    if (cityText) cityText.innerText = 'İstanbul';
                    cityInput.value = 'İstanbul';
                }
                if (cityBtn) {
                    cityBtn.classList.remove('disabled', 'bg-light', 'opacity-75');
                    cityBtn.style.pointerEvents = 'auto';
                }
            }
        },

        /**
         * In-Modal Processing Overlay 🌀 (Sadece form gönderilirken hafif yükleniyor durumu)
         */
        showProcessing: function (modalOrElement, text = 'İşleminiz yapılıyor...') {
            const modalEl = modalOrElement?.closest?.('.modal, .rbn-modal') || document.querySelector('.modal.show, .rbn-modal.show') || this.activeModal;
            if (!modalEl) return null;

            const modalContent = modalEl.querySelector('.modal-content, .rbn-modal-content') || modalEl;
            if (!modalContent) return null;

            this.hideProcessing(modalEl);

            modalContent.style.position = 'relative';
            modalContent.style.overflow = 'hidden';

            const overlay = document.createElement('div');
            overlay.className = 'rbn-modal-processing-overlay d-flex flex-column align-items-center justify-content-center';
            overlay.style.cssText = `
                position: absolute;
                inset: 0;
                background: var(--rbn-bg-canvas, var(--rbn-bg-surface, #ffffff));
                z-index: 1050;
                opacity: 0;
                transition: opacity 0.25s ease-in-out;
                border-radius: inherit;
            `;

            overlay.innerHTML = `
                <div class="d-flex flex-column align-items-center text-center p-4">
                    <div class="mb-3 position-relative d-flex align-items-center justify-content-center" style="width: 64px; height: 64px;">
                        <div style="
                            width: 58px;
                            height: 58px;
                            border: 4px solid var(--rbn-border-subtle, rgba(0, 0, 0, 0.1));
                            border-top-color: var(--rbn-primary, #0d6efd);
                            border-right-color: var(--rbn-primary, #0d6efd);
                            border-radius: 50%;
                            animation: rbnSpin 0.7s linear infinite;
                        "></div>
                        <i class="ri-refresh-line position-absolute" style="font-size: 1.5rem; color: var(--rbn-text-primary, currentColor);"></i>
                    </div>
                    <div class="font-monospace fw-bold fs-6 mb-1" style="color: var(--rbn-text-primary, inherit); font-size: 1.05rem;">
                        ${text}
                    </div>
                    <div class="font-monospace small" style="color: var(--rbn-text-muted, #6c757d); font-size: 0.82rem;">
                        Lütfen bekleyiniz, veriler kaydediliyor...
                    </div>
                </div>
            `;

            if (!document.getElementById('rbn-spinner-keyframes')) {
                const style = document.createElement('style');
                style.id = 'rbn-spinner-keyframes';
                style.innerHTML = `@keyframes rbnSpin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }`;
                document.head.appendChild(style);
            }

            modalContent.appendChild(overlay);
            requestAnimationFrame(() => { overlay.style.opacity = '1'; });

            return overlay;
        },

        /**
         * Hide / Remove Processing Overlay
         */
        hideProcessing: function (modalOrElement) {
            const modalEl = modalOrElement?.closest?.('.modal, .rbn-modal') || this.activeModal;
            if (!modalEl) return;

            const overlay = modalEl.querySelector('.rbn-modal-processing-overlay');
            if (overlay) {
                overlay.style.opacity = '0';
                setTimeout(() => {
                    if (overlay.parentNode) overlay.parentNode.removeChild(overlay);
                }, 150);
            }
        }
    };

    // Auto Init on DOM Ready
    if (typeof rbnReady === 'function') {
        rbnReady(() => RbnModal.init());
    } else {
        document.addEventListener('DOMContentLoaded', () => RbnModal.init());
    }

    // Global Export
    window.RbnModal = RbnModal;

})(window, document);
