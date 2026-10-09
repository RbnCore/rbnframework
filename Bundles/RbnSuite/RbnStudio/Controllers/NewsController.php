<?php

declare(strict_types=1);

namespace Rbn\Framework\Bundles\RbnSuite\RbnStudio\Controllers;

use Rbn\Framework\Core\Base\Attributes\SubModule;

/**
 * NewsController - RBN Framework Published News Management 📰🎨🛰️⚓
 * RBN Framework Standard.
 */
#[SubModule(module: 'studio', entity: 'news')]
class NewsController extends RbnStudioController
{
    /**
     * Yayınlanan Haber Listesi 📋
     */
    public function index()
    {
        $projectKey = $this->activeProjectKey();

        if (!$this->hasNews($projectKey)) {
            $this->response->redirect('/admin/studio/posts');
            return;
        }

        $tab = $this->request->query('tab', 'active');
        $search = $this->request->query('search');
        $categoryId = $this->request->query('category_id');
        $projectKey = $this->activeProjectKey();

        $newsService = $this->resolveNewsService($projectKey);

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
        $posts = $newsService ? $newsService->getPosts($options) : [];
        $paginator = $this->paginate($posts, 15);

        // Tab rozetleri için sayılar
        $allNews = $newsService ? $newsService->getPosts(['project_key' => $projectKey]) : [];
        $activeCount = 0;
        $passiveCount = 0;
        $rewrittenCount = 0;
        foreach ($allNews as $p) {
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
            'all' => count($allNews),
            'active' => $activeCount,
            'passive' => $passiveCount,
            'rewritten' => $rewrittenCount
        ];

        // Haber Kategorileri (Sadece kategori yapılandırması aktifse)
        $categories = $this->hasCategory($projectKey) 
            ? ($this->repository('app.contentCategory')->getCategories(['type' => 'news', 'project_key' => $projectKey]) ?? [])
            : [];
        $categoryMap = array_column($categories, 'name', 'id');
        $categorySlugMap = array_column($categories, 'slug', 'id');

        // Aktif Sosyal Medya Platformları 📱
        $socialPlatforms = $this->getActiveSocialPlatforms($projectKey);

        return $this->render('News/index', [
            'posts' => $paginator->items(),
            'pager' => $paginator,
            'categories' => $categories,
            'categoryMap' => $categoryMap,
            'categorySlugMap' => $categorySlugMap,
            'activeTab' => $tab,
            'counts' => $counts,
            'hasCategory' => $this->hasCategory($projectKey),
            'hasSocial' => $hasSocial,
            'socialPlatforms' => $socialPlatforms
        ]);
    }

    /**
     * Haber kaydet/güncelle 💾
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

        $newsService = $this->resolveNewsService($projectKey);
        $result = $newsService ? $newsService->savePost($data) : false;

        $context = $id ? 'update' : 'create';
        $this->handleResult($result, 'Haber işlemi', 'news', $context);
    }

    /**
     * Haber sil 🗑️
     */
    public function delete($id): void
    {
        $id = (int) $id;
        $projectKey = $this->activeProjectKey();
        $newsService = $this->resolveNewsService($projectKey);
        $result = $newsService ? $newsService->destroyPost($id) : false;
        $this->handleResult($result, 'Haber silme', 'news', 'delete');
    }

    /**
     * Haber Düzenleme Sayfası 📝
     */
    public function edit(?int $id = null)
    {
        $id = $id ?: (int) $this->request->input('id');
        $projectKey = $this->activeProjectKey();

        $newsService = $this->resolveNewsService($projectKey);
        $post = $id && $newsService ? $newsService->find($id) : [];

        $categories = $this->hasCategory($projectKey) 
            ? ($this->repository('app.contentCategory')->getCategories(['type' => 'news', 'project_key' => $projectKey]) ?? [])
            : [];

        return $this->render('News/form', [
            'post' => $post,
            'id' => $id,
            'categories' => $categories,
            'projectKey' => $projectKey,
            'hasCategory' => $this->hasCategory($projectKey),
            'hasSocial' => $this->hasSocial($projectKey)
        ]);
    }

    /**
     * Gemini AI ile Bağımsız Haber Kapak Görseli Üretir 🎨🧠🛰️
     */
    public function generateImage()
    {
        return $this->executeGenerateImage('news');
    }

    /**
     * Gemini AI ile Haber İçeriğini Yeniden Üretir 🧠🖋️🛰️
     */
    public function rewrite()
    {
        return $this->executeRewrite('news');
    }

    /**
     * Sosyal Medya Paylaşım Modalı 🪟📱
     */
    public function socialModal(?int $id = null)
    {
        return $this->executeSocialModal('news', $id);
    }

    /**
     * Sosyal Medya Paylaşım İstek Dağıtıcısı 🚀📱
     */
    public function socialShare()
    {
        return $this->executeSocialShare('news');
    }
}
