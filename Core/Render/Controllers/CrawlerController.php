<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Render\Controllers;

use Rbn\Framework\Core\Base\Web\BaseController;
use Rbn\Framework\Core\Support\Definitions\System\FrameworkIdentity;

/**
 * CrawlerController - Unified Discovery Delivery Hub 🛰️🤖🗺️⚓
 * 
 * RBN 3.5 Masterpiece: Centralized controller for serving Robots.txt and XML Sitemaps.
 * Manages caching orchestration and content-type integrity for all crawler resources.
 */
class CrawlerController extends BaseController
{
    /**
     * Entry Point: Robots.txt Delivery 🤖⚓
     * Route: /robots.txt
     */
    public function robots()
    {
        return $this->serve('robots', 'text/plain', 'robots_txt_');
    }

    /**
     * Entry Point: Main Sitemap Delivery 🗺️⚓
     * Route: /sitemap.xml
     */
    public function sitemap()
    {
        return $this->serve('sitemap', 'application/xml', 'sitemap_xml_');
    }

    /**
     * Entry Point: Specialized Sub-Sitemap Delivery 🗺️🛰️
     * Route: /sitemap_{type}.xml
     */
    public function subSitemap(string $type)
    {
        return $this->serve('sitemap', 'application/xml', "sitemap_xml_{$type}_", $type);
    }

    /**
     * Entry Point: Dynamic RSS Feed Delivery 📡📰
     * Route: /feed
     */
    public function feed()
    {
        if (!$this->provider('crawler')->hasFeed()) {
            shield()->abort(404);
        }

        header('X-Robots-Tag: noindex, follow');

        return $this->serve('feed', 'application/rss+xml; charset=utf-8', 'rss_feed_');
    }

    /**
     * Entry Point: Sitemap XSL Stylesheet Delivery 🎨⚓
     * Route: /sitemap.xsl
     */
    public function sitemapXsl()
    {
        $xslPath = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'Resources' . DIRECTORY_SEPARATOR . 'Views' . DIRECTORY_SEPARATOR . 'System' . DIRECTORY_SEPARATOR . 'sitemap.xsl';
        $content = file_exists($xslPath) ? file_get_contents($xslPath) : '';

        // Dinamik olarak butonları (kaynakları) oluştur
        $buttonsHtml = '<a href="/" class="btn btn-home">Ana Sayfa</a>';
        $buttonsHtml .= '<a href="/sitemap.xml" class="btn">Ana Sitemap</a>';

        $crawlerProvider = $this->provider('crawler');
        $sources = $crawlerProvider->getProject('source') ?? [];

        foreach ($sources as $key => $source) {
            $title = $source['title'] ?? null;
            if (empty($title)) {
                $title = ucfirst(str_replace(['-', '_'], ' ', $key));
            }

            $buttonsHtml .= '<a href="/sitemap-' . $key . '.xml" class="btn">' . htmlspecialchars($title) . '</a>';
        }

        // Dinamik olarak uygulama adını, butonları ve framework bilgilerini yerleştir
        $content = str_replace([
            '{{APP_NAME}}',
            '{{FRAMEWORK_NAME}}',
            '{{FRAMEWORK_URL}}',
            '{{NAV_BUTTONS}}'
        ], [
            $this->appName,
            FrameworkIdentity::FRAMEWORK_NAME,
            FrameworkIdentity::FRAMEWORK_URL,
            $buttonsHtml
        ], $content);

        return $this->response
            ->contentType('text/xsl')
            ->body($content)
            ->send();
    }

    /**
     * Core: Unified Resource Delivery Orchestrator 🎻🚀
     * Handles service discovery, caching, and response dispatching.
     */
    protected function serve(string $type, string $contentType, string $cachePrefix, ?string $subType = null)
    {
        // 🎼 RBN 3.5: Masterpiece Hub Access 🚀⚓
        $crawlerService = $this->service('crawler');
        $cache = $this->service('storage')->cache();

        // 🔥 Dynamic Caching (Universal CacheProvider handles projectKey prepending)
        $cacheKey = rtrim($cachePrefix, '_');
        $content = $cache->get($cacheKey);

        if ($content === null) {
            // 🏹 Autonomous Generation 🚁
            $content = match ($type) {
                'robots' => $crawlerService->generateRobots(),
                'sitemap' => ($subType ? $crawlerService->generateSubSitemap($subType) : $crawlerService->generateSitemapXml()),
                'feed' => $crawlerService->generateFeed(),
                'llms' => $crawlerService->generateLlms(),
                default => ''
            };

            // 💾 Persist in Cache Hub
            $cache->set($cacheKey, $content, [
                'ttl' => $cache->getTtl($type === 'robots' ? 'long' : 'medium')
            ]);
        }

        // ⚓ RBN 3.5: Fluent High-Integrity Response 🏹🚀
        return $this->response
            ->contentType($contentType)
            ->body($content)
            ->send();
    }

    /**
     * Entry Point: Humans.txt Delivery 👤⚓
     * Route: /humans.txt
     */
    public function humans()
    {
        $content = "/* TEAM */\n";
        $content .= "Developer: " . FrameworkIdentity::DEVELOPER_NAME . "\n";
        $content .= "Site: " . FrameworkIdentity::DEVELOPER_URL . "\n\n";
        $content .= "/* SITE */\n";
        $content .= "Framework: " . FrameworkIdentity::FRAMEWORK_NAME . " " . FrameworkIdentity::FRAMEWORK_VERSION . "\n";
        $content .= "Software: PHP " . PHP_VERSION . "\n";

        return $this->response
            ->contentType('text/plain; charset=utf-8')
            ->body($content)
            ->send();
    }

    /**
     * Entry Point: Security.txt Delivery 🛡️⚓
     * Route: /security.txt or /.well-known/security.txt
     */
    public function security()
    {
        $content = "Contact: mailto:" . FrameworkIdentity::DEVELOPER_EMAIL . "\n";
        $content .= "Preferred-Languages: tr, en\n";
        $content .= "Purpose: Security vulnerability reporting channel.\n";

        return $this->response
            ->contentType('text/plain; charset=utf-8')
            ->body($content)
            ->send();
    }

    /**
     * Entry Point: LLMs.txt Delivery 🤖⚓
     * Route: /llms.txt
     */
    public function llms()
    {
        return $this->serve('llms', 'text/plain; charset=utf-8', 'llms_txt_');
    }

    /**
     * Entry Point: IndexNow Verification Key Delivery 🚀
     * Route: /{key}.txt
     */
    public function indexNowKey(string $key)
    {
        return $this->response
            ->contentType('text/plain; charset=utf-8')
            ->body($key)
            ->send();
    }
}
