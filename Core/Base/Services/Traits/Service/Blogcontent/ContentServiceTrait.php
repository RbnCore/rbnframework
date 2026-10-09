<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Base\Services\Traits\Service\Blogcontent;

/**
 * ContentServiceTrait - Autonomous Content & Media Orchestration Engine 🖋️📸⚡
 * 
 * RBN Framework Standard.
 * Standardizes savePost, destroyPost, media uploads, SEO slug, and metadata enrichment across all projects.
 */
trait ContentServiceTrait
{
    use ContentMetaTrait;
    /**
     * Blog / Haber / İçerik yazılarını getirir ve zenginleştirir 📜
     */
    public function getPosts(array $options = []): array
    {
        $repoKey = property_exists($this, 'targetRepository') ? $this->targetRepository : null;
        $posts = [];
        $repoFound = false;

        if ($repoKey && method_exists($this, 'repository')) {
            $repo = $this->repository($repoKey);
            if ($repo && method_exists($repo, 'getPosts')) {
                $posts = $repo->getPosts($options);
                $repoFound = true;
            }
        }

        if (!$repoFound && property_exists($this, 'targetModel') && method_exists($this, 'model')) {
            $model = $this->model($this->targetModel);
            if ($model) {
                // [FW-ALTYAPI-3 / H · G4] Hedef model `scoped = true` ise elle
                // `where('project_key', ...)` yerine kapsam `withProjectScope()`
                // ile daraltılır. Kapsamı OLMAYAN modellerde (blogs, news vb.;
                // G1 beyanı `single-tenant-now`) ESKİ YOL AYNEN korunur —
                // geri uyumluluk kilidi.
                $kapsamli = method_exists($model, 'isProjectScoped') && $model->isProjectScoped();
                if ($kapsamli && !empty($options['project_key'])) {
                    $model = $model->withProjectScope((string) $options['project_key']);
                }
                $query = $model->query();
                if (isset($options['is_active'])) {
                    $query->where('is_active', (int) $options['is_active']);
                }
                if (!$kapsamli && isset($options['project_key'])) {
                    $query->where('project_key', $options['project_key']);
                }
                $posts = $query->get()->toArray();
            }
        }

        if (method_exists($this, 'enrichPost')) {
            foreach ($posts as &$post) {
                $post = $this->enrichPost($post);
            }
            unset($post);
        }

        return $posts;
    }

    /**
     * Otonom Yazı Kaydetme / Güncelleme (SEO Slug + Görsel Yükleme + Görsel Silme) 💾⚓
     */
    public function savePost(array $data)
    {
        $folderParam = func_num_args() > 1 ? func_get_arg(1) : null;
        $uploadFolder = $folderParam ?: (property_exists($this, 'imageUploadFolder') ? $this->imageUploadFolder : (property_exists($this, 'imageFolder') ? $this->imageFolder : (property_exists($this, 'uploadFolder') ? $this->uploadFolder : 'blog')));
        $imageField = property_exists($this, 'imageField') ? $this->imageField : 'image';

        // 1. Otonom SEO Slug Oluşturma
        if (!empty($data['title']) && empty($data['slug'])) {
            if (method_exists($this, 'helper')) {
                $data['slug'] = $this->helper('meta.seo')->seoSlug($data['title']);
            }
        }

        // 2. Otonom Proje Anahtarı Bağlama
        // 🛡️ FW-BASE-1 T3: "sadece bossa doldur" semasi TEK merkeze tasindi.
        $pKey = method_exists($this, 'resolveProjectData') ? $this->resolveProjectData('project_key') : null;
        $data = \Rbn\Framework\Core\Base\Data\BaseModel::fillProjectKeyIfMissing(
            $data,
            $pKey !== null ? (string) $pKey : null
        );

        // 3. 📸 Otonom Görsel Yükleme ve Eski Görseli Temizleme Entegrasyonu
        if (property_exists($this, 'request') && $this->request && $this->request->hasFile($imageField)) {
            // Güncelleme işleminde eski resmi fiziksel disken sil
            if (!empty($data['id']) && method_exists($this, 'find')) {
                $oldPost = $this->find((int) $data['id']);
                if ($oldPost && !empty($oldPost[$imageField]) && method_exists($this, 'service')) {
                    $this->service('file')->delete($oldPost[$imageField], []);
                }
            }

            if (method_exists($this, 'service')) {
                $uploadResult = $this->service('file')->image($imageField, $uploadFolder, 'public');
                if ($uploadResult['success'] ?? false) {
                    $data[$imageField] = $uploadResult['path'];
                }
            }
        } elseif (property_exists($this, 'request') && $this->request && !empty($this->request->input('image_url'))) {
            $data[$imageField] = $this->request->input('image_url');
        }

        if (isset($data['image_url'])) {
            unset($data['image_url']);
        }

        // 4. Kaydetme İşlemini Repository veya Modelle Tamamla
        $savedResult = false;
        $repo = $this->resolveTargetRepository();
        if ($repo && method_exists($repo, 'save')) {
            $savedResult = $repo->save($data);
        } elseif (($model = $this->resolveTargetModel()) && method_exists($model, 'save')) {
            $savedResult = $model->save($data);
        }

        // 5. 📦 Otonom İçerik Önbelleği (content_data) Temizliği
        if ($savedResult) {
            $this->flushContentCache();
        }

        return $savedResult;
    }

