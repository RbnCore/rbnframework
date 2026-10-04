<?php

declare(strict_types=1);

namespace Rbn\Framework\Bundles\RbnSuite\RbnStudio\Controllers;

use Rbn\Framework\Core\Base\Attributes\SubModule;

/**
 * CategoriesController - Sovereign Content Category Management 📂🎨🛰️⚓
 * RBN 3.5 Masterpiece Standard.
 */
#[SubModule(module: 'studio', entity: 'categories', model: 'app.contentCategory', repository: 'app.contentCategory')]
class CategoriesController extends RbnStudioController
{
    /**
     * Kategori Listesi 📋
     */
    public function index()
    {
        $projectKey = $this->activeProjectKey();

        if (!$this->hasCategory($projectKey)) {
            $this->response->redirect('/admin/studio/posts');
            return;
        }

        $hasBlog = $this->hasBlog($projectKey);
        $hasNews = $this->hasNews($projectKey);

        $defaultType = $hasBlog ? 'blog' : ($hasNews ? 'news' : 'blog');
        $type = $this->request->query('type', $defaultType);

        $categories = $this->repository('app.contentCategory')->getCategories([
            'type' => $type,
            'project_key' => $projectKey
        ]) ?? [];

        $allCategories = $this->repository('app.contentCategory')->getCategories([
            'project_key' => $projectKey
        ]) ?? [];

        $blogCount = 0;
        $newsCount = 0;
        foreach ($allCategories as $c) {
            $cType = $c['type'] ?? 'blog';
            if ($cType === 'blog') {
                $blogCount++;
            } elseif ($cType === 'news') {
                $newsCount++;
            }
        }

        $paginator = $this->paginate($categories, 15);

        return $this->render('Categories/index', [
            'categories' => $paginator->items(),
            'pager' => $paginator,
            'totalCount' => count($categories),
            'currentType' => $type,
            'hasBlog' => $hasBlog,
            'hasNews' => $hasNews,
            'blogCount' => $blogCount,
            'newsCount' => $newsCount
        ]);
    }

    /**
     * Kategori Kaydet / Güncelle 💾
     */
    public function save()
    {
        $projectKey = $this->activeProjectKey();
        $data = $this->request->form([
            'id' => 'optional',
            'project_key' => 'optional',
            'type' => 'optional',
            'name' => 'required',
            'description' => 'optional',
            'icon' => 'optional',
            'is_active' => 'required'
        ]);

        if (empty($data['project_key'])) {
            $data['project_key'] = $projectKey;
        }

        if (empty($data['type'])) {
            $hasBlog = (bool) ($this->getRouteConfig($projectKey, 'has_blog') ?? true);
            $hasNews = (bool) ($this->getRouteConfig($projectKey, 'has_news') ?? false);
            $data['type'] = $hasBlog ? 'blog' : ($hasNews ? 'news' : 'blog');
        }

        $result = $this->repository('app.contentCategory')->saveCategory($data);
        $context = $this->request->input('id') ? 'update' : 'create';

        return $this->handleResult($result, 'Kategori işlemi', 'categories', $context);
    }

    /**
     * Kategori Sil 🗑️
     */
    public function delete($id): void
    {
        $id = (int) $id;
        $result = $this->repository('app.contentCategory')->deleteCategory($id);
        $this->handleResult($result, 'Kategori kaydı', 'categories', 'delete');
    }

    /**
     * Kategori Durum Değiştirme (AJAX Toggle) 🎚️
     */
    public function status(): void
    {
        $id = (int) $this->request->input('id');
        $value = $this->request->input('value') ?? $this->request->input('status');
        $status = ($value == '1' || $value === true || $value == 'true' || $value == 'on') ? 1 : 0;

        $result = $this->repository('app.contentCategory')->saveCategory([
            'id' => $id,
            'is_active' => $status
        ]);

        $this->handleResult($result, null, false, 'status');
    }

    /**
     * Modal Görünümü 🪟
     */
    public function modal($id = null, ?string $view = null): void
    {
        $id = $id ?: $this->request->input('id');
        $id = $id ? (int) $id : null;
        $projectKey = $this->activeProjectKey();

        $category = $id ? $this->repository('app.contentCategory')->find($id) : [];
        $hasBlog = (bool) ($this->getRouteConfig($projectKey, 'has_blog') ?? true);
        $hasNews = (bool) ($this->getRouteConfig($projectKey, 'has_news') ?? false);
        $defaultType = $this->request->query('type', ($hasBlog ? 'blog' : ($hasNews ? 'news' : 'blog')));

        $this->render('Categories/Partials/modal', [
            'category' => $category,
            'id' => $id,
            'isEdit' => ($id > 0),
            'hasBlog' => $hasBlog,
            'hasNews' => $hasNews,
            'currentType' => $defaultType,
            'ajax' => true
        ]);
    }
}
