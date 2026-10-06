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

    /**
     * [FW-094-BULGU-DUZELT / G-2] İçerik kaynaklı metni TEK SATIRA indirir.
     * Satır sonları (CR/LF/NEL/U+2028/U+2029) boşluğa çevrilir, köşeli
     * parantez bağlantı sözdizimini bozamasın diye `(` `)` yapılır, satır
     * başındaki başlık/alıntı işaretleri atılır. llms.txt makine tüketicisi
     * içindir; içerik yazarının sahte bölüm/bağlantı enjekte etmesi engellenir.
     */
    public static function sanitizeText(string $text): string
    {
        $text = preg_replace('/[\p{Cc}\x{0085}\x{2028}\x{2029}\s]+/u', ' ', $text) ?? '';
        $text = strtr($text, ['[' => '(', ']' => ')']);

        return trim(ltrim(trim($text), "#> \t"));
    }

    /**
     * [FW-094-BULGU-DUZELT / G-2] Güvenli bir markdown bağlantı satırı üretir:
     * `- [başlık](adres)`. Adresteki boşluk/denetim karakteri atılır, `(` `)`
     * yüzde-kodlanır; başlık `sanitizeText()` ile arındırılır.
     */
    public static function link(string $title, string $url): string
    {
        $url = preg_replace('/[\p{Cc}\x{0085}\x{2028}\x{2029}\s<>]+/u', '', $url) ?? '';
        $url = strtr($url, ['(' => '%28', ')' => '%29']);

        return '- [' . self::sanitizeText($title) . '](' . $url . ')';
    }

    public function setSiteInfo(string $siteName, string $siteUrl): self
    {
        $siteName = self::sanitizeText($siteName);
        $this->siteName = $siteName;
        $this->siteUrl = $siteUrl;
        return $this;
    }

    public function addSection(string $title, array $items): self
    {
        if (!empty($items)) {
            // Tek savunma hattı: hangi çağıran olursa olsun bölüm başlığı ve her
            // madde TEK satır kalır (satır sonu ile sahte başlık/madde eklenemez).
            $this->sections[self::sanitizeText($title)] = array_map(
                static fn ($item): string => trim((string) preg_replace('/[\r\n\x{0085}\x{2028}\x{2029}]+/u', ' ', (string) $item)),
                $items
            );
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
