<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Base\Services\Traits\Service\Blogcontent;

/**
 * ContentDataServiceTrait - High-Performance Frontend Content Data Orchestrator 🖋️📊⚡
 * 
 * RBN Framework Standard.
 * Autonomous data preparation, mapping, pagination, share links, and detail packaging across all projects.
 */
trait ContentDataServiceTrait
{
    use ContentMetaTrait;
    /**
     * İçerik yazılarına kategori slug ve isimlerini ekler 🏷️
     */
    public function getMappedPosts(?string $projectKey = null, array $options = []): array
    {
        $repo = $this->resolveTargetRepository();
        if (!$repo) {
            return [];
        }

        $projectKey = $this->resolveCurrentProjectKey($projectKey);
        if (!empty($projectKey)) {
            $options['project_key'] = $projectKey;
        }

        $posts = method_exists($repo, 'getPosts') ? $repo->getPosts($options) : [];
        $activeCategories = method_exists($repo, 'categoriesWithCounts') ? $repo->categoriesWithCounts($projectKey) : [];

        $categoryMap = array_column($activeCategories, 'slug', 'id');
        $categoryNameMap = array_column($activeCategories, 'name', 'id');

        foreach ($posts as &$post) {
            $catId = $post['category_id'] ?? null;
            $post['category_slug'] = ($catId !== null && isset($categoryMap[$catId])) ? $categoryMap[$catId] : ($post['category_slug'] ?? 'genel');
            $post['category_name'] = ($catId !== null && isset($categoryNameMap[$catId])) ? $categoryNameMap[$catId] : ($post['category_name'] ?? 'Genel');

            if (method_exists($this, 'enrichPost')) {
                $post = $this->enrichPost($post);
            }
        }
        unset($post);

        return $posts;
    }

    /**
     * Kategori nesnesini Slug veya ID üzerinden otonom çözer 🔍
     */
    public function getCategory(int|string $idOrSlug, ?string $projectKey = null): ?array
    {
        $projectKey = $this->resolveCurrentProjectKey($projectKey);
        $repo = $this->resolveTargetRepository();

        if ($repo && method_exists($repo, 'categoriesWithCounts')) {
            $categories = $repo->categoriesWithCounts($projectKey);
            foreach ($categories as $cat) {
                $catArr = is_object($cat) ? (method_exists($cat, 'toArray') ? $cat->toArray() : (array) $cat) : (array) $cat;
                $catId = (int) ($catArr['id'] ?? (is_object($cat) ? $cat->id ?? 0 : 0));
                $catSlug = (string) ($catArr['slug'] ?? (is_object($cat) ? $cat->slug ?? '' : ''));

                if (is_int($idOrSlug) || ctype_digit((string) $idOrSlug)) {
                    if ($catId === (int) $idOrSlug) {
                        return $catArr;
                    }
                } else {
                    if ($catSlug === (string) $idOrSlug) {
                        return $catArr;
                    }
                }
            }
        }

        // Fallback: Model nesnesi üzerinden sorgula 🏺
        $catModelObj = $this->resolveTargetModel('app.contentCategory');
        if ($catModelObj && method_exists($catModelObj, 'query')) {
            // [FW-ALTYAPI-3 / H · G4] `ContentCategoryModel` artık `scoped = true`.
            // Açık anahtar -> `withProjectScope()`; yoksa modelin kapsamı.
            // Elle `where('project_key', ...)` ikinci süzgeçti, kaldırıldı.
            if (!empty($projectKey) && method_exists($catModelObj, 'withProjectScope')) {
                $catModelObj = $catModelObj->withProjectScope((string) $projectKey);
            }
            $query = $catModelObj->query()->where('is_active', 1);
            if (is_int($idOrSlug) || ctype_digit((string) $idOrSlug)) {
                $query->where('id', (int) $idOrSlug);
            } else {
                $query->where('slug', (string) $idOrSlug);
            }
            $res = $query->first();
            if ($res && is_object($res)) {
                return method_exists($res, 'toArray') ? $res->toArray() : (array) $res;
            }
            return is_array($res) ? $res : null;
        }

        return null;
    }

    /**
     * İçerik listeleme (ana sayfa) verilerini hazırlar 📝
     */
    public function getListingData(?string $projectKey = null, ?string $search = null): array
    {
        $projectKey = $this->resolveCurrentProjectKey($projectKey);
        $options = [
            'is_active' => 1
        ];
        if (!empty($search)) {
            $options['search'] = $search;
        }

        $posts = $this->getMappedPosts($projectKey, $options);
        $repo = $this->resolveTargetRepository();
        $categories = $repo && method_exists($repo, 'categoriesWithCounts') ? $repo->categoriesWithCounts($projectKey) : [];

        return [
            'posts' => $posts,
            'categories' => $categories,
        ];
    }

