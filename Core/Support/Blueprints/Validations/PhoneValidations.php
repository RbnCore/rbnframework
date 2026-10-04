<?php

namespace Rbn\Framework\Core\Support\Blueprints\Validations;

/**
 * PhoneValidations - GSM & Regional DNA 📱💎
 */
class PhoneValidations
{
    // --- [ TECHNICAL DNA ] ---

    public const TURKISH_PHONE_PREFIXES = [
        '501', '505', '506', '507', '551', '552', '553', '554', '555', '559',
        '530', '531', '532', '533', '534', '535', '536', '537', '538', '539', '561',
        '540', '541', '542', '543', '544', '545', '546', '547', '548', '549',
        '510'
    ];

    public const TURKISH_PHONE_LENGTH = 11;
}
