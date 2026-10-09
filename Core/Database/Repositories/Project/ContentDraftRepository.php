<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Database\Repositories\Project;

use Rbn\Framework\Core\Base\Data\BaseRepository;

/**
 * ContentDraftRepository - Evrensel İçerik Taslak Veri Deposu 🏛️📦⚡
 * RBN Framework Standard.
 */
class ContentDraftRepository extends BaseRepository
{
    /** @var string Hedef Model Bağlantısı 🏺 */
    protected $targetModel = 'app.contentDraft';

    /**
     * Esnek Taslak Sorgulayıcı 📝
     */
    public function getDrafts(array $options = []): array
    {
        $orderBy = $options['order_by'] ?? 'order_num';
        $direction = $options['direction'] ?? 'ASC';

        // [FW-ALTYAPI-3 / H · G4] `ContentDraftModel` artık `scoped = true`.
        // Açık anahtar -> `withProjectScope()`; yoksa modelin kapsamı.
        // `resolveCurrentProjectKey()` fallback'i kaldırıldı (çakışmanın kendisi).
        $model = $this->model($this->targetModel);
        if (!empty($options['project_key'])) {
            $model = $model->withProjectScope((string) $options['project_key']);
        }
        $query = $model->query();

        if (isset($options['type'])) {
            $query->where('type', $options['type']);
        }

        if (isset($options['category_id']) && $options['category_id'] !== '') {
            $query->where('category_id', (int) $options['category_id']);
        }

        if (!empty($options['search'])) {
            $search = '%' . $options['search'] . '%';
            $query->where('title', 'LIKE', $search);
        }

        if (!empty($options['limit'])) {
            $query->limit((int) $options['limit']);
        }

        $query->orderBy($orderBy, $direction);

        if (!empty($options['first'])) {
            $draft = $query->first();
            return $draft && is_object($draft) && method_exists($draft, 'toArray') ? $draft->toArray() : (array) $draft;
        }

        $res = $query->get();
        return is_object($res) && method_exists($res, 'toArray') ? $res->toArray() : (array) $res;
    }

    /**
     * Taslak kaydet/güncelle 💾
     */
    public function saveDraft(array $data)
    {
        $id = isset($data['id']) ? (int) $data['id'] : null;
        $model = $this->model($this->targetModel);

        if ($id) {
            $draft = $model->find($id);
            if ($draft) {
                if (is_object($draft) && method_exists($draft, 'fill')) {
                    $draft->fill($data);
                    return $draft->save() ? (method_exists($draft, 'toArray') ? $draft->toArray() : (array) $draft) : false;
                }
                $updateData = $data;
                unset($updateData['id']);
                return $model->where('id', $id)->update($updateData) ? array_merge((array) $draft, $data) : false;
            }
        }

        $newDraft = $model->create($data);
        return is_object($newDraft) ? (method_exists($newDraft, 'toArray') ? $newDraft->toArray() : (array) $newDraft) : (array) $newDraft;
    }

    /**
     * Taslak sil 🗑️
     */
    public function destroyDraft(int $id): bool
    {
        $draft = $this->model($this->targetModel)->find($id);
        return $draft ? (bool) $draft->delete() : false;
    }

    /**
     * Toplu Sıralama Güncelleme 🔀
     */
    public function bulkUpdateOrder(array $order): bool
    {
        foreach ($order as $sortOrder => $id) {
            $this->saveDraft([
                'id' => (int) $id,
                'order_num' => $sortOrder + 1
            ]);
        }
        return true;
    }
}
