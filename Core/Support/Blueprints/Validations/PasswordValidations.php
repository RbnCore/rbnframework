<?php

namespace Rbn\Framework\Core\Support\Blueprints\Validations;

/**
 * PasswordValidations - Security & Complexity DNA 🔐💎
 */
class PasswordValidations
{
    // --- [ TECHNICAL DNA ] ---

    /**
     * [A-12] YENI parola belirleyen tum akislarin (kayit / sifre sifirlama)
     * kullandigi TABAN minimum uzunluk. Tek tanim noktasi: form katmani
     * (`'min:' . PasswordValidations::MIN_PASSWORD_LENGTH`) ve isi kurali
     * (`CredentialHandler::validatePassword()`) BOTH buradan beslenir.
     * GIRIS (login) yolunda UZUNLUK DENETLENMEZ; mevcut kullanicilarin
     * kisa parolalari gecerli kalir.
     */
    public const MIN_PASSWORD_LENGTH = 8;

    public const COMMON_PASSWORDS = [
        '123456', 'password', '123456789', '12345678', 'qwerty', 'abc123', 'admin', 'letmein', 'welcome', 'sifre', 'parola'
    ];

    public const COMMON_WORDS = [
        'computer', 'internet', 'security', 'system', 'network', 'server', 'database', 'software',
        'bilgisayar', 'guvenlik', 'sistem', 'sunucu', 'yazilim', 'ahmet', 'mehmet', 'ayse', 'fatma'
    ];

    public const SEQUENTIAL_PATTERNS = [
        'abcdefghijklmnopqrstuvwxyz', 'qwertyuiopasdfghjklzxcvbnm', '123456789', '987654321'
    ];

    public const KEYBOARD_PATTERNS = [
        'qwerty', 'wert', 'asdf', 'zxcv', 'azerty', 'qwertz', '1234', '12345'
    ];
}
