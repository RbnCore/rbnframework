<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnPipeline\Concerns;

/**
 * PipelineResolverTrait - Universal Data & Candidate Resolution Pipeline Concern 🌐🎯⚓
 * 
 * RBN Framework Standard.
 * Handles source mode resolution, draft candidate fetching, RSS pool resolution,
 * category mapping, balanced item selection, and blacklist GUID resolution.
 * 
 * @method mixed service(string $name)
 * @method mixed model(string $name)
 * @method mixed helper(string $name)
 * @method string getProjectKey(?string $key = null, bool $throwOnEmpty = false)
 */
trait PipelineResolverTrait
{
    /** @var array Current task parameters 🛡️ */
    public array $taskParams = [];


    /**
     * Kategorileri AI için Çözümler/Formatlar, Seçilen Kategori ID'sini veya Slug Değerini Doğrular 🏷️
     * RBN Framework Standart: Tüm projelerde sabit 'app.contentCategory' (content_categories) kullanılır.
     */
    public function resolveCategories(
        mixed $categoriesOrModel = null,
        mixed $selectedId = null,
        ?array $candidateRes = null,
        bool $returnSlug = false
    ): array|int|string {
        if ($categoriesOrModel === false || (isset($this->taskParams['has_categories']) && $this->taskParams['has_categories'] === false)) {
            return $returnSlug ? '' : ($selectedId !== null ? (int) $selectedId : []);
        }

        $projectKey = function_exists('project_key') && !empty(project_key()) ? project_key() : (function_exists('active_project_key') && !empty(active_project_key()) ? active_project_key() : '');

        if (is_array($categoriesOrModel)) {
            $categoryList = $categoriesOrModel;
        } else {
            // Görev tipi veya preset filtresi (blog, news vb.)
            $targetType = $this->taskParams['category_type'] 
                ?? ($this->taskParams['content_type'] 
                ?? ($this->taskParams['preset'] 
                ?? ($this->taskParams['type'] ?? null)));

            // targetType ne gelirse (news, blog, trends, program vb.) doğrudan o sorgulanır, zorlama eşleme yapılmaz.

            $catRepo = method_exists($this, 'repository') ? $this->repository('app.contentCategory') : null;
            if ($catRepo && method_exists($catRepo, 'getCategories')) {
                $filterOptions = ['project_key' => $projectKey, 'is_active' => 1];
                if (!empty($targetType)) {
                    $filterOptions['type'] = (string) $targetType;
                }
                $categoryList = $catRepo->getCategories($filterOptions) ?? [];
            } else {
                $catModel = $this->model('app.contentCategory');
                // [FW-ALTYAPI-3 / H · G4] `ContentCategoryModel` kapsamlı;
                // açık anahtar `withProjectScope()` ile verilir (elle süzgeç
                // ikinci kez çalışıyordu ve kapsamla çakışıyordu).
                if ($catModel && !empty($projectKey) && method_exists($catModel, 'withProjectScope')
                    && method_exists($catModel, 'isProjectScoped') && $catModel->isProjectScoped()) {
                    $catModel = $catModel->withProjectScope((string) $projectKey);
                }
                $query = ($catModel && method_exists($catModel, 'query')) ? $catModel->query()->where('is_active', 1) : null;
                if ($query) {
                    if (!empty($projectKey)
                        && method_exists($catModel, 'isProjectScoped') && !$catModel->isProjectScoped()) {
                        $query->where('project_key', $projectKey);
                    }
                    if (!empty($targetType)) {
                        $query->where('type', (string) $targetType);
                    }
                    $categoryList = $query->get()->all();
                } else {
                    $categoryList = [];
                }
            }
        }

        // 1. Slug istendiyse ID'ye göre slug dön
        if ($returnSlug && $selectedId !== null) {
            foreach ($categoryList as $cat) {
                $catArray = is_array($cat) ? $cat : (method_exists($cat, 'toArray') ? $cat->toArray() : (array) $cat);
                $cId = (int) ($catArray['id'] ?? 0);
                if ($cId === (int) $selectedId && !empty($catArray['slug'])) {
                    return (string) $catArray['slug'];
                }
            }
            return '';
        }

        // 2. ID doğrulaması (Seçilen ID geçerli mi kontrol et)
        if ($selectedId !== null && !is_array($categoriesOrModel)) {
            $validId = null;
            foreach ($categoryList as $cat) {
                $cId = (int) (is_object($cat) ? ($cat->id ?? 0) : ($cat['id'] ?? 0));
                if ($cId === (int) $selectedId) {
                    $validId = $cId;
                    break;
                }
            }
            if ($validId !== null) {
                return $validId;
            }
            if (!empty($categoryList[0])) {
                return (int) (is_object($categoryList[0]) ? ($categoryList[0]->id ?? 0) : ($categoryList[0]['id'] ?? 0));
            }
            return (int) $selectedId;
        }

        // 3. AI ve Promptlar için standart formatlanmış kategori listesi dön
        $formatted = [];
        foreach ($categoryList as $cat) {
            $catArray = is_array($cat) ? $cat : (method_exists($cat, 'toArray') ? $cat->toArray() : (array) $cat);
            if (!empty($catArray['id']) && !empty($catArray['name'])) {
                $formatted[] = [
                    'id' => (int) $catArray['id'],
                    'name' => (string) $catArray['name'],
                    'slug' => (string) ($catArray['slug'] ?? '')
                ];
            }
        }

        return $formatted;
    }

