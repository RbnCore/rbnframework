<?php

namespace Rbn\Framework\Core\Database\Engine\Providers;

use Rbn\Framework\Core\Database\Database;
use Rbn\Framework\Core\Support\Contracts\Database\SchemaProviderInterface;

/**
 * SchemaBuilder - DDL & Maintenance Engine 🎻🛠️
 * 
 * Part of the RBN Framework v3.0 Modern Engine.
 * Handles table-level structural operations and database maintenance fluently.
 */
class SchemaBuilder implements SchemaProviderInterface
{
    protected Database $db;
    protected string $table;
    protected string $connectionName = 'default';

    public function __construct(Database $db, string $table)
    {
        $this->db = $db;
        $this->table = $table;
    }

    /**
     * D-23: Kimlik (tablo adı) tek geçitten backtick'li ve kaçışlı yazılır.
     */
    protected function ident(string $name): string
    {
        return '`' . str_replace(['`', "\0"], ['``', ''], $name) . '`';
    }

    /**
     * Switch connection context
     */
    public function connection(string $name): static
    {
        $this->connectionName = $name;
        return $this;
    }

    /**
     * Drop the table completely ⚠️
     */
    public function drop(): bool
    {
        return $this->db->connection($this->connectionName)->rawExecute("DROP TABLE {$this->ident($this->table)}");
    }

    /**
     * Truncate (empty) the table
     */
    public function truncate(): bool
    {
        return $this->db->connection($this->connectionName)->rawExecute("TRUNCATE TABLE {$this->ident($this->table)}");
    }

    /**
     * Run MySQL Optimization on the table 🧹
     */
    public function optimize(): bool
    {
        return $this->db->connection($this->connectionName)->rawExecute("OPTIMIZE TABLE {$this->ident($this->table)}");
    }

    /**
     * Convert table collation and character set 🔠
     */
    public function convert(string $collation): bool
    {
        // D-24: collation SQL'e girer; yalnız [a-z0-9_] ve sunucunun bildiği (SHOW COLLATION) değerler.
        if (!preg_match('/^[a-z0-9]+_[a-z0-9_]+$/i', $collation)) {
            return false;
        }
        $known = $this->db->connection($this->connectionName)->raw("SHOW COLLATION LIKE ?", [$collation]);
        if (empty($known)) {
            return false;
        }
        $charset = explode('_', $collation)[0];
        return $this->db->connection($this->connectionName)->rawExecute(
            "ALTER TABLE {$this->ident($this->table)} CONVERT TO CHARACTER SET {$charset} COLLATE {$collation}"
        );
    }

    /**
     * Rename the table 🏷️
     */
    public function rename(string $newName): bool
    {
        $status = $this->db->connection($this->connectionName)->rawExecute(
            "RENAME TABLE {$this->ident($this->table)} TO {$this->ident($newName)}"
        );
        if ($status) {
            $this->table = $newName;
        }
        return $status;
    }

    /**
     * Check if table exists in the current database
     */
    public function exists(): bool
    {
        $dbName = $this->db->connection($this->connectionName)->rawFirst("SELECT DATABASE() as db")['db'] ?? '';
        $res = $this->db->connection($this->connectionName)->rawFirst(
            "SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = ? AND table_name = ?",
            [$dbName, $this->table]
        );
        return (bool)($res['count'] ?? 0);
    }

    /**
     * Get a list of column names for the table
     */
    public function getColumnListing(): array
    {
        $raw = $this->db->connection($this->connectionName)->raw("SHOW COLUMNS FROM {$this->ident($this->table)}");
        return array_column($raw, 'Field');
    }

    /**
     * Get the current database name 🌐
     */
    public function getDatabaseName(): string
    {
        $res = $this->db->connection($this->connectionName)->rawFirst("SELECT DATABASE() as db");
        return $res['db'] ?? 'Unknown';
    }

    /**
     * Get primary key for the target table 🔑
     */
    public function getPrimaryKey(): string
    {
        $res = $this->db->connection($this->connectionName)->rawFirst(
            "SHOW KEYS FROM {$this->ident($this->table)} WHERE Key_name = 'PRIMARY'"
        );
        return $res['Column_name'] ?? 'id';
    }

    /**
     * Get CREATE TABLE schema for the target table 🛠️
     */
    public function getCreateSql(): string
    {
        $res = $this->db->connection($this->connectionName)->rawFirst(
            "SHOW CREATE TABLE {$this->ident($this->table)}"
        );
        return $res['Create Table'] ?? '';
    }

    /**
     * Get status for the target table (or all tables) 📊
     */
    public function getStatus(?string $table = null): array
    {
        $target = $table ?: $this->table;
        if ($table === null && empty($this->table)) {
            return $this->db->connection($this->connectionName)->raw("SHOW TABLE STATUS");
        }

        // If target is specific, we still use full status but can filter if needed.
        // For MySQL, SHOW TABLE STATUS LIKE 'table' is common.
        if ($target) {
            return $this->db->connection($this->connectionName)->raw("SHOW TABLE STATUS LIKE ?", [$target]);
        }

        return $this->db->connection($this->connectionName)->raw("SHOW TABLE STATUS");
    }

    /**
     * Get the current table name
     */
    public function getTableName(): string
    {
        return $this->table;
    }
}
