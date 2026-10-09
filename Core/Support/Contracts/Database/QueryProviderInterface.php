<?php

namespace Rbn\Framework\Core\Support\Contracts\Database;

/**
 * QueryProviderInterface - The Muscle System Contract 🎻⚙️
 * 
 * Defines the standard for any SQL generation engine used by BaseModel.
 */
interface QueryProviderInterface
{
    public function select(string $columns = '*'): static;

    public function where($column, $operator = null, $value = null): static;

    public function orWhere($column, $operator = null, $value = null): static;

    public function whereIn(string $column, array $values): static;

    public function join(string $table, string $first, string $operator, string $second, string $type = 'INNER'): static;

    public function orderBy(string $column, string $direction = 'ASC'): static;

    public function limit(int $limit): static;

    /**
     * Execute the query and return a result set (Collection in RBN) 🚀
     */
    public function get(): mixed;

    public function first(): mixed;

    public function insert(array $data): int;

    public function update(array $data): bool;

    public function delete(): int;

    public function toSql(): string;

    public function getEagerLoads(): array;

    public function increment(string $column, int $amount = 1): bool;

    public function decrement(string $column, int $amount = 1): bool;

    public function exists(): bool;

    public function count(): int;
}
