<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnEmail\Models;

/**
 * ImapConstant - IMAP/SMTP istemcisinin sabitleri.
 *
 * Bağlantı süreleri, klasör rolleri ve rol eşleme adları TEK yerde durur;
 * istemci ve servisler bu dosyadan okur.
 */
class ImapConstant
{
    /** Bağlantı kurma ve okuma zaman aşımı (saniye). */
    public const TIMEOUT = 15;

    /** Tek bir yanıtta kabul edilen en büyük literal (bayt) — bellek koruması. */
    public const MAX_LITERAL_BYTES = 52428800;

    /** Tek satırda okunacak en çok bayt. */
    public const LINE_CHUNK = 8192;

    /** Güvenlik kipleri: doğrudan TLS ya da düz bağlantı + STARTTLS. Düz metin YOK. */
    public const SECURITY_SSL = 'ssl';
    public const SECURITY_STARTTLS = 'starttls';
    public const SECURITIES = [self::SECURITY_SSL, self::SECURITY_STARTTLS];

    /** Hata kodları (istisnanın `errorCode()` değeri). */
    public const ERR_UNAVAILABLE = 'IMAP_UNAVAILABLE';
    public const ERR_TLS = 'TLS_VERIFY_FAILED';
    public const ERR_AUTH = 'AUTH_FAILED';
    public const ERR_PROTOCOL = 'PROTOCOL_ERROR';
    public const ERR_SMTP = 'SMTP_FAILED';

    /** Klasör rolleri. */
    public const ROLE_INBOX = 'inbox';
    public const ROLE_SENT = 'sent';
    public const ROLE_TRASH = 'trash';
    public const ROLE_SPAM = 'spam';
    public const ROLE_DRAFTS = 'drafts';
    public const ROLE_ARCHIVE = 'archive';
    public const ROLES = [
        self::ROLE_INBOX, self::ROLE_SENT, self::ROLE_DRAFTS,
        self::ROLE_SPAM, self::ROLE_ARCHIVE, self::ROLE_TRASH,
    ];

    /** RFC 6154 SPECIAL-USE öznitelikleri → rol. */
    public const SPECIAL_USE = [
        '\\sent' => self::ROLE_SENT,
        '\\trash' => self::ROLE_TRASH,
        '\\junk' => self::ROLE_SPAM,
        '\\drafts' => self::ROLE_DRAFTS,
        '\\archive' => self::ROLE_ARCHIVE,
    ];

    /** SPECIAL-USE yoksa klasörün SON bölümünün (küçük harf) adına göre rol. */
    public const ROLE_NAMES = [
        self::ROLE_SENT => ['sent', 'sent items', 'sent messages', 'sent mail', 'gönderilenler', 'gönderilmiş'],
        self::ROLE_TRASH => ['trash', 'deleted items', 'deleted messages', 'deleted', 'çöp', 'çöp kutusu'],
        self::ROLE_SPAM => ['spam', 'junk', 'junk e-mail', 'junk email', 'istenmeyen'],
        self::ROLE_DRAFTS => ['drafts', 'draft', 'taslaklar'],
        self::ROLE_ARCHIVE => ['archive', 'archives', 'arşiv'],
    ];

    /** Rol için klasör yoksa oluşturulacak ad (ad alanı öneki eklenir). */
    public const DEFAULT_FOLDER_NAMES = [
        self::ROLE_SENT => 'Sent',
        self::ROLE_TRASH => 'Trash',
    ];
}
