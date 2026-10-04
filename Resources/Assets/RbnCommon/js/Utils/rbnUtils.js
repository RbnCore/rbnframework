/**
 * ==========================================================================
 * rbnUtils.js — Universal Pure Utilities & Calculation Suite 🧮⚙️
 * ==========================================================================
 * Pure calculation, string transformation, and format utilities.
 * Independent of DOM / Events.
 */
(function (window) {
    'use strict';

    const RbnUtils = {
        /**
         * Türkçe Karakter Uyumlu Slug Üretici (Slug Generator)
         * @param {string} text - Metin
         * @returns {string} slugified text
         */
        createSlug: function (text) {
            if (!text) return '';
            const trMap = {
                'ç': 'c', 'ğ': 'g', 'ı': 'i', 'ö': 'o', 'ş': 's', 'ü': 'u',
                'İ': 'i', 'Ç': 'c', 'Ğ': 'g', 'Ö': 'o', 'Ş': 's', 'Ü': 'u'
            };

            let slug = text.toString();
            for (let key in trMap) {
                slug = slug.replace(new RegExp(key, 'g'), trMap[key]);
            }

            return slug
                .toLowerCase()
                .trim()
                .replace(/[^a-z0-9\s-]/g, '')
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-')
                .replace(/^-+|-+$/g, '');
        },

        /**
         * Güvenli Rastgele Parola Üretici (Password Generator) 🔐
         * @param {number} length - Parola uzunluğu
         * @returns {string} Üretilen parola
         */
        generatePassword: function (length = 12) {
            const charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+~`|}{[]:;?><,./-=";
            let password = "";
            for (let i = 0; i < length; ++i) {
                password += charset.charAt(Math.floor(Math.random() * charset.length));
            }
            return password;
        },

        /**
         * Sayı / Para Birimi Formatlayıcı 💰
         * @param {number|string} amount 
         * @param {string} currency 
         * @returns {string}
         */
        formatCurrency: function (amount, currency = 'TRY') {
            const num = parseFloat(amount) || 0;
            return new Intl.NumberFormat('tr-TR', {
                style: 'currency',
                currency: currency
            }).format(num);
        },

        /**
         * Telefon Numarası String Formatlayıcı 📞
         * @param {string} phone 
         * @returns {string}
         */
        formatPhone: function (phone) {
            if (!phone) return '';
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
    };

    window.RbnUtils = RbnUtils;

})(window);
