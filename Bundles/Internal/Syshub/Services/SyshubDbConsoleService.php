<?php

declare(strict_types=1);

namespace Rbn\Framework\Bundles\Internal\Syshub\Services;

use Rbn\Framework\Core\Base\Services\BaseService;

/**
 * SyshubDbConsoleService - Database Orchestrator Hub 🛠️🛰️⚓
 * RBN 3.5 Masterpiece Standard.
 * 
 * @property \Rbn\Framework\Bundles\Internal\Syshub\Handlers\SyshubDbConsoleHandler $syshubDbConsoleHandler
 * @property \Rbn\Framework\Bundles\Internal\Syshub\Providers\SyshubDbConsoleProvider $syshubDbConsoleProvider
 */
class SyshubDbConsoleService extends BaseService
{
    /* ==========================================================================
       [ PROVIDER PROXIES ] - Fluent Data Access
       ========================================================================== */

    public function getInfo(): array
    {
        return $this->syshubDbConsoleProvider->getInfo();
    }

    public function getTables(): array
    {
        return $this->syshubDbConsoleProvider->getTables();
    }

    public function getRows(string $table, int $limit = 100): array
    {
        return $this->syshubDbConsoleProvider->getRows($table, $limit);
    }

    public function getMetadata(string $table): array
    {
        return $this->syshubDbConsoleProvider->getMetadata($table);
    }

    /* ==========================================================================
       [ HANDLER PROXIES ] - Fluent Action Execution
       ========================================================================== */

    public function executeQuery(string $sql): array
    {
        return $this->syshubDbConsoleHandler->executeQuery($sql);
    }

    public function optimize(?string $table = null): bool
    {
        return $this->syshubDbConsoleHandler->optimize($table);
    }

    public function cleanInstall(): bool
    {
        return $this->syshubDbConsoleHandler->cleanInstall();
    }

    public function changeCollation(string $table, string $collation): bool
    {
        return $this->syshubDbConsoleHandler->changeCollation($table, $collation);
    }

    public function dropTable(string $table): bool
    {
        return $this->syshubDbConsoleHandler->dropTable($table);
    }

    public function deleteRows(string $table, array $ids): bool
    {
        return $this->syshubDbConsoleHandler->deleteRows($table, $ids);
    }

    public function exportSql(string $table, ?array $ids = null): string
    {
        return $this->syshubDbConsoleHandler->exportSql($table, $ids);
    }

    /**
     * Seed initial admin account 👥
     */
    public function seedAdmin(): bool
    {
        return (bool) $this->service('masterAccount')->seedAdmin();
    }
}
