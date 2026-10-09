<?php

namespace Rbn\Framework\Core\Database\Models;

use Rbn\Framework\Core\Base\Data\BaseModel;

/**
 * SchemaDoctorModel - The structural health and maintenance engine for the database.
 * Provides high-level methods for system-wide DB maintenance and schema metadata.
 */
class SchemaDoctorModel extends BaseModel
{
    /**
     * For database-wide operations, no specific table is required by default,
     * but we can use any table for raw DB reference.
     *
     * [D-30 / FW-ALTYAPI-1 A-02] Bu model TABLOSUZDUR; aşağıdaki `getTable()`
     * geçersiz kılması bu niyeti SÖZLEŞMEYE taşır. `BaseModel::getTable()`
     * imzası `string` olduğu için ve `strict_types=1` altında `null`
     * dönmek `TypeError` demektir — yani bu model bir "hata" idi, ölçülemez
     * bir hata. Artık çağıran NİYETİ YAZAN bir hata alır ve doğru yola
     * (`getTableOrNull()`) yönlendirilir.
     */
    protected $table = null;

    /**
     * [D-30] Tablo adı YOKTUR — isteyen çağıranı bilinçli şekilde durdur 🛑
     *
     * İmza `string` olarak KORUNUR (`BaseModelInterface` sözleşmesi ve
     * `strict_types` gereği `?string` her çağıranı kırardı).
     *
     * @throws \RuntimeException Her çağrıda: bu model bir tabloya bağlı değildir.
     */
    public function getTable(): string
    {
        throw new \RuntimeException(
            static::class . ' bir tabloya bagli degildir (getTable() cagrilamaz). '
            . 'Tablo bazli islemler icin: getTableOrNull() === null bekleyin ya da '
            . 'belirttiginiz tablo adini metoda gecin '
            . '(or. getRows($tablo), getTablePrimaryKey($tablo), getTableSchema($tablo)).'
        );
    }

    /**
     * [D-30] Bu model için daima `null` döner (tablosuz).
     */
    public function getTableOrNull(): ?string
    {
        return null;
    }

    /**
     * Get all tables in the current connection with metadata 📊
     */
    public function getTableStatus(): array
    {
        return $this->db->schema('')->getStatus();
    }

    /**
     * Get the current active database name 🌐
     */
    public function getCurrentDbName(): string
    {
        return $this->db->schema('')->getDatabaseName();
    }

    /**
     * Get primary key for a specific table 🔑
     */
    public function getTablePrimaryKey(string $table): string
    {
        return $this->db->schema($table)->getPrimaryKey();
    }

    /**
     * Get CREATE TABLE schema for a table 📄
     */
    public function getTableSchema(string $table): string
    {
        return $this->db->schema($table)->getCreateSql();
    }

    /**
     * Run Optimization on a table 🧹
     */
    public function optimize(string $table): bool
    {
        return $this->db->schema($table)->optimize();
    }

    /**
     * Convert table collation 🔠
     */
    public function convertCollation(string $table, string $collation): bool
    {
        return $this->db->schema($table)->convert($collation);
    }

    /**
     * Drop a table completely ⚠️
     */
    public function dropTable(string $table): bool
    {
        return $this->db->schema($table)->drop();
    }

    /**
     * Truncate a table (Empty all data)
     */
    public function truncateTable(string $table): bool
    {
        return $this->db->schema($table)->truncate();
    }

    /**
     * Execute a raw SQL query with timing
     */
    public function executeRaw(string $sql): array
    {
        $start = microtime(true);
        $type = (stripos(trim($sql), 'SELECT') === 0 || stripos(trim($sql), 'SHOW') === 0 || stripos(trim($sql), 'EXPLAIN') === 0) ? 'SELECT' : 'EXECUTE';

        $data = [];
        $affected = 0;

        if ($type === 'SELECT') {
            $data = $this->db->raw($sql);
        } else {
            $stmt = $this->db->query($sql);
            $affected = $stmt->rowCount();
        }

        $time = round((microtime(true) - $start) * 1000, 2);

        return [
            'type' => $type,
            'data' => $data,
            'affected' => $affected,
            'time' => $time
        ];
    }

    /**
     * Get all rows from a table with optional limit
     */
    public function getRows(string $table, int $limit = 100): array
    {
        return $this->db->table($table)->limit($limit)->rows();
    }

    /**
     * Bulk delete rows from a table using primary key
     */
    public function deleteRows(string $table, array $ids, ?string $pk = null): bool
    {
        if (empty($ids))
            return false;
        $primaryKey = $pk ?? $this->getTablePrimaryKey($table);
        return $this->db->table($table)->whereIn($primaryKey, $ids)->delete() > 0;
    }

    /**
     * Get table data for export
     */
    public function getTableData(string $table, ?array $ids = null): array
    {
        $query = $this->db->table($table);
        if ($ids) {
            $query->whereIn($this->getTablePrimaryKey($table), $ids);
        }
        return $query->rows();
    }

    /**
     * Get PDO instance for quoting
     */
    public function getPdo()
    {
        return $this->db->getPdo();
    }
}
