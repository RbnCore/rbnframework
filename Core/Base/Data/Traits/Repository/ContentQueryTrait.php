<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Base\Data\Traits\Repository;

/**
 * ContentQueryTrait - Reusable High-Performance Content Query Engine 🖋️⚡
 * 
 * RBN 3.5 Masterpiece Standard.
 * Trait for blog, news, and content providers or repositories across all projects.
 */
trait ContentQueryTrait
{
    /**
     * B-70: `project_key()` helper'ı guard'sız çağrılıyordu; CLI / erken boot'ta
     * tanımsız olduğu için "Call to undefined function" patlıyordu.
     * Aynı yerde `ShieldSettingsRepository` `function_exists` kullanıyor — tutarlılık.
     */
    protected function fallbackProjectKey(): string
    {
        return function_exists('project_key') ? (string) project_key() : '';
    }

    /**
     * B-72: `QueryBuilder::get()` `collect()` yüklüyse `Collection`, değilse DİZİ
     * döner (`rbn_helpers.php` composer autoload_files'ta DEĞİL, yalnız
     * `Paths.php` ve `rbn` CLI gerektiriyor). Eski kod `$query->get()->toArray()`
     * yazdığı için dizi döndüğünde "Call to a member function toArray() on array"
     * patlıyordu. Burada iki biçim de normalize edilir.
     *
     * @param  mixed $sonuc `get()`/`first()` dönüşü
     * @return array<int, array<string, mixed>>
     */
    protected function rowsToArray($sonuc): array
    {
        if (is_array($sonuc)) {
            return $sonuc;
        }

        if (is_object($sonuc) && method_exists($sonuc, 'toArray')) {
            $satirlar = $sonuc->toArray();
            return is_array($satirlar) ? $satirlar : [];
        }

        return [];
    }

    /**
     * Blog / İçerik yazılarını yüksek performanslı ve süzgeçli getirir 🚀
     */
    public function getPosts(array $options = []): array
    {
        $isFull = $options['full_content'] ?? false;
        $isCacheable = empty($options['search']) && empty($options['select']) && !$isFull;
        $projectKey = $options['project_key'] ?? (string) ($this->resolveProjectData('project_key') ?: $this->fallbackProjectKey());
        $catId = $options['category_id'] ?? 'all';
        $limit = $options['limit'] ?? 'all';
        $offset = $options['offset'] ?? 0;
        $target = property_exists($this, 'targetModel') ? (string) $this->targetModel : 'content';
        $isActiveKey = isset($options['is_active']) ? 'act' . $options['is_active'] : 'actall';
        $isRewrittenKey = isset($options['is_rewritten']) ? 'rw' . $options['is_rewritten'] : 'rwall';
        // D-42: sonucu degistiren TUM secenekler anahtarda olmali (siralama dahil)
        $orderKey = substr(md5(strtolower((string) ($options['order_by'] ?? 'created_at')) . '|' . strtoupper((string) ($options['direction'] ?? 'DESC'))), 0, 8);
        $cacheSubKey = "posts_{$target}_{$projectKey}_c{$catId}_{$isActiveKey}_{$isRewrittenKey}_l{$limit}_o{$offset}_s{$orderKey}";

        if ($isCacheable && method_exists($this, 'getCacheItem')) {
            $cached = $this->getCacheItem($cacheSubKey);
            if (is_array($cached) && !empty($cached)) {
                return $cached;
            }
        }

        $target = property_exists($this, 'targetModel') ? $this->targetModel : null;
        $query = null;

        if ($target && method_exists($this, 'model')) {
            $modelObj = $this->model($target);
            if ($modelObj) {
                $query = $modelObj->query();
            }
        }

        if (!$query && method_exists($this, 'query')) {
            $query = $this->query();
        }

        if (!$query) {
            return [];
        }

        $limit = $options['limit'] ?? null;
        $offset = $options['offset'] ?? 0;
        $orderBy = $options['order_by'] ?? 'created_at';
        $direction = $options['direction'] ?? 'DESC';

        // Performanslı kolon seçimi: Ön yüzde devasa HTML içeriği çekme ⚡
        if (!$isFull && empty($options['select'])) {
            $query->select('id, title, slug, summary, image, category_id, created_at, is_active, is_rewritten, project_key, views');
        } elseif (!empty($options['select'])) {
            $query->select($options['select']);
        }

        if (!empty($projectKey)) {
            $query->where('project_key', $projectKey);
        }

        if (isset($options['category_id'])) {
            $query->where('category_id', (int) $options['category_id']);
        }

        if (isset($options['is_active'])) {
            $query->where('is_active', (int) $options['is_active']);
        }

        if (isset($options['is_rewritten'])) {
            $query->where('is_rewritten', (int) $options['is_rewritten']);
        }

        if (!empty($options['search'])) {
            $search = '%' . $options['search'] . '%';
            $query->where('title', 'LIKE', $search);
        }

        $query->orderBy($orderBy, $direction);

        if ($limit !== null && (int) $limit > 0) {
            $query->limit((int) $limit);
        }

        if ($offset > 0) {
            $query->offset((int) $offset);
        }

        $results = $this->rowsToArray($query->get());

        if ($isCacheable && !empty($results) && method_exists($this, 'setCacheItem')) {
            $this->setCacheItem($cacheSubKey, $results, 3600);
        }

        return $results;
    }

