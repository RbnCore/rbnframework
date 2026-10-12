<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnFactory\Handlers;

use Rbn\Framework\Core\Base\BaseComponent;
use Rbn\Framework\Packages\RbnFactory\Models\FactoryContract;

/** Salt-okunur aday yazıları seçer ve yayına geçişte iç bağlantıları doğrular 🔗 */
class FactoryInternalLinkHandler extends BaseComponent
{
    /**
     * Konuya yakın aktif yazıları, sözleşme alan adlarıyla döndürür.
     * @return array<int,array<string,mixed>>
     */
    public function candidates(string $contentType, string $topic, int $categoryId, string $draftSlug): array
    {
        $config = $this->handler('factoryConfig');
        if (!$config->get('link_candidates.enabled', true)) {
            return [];
        }

        $limit = max(1, min(15, (int) $config->get('link_candidates.limit', 12)));
        $urls = $this->handler('factoryPostUrl');
        $topicWords = $this->words($topic);
        $ranked = [];
        foreach ($this->repository('factory.post')->activeForLinks($contentType, $draftSlug) as $row) {
            $sameCategory = (int) ($row['category_id'] ?? 0) === $categoryId;
            $overlap = count(array_intersect($topicWords, $this->words(
                (string) ($row['title'] ?? '') . ' ' . (string) ($row['seo_keywords'] ?? '') . ' ' . (string) ($row['slug'] ?? '')
            )));
            if (!$sameCategory && $overlap === 0) {
                continue;
            }
            $ranked[] = [
                'score' => ($sameCategory ? 1000 : 0) + $overlap,
                'item' => [
                    FactoryContract::CANDIDATE['title']    => (string) $row['title'],
                    FactoryContract::CANDIDATE['url']      => $urls->path($contentType, (string) $row['slug'], (int) ($row['category_id'] ?? 0)),
                    FactoryContract::CANDIDATE['summary']  => $this->truncate(trim(html_entity_decode(strip_tags((string) ($row['summary'] ?? '')), ENT_QUOTES | ENT_HTML5, 'UTF-8')), 160),
                    FactoryContract::CANDIDATE['category'] => (int) ($row['category_id'] ?? 0),
                ],
            ];
        }
        $titleKey = FactoryContract::CANDIDATE['title'];
        usort($ranked, static fn(array $a, array $b): int => $b['score'] <=> $a['score'] ?: strcmp($a['item'][$titleKey], $b['item'][$titleKey]));
        return array_column(array_slice($ranked, 0, $limit), 'item');
    }

    /** @return string[] İç bağlantı sorunları; boş liste yayına hazır demektir. */
    public function brokenTargets(string $html): array
    {
        preg_match_all('/<a\b[^>]*\bhref\s*=\s*(["\'])(.*?)\1/is', $html, $matches);
        $config = $this->handler('factoryConfig');
        $urls = $this->handler('factoryPostUrl');
        $hosts = array_map('strtolower', (array) $config->get('site_hosts', []));
        $found = [];
        foreach ($matches[2] ?? [] as $href) {
            $href = html_entity_decode(trim((string) $href), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if ($href === '' || str_starts_with($href, '#') || preg_match('#^(?:mailto:|tel:|javascript:|data:)#i', $href)) {
                continue;
            }
            $parts = parse_url($href);
            if ($parts === false) {
                continue;
            }
            $host = strtolower((string) ($parts['host'] ?? ''));
            if ($host !== '' && !in_array($host, $hosts, true)) {
                continue;
            }
            $path = trim(rawurldecode((string) ($parts['path'] ?? '')), '/');
            if (in_array('..', explode('/', $path), true)) {
                $found[] = $href;
                continue;
            }
            $contentType = $this->typeForPath($path);
            if ($contentType === null) {
                continue;
            }
            $slug = basename($path);
            if ($slug === '' || str_contains($slug, '..')) {
                $found[] = $href;
                continue;
            }
            $post = $this->repository('factory.post')->findActiveBySlug($contentType, $slug);
            // Yazı yoksa ya da adres sitenin kanonik adresinden farklıysa (kategorisiz/yanlış kategori → 301) kırık sayılır.
            if (!$post || $path !== trim($urls->path($contentType, $slug, (int) ($post['category_id'] ?? 0)), '/')) {
                $found[] = $href;
            }
        }
        return array_values(array_unique($found));
    }

    /** Yolun ön eki hangi içerik türüne uyuyor (ön eki olmayan tür eşleşmez). */
    private function typeForPath(string $path): ?string
    {
        foreach (array_keys($this->handler('factoryConfig')->enabledTypes()) as $contentType) {
            $prefix = $this->handler('factoryPostUrl')->prefix((string) $contentType);
            if ($prefix !== '' && $path !== $prefix && str_starts_with($path, $prefix . '/')) {
                return (string) $contentType;
            }
        }
        return null;
    }

    /** @return string[] */
    private function words(string $text): array
    {
        $text = mb_strtolower($text, 'UTF-8');
        $text = strtr($text, ['ı' => 'i', 'ş' => 's', 'ğ' => 'g', 'ü' => 'u', 'ö' => 'o', 'ç' => 'c']);
        preg_match_all('/[a-z0-9]{3,}/', $text, $matches);
        return array_values(array_unique($matches[0] ?? []));
    }

    private function truncate(string $text, int $length): string
    {
        return mb_strlen($text, 'UTF-8') <= $length ? $text : mb_substr($text, 0, $length, 'UTF-8');
    }
}
