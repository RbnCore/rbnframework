/**
 * rbnService.js — Global Networking & AJAX Wrapper
 * 
 * RBN FrameworkNetworking Layer.
 * PHP AjaxResponseTrait ve AlertService ile tam uyumlu çalışır.
 * 
 * version 1.0.0 (Native Fetch Based)
 */

/**
 * [B71-#2] Guvenli redirect hedefi (JS tarafi savunma-derinligi).
 *
 * PHP tarafinda `AlertService::send()` `RedirectTrait::guvenliHedef()` ile
 * `redirect` degerini JSON'a YAZMADAN once zaten filtreliyor. Burada ayni
 * kontrolun tarayici tarafinda bir kopyasi daha var: AJAX JSON'u bir baska
 * origin'e sizmis olsa bile (baska bir servis, eski bir cache, bir JS
 * injectioni) `window.location.href` sadece AYNI ORIGIN'e gidebilir.
 *
 * PHP `guvenliHedef()` ile ayni kurallar:
 *   - bos hedef / kontrol karakteri (CRLF) -> '/'
 *   - '\' -> '/' (F-01; tarayici da oyle sayar)
 *   - tek basina '/' ile baslayan goreli yol -> serbest
 *   - mutlak URL: host+port `location.origin` ile ayni degilse -> '/'
 *
 * Tek kaynak prensibi: buradaki JS, guvenligin KAYNAGI degil, ikinci bir
 * emniyet katmanidir. PHP tarafi kaldirilirsa JS tek basina yeterli DEGILDIR.
 */
window.RbnGuvenliHedef = window.RbnGuvenliHedef || function (url) {
    if (typeof url !== 'string') return '/';
    var u = url.trim();
    if (u === '') return '/';
    // Kontrol karakteri / CRLF enjeksiyonu -> reddet.
    if (/[\u0000-\u001F\u007F]/.test(u)) return '/';
    // F-01: ters bolu tarayici gibi '/' sayilir.
    u = u.replace(/\\/g, '/');
    // Kendi segmentlerimiz (tek basina '/' ile basliyor, '//' DEGIL) -> serbest.
    if (u.charAt(0) === '/' && u.charAt(1) !== '/') return u;
    // Mutlak URL: ayni origin mi?
    try {
        var hedef = new URL(u, window.location.origin);
        if (hedef.origin !== window.location.origin) return '/';
        return u;
    } catch (e) {
        return '/';
    }
};