    /**
     * Kategori bazlı yazı sayılarını QueryBuilder ile çekme (1-File Cache Korumalı) ⚡
     */
    public function categoriesWithCounts(?string $projectKey = null): array
    {
        $projectKey = $projectKey ?: (string) ($this->resolveProjectData('project_key') ?: '');
        $catType = property_exists($this, 'categoryType') ? (string) $this->categoryType : 'all';
        $cacheKey = 'cat_counts_' . ($projectKey ?: 'default') . '_' . $catType;

        if (method_exists($this, 'getCacheItem')) {
            $cached = $this->getCacheItem($cacheKey);
            if (is_array($cached) && !empty($cached)) {
                return $cached;
            }
        }

        $target = property_exists($this, 'targetModel') ? $this->targetModel : null;
        $query = null;

        if ($target && method_exists($this, 'model')) {
            $modelObj = $this->model($target);
            if ($modelObj) {
                $query = $modelObj->query();
            }
        }

        if (!$query && method_exists($this, 'query')) {
            $query = $this->query();
        }

        if (!$query) {
            return [];
        }

        $postCountsRaw = $query->select('category_id, COUNT(*) as post_count')
            ->where('is_active', 1);

        if (!empty($projectKey)) {
            $postCountsRaw->where('project_key', $projectKey);
        }

        $results = $this->rowsToArray($postCountsRaw->groupBy('category_id')->get());

        $countsMap = [];
        foreach ($results as $row) {
            $catId = (int) ($row['category_id'] ?? 0);
            $countsMap[$catId] = (int) ($row['post_count'] ?? 0);
        }

        // Kategori modeli soyutlaması: Proje özelindeki $targetCategoryModel veya varsayılan 'app.contentCategory' 🏺
        $catModelAlias = property_exists($this, 'targetCategoryModel') ? $this->targetCategoryModel : 'app.contentCategory';
        $allCategories = [];

        if (method_exists($this, 'model')) {
            $catModelObj = $this->model($catModelAlias);
            if ($catModelObj && method_exists($catModelObj, 'query')) {
                // [FW-ALTYAPI-3 / H · G4] `app.contentCategory` kapsamlı model
                // oldu; açık anahtar `withProjectScope()` ile daraltılır.
                // Kapsamsız hedeflerde (G1 beyanı `single-tenant-now`) ESKİ elle
                // süzgeç yolu BİREBİR korunur.
                $kapsamli = method_exists($catModelObj, 'isProjectScoped') && $catModelObj->isProjectScoped();
                if ($kapsamli && !empty($projectKey) && method_exists($catModelObj, 'withProjectScope')) {
                    $catModelObj = $catModelObj->withProjectScope((string) $projectKey);
                }
                $catQuery = $catModelObj->query()->where('is_active', 1);
                if (!$kapsamli && !empty($projectKey)) {
                    $catQuery->where('project_key', $projectKey);
                }
                // 🎯 DOĞRUDAN TİP FİLTRESİ: Eğer repository $categoryType belirtmişse ('news', 'blog', 'program') doğrudan filtrele!
                if (!empty($catType) && $catType !== 'all') {
                    $catQuery->where('type', $catType);
                }
                $allCategories = $this->rowsToArray($catQuery->get());
            }
        }

        $output = [];
        foreach ($allCategories as $cat) {
            $cId = (int) ($cat['id'] ?? 0);
            $count = $countsMap[$cId] ?? 0;
            if ($count > 0 || empty($countsMap)) {
                $cat['post_count'] = $count;
                $cat['total_posts'] = $count;
                $output[] = $cat;
            }
        }

        if (!empty($output) && method_exists($this, 'setCacheItem')) {
            $this->setCacheItem($cacheKey, $output, 3600);
        }

        if (empty($output) && !empty($countsMap)) {
            foreach ($countsMap as $cId => $count) {
                $output[] = [
                    'id' => $cId,
                    'name' => 'Kategori #' . $cId,
                    'slug' => 'kategori-' . $cId,
                    'post_count' => $count,
                    'total_posts' => $count
                ];
            }
        }

        // Kategori listesini en çok yazıya sahip olandan en aza doğru sırala 📊⚡
        usort($output, fn($a, $b) => ($b['post_count'] ?? 0) <=> ($a['post_count'] ?? 0));

        if (method_exists($this, 'setCacheItem') && !empty($output)) {
            $this->setCacheItem($cacheKey, $output);
        }

        return $output;
    }

