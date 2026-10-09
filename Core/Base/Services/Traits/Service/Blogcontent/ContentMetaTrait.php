<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Base\Services\Traits\Service\Blogcontent;

/**
 * ContentMetaTrait - Tüm RBN projelerinde blog, haber ve içerik nesnelerini otonom zenginleştirici temel Trait 📝📅⏱️
 * RBN Framework Standard.
 */
trait ContentMetaTrait
{
    /**
     * Yazı nesnesini veya dizisini okuma süresi, Türkçe tarih, saat, geçen göreli zaman ve paylaşım linkleri ile zenginleştirir.
     * 
     * @param array|object $post Yazı verisi
     * @param array $contentFields Okuma süresi hesaplanırken birleştirilecek metin alanları
     * @param string|null $customUrl Opsiyonel özel sayfa URL'si
     * @return array|object Zenginleştirilmiş yazı verisi
     */
    protected function enrichPost($post, array $contentFields = ['content', 'author_comment'], ?string $customUrl = null)
    {
        if (empty($post)) {
            return $post;
        }

        $isObj = is_object($post);
        $createdAt = $isObj ? ($post->created_at ?? $post['created_at'] ?? '') : ($post['created_at'] ?? '');
        $updatedAt = $isObj ? ($post->updated_at ?? $post['updated_at'] ?? '') : ($post['updated_at'] ?? '');

        // Belirtilen tüm içerik alanlarındaki metinleri birleştir
        $combinedText = '';
        foreach ($contentFields as $field) {
            $val = $isObj 
                ? ($post->{$field} ?? $post[$field] ?? '') 
                : ($post[$field] ?? '');
            if (!empty($val) && is_string($val)) {
                $combinedText .= ' ' . $val;
            }
        }

        // Framework'ün yerleşik FormatHelper kütüphanesini kullan
        $format = $this->helper('format');
        
        $readTime = $format ? $format->estimatedReadTime($combinedText) : 1;
        $pubDate = $format ? $format->formatDateTurkish($createdAt, 'medium') : $createdAt;
        $pubTime = $format ? $format->formatDateTurkish($createdAt, 'time') : '';
        $pubRelative = $format ? $format->getRelativeTime($createdAt) : '';

        $updDate = $format ? $format->formatDateTurkish($updatedAt, 'medium') : $updatedAt;
        $updTime = $format ? $format->formatDateTurkish($updatedAt, 'time') : '';
        $updRelative = $format ? $format->getRelativeTime($updatedAt) : '';

        // Değerleri nesne veya diziye yaz
        if ($isObj) {
            $post->read_time = $readTime;
            $post->published_date = $pubDate;
            $post->published_time = $pubTime;
            $post->published_relative = $pubRelative;
            $post->updated_date = $updDate;
            $post->updated_time = $updTime;
            $post->updated_relative = $updRelative;
        } else {
            $post['read_time'] = $readTime;
            $post['published_date'] = $pubDate;
            $post['published_time'] = $pubTime;
            $post['published_relative'] = $pubRelative;
            $post['updated_date'] = $updDate;
            $post['updated_time'] = $updTime;
            $post['updated_relative'] = $updRelative;
        }

        // Başlığı iki nokta üst üste (:) veya soru işareti (?) işaretine göre böl
        $title = $isObj ? ($post->custom_title ?? $post->title ?? $post['custom_title'] ?? $post['title'] ?? '') : ($post['custom_title'] ?? $post['title'] ?? '');
        $mainTitle = $title;
        $subTitle = '';

        if (str_contains($title, ':')) {
            $titleParts = explode(':', $title, 2);
            $mainTitle = trim($titleParts[0]);
            $subTitle = trim($titleParts[1]);
        } elseif (str_contains($title, '?')) {
            $titleParts = explode('?', $title, 2);
            $mainTitle = trim($titleParts[0]) . '?';
            $subTitle = trim($titleParts[1]);
        }

        if ($isObj) {
            $post->main_title = $mainTitle;
            $post->sub_title = $subTitle;
        } else {
            $post['main_title'] = $mainTitle;
            $post['sub_title'] = $subTitle;
        }

        // Anahtar kelimeleri (tags) temizleyip diziye dönüştür
        $seoKeywords = $isObj ? ($post->seo_keywords ?? $post['seo_keywords'] ?? '') : ($post['seo_keywords'] ?? '');
        $tags = [];
        if (!empty($seoKeywords)) {
            $tags = array_values(array_filter(array_map('trim', explode(',', $seoKeywords))));
        }

        if ($isObj) {
            $post->tags = $tags;
        } else {
            $post['tags'] = $tags;
        }

        // İçeriğin başındaki mükerrer başlık etiketini (başlık ile aynıysa) temizle
        $title = $isObj ? ($post->custom_title ?? $post->title ?? $post['custom_title'] ?? $post['title'] ?? '') : ($post['custom_title'] ?? $post['title'] ?? '');
        $content = $isObj ? ($post->content ?? $post['content'] ?? '') : ($post['content'] ?? '');
        if (!empty($title) && !empty($content)) {
            $cleanTitle = trim($title);
            $pattern = '/^<h[1-3][^>]*>\s*' . preg_quote($cleanTitle, '/') . '\s*<\/h[1-3]>/is';
            $content = preg_replace($pattern, '', trim($content));
        }

        // İçindekiler (Table of Contents) tespiti ve id enjeksiyonu
        $headings = [];
        if (!empty($content)) {
            // 1. Markdown ## ve ### başlıklarını HTML h2/h3 etiketlerine dönüştür (eğer düz metin/markdown geldiyse)
            if (str_contains($content, '##')) {
                $content = preg_replace('/^(?:<p>)?\s*###\s*(.*?)(?:<\/p>)?$/mu', '<h3>$1</h3>', $content);
                $content = preg_replace('/^(?:<p>)?\s*##\s*(.*?)(?:<\/p>)?$/mu', '<h2>$1</h2>', $content);
            }

            // 2. İki nokta (:) içeren H2 başlıklarını otomatik h2-tag ve h2-subtitle parçalarına ayır
            $content = preg_replace_callback('/<h2([^>]*)>(.*?)<\/h2>/is', function($matches) {
                $attr = $matches[1];
                $text = $matches[2];
                if (str_contains($text, ':') && !str_contains($text, 'class="h2-tag"')) {
                    $parts = explode(':', $text, 2);
                    $tag = trim($parts[0]);
                    $sub = trim($parts[1]);
                    return "<h2{$attr}><span class=\"h2-tag\">{$tag}:</span> <span class=\"h2-subtitle\">{$sub}</span></h2>";
                }
                return "<h2{$attr}>{$text}</h2>";
            }, $content);

            preg_match_all('/<h([2-3])([^>]*)>(.*?)<\/h\1>/is', $content, $matches);
            if (!empty($matches[0])) {
                foreach ($matches[3] as $idx => $hTitle) {
                    $cleanHTitle = strip_tags($hTitle);
                    $anchor = 'heading-' . $idx;
                    $headings[] = [
                        'level' => (int) $matches[1][$idx],
                        'title' => $cleanHTitle,
                        'anchor' => $anchor
                    ];
                    // İçerikteki H2 ve H3 tag'lerine id attribute'u ekleyelim
                    $originalHeader = $matches[0][$idx];
                    $modifiedHeader = preg_replace('/<h([2-3])/is', '<h$1 id="' . $anchor . '"', $originalHeader, 1);
                    $content = str_replace($originalHeader, $modifiedHeader, $content);
                }
            }
        }

        if ($isObj) {
            $post->content = $content;
            $post->headings = $headings;
        } else {
            $post['content'] = $content;
            $post['headings'] = $headings;
        }

        // 🎼 RBN Framework: Clean Markdown & Auto-wrap plaintext character names in <strong> tags 🪐🛡️
        $markdownFields = ['content', 'author_comment', 'our_review', 'senaryo_analysis', 'character_analysis', 'custom_summary', 'summary'];
        foreach ($markdownFields as $field) {
            $val = $isObj ? ($post->{$field} ?? null) : ($post[$field] ?? null);
            if (is_string($val) && !empty($val)) {
                $converted = $val;
                // 1. Eğer eski tip yıldız (**) kaldıysa strong'a çevir
                if (strpos($converted, '**') !== false) {
                    $converted = preg_replace('/\*\*(.*?)\*\*/s', '<strong>$1</strong>', $converted);
                }
                // 2. Paragraf/satır başında "İsim (Oyuncu):" veya "İsim:" şeklinde düz metin varsa ve strong içinde değilse otomatik strong yap
                $converted = preg_replace(
                    '/(^(?:<p>)?\s*)(?!<strong\b)([A-ZÇĞİÖŞÜ][a-zA-ZçÇğĞıİöÖşŞüÜ\s.-]{2,40}(?:\s*\([^)]+\))?\s*:)/mu',
                    '$1<strong>$2</strong>',
                    $converted
                );
                if ($isObj) {
                    $post->{$field} = $converted;
                } else {
                    $post[$field] = $converted;
                }
            }
        }

        // 🔗 Paylaşım bağlantılarını dinamik ve otonom derle
        $slug = $isObj ? ($post->slug ?? '') : ($post['slug'] ?? '');
        $postTitle = $isObj ? ($post->custom_title ?? $post->title ?? '') : ($post['custom_title'] ?? $post['title'] ?? '');

        $postUrl = $customUrl;
        if (empty($postUrl)) {
            $postUrl = $isObj 
                ? ($post->url ?? $post->permalink ?? $post['url'] ?? $post['permalink'] ?? '') 
                : ($post['url'] ?? $post['permalink'] ?? '');
        }

        if (empty($postUrl) && !empty($slug)) {
            $postUrl = url($slug);
        } elseif (!empty($postUrl) && !str_starts_with($postUrl, 'http://') && !str_starts_with($postUrl, 'https://')) {
            $postUrl = url(ltrim($postUrl, '/'));
        }

        $shareLinks = [];
        if (!empty($postUrl) && method_exists($this, 'service')) {
            try {
                $baseProjectService = $this->service('base.project');
                if ($baseProjectService && method_exists($baseProjectService, 'getShareLinks')) {
                    $shareLinks = $baseProjectService->getShareLinks($postUrl, (string)$postTitle);
                }
            } catch (\Throwable $e) {
                $shareLinks = [];
            }
        }

        if ($isObj) {
            $post->share_links = $shareLinks;
            $post->post_url = $postUrl;
        } else {
            $post['share_links'] = $shareLinks;
            $post['post_url'] = $postUrl;
        }

        return $post;
    }
}
