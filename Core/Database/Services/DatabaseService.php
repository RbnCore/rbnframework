<?php

namespace Rbn\Framework\Core\Database\Services;

use Rbn\Framework\Core\Base\Services\BaseService;
use Rbn\Framework\Core\System\Discovery\Clusters\Logic\Definition\Definition;

/**
 * DatabaseService - The Database Management Hub 🛠️
 * 
 * RBN Framework 3.0 "Masterpiece" Implementation.
 * Modernized version that provides high-level DB management features.
 * Acts as a fluent API wrapper around the SchemaDoctorModel.
 */
class DatabaseService extends BaseService
{
    /**
     * Fluent Target: Specific table name or null for all tables.
     */
    protected ?string $targetTable = null;

    /**
     * Explicit Service Discovery
     */
    public static function get(): self
    {
        return parent::get()->service('dbConsole');
    }

    /**
     * Fluent API: Targets a specific table 🎯
     */
    public function table(string $name): self
    {
        $this->targetTable = $name;
        return $this;
    }

    /**
     * Fluent API: Targets the entire database 🌐
     */
    public function entire(): self
    {
        $this->targetTable = null;
        return $this;
    }

    /**
     * Get Database Dashboard Metrics 📊
     */
    public function getDashboardInfo(): array
    {
        $model = $this->model('schemaDoctor');
        $tables = $this->getTables();

        $totalSize = array_sum(array_column($tables, 'size'));
        $totalRows = array_sum(array_column($tables, 'rows'));

        return [
            'dbInfo' => [
                'name' => $model->getCurrentDbName(),
                'size' => $this->rbn->helper('format')->formatFileSize($totalSize),
                'tables' => count($tables),
                'records' => $totalRows,
                'type' => 'MySQL'
            ],
            'tables' => $tables
        ];
    }

    /**
     * Optimize targeted table(s) 🧹
     */
    public function optimize(): bool
    {
        $model = $this->model('schemaDoctor');
        $tables = $this->targetTable ? [$this->targetTable] : array_column($this->getTables(), 'name');

        foreach ($tables as $table) {
            $model->optimize($table);
        }

        return true;
    }

    /**
     * Convert targeted table(s) to a specific collation 🔠
     */
    public function convert(string $collation = 'utf8mb4_unicode_ci'): array
    {
        $model = $this->model('schemaDoctor');
        $tables = $this->targetTable ? [$this->targetTable] : array_column($this->getTables(), 'name');

        $results = [];
        foreach ($tables as $table) {
            $results[$table] = $model->convertCollation($table, $collation);
        }

        return $results;
    }

    /**
     * Performs a clean system reinstall (Truncating specific system tables) 🛠️
     */
    public function cleanInstall(): bool
    {
        $model = $this->model('schemaDoctor');
        $tables = Definition::get('database_project', 'CLEANUP_ALLOWED_TABLES') ?? [];

        foreach ($tables as $table) {
            $model->truncateTable($table);
        }

        return true;
    }

    /**
     * Seeds the initial admin account 👥
     */
    public function seedAdmin(): bool
    {
        return (bool) $this->service('masterAccount')->seedAdmin();
    }

    /* ==========================================================================
       [ SQL CONSOLE ] - Direct Query Engine 🖥️
       ========================================================================== */

