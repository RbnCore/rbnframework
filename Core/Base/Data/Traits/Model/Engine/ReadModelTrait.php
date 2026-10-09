<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Base\Data\Traits\Model\Engine;

/**
 * ReadModelTrait - Model Discovery & Retrieval 🔎🛰️
 */
trait ReadModelTrait
{
    /**
     * Find a single record by ID with optional eager loading 🔍
     */
    public function find($id): ?array
    {
        $query = $this->query();
        $result = $query->where($this->getPrimaryKey(), $id)->first();

        if ($result && method_exists($this, 'loadRelationships') && !empty($query->getEagerLoads())) {
            $hydrated = $this->loadRelationships([$result], $query->getEagerLoads());
            $row = $hydrated[0] ?? null;

            // B-73: hydrate edilmiş MODEL NESNESİ bu yoldan olduğu gibi
            // döndürülüyordu; imza `?array` olduğu için TypeError veriyordu.
            // Aynı sözleşme eager YOK yolunda zaten `toArray()` idi.
            return $row && is_object($row) && method_exists($row, 'toArray') ? $row->toArray() : $row;
        }

        return $result && is_object($result) && method_exists($result, 'toArray') ? $result->toArray() : $result;
    }

    /**
     * Get all records with optional eager loading 🌈
     */
    public function all(): array
    {
        $query = $this->query();
        $results = $query->get()->all();

        if (!empty($results) && method_exists($this, 'loadRelationships') && !empty($query->getEagerLoads())) {
            return $this->loadRelationships($results, $query->getEagerLoads());
        }

        return $results;
    }

    /**
     * RBN Framework HYDRATOR: Veritabanından çekerken veriyi nesneye doldur 🧬🏛️
     * RBN Framework: Otonom JSON dönüşümlerini tetikler.
     */
    public function forceJsonFill(array $row): void
    {
        // 1. JSON Alanları Otonom Decode Et (JsonModelTrait)
        if (method_exists($this, 'fillJsonFields')) {
            $row = $this->fillJsonFields($row);
        }

        // 2. Veriyi Nesne Özelliklerine Doldur 🏗️
        foreach ($row as $key => $value) {
            $this->{$key} = $value;
        }
    }
}
