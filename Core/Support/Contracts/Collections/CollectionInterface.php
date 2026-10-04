<?php

namespace Rbn\Framework\Core\Support\Contracts\Collections;

/**
 * CollectionInterface - The Fluent Data Motor Contract 🔱🧬
 */
interface CollectionInterface
{
    /**
     * Get all items in the collection.
     */
    public function all(): array;

    /**
     * Filter items by key, operator and value.
     */
    public function where(string $key, $operator, $value = null): self;

    /**
     * Transform each item in the collection via callback.
     */
    public function map(callable $callback): self;

    /**
     * Filter the collection via callback.
     */
    public function filter(callable $callback): self;

    /**
     * Extract a single column from the items.
     */
    public function pluck(string $key): self;

    /**
     * Sort the collection by a given key.
     */
    public function sortBy(string $key): self;

    /**
     * Sort the collection by a given key in descending order.
     */
    public function sortByDesc(string $key): self;

    /**
     * Group the collection by a given key.
     */
    public function groupBy(string $key): self;

    /**
     * Get the first item in the collection.
     */
    public function first();

    /**
     * Get the last item in the collection.
     */
    public function last();

    /**
     * Get the count of items in the collection.
     */
    public function count(): int;

    /**
     * Merge the collection with a given array or collection. ✨
     */
    public function merge($items): self;

    /**
     * Add an item to the collection (Alias for push). ✨
     */
    public function add($item): self;

    /**
     * Push an item onto the end of the collection. ✨
     */
    public function push($item): self;

    /**
     * Convert the collection to a plain array.
     */
    public function toArray(): array;
}
