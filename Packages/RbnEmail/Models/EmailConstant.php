<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnEmail\Models;

use Rbn\Framework\Core\Support\Definitions\System\FrameworkIdentity;

/**
 * EmailConstant - RbnEmail Specific Metadata and Defaults 🚀📧
 */
class EmailConstant
{
    public const NAME = 'RbnEmail';
    public const VERSION = '1.5'; // Upgraded to Masterpiece Standard
    public const SLOGAN = 'Professional Email Delivery Service';

    // PHPMailer Basics
    public const SMTP_AUTH = true;
    public const SMTP_SECURE = 'tls'; // 'ssl' for port 465, 'tls' for 587
    public const TIMEOUT = 30;
    public const CHARSET = 'UTF-8';

    // Debug & Logging
    public const DEBUG = false;
    public const LOG_ENABLED = true;
    public const LOG_FILE = 'email';
    public const DEBUG_LOG_FILE = 'email_debug';

    // Visual Themes & HTML Defaults
    public const DEFAULT_THEME_COLOR = '#3b82f6';
    public const DEFAULT_BG_COLOR = '#f3f4f6';
    public const DEFAULT_TEXT_COLOR = '#1f2937';

    public const FOOTER_SIGNATURE = 'Powered by ' . FrameworkIdentity::DEVELOPER_NAME;
    public const FOOTER_URL = FrameworkIdentity::DEVELOPER_URL;

    public const DEFAULT_LOGO_SVG = "data:image/svg+xml;utf8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Ctext y='.9em' font-size='80'%3E%F0%9F%93%A8%3C/text%3E%3C/svg%3E";

    /**
     * VIP Defaults DNA 🧬⚓
     */
    public const DEFAULTS = [
        'enabled'           => false,
        'timeout'           => self::TIMEOUT,
        'charset'           => self::CHARSET,
        'is_html'           => true,
        'site_name'         => null,
        'site_logo'         => self::DEFAULT_LOGO_SVG,
        'primary_color'     => self::DEFAULT_THEME_COLOR,
        'secondary_color'   => self::DEFAULT_THEME_COLOR,
        'bg_color'          => self::DEFAULT_BG_COLOR,
        'text_color'        => self::DEFAULT_TEXT_COLOR,
        'signature'         => self::FOOTER_SIGNATURE,
        'app_url'           => self::FOOTER_URL
    ];
}
