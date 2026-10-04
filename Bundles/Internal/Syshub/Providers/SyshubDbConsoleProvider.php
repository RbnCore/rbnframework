<?php

declare(strict_types=1);

namespace Rbn\Framework\Bundles\Internal\Syshub\Providers;

use Rbn\Framework\Core\Base\BaseComponent;

/**
 * SyshubDbConsoleProvider - Database Data Discovery 🛰️⚓
 * RBN 3.5 Masterpiece Standard.
 */
class SyshubDbConsoleProvider extends BaseComponent
{
    /**
     * Get Database Metrics & Info 📊
     */
    public function getInfo(): array
    {
        $model = $this->model('schemaDoctor');
        $tables = $this->getTables();

        $totalSize = array_sum(array_column($tables, 'size'));
        $totalRecords = array_sum(array_column($tables, 'rows'));

        return [
            'name' => $model->getCurrentDbName(),
            'size' => $this->rbn->helper('format')->formatFileSize($totalSize),
            'tables' => count($tables),
            'records' => $totalRecords,
            'type' => 'MySQL'
        ];
    }

    /**
     * Get All Tables Status 📋
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
     * Get Rows from specific table 🔍
     */
    public function getRows(string $table, int $limit = 100): array
    {
        return $this->model('schemaDoctor')->getRows($table, $limit);
    }

    /**
     * Get Table Metadata 🧪
     */
    public function getMetadata(string $table): array
    {
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
            'primaryKey' => $model->getTablePrimaryKey($table)
        ];
    }
}
