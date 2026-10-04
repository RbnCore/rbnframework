<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Database\Repositories\Project;

use Rbn\Framework\Core\Base\Data\BaseRepository;

/**
 * FaqRepository - Sovereign Data Repository for FAQ Submodule 🏛️📦⚓
 * 
 * RBN 3.5 Masterpiece Standard.
 * Handles data retrieval and persistence for Frequently Asked Questions.
 * 
 * @property \Rbn\Framework\Core\Database\Models\Project\FaqsModel $faqsModel
 */
class FaqRepository extends BaseRepository
{
    /** @var string Target primary model alias */
    protected $targetModel = 'project.faq';

    /**
     * Fetch all FAQs with standardized ordering 🔍🏛️⚓
     */
    public function fetch(array $options = []): array
    {
        $model = $this->model('project.faq');

        // 🎯 RBN 3.5: [SOVEREIGN FILTERING]
        // [FW-ALTYAPI-3 / H · G4] Elle `where('project_key', active_project_key())`
        // KALDIRILDI: `FaqsModel` artık `scoped = true`, kapsam modelin kendi
        // beyanı. Aynı değer iki kez yazılınca davranış değişmiyordu; kapsam
        // açılınca ikinci süzgeç çakışıyordu (fail-CLOSED ama gereksiz).
        $query = $model->query();

        if (isset($options['is_active'])) {
            $query->where('is_active', (int) $options['is_active']);
        }

        // Standard Identity Ordering
        $results = $query->orderBy('order_num', 'ASC')->get();

        return collect($results)->all();
    }

    /**
     * Saves or Updates the FAQ record 💾🛰️⚓
     */
    public function save(array $data): bool|int
    {
        $model = $this->model('project.faq');
        $id = isset($data['id']) ? (int) $data['id'] : null;

        // 🛡️ FW-BASE-1 T3: "sadece bossa doldur" semasi TEK merkeze tasindi
        // (BaseModel::fillProjectKeyIfMissing).
        // [FW-ALTYAPI-3 / H · G4] AYRICA kapsam açıldığı için `create/update`
        // yazma yolu da `project_key`'i sunucu bağlamından yazar; iki katman
        // AYNI değeri yazdığı için çakışma yok. Buradaki dolgu, aşağıdaki
        // `order_num` hesabı için gereklidir (o sorgu `project_key` okur).
        $data = \Rbn\Framework\Core\Base\Data\BaseModel::fillProjectKeyIfMissing($data);

        if (!$id && !isset($data['order_num'])) {
            // [FW-ALTYAPI-3 / H · G4] `order_num` hesabı AÇIK anahtar ister
            // (aktif bağlam her zaman doğru olmayabilir) → `withProjectScope()`.
            // Elle `where('project_key', ...)` ikinci süzgeçti.
            $max = $model->withProjectScope((string) $data['project_key'])->query()
                ->max('order_num');
            $data['order_num'] = ((int) $max) + 1;
        }

        return $model->save($data);
    }
}
