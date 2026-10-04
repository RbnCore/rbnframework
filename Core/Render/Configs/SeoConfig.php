<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Render\Configs;

use Rbn\Framework\Core\Base\Data\BaseConfig;
use Rbn\Framework\Core\Support\Definitions\System\FrameworkIdentity;

/**
 * SeoConfig - Centralized SEO and Metadata Configuration 📈⚙️⚓
 * 
 * RBN 3.5 Masterpiece: Core defaults for the SEO Engine.
 * Leverages FrameworkIdentity for branding synchronization.
 */
class SeoConfig extends BaseConfig
{
    public const NAME = 'RbnSeo';
    public const VERSION = '1.3';
    public const SLOGAN = 'Professional SEO and Metadata Service';

    /** Default social image placeholder */
    public const OG_IMAGE = 'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><text y=".9em" font-size="80">🚀</text></svg>';

    /** Default favicon placeholder */
    public const LOGO_SVG = 'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><text y=".9em" font-size="80">🌐</text></svg>';

    /**
     * Standard SEO Defaults 🧬
     * Dynamically pulls Brand Name from FrameworkIdentity.
     */
    public static function defaults(): array
    {
        $brand = FrameworkIdentity::FRAMEWORK_NAME;

        return [
            'title' => "{$brand} | Digital Transformation & Software Solutions",
            'description' => "{$brand} provides modern web technologies, enterprise software solutions, and high-performance digital transformation architectures.",
            'keywords' => "enterprise software, web design, e-commerce, mobile application, seo, digital transformation, {$brand}",
            'og_title' => "{$brand} | Digital Transformation & Software Solutions",
            'og_description' => "{$brand} provides modern web technologies, enterprise software solutions, and high-performance digital transformation architectures.",
            'og_image' => self::OG_IMAGE
        ];
    }
}