    /**
     * Slug ve Proje Anahtarına göre yazıyı getirir 🔍
     */
    public function getPostBySlug(string $slug, ?string $projectKey = null): ?array
    {
        $target = property_exists($this, 'targetModel') ? $this->targetModel : null;
        $query = null;

        if ($target && method_exists($this, 'model')) {
            $modelObj = $this->model($target);
            if ($modelObj) {
                $query = $modelObj->query();
            }
        }

        if (!$query && method_exists($this, 'query')) {
            $query = $this->query();
        }

        if (!$query) {
            return null;
        }

        $query->where('slug', $slug)->where('is_active', 1);

        if (!empty($projectKey)) {
            $query->where('project_key', $projectKey);
        }

        $result = $query->first();

        return $result && is_object($result) && method_exists($result, 'toArray') ? $result->toArray() : (is_array($result) ? $result : null);
    }

    /**
     * Benzer slug ve Proje Anahtarına göre yazıyı getirir (Fallback) 🔍
     */
    public function getPostBySimilarSlug(string $slug, ?string $projectKey = null): ?array
    {
        $target = property_exists($this, 'targetModel') ? $this->targetModel : null;
        $query = null;

        if ($target && method_exists($this, 'model')) {
            $modelObj = $this->model($target);
            if ($modelObj) {
                $query = $modelObj->query();
            }
        }

        if (!$query && method_exists($this, 'query')) {
            $query = $this->query();
        }

        if (!$query) {
            return null;
        }

        // 1. LIKE ile başlangıcı eşleşenleri ara
        $q1 = clone $query;
        $q1->where('slug', 'LIKE', $slug . '%')->where('is_active', 1);
        if (!empty($projectKey)) {
            $q1->where('project_key', $projectKey);
        }
        $result = $q1->first();

        if ($result) {
            return is_object($result) && method_exists($result, 'toArray') ? $result->toArray() : (is_array($result) ? $result : null);
        }

        // 2. Sondaki sayısal ekleri temizleyip aramayı dene
        $cleanSlug = preg_replace('/-\d+$/', '', $slug);
        if ($cleanSlug !== $slug) {
            $q2 = clone $query;
            $q2->where('slug', 'LIKE', $cleanSlug . '%')->where('is_active', 1);
            if (!empty($projectKey)) {
                $q2->where('project_key', $projectKey);
            }
            $result = $q2->first();
            if ($result) {
                return is_object($result) && method_exists($result, 'toArray') ? $result->toArray() : (is_array($result) ? $result : null);
            }
        }

        return null;
    }

    /**
     * Yazının okunma sayısını 1 artırır 👁️
     */
    public function incrementViews(int $id): bool
    {
        $target = property_exists($this, 'targetModel') ? $this->targetModel : null;
        $model = null;

        if ($target && method_exists($this, 'model')) {
            $model = $this->model($target);
        }

        if (!$model && method_exists($this, 'query')) {
            $model = $this;
        }

        if (!$model) {
            return false;
        }

        $post = $model->find($id);
        if ($post) {
            $newViews = ((int) ($post['views'] ?? 0)) + 1;
            $saved = $model->save([
                'id' => $id,
                'views' => $newViews
            ]) !== false;

            if ($saved && method_exists($this, 'flushProjectCache')) {
                $this->flushProjectCache();
            }

            return $saved;
        }

        return false;
    }
}
