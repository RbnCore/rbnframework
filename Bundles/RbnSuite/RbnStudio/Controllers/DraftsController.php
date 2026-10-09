<?php

declare(strict_types=1);

namespace Rbn\Framework\Bundles\RbnSuite\RbnStudio\Controllers;

use Rbn\Framework\Core\Base\Attributes\SubModule;

/**
 * DraftsController - RBN Framework AI & Content Drafts Management 📝🎨🛰️⚓
 * RBN Framework Standard.
 */
#[SubModule(module: 'studio', entity: 'drafts', model: 'app.contentDraft', repository: 'app.contentDraft')]
class DraftsController extends RbnStudioController
{
    /**
     * Fikir & Taslak Listesi 📋
     */
    public function index()
    {
        $projectKey = $this->activeProjectKey();

        if (!$this->hasDraft($projectKey)) {
            $this->response->redirect('/admin/studio/posts');
            return;
        }

        $search = $this->request->query('search');
        $categoryId = $this->request->query('category_id');

        $options = ['project_key' => $projectKey];
        if (!empty($search)) {
            $options['search'] = $search;
        }
        if (!empty($categoryId)) {
            $options['category_id'] = (int) $categoryId;
        }

        $drafts = $this->repository('app.contentDraft')->getDrafts($options);
        $paginator = $this->paginate($drafts, 15);

        // Kategoriler
        $categories = $this->repository('app.contentCategory')->getCategories(['type' => 'blog', 'project_key' => $projectKey]) ?? [];
        $categoryMap = array_column($categories, 'name', 'id');

        return $this->render('Drafts/index', [
            'drafts' => $paginator->items(),
            'pager' => $paginator,
            'categories' => $categories,
            'categoryMap' => $categoryMap,
            'totalCount' => count($drafts)
        ]);
    }

    /**
     * Fikir ekle / güncelle 💾
     */
    public function save()
    {
        $id = $this->request->input('id');
        $projectKey = $this->request->input('project_key', $this->activeProjectKey());
        $categoryId = (int) $this->request->input('category_id');
        $bulkMode = $this->request->input('bulk_mode') === '1';

        if ($id) {
            $data = [
                'id' => (int) $id,
                'project_key' => $projectKey,
                'category_id' => $categoryId,
                'title' => (string) $this->request->input('title')
            ];
            $result = $this->repository('app.contentDraft')->saveDraft($data);
            return $this->handleResult($result, 'Fikir kaydı', 'drafts', 'update');
        }

        if ($bulkMode) {
            $titlesText = (string) $this->request->input('titles');
            $titles = array_filter(array_map('trim', preg_split('/[;\n\r]+/', $titlesText)));

            if (empty($titles)) {
                return $this->handleResult(['success' => false, 'message' => 'Geçerli bir konu başlığı bulunamadı.'], 'Fikir kaydı', 'drafts', 'create');
            }

            $savedCount = 0;
            foreach ($titles as $title) {
                if (!empty($title)) {
                    $this->repository('app.contentDraft')->saveDraft([
                        'project_key' => $projectKey,
                        'category_id' => $categoryId,
                        'title' => $title,
                        'is_generated' => 0
                    ]);
                    $savedCount++;
                }
            }

            return $this->handleResult(['success' => true, 'message' => "{$savedCount} adet yeni taslak başarıyla eklendi."], 'Fikir kaydı', 'drafts', 'create');
        }

        // Tekli Ekleme
        $title = (string) $this->request->input('title');
        $result = $this->repository('app.contentDraft')->saveDraft([
            'project_key' => $projectKey,
            'category_id' => $categoryId,
            'title' => $title,
            'is_generated' => 0
        ]);

        return $this->handleResult($result, 'Fikir kaydı', 'drafts', 'create');
    }

    /**
     * Silme işlemi 🗑️
     */
    public function delete($id): void
    {
        $id = (int) $id;
        $result = $this->repository('app.contentDraft')->deleteDraft($id);
        $this->handleResult($result, 'Taslak kaydı', 'drafts', 'delete');
    }

    /**
     * Yapay Zeka ile Blog Yazısı Üret 🤖
     */
    public function generate(int $id)
    {
        $projectKey = $this->activeProjectKey();
        $result = $this->service('blogAutopilot')->generateArticle(
            $id,
            'Ssblogs.post',
            'app.contentDraft',
            'app.contentCategory',
            $projectKey,
            ['has_author_comment' => true]
        );

        return $this->response->json([
            'success' => (bool) ($result['success'] ?? false),
            'type' => ($result['success'] ?? false) ? 'success' : 'error',
            'message' => $result['message'] ?? 'Bir hata oluştu.',
            'redirect' => $this->returnPath('drafts')
        ]);
    }

    /**
     * Modal Görünümü 🪟
     */
    public function modal($id = null, ?string $view = null): void
    {
        $id = $id ?: $this->request->input('id');
        $id = $id ? (int) $id : null;
        $projectKey = $this->activeProjectKey();

        $draft = $id ? $this->repository('app.contentDraft')->find($id) : [];
        $categories = $this->repository('app.contentCategory')->getCategories(['type' => 'blog', 'project_key' => $projectKey]) ?? [];

        $this->render('Drafts/Partials/modal', [
            'draft' => $draft,
            'id' => $id,
            'isEdit' => ($id > 0),
            'categories' => $categories,
            'ajax' => true
        ]);
    }
}
