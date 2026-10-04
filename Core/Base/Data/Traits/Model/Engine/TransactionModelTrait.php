<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Base\Data\Traits\Model\Engine;

/**
 * TransactionModelTrait - Atomic Database Transactions 🧬🛡️
 */
trait TransactionModelTrait
{
    /**
     * Wrap logic within a database transaction 🔋
     */
    public function transaction(callable $callback)
    {
        return $this->db->transaction($callback);
    }

    /**
     * Manual Transaction: START 🏁
     */
    public function beginTransaction(): void
    {
        $this->db->beginTransaction();
    }

    /**
     * Manual Transaction: COMMIT ✅
     */
    public function commit(): void
    {
        $this->db->commit();
    }

    /**
     * Manual Transaction: ROLLBACK ❌
     */
    public function rollBack(): void
    {
        $this->db->rollBack();
    }
}
