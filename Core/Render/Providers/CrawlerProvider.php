<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Render\Providers;

use Rbn\Framework\Core\Base\Web\BaseRender;
use Rbn\Framework\Core\Support\Contracts\Base\BaseRenderInterface;

/**
 * CrawlerProvider - Unified Search Engine Indexing & Discovery Hub 🛰️🤖🗺️⚓
 * 
 * RBN 3.5 Masterpiece: Centralized provider for Robots directives and XML Sitemaps.
 * Manages how external crawlers perceive and navigate the system structure.
 */
class CrawlerProvider extends BaseRender implements BaseRenderInterface
{
    /**
     * Unified Entry Point: Dispatches to specialized crawling segments. 🏹
     */
    public function render(?string $view, array $data = []): string
    {
        if ($view === 'robots')
            return $this->renderRobots($data);
        if ($view === 'sitemap')
            return $this->renderSitemap($data);
        if ($view === 'feed')
            return $this->renderFeed($data);
        if ($view === 'llms')
            return $this->renderLlms($data);

        return '';
    }

    /**
     * Segment: Robots.txt Rendering 🤖⚓
     * RBN 3.5: Generates plain-text directives for search engine crawlers.
     */
    public function renderRobots(array $data = []): string
    {
        $isProduction = $data['is_production'] ?? false;
        $rows = ["# RBN CRAWLER HUB: ROBOTS ACTIVE"];

        if (!$isProduction) {
            $rows[] = "User-agent: *";
            $rows[] = "Disallow: /";
        } else {
            $rows[] = "User-agent: *";

            // 🛡️ Standard & Project Disallows
            $disallows = array_merge(
                $data['disallows'] ?? [],
                $data['project_disallows'] ?? [],
                $data['asset_disallows'] ?? []
            );

            foreach (array_unique($disallows) as $path) {
                $rows[] = "Disallow: $path";
            }

            // ✅ Standard & Project Allows (Googlebot Spec: Allow rules take priority when more specific or listed)
            $allows = array_merge(
                $data['allows'] ?? [],
                $data['project_allows'] ?? []
            );

            foreach (array_unique($allows) as $path) {
                $rows[] = "Allow: $path";
            }

            // 🤖 Bot Specific Rules
            foreach ($data['bot_rules'] ?? [] as $bot => $rules) {
                $rows[] = "";
                $rows[] = "User-agent: $bot";
                foreach ($rules as $rule) {
                    $rows[] = $rule;
                }
            }

            if (!empty($data['sitemap'])) {
                $rows[] = "";
                $rows[] = "Sitemap: " . $data['sitemap'];
            }
        }

        return implode(PHP_EOL, $rows);
    }

    /**
     * Segment: XML Sitemap Rendering 🗺️⚓
     * RBN 3.5: Generates valid XML directory map for index optimization.
     */
    public function renderSitemap(array $data = []): string
    {
        $builder = $data['builder'] ?? null;
        $isIndex = $data['is_index'] ?? false;

        if ($builder instanceof \Rbn\Framework\Core\Render\Builders\SitemapBuilder) {
            return $builder->buildXml($isIndex);
        }

        return '';
    }

    /**
     * Physical Data: Fetch active legal pages from DB 🏺🛰️⚓
     */
    public function getActivePages(): array
    {
        try {
            // [FW-ALTYAPI-3 / H · G4] `PagesModel` artık `scoped = true`.
            // Açık anahtar verilirse kapsam `withProjectScope()` ile O anahtara
            // daraltılır; verilmezse modelin kapsamı (aktif bağlam) geçerli.
            $model = $this->model('page');
            if (!empty($this->projectKey)) {
                $model = $model->withProjectScope((string) $this->projectKey);
            }
            return $model->where('status', 'active')->get()->all();
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Physical Data: Count active legal pages 📊
     */
    public function getActivePagesCount(): int
    {
        try {
            $model = $this->model('page');
            if (!empty($this->projectKey)) {
                $model = $model->withProjectScope((string) $this->projectKey);
            }
            return $model->where('status', 'active')->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Check if the current project has a valid feed source (posts) configured 📡
     */
    public function hasFeed(): bool
    {
        $sources = $this->getProject('source') ?? [];
        foreach ($sources as $source) {
            if (!empty($source['feed'])) {
                return true;
            }
        }
        return isset($sources['posts']);
    }

    /**
     * 🛰️ RBN 3.5: [SOVEREIGN PROJECT SSOT] ⚓
     * Projeye ait çekirdek ayarları ve kaynakları (Handler üzerinden) döndürür.
     */
    public function getProject(string $type)
    {
        if ($type === 'url') {
            return rtrim(\Rbn\Framework\Core\System\Paths\Paths::project()->baseUrl(), '/');
        }

        if ($type === 'source') {
            // ⚓ Çekirdek (Core) Kaynaklar
            $coreSources = [
                'pages' => [
                    'title' => 'Sayfalar',
                    'service' => \Rbn\Framework\Core\Render\Resolvers\Sub\SitemapResolver::class,
                    'count_method' => 'getPagesCount',
                    'entries_method' => 'getPagesSitemap'
                ]
            ];

            // 🎼 Project Sources: Resolver üzerinden otonom olarak çekilir 🛡️🛰️⚓
            $projectSources = $this->resolver('crawler')->resolveProjectSources();

            return array_merge($coreSources, $projectSources);
        }

    }

    /**
     * Segment: XML RSS Feed Rendering 📡📰
     * RBN 3.5: Generates valid XML feed using FeedBuilder.
     */
    public function renderFeed(array $data = []): string
    {
        $builder = $this->handler('feedBuilder');
        if ($builder instanceof \Rbn\Framework\Core\Render\Builders\FeedBuilder) {
            $channel = [
                'title' => $data['title'] ?? '',
                'channelLink' => $data['channelLink'] ?? '',
                'feedLink' => $data['feedLink'] ?? '',
                'description' => $data['description'] ?? ''
            ];
            return $builder->setChannel($channel)->addPosts($data['posts'] ?? [])->buildXml();
        }
        return '';
    }

    /**
     * Segment: LLMs.txt Markdown Rendering 🤖📄
     * RBN 3.5: Generates valid LLMs markdown using LlmsBuilder.
     */
    public function renderLlms(array $data = []): string
    {
        $builder = $this->handler('llmsBuilder');
        if ($builder instanceof \Rbn\Framework\Core\Render\Builders\LlmsBuilder) {
            $builder->reset();
            $builder->setSiteInfo($data['site_name'] ?? 'Website', $data['site_url'] ?? '');

            foreach (($data['sections'] ?? []) as $sectionTitle => $items) {
                $builder->addSection($sectionTitle, $items);
            }

            return $builder->buildMarkdown();
        }
        return '';
    }
}
