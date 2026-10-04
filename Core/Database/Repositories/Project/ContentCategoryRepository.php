<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Database\Repositories\Project;

use Rbn\Framework\Core\Base\Data\BaseRepository;
use Rbn\Framework\Core\Base\Attributes\Component;

/**
 * ContentCategoryRepository - Evrensel İçerik Kategori Veri Deposu 🏛️📦⚡
 * RBN 3.5 Masterpiece Standard.
 */
class ContentCategoryRepository extends BaseRepository
{
    /** @var string Hedef Model Bağlantısı 🏺 */
    protected $targetModel = 'app.contentCategory';

    /**
     * Esnek Kategori Sorgulayıcı 📂
     */
    public function getCategories(array $options = []): array|null
    {
        // 1. Proje Filtresi
        //
        // [FW-ALTYAPI-3 / H · G4] `ContentCategoryModel` artık `scoped = true`.
        // ÖNCE burada `where('project_key', X)` ikinci kez çalışıyordu. Artık:
        // seçenekte AÇIK anahtar varsa kapsam `withProjectScope()` ile O'ya
        // daraltılır; yoksa modelin kapsamı (aktif bağlam) geçerli.
        // `resolveCurrentProjectKey()` fallback'i KALDIRILDI: kapsam açıkken
        // elle yeniden hesaplamak çakışmanın kendisidir.
        $model = $this->model($this->targetModel);
        if (!empty($options['project_key'])) {
            $model = $model->withProjectScope((string) $options['project_key']);
        }
        $query = $model->query();

        // 2. İçerik Türü (blog, news, program vb.)
        if (!empty($options['type'])) {
            $query->where('type', $options['type']);
        }

        // 3. Aktiflik Durumu
        if (array_key_exists('is_active', $options)) {
            if ($options['is_active'] !== null && $options['is_active'] !== 'all') {
                $query->where('is_active', (int) $options['is_active']);
            }
        } else {
            $query->where('is_active', 1);
        }

        if (isset($options['id'])) {
            $query->where('id', (int) $options['id']);
        }

        if (isset($options['slug'])) {
            $query->where('slug', (string) $options['slug']);
        }

        $orderBy = $options['order_by'] ?? 'order_num';
        $direction = $options['direction'] ?? 'ASC';
        $query->orderBy($orderBy, $direction);

        if (!empty($options['first'])) {
            $category = $query->first();
            return $category && is_object($category) && method_exists($category, 'toArray') ? $category->toArray() : $category;
        }

        if (isset($options['limit'])) {
            $query->limit((int) $options['limit']);
        }

        $res = $query->get();
        return is_object($res) && method_exists($res, 'toArray') ? $res->toArray() : (array) $res;
    }

    /**
     * Kategori kaydet veya güncelle 💾
     */
    public function saveCategory(array $data): array|bool
    {
        $id = isset($data['id']) ? (int) $data['id'] : null;
        $model = $this->model($this->targetModel);

        if ($id) {
            $category = $model->find($id);
            if ($category) {
                if (is_object($category) && method_exists($category, 'fill')) {
                    $category->fill($data);
                    return $category->save() ? true : false;
                }
                $updateData = $data;
                unset($updateData['id']);

                // [BULGU-6 / FW-ALTYAPI-1 A-01] Ham veri YAZILMAZ: `fill()`
                // yolu toplu atama korumasından geçiyordu, bu dizi dalı
                // (`where()->update()`) GEÇMİYORDU — panelden gönderilen
                // korumalı alan (ör. `project_key`) doğrudan yazılabiliyordu.
                // `filterFillable()` beyaz listeyi budar; `$fillable`
                // TANIMLANMAYAN modellerde veri DOKUNULMAZ (geri uyumluluk
                // kilidi: `MassAssignmentTrait::filterFillable()`).
                if (method_exists($model, 'filterFillable')) {
                    $updateData = $model->filterFillable($updateData);
                }

                return (bool) $model->where('id', $id)->update($updateData);
            }
        }

        $newCategory = $model->create($data);
        return $newCategory ? true : false;
    }

    /**
     * Kategori sil 🗑️
     */
    public function deleteCategory(int $id): bool
    {
        $category = $this->model($this->targetModel)->find($id);
        return $category ? (bool) $category->delete() : false;
    }

    /**
     * Toplu Sıralama Güncelleme 🔀
     */
    public function bulkUpdateOrder(array $order): bool
    {
        foreach ($order as $sortOrder => $id) {
            $this->saveCategory([
                'id' => (int) $id,
                'order_num' => $sortOrder + 1
            ]);
        }
        return true;
    }
}
