/**
 * ==========================================================================
 * rbnBinders.js — Universal Action, Form & Confirmation Binders (Frontend + Panel) ⚡🛰️
 * ==========================================================================
 * Zero-jQuery, 100% Native Event Delegation Engine.
 * Handles: AJAX Forms, Data URL Actions, Confirmation Modals, Status Toggles.
 */
(function (window, document) {
    'use strict';

    /**
     * [B71-#2] Guvenli redirect hedefi — FAIL-COSED yedek.
     *
     * Birincil kaynak `rbnService.js` icindeki `window.RbnGuvenliHedef` (PHP
     * `RedirectTrait::guvenliHedef()` ile ayni kurallar). Bu dosya, o asset
     * sayfaya yuklenmemisse (panel parcalari, bagimsiz sayfalar) sessizce
     * KORUMASIZ yonlendirme yapmamak icin YALNIZCA goreli yollari kabul eden
     * kapatik (fail-closed) bir yedek kullanir: mutlak URL, '//saldirgan',
     * port degistiren origin ve bos deger -> '/'.
     *
     * Guvenligin KAYNAGI PHP'tir; buradaki iki katman sadece son savunmadır.
     */
    const guvenliHedef = (typeof window.RbnGuvenliHedef === 'function')
        ? window.RbnGuvenliHedef.bind(window)
        : function (u) {
            if (typeof u !== 'string') return '/';
            let v = u.trim();
            if (v === '' || /[\u0000-\u001F\u007F]/.test(v)) return '/';
            // F-01: tarayici da '\'i '/' sayar; normalize etmezsek '/\evil.example'
            // goreli yol gibi gorunur ama kullaniciyi baska host'a gonderir.
            v = v.replace(/\\/g, '/');
            return (v.charAt(0) === '/' && v.charAt(1) !== '/') ? v : '/';
        };

    const RbnBinders = {
        isInit: false,

        initAll: function () {
            if (this.isInit) return;
            this.initAjaxActions();
            this.initConfirmActions();
            this.initAjaxForms();
            this.initStatusToggles();
            this.isInit = true;
        },

        /* 1. Onaylı İşlem Tetikleyici (Universal Confirmation Binder) 🛡️ */
        initConfirmActions: function () {
            document.addEventListener('click', function (e) {
                const btn = e.target.closest('[data-rbn-confirm], [data-confirm], .action-confirm, .rbn-confirm');
                if (!btn) return;

                // Eğer form onaylanıp yeniden tetiklendiyse engelleme yapma
                if (btn.dataset.rbnConfirmed === 'true') {
                    delete btn.dataset.rbnConfirmed;
                    return;
                }

                const form = btn.closest('form');
                const isSubmit = form && (btn.type === 'submit' || btn.getAttribute('type') === 'submit');
                const url = btn.getAttribute('data-url') || btn.getAttribute('href');
                const title = btn.getAttribute('data-title') || btn.getAttribute('data-rbn-title') || 'İşlemi Onaylıyor musunuz?';
                const message = btn.getAttribute('data-message') || btn.getAttribute('data-text') || btn.getAttribute('data-confirm') || 'Bu işlem geri alınamaz.';
                const type = btn.getAttribute('data-rbn-type') || btn.getAttribute('data-type') || 'danger';
                const method = (btn.getAttribute('data-method') || (form ? form.getAttribute('method') : 'POST') || 'POST').toUpperCase();

                // Eğer bir FORM submit butonuysa ve harici bir URL atanmamışsa: Evrensel Form Onay Döngüsü 🚀
                if (isSubmit && !url) {
                    e.preventDefault();

                    const doSubmit = () => {
                        btn.dataset.rbnConfirmed = 'true';
                        if (typeof form.requestSubmit === 'function') {
                            form.requestSubmit(btn);
                            delete btn.dataset.rbnConfirmed;
                        } else {
                            btn.click();
                        }
                    };

                    if (typeof window.RbnAlert !== 'undefined' && typeof window.RbnAlert.confirm === 'function') {
                        window.RbnAlert.confirm(title, message, {
                            type: type,
                            skipSuccessToast: true
                        }).then(res => {
                            if (res && res.isConfirmed) {
                                doSubmit();
                            }
                        });
                    } else {
                        doSubmit();
                    }
                    return;
                }

                // Normal Link veya Harici Buton URL Aksiyonları 🛡️
                e.preventDefault();

                if (typeof window.RbnAlert !== 'undefined' && typeof window.RbnAlert.ajaxConfirm === 'function') {
                    window.RbnAlert.ajaxConfirm({
                        title: title,
                        text: message,
                        type: type,
                        url: url,
                        method: method,
                        onSuccess: (res) => {
                            if (btn.hasAttribute('data-remove-target')) {
                                const target = document.querySelector(btn.getAttribute('data-remove-target'));
                                if (target) target.remove();
                            }
                        }
                    });
                } else if (confirm(`${title}\n${message}`)) {
                    if (url && url !== '#' && !url.startsWith('javascript:')) {
                        if (method === 'GET') {
                            window.location.href = url;
                        } else if (typeof window.RbnService !== 'undefined') {
                            window.RbnService.post(url).then(() => window.location.reload());
                        }
                    }
                }
            });
        },

        /* 2. Sessiz AJAX İşlem Tetikleyici (Universal Silent Action) ⚡ */
        initAjaxActions: function () {
            document.addEventListener('click', function (e) {
                const btn = e.target.closest('[data-rbn-action], .rbn-ajax-action');
                if (!btn || btn.hasAttribute('data-rbn-confirm') || btn.hasAttribute('data-confirm')) return;

                e.preventDefault();
                const url = btn.getAttribute('data-url') || btn.getAttribute('href');
                if (!url || url === '#' || url.startsWith('javascript:')) return;

                const method = (btn.getAttribute('data-method') || 'POST').toUpperCase();
                const params = btn.dataset.params ? JSON.parse(btn.dataset.params) : {};
                const id = btn.getAttribute('data-id');
                const value = btn.getAttribute('data-value');

                const payload = { id, value, ...params };

                btn.setAttribute('disabled', 'true');
                btn.classList.add('opacity-50', 'is-loading');

                if (typeof window.RbnService !== 'undefined') {
                    const req = method === 'GET' ? window.RbnService.get(url, payload) : window.RbnService.post(url, payload);
                    req.finally(() => {
                        btn.removeAttribute('disabled');
                        btn.classList.remove('opacity-50', 'is-loading');
                    });
                }
            });
        },

        /* 3. Evrensel AJAX Form Gönderici (Universal AJAX Form Binder) 📝 */
        initAjaxForms: function () {
            document.addEventListener('submit', function (e) {
                const form = e.target.closest('[data-rbn-ajax], form.rbn-ajax-form, form[data-ajax="true"], form[data-rbn-form="true"]');
                if (!form) return;

                e.preventDefault();
                e.stopImmediatePropagation();

                // Eğer form zaten gönderiliyorsa mükerrer gönderimi engelle 🛑
                if (form.getAttribute('data-is-submitting') === 'true') {
                    return;
                }
                form.setAttribute('data-is-submitting', 'true');

                const url = form.getAttribute('action') || window.location.href;
                const method = (form.getAttribute('method') || 'POST').toUpperCase();
                const submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
                const inModal = form.closest('.modal, .rbn-modal');

                if (submitBtn) {
                    submitBtn.setAttribute('disabled', 'true');
                    submitBtn.classList.add('opacity-50', 'is-loading');
                }

                // 🌀 Eğer form bir modal içindeyse, modal içi şık yükleniyor overlay'i başlat
                if (inModal && typeof window.RbnModal !== 'undefined') {
                    window.RbnModal.showProcessing(inModal, 'Değişiklikler kaydediliyor...');
                }

                const formData = new FormData(form);

                if (typeof window.RbnService !== 'undefined') {
                    // Modal içindeyse silent: true yapıyoruz ki alert henüz modal açıkken erken fırlamasın
                    const serviceOptions = inModal ? { silent: true } : {};

                    window.RbnService.post(url, formData, serviceOptions).then(res => {
                        const isSuccess = (res && (res.type === 'success' || res.status === 'success' || res.status === 200));
                        const msg = res?.message || 'İşlem Başarıyla Tamamlandı!';

                        if (inModal && typeof window.RbnModal !== 'undefined') {
                            if (isSuccess) {
                                // 1.1 saniye tatlı ve tok spinner döner 🌀
                                setTimeout(() => {
                                    window.RbnModal.hideProcessing(inModal);
                                    window.RbnModal.close(inModal);

                                    // 🔔 Modal tam kapandığı an toast çıkar (Çerez temizliğini RbnAlert kendi içinde yapar)
                                    if (typeof RbnAlert !== 'undefined') {
                                        RbnAlert.show('success', msg);
                                    }
                                }, 1100);
                            } else {
                                window.RbnModal.hideProcessing(inModal);
                                if (typeof RbnAlert !== 'undefined') {
                                    RbnAlert.show('error', res?.message || 'Bir hata oluştu!');
                                }
                            }
                        } else {
                            if (isSuccess && typeof RbnAlert !== 'undefined') {
                                RbnAlert.show('success', msg);
                            }
                        }

                        if (isSuccess) {
                            if (form.hasAttribute('data-reset-on-success')) form.reset();
                            // Kullanıcı toast bildirimini rahatça okuyabilsin diye yönlendirme süresi
                            const redirectDelay = inModal ? 2400 : 1200;
                            const targetRedirect = form.getAttribute('data-redirect') || res?.redirect || res?.data?.redirect;
                            if (targetRedirect) {
                                // [B71-#2] `res.redirect` AJAX JSON'undan gelir; atlamadan
                                // once ayni-origin kontrolunden gecer (bkz. RbnGuvenliHedef).
                                // rbnService.js/rbnAlert.js ile ayni koruma.
                                setTimeout(() => { window.location.href = guvenliHedef(targetRedirect); }, redirectDelay);
                            } else {
                                setTimeout(() => { window.location.reload(); }, redirectDelay);
                            }
                        }
                    }).catch(err => {
                        if (inModal && typeof window.RbnModal !== 'undefined') {
                            window.RbnModal.hideProcessing(inModal);
                        }
                        if (typeof RbnAlert !== 'undefined') {
                            RbnAlert.show('error', err?.message || 'Sunucu ile iletişim kurulamadı!');
                        }
                    }).finally(() => {
                        form.removeAttribute('data-is-submitting');
                        if (submitBtn) {
                            submitBtn.removeAttribute('disabled');
                            submitBtn.classList.remove('opacity-50', 'is-loading');
                        }
                    });
                }
            });
        },

        /* 4. Durum Değiştirici (Status Toggle Binder) 🔄 */
        initStatusToggles: function () {
            document.addEventListener('change', function (e) {
                const toggle = e.target.closest('[data-rbn-status-toggle], .rbn-status-toggle');
                if (!toggle) return;

                const url = toggle.getAttribute('data-url');
                if (!url) return;

                const id = toggle.getAttribute('data-id');
                const status = toggle.checked ? 1 : 0;

                // UI Badge & Label güncellemesi
                const targetLabel = toggle.getAttribute('data-status-label') ? document.querySelector(toggle.getAttribute('data-status-label')) : null;
                if (targetLabel) {
                    const activeText = toggle.getAttribute('data-active-text') || 'AKTİF';
                    const passiveText = toggle.getAttribute('data-passive-text') || 'PASİF';
                    targetLabel.textContent = status ? activeText : passiveText;
                    targetLabel.className = status ? 'badge bg-success' : 'badge bg-secondary';
                }

                if (typeof window.RbnService !== 'undefined') {
                    window.RbnService.post(url, { id: id, status: status });
                }
            });
        },

        /* 5. Sıralama Motoru (Universal Sortable Drag & Drop Binder) 🔀 */
        initSortableSystem: function () {
            const containers = document.querySelectorAll('[data-rbn-sortable], .rbn-sortable, .sortable-container');
            if (!containers.length) return;

            if (typeof Sortable === 'undefined') {
                if (!window._rbnSortableRetry) window._rbnSortableRetry = 0;
                if (window._rbnSortableRetry < 25) {
                    window._rbnSortableRetry++;
                    setTimeout(() => RbnBinders.initSortableSystem(), 120);
                }
                return;
            }

            containers.forEach(container => {
                if (container._rbnSortableInit) return;

                const url = container.getAttribute('data-url') || container.getAttribute('data-sort-url');
                const targetEl = (container.tagName === 'TABLE' && container.querySelector('tbody'))
                    ? container.querySelector('tbody')
                    : container;

                const handleSelector = container.getAttribute('data-handle') || container.getAttribute('data-sort-handle') || '.drag-handle';
                const resolvedHandle = targetEl.querySelector(handleSelector)
                    ? handleSelector
                    : (targetEl.querySelector('.sortable-handle') ? '.sortable-handle' : null);

                container._rbnSortableInit = true;

                new Sortable(targetEl, {
                    animation: 150,
                    handle: resolvedHandle,
                    ghostClass: 'rbn-sortable-ghost',
                    chosenClass: 'rbn-sortable-chosen',
                    onEnd: function () {
                        if (!url) return;

                        const ids = [];
                        const items = [];
                        targetEl.querySelectorAll('[data-id]').forEach((el, index) => {
                            const id = el.getAttribute('data-id');
                            ids.push(id);
                            items.push({
                                id: id,
                                order: index + 1
                            });
                        });

                        if (typeof window.RbnService !== 'undefined') {
                            const formData = new FormData();
                            ids.forEach((id, idx) => {
                                formData.append('order[' + idx + ']', id);
                                formData.append('ids[]', id);
                            });
                            formData.append('items', JSON.stringify(items));

                            window.RbnService.post(url, formData);
                        }
                    }
                });
            });
        },

        /* 6. Sinematik Yönlendirme Motoru (Universal Gateway & Redirect Binder) 🚀🪐 */
        initRedirectActions: function () {
            document.addEventListener('click', function (e) {
                const btn = e.target.closest('[data-rbn-redirect="true"], .rbn-redirect-link');
                if (!btn) return;

                e.preventDefault();
                const url = btn.getAttribute('href') || btn.getAttribute('data-url');
                if (!url || url === '#' || url.startsWith('javascript:')) return;

                const title = btn.getAttribute('data-title') || 'Yönlendiriliyorsunuz...';
                const message = btn.getAttribute('data-message') || 'Yönlendiriliyorsunuz';
                const subMessage = btn.getAttribute('data-sub-message') || 'Lütfen bekleyin, işleminiz gerçekleştiriliyor.';
                const ajaxParam = btn.getAttribute('data-ajax-param');

                const template = document.getElementById('rbn-redirect-loader-template');
                if (!template) {
                    window.location.href = url;
                    return;
                }

                let loaderHtml = template.innerHTML
                    .replace(/Yönlendiriliyorsunuz\.\.\./g, title)
                    .replace(/Yönlendiriliyorsunuz/g, message)
                    .replace(/Lütfen bekleyin, işleminiz gerçekleştiriliyor\./g, subMessage);

                const newTab = window.open('about:blank', '_blank');
                if (!newTab) {
                    window.location.href = url;
                    return;
                }

                newTab.document.open();
                newTab.document.write(loaderHtml);
                newTab.document.close();

                setTimeout(() => {
                    const progressLine = newTab.document.querySelector('.rbn-progress-line');
                    if (progressLine) progressLine.style.transform = 'scaleX(0.9)';
                }, 50);

                if (!ajaxParam) {
                    setTimeout(() => {
                        const progressLine = newTab.document.querySelector('.rbn-progress-line');
                        if (progressLine) {
                            progressLine.style.transition = 'transform 0.2s ease-out';
                            progressLine.style.transform = 'scaleX(1)';
                        }
                        setTimeout(() => { newTab.location.replace(url); }, 200);
                    }, 600);
                    return;
                }

                // AJAX SSO Parametre Modu
                let fetchUrl = url + (url.indexOf('?') !== -1 ? '&' : '?') + ajaxParam;
                fetch(fetchUrl)
                    .then(res => res.json())
                    .then(data => {
                        if (data.success && data.url) {
                            newTab.location.replace(data.url);
                        } else {
                            alert(data.message || 'Yönlendirme hatası.');
                            newTab.close();
                        }
                    }).catch(() => {
                        newTab.location.replace(url);
                    });
            });
        },

        /* 7. Evrensel Bağımlı Select / Cascading Motoru (data-rbn-cascade) 🔗🪄 */
        initCascades: function (root = document) {
            const populateSelect = (targetSelect, items, selectedVal, placeholder) => {
                targetSelect.innerHTML = placeholder ? `<option value="">${placeholder}</option>` : '';
                if (!items) return;
                const list = Array.isArray(items) ? items : Object.keys(items);
                list.forEach(item => {
                    const opt = document.createElement('option');
                    opt.value = item;
                    opt.textContent = item;
                    if (selectedVal && selectedVal === item) opt.selected = true;
                    targetSelect.appendChild(opt);
                });
            };

            const handleCascade = (select) => {
                const targetSelector = select.getAttribute('data-target');
                if (!targetSelector) return;
                const targetSelect = document.querySelector(targetSelector);
                if (!targetSelect) return;

                const val = select.value;
                const sourceVarName = select.getAttribute('data-source-var');
                const placeholder = targetSelect.getAttribute('data-placeholder') || 'Seçiniz';
                const initialSelected = targetSelect.getAttribute('data-selected') || '';

                if (sourceVarName && window[sourceVarName]) {
                    const sourceData = window[sourceVarName];
                    const items = sourceData[val] || [];
                    populateSelect(targetSelect, items, initialSelected, placeholder);
                    targetSelect.removeAttribute('data-selected');
                    targetSelect.dispatchEvent(new Event('change', { bubbles: true }));
                } else if (select.hasAttribute('data-endpoint')) {
                    const endpoint = select.getAttribute('data-endpoint') + encodeURIComponent(val);
                    fetch(endpoint)
                        .then(r => r.json())
                        .then(res => {
                            const items = res.data || res.items || res;
                            populateSelect(targetSelect, items, initialSelected, placeholder);
                            targetSelect.removeAttribute('data-selected');
                            targetSelect.dispatchEvent(new Event('change', { bubbles: true }));
                        });
                }
            };

            root.querySelectorAll('select[data-rbn-cascade]').forEach(select => {
                select.addEventListener('change', () => handleCascade(select));
                handleCascade(select); // Sayfa açılışında başlangıç verisini bağla
            });
        }
    };

    // Auto boot
    RbnBinders.initAll = function () {
        this.initAjaxActions();
        this.initConfirmActions();
        this.initAjaxForms();
        this.initStatusToggles();
        this.initSortableSystem();
        this.initRedirectActions();
        this.initCascades();
        this.isInit = true;
    };

    // DOM Ready & Auto boot
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            RbnBinders.initAll();
        });
    } else {
        RbnBinders.initAll();
    }

    window.addEventListener('load', function () {
        RbnBinders.initSortableSystem();
    });

    // Export & Backward Compatibility
    window.RbnBinders = RbnBinders;
    window.rbnEternalRedirect = RbnBinders;

})(window, document);
