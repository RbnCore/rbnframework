<?php

namespace Rbn\Framework\Core\Support\Blueprints\Validations;

/**
 * EmailValidations - Networking & Identity DNA 📧💎
 */
class EmailValidations
{
    // --- [TECHNICAL DNA ] ---

    public const TRUSTED_DOMAINS = [
        'gmail.com',
        'hotmail.com',
        'outlook.com',
        'yahoo.com',
        'icloud.com',
        'protonmail.com',
        'me.com',
        'live.com',
        'rbncore.tr',
        'rbn.com.tr',
        'rbn.tr',
        'rbn.net.tr',
        'rbn.org.tr',
        'rbn.info.tr',
        'rbn.biz.tr',
        'rbn.com',
        'rbn.net',
        'rbn.org',
        'rbn.info',
        'rbn.biz',
        'rbnbilisim.com',
        'rbnbilisim.com.tr',
        'rbnbilisim.tr',
        'rbnbilisim.net',
        'rbnbilisim.org',
        'rbnbilisim.info',
        'rbnbilisim.biz',
    ];

    public const HIGH_REPUTATION_DOMAINS = [
        'apple.com',
        'microsoft.com',
        'google.com',
        'facebook.com',
        'github.com',
        'twitter.com',
        'linkedin.com',
        'amazon.com',
        'netflix.com'
    ];

    public const TEMP_DOMAINS = [
        '10minutemail.com',
        '10minutemail.net',
        '10minutemail.org',
        'tempmail.org',
        'temp-mail.org',
        'guerrillamail.com',
        'guerrillamail.net',
        'guerrillamail.org',
        'mailinator.com',
        'mailinator.net',
        'sharklasers.com',
        'guerrillamailblock.com',
        'yopmail.com',
        'yopmail.fr',
        'cool.fr.nf',
        'jetable.fr.nf',
        'throwaway.email',
        'tempail.com',
        'temp-mail.ru',
        'mohmal.com',
        'tempinbox.com',
        'minuteinbox.com',
        'emailondeck.com',
        'trashmail.com',
        'getnada.com',
        'tempmailo.com',
        'maildrop.cc',
        'disposablemail.com',
        'tempmail.plus',
        'temp-mail.io',
        'dropmail.me',
        'emlpro.com',
        'boun.cr',
        'temp-mail.org.ua',
        'disposable.com',
        'crazymailing.com'
    ];

    public const SPAM_DOMAINS = [
        'spam.com',
        'example-spam.com',
        'test-spam.org',
        'malware-links.net',
        'spammer.me',
        'spammer.com',
        'bulk-email.net',
        'market-leads.com',
        'cheap-marketing.biz',
        'spam-sender.org',
        'mail-leads.io',
        'unsolicited-mail.com',
        'spam-trap.org',
        'blacklisted-domain.com',
        'spam-domain.net',
        'marketing-spams.com',
        'bulkmail.xyz',
        'leads-collector.com',
        'spam-mail.top',
        'email-marketing-leads.com'
    ];

    public const ROLE_BASED_ADDRESSES = [
        'info',
        'support',
        'sales',
        'marketing',
        'it',
        'billing',
        'office',
        'contact',
        'hello',
        'help',
        'service',
        'news'
    ];

    public const SUSPICIOUS_PATTERNS = [
        '/noreply/i',
        '/no-reply/i',
        '/admin@/i',
        '/test@/i',
        '/demo@/i',
        '/abuse@/i',
        '/postmaster@/i',
        '/root@/i',
        '/dont-reply/i',
        '/unsubscribed/i',
        '/do-not-reply/i'
    ];

    public const DISPOSABLE_PATTERNS = [
        '/\+/i', // 🧬 user+alias@domain.com
        '/[0-9]{8,}/i' // 🔍 Randomized strings like a1b2c3d4@...
    ];
}
