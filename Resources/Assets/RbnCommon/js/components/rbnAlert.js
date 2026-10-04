/**
 * rbnAlert.js — Unified Alert & Confirmation Engine
 * 
 * RBN Framework 3.1 "Masterpiece" Edition.
 * Consolidates Toast, Modal, and Auth alerts into a single smart API.
 * version 3.1.0 (Native Pure)
 */

/**
 * [B71-#2] Guvenli redirect hedefi (JS tarafi savunma-derinligi).
 *
 * `rbnService.js` ile ayni tanim. Iki dosya da bagimsiz yuklenebildigi icin
 * `window.RbnGuvenliHedef = window.RbnGuvenliHedef || ...` korumasiyla
 * YALNIZCA BIR kopyasi yasar (ilk yuklenen kazanir, ikincisi dokunmaz).
 * Tek kopyada tutmanin tek yolu vardi: yeni bir varlik dosyasi + view
 * kaydi; bu, yalnizca iki satirlik bir korumayi dagitmak icin degismez
 * degil. Kurallar PHP `RedirectTrait::guvenliHedef()` ile aynidir.
 */
window.RbnGuvenliHedef = window.RbnGuvenliHedef || function (url) {
    if (typeof url !== 'string') return '/';
    var u = url.trim();
    if (u === '') return '/';
    if (/[\u0000-\u001F\u007F]/.test(u)) return '/';
    u = u.replace(/\\/g, '/');
    if (u.charAt(0) === '/' && u.charAt(1) !== '/') return u;
    try {
        var hedef = new URL(u, window.location.origin);
        if (hedef.origin !== window.location.origin) return '/';
        return u;
    } catch (e) {
        return '/';
    }
};

