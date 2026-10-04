<?php
/**
 * RBN Framework 3.0: High-Performance Database Engine 🎻🔐
 */
namespace Rbn\Framework\Core\Database\Engine\Traits;

/**
 * TransactionTrait - The Atomic Controller 🔐
 * 
 * Manages database transactions via the active provider.
 */
trait TransactionTrait
{
    /**
     * Start a database transaction 🧱
     */
    public function beginTransaction(): bool
    {
        return $this->getProvider($this->activeConnection)->beginTransaction();
    }

    /**
     * Commit a database transaction ✅
     */
    public function commit(): bool
    {
        return $this->getProvider($this->activeConnection)->commit();
    }

    /**
     * Rollback a database transaction ❌
     */
    public function rollback(): bool
    {
        return $this->getProvider($this->activeConnection)->rollback();
    }

    /**
     * Is a transaction currently open on the active connection? 🔍
     */
    public function inTransaction(): bool
    {
        return $this->getProvider($this->activeConnection)->inTransaction();
    }
}
