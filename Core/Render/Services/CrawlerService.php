<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Render\Services;

use Rbn\Framework\Core\Base\Services\BaseService;

/**
 * CrawlerService - Unified Search Engine Indexing & Discovery Orchestrator 🎼🛰️🤖🗺️⚓
 * 
 * RBN Framework: Centralized service for management of Robots directives and Sitemaps.
 * Coordinates between Context Handlers and the Crawler Provider Hub.
 */
class CrawlerService extends BaseService
{
    /**
     * Entry Point: Generate full robots.txt content. 🤖⚓
     */
    public function generateRobots(): string
    {
        $payload = $this->resolver('robotsResolver')->resolvePayload();
        return $this->provider('crawler')->render('robots', $payload);
    }

    /**
     * Entry Point: Generate main sitemap.xml content. 🗺️⚓
     */
    public function generateSitemapXml(): string
    {
        $payload = $this->resolver('sitemapResolver')->resolvePayload('sitemap');
        return $this->provider('crawler')->render('sitemap', $payload);
    }

    /**
     * Entry Point: Generate specialized sub-sitemap content. 🗺️🛰️
     */
    public function generateSubSitemap(string $type): string
    {
        $payload = $this->resolver('sitemapResolver')->resolvePayload($type);
        return $this->provider('crawler')->render('sitemap', $payload);
    }

    /**
     * Entry Point: Generate dynamic RSS Feed XML. 📡📰
     */
    public function generateFeed(): string
    {
        $payload = $this->resolver('feedResolver')->resolvePayload();
        return $this->provider('crawler')->render('feed', $payload);
    }

    /**
     * Entry Point: Generate dynamic LLMs.txt markdown content. 🤖⚓
     */
    public function generateLlms(): string
    {
        $payload = $this->resolver('llmsResolver')->resolvePayload();
        return $this->provider('crawler')->render('llms', $payload);
    }
}
