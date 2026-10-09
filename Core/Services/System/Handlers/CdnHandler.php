<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Services\System\Handlers;

use Rbn\Framework\Core\Base\BaseComponent;

/**
 * CdnHandler - CDN Logic & Data Processor ⚙️🛰️⚓
 * 
 * RBN Framework: Veri setlerini (dizi, model, koleksiyon) CDN standartlarına göre işler.
 */
class CdnHandler extends BaseComponent
{
    /**
     * Tekil veri setindeki (model/array) alanları çözümler.
     */
    public function processFields(object|array &$data, array $fields, string $baseUrl): void
    {
        foreach ($fields as $field) {
            $value = is_array($data) ? ($data[$field] ?? null) : ($data->{$field} ?? null);

            if (empty($value)) continue;

            // 1. Dizi (JSON Decode edilmiş) işleme 🖼️
            if (is_array($value)) {
                $processedArray = [];
                foreach ($value as $key => $subValue) {
                    if (is_string($subValue) && !str_starts_with($subValue, 'http')) {
                        $processedArray[$key] = $baseUrl . ltrim($subValue, '/');
                    } else {
                        $processedArray[$key] = $subValue;
                    }
                }
                $this->updateValue($data, $field, $processedArray);
            } 
            // 2. Düz metin işleme 📝
            else if (is_string($value) && !str_starts_with($value, 'http')) {
                $this->updateValue($data, $field, $baseUrl . ltrim($value, '/'));
            }
        }
    }

    /**
     * Veriyi tipine göre güvenli güncelleme.
     */
    protected function updateValue(object|array &$data, string $field, mixed $newValue): void
    {
        if (is_array($data)) {
            $data[$field] = $newValue;
        } else {
            $data->{$field} = $newValue;
        }
    }

    /**
     * Koleksiyonları toplu işleme. 🛰️⚓
     */
    public function processCollection(iterable $collection, array $fields, string $baseUrl): iterable
    {
        foreach ($collection as $item) {
            $this->processFields($item, $fields, $baseUrl);
        }

        return $collection;
    }
}
