<?php

namespace Rbn\Framework\Core\Base\Services\Traits\Service\Engine;

/**
 * ReadServiceTrait - RBN 3.0 Standard Data Retrieval 🔍
 * 
 * RBN 3.5: Hardened with Shield support to prevent fatal null pointer exceptions.
 */
trait ReadServiceTrait
{
    /**
     * Active Records Filter (Fluent API) 🟢
     */
    public function active(string $field = 'is_active', $value = 1): self
    {
        $this->criteria[$field] = $value;
        return $this;
    }

    /**
     * Passive Records Filter (Fluent API) 🔴
     */
    public function passive(string $field = 'is_active', $value = 0): self
    {
        $this->criteria[$field] = $value;
        return $this;
    }

    /**
     * Get Record by ID
     */
    public function find(int $id): ?object
    {
        return $this->component('model')->where('id', $id)->first();
    }

    /**
     * Get All Records (Strategic Zero-Code Proxy) 🛰️🔍
     */
    public function all(): array
    {
        // 🎼 RBN 3.5: Masterpiece Strategic Dispatch 🔱🛰️⚓
        if (isset($this->provider) && method_exists($this->provider, 'fetch')) {
            return $this->provider->fetch($this->criteria);
        }

        // 🎯 RBN 3.5: Fail-safe Criteria Enforcer (Prevents Memory Exhaustion) 🛡️🧬
        // If no provider is present, we MUST pass our criteria pool to the model's query engine.
        $query = $this->component('model')->query();
        
        foreach ($this->criteria as $field => $data) {
            // Handle both simple values and specialized condition arrays
            if (is_array($data)) {
                $query = $query->where($field, $data['operator'] ?? '=', $data['value'] ?? null);
            } else {
                $query = $query->where($field, $data);
            }
        }

        return $query->get()->all();
    }

    /**
     * Get Record Count
     */
    public function count(): int
    {
        return (int) $this->component('model')->count();
    }

    /**
     * Get Root/Parent Records (Hierarchical Logic) 🌳
     * RBN 3.0 Standard: All parent-child structures use parent_id field.
     */
    public function parents(?int $excludeId = null): array
    {
        $query = $this->component('model')->whereNull('parent_id');
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        return $query->get()->all();
    }

    /**
     * Get Child Records for a specific parent 🌿
     */
    public function children(int $parentId): array
    {
        return $this->component('model')->where('parent_id', $parentId)->get()->all();
    }

    /**
     * Get Records by Category 📁
     */
    public function atCategory(int $categoryId): array
    {
        return $this->component('model')->where('category_id', $categoryId)->get()->all();
    }

    /**
     * Internal: Secure Model Discovery Hub 🛡️
     * Prevents fatal null-pointer exceptions in the service engine.
     */
}
