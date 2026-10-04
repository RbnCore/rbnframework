<?php
/**
 * RBN Framework 3.0: High-Performance Http Engine 🎻🧱
 */
namespace Rbn\Framework\Core\Http\Engine\Traits\Validator;

use Rbn\Framework\Core\Database\Database;

/**
 * DatabaseRulesTrait - DB-Driven Validation Rules 🧱
 * 
 * Handles relational constraints like unique and exists checks.
 */
trait DatabaseRulesTrait
{
    /**
     * Uniqueness check (ensure value doesn't exist in table) 🔒
     */
    protected function validateUnique(string $field, $value, array $params): void
    {
        if (empty($value) || empty($params)) return;

        $table    = $params[0];
        $column   = $params[1] ?? $field;
        $except   = $params[2] ?? null;
        $idColumn = $params[3] ?? 'id';

        $db    = Database::getInstance();
        $query = "SELECT COUNT(*) as count FROM {$table} WHERE {$column} = ?";
        $qParams = [$value];

        if ($except) {
            $query .= " AND {$idColumn} != ?";
            $qParams[] = $except;
        }

        $result = $db->query($query, $qParams)->fetch();
        if ($result && (int) $result['count'] > 0) {
            $this->addError($field, 'unique', [$table]);
        }
    }

    /**
     * Existence check (ensure value exists in table) 🧱
     */
    protected function validateExists(string $field, $value, array $params): void
    {
        if (empty($value) || empty($params)) return;

        $table  = $params[0];
        $column = $params[1] ?? $field;

        $db    = Database::getInstance();
        $query = "SELECT COUNT(*) as count FROM {$table} WHERE {$column} = ?";

        $result = $db->query($query, [$value])->fetch();
        if (!$result || (int) $result['count'] === 0) {
            $this->addError($field, 'exists', [$table]);
        }
    }
}