    /**
     * Executes a raw SQL query and returns formatted result (Data, Type, Time etc.)
     */
    public function executeQuery(string $sql): array
    {
        if (empty(trim($sql))) {
            return ['success' => false, 'message' => 'Sorgu boş olamaz.'];
        }

        try {
            $res = $this->model('schemaDoctor')->executeRaw($sql);

            return [
                'success' => true,
                'type' => $res['type'],
                'data' => $res['data'],
                'affectedRows' => $res['affected'],
                'executionTime' => $res['time'] . ' ms',
                'time_raw' => $res['time'],
                'message' => $res['type'] === 'EXECUTE' ? 'Başarılı.' : null
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Rapid Table Content Retrieval for SQL Console Explorer
     */
    public function getTableContent(string $table, int $limit = 100): array
    {
        // [D-28] BOŞ TABLO ADI KORUMASI: süzgeç (`preg_replace`) yalnız yasak
        // karakterleri siliyordu; boş string ya da yalnız yasak karakterlerden
        // oluşan bir ad geçerse `SELECT * FROM \`\`` üretiliyordu (panelde
        // anlamsız MySQL sözdizimi hatası). Artık süzülmüş ad boşsa istek
        // `executeQuery()` ile AYNI hata şeklinde reddedilir.
        try {
            $safeTable = $this->sanitizeTableName($table);
        } catch (\InvalidArgumentException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }

        $sql = "SELECT * FROM `{$safeTable}` LIMIT {$limit}";

        return $this->executeQuery($sql);
    }

    /**
     * [D-28] Tablo adı süzgeci — boş sonuç REDDEDİLİR.
     *
     * Yalnız `Core/Database` içinde kullanılır; SQL konsolundan gelen ad
     * backtick/bol/kesme gibi karakterlerden arındırılır, ardından boşsa
     * istisna fırlatılır (sessiz geçersiz SQL üretmek yerine).
     *
     * @throws \InvalidArgumentException Süzülmüş ad boşsa.
     */
    protected function sanitizeTableName(string $table): string
    {
        $temiz = preg_replace('/[^a-zA-Z0-9_]/', '', $table) ?? '';
        if ($temiz === '') {
            throw new \InvalidArgumentException(
                'Geçersiz tablo adı. Tablo adı en az bir harf, rakam veya alt çizgi içermelidir.'
            );
        }

        return $temiz;
    }

    /* ==========================================================================
       [ TABLE EXPLORER ] - Schema & Row Management 📊
       ========================================================================== */

    /**
     * Get all tables with full metadata
     */
    public function getTables(): array
    {
        $raw = $this->model('schemaDoctor')->getTableStatus();
        $formatHelper = $this->rbn->helper('format');

        return array_map(function ($row) use ($formatHelper) {
            $size = ($row['Data_length'] ?? 0) + ($row['Index_length'] ?? 0);
            return [
                'name' => $row['Name'] ?? (reset($row) ?: ''),
                'rows' => (int) ($row['Rows'] ?? 0),
                'size' => $size,
                'size_formatted' => $formatHelper->formatFileSize($size),
                'engine' => $row['Engine'] ?? '-',
                'collation' => $row['Collation'] ?? '-'
            ];
        }, $raw);
    }

    /**
     * Get all rows from a table (Raw Data)
     */
    public function getRows(?string $table = null, int $limit = 100): array
    {
        $table = $this->resolveTable($table);
        return $this->model('schemaDoctor')->getRows($table, $limit);
    }

    /**
     * Get table specific metadata and primary key
     */
    public function getTableMetadata(?string $table = null): array
    {
        $table = $this->resolveTable($table);
        $model = $this->model('schemaDoctor');
        $tables = $this->getTables();
        $metadata = [];

        foreach ($tables as $t) {
            if ($t['name'] === $table) {
                $metadata = $t;
                break;
            }
        }

        return [
            'metadata' => $metadata,
            'info' => [
                'name' => $model->getCurrentDbName(),
                'type' => 'MySQL'
            ],
            'primaryKey' => $model->getTablePrimaryKey($table)
        ];
    }

    /**
     * Delete a single row
     */
    public function deleteRow(?string $table = null, $id = null): bool
    {
        $table = $this->resolveTable($table);
        return $this->model('schemaDoctor')->deleteRows($table, (array) $id);
    }

    /**
     * Bulk delete rows
     */
    public function bulkDeleteRows(?string $table = null, array $ids = []): bool
    {
        $table = $this->resolveTable($table);
        return $this->model('schemaDoctor')->deleteRows($table, $ids);
    }

    /**
     * Drop a full table ⚠️
     */
    public function dropTable(?string $table = null): bool
    {
        $table = $this->resolveTable($table);
        return $this->model('schemaDoctor')->dropTable($table);
    }

    /**
     * Change table collation
     */
    public function changeTableCollation(?string $table = null, string $collation = 'utf8mb4_unicode_ci'): bool
    {
        $table = $this->resolveTable($table);
        return $this->model('schemaDoctor')->convertCollation($table, $collation);
    }

    /**
     * Export table as SQL
     */
    public function exportTableSql(?string $table = null, ?array $ids = null): string
    {
        $table = $this->resolveTable($table);
        $model = $this->model('schemaDoctor');
        $pdo = $model->getPdo();

        $schema = $model->getTableSchema($table);
        $rows = $model->getTableData($table, $ids);

        $sql = "-- RBN Framework SQL Export\n";
        $sql .= "-- Table: {$table}\n";
        $sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";

        $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
        $sql .= $schema . ";\n\n";

        if (!empty($rows)) {
            $columns = array_keys($rows[0]);
            $colStr = "`" . implode("`, `", $columns) . "`";

            $sql .= "INSERT INTO `{$table}` ({$colStr}) VALUES \n";
            $values = [];
            foreach ($rows as $row) {
                $rowValues = array_map(function ($v) use ($pdo) {
                    if ($v === null)
                        return 'NULL';
                    return $pdo->quote($v);
                }, array_values($row));
                $values[] = "(" . implode(", ", $rowValues) . ")";
            }
            $sql .= implode(",\n", $values) . ";\n";
        }

        return $sql;
    }

    /**
     * Resolve the target table from argument or fluent state
     */
    private function resolveTable(?string $table): string
    {
        $resolved = $table ?: $this->targetTable;
        if (!$resolved) {
            throw new \Exception("Herhangi bir tablo seçilmedi. ->table('ad') kullanın.");
        }
        return $resolved;
    }
}
