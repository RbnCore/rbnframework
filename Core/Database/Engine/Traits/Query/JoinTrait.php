<?php

namespace Rbn\Framework\Core\Database\Engine\Traits\Query;

/**
 * JoinTrait - The Relational Link Engine ⛓️🛰️⚓
 */
trait JoinTrait
{
    /**
     * Add a generic JOIN to the query ⚓🛰️
     */
    public function join(string $table, string $first, string $operator, string $second, string $type = 'INNER'): static
    {
        $table = trim($table);
        if (preg_match('/^([a-zA-Z0-9_`\.]+)\s+(?:AS\s+)?([a-zA-Z0-9_]+)$/i', $table, $matches)) {
            $formattedTable = "{$matches[1]} AS `{$matches[2]}`";
        } elseif (str_contains($table, '`')) {
            $formattedTable = $table;
        } elseif (str_contains($table, '.')) {
            $parts = explode('.', $table);
            $formattedTable = "`" . trim($parts[0], '`') . "`.`" . trim($parts[1], '`') . "`";
        } else {
            $formattedTable = "`{$table}`";
        }

        $this->joins[] = "{$type} JOIN {$formattedTable} ON {$first} {$operator} {$second}";
        return $this;
    }

    /**
     * Add a LEFT JOIN ⛓️
     */
    public function leftJoin(string $table, string $first, string $operator, string $second): static
    {
        return $this->join($table, $first, $operator, $second, 'LEFT');
    }

    /**
     * Add a RIGHT JOIN ⛓️
     */
    public function rightJoin(string $table, string $first, string $operator, string $second): static
    {
        return $this->join($table, $first, $operator, $second, 'RIGHT');
    }

    /**
     * Add an INNER JOIN 🏎️
     */
    public function innerJoin(string $table, string $first, string $operator, string $second): static
    {
        return $this->join($table, $first, $operator, $second, 'INNER');
    }
}
