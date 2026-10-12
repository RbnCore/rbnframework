<?php

declare(strict_types=1);

namespace Rbn\Framework\Packages\RbnFactory\Repositories;

use Rbn\Framework\Core\Base\Attributes\Component;
use Rbn\Framework\Core\Base\Data\BaseRepository;

/**
 * FactoryDraftRepository - Fabrikanın konu kaynağı taslak tablosu erişimi 📝📦
 */
#[Component(alias: 'factory.draft', type: 'repository')]
class FactoryDraftRepository extends BaseRepository
{
    /** @var string Hedef model takma adı */
    protected $targetModel = 'factory.draft';

    private function drafts(): object
    {
        return $this->model('factory.draft')->withProjectScope($this->handler('factoryConfig')->projectKey());
    }

    /**
     * Sunulabilir aday taslaklar (sıra: order_num, id).
     * @param string[] $types
     * @return array<int,array<string,mixed>>
     */
    public function offerable(array $types): array
    {
        if ($types === []) {
            return [];
        }
        return $this->drafts()->query()
            ->whereIn('type', $types)
            ->orderBy('order_num', 'ASC')
            ->orderBy('id', 'ASC')
            ->rows();
    }

    /** @return array<string,mixed>|null */
    public function findDraft(int $id): ?array
    {
        $rows = $this->drafts()->query()->where('id', $id)->limit(1)->rows();
        return $rows[0] ?? null;
    }

    public function deleteDraft(int $id): bool
    {
        return $this->drafts()->query()->where('id', $id)->delete() > 0;
    }
}
