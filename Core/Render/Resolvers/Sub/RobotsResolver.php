<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Render\Resolvers\Sub;

use Rbn\Framework\Core\Base\Web\BaseRender;
use Rbn\Framework\Core\Render\Configs\RobotsConfig;
use Rbn\Framework\Core\System\Paths\Paths;

/**
 * RobotsResolver - Dedicated Search Engine Rules Resolver 🤖🛡️⚓
 * Part of RBN 3.5 Sovereign Framework Standards.
 */
class RobotsResolver extends BaseRender
{
    public function resolvePayload(): array
    {
        $isProduction = !is_local();
        $rules = [
            'disallows' => [],
            'allows' => [],
            'bot_rules' => RobotsConfig::BOT_RULES,
            'is_production' => $isProduction,
            'sitemap' => Paths::project()->baseUrl() . '/sitemap.xml'
        ];

        // 🛡️ DEV / STAGING & DOMAIN MISMATCH GUARD
        $currentHost = strtolower($_SERVER['HTTP_HOST'] ?? '');
        $projectData = \Rbn\Framework\Core\System\Kernel\Bootstrap::getAppContext('project_data') ?: [];
        $officialDomain = strtolower((string) ($projectData['domain'] ?? ''));

        $isRbnCoreDomain = str_contains($currentHost, '.rbncore.tr');
        $isDomainMismatch = !empty($officialDomain) && $currentHost !== $officialDomain && $currentHost !== ($officialDomain . '.test') && $currentHost !== ($officialDomain . '.local');

        if (!$isProduction || $isRbnCoreDomain || $isDomainMismatch) {
            $rules['is_production'] = false;
            return $rules;
        }

        $rules['disallows'] = RobotsConfig::ROBOTS_DISALLOWS;
        $rules['asset_disallows'] = RobotsConfig::ASSET_DISALLOWS;
        $rules['allows'] = RobotsConfig::ALLOWED_PATHS;

        if ($prefix = $this->service('route')?->getBasePrefix()) {
            $rules['disallows'][] = '/' . trim($prefix, '/');
        }

        $seoConfig = $this->resolver('seo')?->getSeoConfig() ?? [];
        $projectRobots = $seoConfig['robots'] ?? [];
        $rules['project_disallows'] = (array) ($projectRobots['disallow'] ?? []);
        $rules['project_allows'] = (array) ($projectRobots['allow'] ?? []);

        if (!$this->provider('crawler')->hasFeed()) {
            $rules['project_disallows'][] = '/feed';
        }

        return $rules;
    }

    public function isPathRestricted(string $path): bool
    {
        $currentHost = strtolower($_SERVER['HTTP_HOST'] ?? '');
        $projectData = \Rbn\Framework\Core\System\Kernel\Bootstrap::getAppContext('project_data') ?: [];
        $officialDomain = strtolower((string) ($projectData['domain'] ?? ''));

        if (str_contains($currentHost, '.rbncore.tr') || (!empty($officialDomain) && $currentHost !== $officialDomain && $currentHost !== ($officialDomain . '.test') && $currentHost !== ($officialDomain . '.local'))) {
            return true;
        }

        $path = '/' . ltrim($path, '/');
        $lowerPath = strtolower($path);

        if ($lowerPath === '/feed' && !$this->provider('crawler')->hasFeed()) {
            return true;
        }

        foreach (RobotsConfig::SYSTEM_DISALLOWS as $disallow) {
            if (str_starts_with($lowerPath, strtolower($disallow))) {
                return true;
            }
        }

        $prefix = $this->service('route')?->getBasePrefix();
        if ($prefix && str_starts_with($lowerPath, strtolower('/' . ltrim($prefix, '/')))) {
            return true;
        }

        $seoConfig = $this->resolver('seo')?->getSeoConfig() ?? [];
        foreach ((array) ($seoConfig['robots']['disallow'] ?? []) as $disallow) {
            if (str_starts_with($path, $disallow)) {
                return true;
            }
        }

        return false;
    }
}