    /**
     * Ana sayfa için öne çıkanlar, popüler yazılar ve kategori gruplarını parametrik ve temiz sorumlulukla hazırlar 🏠📊
     */
    public function getHomeData(?string $projectKey = null, array $options = []): array
    {
        $projectKey = $this->resolveCurrentProjectKey($projectKey);

        $limit = $options['limit'] ?? 30;
        $featuredLimit = $options['featured_limit'] ?? 3;
        $popularLimit = $options['popular_limit'] ?? 5;
        $perCategory = $options['per_category'] ?? 3;

        // 1. Tüm haritalanmış verileri ana listeleme metodumuzdan tek hamlede çek ⚡
        $baseData = $this->getListingData($projectKey);
        $allPosts = $baseData['posts'] ?? [];
        $categories = $baseData['categories'] ?? [];

        // 2. Ana liste (Controller limidine göre) 📝
        $posts = array_slice($allPosts, 0, $limit);

        // 3. Öne çıkan yazılar (Hero) 🌟
        $featuredPosts = array_slice($allPosts, 0, $featuredLimit);
        $featuredIds = array_column($featuredPosts, 'id');

        // 4. Popüler yazılar (Views sayısına göre azalan sıralı) 🔥
        $popularPosts = $posts;
        usort($popularPosts, fn($a, $b) => ((int) ($b['views'] ?? 0)) <=> ((int) ($a['views'] ?? 0)));
        $popularPosts = array_slice($popularPosts, 0, $popularLimit);

        // 5. Kategori grupları (Hero'daki haberler elenerek her kategori için taze 3 haber ayrıştırılır) 📁
        $categoryPosts = [];
        if ($perCategory > 0 && !empty($categories)) {
            // Hero'daki yazıları havuzdan tamamen süz 🧼
            $availablePosts = array_filter($allPosts, fn($p) => !in_array($p['id'] ?? 0, $featuredIds, true));

            foreach ($categories as $val) {
                $catArr = is_object($val) ? (method_exists($val, 'toArray') ? $val->toArray() : (array) $val) : (array) $val;
                $catId = (int) ($catArr['id'] ?? 0);
                if (!$catId) {
                    continue;
                }

                $catObj = $catArr;
                if (!isset($catObj['total_posts']) && isset($catObj['count'])) {
                    $catObj['total_posts'] = (int) $catObj['count'];
                }

                // O kategoriye ait en güncel (Hero hariç) yazılar
                $catPosts = array_filter($availablePosts, fn($p) => (int) ($p['category_id'] ?? 0) === $catId);
                $sliced = array_slice(array_values($catPosts), 0, $perCategory);

                if (!empty($sliced)) {
                    $categoryPosts[] = [
                        'category' => $catObj,
                        'posts' => $sliced
                    ];
                }
            }

            // Kategorileri içerdikleri en güncel yayına göre sırala 🕒
            usort($categoryPosts, function ($a, $b) {
                $dateA = strtotime($a['posts'][0]['created_at'] ?? '1970-01-01');
                $dateB = strtotime($b['posts'][0]['created_at'] ?? '1970-01-01');
                return $dateB <=> $dateA;
            });
        }

        return [
            'posts' => $posts,
            'featuredPosts' => $featuredPosts,
            'popularPosts' => $popularPosts,
            'categoryPosts' => $categoryPosts,
            'categories' => $categories,
        ];
    }

    /**
     * Kategoriye özel içerik verilerini hazırlar (kategori yoksa otonom 301 yönlendirmesi dahil) 📁🧭
     */
    public function getCategoryListingData(string $categorySlug, ?string $projectKey = null): array
    {
        $projectKey = $this->resolveCurrentProjectKey($projectKey);

        // 1. Kategoriyi çöz
        $category = $this->getCategory($categorySlug, $projectKey);

        // 2. Kategori bulunamadıysa: Belki eski bir tekil yazı slug'ıdır, otonom yönlendir 🧭
        if (!$category) {
            $redirectUrl = method_exists($this, 'resolveLegacyPostRedirect')
                ? $this->resolveLegacyPostRedirect($categorySlug, $projectKey, '/blog')
                : '/blog';

            $this->perform301Redirect($redirectUrl);
        }

        $allPosts = $this->getMappedPosts($projectKey, ['is_active' => 1]);
        $repo = $this->resolveTargetRepository();
        $categories = $repo && method_exists($repo, 'categoriesWithCounts') ? $repo->categoriesWithCounts($projectKey) : [];

        $catId = (int) ($category['id'] ?? 0);
        $posts = array_values(array_filter($allPosts, fn($p) => (int) ($p['category_id'] ?? 0) === $catId));

        return [
            'posts' => $posts,
            'categories' => $categories,
            'activeCategory' => $category,
        ];
    }

