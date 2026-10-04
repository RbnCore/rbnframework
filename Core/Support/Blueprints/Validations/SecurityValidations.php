<?php

namespace Rbn\Framework\Core\Support\Blueprints\Validations;

/**
 * SecurityValidations - Content & URL Safety DNA 🛡️💎
 */
class SecurityValidations
{
    // --- [ TECHNICAL DNA ] ---

    public const BTK_BLOCK_IPS = [
        '195.175.254.2', '212.156.4.20'
    ];

    public const PROHIBITED_DOMAINS = [
        '.top', '.win', '.bid', '.date', '.party', '.adult', '.casino', '.bet', '.sex', 'bets10'
    ];

    public const SPAM_WORDS = [
        'spam', 'casino', 'betting', 'forex', 'viagra', 'cialis', 'porn', 'xxx', 'gambling'
    ];
}
