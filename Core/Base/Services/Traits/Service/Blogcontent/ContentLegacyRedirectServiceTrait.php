<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Base\Services\Traits\Service\Blogcontent;

/**
 * ContentLegacyRedirectServiceTrait - Autonomous Content Migration & Redirect Engine 🧭⚡
 * 
 * RBN 3.5 Masterpiece Standard.
 * Pure service-trait for resolving legacy URL migrations using sibling traits (ContentDataServiceTrait, ContentServiceTrait)
 * with ZERO hardcoded service, repository, or provider names.
 */
trait ContentLegacyRedirectServiceTrait
{
    /**
     * Eski WordPress post slug yönlendirmesini otonom çözer ve hedef URL üretir 🧭
     */
    public function resolveLegacyPostRedirect(string $slug, ?string $projectKey = null, string $prefix = '/blog'): string
    {
        $projectKey = $this->resolveCurrentProjectKey($projectKey);
        
        // 1. Çift UTF-8 / ISO Türkçe Slug Temizliği 🧼
        $cleanSlug = $this->sanitizeLegacySlug($slug);

        $post = null;
        if (method_exists($this, 'getPostBySlug')) {
            $post = $this->getPostBySlug($cleanSlug, $projectKey);
        }
        if (!$post && method_exists($this, 'getPostBySimilarSlug')) {
            $post = $this->getPostBySimilarSlug($cleanSlug, $projectKey);
        }

        // Strategy C: Bağlaç / Stop-words Temizleme Denemesi 🔍
        if (!$post && method_exists($this, 'helper')) {
            $seoHelper = $this->helper('meta.seo');
            if ($seoHelper && method_exists($seoHelper, 'seoSlug')) {
                $cleanedSlug = $seoHelper->seoSlug(str_replace('-', ' ', $cleanSlug));
                if ($cleanedSlug !== $cleanSlug) {
                    if (method_exists($this, 'getPostBySlug')) {
                        $post = $this->getPostBySlug($cleanedSlug, $projectKey);
                    }
                    if (!$post && method_exists($this, 'getPostBySimilarSlug')) {
                        $post = $this->getPostBySimilarSlug($cleanedSlug, $projectKey);
                    }
                }
            }
        }

        if ($post && !empty($post['slug'])) {
            $catSlug = null;
            if (!empty($post['category_id']) && method_exists($this, 'getCategory')) {
                $cat = $this->getCategory((int) $post['category_id'], $projectKey);
                if ($cat && !empty($cat['slug'])) {
                    $catSlug = $cat['slug'];
                }
            } elseif (!empty($post['category_slug'])) {
                $catSlug = $post['category_slug'];
            }

            if ($catSlug) {
                return rtrim($prefix, '/') . '/' . $catSlug . '/' . $post['slug'];
            }
            return rtrim($prefix, '/') . '/' . $post['slug'];
        }

        return rtrim($prefix, '/');
    }

    /**
     * Eski WordPress kategori slug yönlendirmesini otonom çözer 📁
     */
    public function resolveLegacyCategoryRedirect(string $categorySlug, ?string $projectKey = null, string $prefix = '/blog'): string
    {
        $projectKey = $this->resolveCurrentProjectKey($projectKey);
        
        $cleanCatSlug = trim($categorySlug, '/');
        $parts = explode('/', $cleanCatSlug);
        $cleanCatSlug = $this->sanitizeLegacySlug($parts[0] ?? $cleanCatSlug);

        if (method_exists($this, 'getCategory')) {
            $cat = $this->getCategory($cleanCatSlug, $projectKey);
            if ($cat && !empty($cat['slug'])) {
                return rtrim($prefix, '/') . '/' . $cat['slug'];
            }
        }

        return rtrim($prefix, '/') . '/' . $cleanCatSlug;
    }

    /**
     * Eski WordPress etiket yönlendirmesini ana sayfaya yönlendirir 🏷️
     */
    public function resolveLegacyTagRedirect(string $tagSlug, string $prefix = '/blog'): string
    {
        return rtrim($prefix, '/');
    }

    /**
     * Türkçe ve UTF-8 karakter desteğiyle slug temizleme 🧼
     */
    protected function sanitizeLegacySlug(string $slug): string
    {
        $decoded = urldecode($slug);
        if (preg_match('/[\xc3\x84-\xc3\xbc]/', $decoded)) {
            $iso = @mb_convert_encoding($decoded, 'ISO-8859-1', 'UTF-8');
            if ($iso !== false && $iso !== '') {
                $decoded = $iso;
            }
        }

        if (method_exists($this, 'helper')) {
            $textHelper = $this->helper('text');
            if ($textHelper && method_exists($textHelper, 'turkishSlug')) {
                return $textHelper->turkishSlug($decoded);
            }
        }

        return strtolower(trim(preg_replace('/[^a-zA-Z0-9-]+/', '-', $decoded), '-'));
    }
}
