<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Render\Builders;

use Rbn\Framework\Core\Base\BaseComponent;

/**
 * LlmsBuilder - Stateful accumulator and Markdown generator for LLMs.txt 🤖📄⚓
 * Part of RBN 3.5 Sovereign Framework Standards.
 */
class LlmsBuilder extends BaseComponent
{
    protected string $siteName = '';
    protected string $siteUrl = '';
    protected array $sections = [];

    public function reset(): self
    {
        $this->siteName = '';
        $this->siteUrl = '';
        $this->sections = [];
        return $this;
    }

    public function setSiteInfo(string $siteName, string $siteUrl): self
    {
        $this->siteName = $siteName;
        $this->siteUrl = $siteUrl;
        return $this;
    }

    public function addSection(string $title, array $items): self
    {
        if (!empty($items)) {
            $this->sections[$title] = $items;
        }
        return $this;
    }

    public function buildMarkdown(): string
    {
        $content = "# " . ($this->siteName ?: 'Website') . "\n\n";
        $content .= "> " . ($this->siteName ?: 'Bu platform') . " resmi içerik haritası, editoryal rehberleri ve öne çıkan sayfaları aşağıda AI ajanları için listelenmiştir.\n\n";

        foreach ($this->sections as $sectionTitle => $items) {
            $content .= "## {$sectionTitle}\n";
            foreach ($items as $item) {
                $content .= "{$item}\n";
            }
            $content .= "\n";
        }

        $content .= "## Yapay Zeka Ajanları & Crawler Notları\n";
        $content .= "- Tüm güncel içerik indekslerine ve XML haritalarına [sitemap.xml](" . rtrim($this->siteUrl, '/') . "/sitemap.xml) adresinden ulaşabilirsiniz.\n";

        return $content;
    }
}
