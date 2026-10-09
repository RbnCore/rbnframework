<?php

namespace Rbn\Framework\Core\Support\Contracts\Database;

use PDO;

/**
 * DbProviderInterface - Standard Database Connection Provider
 * 
 * Defines the contract for different database drivers 
 * (MySQL, SQLite, etc.) in RBN.
 */
interface DbProviderInterface
{
    /**
     * Get the underlying PDO connection
     */
    public function getPdo(): PDO;

    /**
     * Start a transaction
     */
    public function beginTransaction(): bool;

    /**
     * Commit a transaction
     */
    public function commit(): bool;

    /**
     * Rollback a transaction
     */
    public function rollback(): bool;

    /**
     * Is a transaction currently open?
     */
    public function inTransaction(): bool;

    /**
     * Get the last inserted ID
     */
    public function getLastInsertId(): int;
}
