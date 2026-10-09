<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Support\Contracts\Base;

use Rbn\Framework\Core\Support\Contracts\Discovery\DiscoveryInterface;

/**
 * BaseModelInterface - The Contract for RBN Framework Models 🎻⚖️
 * 
 * Defines the standard API for data orchestration and query building.
 */
interface BaseModelInterface extends DiscoveryInterface
{
    /**
     * Start a fresh fluent query (Muscle Spoke) 🏎️
     */
    public function query(): object;

    /**
     * Create a new record 📦
     */
    public function create(array $data): int|bool;
 
    /**
     * Update an existing record ⚙️
     */
    public function update($id, array $data): bool;
 
    /**
     * Delete a record by primary key 🗑️
     */
    public function destroy($id): bool;

    /**
     * Standard Save (Smart Insert or Update) 🚀
     */
    public function save(array $data): bool|int;

    /**
     * Standard Toggle Status (Atomic Switch) 🔄
     */
    public function toggleStatus($id, string $field = 'is_active'): bool;

    /**
     * Delete multiple records 🗑️🚀
     */
    public function deleteBulk(array $ids): int;

    /**
     * Update multiple records ⚙️⚡
     */
    public function updateBulk(array $ids, array $data): int;

    /**
     * Toggle multiple records' status 🔄⚡
     */
    public function toggleStatusBulk(array $ids, bool $status, string $field = 'is_active'): int;

    /**
     * Find a single record by ID 🔍
     */
    public function find($id): ?array;
 
    /**
     * Get all records 🌈
     */
    public function all(): array;

    /**
     * Identity Accessors 🏗️
     */
    public function getTable(): string;
    public function getPrimaryKey(): string;

}
