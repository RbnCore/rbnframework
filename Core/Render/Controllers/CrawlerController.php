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
        // [FW-094-BULGU-DUZELT / G-3] Beyan edilmemiş tür 404 verir ve ÖNBELLEĞE
        // YAZILMAZ (kimliksiz isteklerle önbellek/disk şişirme + 200 soft-404 kapanır).
        if (!$this->isKnownSubSitemap($type)) {
            $this->notFound();
        }

        return $this->serve('sitemap', 'application/xml', "sitemap_xml_{$type}_", $type);
    }

    /**
     * Entry Point: Dynamic RSS Feed Delivery 📡📰
     * Route: /feed
     */
    public function feed()
    {
        if (!$this->provider('crawler')->hasFeed()) {
            $this->notFound();
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

        // [FW-094-NOINDEX] Site düzeyi noindex: sitemap (+ alt sitemap'ler), llms.txt ve
        // feed YAYINLANMAZ (404). Önbellekten ÖNCE karar verilir: noindex açılmadan önce
        // üretilmiş çıktı asla servis edilmez. robots.txt ise `Disallow: /` ile cevap verir;
        // önbellek anahtarı noindex durumunu taşır, böylece iki durum birbirinin çıktısını
        // servis etmez (noindex değişince anahtar değişir; eski kayıt TTL ile kendiliğinden düşer).
        $siteNoindex = $this->provider('crawler')->isSiteNoindex();
        if ($siteNoindex) {
            if ($type !== 'robots') {
                $this->notFound();
            }
            $cacheKey .= '_noindex';
        }

        // [FW-094-BULGU-DUZELT / G-4] Çıktı host'a bağlıdır (robots/sitemap üretim ve
        // alan adı eşleşmesi kararı); önbellek anahtarı da host sınıfını taşır.
        $cacheKey .= '_' . $this->hostCacheSuffix();

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
     * [FW-094-BULGU-DUZELT / G-4] Önbellek anahtarının host eki.
     *
     * Resmi alan adı ailesinden (`domain`, `domain.test`, `domain.local`) gelen
     * istekler host'un kısa özetini taşır; resmi aileden OLMAYAN her host TEK
     * `x` sınıfına düşer (rastgele `Host` başlığıyla anahtar çoğaltılamaz).
     * Resmi alan adı tanımsızsa host başına özet kullanılır.
     */
    private function hostCacheSuffix(): string
    {
        $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
        $host = (string) preg_replace('/:\d+$/', '', $host);

        $projectData = \Rbn\Framework\Core\System\Kernel\Bootstrap::getAppContext('project_data') ?: [];
        $official = strtolower((string) ($projectData['domain'] ?? ''));

        if ($official !== '' && !in_array($host, [$official, $official . '.test', $official . '.local'], true)) {
            return 'x';
        }

        return substr(md5($host), 0, 8);
    }

    /**
     * [FW-094-BULGU-DUZELT / G-3] `/sitemap-{type}.xml` yalnız beyan edilmiş bir
     * kaynak adı (`pages`, proje kaynakları) ya da onun `-{sayfa}` eki olabilir.
     * Sayfa eki 1..toplam sayfa aralığındadır (başı sıfırlı/aralık dışı sayılar
     * her biri ayrı önbellek anahtarı üretirdi).
     */
    private function isKnownSubSitemap(string $type): bool
    {
        $sources = (array) $this->provider('crawler')->getProject('source');
        if (isset($sources[$type])) {
            return true;
        }

        if (preg_match('/^(.+)-([1-9][0-9]*)$/D', $type, $m) !== 1 || !isset($sources[$m[1]])) {
            return false;
        }

        $total = (int) $this->resolver('sitemapResolver')->getData($m[1], 1, true);

        return (int) $m[2] <= max(1, (int) ceil($total / 50000));
    }

    /**
     * [FW-094-NOINDEX] Gerçek HTTP 404 🚫
     * `shield()->abort(404)` düz `\Exception` fırlatır ve hata sayfası 500 döner;
     * yönlendirici ile aynı `PageNotFoundException` kullanılır.
     */
    private function notFound(): never
    {
        $uri = trim((string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH), '/');
        throw new \Rbn\Framework\Core\Support\Exceptions\PageNotFoundException($uri);
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