window.RbnService = window.RbnService || {

    /**
     * GET İsteği Gönderir
     * @param {string} url - Hedef URL
     * @param {Object} params - URL parametreleri
     * @param {Object} options - Ek Fetch seçenekleri
     */
    get: function (url, params = {}, options = {}) {
        const query = new URLSearchParams(params).toString();
        const finalUrl = query ? `${url}${url.includes('?') ? '&' : '?'}${query}` : url;

        return this._request(finalUrl, {
            method: 'GET',
            ...options
        });
    },

    /**
     * POST İsteği Gönderir (JSON veya Form Data)
     * @param {string} url - Hedef URL
     * @param {Object|FormData} data - Gönderilecek veri
     * @param {Object} options - Ek Fetch seçenekleri
     */
    post: function (url, data = {}, options = {}) {
        let body;
        let headers = options.headers || {};

        if (data instanceof FormData) {
            body = data;

            // Otomatik CSRF Enjeksiyonu (View'da manuel eklemeye gerek kalmaz)
            const csrf = this._getCsrf();
            if (csrf && !data.has('csrf_token')) {
                data.append('csrf_token', csrf);
            }
        } else {
            const params = new URLSearchParams();
            Object.entries(data).forEach(([k, v]) => {
                if (Array.isArray(v)) {
                    v.forEach(item => params.append(k + '[]', item));
                } else {
                    params.append(k, v);
                }
            });

            // CSRF Token Ekle
            const csrf = this._getCsrf();
            if (csrf && !params.has('csrf_token')) {
                params.append('csrf_token', csrf);
            }

            body = params;
            headers['Content-Type'] = 'application/x-www-form-urlencoded';
        }

        return this._request(url, {
            ...options,
            method: 'POST',
            body: body,
            headers: {
                ...headers,
                ...options.headers
            }
        });
    },

    /**
     * PUT İsteği Gönderir
     */
    put: function (url, data = {}, options = {}) {
        return this.post(url, data, { ...options, method: 'PUT' });
    },

    /**
     * DELETE İsteği Gönderir
     */
    delete: function (url, data = {}, options = {}) {
        return this.post(url, data, { ...options, method: 'DELETE' });
    },

    /**
     * Dahili İstek Motoru (Private)
     */
    _request: async function (url, options) {
        // Safe-guard: GET/HEAD requests cannot have bodies in Fetch API
        if (['GET', 'HEAD'].includes(options.method?.toUpperCase()) && options.body) {
            delete options.body;
        }

        // Global Loading (Opsiyonel)
        if (options.loading !== false && typeof RbnAlert !== 'undefined' && options.silent !== true) {
            // RbnAlert.loading(options.loadingText || 'İşlem yapılıyor...');
        }

        try {
            const response = await fetch(url, {
                ...options,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    ...options.headers
                }
            });

            if (!response.ok) {
                // FW-096-C6: hata gövdesi (JSON) atılmaz; code/fields/new_token okunabilir.
                // Geriye uyumlu: varsayılan hâlâ Error fırlatır (message değişmedi), gövde error.response / .code / .fields / .status'ta.
                // { rawErrors: true } verilirse fırlatmaz, gövdeyi { ...body, success:false, status } olarak döndürür.
                let body = null;
                try {
                    const ct = response.headers.get('content-type') || '';
                    if (ct.includes('json')) body = await response.json();
                } catch (_) { body = null; }

                if (body && body.new_token && body.new_token.token) {
                    const t = body.new_token.token;
                    const meta = document.querySelector('meta[name="csrf-token"]');
                    if (meta) meta.setAttribute('content', t);
                    document.querySelectorAll('input[name="csrf_token"]').forEach(input => { input.value = t; });
                    if (window.APP_CONFIG) window.APP_CONFIG.csrfToken = t;
                }

                if (options.rawErrors === true) {
                    return Object.assign({ success: false }, body || {}, { status: response.status });
                }

                const httpErr = new Error(`HTTP Hata! Statü: ${response.status}`);
                httpErr.status = response.status;
                httpErr.response = body;
                httpErr.code = body && body.code !== undefined ? body.code : null;
                httpErr.fields = body && body.fields ? body.fields : null;
                httpErr.newToken = body && body.new_token ? body.new_token : null;
                throw httpErr;
            }

            // Response format kontrolü (json, text, blob)
            const format = options.format || 'json';
            let result;

            if (format === 'blob') {
                result = await response.blob();
            } else if (format === 'text') {
                result = await response.text();
            } else {
                result = await response.json();
            }

            // [RBN Framework] Auto-refresh CSRF tokens on page if returned by the server
            if (result && result.new_token && result.new_token.token) {
                const newToken = result.new_token.token;
                const meta = document.querySelector('meta[name="csrf-token"]');
                if (meta) meta.setAttribute('content', newToken);
                document.querySelectorAll('input[name="csrf_token"]').forEach(input => {
                    input.value = newToken;
                });
                if (window.APP_CONFIG) {
                    window.APP_CONFIG.csrfToken = newToken;
                }
            }

            // RBN Standart Response Mapping (PHP AjaxResponseTrait Uyumu)
            if (options.silent !== true && format === 'json') {
                this._handleResponse(result, options);

                // Force Reject if server returns an error type (consistency for .catch users)
                if (result.success === false || result.type === 'error') {
                    const err = new Error(result.message || 'Bir sunucu hatası oluştu.');
                    err.isHandled = true; // [RBN Framework] Hata zaten _handleResponse ile ekrana basıldı işareti
                    throw err;
                }
            }

            return result;

        } catch (error) {
            console.error('RbnService Error:', error);

            // Gerçek bağlantı hatalarında veya sunucu çökmelerinde kullanıcıya haber ver
            // Ancak isHandled true ise, bu zaten kontrollü bir hatadır (çift toast engelleme)
            if (!error.isHandled && options.silent !== true && typeof RbnAlert !== 'undefined') {
                RbnAlert.error('Hata!', error.message || 'Bağlantı hatası veya sunucu cevap vermiyor.');
            }

            throw error;
        }
    },

    /**
     * PHP AjaxResponseTrait'ten gelen cevabı yönetir
     */
    _handleResponse: function (res, options = {}) {
        if (typeof RbnAlert === 'undefined') return;

        const type = res.type || (res.success ? 'success' : 'error');
        const message = res.message || '';
        const message2 = res.message2 || '';
        const redirect = res.redirect || null;
        const display = res.display || 'toast';

        // Durum Mesajını Göster (RBN Global Estetik Standart)
        if (message) {
            const title = type === 'success' ? 'Başarılı' : (type === 'error' ? 'Hata' : 'Bilgi');

            if (display === 'toast') {
                // Şık Toast: RbnAlert.show(type, title, message, options)
                RbnAlert.show(type, title, message, { display: 'toast' });
            } else {
                RbnAlert.show(type, title, message, { display: 'center' });
            }

            // [RBN Framework Double-Toast Prevention] 🛡️
            // Mesaj JS ile gösterildiyse, yönlendirme sonrası tekrar çıkmasın diye çerezi temizliyoruz.
            document.cookie = "rbn_alert=; Max-Age=-99999999; path=/;";
        }

        // Yönlendirme Kontrolü (Feedback okunsun diye 1.5sn gecikmeli)
        if (redirect && options.redirect !== false) {
            const delay = options.delay || 1500;
            setTimeout(() => {
                // [B71-#2] Yonlendirme degeri JSON'dan gelir; atlamadan once
                // ayni-origin kontrolunden gecer (bkz. RbnGuvenliHedef).
                window.location.href = window.RbnGuvenliHedef(redirect);
            }, delay);
        } else if (res.reload === true || options.reload === true || (redirect && options.redirect === false)) {
            setTimeout(() => {
                window.location.reload();
            }, options.delay || 1500);
        }
    },

    /**
     * CSRF Token Bulucu
     */
    _getCsrf: function () {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            || document.getElementById('csrf-token')?.value
            || (window.APP_CONFIG ? window.APP_CONFIG.csrfToken : null);
    },

    /**
     * [CORE] Otomatik Form & OTP Başlatıcı (Sıfır Bağımlılık / Vanilla JS)
     */
    init: function () {
        // 1. data-ajax="true" Formlarını Otomatik Dinle & Doğrula (Validation)
        document.addEventListener('submit', function (e) {
            const form = e.target.closest('form[data-ajax="true"], form[data-rbn-form="true"], form.needs-validation');
            if (!form) return;

            // HTML5 Form Doğrulama Kontrolü (Form Validation)
            if (form.checkValidity && !form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
                form.classList.add('was-validated');
                return;
            }
            form.classList.add('was-validated');

            // Eğer form sadece standart bir HTML5 validasyon formuysa ve data-ajax taşımıyorsa AJAX ile gönderme
            if (!form.hasAttribute('data-ajax') && !form.hasAttribute('data-rbn-form')) {
                return;
            }

            e.preventDefault();
            const btn = form.querySelector('button[type="submit"]');
            const url = form.getAttribute('action') || window.location.href;
            const data = new FormData(form);

            let originalHtml = '';
            if (btn) {
                originalHtml = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" style="width:1rem;height:1rem;border:2px solid currentColor;border-right-color:transparent;border-radius:50%;display:inline-block;animation:rbnSpin 0.75s linear infinite;"></span> Bekleyin...';
            }

            RbnService.post(url, data).then(res => {
                // Başarılı akış rbnService._handleResponse içinde yönetilir
            }).catch(err => {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                }
            });
        });

        // 2. 6 Haneli OTP Kod Kutuları (Varsa)
        document.addEventListener('input', function (e) {
            const input = e.target.closest('.rbn-auth-otp-input, [data-rbn-otp]');
            if (!input) return;

            const form = input.closest('form');
            if (!form) return;

            const inputs = Array.from(form.querySelectorAll('.rbn-auth-otp-input, [data-rbn-otp]'));
            const idx = inputs.indexOf(input);

            if (input.value.length === 1 && idx < inputs.length - 1) {
                inputs[idx + 1].focus();
            }

            const hidden = form.querySelector('input[name="verification_code"]');
            if (hidden) {
                hidden.value = inputs.map(i => i.value).join('');
            }
        });

        document.addEventListener('keydown', function (e) {
            const input = e.target.closest('.rbn-auth-otp-input, [data-rbn-otp]');
            if (!input) return;

            const form = input.closest('form');
            if (!form) return;

            const inputs = Array.from(form.querySelectorAll('.rbn-auth-otp-input, [data-rbn-otp]'));
            const idx = inputs.indexOf(input);

            if (e.key === 'Backspace' && !input.value && idx > 0) {
                inputs[idx - 1].focus();
            }
        });

        document.addEventListener('paste', function (e) {
            const input = e.target.closest('.rbn-auth-otp-input, [data-rbn-otp]');
            if (!input) return;

            const form = input.closest('form');
            if (!form) return;

            e.preventDefault();
            const pasteData = (e.clipboardData || window.clipboardData).getData('text').trim();
            const inputs = Array.from(form.querySelectorAll('.rbn-auth-otp-input, [data-rbn-otp]'));

            if (/^\d+$/.test(pasteData)) {
                const digits = pasteData.split('');
                digits.forEach((d, i) => {
                    if (inputs[i]) inputs[i].value = d;
                });
                const nextIdx = Math.min(digits.length, inputs.length - 1);
                inputs[nextIdx].focus();

                const hidden = form.querySelector('input[name="verification_code"]');
                if (hidden) hidden.value = pasteData.slice(0, inputs.length);
            }
        });
    }
};

// Global Export & Auto-Init
window.RbnService = RbnService;

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => RbnService.init());
} else {
    RbnService.init();
}
