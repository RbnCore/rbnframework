<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Base\Data\Traits\Model\Engine;

/**
 * BulkModelTrait - High-Performance Batch Operations for RBN 3.5 🚀⚡
 */
trait BulkModelTrait
{
    /**
     * Delete multiple records 🗑️
     */
    public function deleteBulk(array $ids): int
    {
        if (empty($ids))
            return 0;
        return (int) $this->query()->whereIn($this->getPrimaryKey(), $ids)->delete();
    }

    /**
     * Update multiple records ⚙️
     */
    public function updateBulk(array $ids, array $data): int
    {
        if (empty($ids) || empty($data))
            return 0;
        return (int) $this->query()->whereIn($this->getPrimaryKey(), $ids)->update($data);
    }

    /**
     * Toggle multiple records' status 🔄
     */
    public function toggleStatusBulk(array $ids, bool $status, string $field = 'is_active'): int
    {
        if (empty($ids))
            return 0;
        return (int) $this->query()->whereIn($this->getPrimaryKey(), $ids)->update([$field => (int) $status]);
    }

    /**
     * Insert multiple records in one go 🚀⚡
     */
    public function insertBatch(array $data): bool
    {
        return $this->query()->insertBatch($data);
    }

    /**
     * Update multiple records in one go 🚀⚡
     */
    public function updateBatch(array $data, string $index = 'id'): bool
    {
        return $this->query()->updateBatch($data, $index);
    }
}
