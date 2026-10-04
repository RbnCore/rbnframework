<?php

namespace Rbn\Framework\Core\Support\Contracts\Database;

/**
 * SchemaProviderInterface - The DDL Contract 🎻⚙️
 * 
 * Defines the standard for table-level structure and maintenance operations.
 */
interface SchemaProviderInterface
{
    public function drop(): bool;
    
    public function truncate(): bool;
    
    public function optimize(): bool;
    
    public function convert(string $collation): bool;
    
    public function rename(string $newName): bool;
    
    public function exists(): bool;
    
    public function getColumnListing(): array;

    public function getDatabaseName(): string;

    public function getPrimaryKey(): string;

    public function getCreateSql(): string;

    /**
     * Get table status (metadata)
     * @param string|null $table Table name (or null for all)
     */
    public function getStatus(?string $table = null): array;
}
