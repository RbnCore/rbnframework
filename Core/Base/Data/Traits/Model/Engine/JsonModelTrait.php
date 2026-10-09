<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Base\Data\Traits\Model\Engine;

/**
 * JsonModelTrait - Autonomous JSON Casting Engine 🎭🧬⚓
 * 
 * RBN Framework: Modellerde JSON sütunlarını otomatik olarak
 * Array <-> JSON dönüşümü yapan motor parçası.
 */
trait JsonModelTrait
{
    /**
     * JSON spesifik veri hazırlama motoru 🛰️⚓
     */
    protected function prepareJsonForStorage(array $data): array
    {
        if (isset($this->jsonFields) && is_array($this->jsonFields)) {
            foreach ($this->jsonFields as $field) {
                if (isset($data[$field])) {
                    if (is_array($data[$field]) || is_object($data[$field])) {
                        // B-78: NESNE değerler de JSON'a çevrilmeliydi.
                        // Önceki `else { null }` kolu stdClass vb. alanları
                        // sessizce siliyordu; şimdi nesne de metne dönüşür.
                        $data[$field] = json_encode($data[$field], JSON_UNESCAPED_UNICODE);
                    } elseif (!empty($data[$field]) && is_string($data[$field])) {
                        // Zaten string ise dokunma
                    } else {
                        $data[$field] = null;
                    }
                }
            }
        }

        return $data;
    }

    /**
     * JSON spesifik veri doldurma motoru 🧬🏛️
     */
    protected function fillJsonFields(array $row): array
    {
        $jsonFields = $this->jsonFields ?? [];

        foreach ($row as $key => $value) {
            if (in_array($key, $jsonFields) && is_string($value)) {
                $decoded = json_decode($value, true);
                $row[$key] = (json_last_error() === JSON_ERROR_NONE) ? $decoded : $value;
            }
        }

        return $row;
    }
}
