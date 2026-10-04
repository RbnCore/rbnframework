<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Base\Services\Traits\Provider\Engine;

/**
 * BulkProviderTrait - Strategic Bulk Operations for Providers 🛸🛰️
 * 
 * RBN 3.5: Proxies high-performance batch operations to the model.
 */
trait BulkProviderTrait
{
    /**
     * Bulk Destroy (Multiple IDs) 🗑️
     */
    public function destroyBulk(array $ids): int
    {
        $model = $this->component('model');
        return $model ? (int) $model->deleteBulk($ids) : 0;
    }

    /**
     * Bulk Toggle Status (Multiple IDs) 🔄
     */
    public function toggleStatusBulk(array $ids, bool $status, string $field = 'is_active'): int
    {
        $model = $this->component('model');
        return $model ? (int) $model->toggleStatusBulk($ids, $status, $field) : 0;
    }

    /**
     * Bulk Update (Column/Value pairs for Multiple IDs) 💾
     */
    public function updateBulk(array $ids, array $data): int
    {
        $model = $this->component('model');
        return $model ? (int) $model->updateBulk($ids, $data) : 0;
    }
}