    /**
     * Son Yayınlanan Aktif İçerikleri/Haberleri Otomatik Çözümler 📰
     */
    public function resolveRecentPosts(mixed $postModelOrName = null, int $limit = 50): array
    {
        $targetModel = $postModelOrName ?? ($this->taskParams['local_model'] ?? ($this->taskParams['post_model'] ?? 'blog'));
        if (empty($targetModel)) {
            return [];
        }

        try {
            $model = $this->model($targetModel);
            if ($model && method_exists($model, 'query')) {
                $projectKey = $this->getProjectKey();
                $query = $model->query()->where('is_active', 1);
                if (!empty($projectKey)) {
                    try {
                        $query->where('project_key', $projectKey);
                    } catch (\Throwable $e) {
                    }
                }
                return $query->orderBy('id', 'DESC')->limit($limit)->get()->all();
            }
        } catch (\Throwable $e) {
            // Ignore query failure
        }
        return [];
    }

    /**
     * Veritabanından sıradaki taslağı çeker 💾
     * RBN Framework Standart: Tüm projelerde sabit 'app.contentDraft' (content_drafts) kullanılır.
     */
    public function fetchDraftCandidate(array $params): ?array
    {
        $projectKey = $params['project_key'] ?? (function_exists('project_key') && !empty(project_key()) ? project_key() : (function_exists('active_project_key') && !empty(active_project_key()) ? active_project_key() : ''));
        $type = $params['type'] ?? ($this->taskParams['type'] ?? 'blog');

        $draftObj = null;

        // 1. Standart Core Repository Çözümlemesi 🏛️
        $draftRepo = method_exists($this, 'repository') ? $this->repository('app.contentDraft') : null;
        if ($draftRepo && method_exists($draftRepo, 'getDrafts')) {
            $draftObj = $draftRepo->getDrafts([
                'project_key' => $projectKey,
                'type' => $type,
                'first' => true
            ]);
        }

        // 2. Core Model Query Fallback 🚀
        if (empty($draftObj)) {
            $draftModel = $this->model('app.contentDraft');
            if ($draftModel && method_exists($draftModel, 'query')) {
                $query = $draftModel->query();
                if (!empty($projectKey)) {
                    $query->where('project_key', $projectKey);
                }
                if (!empty($type)) {
                    $query->where('type', $type);
                }
                $draftObj = $query->orderBy('order_num', 'ASC')->orderBy('id', 'ASC')->first();
            }
        }

        if (empty($draftObj)) {
            return null;
        }

        $draft = is_array($draftObj) ? $draftObj : (method_exists($draftObj, 'toArray') ? $draftObj->toArray() : (array) $draftObj);
        $draft['source'] = 'draft';
        $draft['source_type'] = 'draft';

        return $draft;
    }

    /**
     * Kategori Dağılımına Göre En Az İçerik Üretilmiş Kategoriden Sıradaki Öğeyi Seçer ⚖️
     */
    public function getNextBalancedItem(array $candidates, array $posts = [], array $categories = []): ?array
    {
        if (empty($candidates)) {
            return null;
        }

        if (empty($categories)) {
            return $candidates[0] ?? null;
        }

        $catCounts = [];
        foreach ($categories as $cat) {
            $catId = is_object($cat) ? ($cat->id ?? 0) : ($cat['id'] ?? 0);
            if ($catId > 0) {
                $catCounts[$catId] = 0;
            }
        }

        foreach ($posts as $post) {
            $pCatId = is_object($post) ? ($post->category_id ?? 0) : ($post['category_id'] ?? 0);
            if (isset($catCounts[$pCatId])) {
                $catCounts[$pCatId]++;
            }
        }

        asort($catCounts);
        $prioritizedCategoryIds = array_keys($catCounts);

        foreach ($prioritizedCategoryIds as $targetCatId) {
            foreach ($candidates as $cand) {
                $cCatId = (int) ($cand['category_id'] ?? 0);
                if ($cCatId === $targetCatId) {
                    return $cand;
                }
            }
        }

        return $candidates[0] ?? null;
    }

    /**
     * İşlenen RSS adaylarının GUID'lerini otomatik olarak kara liste için derler 🛡️
     */
    public function resolveGuidsForBlacklist(array $candidateRes, array $newsData = []): array
    {
        $guids = [$candidateRes['guid'] ?? null];
        $processedKeys = $newsData['processed_keys'] ?? [];
        $candidatesForAi = $candidateRes['candidates_for_ai'] ?? [];

        foreach ($processedKeys as $pKey) {
            if (isset($candidatesForAi[$pKey]['guid'])) {
                $guids[] = $candidatesForAi[$pKey]['guid'];
            }
        }

        return array_values(array_filter($guids));
    }
}
