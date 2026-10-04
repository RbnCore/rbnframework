<?php

namespace Rbn\Framework\Core\Support\Blueprints\Validations;

/**
 * FormValidations - Unified DNA for Form Messages 🧱💎
 * Each rule is defined as a standard PHP constant.
 */
class FormValidations
{
    // --- [ BASIC ] ---
    public const REQUIRED   = "{field} alanı zorunludur.";
    public const MIN        = "{field} en az {min} karakter olmalıdır.";
    public const MAX        = "{field} en fazla {max} karakter olmalıdır.";
    public const NUMERIC    = "{field} sayısal bir değer olmalıdır.";
    public const ALPHA      = "{field} sadece harf içerebilir.";
    public const REGEX      = "{field} formatı geçersiz.";

    // --- [ IDENTITY / AUTH ] ---
    public const CONFIRMED  = "{field} doğrulaması eşleşmiyor.";
    public const UNIQUE     = "Bu {field} zaten kullanımda.";
    public const EXISTS     = "Seçilen {field} geçersiz.";

    // --- [ NETWORK / PROTOCOL ] ---
    public const EMAIL      = "{field} geçerli bir e-posta adresi olmalıdır.";
    public const EMAIL_TEMP = "Geçici e-posta servisleri kabul edilmemektedir.";
    public const EMAIL_MX   = "Bu domain için e-posta servisi bulunamadı.";
    // [A-07] Canlı DNS (MX) sorgusu kaldırıldı; alan adı YAZIMI denetlenir.
    public const EMAIL_DOMAIN = "{field} alan adı geçersiz görünüyor.";
    public const URL        = "{field} geçerli bir URL olmalıdır.";
    public const IP         = "{field} geçerli bir IP adresi olmalıdır.";
    public const DATE       = "{field} geçerli bir tarih olmalıdır.";
    public const PHONE_TR   = "{field} geçerli bir Türk GSM numarası olmalıdır.";
}
