<?php

declare(strict_types=1);

namespace Rbn\Framework\Bundles\RbnSuite\RbnStudio\Controllers;

use Rbn\Framework\Core\Base\Attributes\SubModule;

/**
 * PostsController - RBN Framework Published Articles Management 📰🎨🛰️⚓
 * RBN Framework Standard.
 */
#[SubModule(module: 'studio', entity: 'posts')]
class PostsController extends RbnStudioController
{
    /**
     * Yayınlanan Makale Listesi 📋
     */
    public function index()
    {
        $tab = $this->request->query('tab', 'active');
        $search = $this->request->query('search');
        $categoryId = $this->request->query('category_id');
        $projectKey = $this->activeProjectKey();

        $postService = $this->resolvePostService($projectKey);

        $options = ['project_key' => $projectKey];
        if ($tab === 'active') {
            $options['is_active'] = 1;
        } elseif ($tab === 'passive') {
            $options['is_active'] = 0;
        } elseif ($tab === 'rewritten') {
            $options['is_rewritten'] = 1;
        }

        if (!empty($search)) {
            $options['search'] = $search;
        }

        if (!empty($categoryId)) {
            $options['category_id'] = (int) $categoryId;
        }

        $hasSocial = $this->hasSocial($projectKey);
        $selectFields = 'id, title, slug, summary, image, category_id, created_at, is_active, is_rewritten, project_key, views';
        if ($hasSocial) {
            $selectFields .= ', social_summary, social_hashtags';
        }

        $options['select'] = $selectFields;
        $posts = $postService ? $postService->getPosts($options) : [];
        $paginator = $this->paginate($posts, 15);

        // Tab rozetleri için sayılar
        $allPosts = $postService ? $postService->getPosts(['project_key' => $projectKey]) : [];
        $activeCount = 0;
        $passiveCount = 0;
        $rewrittenCount = 0;

        foreach ($allPosts as $p) {
            if (($p['is_active'] ?? 1) == 1) {
                $activeCount++;
            } else {
                $passiveCount++;
            }
            if (!empty($p['is_rewritten'])) {
                $rewrittenCount++;
            }
        }

        $counts = [
            'all' => count($allPosts),
            'active' => $activeCount,
            'passive' => $passiveCount,
            'rewritten' => $rewrittenCount
        ];

        // Kategoriler (Sadece kategori yapılandırması aktifse)
        $categories = $this->hasCategory($projectKey) 
            ? ($this->repository('app.contentCategory')->getCategories(['type' => 'blog', 'project_key' => $projectKey]) ?? [])
            : [];
        $categoryMap = array_column($categories, 'name', 'id');
        $categorySlugMap = array_column($categories, 'slug', 'id');

        // Aktif Sosyal Medya Platformları 📱
        $socialPlatforms = $this->getActiveSocialPlatforms($projectKey);

        return $this->render('Posts/index', [
            'posts' => $paginator->items(),
            'pager' => $paginator,
            'categories' => $categories,
            'categoryMap' => $categoryMap,
            'categorySlugMap' => $categorySlugMap,
            'activeTab' => $tab,
            'counts' => $counts,
            'rewrittenCount' => $rewrittenCount,
            'hasCategory' => $this->hasCategory($projectKey),
            'hasSocial' => $hasSocial,
            'socialPlatforms' => $socialPlatforms
        ]);
    }

    /**
     * Makale kaydet/güncelle 💾
     */
    public function save(): void
    {
        $id = $this->request->input('id');
        $projectKey = $this->activeProjectKey();

        $data = [
            'id' => $id ? (int) $id : null,
            'project_key' => $projectKey,
            'category_id' => (int) $this->request->input('category_id'),
            'title' => (string) $this->request->input('title'),
            'summary' => (string) $this->request->input('summary'),
            'content' => (string) $this->request->input('content'),
            'image' => (string) $this->request->input('image'),
            'is_active' => (int) ($this->request->input('is_active', 1)),
            'seo_title' => (string) $this->request->input('seo_title'),
            'seo_description' => (string) $this->request->input('seo_description'),
            'seo_keywords' => (string) $this->request->input('seo_keywords'),
        ];

        if ($this->hasSocial($projectKey)) {
            $data['social_summary'] = $this->request->input('social_summary') !== null ? (string) $this->request->input('social_summary') : null;
            $data['social_hashtags'] = $this->request->input('social_hashtags') !== null ? (string) $this->request->input('social_hashtags') : null;
        }

        $postService = $this->resolvePostService($projectKey);
        $result = $postService ? $postService->savePost($data) : false;

        $context = $id ? 'update' : 'create';
        $this->handleResult($result, 'Makale işlemi', 'posts', $context);
    }

    /**
     * Makale sil 🗑️
     */
    public function delete($id): void
    {
        $id = (int) $id;
        $projectKey = $this->activeProjectKey();
        $postService = $this->resolvePostService($projectKey);
        $result = $postService ? $postService->deletePost($id) : false;
        $this->handleResult($result, 'Makale silme', 'posts', 'delete');
    }


    /**
     * Makale Düzenleme Sayfası 📝
     */
    public function edit(?int $id = null)
    {
        $id = $id ?: (int) $this->request->input('id');
        $projectKey = $this->activeProjectKey();

        $postService = $this->resolvePostService($projectKey);
        $post = $id && $postService ? $postService->find($id) : [];

        $categories = $this->hasCategory($projectKey) 
            ? ($this->repository('app.contentCategory')->getCategories(['type' => 'blog', 'project_key' => $projectKey]) ?? [])
            : [];

        return $this->render('Posts/form', [
            'post' => $post,
            'id' => $id,
            'categories' => $categories,
            'projectKey' => $projectKey,
            'hasCategory' => $this->hasCategory($projectKey),
            'hasSocial' => $this->hasSocial($projectKey)
        ]);
    }

    /**
     * Gemini AI ile Yazı İçeriğini Yeniden Üretir (v2) 🧠🖋️🛰️
     */
    public function rewrite()
    {
        return $this->executeRewrite('blog');
    }

    /**
     * Gemini AI ile Bağımsız Kapak Görseli Üretir 🎨🧠🛰️
     */
    public function generateImage()
    {
        return $this->executeGenerateImage('blog');
    }

    /**
     * Sosyal Medya Paylaşım Modalı 🪟📱
     */
    public function socialModal(?int $id = null)
    {
        return $this->executeSocialModal('blog', $id);
    }

    /**
     * Sosyal Medya Paylaşım İstek Dağıtıcısı 🚀📱
     */
    public function socialShare()
    {
        return $this->executeSocialShare('blog');
    }
}
