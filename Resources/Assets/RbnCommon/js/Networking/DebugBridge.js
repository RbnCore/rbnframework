/**
 * AJAX Debug Bridge 🧬🛰️🔘
 * [Sarsılmaz Köprü] - RBN Framework Standard.
 * 
 * Automatically detects RBN diagnostic JSON responses in AJAX/Fetch 
 * and shows a rescue button to open a high-fidelity diagnostic popup.
 */
(function() {
    'use strict';

    if (window._rbn_debug_bridge_active) return;
    window._rbn_debug_bridge_active = true;

    // 1. Styles for the Rescue Button 🎨
    const style = document.createElement('style');
    style.innerHTML = `
        #rbn-debug-bridge { position: fixed; bottom: 30px; right: 30px; z-index: 2147483647; display: none; }
        .rbn-rescue-btn { background: #db2777; color: #fff; border: none; padding: 12px 24px; border-radius: 12px; font-family: sans-serif; font-weight: 900; font-size: 14px; cursor: pointer; box-shadow: 0 15px 35px rgba(219, 39, 119, 0.4); border: 2px solid #fff; transition: all 0.3s; text-transform: uppercase; letter-spacing: 2px; animation: rbn-pulse 2s infinite; }
        .rbn-rescue-btn:hover { background: #7c3aed; transform: scale(1.1) translateY(-5px); box-shadow: 0 20px 45px rgba(124, 58, 237, 0.5); }
        @keyframes rbn-pulse { 0% { box-shadow: 0 0 0 0 rgba(219, 39, 119, 0.7); } 70% { box-shadow: 0 0 0 20px rgba(219, 39, 119, 0); } 100% { box-shadow: 0 0 0 0 rgba(219, 39, 119, 0); } }
    `;
    document.head.appendChild(style);

    // 2. Create the Button Container 🏮
    const container = document.createElement('div');
    container.id = 'rbn-debug-bridge';
    container.innerHTML = `<button class="rbn-rescue-btn" onclick="openRbnDebug()">RBN DEBUG 🛰️</button>`;
    document.body.appendChild(container);

    let lastError = null;

    // 3. Popup Mechanic 🎭
    window.openRbnDebug = function() {
        if (!lastError) return;
        
        const popup = window.open('', '_blank', 'width=1000,height=800,resizable=yes');
        const data = lastError.data || {};
        // Surum elle yazilmaz: sunucu `shield` gonderir; yoksa sayfanin generator etiketi (FrameworkIdentity'den uretilir).
        const shield = lastError.shield || (document.querySelector('meta[name="generator"]') || {}).content || 'RBN Framework';
        const now = lastError.timestamp || '';
        const snippet = data.snippet || null;
        const errLine = data.line || 0;

        let snippetHtml = '';
        if (snippet) {
            snippetHtml = '<div class="code-container"><ul class="code-lines">';
            for (const [num, content] of Object.entries(snippet)) {
                const isActive = (parseInt(num) === errLine);
                const escaped = content.replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
                snippetHtml += `<li class="code-line ${isActive ? 'active' : ''}" style="counter-set: line ${num - 1};">
                    <div class="code-content">${escaped}</div>
                </li>`;
            }
            snippetHtml += '</ul></div>';
        }

        const html = `
            <!DOCTYPE html>
            <html lang="tr">
            <head>
                <meta charset="UTF-8">
                <title>${shield} AJAX Diagnostic</title>
                <style>
                    :root { --p-color: #db2777; --bg-main: #060606; --bg-card: #0c0c0c; --text-muted: #71717a; --text-bold: #f8fafc; }
                    * { user-select: text !important; -webkit-user-select: text !important; }
                    body { background: var(--bg-main); color: var(--text-bold); font-family: 'Segoe UI', system-ui, sans-serif; margin: 0; padding: 40px; }
                    .container { max-width: 900px; margin: 0 auto; background: var(--bg-card); padding: 50px; border-radius: 12px; border: 1px solid #1a1a1a; box-shadow: 0 40px 100px rgba(0,0,0,0.5); position: relative; }
                    .container::before { content: ''; position: absolute; top:0; left:0; width:100%; height:4px; background: linear-gradient(90deg, #7c3aed, var(--p-color)); border-radius: 12px 12px 0 0; }
                    h1 { color: var(--p-color); font-size: 0.8rem; text-transform: uppercase; letter-spacing: 3px; font-weight: 900; }
                    .type { font-size: 2.2rem; margin: 20px 0 10px; font-weight: 800; color: #fff; }
                    .msg { font-size: 1.2rem; color: #a1a1aa; line-height: 1.6; margin-bottom: 30px; }
                    .hint-box { background: #040404; padding: 20px; border-radius: 8px; font-family: monospace; font-size: 0.9rem; color: var(--text-muted); border-left: 5px solid #27272a; margin-bottom: 30px; white-space: pre-wrap; }
                    
                    /* Snippet Styling 🧬 */
                    .code-container { background: #010101; border: 1px solid #161616; border-radius: 8px; overflow: hidden; margin-bottom: 30px; font-family: 'Consolas', 'Monaco', monospace; font-size: 13px; }
                    .code-lines { list-style: none; padding: 0; margin: 0; }
                    .code-line { display: flex; transition: background 0.2s; }
                    .code-line::before { content: counter(line); counter-increment: line; width: 45px; text-align: right; margin-right: 15px; color: #333; padding: 3px 0; border-right: 1px solid #161616; padding-right: 10px; }
                    .code-line.active { background: #1a0610; }
                    .code-line.active::before { color: #db2777; border-right: 1px solid #db2777; }
                    .code-content { padding: 3px 0; white-space: pre; color: #ccc; }

                    .footer { font-size: 0.7rem; color: #444; border-top: 1px solid #161616; padding-top: 20px; text-transform: uppercase; letter-spacing: 1px; display: flex; justify-content: space-between; align-items: center; }
                    .copy-btn { background: #1a1a1a; color: #888; border: 1px solid #333; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 0.65rem; font-weight: 700; transition: all 0.2s; text-transform: uppercase; }
                    .copy-btn:hover { background: var(--p-color); color: #fff; border-color: #fff; }
                </style>
                <script>
                    function copyDiagnostic() {
                        const typeEl = document.querySelector('.type');
                        const msgEl = document.querySelector('.msg');
                        const hintEl = document.querySelector('.hint-box');
                        
                        const type = typeEl ? typeEl.innerText.trim() : '';
                        const msg = msgEl ? msgEl.innerText.trim() : '';
                        const hint = hintEl ? hintEl.innerText.trim() : '';
                        
                        const text = "[ AJAX DIAGNOSTIC REPORT ]\n" +
                                     "TYPE: " + type + "\n" +
                                     "MESSAGE: " + msg + "\n\n" +
                                     hint;
                        
                        const btn = document.querySelector('.copy-btn');
                        const showSuccess = () => {
                            if (!btn) return;
                            const original = btn.innerText;
                            btn.innerText = 'KOPYALANDI! ✅';
                            btn.style.background = '#10b981';
                            setTimeout(() => {
                                btn.innerText = original;
                                btn.style.background = '';
                            }, 2000);
                        };

                        if (navigator.clipboard && window.isSecureContext) {
                            navigator.clipboard.writeText(text).then(showSuccess).catch(() => fallbackCopy(text, showSuccess));
                        } else {
                            fallbackCopy(text, showSuccess);
                        }
                    }

                    function fallbackCopy(text, callback) {
                        const textArea = document.createElement("textarea");
                        textArea.value = text;
                        textArea.style.position = "fixed";
                        textArea.style.left = "-999999px";
                        textArea.style.top = "-999999px";
                        document.body.appendChild(textArea);
                        textArea.focus();
                        textArea.select();
                        try {
                            document.execCommand('copy');
                            if (callback) callback();
                        } catch (err) {
                            console.error('Kopyalama başarısız:', err);
                        }
                        document.body.removeChild(textArea);
                    }
                </script>
            </head>
            <body>
                <div class="container">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <h1>[ AJAX DIAGNOSTIC REPORT ]</h1>
                        <button class="copy-btn" onclick="copyDiagnostic()">RAPORU KOPYALA 📋</button>
                    </div>
                    <div class="type">${data.type || data.title || 'Sistem Hatası'}</div>
                    <div class="msg">${data.message || data.error || (typeof data === 'string' ? data : 'Hata açıklaması bulunamadı.')}</div>
                    
                    ${snippetHtml}
 
                    <div class="hint-box"><b>DOCTOR HINT</b><br>${data.hint || data.solution || 'Özel bir teşhis ipucu bulunmuyor.'}<br><br><b>FILE:</b> ${data.file || 'Bilinmiyor'}<br><b>LINE:</b> ${data.line || 'N/A'}</div>
                    <div class="footer">
                        <div>${shield}</div>
                        <div>GEN: ${now}</div>
                    </div>
                </div>
            </body>
            </html>
        `;
        popup.document.write(html);
        popup.document.close();
    };

    // 4. Intercept Fetch API 🛰️
    const originalFetch = window.fetch;
    window.fetch = async function(...args) {
        try {
            const response = await originalFetch(...args);
            if (!response.ok) {
                const clone = response.clone();
                try {
                    const json = await clone.json();
                    if (json && json._is_rbn_diagnostic) {
                        lastError = json;
                        container.style.display = 'block';
                    }
                } catch(e) {}
            }
            return response;
        } catch (error) {
            throw error;
        }
    };

    // 5. Intercept XMLHttpRequest 🧬
    const originalOpen = XMLHttpRequest.prototype.open;
    XMLHttpRequest.prototype.open = function() {
        this.addEventListener('load', function() {
            if (this.status >= 400) {
                try {
                    const json = JSON.parse(this.responseText);
                    if (json && json._is_rbn_diagnostic) {
                        lastError = json;
                        container.style.display = 'block';
                    }
                } catch(e) {}
            }
        });
        return originalOpen.apply(this, arguments);
    };

    console.log("%c RBN AJAX Bridge Active 🧬 ", "background: #db2777; color: #fff; border-radius: 4px; padding: 2px 6px;");
})();
