<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Base\Data\Traits\Model\Engine;

/**
 * TimestampModelTrait - Automatic date management ⏰
 * 
 * Provides automated created_at and updated_at handling.
 */
trait TimestampModelTrait
{
    protected bool $timestamps = true;

    /**
     * Automatic enrichment for persistence
     */
    protected function prepareTimestampForStorage(array $data, bool $isNew = true): array
    {
        if ($this->timestamps) {
            $now = now('Y-m-d H:i:s');
            if ($isNew) {
                $data['created_at'] = $now;
            } else {
                // B-77: UPDATE yolunda gelen `created_at` KULLANICI VERİSİYDİ ve
                // oluşturma zaman damgasını ezebiliyordu. Artık reddedilir;
                // INSERT yolunda `created_at` yine sunucu tarafından üretilir.
                unset($data['created_at']);
            }
            $data['updated_at'] = $now;
        }

        return $data;
    }
}
