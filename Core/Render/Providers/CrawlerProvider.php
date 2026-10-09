<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Render\Providers;

use Rbn\Framework\Core\Base\Web\BaseRender;
use Rbn\Framework\Core\Support\Contracts\Base\BaseRenderInterface;

/**
 * CrawlerProvider - Unified Search Engine Indexing & Discovery Hub 🛰️🤖🗺️⚓
 * 
 * RBN Framework: Centralized provider for Robots directives and XML Sitemaps.
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
     * RBN Framework: Generates plain-text directives for search engine crawlers.
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
     * RBN Framework: Generates valid XML directory map for index optimization.
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
            //
            // [FW-094-CRAWLER / Ö-1] Registry anahtarı `project.page`'dir
            // (`SystemPhysicalMapTrait.php:55`). ÖNCEKİ HALİ `model('page')`
            // idi: registry'de olmayan anahtar NULL döner, `->where()` Throwable
            // atar ve `catch` sessizce `[]` verirdi. ÖLÇÜM: 17/17 projede
            // `getActivePages()` = 0, `model('project.page')` = 62 satır →
            // DB'de aktif olan 62 yasal sayfa sitemap'ten SİLİNİYORDU (kayıp %100).
            $model = $this->model('project.page');
            if (!empty($this->projectKey)) {
                $model = $model->withProjectScope((string) $this->projectKey);
            }
            return $model->where('status', 'active')->get()->all();
        } catch (\Throwable $e) {
            // [FW-094-CRAWLER / Ö-1] Sessizlik kaldırıldı. Bu blok bir kez daha
            // sessiz `[]` dönerse aynı hata aylarca fark edilmez.
            error_log('[RBN-CRAWLER] getActivePages: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Physical Data: Does an ACTIVE page with this slug exist? 🔍📄
     *
     * [FW-094-CRAWLER / Ö-3] Standart dinamik sayfalar (`sik-sorulan-sorular`)
     * sabit bir sayfa DEĞİLDİR: denetleyici içerik yoksa ana sayfaya
     * yönlendirir (`GroupBaseController::faqs()` → `redirect('/')`). Sitemap ve
     * `llms.txt` böyle bir adresi listelerse arama motoruna "git, ana sayfa"
     * demiş olur. Sitemap girdisi bu yüzden "içerik gerçekten var mı" sorusunu
     * buradan sorar.
     *
     * TASLAK/ARŞİV SAYFALAR SAYILMAZ: yalnızca `status = active`.
     */
    public function hasActivePage(string $slug): bool
    {
        $slug = trim($slug, '/');
        if ($slug === '') {
            return false;
        }

        try {
            $model = $this->model('project.page');
            if (!empty($this->projectKey)) {
                $model = $model->withProjectScope((string) $this->projectKey);
            }

            return $model->where('status', 'active')->where('slug', $slug)->count() > 0;
        } catch (\Throwable $e) {
            error_log('[RBN-CRAWLER] hasActivePage: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Physical Data: Are there any ACTIVE FAQ entries? ❓🔍
     *
     * [FW-094-CRAWLER / Ö-3] `faqs()` denetleyicisi yalnızca SSS satırı VEYA
     * `sik-sorulan-sorular` sayfası varsa içerik render eder; ikisi de yoksa
     * yönlendirir. İki kaynaktan biri boşsa sayfa içeriksizdir.
     */
    public function hasActiveFaqs(): bool
    {
        try {
            $model = $this->model('project.faq');
            if (!empty($this->projectKey)) {
                $model = $model->withProjectScope((string) $this->projectKey);
            }

            return $model->where('is_active', 1)->count() > 0;
        } catch (\Throwable $e) {
            error_log('[RBN-CRAWLER] hasActiveFaqs: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Physical Data: Count active legal pages 📊
     */
    public function getActivePagesCount(): int
    {
        try {
            // [FW-094-CRAWLER / Ö-1] Anahtar `model('page')` DEĞİL `project.page`
            // (bkz. `getActivePages()` ve `SystemPhysicalMapTrait.php:55`).
            $model = $this->model('project.page');
            if (!empty($this->projectKey)) {
                $model = $model->withProjectScope((string) $this->projectKey);
            }
            return $model->where('status', 'active')->count();
        } catch (\Throwable $e) {
            error_log('[RBN-CRAWLER] getActivePagesCount: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * [FW-094-NOINDEX] Site düzeyi noindex: robots/sitemap/llms/feed tek cevap 🛡️
     */
    public function isSiteNoindex(): bool
    {
        $seo = $this->resolver('seo');
        return $seo ? $seo->isSiteNoindex() : false;
    }

    /**
     * [FW-094-NOINDEX] Sayfa/kayıt düzeyi noindex: yalnız o kayıt çıktıdan düşer 🛡️
     */
    public function isPageNoindex(array|object $entry): bool
    {
        $seo = $this->resolver('seo');
        return $seo ? $seo->isPageNoindex($entry) : false;
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
     * 🛰️ RBN Framework: [RBN Framework PROJECT SSOT] ⚓
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
     * RBN Framework: Generates valid XML feed using FeedBuilder.
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
     * RBN Framework: Generates valid LLMs markdown using LlmsBuilder.
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
