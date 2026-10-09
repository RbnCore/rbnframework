<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Render\Builders;

use Rbn\Framework\Core\Base\BaseComponent;

/**
 * SitemapBuilder - Stateful accumulator and XML generator for Sitemaps 🗺️🛰️⚓
 * Part of RBN Framework.
 */
class SitemapBuilder extends BaseComponent
{
    /** @var array Accumulated sitemap entries */
    protected array $entries = [];

    /**
     * Reset the builder state.
     */
    public function reset(): self
    {
        $this->entries = [];
        return $this;
    }

    /**
     * Add a single URL entry to the sitemap.
     */
    public function addUrl(string $loc, string $priority = '0.5', string $changefreq = 'monthly', ?string $lastmod = null): self
    {
        if ($lastmod) {
            $timestamp = strtotime($lastmod);
            $lastmod = ($timestamp !== false) ? date('c', $timestamp) : date('c');
        } else {
            $lastmod = date('c');
        }

        $this->entries[] = [
            'loc' => $loc,
            'lastmod' => $lastmod,
            'changefreq' => $changefreq,
            'priority' => $priority
        ];

        return $this;
    }

    /**
     * Add multiple entries at once.
     */
    public function addUrls(array $urls): self
    {
        foreach ($urls as $url) {
            $this->addUrl(
                $url['loc'] ?? $url['path'] ?? '',
                $url['priority'] ?? '0.5',
                $url['changefreq'] ?? 'monthly',
                $url['lastmod'] ?? $url['updated_at'] ?? null
            );
        }
        return $this;
    }

    /**
     * Get all accumulated entries.
     */
    public function getEntries(): array
    {
        return $this->entries;
    }

    /**
     * Check if the builder contains any entries.
     */
    public function hasEntries(): bool
    {
        return !empty($this->entries);
    }

    /**
     * Generates a sitemap index or urlset XML payload.
     */
    public function buildXml(bool $isIndex = false): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xml .= '<?xml-stylesheet type="text/xsl" href="/sitemap.xsl"?>' . PHP_EOL;
        $xml .= '<!-- RBN CRAWLER HUB: SITEMAP ACTIVE -->' . PHP_EOL;

        if ($isIndex) {
            $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;
            foreach ($this->entries as $entry) {
                $xml .= '    <sitemap>' . PHP_EOL;
                $xml .= '        <loc>' . htmlspecialchars((string) $entry['loc']) . '</loc>' . PHP_EOL;
                $xml .= '        <lastmod>' . ($entry['lastmod'] ?? now('Y-m-d')) . '</lastmod>' . PHP_EOL;
                $xml .= '    </sitemap>' . PHP_EOL;
            }
            $xml .= '</sitemapindex>';
        } else {
            $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;
            foreach ($this->entries as $entry) {
                $xml .= '    <url>' . PHP_EOL;
                $xml .= '        <loc>' . htmlspecialchars((string) $entry['loc']) . '</loc>' . PHP_EOL;
                $xml .= '        <lastmod>' . ($entry['lastmod'] ?? now('Y-m-d')) . '</lastmod>' . PHP_EOL;
                $xml .= '        <changefreq>' . ($entry['changefreq'] ?? 'weekly') . '</changefreq>' . PHP_EOL;
                $xml .= '        <priority>' . ($entry['priority'] ?? '0.8') . '</priority>' . PHP_EOL;
                $xml .= '    </url>' . PHP_EOL;
            }
            $xml .= '</urlset>';
        }

        return $xml;
    }
}
