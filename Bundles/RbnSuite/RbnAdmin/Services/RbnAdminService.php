<?php

declare(strict_types=1);

namespace Rbn\Framework\Bundles\RbnSuite\RbnAdmin\Services;

use Rbn\Framework\Core\Base\Services\BaseService;

/**
 * RbnAdminService - Centralized Intelligence Service for Admin Suite 🏛️🛰️⚓
 * Handles core administrative logic and data orchestration.
 */
class RbnAdminService extends BaseService
{
    /**
     * Saves primary and secondary colors using SettingsService directly 🎨✨
     *
     * @param string $primaryColor
     * @param string|null $secondaryColor
     * @param string|null $projectKey
     * @return bool
     * @throws \Exception
     */
    public function saveColors(string $primaryColor, ?string $secondaryColor = null, ?string $projectKey = null): bool
    {
        // 1. Permission Challenge 🛡️
        $access = $this->handler('access');
        if (!$access || !$access->can('admin')) {
            throw new \Exception('Bu işlem için yönetici yetkisi gereklidir.');
        }

        $projectKey = $projectKey ?: project_key() ?: 'default';

        $settings = [
            'theme_color_primary' => $primaryColor
        ];

        if ($secondaryColor) {
            $settings['theme_color_secondary'] = $secondaryColor;
        }

        // 2. Direct save using SettingsService updateSettings
        return $this->service('settings')->updateSettings($settings, $projectKey);
    }

    /* ==========================================================================
       [ SOCIAL MEDIA HUB ] 🎭
       ========================================================================== */

    /**
     * Get all social media platforms 🌈
     */
    public function allSocialPlatforms(bool $sortByOrder = true): array
    {
        return $this->provider('social')->all($sortByOrder);
    }

    /**
     * Get a specific platform fluently 🕊️
     */
    public function socialPlatform(string $key): object
    {
        return $this->provider('social')->getPlatform($key);
    }

    /**
     * Get total platform count 📈
     */
    public function totalSocialPlatforms(): int
    {
        return $this->provider('social')->total();
    }

    /**
     * Get share buttons for a URL and title 📢
     *
     * @param string $url
     * @param string $title
     * @return array
     */
    public function getShareButtons(string $url, string $title): array
    {
        $encodedUrl = \urlencode($url);
        $encodedTitle = \urlencode($title);
        
        $socialProvider = $this->provider('social');
        $platforms = $socialProvider->all();

        $shareButtons = [];

        foreach ($platforms as $key => $rawPlatform) {
            $platform = $socialProvider->getPlatform($key);

            if ($platform->exists() && $platform->shareUrl() !== '' && $platform->shareClass() !== '') {
                $shareUrl = \str_replace(
                    ['{url}', '{title}'],
                    [$encodedUrl, $encodedTitle],
                    $platform->shareUrl()
                );

                $shareButtons[] = [
                    'name' => $platform->title(),
                    'class' => $platform->shareClass(),
                    'bi_icon' => $platform->icon(),
                    'fa_icon' => $platform->faIcon(),
                    'ri_icon' => $platform->riIcon(),
                    'url' => $shareUrl,
                    'color' => $platform->color()
                ];
            }
        }

        return $shareButtons;
    }
}

