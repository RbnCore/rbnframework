<?php

namespace Rbn\Framework\Core\Database;

use Rbn\Framework\Core\Database\Engine\Traits\ConnectionTrait;
use Rbn\Framework\Core\Database\Engine\Traits\ExecutionTrait;
use Rbn\Framework\Core\Database\Engine\Traits\TransactionTrait;
use Rbn\Framework\Core\Database\Engine\Traits\QueryBridgeTrait;

/**
 * Database - High-Performance Connection Orchestrator
 * 
 * Manages multiple database connections using a Trait-Driven Architecture.
 * Part of the "RBN Framework" framework pillars.
 */
class Database
{
    use ConnectionTrait;
    use ExecutionTrait;
    use TransactionTrait;
    use QueryBridgeTrait;

    /** @var self|null Singleton instance */
    private static ?self $instance = null;
    
    /**
     * Singleton constructor 🧱
     */
    private function __construct()
    {
    }

    /**
     * Get the master database instance 🛰️
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Run a callback inside an atomic transaction 🧱
     *
     * Commit on success, rollback on any Throwable, then rethrow.
     * Nested calls JOIN the outer transaction (no savepoint): the inner call
     * never commits/rolls back on its own, so only the outermost call decides.
     *
     * @template TReturn
     * @param callable(self): TReturn $callback
     * @return TReturn
     */
    public function transaction(callable $callback): mixed
    {
        // Nested: already inside a transaction -> join it, do NOT open a second one.
        if ($this->inTransaction()) {
            return $callback($this);
        }

        $this->beginTransaction();

        try {
            $sonuc = $callback($this);
            $this->commit();
            return $sonuc;
        } catch (\Throwable $e) {
            // Rollback only while still inside the transaction (a failed
            // callback may already have closed it).
            if ($this->inTransaction()) {
                $this->rollback();
            }
            throw $e;
        }
    }

    private function __clone() {}
    public function __wakeup() {}
}
