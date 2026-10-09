<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Base\Attributes;

use Attribute;
use Rbn\Framework\Core\Base\BaseAttribute;

/**
 * Bundle Attribute - RBN Framework Module Identity & Discovery 🏛️🛰️⚓
 * RBN Framework Standard.
 * 
 * Replaces static CONFIG arrays with modern PHP 8.1 Attributes.
 */
#[Attribute(Attribute::TARGET_CLASS)]
class Bundle extends BaseAttribute
{
    /**
     * @param string      $name     Modülün sistemdeki benzersiz adı (örn: blog, widgets)
     * @param string|null $context  Çalışma bağlamı (frontend, backend, api)
     * @param array       $map      Modülün harita/sub-module yapılandırması
     */
    public function __construct(
        public string $name,
        public ?string $context = null,
        public array $map = [],
        public bool $register = true
    ) {
        parent::__construct(['alias' => $name]);
    }
}
