<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnPipeline\Concerns;

/**
 * PipelineSanitizerTrait - Universal Content Sanitization & Normalization Concern 🧼✨⚓
 * 
 * RBN Framework Standard.
 * Handles AI response normalization, FAQ sanitization, HTML cleanup, and existing link formatting for AI context.
 */
trait PipelineSanitizerTrait
{
    /**
     * AI Yanıt Yapısını Düzleştirir (Normalize) 🧼
     */
    public function normalizeAiResponse(array $aiResult): array
    {
        $rawRes = $aiResult['response'] ?? ($aiResult['data'] ?? []);
        if (is_string($rawRes)) {
            $decoded = json_decode($rawRes, true);
            $res = is_array($decoded) ? $decoded : ['content' => $rawRes];
        } else {
            $res = is_array($rawRes) ? $rawRes : [];
        }

        if (isset($res['article']) && is_array($res['article'])) {
            $res = array_merge($res, $res['article']);
            unset($res['article']);
        }
        if (isset($res['blog']) && is_array($res['blog'])) {
            $res = array_merge($res, $res['blog']);
            unset($res['blog']);
        }
        if (isset($res['news']) && is_array($res['news'])) {
            $res = array_merge($res, $res['news']);
            unset($res['news']);
        }
        if (isset($res['trends']) && is_array($res['trends'])) {
            $res = array_merge($res, $res['trends']);
            unset($res['trends']);
        }
        if (isset($res['trend']) && is_array($res['trend'])) {
            $res = array_merge($res, $res['trend']);
            unset($res['trend']);
        }

        return $res;
    }

    /**
     * SSS (FAQ) Verilerini Temizler ve Standart Dizileştirir ❓
     */
    public function formatFaqs($faqsRaw): ?array
    {
        if (empty($faqsRaw)) {
            return null;
        }

        if (is_string($faqsRaw)) {
            $decoded = json_decode($faqsRaw, true);
            if (is_array($decoded)) {
                $faqsRaw = $decoded;
            }
        }

        if (!is_array($faqsRaw)) {
            return null;
        }

        $cleanFaqs = [];
        foreach ($faqsRaw as $faq) {
            if (is_array($faq)) {
                $q = trim(strip_tags($faq['question'] ?? ($faq['q'] ?? '')));
                $a = trim(strip_tags($faq['answer'] ?? ($faq['a'] ?? '')));
                if ($q !== '' && $a !== '') {
                    $cleanFaqs[] = ['question' => $q, 'answer' => $a];
                }
            }
        }

        return !empty($cleanFaqs) ? $cleanFaqs : null;
    }

    /**
     * SEO İç Linkleme için Mevcut Makalelerin Bağlantılarını Yapay Zeka Bağlamı (AI Context) için Derler 🔗
     */
    public function formatExistingLinksForAi(array $rawPosts, array $categoryMap = [], string $type = 'blog'): array
    {
        $links = [];
        $typePrefix = strtolower($type) === 'news' ? 'haber' : 'blog';

        foreach ($rawPosts as $post) {
            $pArray = is_array($post) ? $post : (method_exists($post, 'toArray') ? $post->toArray() : (array) $post);
            $pTitle = trim((string) ($pArray['title'] ?? ''));
            $pSlug = trim((string) ($pArray['slug'] ?? ''));
            $catId = (int) ($pArray['category_id'] ?? 0);
            $catSlug = $categoryMap[$catId] ?? '';

            if (!empty($pTitle) && !empty($pSlug)) {
                $urlPath = (!empty($catSlug) && $catSlug !== 'none' && $catSlug !== 'genel') ? "{$typePrefix}/{$catSlug}/{$pSlug}" : "{$typePrefix}/{$pSlug}";
                $links[] = [
                    'title' => $pTitle,
                    'url' => $urlPath,
                    'keywords' => array_filter(array_map('trim', explode(',', (string) ($pArray['seo_keywords'] ?? ''))))
                ];
            }
        }

        return array_slice($links, 0, 15);
    }
}
