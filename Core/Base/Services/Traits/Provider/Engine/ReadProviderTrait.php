<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Base\Services\Traits\Provider\Engine;

/**
 * ReadProviderTrait - Strategic Data Discovery for Providers 🔎🛰️
 * 
 * RBN 3.5: Proxies Model Read traits with added relationship management.
 */
trait ReadProviderTrait
{
    /**
     * Internal storage for requested relationships before execution 🧠
     */
    protected array $providerEagerLoads = [];

    /**
     * Proxy for Eager Loading (Model Relationships) 🔗
     */
    public function with($relations): self
    {
        $this->providerEagerLoads = is_array($relations) ? $relations : [$relations];
        return $this;
    }

    /**
     * Find a single record by ID with relationship support 🔍
     */
    public function find($id): ?array
    {
        $model = $this->component('model');
        if (!$model)
            return null;

        $query = $model->query();

        // Pass collected relationships to the query engine
        if (!empty($this->providerEagerLoads)) {
            $query->with($this->providerEagerLoads);
            $this->providerEagerLoads = []; // Reset after use
        }

        // Standard find logic with hydration support 🌊
        $result = $query->where($model->getPrimaryKey(), $id)->first();

        if ($result && !empty($query->getEagerLoads()) && method_exists($model, 'loadRelationships')) {
            $hydrated = $model->loadRelationships([$result], $query->getEagerLoads());
            return $hydrated[0] ?? null;
        }

        return $result && is_object($result) && method_exists($result, 'toArray') ? $result->toArray() : $result;
    }

    /**
     * Get all records with relationship support 🌈
     */
    public function all(): array
    {
        $model = $this->component('model');
        if (!$model)
            return [];

        $query = $model->query();

        // Pass collected relationships to the query engine
        if (!empty($this->providerEagerLoads)) {
            $query->with($this->providerEagerLoads);
            $this->providerEagerLoads = []; // Reset after use
        }

        $results = $query->get()->all();

        if (!empty($results) && !empty($query->getEagerLoads()) && method_exists($model, 'loadRelationships')) {
            return $model->loadRelationships($results, $query->getEagerLoads());
        }

        $results = array_map(fn($item) => (is_object($item) && method_exists($item, 'toArray')) ? $item->toArray() : (array) $item, $results);

        return $results;
    }
}
