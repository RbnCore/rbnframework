<?php

namespace Rbn\Framework\Core\Services\Console\Services;

use Rbn\Framework\Core\Database\Database;
use Rbn\Framework\Core\System\Paths\Paths;

class MigrationService
{
    protected Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function migrate(): array
    {
        $this->ensureMigrationsTable();
        $files = $this->getPendingMigrations();

        if (empty($files)) {
            return ['status' => 'nothing_to_migrate'];
        }

        $migrated = [];
        $batch = $this->getNextBatch();

        foreach ($files as $file) {
            $class = $this->loadMigrationFile($file);
            $migration = new $class();
            $migration->up();

            $this->db->rawExecute("INSERT INTO migrations (migration, batch) VALUES (?, ?)", [$file, $batch]);
            $migrated[] = $file;
        }

        return ['status' => 'success', 'migrated' => $migrated];
    }

    public function rollback(): array
    {
        $this->ensureMigrationsTable();
        $batch = $this->getLastBatch();
        if (!$batch) {
            return ['status' => 'nothing_to_rollback'];
        }

        $migrations = $this->db->raw("SELECT migration FROM migrations WHERE batch = ? ORDER BY migration DESC", [$batch]);
        $rolledBack = [];

        foreach ($migrations as $m) {
            $file = $m['migration'];
            $class = $this->loadMigrationFile($file);
            $migration = new $class();
            $migration->down();

            $this->db->rawExecute("DELETE FROM migrations WHERE migration = ?", [$file]);
            $rolledBack[] = $file;
        }

        return ['status' => 'success', 'rolled_back' => $rolledBack];
    }

    public function getStatus(): array
    {
        $this->ensureMigrationsTable();
        return $this->db->raw("SELECT migration, batch FROM migrations ORDER BY migration DESC");
    }

    protected function ensureMigrationsTable(): void
    {
        $this->db->rawExecute("CREATE TABLE IF NOT EXISTS migrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(255) NOT NULL,
            batch INT NOT NULL
        )");
    }

    protected function getPendingMigrations(): array
    {
        $path = Paths::project()->root('database/migrations');
        if (!is_dir($path))
            return [];

        $all = glob($path . '/*.php');
        $executed = array_column($this->db->raw("SELECT migration FROM migrations"), 'migration');

        $pending = [];
        foreach ($all as $file) {
            $name = basename($file, '.php');
            if (!in_array($name, $executed)) {
                $pending[] = $name;
            }
        }
        return $pending;
    }

    protected function getNextBatch(): int
    {
        $res = $this->db->rawFirst("SELECT MAX(batch) as max_batch FROM migrations");
        return ($res['max_batch'] ?? 0) + 1;
    }

    protected function getLastBatch(): ?int
    {
        $res = $this->db->rawFirst("SELECT MAX(batch) as max_batch FROM migrations");
        return $res['max_batch'] ?? null;
    }

    protected function loadMigrationFile(string $name): string
    {
        $path = Paths::project()->root("database/migrations/{$name}.php");
        require_once $path;

        // Class name logic (Simplified: 2024_01_01_000000_CreateUsersTable -> CreateUsersTable)
        $parts = explode('_', $name);
        $classParts = array_slice($parts, 4);
        return str_replace('_', '', ucwords(implode('_', $classParts), '_'));
    }
}
