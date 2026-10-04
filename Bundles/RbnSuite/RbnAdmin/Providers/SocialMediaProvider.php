<?php

namespace Rbn\Framework\Bundles\RbnSuite\RbnAdmin\Providers;

use Rbn\Framework\Core\Base\Services\BaseProvider;
use Rbn\Framework\Core\System\Paths\Paths;

/**
 * SocialMediaProvider - Platform Data Specialist 🎭🏛️⚓
 * RBN 3.5 Masterpiece Standard.
 * 
 * Bu sağlayıcı hem platform tanımlarını yönetir hem de 
 * akıcı (fluent) bir arayüz sunar.
 */
class SocialMediaProvider extends BaseProvider
{
    protected $targetModel = 'SocialMedia';

    /** @var array Platform veri havuzu */
    protected array $platforms = [];

    /**
     * Constructor: Load platforms directly from JSON 🧬
     */
    public function __construct()
    {
        $path = Paths::frameworkRoot() . '/Resources/Data/social_media_platforms.json';
        if (\file_exists($path)) {
            $json = \file_get_contents($path);
            $this->platforms = \json_decode($json, true) ?: [];
        }
    }

    /** @var array Aktif seçili platform verisi */
    protected array $activeData = [];

    /**
     * Set context to a specific platform 🏗️
     */
    public function getPlatform(string $key): self
    {
        $this->activeData = $this->platforms[$key] ?? [];
        return $this;
    }

    /**
     * Get all defined platforms 🌈
     */
    public function all(bool $sortByOrder = true): array
    {
        $platforms = $this->platforms;

        if ($sortByOrder) {
            \uasort($platforms, fn($a, $b) => ($a['order'] ?? 999) <=> ($b['order'] ?? 999));
        }

        return $platforms;
    }

    /**
     * Get the total count of platforms 📈
     */
    public function total(): int
    {
        return count($this->platforms);
    }

    /* ==========================================================================
       [ FLUENT ACCESSORS ] - Single Platform Context 🕊️
       ========================================================================== */

    public function exists(): bool
    {
        return !empty($this->activeData);
    }

    public function title(): string
    {
        return $this->activeData['title'] ?? '';
    }

    public function icon(): string
    {
        return $this->activeData['bi_icon'] ?? 'bi-link-45deg';
    }

    public function faIcon(): string
    {
        return $this->activeData['fa_icon'] ?? 'fas fa-link';
    }

    public function riIcon(): string
    {
        return $this->activeData['ri_icon'] ?? 'ri-link';
    }

    public function color(): string
    {
        return $this->activeData['color'] ?? '#6c757d';
    }

    public function order(): int
    {
        return $this->activeData['order'] ?? 999;
    }

    public function shareUrl(): string
    {
        return $this->activeData['share_url'] ?? '';
    }

    public function shareClass(): string
    {
        return $this->activeData['share_class'] ?? '';
    }
}