    /**
     * Detay sayfası verilerini otonom çözer (slug doğrulama, otonom 301 yönlendirmesi, okunma artırımı, sosyal paylaşım butonları, faqs, başlıklar ve ilişkili yazılar dahil) 🚀👁️🔗
     */
    public function getDetailData(string $categorySlug, string $postSlug, ?string $projectKey = null, ?string $currentUrl = null, ?string $prefix = null): array
    {
        $projectKey = $this->resolveCurrentProjectKey($projectKey);
        $repo = $this->resolveTargetRepository();
        $prefix = $prefix ?? (property_exists($this, 'routePrefix') ? $this->routePrefix : (property_exists($this, 'categoryType') && $this->categoryType === 'news' ? '/haber' : '/blog'));

        // 1. Yazıyı veritabanından getir 🔍
        $post = ($repo && method_exists($repo, 'getPostBySlug')) ? $repo->getPostBySlug($postSlug, $projectKey) : null;

        // 1.5. Yazıyı enrichPost ile zenginleştir (Headings/İçindekiler, Okuma Süresi, Tarihler) 📝
        if ($post && method_exists($this, 'enrichPost')) {
            $post = $this->enrichPost($post);
        }

        // 2. Yazı bulunamadıysa otonom 301 yönlendir 🧭
        if (!$post) {
            $redirectUrl = method_exists($this, 'resolveLegacyPostRedirect')
                ? $this->resolveLegacyPostRedirect($postSlug, $projectKey, $prefix)
                : $prefix;

            $this->perform301Redirect($redirectUrl);
        }

        // 3. Kategoriyi çöz 📁
        $category = !empty($post['category_id']) ? $this->getCategory((int) $post['category_id'], $projectKey) : [];

        // 4. Kategori uyuşmazlığı varsa doğru kategori URL'sine 301 yönlendir 📁
        if (!empty($category['slug']) && $category['slug'] !== $categorySlug) {
            $this->perform301Redirect(trim($prefix, '/') . '/' . $category['slug'] . '/' . $post['slug']);
        }

        // 5. Okunma sayısını artır 👁️
        if ($repo && !empty($post['id']) && method_exists($repo, 'incrementViews')) {
            $repo->incrementViews((int) $post['id']);
        }

        $catId = (int) ($category['id'] ?? 0);
        $postId = (int) ($post['id'] ?? 0);

        // 6. Benzer yazılar (Aynı kategorideki diğer yazılar) 📌
        $relatedPosts = $this->getMappedPosts($projectKey, [
            'is_active' => 1,
            'category_id' => $catId,
            'limit' => 4
        ]);
        $relatedPosts = array_values(array_filter($relatedPosts, fn($item) => (int) ($item['id'] ?? 0) !== $postId));
        $relatedPosts = array_slice($relatedPosts, 0, 3);

        // 7. Son yazılar 🕒
        $recentPosts = $this->getMappedPosts($projectKey, [
            'is_active' => 1,
            'limit' => 6
        ]);
        $recentPosts = array_values(array_filter($recentPosts, fn($item) => (int) ($item['id'] ?? 0) !== $postId));
        $recentPosts = array_slice($recentPosts, 0, 5);

        // 8. Aktif Kategoriler Listesi 📂
        $categories = $repo && method_exists($repo, 'categoriesWithCounts') ? $repo->categoriesWithCounts($projectKey) : [];

        // 9. Sosyal Paylaşım Butonlarını Otomatik Üret 🔗
        $urlToShare = $currentUrl ?: (function_exists('url') ? url('blog/' . ($category['slug'] ?? 'genel') . '/' . ($post['slug'] ?? '')) : '');
        $shareButtons = [];
        if (method_exists($this, 'service')) {
            $baseProjectService = $this->service('base.project');
            if ($baseProjectService && method_exists($baseProjectService, 'getShareLinks')) {
                $shareButtons = $baseProjectService->getShareLinks($urlToShare, $post['title'] ?? '');
            }
        }

        // 10. FAQ & Headings Ayrıştırması ❓
        $faqsArray = [];
        if (!empty($post['faqs'])) {
            $faqsArray = is_array($post['faqs']) ? $post['faqs'] : json_decode((string) $post['faqs'], true);
        }
        $headings = $post['headings'] ?? [];

        return [
            'post' => $post,
            'category' => $category,
            'relatedPosts' => $relatedPosts,
            'recentPosts' => $recentPosts,
            'categories' => $categories,
            'shareButtons' => $shareButtons,
            'currentUrl' => $urlToShare,
            'faqsArray' => $faqsArray,
            'headings' => $headings,
        ];
    }
}
