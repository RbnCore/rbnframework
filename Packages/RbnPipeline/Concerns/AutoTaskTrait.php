<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnPipeline\Concerns;

use Rbn\Framework\Core\Support\Exceptions\CronTaskException;

/**
 * AutoTaskTrait - Shared Sovereign Pipeline Logic for All Automated Tasks (Blog, News, Product, etc.) 🤖🛰️⚓
 * 
 * Provides unified lifecycle checks, time matching, image generation retry loops,
 * and publishing handlers for all automated task services.
 * Masterpiece Refactoring: Modularized with PipelineResolverTrait & PipelineSanitizerTrait.
 */
trait AutoTaskTrait
{
    use PipelineResolverTrait;
    use PipelineSanitizerTrait;

    /**
     * Projenin aktif anahtarını (project_key) çözümleyen SOVEREIGN tekil metod 🔑🛡️
     */
    public function getProjectKey(?string $key = null, bool $throwOnEmpty = false): string
    {
        $resolvedKey = '';
        if (!empty($key) && $key !== 'unknown') {
            $resolvedKey = (string) $key;
        } elseif (isset($this->taskParams['project_key']) && !empty($this->taskParams['project_key'])) {
            $resolvedKey = (string) $this->taskParams['project_key'];
        } elseif (function_exists('project_key')) {
            $resolvedKey = (string) project_key();
        }

        if (empty($resolvedKey) && $throwOnEmpty) {
            throw CronTaskException::missingParameter('project_key');
        }

        return $resolvedKey;
    }

    /**
     * İlgili model üzerinden bugünkü gerçek yayın sayısını çeker 📊
     */
    public function getTodayPublishedCount(array $params): int
    {
        $localModel = $params['local_model'] ?? ($params['post_model'] ?? null);
        if (empty($localModel)) {
            return 0;
        }

        try {
            $modelInstance = $this->model($localModel);
            if (!$modelInstance) {
                return 0;
            }

            return (int) $modelInstance->query()
                ->whereRaw('DATE(created_at) = CURDATE()')
                ->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }
    public function publishPost(int $postId, string $modelAlias): bool
    {
        if ($postId <= 0 || empty($modelAlias)) {
            return false;
        }

        $model = $this->model($modelAlias);
        if (!$model) {
            return false;
        }

        try {
            return (bool) $model->query()->where('id', $postId)->update(['is_active' => 1]);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Sitemap önbelleğini (tüm alt sitemapler dâhil) temizler 🧹
     */
    public function clearSitemapCache(): bool
    {
        try {
            $cache = $this->cache();
            if ($cache) {
                if (method_exists($cache, 'deleteByPrefix')) {
                    $cache->deleteByPrefix('sitemap_xml');
                } else {
                    $cache->delete('sitemap_xml');
                    $cache->delete('sitemap_index');
                }
            }
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * RSS Feed önbelleğini temizler 📡🧹
     */
    public function clearFeedCache(): bool
    {
        try {
            $cache = $this->cache();
            if ($cache) {
                if (method_exists($cache, 'deleteByPrefix')) {
                    $cache->deleteByPrefix('rss_feed');
                } else {
                    $cache->delete('rss_feed');
                    $cache->delete('rss_feed_');
                }
            }
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Projenin tek izole içerik (content_data) önbelleğini temizler 📦🧹
     */
    public function clearContentCache(): bool
    {
        try {
            $cache = $this->cache();
            if ($cache) {
                $cache->delete('content_data');
            }
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Yayınlanan makale/haber için URL hesaplama ve HTML link üretme TEKİL metodu 🔗🌐
     */
    public function resolvePublishedPostLink(?string $projectKey = null, string $typePrefixOrUrl = '', string $postSlug = '', string $categorySlug = '', bool $asHtmlLink = true): string
    {
        $projectKey = $this->getProjectKey($projectKey);

        if (str_starts_with($typePrefixOrUrl, 'http://') || str_starts_with($typePrefixOrUrl, 'https://')) {
            $fullUrl = $typePrefixOrUrl;
        } else {
            $cleanType = trim($typePrefixOrUrl, '/');
            $postPath = !empty($categorySlug) ? "{$cleanType}/{$categorySlug}/{$postSlug}" : "{$cleanType}/{$postSlug}";
            $fullUrl = $this->targetProjectUrl($projectKey, $postPath);
        }

        if (empty($fullUrl)) {
            return '';
        }

        return $asHtmlLink
            ? "<a href=\"{$fullUrl}\" target=\"_blank\" style=\"color:#2563eb; text-decoration:underline; font-weight:600;\">{$fullUrl}</a>"
            : $fullUrl;
    }
}
