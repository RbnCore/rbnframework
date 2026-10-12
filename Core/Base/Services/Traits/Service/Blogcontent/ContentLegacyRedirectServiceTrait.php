<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Base\Services\Traits\Service\Blogcontent;

/**
 * ContentLegacyRedirectServiceTrait - Autonomous Content Migration & Redirect Engine 🧭⚡
 * 
 * RBN Framework Standard.
 * Pure service-trait for resolving legacy URL migrations using sibling traits (ContentDataServiceTrait, ContentServiceTrait)
 * with ZERO hardcoded service, repository, or provider names.
 */
trait ContentLegacyRedirectServiceTrait
{
    /**
     * Eski WordPress post slug yönlendirmesini otonom çözer ve hedef URL üretir 🧭
     *
     * ÖNCE `app_content_redirects` kaydına bakılır (slug/kategori değişiminde otomatik yazılan eski adres:
     * kesin eşleşme → yazının GÜNCEL adresi); kayıt yoksa eski benzer-slug yolu aynen sürer.
     *
     * @param string|null $categorySlug eski adresin kategori parçası (kategorisiz adreste null/boş)
     */
    public function resolveLegacyPostRedirect(string $slug, ?string $projectKey = null, string $prefix = '/blog', ?string $categorySlug = null): string
    {
        $projectKey = $this->resolveCurrentProjectKey($projectKey);

        $recorded = $this->resolveRecordedRedirect($slug, (string) $categorySlug, $projectKey, $prefix);
        if ($recorded !== null) {
            return $recorded;
        }

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
            return $this->composeContentAddress($post, $projectKey, $prefix);
        }

        return rtrim($prefix, '/');
    }

    /**
     * Kayıtlı eski adres (app_content_redirects) → yazının güncel adresi; kayıt/yazı yoksa null 🧭
     */
    protected function resolveRecordedRedirect(string $slug, string $categorySlug, ?string $projectKey, string $prefix): ?string
    {
        $repo = $this->resolveTargetRepository();
        if (!method_exists($this, 'repository') || !$repo || !method_exists($repo, 'find')) {
            return null;
        }
        try {
            $postId = $this->repository('app.contentRedirect')->findPostId(
                $this->legacyContentType(),
                trim(urldecode($categorySlug), '/'),
                trim(urldecode($slug), '/')
            );
            $post = $postId ? $repo->find($postId) : null;
        } catch (\Throwable $e) {
            error_log('[content-redirect] okuma başarısız: ' . $e->getMessage());
            return null;
        }
        if (!$post || empty($post['slug']) || (isset($post['is_active']) && !(int) $post['is_active'])) {
            return null;
        }
        return $this->composeContentAddress($post, $projectKey, $prefix);
    }

    /**
     * Yazının güncel adresi: {ön ek}/{kategori}/{slug} (kategorisizse {ön ek}/{slug}) 🔗
     */
    protected function composeContentAddress(array $post, ?string $projectKey, string $prefix): string
    {
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

    /**
     * Yazının bugünkü adres parçaları (slug + kategori slug'ı); kaydetmeden ÖNCE ve SONRA alınıp karşılaştırılır 📸
     *
     * @return array{slug:string, category_slug:string}|null yazı yoksa null
     */
    protected function snapshotContentAddress(int $postId): ?array
    {
        $repo = $this->resolveTargetRepository();
        $post = $repo && method_exists($repo, 'find') ? $repo->find($postId) : null;
        if (!$post || empty($post['slug'])) {
            return null;
        }
        $category = !empty($post['category_id']) && method_exists($this, 'getCategory')
            ? $this->getCategory((int) $post['category_id'], null)
            : null;
        return ['slug' => (string) $post['slug'], 'category_slug' => (string) ($category['slug'] ?? '')];
    }

    /**
     * Yazının slug'ı ya da kategorisi kayıt sonrası değiştiyse ESKİ adresi 301 tablosuna yazar 🧭
     * (İçerik kaydının tek noktası: panel düzenlemesi de fabrika yenilemesi de `savePost()` üzerinden buraya gelir.)
     * Kayıt hatası içerik kaydını ASLA bozmaz.
     *
     * @param array{slug:string, category_slug:string}|null $before kaydetmeden önceki adres
     */
    protected function recordContentAddressChange(?array $before, int $postId): void
    {
        if ($before === null || !method_exists($this, 'repository')) {
            return;
        }
        try {
            $after = $this->snapshotContentAddress($postId);
            if ($after === null || $after === $before) {
                return;
            }
            $redirects = $this->repository('app.contentRedirect');
            $type = $this->legacyContentType();
            $redirects->record($type, $postId, $before['slug'], $before['category_slug']);
            // Yazı bir zamanlar bırakılmış adrese geri döndüyse o adres artık eski değildir
            $redirects->forget($type, $after['category_slug'], $after['slug']);
        } catch (\Throwable $e) {
            error_log('[content-redirect] kayıt başarısız post=' . $postId . ': ' . $e->getMessage());
        }
    }

    /** Servisin içerik türü: haber servisi `news`, diğerleri `blog` 🏷️ */
    protected function legacyContentType(): string
    {
        return property_exists($this, 'categoryType') && $this->categoryType === 'news' ? 'news' : 'blog';
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