window.RbnAlert = window.RbnAlert || {

    /* --------------------------------------------------------
       1. SMART DISPATCHER (The Brain)
    -------------------------------------------------------- */

    /**
     * Universal entry point for all notifications.
     * @param {string} type - success, error, warning, info
     * @param {string} title - Main message
     * @param {string} message - Secondary detail
     * @param {object} options - { display: 'toast'|'center'|'auth', duration: 4000 }
     */
    show: function (type, title, message = '', options = {}) {
        if (!title) return;

        // [RBN 3.5] Double-Toast Prevention: JS ile bir alert gösterildiği an çerezi temizle (SSoT) 🛡️
        this._deleteCookie('rbn_alert');

        const display = options.display || 'toast';

        switch (display) {
            case 'center':
                return this._renderModalAlert(type, title, message, options);
            default:
                return this._renderToast(type, title, message, options);
        }
    },

    /* --------------------------------------------------------
       2. UNIFIED CONFIRM SYSTEM (Visual & AJAX)
    -------------------------------------------------------- */

    /**
     * Intelligent confirmation hub.
     * Detects if it should perform a network request or just return a choice.
     */
    /**
     * [RBN 3.1] Global Onay Şablonları (Template Engine)
     */
    _getTemplate: function (type, url = '', confirmClass = '') {
        const templates = {
            delete: {
                title: 'Kalıcı Olarak Silinsin mi?',
                message: 'Bu işlem geri alınamaz ve tüm ilişkili veriler sistemden tamamen silinecektir.',
                icon: 'ri-delete-bin-5-fill',
                confirmClass: 'is-danger',
                iconClass: 'is-delete',
                typeClass: 'delete'
            },
            reset: {
                title: 'Ayarlar Sıfırlansın mı?',
                message: 'Mevcut tüm ayarlar varsayılan değerlerine geri döndürülecektir.',
                icon: 'ri-restart-line',
                confirmClass: 'is-warning',
                iconClass: 'is-warning',
                typeClass: 'warning'
            },
            action: {
                title: 'Emin misiniz?',
                message: 'Bu işlemi gerçekleştirmek istediğinize emin misiniz?',
                icon: 'ri-question-fill',
                confirmClass: 'is-confirm',
                iconClass: 'is-question',
                typeClass: 'question'
            },
            save: {
                title: 'Değişiklikler Kaydedilsin mi?',
                message: 'Yapılan tüm değişiklikler kalıcı olarak sisteme işlenecektir.',
                icon: 'ri-checkbox-circle-fill',
                confirmClass: 'is-success',
                iconClass: 'is-success',
                typeClass: 'success'
            }
        };

        // [RBN 3.1] Zeki Teşhis: Tip veya renk üzerinden şablonu belirle
        const urlStr = String(url).toLowerCase();
        const isDelete = /(delete|sil|clear)/i.test(urlStr) || confirmClass === 'is-danger' || type === 'danger' || type === 'delete';
        const isReset = /(reset|sifirla)/i.test(urlStr) || confirmClass === 'is-warning' || type === 'warning';

        let finalType = type;
        if (!type || type === 'action' || type === 'danger') {
            finalType = isDelete ? 'delete' : (isReset ? 'reset' : 'action');
        }

        return templates[finalType] || templates.action;
    },

    /**
     * Gelişmiş Onay Modalı (Şablon Destekli)
     */
    confirm: function (title, message, options = {}) {
        return new Promise((resolve) => {
            const id = 'rbnConfirm_' + Math.random().toString(36).substr(2, 9);
            const url = options.url || options.endpoint || options.href || '';
            const confirmClassFromOptions = options.confirmClass || '';
            const template = this._getTemplate(options.type, url, confirmClassFromOptions);

            // [RBN 3.1] Başlık Önceliği: Gelen başlık jenerikse şablonun kaliteli başlığını kullan
            const standardTitles = ['Emin misiniz?', 'Sorgu', 'Emin Misiniz?'];
            const finalTitle = (title && !standardTitles.includes(title)) ? title : (options.title || template.title);
            const finalMessage = message || options.text || template.message;
            const confirmClass = options.confirmClass || template.confirmClass;
            const iconClass = template.iconClass;
            const icon = template.icon;

            const html = `
            <div class="rbn-modal-overlay rbn-fade-in" id="${id}_overlay">
                <div class="rbn-modal-card is-${template.typeClass || 'delete'} rbn-scale-in">
                    <div class="rbn-modal-body">
                        <div class="rbn-modal-icon ${iconClass}"><i class="${icon}"></i></div>
                        <h3 class="rbn-modal-title">${finalTitle}</h3>
                        <p class="rbn-modal-text">${finalMessage}</p>
                        <div class="rbn-modal-footer">
                            <button type="button" class="rbn-modal-btn is-cancel" id="${id}_cancel">${options.cancelButtonText || 'İptal'}</button>
                            <button type="button" class="rbn-modal-btn ${confirmClass}" id="${id}_ok">${options.confirmButtonText || 'Evet'}</button>
                        </div>
                    </div>
                </div>
            </div>`;

            document.body.insertAdjacentHTML('beforeend', html);
            const overlay = document.getElementById(id + '_overlay');
            const btnOk = document.getElementById(id + '_ok');
            const btnCancel = document.getElementById(id + '_cancel');

            const close = (confirmed, showCancelToast = true) => {
                overlay.classList.add('rbn-fade-out');
                setTimeout(() => {
                    overlay.remove();
                    if (!confirmed && showCancelToast) {
                        this.miniToast('İşlem iptal edildi', 'info');
                    }
                    resolve({ isConfirmed: confirmed, value: confirmed });
                }, 300);
            };

            btnCancel.onclick = () => close(false);
            overlay.onclick = (e) => { if (e.target === overlay) close(false); };

            btnOk.onclick = () => {
                const ajaxUrl = options.url || options.endpoint || null;
                if (ajaxUrl && typeof RbnService !== 'undefined') {
                    this._handleAjaxConfirm(ajaxUrl, options, btnOk, btnCancel, close);
                } else {
                    close(true, false);
                    if (options.successToast !== false && !options.silent && !options.skipSuccessToast) {
                        this.miniToast(options.successTitle || 'İşlem Başarılı', 'success');
                    }
                }
            };
        });
    },

    /**
     * Internal AJAX handler for unify confirm
     */
    _handleAjaxConfirm: function (url, options, btnOk, btnCancel, close) {
        const originalHtml = btnOk.innerHTML;
        const hasLoading = !!options.loadingMessage;

        if (hasLoading) {
            // Close confirmation modal immediately to show the loading screen cleanly
            close(true, false);
            this.loading(options.loadingMessage);
        } else {
            btnOk.innerHTML = '<span class="rbn-spinner-mini"></span> Bekleyin...';
            btnOk.style.pointerEvents = 'none';
            btnCancel.style.opacity = '0.5';
            btnCancel.style.pointerEvents = 'none';
        }

        RbnService.post(url, options.data || {}, {
            method: options.method || 'POST',
            silent: true // Merkezi servisin Erken Toast'ını ve Redirect'ini Sustur
        }).then(res => {
            if (hasLoading) {
                this.close();
            } else {
                // 1. Modalı Kapat
                close(true, false);
            }

            // 2. Modal animasyonunu (350ms) bekle ve Bildirimi Göster
            setTimeout(() => {
                if (res.message) {
                    const title = res.type === 'success' ? 'Başarılı' : (res.type === 'error' ? 'Hata' : 'Bilgi');
                    const displayMode = res.display || (res.data && res.data.display) || 'toast';
                    const isCenter = (displayMode === 'center' || displayMode === 'modal');

                    if (isCenter) {
                        this.show(res.type || 'info', title, res.message, { display: 'center' });
                    } else {
                        const toastMethod = res.type === 'error' ? 'error' : (res.type === 'info' ? 'info' : 'success');
                        this[toastMethod](title, res.message, true); // true = isToast
                    }

                    // [RBN 3.5] Double-Toast Prevention 🛡️
                    document.cookie = "rbn_alert=; Max-Age=-99999999; path=/;";

                    // Hata modalı açılmışsa kullanıcı okumadan sayfayı hemen yenileme!
                    if (isCenter && res.type === 'error') {
                        return;
                    }
                }

                // 3. Mesaj okunsun diye 1.5 saniye bekle ve yönlendir
                setTimeout(() => {
                    const redirect = res.redirect || (res.data && res.data.redirect);
                    if (redirect && options.redirect !== false) {
                        // [B71-#2] JSON'daki redirect degeri atlamadan once
                        // ayni-origin kontrolunden gecer (bkz. RbnGuvenliHedef).
                        window.location.href = window.RbnGuvenliHedef(redirect);
                    } else if (res.reload !== false || (redirect && options.redirect === false)) {
                        window.location.reload();
                    }
                }, 1500);
            }, 350);
        }).catch((err) => {
            if (hasLoading) {
                this.close();
            } else {
                close(false, false);
                btnOk.innerHTML = originalHtml;
                btnOk.style.pointerEvents = 'auto';
                btnCancel.style.opacity = '1';
                btnCancel.style.pointerEvents = 'auto';
            }

            // Try to extract specific error message from server response
            const errorMsg = (err && err.message) ? err.message : 'Sunucu isteği gerçekleştirilemedi veya bir hata oluştu.';
            this.error('İşlem Başarısız', errorMsg);
        });
    },

    /* --------------------------------------------------------
       3. INTERNAL RENDERERS (Private-ish)
    -------------------------------------------------------- */

    _renderToast: function (type, title, message, options = {}) {
        const id = 'rbnToast_' + Math.random().toString(36).substr(2, 9);
        const duration = options.duration || 4000;
        const isAuthPage = document.querySelector('.rbn-auth-body, .rbn-auth-stage-card, .login-page, .rbn-auth-page');

        // 1. Stage Card Shake on error
        if (type === 'error' && isAuthPage) {
            const card = document.querySelector('.rbn-auth-stage-card');
            if (card) {
                card.classList.remove('rbn-auth-shake');
                void card.offsetWidth;
                card.classList.add('rbn-auth-shake');
            }
        }

        // 2. Cyber Shield Theme for Auth or Modern Toast
        const icons = { 
            success: 'ri-shield-check-fill', 
            error: 'ri-error-warning-fill', 
            warning: 'ri-alarm-warning-fill', 
            info: 'ri-information-fill' 
        };
        const tags = { 
            success: 'DOĞRULANDI', 
            error: 'GÜVENLİK UYARISI', 
            warning: 'SİSTEM UYARISI', 
            info: 'BİLGİLENDİRME' 
        };

        const html = isAuthPage ? `
        <div class="rbn-auth-toast is-${type} rbn-auth-slide-in" id="${id}">
            <div class="rbn-auth-toast-badge">
                <span class="rbn-auth-toast-led"></span>
                <i class="${icons[type] || icons.error}"></i>
            </div>
            <div class="rbn-auth-toast-content">
                <div class="rbn-auth-toast-tag">[ ${tags[type] || 'BİLDİRİM'} ]</div>
                <div class="rbn-auth-toast-title">${title}</div>
                ${message ? `<div class="rbn-auth-toast-desc">${message}</div>` : ''}
            </div>
            <div class="rbn-auth-toast-progress"><div class="rbn-auth-toast-progress-bar" style="transition-duration: ${duration}ms;"></div></div>
        </div>` : `
        <div class="rbn-toast is-${type} rbn-slide-in" id="${id}">
            <div class="rbn-toast-icon"><i class="${icons[type] || icons.info}"></i></div>
            <div class="rbn-toast-content">
                <div class="rbn-toast-title">${title}</div>
                ${message ? `<div class="rbn-toast-desc">${message}</div>` : ''}
            </div>
            <div class="rbn-toast-progress"><div class="rbn-toast-progress-bar" style="transition-duration: ${duration}ms;"></div></div>
        </div>`;

        const containerClass = isAuthPage ? 'rbn-auth-toast-container' : 'rbn-toast-container';
        let container = document.querySelector('.' + containerClass);
        if (!container) {
            container = document.createElement('div');
            container.className = containerClass;
            document.body.appendChild(container);
        }

        // Remove old to prevent clutter
        container.querySelectorAll(isAuthPage ? '.rbn-auth-toast' : '.rbn-toast').forEach(el => el.remove());

        container.insertAdjacentHTML('beforeend', html);
        const toast = document.getElementById(id);

        setTimeout(() => {
            if (!toast) return;
            const bar = toast.querySelector(isAuthPage ? '.rbn-auth-toast-progress-bar' : '.rbn-toast-progress-bar');
            if (bar) {
                if (isAuthPage) bar.style.height = '0%';
                else bar.style.width = '0%';
            }
        }, 50);

        const close = () => {
            if (!toast) return;
            toast.classList.add(isAuthPage ? 'rbn-auth-slide-out' : 'rbn-slide-out');
            setTimeout(() => toast.remove(), 400);
        };

        toast.onclick = close;
        setTimeout(close, duration);
    },

    _renderModalAlert: function (type, title, message, options) {
        const id = 'rbnModal_' + Math.random().toString(36).substr(2, 9);
        const icons = { 
            success: 'ri-checkbox-circle-fill', 
            error: 'ri-close-circle-fill', 
            warning: 'ri-error-warning-fill', 
            info: 'ri-information-fill' 
        };

        const html = `
        <div class="rbn-modal-overlay rbn-fade-in" id="${id}_overlay">
            <div class="rbn-modal-card rbn-scale-in is-${type}">
                <div class="rbn-modal-body text-center">
                    <div class="rbn-modal-icon is-${type}"><i class="${icons[type] || icons.info}"></i></div>
                    <h3 class="rbn-modal-title">${title}</h3>
                    <p class="rbn-modal-text">${message}</p>
                    <button class="rbn-modal-btn is-close" id="${id}_close">Tamam</button>
                </div>
            </div>
        </div>`;

        document.body.insertAdjacentHTML('beforeend', html);
        const overlay = document.getElementById(id + '_overlay');

        const close = () => {
            overlay.classList.add('rbn-fade-out');
            setTimeout(() => overlay.remove(), 300);
        };

        document.getElementById(id + '_close').onclick = close;
        overlay.onclick = (e) => { if (e.target === overlay) close(); };
    },


    /* --------------------------------------------------------
       4. CONVENIENCE WRAPPERS (Shorthands)
    -------------------------------------------------------- */

    success: function (msg, detail = '', isToast = false) { this.show('success', msg, detail, { display: isToast ? 'toast' : 'center' }); },
    error: function (msg, detail = '', isToast = false) { this.show('error', msg, detail, { display: isToast ? 'toast' : 'center' }); },
    warning: function (msg, detail = '', isToast = false) { this.show('warning', msg, detail, { display: isToast ? 'toast' : 'center' }); },
    info: function (msg, detail = '', isToast = false) { this.show('info', msg, detail, { display: isToast ? 'toast' : 'center' }); },

    miniToast: function (msg, type = 'success', duration = 3000) { this.show(type, msg, '', { display: 'toast', duration: duration }); },

    loading: function (title = 'Lütfen bekleyin...') {
        this.close();
        const html = `
        <div class="rbn-modal-overlay rbn-fade-in" id="rbnLoading">
            <div class="rbn-loading-card rbn-scale-in">
                <div class="rbn-spinner"></div>
                <div class="rbn-loading-text">${title}</div>
            </div>
        </div>`;
        document.body.insertAdjacentHTML('beforeend', html);
    },

    close: function () {
        document.querySelectorAll('.rbn-modal-overlay, .rbn-toast-container, .rbn-mini-toast').forEach(el => el.remove());
    },

    /* --------------------------------------------------------
       5. FLASH SYSTEM & LEGACY (Backward Compatibility)
    -------------------------------------------------------- */

    ajaxConfirm: function (options) { return this.confirm(options.title || 'Emin misiniz?', options.text || '', options); },
    showModal: function (type, title, message) { return this.show(type, title, message, { display: 'center' }); },

    initFlash: function () {
        if (window.flashMessage) {
            const fm = window.flashMessage;
            this.show(fm.type, fm.message, fm.message2 || '', { display: fm.display === 'center' ? 'center' : 'toast' });
            delete window.flashMessage;
            return;
        }

        const cookieValue = this._getCookie('rbn_alert');
        if (cookieValue) {
            try {
                const data = JSON.parse(decodeURIComponent(cookieValue.replace(/\+/g, ' ')));
                if (data.message) {
                    this.show(data.type, data.message, data.message2, { display: data.display === 'center' ? 'center' : 'toast' });
                }
                this._deleteCookie('rbn_alert');
            } catch (e) {
                console.error('RbnAlert cookie error:', e);
            }
        }
    },

    _getCookie: function (name) {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) return parts.pop().split(';').shift();
        return null;
    },

    _deleteCookie: function (name) {
        document.cookie = name + '=; Max-Age=-99999999; path=/;';
    }
};

// Auto-init on Ready
if (typeof window.rbnReady === 'function') {
    window.rbnReady(() => RbnAlert.initFlash());
} else {
    document.addEventListener('DOMContentLoaded', () => RbnAlert.initFlash());
}

window.RbnAlert = RbnAlert;