    /**
     * Otonom Yazı Silme (Veritabanından Düşmeden Önce Görselini Diskten Silme) 🗑️⚓
     */
    public function destroyPost(?int $id = null): self
    {
        $targetId = $id ?? (property_exists($this, 'targetId') ? $this->targetId : null);
        if (!$targetId) {
            return $this;
        }

        // 1. Fiziksel resim dosyasını temizle
        $imageField = property_exists($this, 'imageField') ? $this->imageField : 'image';
        if (method_exists($this, 'find')) {
            $post = $this->find($targetId);
            if ($post && !empty($post[$imageField]) && method_exists($this, 'service')) {
                $this->service('file')->delete($post[$imageField], []);
            }
        }

        // 2. Veritabanından sil
        $repo = $this->resolveTargetRepository();
        $deleted = false;

        if ($repo && method_exists($repo, 'destroy')) {
            $deleted = (bool) $repo->destroy($targetId);
        }

        if (!$deleted) {
            $model = $this->resolveTargetModel();
            if ($model && method_exists($model, 'destroy')) {
                $deleted = (bool) $model->destroy($targetId);
            }
        }

        if (property_exists($this, 'lastResult')) {
            $this->lastResult = $deleted;
        }

        // 3. 📦 Otonom İçerik Önbelleği (content_data) Temizliği
        if ($deleted) {
            $this->flushContentCache();
        }

        return $this;
    }

    /**
     * Projenin tek izole içerik (content_data) önbelleğini sıfırlar 📦🧹
     */
    public function flushContentCache(): bool
    {
        try {
            $repo = $this->resolveTargetRepository();
            if ($repo && method_exists($repo, 'flushProjectCache')) {
                $repo->flushProjectCache();
            }
            if (method_exists($this, 'cache') && ($cache = $this->cache())) {
                if (method_exists($cache, 'delete')) {
                    $cache->delete('content_data');
                } elseif (method_exists($cache, 'forget')) {
                    $cache->forget('content_data');
                }
            }
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Alias for destroyPost with boolean return 🗑️
     */
    public function deletePost(?int $id = null): bool
    {
        $this->destroyPost($id);
        return property_exists($this, 'lastResult') ? (bool) $this->lastResult : true;
    }

    /**
     * Slug'a göre tekil içerik getirme 🔍
     */
    public function getPostBySlug(string $slug, ?string $projectKey = null): ?array
    {
        $repo = $this->resolveTargetRepository();
        $post = ($repo && method_exists($repo, 'getPostBySlug')) ? $repo->getPostBySlug($slug, $projectKey) : null;

        if ($post && method_exists($this, 'enrichPost')) {
            return $this->enrichPost($post);
        }

        return $post;
    }

    /**
     * Benzer slug'a göre içerik getirme (Fallback) 🔍
     */
    public function getPostBySimilarSlug(string $slug, ?string $projectKey = null): ?array
    {
        $repo = $this->resolveTargetRepository();
        $post = ($repo && method_exists($repo, 'getPostBySimilarSlug')) ? $repo->getPostBySimilarSlug($slug, $projectKey) : null;

        if ($post && method_exists($this, 'enrichPost')) {
            return $this->enrichPost($post);
        }

        return $post;
    }

    /**
     * Okunma / Gösterim sayısını 1 artırır 👁️
     */
    public function incrementViews(int $id): bool
    {
        $repo = $this->resolveTargetRepository();
        if ($repo && method_exists($repo, 'incrementViews')) {
            return $repo->incrementViews($id);
        }

        return false;
    }
}
