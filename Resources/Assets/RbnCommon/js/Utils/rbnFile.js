/**
 * ==========================================================================
 * rbnFile.js — Universal File, Media, Upload & Export Suite 📁🖼️📊
 * ==========================================================================
 * Zero-dependency RBN Framework File Engine for RBN Applications.
 * Combines: Image Preview, File Uploader, Excel/CSV Exporter, Remote Downloader.
 */
(function (window, document) {
    'use strict';

    const RbnFile = {
        config: {
            defaultCsvName: 'export',
            defaultImgName: 'downloaded-image.jpg'
        },

        /* ========================================================
           1. GÖRSEL & MEDYA İŞLEMLERİ (Image & Media Engine) 🖼️
           ======================================================== */

        /**
         * Canlı Görsel Önizleme Motoru (Live Image Preview)
         * data-rbn-preview veya .rbn-image-preview özniteliklerini dinler.
         */
        initImagePreview: function () {
            document.addEventListener('change', function (e) {
                const input = e.target.closest('input[type="file"][data-rbn-preview], .rbn-image-preview');
                if (!input || !input.files || !input.files[0]) return;

                const file = input.files[0];
                if (!file.type.startsWith('image/')) return;

                const targetSelector = input.getAttribute('data-rbn-preview') || input.getAttribute('data-target');
                const targetImg = targetSelector ? document.querySelector(targetSelector) : null;

                if (targetImg) {
                    const reader = new FileReader();
                    reader.onload = (event) => {
                        targetImg.src = event.target.result;
                        targetImg.classList.remove('d-none');
                    };
                    reader.readAsDataURL(file);
                }
            });
        },

        /**
         * URL Üzerinden Görsel / Dosya İndirici (Remote Downloader)
         * @param {string} url - Dosya URL'si
         * @param {string} filename - İndirilecek dosya adı
         */
        download: function (url, filename = this.config.defaultImgName) {
            if (typeof window.RbnService !== 'undefined') {
                window.RbnService.get(url, {}, { format: 'blob' })
                    .then(blob => {
                        const blobUrl = window.URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = blobUrl;
                        a.download = filename;
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                        window.URL.revokeObjectURL(blobUrl);
                    })
                    .catch(() => {
                        window.open(url, '_blank');
                    });
            } else {
                const a = document.createElement('a');
                a.href = url;
                a.download = filename;
                a.target = '_blank';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
            }
        },

        /* ========================================================
           2. DOSYA YÜKLEME MOTORU (Uploader & Dropzone Engine) 📤
           ======================================================== */

        /**
         * Dosya Seçim Arayüzü Geri Bildirimi (File Selection UI)
         */
        initFileInputUI: function () {
            document.addEventListener('change', function (e) {
                const input = e.target.closest('input[type="file"][data-rbn-file-label], .rbn-file-input');
                if (!input || !input.files || !input.files.length) return;

                const labelSelector = input.getAttribute('data-rbn-file-label');
                const labelEl = labelSelector ? document.querySelector(labelSelector) : null;
                const fileName = input.files[0].name;

                if (labelEl) {
                    labelEl.textContent = fileName;
                    labelEl.classList.remove('text-muted');
                    labelEl.classList.add('text-primary', 'fw-bold');
                }
            });
        },

        /**
         * AJAX Otomatik Dosya Yükleme (Direct File Uploader)
         * @param {File} file - Yüklenecek dosya
         * @param {string} url - Hedef API adresi
         * @param {Object} extraData - Ek parametreler
         * @returns {Promise}
         */
        upload: function (file, url, extraData = {}) {
            if (!file || !url || typeof window.RbnService === 'undefined') return Promise.reject();

            const formData = new FormData();
            formData.append('file', file);

            Object.entries(extraData).forEach(([k, v]) => {
                formData.append(k, v);
            });

            return window.RbnService.post(url, formData);
        },

        /* ========================================================
           3. DIŞA AKTARMA MOTORU (Table to Excel/CSV Exporter) 📊
           ======================================================== */

        /**
         * HTML Tablosunu Excel/CSV olarak Dışa Aktarır
         * @param {string} selector - Tablo CSS seçicisi (#myTable vb.)
         * @param {string} filename - Çıktı dosya adı
         */
        exportTableToExcel: function (selector, filename = this.config.defaultCsvName) {
            const table = document.querySelector(selector);
            if (!table) {
                console.error(`RbnFile: Tablo bulunamadı (${selector})`);
                return;
            }

            let csv = [];
            const rows = table.querySelectorAll('tr');

            for (let i = 0; i < rows.length; i++) {
                let row = [];
                const cols = rows[i].querySelectorAll('td, th');

                for (let j = 0; j < cols.length; j++) {
                    // İşlemler / Butonlar sütununu es geç (Son sütun)
                    if (j === cols.length - 1 && i > 0 && cols[j].querySelector('button, a, .dropdown')) continue;

                    let data = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, '').replace(/(\s\s+)/gm, ' ').trim();
                    data = data.replace(/"/g, '""');
                    row.push('"' + data + '"');
                }
                csv.push(row.join(';'));
            }

            // UTF-8 BOM
            const csvString = '\uFEFF' + csv.join('\n');
            const blob = new Blob([csvString], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);

            link.setAttribute('href', url);
            link.setAttribute('download', `${filename}_${new Date().getTime()}.csv`);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);

            if (typeof window.RbnAlert !== 'undefined') {
                window.RbnAlert.success('Dışa Aktarma Başlatıldı', `${filename}.csv dosyası indiriliyor.`);
            }
        }
    };

    // Otomatik Dinleyicileri Başlat
    RbnFile.initImagePreview();
    RbnFile.initFileInputUI();

    // Global Export (Hem RbnFile hem de RbnExporter uyumluluğu ile)
    window.RbnFile = RbnFile;
    window.RbnExporter = RbnFile; // Geriye dönük tam uyumluluk 🛡️

})(window, document);
